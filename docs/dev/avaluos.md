# Documentación Técnica - Módulo de Avalúos

## 📋 Tabla de Contenidos

1. Introducción
2. Base de Datos
3. Modelo
4. Controlador
5. Rutas
6. Policies y Permisos
7. Requests
8. Vistas
9. Integración con otros módulos
10. Guía para Desarrolladores
11. Análisis de Calidad y Mejoras (Bugs Detectados)

---

## 🎯 Introducción

El módulo de **Avalúos** gestiona las valoraciones técnicas y económicas de los inmuebles. Su función principal es proporcionar un valor actualizado y respaldado (por un perito o entidad fiscal) que sirva como referencia para el cálculo de la **Base Imponible** del Impuesto de Transmisiones Gratuítas de Bienes (ITGB).

### Propósito
- Registrar valoraciones periciales, comerciales o fiscales de un inmueble.
- Almacenar la evidencia digital (informes PDF/Imágenes) del avalúo.
- Controlar la vigencia de las valoraciones para asegurar que los impuestos se calculen sobre valores actuales.
- Vincular a los peritos (Personas) con las valoraciones realizadas.

### Importancia en el Sistema ITGB
El sistema compara automáticamente el **Valor Catastral** del inmueble con el valor del **Avalúo Vigente** (si existe) y el **Valor Declarado** por el contribuyente. Por ley, el impuesto se calcula sobre el mayor de estos valores. Por tanto, este módulo impacta directamente en la recaudación.

---

## 🗄️ Base de Datos

### Migración: `2025_09_22_122750_create_avaluos_table.php`

**Tabla:** `avaluos`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `inmueble_id` | BIGINT | FK, NOT NULL | El inmueble que está siendo valorado. |
| `perito_id` | BIGINT | FK, NULLABLE | La persona (perito) que realizó el avalúo. |
| `tipo_avaluo` | ENUM | ['Fiscal', 'Comercial', 'Pericial'] | El tipo de valoración. |
| `valor` | DECIMAL(14,2) | NOT NULL | El monto valorado en Bolivianos (Bs). |
| `fecha_avaluo` | DATE | NOT NULL | Fecha en que se realizó el avalúo. |
| `documento_path` | VARCHAR(255) | NULLABLE | Ruta al archivo de respaldo. |
| `estado` | ENUM | ['Vigente', 'Caducado'] | Estado de la valoración. |
| `created_by` | BIGINT | FK | Usuario que creó el registro. |
| `updated_by` | BIGINT | FK | Usuario que actualizó el registro. |
| `created_at` | TIMESTAMP | | Fecha de registro en el sistema. |
| `updated_at` | TIMESTAMP | | Fecha de última actualización. |

**Relaciones y Claves Foráneas:**
- `inmueble_id` referencia a `inmuebles(id)`.
- `perito_id` referencia a `people(id)`.
- `created_by`, `updated_by` referencia a `users(id)`.

---

## 🧩 Modelo

### Modelo: `Avaluo`

**Ubicación:** `app/Models/Avaluo.php`

**Atributos:**
- `$table = 'avaluos'`
- `$fillable`: `['inmueble_id', 'tipo_avaluo', 'fecha_avaluo', 'valor', 'perito_id', 'documento_path', 'estado', 'created_by', 'updated_by']`
- `$casts`:
    - `fecha_avaluo` => `date`
    - `valor` => `decimal:2`
    - `estado` => `string`

**Relaciones:**

