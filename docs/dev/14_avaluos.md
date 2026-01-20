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

## 🚨 Análisis de Calidad y Mejoras v2.0.0

### 🐛 Bugs Corregidos ✅

1. **Falta de autorización en todos los métodos del controlador**
   - **Ubicación:** `app/Http/Controllers/AvaluoController.php`
   - **Corrección:** Se agregaron llamadas a `authorize()` en todos los métodos
   - **Métodos actualizados:**
     - `index()` - authorize('viewAny', Avaluo::class)
     - `list()` - authorize('viewAny', Avaluo::class)
     - `show()` - authorize('view', $avaluo)
     - `create()` - authorize('create', Avaluo::class)
     - `store()` - authorize('create', Avaluo::class)
     - `edit()` - authorize('update', $avaluo)
     - `update()` - authorize('update', $avaluo)
     - `destroy()` - authorize('delete', $avaluo)
     - `download()` - authorize('view', $avaluo)

2. **Falta de campos de auditoría en registros**
   - **Ubicación:** `app/Http/Controllers/AvaluoController.php:75-79, 106-109`
   - **Corrección:** Se agregaron campos `created_by` y `updated_by` en todos los métodos de creación/actualización
   - **Código en store():**
     ```php
     Avaluo::create(array_merge($request->validated(), [
         'documento_path' => $path,
         'created_by'     => auth()->id(),
         'updated_by'     => auth()->id(),
     ]));
     ```
   - **Código en update():**
     ```php
     $avaluo->update(array_merge($request->validated(), [
         'documento_path' => $path,
         'updated_by'     => auth()->id(),
     ]));
     ```

3. **Descarga de archivos sin verificación de permisos**
   - **Ubicación:** `app/Http/Controllers/AvaluoController.php:130-139`
   - **Corrección:** Se agregó `authorize('view', $avaluo)` en el método `download()` para asegurar que solo usuarios autorizados puedan descargar archivos
   - **Código:**
     ```php
     public function download(Avaluo $avaluo)
     {
         $this->authorize('view', $avaluo);

         if (!$avaluo->documento_path) {
             abort(404, 'Archivo no encontrado');
         }

         return Storage::disk('public')->download($avaluo->documento_path);
     }
     ```

4. **Archivos físicos no eliminados al borrar registros**
   - **Ubicación:** `app/Http/Controllers/AvaluoController.php:116-127`
   - **Corrección:** Se agregó eliminación del archivo físico en el método `destroy()` antes de borrar el registro de BD
   - **Código:**
     ```php
     public function destroy(Avaluo $avaluo)
     {
         $this->authorize('delete', $avaluo);

         if ($avaluo->documento_path) {
             Storage::disk('public')->delete($avaluo->documento_path);
         }
         $avaluo->delete();

         return redirect()->route('admin.avaluos.index')
             ->with(['message' => 'Avalúo eliminado.', 'alert-type' => 'success']);
     }
     ```

5. **Gestión de archivos en actualizaciones**
   - **Ubicación:** `app/Http/Controllers/AvaluoController.php:100-104`
   - **Corrección:** Se implementa eliminación del archivo anterior antes de guardar el nuevo en el método `update()`
   - **Código:**
     ```php
     $path = $avaluo->documento_path;
     if ($request->hasFile('documento')) {
         if ($path) Storage::disk('public')->delete($path);
         $path = $request->file('documento')->store('avaluos', 'public');
     }
     ```

### 🚀 Mejoras Implementadas 🚀

1. **Seguridad en descargas de archivos**
   - Implementación de authorize() en método download()
   - Verificación de existencia del archivo antes de intentar descargar
   - Mensajes de error claros para archivo no encontrado

2. **Auditoría completa de cambios**
   - Registro de usuario creador (created_by) en todas las creaciones
   - Registro de usuario actualizador (updated_by) en todas las actualizaciones
   - Trazabilidad completa de quién modificó cada avalúo

3. **Limpieza de archivos huérfanos**
   - Eliminación automática de archivos físicos al borrar registros
   - Eliminación de archivos anteriores al actualizar con nuevo documento
   - Evita acumulación de basura en el sistema de archivos

4. **Uso de FormRequests dedicados**
   - Implementación de `StoreAvaluoRequest` y `UpdateAvaluoRequest`
   - Centralización de reglas de validación
   - Separación de lógica de validación de la lógica del controlador

5. **Manejo de errores robusto**
   - Bloques try-catch en métodos críticos (list)
   - Mensajes de error descriptivos para el usuario
   - Manejo apropiado de excepciones

### 📝 Historial de Cambios
### Versión 2.0.0 (Enero 2026)
**Correcciones:**
- Agregado authorize() en todos los métodos del controlador (index, list, show, create, store, edit, update, destroy, download)
- Implementados campos de auditoría created_by y updated_by en store() y update()
- Agregada verificación de permisos en método download()
- Implementada eliminación de archivos físicos en destroy() y update()
- Implementado manejo de errores con try-catch en método list()
- Mensajes de error descriptivos para archivo no encontrado