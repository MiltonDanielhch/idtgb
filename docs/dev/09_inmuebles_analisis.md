# Documentación Técnica - Módulo de Inmuebles

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelo](#modelo)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Policies y Permisos](#policies-y-permisos)
7. [Requests](#requests)
8. [Vistas](#vistas)
9. [Integración con otros módulos](#integración-con-otros-módulos)
10. [Guía para Desarrolladores](#guía-para-desarrolladores)
11. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **Inmuebles** es el núcleo del sistema para la gestión de propiedades. Permite registrar, clasificar y valorar todos los bienes inmuebles sobre los cuales se aplicará el Impuesto de Transmisiones Gratuítas de Bienes (ITGB).

### Propósito
- Mantener un registro centralizado y único de todos los inmuebles.
- Clasificar los inmuebles por tipo (Urbano, Rústico) y ubicación (municipio).
- Almacenar datos clave como el número de catastro, valor catastral, superficie y dirección.
- Gestionar el ciclo de vida CRUD completo de los inmuebles.

### Importancia en el Sistema ITGB
El registro de un inmueble es el punto de partida para cualquier trámite de ITGB. La información contenida en este módulo es fundamental para:
- La creación de trámites de transferencia.
- El cálculo de la base imponible del impuesto.
- La realización de avalúos técnicos.
- La generación de reportes y estadísticas.

---

## 🗄️ Base de Datos

### Migración: `create_inmuebles_table.php`

**Ubicación:** `database/migrations/2025_09_22_122745_create_inmuebles_table.php`

**Estructura de la tabla `inmuebles`:**

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único del inmueble. |
| `complemento` | VARCHAR(3) | NULLABLE | Complemento del número de catastro. |
| `catastro` | VARCHAR(15) | UNIQUE, NOT NULL | Número de catastro, identificador único del inmueble. |
| `tipo_inmueble_id`| BIGINT | FK, NOT NULL | Relación con el tipo de inmueble (Urbano, Rústico). |
| `municipio_id` | BIGINT | FK, NULLABLE | Relación con el municipio donde se ubica. |
| `barrio_comunidad`| VARCHAR(100) | NULLABLE | Nombre del barrio o comunidad. |
| `direccion` | VARCHAR(200) | NULLABLE | Dirección exacta del inmueble. |
| `superficie_m2` | DECIMAL(12,2) | NULLABLE | Superficie total del inmueble en metros cuadrados. |
| `valor_catastral`| DECIMAL(14,2) | NOT NULL | Valor catastral registrado del inmueble. |
| `matricula_rr` | VARCHAR(20) | NULLABLE | Número de matrícula de Derechos Reales. |
| `es_vivienda_unica_familiar` | BOOLEAN | DEFAULT `false` | Indica si es la única vivienda de la familia. |
| `estado_inmueble`| ENUM(...) | DEFAULT `Activo` | Estado actual: `Activo`, `Transferido`, `Baja`. |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación. |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de última actualización. |

**Relaciones y Restricciones:**
- `catastro` debe ser único en toda la tabla.
- `tipo_inmueble_id` tiene una clave foránea a la tabla `tipos_inmueble`.
- `municipio_id` tiene una clave foránea a la tabla `municipios`.
- `softDeletes` implementado para eliminación lógica.

---

## 🧩 Modelo

### Modelo: `Inmueble`

**Ubicación:** `app/Models/Inmueble.php`

**Atributos:**
- `$table = 'inmuebles'`
- `$fillable`: Array con todos los campos de la tabla para asignación masiva.
- `$casts`:
    - `es_vivienda_unica_familiar` => `boolean`
    - `superficie_m2` => `decimal:2`
    - `valor_catastral` => `decimal:2`
- `$dates`: Incluye `deleted_at` y `birth_date` (si aplica)

**Traits:**
- `SoftDeletes`: Permite la recuperación de registros eliminados.

**Métodos de relación:**

```php
// Un inmueble tiene muchos registros en la tabla pivote tramite_inmuebles
public function tramiteInmuebles()
{
    return $this->hasMany(TramiteInmueble::class);
}

// Un inmueble pertenece a un tipo de inmueble
public function tipoInmueble()
{
    return $this->belongsTo(TipoInmueble::class);
}

// Un inmueble pertenece a un municipio
public function municipio()
{
    return $this->belongsTo(Municipio::class);
}

// Un inmueble puede tener múltiples avalúos
public function avaluos()
{
    return $this->hasMany(Avaluo::class);
}
```

**Métodos Helper:**

```php
// Retorna el último avalúo con estado 'Vigente' para este inmueble
public function avaluoVigente()
{
    return $this->avaluos()->where('estado', 'Vigente')->latest()->first();
}
```

---

## 🎮 Controlador

### Controlador: `InmuebleController`

**Ubicación:** `app/Http/Controllers/InmuebleController.php`

**Middleware:** `auth`

### Métodos del Controlador

#### 1. `index()` y `list()`
```
GET /admin/inmuebles
GET /admin/inmuebles/ajax/list
```
- `index()` muestra la vista principal `browse.blade.php`.
- `list()` retorna la lista paginada de inmuebles en formato HTML para AJAX.
- Carga relaciones `tipoInmueble` y `municipio.provincia.departamento`.
- Permite búsqueda por `catastro` y `direccion`.
- Autorización: `viewAny` (permiso `browse_inmuebles`).

#### 2. `show(Inmueble $inmueble)`
```
GET /admin/inmuebles/{inmueble}
```
- Muestra la vista detallada `read.blade.php`.
- Autorización: `view` (permiso `read_inmuebles`).

#### 3. `create()` y `store(StoreInmuebleRequest $request)`
```
GET  /admin/inmuebles/create
POST /admin/inmuebles
```
- `create()` muestra el formulario `edit-add.blade.php` con datos para los selects (`tipos`, `municipios`).
- `store()` valida los datos usando `StoreInmuebleRequest` y crea el nuevo inmueble.
- Autorización: `create` (permiso `add_inmuebles`).

#### 4. `edit(Inmueble $inmueble)` y `update(UpdateInmuebleRequest $request, Inmueble $inmueble)`
```
GET /admin/inmuebles/{inmueble}/edit
PUT /admin/inmuebles/{inmueble}
```
- `edit()` muestra el formulario `edit-add.blade.php` con los datos del inmueble a editar.
- `update()` valida los datos usando `UpdateInmuebleRequest` y actualiza el inmueble.
- Autorización: `update` (permiso `edit_inmuebles`).

#### 5. `destroy(Inmueble $inmueble)`
```
DELETE /admin/inmuebles/{inmueble}
```
- Elimina un inmueble (soft delete).
- **Validación de dependencias:** Verifica si el inmueble tiene `avaluos` o `tramiteInmuebles` asociados antes de borrar. Si los tiene, bloquea la eliminación.
- Autorización: `delete` (permiso `delete_inmuebles`).

#### 6. `ajaxSearch(Request $request)`
```
GET /admin/inmuebles/ajax/search
```
- Endpoint para búsquedas dinámicas (usado en Select2).
- Busca por `catastro`, `direccion` o `matricula_rr`.
- Retorna un JSON con formato `{ id, text, ... }` para ser consumido por Select2.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // ...
    Route::resource('inmuebles', InmuebleController::class)->names('admin.inmuebles');
    Route::get('inmuebles/ajax/list', [InmuebleController::class, 'list'])->name('admin.inmuebles.ajax.list');
    Route::get('inmuebles/ajax/search', [InmuebleController::class, 'ajaxSearch'])->name('admin.inmuebles.ajax.search');
    // ...
});
```

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| GET | `/admin/inmuebles` | `admin.inmuebles.index` | Listado principal. |
| GET | `/admin/inmuebles/create` | `admin.inmuebles.create` | Formulario para crear. |
| POST | `/admin/inmuebles` | `admin.inmuebles.store` | Guardar nuevo inmueble. |
| GET | `/admin/inmuebles/{inmueble}`| `admin.inmuebles.show` | Ver detalle. |
| GET | `/admin/inmuebles/{inmueble}/edit`| `admin.inmuebles.edit` | Formulario para editar. |
| PUT/PATCH| `/admin/inmuebles/{inmueble}`| `admin.inmuebles.update` | Actualizar inmueble. |
| DELETE | `/admin/inmuebles/{inmueble}`| `admin.inmuebles.destroy`| Eliminar inmueble. |
| GET | `/admin/inmuebles/ajax/list` | `admin.inmuebles.ajax.list` | Listado para AJAX. |
| GET | `/admin/inmuebles/ajax/search` | `admin.inmuebles.ajax.search` | Búsqueda para Select2. |

---

## 🔒 Policies y Permisos

### Policy: `InmueblePolicy`

**Ubicación:** `app/Policies/InmueblePolicy.php`

| Método | Permiso Requerido | Descripción |
|--------|-------------------|-------------|
| `viewAny()` | `browse_inmuebles` | Permite ver el listado de inmuebles. |
| `view()` | `read_inmuebles` | Permite ver el detalle de un inmueble. |
| `create()` | `add_inmuebles` | Permite mostrar el formulario y crear un inmueble. |
| `update()` | `edit_inmuebles` | Permite mostrar el formulario y actualizar un inmueble. |
| `delete()` | `delete_inmuebles` | Permite eliminar un inmueble. |
| `before()` | `browse_admin` | Otorga todos los permisos al rol de administrador. |

---

## ✅ Requests

### `StoreInmuebleRequest` y `UpdateInmuebleRequest`

**Ubicación:** `app/Http/Requests/`

**Reglas de Validación Clave:**

| Campo | Reglas |
|-------|--------|
| `catastro` | `required`, `string`, `max:15`, `unique:inmuebles` (ignora el ID actual en `Update`). |
| `tipo_inmueble_id`| `required`, `exists:tipos_inmueble,id`. |
| `municipio_id` | `nullable`, `exists:municipios,id`. |
| `valor_catastral`| `required`, `numeric`, `min:0`. |
| `es_vivienda_unica_familiar` | `boolean`. |
| `estado_inmueble`| `in:Activo,Transferido,Baja`. |

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/inmuebles/`

### 1. `browse.blade.php`
- Vista principal del módulo.
- Contiene el encabezado, el botón "Nuevo Inmueble" (controlado por el permiso `create`), y los controles de búsqueda y paginación.
- Utiliza JavaScript para realizar llamadas AJAX al endpoint `list` y renderizar los resultados en el div `#div-results`.

### 2. `list.blade.php`
- Plantilla parcial que renderiza la tabla de inmuebles.
- Muestra campos clave y badges de colores para el estado y si es vivienda única.
- Contiene los botones de acción (Ver Avalúos, Ver, Editar, Borrar) para cada fila, protegidos por directivas `@can`.
- Incluye la lógica de paginación de Laravel, adaptada para funcionar con AJAX.

### 3. `edit-add.blade.php`
- Formulario unificado para crear y editar inmuebles.
- El título y la acción del formulario cambian dinámicamente.
- Utiliza el plugin `select2` para la selección de `Tipo Inmueble` y `Municipio`.
- Muestra los errores de validación retornados por los `FormRequest`.

### 4. `read.blade.php`
- Vista de solo lectura que muestra todos los detalles de un inmueble en una tabla.
- Muestra información de las relaciones (nombre del tipo, nombre del municipio).
- Incluye un apartado para mostrar la información del **último avalúo vigente**.

---

## 🔗 Integración con otros módulos

### Módulo de Trámites (Wizard)
- En el **Paso 4**, se pueden buscar y asociar inmuebles a un trámite. El buscador utiliza el endpoint `admin.inmuebles.ajax.search`.
- El modelo `TramiteInmueble` actúa como tabla pivote.

### Módulo de Avalúos
- El modelo `Inmueble` tiene una relación `hasMany` con `Avaluo`.
- Desde el listado de inmuebles, hay un botón "Ver Avalúos" que redirige al listado de avalúos, pre-filtrado por el `inmueble_id`.

### Módulo de Tipos de Inmueble y Municipios
- El modelo `Inmueble` tiene relaciones `belongsTo` con `TipoInmueble` y `Municipio`, que son obligatorias (excepto municipio que es nullable) para la clasificación.

### Seeder: `InmuebleSeeder`
- El seeder `InmuebleSeeder` se encarga de poblar la base de datos con inmuebles de ejemplo, asegurándose de enlazar correctamente los `municipio_id` y `tipo_inmueble_id` buscando los modelos por nombre.

---

## 📚 Ejemplos de Uso

### Búsqueda de un inmueble para un trámite

```javascript
// En el wizard de trámites, se inicializa un Select2
$('.select2-inmuebles').select2({
    ajax: {
        url: '{{ route("admin.inmuebles.ajax.search") }}',
        dataType: 'json',
        delay: 250,
        // ...
    }
});
```

Esto permite buscar y seleccionar un inmueble por su catastro, dirección o matrícula.

### Obtener el último avalúo

```php
// En un controlador o vista
$inmueble = Inmueble::find(1);
$avaluoReciente = $inmueble->avaluoVigente();

if ($avaluoReciente) {
    echo "El valor del último avalúo es: " . $avaluoReciente->valor;
}
```

---

## 🔍 Consideraciones Importantes

- **Unicidad del Catastro:** El campo `catastro` es la clave de negocio principal y está restringido a ser único en la base de datos.
- **Dependencias:** La lógica de eliminación previene que un inmueble sea borrado si tiene avalúos o trámites asociados.
- **Autorización:** Todo el módulo está correctamente protegido por Policies y FormRequests, siguiendo las mejores prácticas de Laravel.

---

## 📝 Guía para Desarrolladores

### Extender el modelo
Si se necesita agregar un nuevo campo, por ejemplo `zona_geografica`:
1. **Crear migración:** `php artisan make:migration add_zona_geografica_to_inmuebles_table`.
2. **Actualizar `$fillable`** en el modelo `Inmueble`.
3. **Añadir el campo** al formulario `edit-add.blade.php`.
4. **Actualizar las reglas** en `StoreInmuebleRequest` y `UpdateInmuebleRequest`.
5. **Actualizar las vistas** `browse.blade.php` y `list.blade.php` para mostrar el nuevo campo.

### Mejorar la validación de borrado

Para evitar que un inmueble asociado a un trámite sea eliminado:

```php
// En InmuebleController@destroy
public function destroy(Inmueble $inmueble)
{
    $this->authorize('delete', $inmueble);

    if ($inmueble->avaluos()->exists()) {
        return back()->with(['message' => 'No se puede eliminar: tiene avalúos asociados.', 'alert-type' => 'error']);
    }
    if ($inmueble->tramiteInmuebles()->exists()) {
        return back()->with(['message' => 'No se puede eliminar: está asociado a uno o más trámites.', 'alert-type' => 'error']);
    }

    $inmueble->delete();
    return redirect()->route('admin.inmuebles.index')
        ->with(['message' => 'Inmueble eliminado.', 'alert-type' => 'success']);
}
```

---

## 🚨 Análisis de Calidad y Mejoras

A continuación se detallan posibles bugs, riesgos y oportunidades de mejora detectadas en el código del módulo de Inmuebles.

### 🐛 Posibles Bugs / Riesgos

### 1. Consultas ineficientes en listados

**Ubicación:** `app/Http/Controllers/InmuebleController.php:35-38`

**Problema:**
```php
$data = Inmueble::with(['tipoInmueble', 'municipio.provincia.departamento'])->orderBy('id', 'desc')->paginate($paginate);
```

**Impacto:** Si hay muchos inmuebles con relaciones anidadas, esto puede generar N+1 queries en las vistas.

**Solución:**
```php
// Optimizado
$data = Inmueble::with([
        'tipoInmueble', 
        'municipio.provincia.departamento'
    ])
    ->orderBy('id', 'desc')
    ->paginate($paginate);
```

### 2. Validación de municipio inconsistente

**Ubicación:** `app/Http/Requests/UpdateInmuebleRequest.php`

**Problema:** Permite actualizar el `municipio_id` sin verificar que exista en la base de datos.

**Solución:**
```php
'municipio_id' => [
    'nullable|exists:municipios,id'
],
```

### 3. Falta validación de unicidad compuesta

**Ubicación:** `app/Http/Requests/UpdateInmuebleRequest.php`

**Problema:** No se valida que el catastro + complemento sea único en la actualización. El usuario podría cambiar el complemento de un inmueble existente.

**Solución:**
```php
'catastro' => [
    'required|string|max:15',
    Rule::unique('inmuebles')->where(function($query) {
        $query->where('id', '!=', $this->route('inmueble')->id);
    }),
    'complemento' => [
        'nullable|string|max:3',
        Rule::unique('inmuebles')->where(function($query) {
            $query->where('id', '!=', $this->route('inmueble')->id)
                  ->where('catastro', $this->catastro);
        }),
    ],
],
```

---

## 💡 Mejoras Sugeridas

### 1. Implementar caching de municipios

**Beneficio:** Reduce consultas repetitivas.

```php
// En InmuebleController@create y edit
$municipios = Cache::remember('municipios.all', 3600, function() {
    return Municipio::with('provincia.departamento')->orderBy('nombre')->get();
});
```

### 2. Agregar búsqueda por tipo de inmueble en AJAX list

**Ubicación:** `app/Http/Controllers/InmuebleController.php:35-38`

**Implementación:**
```php
// En método list()
$data = Inmueble::with(['tipoInmueble'])
    ->when($tipo, fn($q) => $q->whereHas('tipoInmueble', fn($q) => $q->where('nombre', 'like', "%{$tipo}%")))
    ->when($search, fn($q) => $q->where('catastro', 'like', "%{$search}%"))
    ->orderByDesc('id')
    ->paginate($paginate);
```

### 3. Implementar validación de avalúos en inmuebles

**Descripción:** Si un inmueble tiene avalúos vigentes, no se debería permitir eliminarlo o cambiarlo a estado "Baja" sin advertencia.

**Implementación:**
```php
// En destroy()
if ($inmueble->avaluos()->where('estado', 'Vigente')->exists()) {
    return back()->with([
        'message' => 'No se puede eliminar: tiene avalúos vigentes asociados.', 
        'alert-type' => 'warning'
    ]);
}
```

### 4. Agregar búsqueda por municipio en vista browse

**Ubicación:** `resources/views/admin/inmuebles/browse.blade.php`

**Implementación:**
```blade
<!-- Agregar filtro de municipio -->
<div class="form-group">
    <label>Municipio</label>
    <select id="filtro_municipio" class="form-control">
        <option value="">Todos</option>
        @foreach($municipios as $m)
            <option value="{{ $m->id }}">{{ $m->nombre }} - {{ $m->provincia->departamento->nombre }}</option>
        @endforeach
    </select>
</div>

<!-- Script JavaScript -->
<script>
$('#filtro_municipio').on('change', function() {
    var municipioId = $(this).val();
    // Actualizar parámetro AJAX de list
    // ...
});
</script>
```

---

## ⚡ Optimizaciones de Rendimiento

### 1. Agregar índices en base de datos

**Ubicación:** `database/migrations/2025_09_22_122745_create_inmuebles_table.php`

**Implementación:**
```php
// Índices sugeridos
$table->index('catastro');
$table->index('tipo_inmueble_id');
$table->index('municipio_id');
$table->index('estado_inmueble');
$table->index('matricula_rr');
$table->index(['catastro', 'complemento'], 'uq_catastro_complemento'); // Para unicidad compuesta
```

### 2. Implementar lazy loading para selects de municipio

**Beneficio:** Reducir carga inicial de la página.

**Implementación:**
```javascript
// En edit-add.blade.php
$('#municipio').select2({
    ajax: {
        url: '/admin/municipios/ajax/list',
        dataType: 'json',
        minimumInputLength: 2
    }
});
```

### 3. Implementar API endpoint para inmuebles

**Beneficio:** Permitir integraciones externas.

**Implementación sugerida:**
```php
// routes/api.php
Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('inmuebles', Api\InmuebleController::class);
});
```

---

## 🚧 Faltas y Cosas por Implementar

### 1. Sin sistema de importación masiva de inmuebles

**Descripción:** No hay forma de importar inmuebles desde archivos externos (CSV, Excel, JSON).

**Solución sugerida:** Implementar importación masiva similar al módulo UFVs.

### 2. Sin validación de integridad referencial

**Problema:** No hay validación para asegurarse de que el municipio_id corresponda a la provincia y departamento correctos.

**Solución sugerida:** Agregar validación personalizada en `StoreInmuebleRequest`:

```php
// En StoreInmuebleRequest
public function withValidator($validator)
{
    $validator->after(function ($v) {
        $municipioId = $this->input('municipio_id');
        
        if ($municipioId) {
            $municipio = Municipio::find($municipioId);
            
            // Verificar que el municipio existe y tiene provincia
            if (!$municipio || !$municipio->provincia) {
                $v->errors()->add('municipio_id', 'El municipio seleccionado es inválido.');
            }
        }
    });
}
```

### 3. Sin notificaciones de cambios

**Descripción:** No hay registro de quién modificó un inmueble.

**Solución sugerida:** Implementar observador `InmuebleObserver` para registrar cambios.

### 4. Sin sistema de exportación

**Descripción:** No hay funcionalidad para exportar inmuebles a formatos como CSV o PDF.

**Solución sugerida:** Implementar exportación con paquete `maatwebsite/excel`.

---

## 📊 Resumen de Prioridades

| Severidad | Problema | Ubicación |
|-----------|----------|-----------|
| **Alta** | Consultas ineficientes en listados | `InmuebleController.php:35-38` |
| **Alta** | Validación de municipio inconsistente | `UpdateInmuebleRequest.php` |
| **Media** | Falta validación de unicidad compuesta (catastro + complemento) | `UpdateInmuebleRequest.php` |
| **Media** | Falta validación de avalúos vigentes en borrado | `InmuebleController.php:89-99` |
| **Baja** | Sin búsqueda por tipo de inmueble | `InmuebleController.php:35-38` |
| **Baja** | Sin sistema de importación masiva | - |

---

**Última actualización:** Enero 2026
**Versión:** 2.0.0