```php
// El avalúo pertenece a un inmueble
public function inmueble()
{
    return $this->belongsTo(Inmueble::class);
}

// El avalúo fue realizado por una persona (Perito)
public function perito()
{
    return $this->belongsTo(Person::class, 'perito_id');
}

// Auditoría de creación
public function creador()
{
    return $this->belongsTo(User::class, 'created_by');
}

// Auditoría de edición
public function editor()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

**Helpers:**
- `public static function vigente(int $inmuebleId)`: Método estático que retorna el último avalúo con estado 'Vigente' para un inmueble dado.

---

## 🎮 Controlador

### Controlador: `AvaluoController`

**Ubicación:** `app/Http/Controllers/AvaluoController.php`

Gestiona el ciclo de vida de los avalúos.

**Métodos Principales:**
- `index()`: Muestra la vista principal del listado.
- `list()`: Endpoint AJAX que retorna la lista paginada. Permite filtrar por `catastro` del inmueble (vía relación) o por `inmueble_id` específico.
- `create()`: Muestra el formulario de alta. Carga listas de inmuebles y peritos (personas naturales).
- `store(StoreAvaluoRequest $request)`:
    1. Valida los datos.
    2. Sube el archivo `documento` al disco `public` en la carpeta `avaluos`.
    3. Crea el registro asignando `created_by` y `updated_by` al usuario actual.
- `update(UpdateAvaluoRequest $request, Avaluo $avaluo)`:
    1. Valida los datos.
    2. Si se sube un nuevo documento, elimina el anterior del disco y guarda el nuevo.
    3. Actualiza el registro y el campo `updated_by`.
- `destroy(Avaluo $avaluo)`: Elimina el archivo asociado del disco y borra el registro de la base de datos.
- `download(Avaluo $avaluo)`: Permite la descarga segura del archivo adjunto (`documento_path`).

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // ...
    Route::resource('avaluos', AvaluoController::class)->names('admin.avaluos');
    Route::get('avaluos/ajax/list', [AvaluoController::class, 'list'])->name('admin.avaluos.ajax.list');
    Route::get('avaluos/{avaluo}/download', [AvaluoController::class, 'download'])->name('admin.avaluos.download');
});
```

---

## 🔒 Policies y Permisos

### Policy: `AvaluoPolicy`

**Ubicación:** `app/Policies/AvaluoPolicy.php`

| Método | Permiso Voyager | Descripción |
|---|---|---|
| `viewAny` | `browse_avaluos` | Ver listado. |
| `view` | `read_avaluos` | Ver detalle y descargar archivo. |
| `create` | `add_avaluos` | Crear nuevo avalúo. |
| `update` | `edit_avaluos` | Editar existente. |
| `delete` | `delete_avaluos` | Eliminar avalúo. |

El método `before` otorga acceso total a usuarios con permiso `browse_admin`.

---

## ✅ Requests

### `StoreAvaluoRequest`

**Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php`

**Reglas de Validación:**
- `inmueble_id`: `required|exists:inmuebles,id`
- `tipo_avaluo`: `required|in:Fiscal,Comercial,Pericial`
- `fecha_avaluo`: `required|date`
- `valor`: `required|numeric|min:0`
- `perito_id`: `nullable|exists:people,id`
- `documento`: `nullable|file|mimes:pdf,jpg,png|max:5120` (Máx 5MB)
- `estado`: `in:Vigente,Caducado`

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/avaluos/`

- **`browse.blade.php`**: Contenedor principal del listado.
- **`list.blade.php`**: Tabla cargada vía AJAX. Muestra columnas como Inmueble (Catastro), Perito, Tipo, Valor, Fecha y Estado.
- **`edit-add.blade.php`**: Formulario de creación/edición.
    - Select de Inmuebles.
    - Select de Peritos (filtrado por `person_type = 'Natural'`).
    - Input de archivo para el documento de respaldo.
- **`read.blade.php`**: Vista de detalle.

---

## 🔗 Integración con otros módulos

### 1. Módulo de Inmuebles
- El modelo `Inmueble` tiene la relación `hasMany(Avaluo::class)`.
- Se utiliza el helper `Avaluo::vigente($inmuebleId)` para obtener la valoración actual.

### 2. Módulo de Trámites (Cálculo de Impuestos)
- El servicio `IdtgbCalculator` (o lógica equivalente) debe consultar los avalúos para determinar la base imponible.
- **Lógica de Selección de Base Imponible**:
  ```php
  $valorCatastral = $inmueble->valor_catastral;
  $avaluoVigente = Avaluo::vigente($inmueble->id);
  $valorAvaluo = $avaluoVigente ? $avaluoVigente->valor : 0;
  $valorDeclarado = $tramite->valor_declarado;

  $baseImponible = max($valorCatastral, $valorAvaluo, $valorDeclarado);
  ```

### 3. Módulo de Personas
- Se utiliza para registrar a los peritos tasadores (`perito_id`). El controlador filtra para mostrar solo personas naturales.

---

## 📝 Guía para Desarrolladores

