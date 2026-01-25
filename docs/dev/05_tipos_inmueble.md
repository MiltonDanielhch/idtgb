# Documentación Técnica - Módulo de Tipos de Inmueble

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelo](#modelo)
4. [Controlador](#controlador)
5. [Policy](#policy)
6. [Requests](#requests)
7. [Rutas](#rutas)
8. [Vistas](#vistas)
9. [Integración con otros módulos](#integración-con-otros-módulos)
10. [Ejemplos de Uso](#ejemplos-de-uso)
11. [Consideraciones Importantes](#consideraciones-importantes)
12. [Guía para Desarrolladores](#guía-para-desarrolladores)
13. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **Tipos de Inmueble** gestiona los diferentes tipos de clasificación de inmuebles que pueden ser registrados en el sistema del Impuesto de Transmisiones Gratuítas de Bienes (ITGB). Este módulo permite categorizar los inmuebles según su naturaleza, uso o características.

### Propósito
- Mantener un catálogo de tipos de inmuebles (Urbano, Rústico, Comercial, etc.)
- Permitir la clasificación de inmuebles al crear registros de propiedades
- Facilitar el filtrado y búsqueda de inmuebles por tipo
- Gestionar el ciclo de vida CRUD completo de tipos de inmueble

### Importancia en el Sistema ITGB
La clasificación del tipo de inmueble es fundamental para:
- Determinar el valor catastral y avalúo
- Aplicar tasas o exenciones específicas según el tipo
- Generar reportes estadísticos por categoría de inmueble
- Facilitar búsquedas en el sistema

---

## 🗄️ Base de Datos

### Migraciones
1. `create_tipos_inmueble_table.php` - Creación de tabla
2. `add_soft_deletes_to_tipos_inmueble_table.php` - Soft Deletes
3. `add_audit_fields_to_tipos_inmueble_table.php` - Campos de auditoría

**Estructura de la tabla:**

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre del tipo de inmueble (ej: "Urbano", "Rústico") |
| `created_by` | BIGINT | FK, NULLABLE | Usuario que creó el registro |
| `updated_by` | BIGINT | FK, NULLABLE | Usuario que actualizó el registro |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |
| `deleted_at` | TIMESTAMP | NULLABLE | Fecha de eliminación (Soft Deletes) |

---

## 🧩 Modelo

### Modelo: `TipoInmueble`

**Ubicación:** `app/Models/TipoInmueble.php`

**Traits:**
- `HasFactory`
- `SoftDeletes` - Permite la recuperación de registros eliminados

**Atributos:**
- `$table = 'tipos_inmueble'`
- `$fillable = ['nombre', 'created_by', 'updated_by']`
- `$hidden = ['deleted_at']`

**Auto-auditoría:**
- `created_by` se establece automáticamente al crear
- `updated_by` se establece automáticamente al actualizar

**Relaciones:**
```php
public function inmuebles()
{
    return $this->hasMany(Inmueble::class);
}

public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

---

## 🎮 Controlador

### Controlador: `TipoInmuebleController`

**Ubicación:** `app/Http/Controllers/TipoInmuebleController.php`

**Middleware:** `auth`

### Métodos del Controlador

- **`index()`**: Muestra el listado principal con autorización `viewAny`.
- **`list()`**: Retorna la tabla de datos vía AJAX con conteo de inmuebles asociados (`withCount('inmuebles')`) y carga de creador (`with(['createdBy'])`).
- **`create()`**: Muestra el formulario de creación con autorización `create`.
- **`store(StoreTipoInmuebleRequest $request)`**: Guarda un nuevo registro usando validación especializada y transacción de DB.
- **`show(TipoInmueble $tipoInmueble)`**: Muestra la vista de detalle con autorización `view`, carga relaciones de auditoría.
- **`edit(TipoInmueble $tipoInmueble)`**: Muestra el formulario de edición con autorización `update`.
- **`update(UpdateTipoInmuebleRequest $request, TipoInmueble $tipoInmueble)`**: Actualiza un registro existente usando transacción de DB.
- **`destroy(TipoInmueble $tipoInmueble)`**: Elimina un registro (soft delete), verificando primero que no esté en uso por ningún inmueble, usando transacción de DB y Log.

---

## 🔒 Policy

### Policy: `TipoInmueblePolicy`

**Ubicación:** `app/Policies/TipoInmueblePolicy.php`

**Registro:** `app/Providers/AuthServiceProvider.php`

| Método | Permiso Requerido | Descripción |
|--------|-------------------|-------------|
| `viewAny()` | `browse_tipos-inmueble` | Permite ver el listado de tipos de inmueble. |
| `view()` | `read_tipos-inmueble` | Permite ver el detalle de un tipo de inmueble. |
| `create()` | `add_tipos-inmueble` | Permite mostrar el formulario y crear un tipo de inmueble. |
| `update()` | `edit_tipos-inmueble` | Permite mostrar el formulario y actualizar un tipo de inmueble. |
| `delete()` | `delete_tipos-inmueble` | Permite eliminar un tipo de inmueble. |
| `restore()` | `browse_admin` | Permite restaurar un tipo de inmueble eliminado. |
| `forceDelete()` | `browse_admin` | Permite eliminar permanentemente un tipo de inmueble. |
| `before()` | `browse_admin` | Otorga todos los permisos al rol de administrador. |

---

## ✅ Requests

### `StoreTipoInmuebleRequest`

**Ubicación:** `app/Http/Requests/StoreTipoInmuebleRequest.php`

```php
public function authorize(): bool
{
    return Gate::allows('create', \App\Models\TipoInmueble::class);
}

public function rules(): array
{
    return [
        'nombre' => 'required|string|max:50|unique:tipos_inmueble,nombre',
    ];
}
```

### `UpdateTipoInmuebleRequest`

**Ubicación:** `app/Http/Requests/UpdateTipoInmuebleRequest.php`

```php
public function authorize(): bool
{
    return Gate::allows('update', $this->route('tipoInmueble'));
}

public function rules(): array
{
    return [
        'nombre' => [
            'required',
            'string',
            'max:50',
            Rule::unique('tipos_inmueble')->ignore($this->route('tipoInmueble'))
        ],
    ];
}
```

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::resource('tipos-inmueble', TipoInmuebleController::class)
    ->names('admin.tipos-inmueble')
    ->parameters(['tipos-inmueble' => 'tipoInmueble']);

Route::get('tipos-inmueble/ajax/list', [TipoInmuebleController::class, 'list'])
    ->name('admin.tipos-inmueble.ajax.list');
```

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| GET | `/admin/tipos-inmueble` | `admin.tipos-inmueble.index` | Listado principal. |
| GET | `/admin/tipos-inmueble/create` | `admin.tipos-inmueble.create` | Formulario para crear. |
| POST | `/admin/tipos-inmueble` | `admin.tipos-inmueble.store` | Guardar nuevo tipo. |
| GET | `/admin/tipos-inmueble/{tipoInmueble}`| `admin.tipos-inmueble.show` | Ver detalle. |
| GET | `/admin/tipos-inmueble/{tipoInmueble}/edit`| `admin.tipos-inmueble.edit` | Formulario para editar. |
| PUT/PATCH| `/admin/tipos-inmueble/{tipoInmueble}`| `admin.tipos-inmueble.update` | Actualizar tipo. |
| DELETE | `/admin/tipos-inmueble/{tipoInmueble}`| `admin.tipos-inmueble.destroy`| Eliminar tipo (soft delete). |
| GET | `/admin/tipos-inmueble/ajax/list` | `admin.tipos-inmueble.ajax.list` | Listado para AJAX. |

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/tipos-inmueble/`

### 1. `browse.blade.php`
- Vista principal del módulo.
- Contiene el encabezado, el botón "Añadir nuevo" (protegido por permiso `create`), y los controles de búsqueda y paginación.
- Utiliza JavaScript para realizar llamadas AJAX al endpoint `list` y renderizar los resultados.
- **Modal de confirmación de eliminación:** Incluye un modal Bootstrap (`id="delete_modal"`) para confirmar la eliminación de tipos de inmueble antes de procesar la acción.

### 2. `list.blade.php`
- Plantilla parcial que renderiza la tabla de tipos de inmueble.
- Muestra: ID, Nombre, Conteo de inmuebles (con badge), Creado por, Fecha de creación.
- Contiene los botones de acción (Ver, Editar, Borrar) para cada fila, protegidos por directivas `@can`.
- El botón de borrar abre el modal de confirmación en lugar de ejecutar la acción inmediatamente.
- Incluye la lógica de paginación de Laravel, adaptada para funcionar con AJAX.

### 3. `edit-add.blade.php`
- Formulario unificado para crear y editar tipos de inmueble.
- El título y la acción del formulario cambian dinámicamente.
- Muestra los errores de validación retornados por los `FormRequest`.

### 4. `read.blade.php`
- Vista de solo lectura que muestra todos los detalles de un tipo de inmueble.
- Muestra información de auditoría: creado por, actualizado por.

---

## 🔄 Flujo de Eliminación con Modal

El módulo implementa un modal de confirmación para eliminar tipos de inmueble, siguiendo el patrón usado en otros módulos del sistema:

### Implementación

1. **Botón de borrar en `list.blade.php`:**
   - Usa `data-delete-url` para almacenar la URL de eliminación
   - Usa `data-delete-name` para almacenar el nombre del tipo a eliminar
   - Usa `data-toggle="modal"` y `data-target="#delete_modal"` para abrir el modal

2. **Modal en `browse.blade.php`:**
   - ID del modal: `delete_modal`
   - Clase: `modal modal-danger fade`
   - Contiene un formulario con `method="POST"` y `@method('DELETE')`
   - El formulario se actualiza dinámicamente mediante JavaScript

3. **JavaScript:**
   ```javascript
   $(document).on('click', '.delete[data-toggle="modal"]', function() {
       let url = $(this).data('delete-url');
       let nombre = $(this).data('delete-name');
       $('#delete_form').attr('action', url);
       $('.modal-title').html('<i class="voyager-trash"></i> ¿Eliminar el tipo de inmueble "<strong>' + nombre + '</strong>"?');
   });
   ```

### Ventajas
- Evita eliminaciones accidentales
- Muestra el nombre del item a eliminar para confirmación visual
- Mejor experiencia de usuario con UI moderna de Bootstrap modals

---

## 🔗 Integración con otros módulos

La integración principal es con el módulo de **Inmuebles**. Cada `Inmueble` tiene un campo `tipo_inmueble_id` que es una clave foránea a esta tabla, siendo un campo obligatorio en el formulario de creación y edición de inmuebles.

### Integración con Trámites
- Los inmuebles se asocian a trámites a través de la tabla pivote `tramite_inmuebles`.
- El tipo de inmueble se usa indirectamente para cálculos del impuesto.

---

## 📚 Ejemplos de Uso

**Crear un nuevo tipo:**
```bash
# Petición HTTP
POST /admin/tipos-inmueble
{
    "nombre": "Urbano"
}
```

**Verificar dependencias antes de eliminar:**
```php
// En TipoInmuebleController@destroy
if ($tipoInmueble->inmuebles()->exists()) {
    // Retorna error, no se puede eliminar
}
```

**Recuperar un tipo eliminado (soft delete):**
```php
$tipo = TipoInmueble::withTrashed()->find(1);
$tipo->restore();
```

---

## 🔍 Consideraciones Importantes

- **Unicidad:** El campo `nombre` es único. El sistema no permite dos tipos de inmueble con el mismo nombre.
- **Dependencias:** El sistema protege la integridad de los datos al no permitir la eliminación de un tipo si está siendo utilizado por al menos un inmueble.
- **Soft Deletes:** Los registros eliminados no se borran permanentemente, se marcan con `deleted_at` y pueden ser restaurados.
- **Autorización:** Todo el módulo está protegido por Policies y FormRequests, siguiendo las mejores prácticas de Laravel.
- **Auditoría:** Los campos `created_by` y `updated_by` se registran automáticamente en cada operación.
- **Transacciones:** Las operaciones de creación, actualización y eliminación usan transacciones de base de datos.

---

## 📝 Guía para Desarrolladores

### Crear un nuevo tipo de inmueble
1. Acceder a la ruta `/admin/tipos-inmueble/create`
2. El usuario debe tener el permiso `add_tipos-inmueble`
3. Ingresar el nombre del tipo (máximo 50 caracteres)
4. El nombre debe ser único en el sistema

### Extender el módulo
Para añadir un campo `descripcion`:
1. Crear migración: `php artisan make:migration add_descripcion_to_tipos_inmueble_table`
2. Añadir campo a `$fillable` en el modelo `TipoInmueble`
3. Actualizar las reglas en `StoreTipoInmuebleRequest` y `UpdateTipoInmuebleRequest`
4. Añadir el campo al formulario `edit-add.blade.php`
5. Mostrar el campo en `read.blade.php` y `list.blade.php`

### Ver tipos eliminados
```php
$tiposEliminados = TipoInmueble::onlyTrashed()->get();
```

### Restaurar un tipo eliminado
```php
$tipo = TipoInmueble::withTrashed()->find($id);
$tipo->restore();
```

### Conteo de inmuebles por tipo
El método `list()` del controlador carga el conteo usando `withCount('inmuebles')`, lo que permite mostrar cuántos inmuebles están asociados a cada tipo en la vista de listado.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (21 de enero de 2026) ✅

Todas las mejores prácticas han sido implementadas exitosamente. El módulo de Tipos de Inmueble ahora cuenta con:

- ✅ Soft Deletes implementados
- ✅ Policy con permisos completos (incluyendo `restore` y `forceDelete`)
- ✅ Validación centralizada con Form Requests
- ✅ `withCount('inmuebles')` y carga de auditoría en listados
- ✅ Verificación de dependencias antes de eliminar
- ✅ Autorización en todos los métodos del controlador
- ✅ Campos de auditoría `created_by` y `updated_by`
- ✅ Auto-auditoría en eventos del modelo
- ✅ Transacciones de DB en todas las operaciones
- ✅ Manejo de errores con Log
- ✅ Modal de confirmación de eliminación

### 🐛 Bugs Corregidos (0/0)

No se han identificado bugs en el módulo.

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Soft Deletes | Trait agregado al modelo, permite restaurar registros |
| 2 | Policy completa | `TipoInmueblePolicy` con permisos `browse`, `read`, `add`, `edit`, `delete`, `restore`, `forceDelete` |
| 3 | Validación centralizada | `StoreTipoInmuebleRequest` y `UpdateTipoInmuebleRequest` |
| 4 | withCount optimizado | `withCount('inmuebles')` en listados |
| 5 | Carga de auditoría | `with(['createdBy'])` en listados, `load(['createdBy', 'updatedBy'])` en show |
| 6 | Protección contra eliminación | Verifica dependencias antes de eliminar |
| 7 | Modal de confirmación | UI mejorada para evitar eliminaciones accidentales |
| 8 | Campos de auditoría | `created_by`, `updated_by` agregados a tabla y modelo |
| 9 | Auto-auditoría | Eventos `creating` y `updating` en modelo `boot()` |
| 10 | Transacciones de DB | `DB::beginTransaction()` en `store()`, `update()`, `destroy()` |
| 11 | Manejo de errores con Log | Logging en `destroy()` y manejo de excepciones en `store()`, `update()` |
| 12 | Relaciones de auditoría | Métodos `createdBy()` y `updatedBy()` en modelo |

### 📝 Historial de Cambios

### v2.0.0 (21 de enero de 2026)

**Mejoras Implementadas:**
- ✅ Soft Deletes con migración `2026_01_18_020617_add_soft_deletes_to_tipos_inmueble_table.php`
- ✅ Auditoría con migración `2026_01_21_213421_add_audit_fields_to_tipos_inmueble_table.php`
- ✅ Policy `TipoInmueblePolicy` con permisos completos (incluyendo `restore` y `forceDelete`)
- ✅ Form Requests `StoreTipoInmuebleRequest` y `UpdateTipoInmuebleRequest`
- ✅ Eventos del modelo en `boot()` para auto-auditoría
- ✅ Método `destroy()` con verificación de dependencias, transacciones y logging
- ✅ Método `store()` con transacciones y manejo de errores
- ✅ Método `update()` con transacciones y manejo de errores
- ✅ Vista `list.blade.php` con `withCount()`, carga de `createdBy` y columna "Creado por"
- ✅ Vista `read.blade.php` con sección de auditoría (creado por, actualizado por)
- ✅ Modal de confirmación de eliminación en `browse.blade.php`

**Archivos Modificados/Creados:**
- `app/Models/TipoInmueble.php` - Agregado trait `SoftDeletes`, campos `created_by`, `updated_by` a fillable, relaciones `createdBy`, `updatedBy`, eventos en `boot()`, `$hidden`
- `app/Http/Controllers/TipoInmuebleController.php` - Actualizado con `authorize()` en todos los métodos, transacciones de DB, manejo de errores con Log, carga de relaciones
- `app/Http/Requests/StoreTipoInmuebleRequest.php` - Con validación y autorización
- `app/Http/Requests/UpdateTipoInmuebleRequest.php` - Con validación con `Rule::unique()->ignore()`
- `app/Policies/TipoInmueblePolicy.php` - Agregados métodos `restore()` y `forceDelete()`
- `resources/views/admin/tipos-inmueble/read.blade.php` - Actualizado con sección de auditoría
- `resources/views/admin/tipos-inmueble/list.blade.php` - Actualizado con columna "Creado por"
- `resources/views/admin/tipos-inmueble/browse.blade.php` - Modal de confirmación
- Migraciones: `2026_01_18_020617_add_soft_deletes_to_tipos_inmueble_table.php`, `2026_01_21_213421_add_audit_fields_to_tipos_inmueble_table.php`

**Beneficios:**
- Auditoría completa de cambios
- Prevención de eliminación de registros en uso
- Código más seguro y mantenible
- Soft deletes para recuperación de datos
- Transacciones garantizan integridad
- Logging facilita depuración
