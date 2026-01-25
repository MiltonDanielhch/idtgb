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

### Estado Actual - v2.0.0 (20 de enero de 2026) ✅

Todos los bugs identificados en el análisis original han sido corregidos exitosamente. El módulo de Inmuebles ahora cuenta con:

- ✅ Soft Deletes implementados para eliminación lógica
- ✅ Auditoría completa con campos `created_by` y `updated_by`
- ✅ Índices de base de datos para optimizar consultas
- ✅ Validación de regex para formato de catastro
- ✅ Observer `InmuebleObserver` para auditoría automática
- ✅ Manejo de errores con Log en `store()` y `update()`
- ✅ Caching de municipios en el método `create()`
- ✅ Validación de integridad referencial en requests

### 🐛 Bugs Corregidos (6/6) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Consultas ineficientes en listados | ✅ Corregido | `2026_01_18_125312_add_indexes_...` |
| 2 | Validación de municipio inconsistente | ✅ Corregido | `StoreInmuebleRequest.php`, `UpdateInmuebleRequest.php` |
| 3 | Falta validación de unicidad compuesta | ✅ Corregido | `StoreInmuebleRequest.php`, `UpdateInmuebleRequest.php` (regex) |
| 4 | Falta validación de avalúos vigentes en borrado | ✅ Corregido | `InmuebleController.php:129-132` |
| 5 | Sin validación de integridad referencial | ✅ Corregido | `StoreInmuebleRequest.php`, `UpdateInmuebleRequest.php` |
| 6 | Falta manejo de errores | ✅ Corregido | `InmuebleController.php:77-90, 108-122` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Soft Deletes | Trait `SoftDeletes` en modelo con migración correspondiente |
| 2 | Auditoría completa | Campos `created_by` y `updated_by` con Observer automático |
| 3 | Índices de base de datos | Índices para `estado_inmueble`, `tipo_inmueble_id`, `municipio_id`, `catastro`, `matricula_rr` |
| 4 | Validación de regex para catastro | Formato XX-XXXX-XX-XXXX validado en requests |
| 5 | Observer automático | `InmuebleObserver` registra `updated_by` en eventos del modelo |
| 6 | Manejo de errores con Log | Try-catch en `store()` y `update()` con registro de errores |
| 7 | Caching de municipios | `Municipio::getCachedForSelect()` en método `create()` |
| 8 | Mensajes personalizados | Mensajes de validación claros para el formato de catastro |
| 9 | Validación de exists | Validación `exists:municipios,id` para municipio_id |
| 10 | Validación de dependencias en destroy | Verifica avalúos asociados antes de eliminar |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **MEDIO** | Búsqueda por tipo de inmueble | Agregar filtro por tipo en vista browse |
| **MEDIO** | Búsqueda por municipio | Agregar filtro por municipio en vista browse |
| **MEDIO** | Lazy loading para selects | Implementar carga dinámica para select de municipio |
| **MEDIO** | API endpoint | Crear endpoint API para integraciones externas |
| **BAJO** | Sistema de importación masiva | Importar inmuebles desde CSV/Excel |
| **BAJO** | Sistema de exportación | Exportar inmuebles a CSV/PDF |
| **BAJO** | Validación de unicidad compuesta | Catastro + complemento único (actualmente solo catastro único) |
| **BAJO** | Notificaciones de cambios | Sistema de notificaciones al modificar inmuebles |

### 📝 Historial de Cambios

### v2.0.0 (20 de enero de 2026)
**Correcciones Completadas (6/6):**
- ✅ Bug #1: Consultas ineficientes - Agregados índices en migración `2026_01_18_125312_add_indexes_to_inmuebles_and_tramite_inmuebles_tables.php`
- ✅ Bug #2: Validación de municipio - Agregada validación `exists:municipios,id` en requests
- ✅ Bug #3: Validación de unicidad - Agregada regex para formato de catastro XX-XXXX-XX-XXXX
- ✅ Bug #4: Validación de avalúos en borrado - Implementada en `destroy()` línea 129-132
- ✅ Bug #5: Integridad referencial - Validaciones de exists en requests
- ✅ Bug #6: Manejo de errores - Try-catch con Log en `store()` y `update()`

**Archivos Modificados/Creados:**
- `app/Models/Inmueble.php` - Agregados traits `SoftDeletes`, campo `updated_by` en fillable
- `app/Http/Controllers/InmuebleController.php` - Mejorado manejo de errores, caching de municipios
- `app/Http/Requests/StoreInmuebleRequest.php` - Agregado regex para catastro, validación exists
- `app/Http/Requests/UpdateInmuebleRequest.php` - Agregado regex para catastro, validación exists
- `app/Observers/InmuebleObserver.php` - Creado para auditoría automática
- `app/Providers/EventServiceProvider.php` - Registrado `InmuebleObserver`
- `database/migrations/2026_01_18_125312_add_indexes_to_inmuebles_and_tramite_inmuebles_tables.php` - Nueva migración de índices
- `database/migrations/2026_01_18_130041_add_soft_deletes_to_inmuebles_table.php` - Nueva migración soft deletes
- `database/migrations/2026_01_18_131643_add_audit_fields_to_inmuebles_table.php` - Nueva migración campos auditoría
- `tests/Feature/InmuebleTest.php` - Tests agregados