### Obtener el avalúo vigente
Para obtener el avalúo vigente de un inmueble, se debe usar el método estático del modelo:

```php
use App\Models\Avaluo;

$avaluo = Avaluo::vigente($inmueble_id);
if ($avaluo) {
    // Usar $avaluo->valor
}
```

### Gestión de Archivos
El controlador maneja automáticamente la subida y reemplazo de archivos en el disco `public`. Al eliminar un avalúo, el archivo físico también se elimina para no dejar residuos.

---

## 🚨 Análisis de Calidad y Mejoras

A continuación se detallan posibles bugs, inconsistencias y oportunidades de mejora detectadas en el análisis del código actual.

### 🐛 Posibles Bugs / Riesgos

1.  **Cálculo de Vigencia Manual (Riesgo Alto):**
    -   **Problema:** A diferencia de lo que sugería la documentación anterior, el código actual del `AvaluoController@store` **NO calcula automáticamente** el estado 'Vigente'/'Caducado' basado en la fecha. Simplemente guarda lo que viene en el request o el valor por defecto de la base de datos.
    -   **Impacto:** Depende totalmente de que el usuario seleccione el estado correcto manualmente. Si un usuario marca como 'Vigente' un avalúo de hace 5 años, el sistema lo aceptará.
    -   **Solución:** Implementar la lógica de negocio en el `store` y `update` para forzar el estado según la fecha (`$fecha->diffInYears(now()) < 1`), o usar un Observer.

2.  **Validación de Perito (Riesgo Medio):**
    -   **Problema:** Aunque el controlador filtra la lista visualmente (`Person::where('person_type', 'Natural')`), la validación en `StoreAvaluoRequest` solo verifica `exists:people,id`. Un usuario malintencionado podría enviar el ID de una persona jurídica o un menor de edad.
    -   **Solución:** Refinar la regla de validación: `Rule::exists('people', 'id')->where('person_type', 'Natural')`.

3.  **Helper `vigente` Estático:**
    -   **Observación:** El método `Avaluo::vigente($id)` es útil, pero al ser estático dificulta el "Eager Loading" si quisiéramos traer los avalúos vigentes de una lista de 50 inmuebles (problema N+1).
    -   **Mejora:** Convertirlo también en un Scope (`scopeVigente`) o una relación `hasOne` filtrada en el modelo `Inmueble` (`public function avaluoVigente() { return $this->hasOne(Avaluo::class)->where('estado', 'Vigente')->latest('fecha_avaluo'); }`).

### 🚀 Mejoras y Optimizaciones

1.  **Automatización de Caducidad:**
    -   **Mejora:** Crear un comando programado (`Schedule`) que corra cada noche y cambie el estado a 'Caducado' de todos los avalúos cuya `fecha_avaluo` haya superado el año de antigüedad.

2.  **Validación de Archivos:**
    -   **Mejora:** El tamaño máximo de archivo es 5MB (`max:5120`). Considerar si esto es suficiente para informes periciales escaneados de alta resolución.

3.  **Auditoría:**
    -   **Mejora:** El modelo ya tiene `created_by` y `updated_by`. Asegurarse de que las vistas muestren quién cargó el avalúo para fines de trazabilidad administrativa.

---

## 🔍 ANÁLISIS COMPLETO DEL MÓDULO

### 🐛 BUGS Y RIESGOS CRÍTICOS

#### 1. **No se automatiza el estado según la fecha de vigencia**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:66-83` (método `store`)
- **Problema:** El controlador guarda el estado 'Vigente'/'Caducado' tal cual viene del formulario, sin validar si la fecha del avalúo corresponde al estado. Un usuario puede marcar como 'Vigente' un avalúo de hace 5 años.
- **Impacto:** Alto - Puede causar errores en el cálculo de impuestos al usar valores desactualizados.
- **Código actual:**
  ```php
  Avaluo::create(array_merge($request->validated(), [
      'documento_path' => $path,
      'created_by'     => auth()->id(),
      'updated_by'     => auth()->id(),
  ]));
  ```
- **Solución sugerida:** Implementar lógica automática:
  ```php
  $estado = $request->fecha_avaluo->diffInYears(now()) < 1 ? 'Vigente' : 'Caducado';
  ```

