# Documentación Técnica - Módulo de Inmuebles

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelo](#modelo)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Policies](#policies)
7. [Requests](#requests)
8. [Vistas](#vistas)
9. [Integración con otros módulos](#integración-con-otros-módulos)
10. [Ejemplos de Uso](#ejemplos-de-uso)
11. [Consideraciones Importantes](#consideraciones-importantes)
12. [Guía para Desarrolladores](#guía-para-desarrolladores)

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

### Migración: `2025_09_22_122745_create_inmuebles_table.php`

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
- Elimina un inmueble.
- **Validación de dependencias:** Verifica si el inmueble tiene `avaluos` asociados antes de borrar. Si los tiene, bloquea la eliminación.
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

## 🔒 Policies

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

- Centralizan la lógica de **autorización** y **validación** para crear y actualizar inmuebles.

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

## 🔗 Integración con otros Módulos

### Módulo de Trámites (Wizard)
- En el **Paso 4**, se pueden buscar y asociar inmuebles a un trámite. El buscador utiliza el endpoint `admin.inmuebles.ajax.search`.
- El modelo `TramiteInmueble` actúa como tabla pivote.

### Módulo de Avalúos
- El modelo `Inmueble` tiene una relación `hasMany` con `Avaluo`.
- Desde el listado de inmuebles, hay un botón "Ver Avalúos" que redirige al listado de avalúos, pre-filtrado por el `inmueble_id`.
- La eliminación de un inmueble se bloquea si existen avalúos asociados.

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
- **Dependencias:** La lógica de eliminación previene que un inmueble sea borrado si tiene avalúos, pero **no valida** si está asociado a trámites. Esto podría ser una mejora a futuro.
- **Autorización:** Todo el módulo está correctamente protegido por Policies y FormRequests, siguiendo las mejores prácticas de Laravel.

---

## 📝 Guía para Desarrolladores

### Extender el modelo
Si se necesita agregar un nuevo campo, por ejemplo `zona_geografica`:
1.  **Crear migración:** `php artisan make:migration add_zona_geografica_to_inmuebles_table`.
2.  **Actualizar `$fillable`** en el modelo `Inmueble`.
3.  **Añadir el campo** al formulario `edit-add.blade.php`.
4.  **Actualizar las reglas** en `StoreInmuebleRequest` y `UpdateInmuebleRequest`.

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
    // NUEVA VALIDACIÓN
    if ($inmueble->tramiteInmuebles()->exists()) {
        return back()->with(['message' => 'No se puede eliminar: está asociado a uno or más trámites.', 'alert-type' => 'error']);
    }

    $inmueble->delete();
    return redirect()->route('admin.inmuebles.index')
        ->with(['message' => 'Inmueble eliminado.', 'alert-type' => 'success']);
}
```

---

## 🐛 Análisis del Módulo: Bugs, Mejoras y Optimizaciones

### 🔴 Posibles Bugs (CRÍTICOS)

#### 1. **Validación incompleta en eliminación de inmuebles**
**Ubicación:** `app/Http/Controllers/InmuebleController.php:89-99`

**Problema:**
El método `destroy()` solo valida si el inmueble tiene avalúos asociados, pero no verifica si está asociado a trámites activos. Si se elimina un inmueble que tiene trámites asociados, se perderá la integridad de la información histórica de esos trámites.

```php
// Lógica actual (BUG)
if ($inmueble->avaluos()->exists()) {
    return back()->with(['message' => 'No se puede eliminar: tiene avalúos asociados.', 'alert-type' => 'error']);
}
// ❌ Falta validar trámites
$inmueble->delete();
```

**Solución propuesta:**
```php
if ($inmueble->avaluos()->exists()) {
    return back()->with(['message' => 'No se puede eliminar: tiene avalúos asociados.', 'alert-type' => 'error']);
}
if ($inmueble->tramiteInmuebles()->exists()) {
    return back()->with(['message' => 'No se puede eliminar: tiene trámites asociados.', 'alert-type' => 'error']);
}
```

---

#### 2. **Posible error null en IdtgbCalculator**
**Ubicación:** `app/Services/IdtgbCalculator.php:51` y `:74`

**Problema:**
El servicio asume que `$tramite->inmuebles->first()` siempre existe, lo que podría causar un error "Trying to get property of non-object" si un trámite no tiene inmuebles asociados.

```php
// Línea 51
$tramite->inmuebles->first()->municipio->provincia->departamento_id ?? 1
// ❌ Si inmuebles está vacío, first() es null, y se intenta acceder a municipio
```

**Solución propuesta:**
```php
$primerInmueble = $tramite->inmuebles->first();
$depId = $primerInmueble?->municipio?->provincia?->departamento_id ?? 1;
```

---

#### 3. **Cascade on delete inseguro en tabla pivote**
**Ubicación:** `database/migrations/2025_09_22_122800_create_tramite_inmuebles_table.php:17`

**Problema:**
La migración tiene `cascadeOnDelete()` en el campo `inmueble_id`. Si se elimina un inmueble, se eliminarán automáticamente todos los registros de `tramite_inmuebles` asociados, lo cual puede causar pérdida de datos históricos.

```php
$table->foreignId('inmueble_id')->constrained()->cascadeOnDelete(); // ❌
```

**Solución propuesta:**
```php
$table->foreignId('inmueble_id')->constrained()->restrictOnDelete();
```

---

#### 4. **Consulta duplicada en vista read.blade.php**
**Ubicación:** `resources/views/admin/inmuebles/read.blade.php:96`

**Problema:**
La vista hace una consulta directa a avaluos en lugar de usar el método helper del modelo, causando duplicación de lógica y posible inconsistencia.

```php
// Línea 96 - En la vista
$ultimo = $inmueble->avaluos()->where('estado', 'Vigente')->latest('fecha_avaluo')->first();
// ❌ Debería usar: $inmueble->avaluoVigente()
```

---

#### 5. **Validación insuficiente en addInmueble del wizard**
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php:268-281`

**Problema:**
Aunque se valida que el inmueble no esté duplicado en la sesión, no se valida si el inmueble tiene un estado que permita ser asociado (ej: un inmueble con estado "Transferido" no debería poder asociarse a nuevos trámites).

```php
public function addInmueble(Request $request)
{
    $request->validate(['inmueble_id' => 'required|exists:inmuebles,id']);
    // ❌ Falta validar estado del inmueble
    if (!in_array($inmuebleId, $wizardData['step4']['inmuebles'] ?? [])) {
        // ...
    }
}
```

---

### 🟡 Mejoras Sugeridas

#### 1. **Implementar Soft Deletes**
**Ubicación:** `app/Models/Inmueble.php`

**Problema:**
No hay soft deletes implementados, por lo que si se elimina un inmueble por error, no se puede recuperar.

**Solución propuesta:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Inmueble extends Model
{
    use HasFactory, SoftDeletes;
    // ...
}
```

---

#### 2. **Agregar eventos/observadores para auditoría**
**Ubicación:** Crear `app/Observers/InmuebleObserver.php`

**Beneficio:**
Permite mantener un registro de quién creó, modificó o eliminó un inmueble.

```php
class InmuebleObserver
{
    public function creating(Inmueble $inmueble)
    {
        $inmueble->created_by = auth()->id();
    }

    public function updating(Inmueble $inmueble)
    {
        $inmueble->updated_by = auth()->id();
    }
}
```

---

#### 3. **Mejorar búsqueda con filtros adicionales**
**Ubicación:** `app/Http/Controllers/InmuebleController.php:27-41`

**Beneficio:**
Permitir filtrar por tipo de inmueble, municipio y estado.

```php
public function list()
{
    $search   = request('search');
    $paginate = request('paginate', 10);
    $tipoId   = request('tipo_inmueble_id');
    $muniId   = request('municipio_id');
    $estado   = request('estado_inmueble');

    $data = Inmueble::with(['tipoInmueble', 'municipio.provincia.departamento'])
        ->when($tipoId, fn($q) => $q->where('tipo_inmueble_id', $tipoId))
        ->when($muniId, fn($q) => $q->where('municipio_id', $muniId))
        ->when($estado, fn($q) => $q->where('estado_inmueble', $estado))
        ->when($search, fn($q) => $q->where('catastro', 'like', "%{$search}%")
            ->orWhere('direccion', 'like', "%{$search}%"))
        ->orderByDesc('id')
        ->paginate($paginate);
    // ...
}
```

---

#### 4. **Validación de formato de catastro**
**Ubicación:** `app/Http/Requests/StoreInmuebleRequest.php`

**Beneficio:**
Validar que el número de catastro siga el formato estándar (ej: `XX-XXXX-XX-XXXX`).

```php
'catastro' => [
    'required',
    'string',
    'max:15',
    'unique:inmuebles',
    'regex:/^[0-9]{2}-[0-9]{4}-[0-9]{2}-[0-9]{4}$/', // Formato esperado
],
```

---

#### 5. **Implementar sistema de notificaciones**
**Beneficio:**
Notificar a usuarios relevantes cuando se crea o modifica un inmueble importante.

```php
// Al crear un inmueble con valor catastral alto
if ($inmueble->valor_catastral > 1000000) {
    $inmueble->notify(new NewHighValuePropertyNotification($inmueble));
}
```

---

#### 6. **Agregar campos de auditoría**
**Ubicación:** `database/migrations/2025_09_22_122745_create_inmuebles_table.php`

**Beneficio:**
Registrar quién creó y modificó cada registro.

```php
$table->foreignId('created_by')->nullable()->constrained('users');
$table->foreignId('updated_by')->nullable()->constrained('users');
$table->timestamp('deleted_at')->nullable();
```

---

#### 7. **Implementar búsqueda global mejorada**
**Ubicación:** `app/Http/Controllers/InmuebleController.php:101-127`

**Beneficio:**
Buscar también por barrio, matrícula y permitir búsqueda aproximada.

```php
$inmuebles = Inmueble::where(function($query) use ($term) {
    $query->where('catastro', 'LIKE', "%{$term}%")
        ->orWhere('direccion', 'LIKE', "%{$term}%")
        ->orWhere('matricula_rr', 'LIKE', "%{$term}%")
        ->orWhere('barrio_comunidad', 'LIKE', "%{$term}%")
        ->orWhere('complemento', 'LIKE', "%{$term}%");
})->limit(20)->get();
```

---

#### 8. **Agregar validación de negocio compleja**
**Beneficio:**
Impedir que un inmueble con estado "Transferido" sea modificado o asociado a nuevos trámites.

```php
// En UpdateInmuebleRequest
public function rules()
{
    $inmueble = $this->route('inmueble');
    
    if ($inmueble->estado_inmueble === 'Transferido') {
        abort(422, 'No se puede modificar un inmueble transferido');
    }
    
    return [
        // reglas existentes...
    ];
}
```

---

### 🟢 Optimizaciones de Rendimiento

#### 1. **Agregar índices en la base de datos**
**Ubicación:** Nueva migración

**Beneficio:**
Mejorar el rendimiento de búsquedas frecuentes.

```php
Schema::table('inmuebles', function (Blueprint $table) {
    $table->index(['estado_inmueble', 'tipo_inmueble_id'], 'idx_estado_tipo');
    $table->index('municipio_id', 'idx_municipio');
    $table->index('catastro', 'idx_catastro');
});
```

---

#### 2. **Cargar selectores con lazy loading**
**Ubicación:** `app/Http/Controllers/InmuebleController.php:51-58`

**Beneficio:**
Evitar cargar todos los municipios y tipos al mismo tiempo.

```php
public function create()
{
    $this->authorize('create', Inmueble::class);
    return view('admin.inmuebles.edit-add', [
        'inmueble' => new Inmueble(),
        'tipos' => TipoInmueble::orderBy('nombre')->pluck('nombre', 'id'),
        'municipios' => [], // Se cargarán vía AJAX por departamento
    ]);
}
```

---

#### 3. **Implementar caché para listados frecuentes**
**Beneficio:**
Reducir consultas a la base de datos.

```php
public function create()
{
    $tipos = Cache::remember('tipos_inmueble_list', 3600, function () {
        return TipoInmueble::orderBy('nombre')->get();
    });
    // ...
}
```

---

#### 4. **Optimizar carga eager loading en listado**
**Ubicación:** `app/Http/Controllers/InmuebleController.php:34-38`

**Problema actual:**
Carga relaciones anidadas que quizás no se usan en el listado.

```php
// Actual
->with(['tipoInmueble', 'municipio.provincia.departamento'])
// Solo se usa tipoInmueble y municipio en list.blade.php
```

**Solución:**
```php
->with(['tipoInmueble', 'municipio'])
```

---

#### 5. **Implementar paginación con cursor para grandes datasets**
**Ubicación:** `app/Http/Controllers/InmuebleController.php:38`

**Beneficio:**
Mejor rendimiento para tablas con muchos registros.

```php
$data = Inmueble::with(['tipoInmueble', 'municipio'])
    ->cursorPaginate($paginate);
```

---

### ⚠️ Faltas de Cosas (Functionality Gaps)

#### 1. **No hay tests automatizados**
**Ubicación:** `tests/Feature/`

**Problema:**
No existe ningún test para el módulo de inmuebles, lo que hace difícil asegurar que futuros cambios no rompan funcionalidad existente.

**Se recomienda crear:**
- `InmuebleTest.php`
- `InmuebleControllerTest.php`
- `InmueblePolicyTest.php`

---

#### 2. **No hay API endpoints**
**Ubicación:** `routes/api.php`

**Problema:**
No hay API REST para consumo por aplicaciones móviles o integraciones externas.

**Se recomienda:**
```php
Route::apiResource('api/inmuebles', ApiInmuebleController::class);
Route::get('api/inmuebles/search', [ApiInmuebleController::class, 'search']);
```

---

#### 3. **Falta documentación de errores/exceptions**
**Ubicación:** Documentación del módulo

**Problema:**
No hay documentación sobre qué excepciones pueden lanzarse y cómo manejarlas.

**Se recomienda agregar:**
- Sección de "Errores Comunes" en la documentación
- Códigos de error estandarizados
- Mensajes de error amigables para el usuario

---

#### 4. **No hay historial de cambios**
**Problema:**
No se puede rastrear qué cambios se han hecho a un inmueble a lo largo del tiempo.

**Se recomienda:**
- Implementar paquete como `spatie/laravel-activitylog`
- O crear tabla personalizada `inmueble_history`

---

#### 5. **Falta funcionalidad de import/export**
**Problema:**
No hay manera de importar inmuebles en masa desde Excel/CSV, ni exportarlos para análisis externos.

**Se recomienda:**
```php
// Usar Laravel Excel
public function export()
{
    return Excel::download(new InmueblesExport, 'inmuebles.xlsx');
}

public function import(Request $request)
{
    Excel::import(new InmueblesImport, $request->file('file'));
}
```

---

#### 6. **No hay validación de unicidad por municipio**
**Problema:**
El número de catastro es único globalmente, pero en algunos casos podría necesitar ser único solo por municipio o departamento.

**Se recomienda:**
Agregar validación condicional según normativa vigente.

---

#### 7. **Falta funcionalidad de búsqueda avanzada**
**Problema:**
La búsqueda actual es muy básica (solo por catastro y dirección).

**Se recomienda implementar:**
- Rangos de valores catastrales
- Rangos de superficie
- Combinaciones múltiples de filtros
- Guardado de búsquedas frecuentes

---

#### 8. **No hay sistema de etiquetas/categorías**
**Problema:**
No hay manera de etiquetar o categorizar inmuebles para búsquedas personalizadas (ej: "propiedades prioritarias", "en litigio", etc.)

**Se recomienda:**
- Agregar tabla `inmueble_tags`
- Relación many-to-many con `Tag`

---

### 📊 Resumen de Prioridades

| Prioridad | Ítem | Tipo | Impacto | Esfuerzo |
|-----------|------|------|---------|----------|
| **ALTA** | Validación de trámites en destroy | Bug | Crítico | Bajo |
| **ALTA** | Soft deletes | Mejora | Alto | Bajo |
| **ALTA** | Tests automatizados | Falta | Alto | Medio |
| **ALTA** | Índices en BD | Optimización | Alto | Bajo |
| **MEDIA** | Validación de formato catastro | Mejora | Medio | Bajo |
| **MEDIA** | Observadores/auditoría | Mejora | Medio | Medio |
| **MEDIA** | Búsqueda avanzada | Mejora | Medio | Alto |
| **BAJA** | API endpoints | Falta | Medio | Alto |
| **BAJA** | Import/Export | Falta | Bajo | Medio |
| **BAJA** | Etiquetas | Mejora | Bajo | Medio |

---

### 🎯 Plan de Acción Recomendado

#### Fase 1: Críticos (1-2 semanas)
1. ✅ Agregar validación de trámites en `destroy()`
2. ✅ Corregir posible null en `IdtgbCalculator`
3. ✅ Cambiar cascadeOnDelete a restrictOnDelete
4. ✅ Implementar soft deletes

#### Fase 2: Mejoras de Calidad (2-3 semanas)
1. ✅ Crear tests básicos
2. ✅ Agregar índices en BD
3. ✅ Implementar observadores para auditoría
4. ✅ Validación de formato de catastro

#### Fase 3: Funcionalidad Avanzada (3-4 semanas)
1. ✅ Búsqueda avanzada con filtros
2. ✅ API REST endpoints
3. ✅ Sistema de import/export
4. ✅ Historial de cambios con activity log

---

### 📝 Notas Adicionales

- **Compatibilidad con normativas:** Verificar que el módulo cumple con las últimas normativas del Servicio de Impuestos Nacionales (SIN).
- **Performance:** Considerar implementar Redis para caché de consultas frecuentes en entornos de producción.
- **Seguridad:** Revisar que todos los campos sensibles estén correctamente validados y sanitizados.
- **Documentación:** Mantener esta documentación actualizada con cada cambio significativo en el módulo.