#### 2. **Validación de perito no verifica tipo de persona**
- **Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php:21` y `UpdateAvaluoRequest.php:19`
- **Problema:** Solo valida `exists:people,id`, permitiendo enviar ID de personas jurídicas o menores de edad.
- **Impacto:** Medio - Puede asignar como perito a una persona no apta.
- **Código actual:**
  ```php
  'perito_id' => 'nullable|exists:people,id',
  ```
- **Solución sugerida:**
  ```php
  'perito_id' => 'nullable|exists:people,id|exists:people,id,0,person_type,Natural',
  ```

#### 3. **Posible N+1 Query Problem en listado**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:35` y `resources/views/admin/avaluos/list.blade.php:18-26`
- **Problema:** Aunque el controlador hace `with(['inmueble', 'perito'])`, en la vista se accede a `$a->perito->first_name` y `$a->perito->paternal_surname` lo cual está correcto, pero si se agregan más relaciones sin eager loading habrá problemas.
- **Impacto:** Bajo/Medio - Rendimiento en listados grandes.
- **Observación:** Actualmente está bien implementado, pero documentar para futuras mejoras.

#### 4. **Borrado en cascada de inmuebles puede romper referencias**
- **Ubicación:** `database/migrations/2025_09_22_122822_create_avaluos_table.php:16`
- **Problema:** La FK tiene `cascadeOnDelete()`, lo cual es correcto para mantener integridad referencial, pero NO hay validación en el controlador de Inmuebles que prevenga borrar un inmueble que tiene avalúos usados en trámites.
- **Impacto:** Alto - Puede perderse información histórica de valoraciones.
- **Código actual:**
  ```php
  $table->foreignId('inmueble_id')->constrained()->cascadeOnDelete();
  ```
- **Ubicación validación:** `app/Http/Controllers/InmuebleController.php:92-95`
- **Solución sugerida:** Implementar Soft Deletes o bloquear borrado si hay trámites asociados.

#### 5. **Falta validación de unicidad de avalúo vigente**
- **Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php` (falta regla)
- **Problema:** El sistema permite tener múltiples avalúos 'Vigente' para el mismo inmueble simultáneamente.
- **Impacto:** Medio - Puede causar confusión en cuál valor usar.
- **Solución sugerida:** Al crear un nuevo avalúo 'Vigente', marcar como 'Caducado' los anteriores del mismo inmueble, o validar que solo haya uno vigente.

#### 6. **Archivo puede sobrescribirse sin confirmación**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:101-104` (método `update`)
- **Problema:** Al subir un nuevo documento, el anterior se elimina automáticamente sin pedir confirmación.
- **Impacto:** Bajo - Puede perderse información si fue un error.
- **Código actual:**
  ```php
  if ($path) Storage::disk('public')->delete($path);
  $path = $request->file('documento')->store('avaluos', 'public');
  ```
- **Solución sugerida:** Preguntar confirmación o mantener versiones.

---

### 🚀 MEJORAS SUGERIDAS

#### 1. **Implementar Observer para automatizar estado**
- **Ubicación:** Crear `app/Observers/AvaluoObserver.php`
- **Mejora:** Automatizar el cálculo de estado cuando se crea o actualiza un avalúo.
- **Código sugerido:**
  ```php
  public function creating(Avaluo $avaluo)
  {
      $avaluo->estado = $avaluo->fecha_avaluo->diffInYears(now()) < 1 ? 'Vigente' : 'Caducado';
  }
  
  public function creating(Avaluo $avaluo)
  {
      if ($avaluo->estado === 'Vigente') {
          Avaluo::where('inmueble_id', $avaluo->inmueble_id)
                 ->where('estado', 'Vigente')
                 ->where('id', '!=', $avaluo->id)
                 ->update(['estado' => 'Caducado']);
      }
  }
  ```

#### 2. **Crear comando programado para caducidad automática**
- **Ubicación:** Crear `app/Console/Commands/CaducarAvaluosCommand.php`
- **Mejora:** Ejecutar diariamente para cambiar estado de avalúos que cumplen 1 año.
- **Código sugerido:**
  ```php
  public function handle()
  {
      Avaluo::where('estado', 'Vigente')
            ->where('fecha_avaluo', '<', now()->subYear())
            ->update(['estado' => 'Camucado']);
  }
  ```
- **Registrar en:** `app/Console/Kernel.php:17`
- **Código sugerido:**
  ```php
  $schedule->command('avaluos:caducar')->daily();
  ```

#### 3. **Convertir helper estático a Scope**
- **Ubicación:** `app/Models/Avaluo.php:54-60`
- **Mejora:** Facilitar el uso de eager loading y encadenamiento de queries.
- **Código sugerido:**
  ```php
  public function scopeVigente($query, $inmuebleId)
  {
      return $query->where('inmueble_id', $inmuebleId)
                   ->where('estado', 'Vigente')
                   ->latest('fecha_avaluo');
  }
  ```
- **Uso:** `Avaluo::vigente($id)->first()` o `Avaluo::where(...)->vigente($id)`

#### 4. **Agregar Soft Deletes**
- **Ubicación:** Modificar migración y modelo
- **Mejora:** Mantener historial de avalúos eliminados para auditoría.
- **Código en modelo:**
  ```php
  use Illuminate\Database\Eloquent\SoftDeletes;
  use SoftDeletes;
  ```
- **Código en migración:**
  ```php
  $table->softDeletes();
  ```

#### 5. **Implementar Job para procesamiento de archivos**
- **Ubicación:** Crear `app/Jobs/ProcesarDocumentoAvaluoJob.php`
- **Mejora:** Procesar archivos pesados en background para no bloquear la UI.
- **Uso en controlador:**
  ```php
  ProcesarDocumentoAvaluoJob::dispatch($request->file('documento'), $avaluoId);
  ```

#### 6. **Agregar campos adicionales al modelo**
- **Ubicación:** Migración y modelo
- **Mejora:** Capturar más información del avalúo.
- **Campos sugeridos:**
  - `numero_resolucion`: VARCHAR(100) - Número de resolución del avalúo
  - `entidad_certificadora`: VARCHAR(255) - Entidad que emitió el avalúo
  - `observaciones`: TEXT - Comentarios adicionales
  - `fecha_caducidad`: DATE - Fecha explícita de caducidad (puede diferir de fecha_avaluo + 1 año)

#### 7. **Agregar índices en base de datos**
- **Ubicación:** `database/migrations/2025_09_22_122822_create_avaluos_table.php`
- **Mejora:** Optimizar búsquedas frecuentes.
- **Índices sugeridos:**
  ```php
  $table->index(['inmueble_id', 'estado', 'fecha_avaluo']); // Para buscar avalúos vigentes
  $table->index(['perito_id']); // Para buscar por perito
  $table->index(['estado']); // Para filtrar por estado
  $table->index(['fecha_avaluo']); // Para ordenar por fecha
  ```

#### 8. **Implementar API endpoints**
- **Ubicación:** `routes/api.php`
- **Mejora:** Permitir integración con sistemas externos.
- **Endpoints sugeridos:**
  - `GET /api/avaluos/{inmueble_id}/vigente` - Obtener avalúo vigente
  - `GET /api/avaluos/{inmueble_id}/historial` - Historial completo
  - `POST /api/avaluos` - Crear nuevo avalúo (con autenticación)

#### 9. **Agregar sistema de versiones de documentos**
- **Ubicación:** Nueva tabla `avaluo_documentos` o cambiar lógica de storage
- **Mejora:** Mantener historial de documentos cuando se reemplazan.
- **Implementación:** En lugar de sobrescribir, guardar como `avaluos/{avaluo_id}/v{version}_filename.pdf`

#### 10. **Validación más estricta de fechas**
- **Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php`
- **Mejora:** No permitir fechas futuras.
- **Código sugerido:**
  ```php
  'fecha_avaluo' => 'required|date|before_or_equal:today',
  ```

---

### ❌ FALTANTES

#### 1. **No hay tests automatizados**
- **Ubicación:** No existe `tests/Feature/AvaluoTest.php`
- **Faltante:** Tests unitarios y de integración para todos los métodos del controlador.
- **Tests sugeridos:**
  - `test_user_can_create_avaluo`
  - `test_user_cannot_create_avaluo_with_future_date`
  - `test_user_cannot_assign_juridica_person_as_perito`
  - `test_estado_caduca_automatically`
  - `test_user_can_download_document`
  - `test_file_is_deleted_when_avaluo_is_deleted`

#### 2. **No hay API documentation**
- **Ubicación:** No existe documentación de API (Swagger/OpenAPI)
- **Faltante:** Documentación de endpoints para integración externa.

#### 3. **No hay validación de límites de archivos**
- **Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php:22`
- **Faltante:** Validación de que el archivo no esté corrupto o dañado.
- **Mejora:** Validar integridad del archivo PDF.

#### 4. **No hay logging de acciones críticas**
- **Ubicación:** Controlador `AvaluoController.php`
- **Faltante:** Log de eliminaciones, creaciones y actualizaciones para auditoría.
- **Código sugerido:**
  ```php
  \Log::info('Avaluo creado', ['avaluo_id' => $avaluo->id, 'user_id' => auth()->id()]);
  ```

#### 5. **No hay configuración de límite de vigencia**
- **Ubicación:** Código hardcodeado
- **Faltante:** El límite de 1 año para vigencia está hardcodeado.
- **Mejora:** Mover a config `config/avaluos.php`
  ```php
  return [
      'vigencia_meses' => env('AVALUO_VIGENCIA_MESES', 12),
  ];
  ```

#### 6. **No hay notificaciones**
- **Ubicación:** No implementado
- **Faltante:** Notificar cuando un avalúo está próximo a caducar.
- **Implementación:** Job que revisa y envía emails 15 días antes.

#### 7. **No hay validación de valor mínimo**
- **Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php:20`
- **Faltante:** Validar que el valor no sea irracionalmente bajo (ej: menor a 100 Bs).
- **Código sugerido:**
  ```php
  'valor' => 'required|numeric|min:100|max:999999999999.99',
  ```

#### 8. **No hay migración para optimizar documento_path**
- **Ubicación:** Migración actual
- **Faltante:** El campo `documento_path` es VARCHAR(250), debería ser TEXT para paths largos o usar UUID para nombres de archivos.

#### 9. **No hay seed para producciones**
- **Ubicación:** Solo existe `AvaluoSeeder.php` para desarrollo
- **Faltante:** Seeder con datos realistas para producción/testing.

#### 10. **No hay internacionalización**
- **Ubicación:** Hardcoded en español
- **Faltante:** Uso de Laravel localization para soportar múltiples idiomas en el futuro.

---

### ⚡ OPTIMIZACIONES

#### 1. **Optimizar consulta en método list()**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:35-39`
- **Problema:** No hay límite de resultados por página por defecto.
- **Optimización actual:** Ya tiene paginación configurable ✓
- **Sugerencia:** Agregar cache para búsquedas frecuentes:
  ```php
  $data = Avaluo::with(['inmueble', 'perito'])
      ->remember(now()->addMinutes(30)) // Si usas cache
      ->when($search, fn($q) => $q->whereHas(...))
      ->paginate($paginate);
  ```

#### 2. **Caching de avalúos vigentes**
- **Ubicación:** `app/Models/Avaluo.php:54-60`
- **Problema:** Consulta repetitiva en trámites.
- **Optimización sugerida:**
  ```php
  public static function vigente(int $inmuebleId): ?self
  {
      return Cache::remember("avaluo:vigente:{$inmuebleId}", now()->addHours(6), function() use ($inmuebleId) {
          return self::where('inmueble_id', $inmuebleId)
                     ->where('estado', 'Vigente')
                     ->latest('fecha_avaluo')
                     ->first();
      });
  }
  ```

#### 3. **Optimizar carga de listas de peritos**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:62` y `:92`
- **Problema:** Carga TODOS los peritos cada vez.
- **Optimización sugerida:** Usar lazy loading o search API para select2 con muchos registros:
  ```php
  'peritos' => collect(), // Vacío, cargar via AJAX
  ```

#### 4. **Optimizar búsqueda en list()**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:36`
- **Problema:** Búsqueda solo por catastro del inmueble.
- **Optimización sugerida:** Agregar búsqueda por perito:
  ```php
  ->when($search, fn($q) => $q->whereHas('inmueble', fn($b) => 
      $b->where('catastro', 'like', "%{$search}%")
  )->orWhereHas('perito', fn($p) => 
      $p->where('first_name', 'like', "%{$search}%")
         ->orWhere('paternal_surname', 'like', "%{$search}%")
  ))
  ```

#### 5. **Usar Chunk para operaciones masivas**
- **Ubicación:** Cualquier operación que procese muchos registros
- **Optimización:** Si se implementa el comando de caducidad:
  ```php
  Avaluo::where('estado', 'Vigente')
        ->where('fecha_avaluo', '<', now()->subYear())
        ->chunk(100, function ($avaluos) {
            foreach ($avaluos as $avaluo) {
                $avaluo->update(['estado' => 'Caducado']);
            }
        });
  ```

#### 6. **Comprimir documentos antes de guardar**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:72`
- **Optimización:** Reducir tamaño de archivos.
- **Implementación:** Usar librería como `spatie/pdf-to-image` o similar.

#### 7. **Usar Queue para procesamiento de archivos**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:72`
- **Optimización:** No bloquear la request mientras se sube.
- **Implementación:** Usar Laravel Queues:
  ```php
  $path = $request->file('documento')->store('avaluos', 'public');
  dispatch(new ProcesarAvaluoJob($path));
  ```

---

### 🔒 SEGURIDAD

#### 1. **Validar tipos MIME más estrictamente**
- **Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php:22`
- **Problema:** Solo valida extensión, no contenido real.
- **Mejora sugerida:**
  ```php
  'documento' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:5120',
  ```
  Y además validar el contenido real del archivo en el controlador.

#### 2. **Sanitizar nombres de archivos**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:72`
- **Problema:** El nombre original del archivo se usa en el path.
- **Mejora sugerida:**
  ```php
  $fileName = Str::uuid() . '.' . $request->file('documento')->getClientOriginalExtension();
  $path = $request->file('documento')->storeAs('avaluos', $fileName, 'public');
  ```

#### 3. **Agregar validación de CSRF**
- **Ubicación:** Vistas `edit-add.blade.php:14`
- **Estado:** ✓ Ya implementado con `@csrf`

#### 4. **Validar permisos en download**
- **Ubicación:** `app/Http/Controllers/AvaluoController.php:132`
- **Estado:** ✓ Ya implementado con `authorize('view', $avaluo)`

#### 5. **Agregar rate limiting**
- **Ubicación:** Rutas en `routes/web.php`
- **Faltante:** Prevenir abuse en endpoints públicos si se crea API.
- **Implementación sugerida:**
  ```php
  Route::middleware('throttle:60,1')->group(function () {
      Route::get('avaluos/ajax/list', [AvaluoController::class, 'list']);
  });
  ```

---

### 📍 UBICACIÓN DE ARCHIVOS DEL MÓDULO

#### Estructura de archivos:

```
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── AvaluoController.php              # Controlador principal
│   │   └── Requests/
│   │       ├── StoreAvaluoRequest.php             # Validación de creación
│   │       └── UpdateAvaluoRequest.php           # Validación de actualización
│   ├── Models/
│   │   └── Avaluo.php                             # Modelo Eloquent
│   └── Policies/
│       └── AvaluoPolicy.php                      # Politicas de permisos
├── database/
│   ├── migrations/
│   │   └── 2025_09_22_122822_create_avaluos_table.php
│   └── seeders/
│       └── AvaluoSeeder.php
├── resources/
│   └── views/
│       └── admin/
│           └── avaluos/
│               ├── browse.blade.php               # Página principal
│               ├── list.blade.php                 # Tabla AJAX
│               ├── edit-add.blade.php             # Formulario crear/editar
│               └── read.blade.php                 # Vista detalle
└── docs/
    └── dev/
        └── avaluos.md                             # Esta documentación
```

#### Archivos relacionados:

- `app/Models/Inmueble.php:51-60` - Relación con inmuebles
- `app/Http/Controllers/InmuebleController.php:92-95` - Validación de borrado
- `routes/web.php:120-122` - Definición de rutas
- `database/seeders/PermissionsTableSeeder.php:125-126` - Permisos
- `database/seeders/IdtgbMenuAppendSeeder.php:45` - Menú de navegación
- `resources/views/admin/inmuebles/read.blade.php:95-104` - Vista de avalúo en inmueble