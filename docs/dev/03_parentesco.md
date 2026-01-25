# Documentación Técnica - Módulo de Parentescos

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
13. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **Parentescos** gestiona los tipos de relaciones familiares que pueden existir entre los adquirentes y disponentes en los trámites del Impuesto de Transmisiones Gratuítas de Bienes (ITGB). Este módulo es fundamental para calcular las tasas impositivas correspondientes según el tipo de parentesco.

### Propósito
- Mantener un catálogo de tipos de parentescos (Padre, Madre, Hermano, Hijos, etc.)
- Asociar tasas impositivas a cada parentesco por departamento
- Permitir la selección del parentesco al crear adquirentes en trámites
- Gestionar el ciclo de vida CRUD completo de parentescos
- Mantener auditoría de cambios y permitir recuperación de registros eliminados

### Importancia en el Sistema ITGB
El parentesco seleccionado para un adquirente determina directamente la tasa impositiva aplicable en el cálculo del ITGB. Diferentes parentescos tienen tasas diferentes según la legislación vigente en cada departamento.

---

## 🗄️ Base de Datos

### Estructura de la tabla: `parentescos`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre del parentesco (ej: "Padre", "Hermano") |
| `created_by` | BIGINT | FK → users.id, NULLABLE | Usuario que creó el registro |
| `updated_by` | BIGINT | FK → users.id, NULLABLE | Usuario que actualizó el registro |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |
| `deleted_at` | TIMESTAMP | NULLABLE | Fecha de eliminación suave |

**Relaciones:**
- Tiene muchos `Tasa` (un parentesco puede tener múltiples tasas asociadas por departamento)
- Es utilizado por la tabla pivote `adquirentes_tramites`
- Pertenece a `User` (created_by, updated_by)

**Migraciones aplicadas:**
- `2025_09_22_122721_create_parentescos_table.php` - Creación de tabla
- `2026_01_17_233959_add_soft_deletes_to_parentescos_table.php` - Soft Deletes
- `2026_01_17_234227_add_audit_fields_to_parentescos_table.php` - Campos de auditoría

---

## 🧩 Modelo

### Modelo: `Parentesco`

**Ubicación:** `app/Models/Parentesco.php`

**Traits:**
- `HasFactory`
- `SoftDeletes`

**Atributos:**
- `$table = 'parentescos'`
- `$fillable = ['nombre', 'created_by', 'updated_by']`

**Relaciones:**
```php
public function tasas()
{
    return $this->hasMany(Tasa::class);
}

public function adquirentesTramite()
{
    return $this->hasMany(AdquirenteTramite::class);
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

**Eventos de modelo:**
```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (auth()->check()) {
            $model->created_by = auth()->id();
        }
    });

    static::updating(function ($model) {
        if (auth()->check()) {
            $model->updated_by = auth()->id();
        }
    });
}
```

---

## 🎮 Controlador

### Controlador: `ParentescoController`

**Ubicación:** `app/Http/Controllers/ParentescoController.php`

**Middleware:** `auth`

### Métodos del Controlador

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| `index()` | `GET /admin/parentescos` | `admin.parentescos.index` | Listado principal |
| `list(Request $request)` | `GET /admin/parentescos/ajax/list` | `admin.parentescos.ajax.list` | Listado AJAX |
| `show(Parentesco $parentesco)` | `GET /admin/parentescos/{parentesco}` | `admin.parentescos.show` | Ver detalle |
| `create()` | `GET /admin/parentescos/create` | `admin.parentescos.create` | Formulario crear |
| `store(StoreParentescoRequest $request)` | `POST /admin/parentescos` | `admin.parentescos.store` | Guardar nuevo |
| `edit(Parentesco $parentesco)` | `GET /admin/parentescos/{parentesco}/edit` | `admin.parentescos.edit` | Formulario editar |
| `update(UpdateParentescoRequest $request, Parentesco $parentesco)` | `PUT /admin/parentescos/{parentesco}` | `admin.parentescos.update` | Actualizar |
| `destroy(Parentesco $parentesco)` | `DELETE /admin/parentescos/{parentesco}` | `admin.parentescos.destroy` | Eliminar (soft delete) |

**Lógica destacada:**
- `list()`: Retorna lista paginada con `withCount(['tasas', 'adquirentesTramite'])`
- `store()`/`update()`: Delegan validación a Form Requests y registran automáticamente `created_by`/`updated_by`
- `destroy()`: Verifica dependencias antes de eliminar, realiza soft delete, registra errores en Log

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::resource('parentescos', ParentescoController::class)
    ->names('admin.parentescos');

Route::get('parentescos/ajax/list', [ParentescoController::class, 'list'])
    ->name('admin.parentescos.ajax.list');
```

**Middleware aplicado:** `loggin`, `system`

---

## 🔒 Policies

### Policy: `ParentescoPolicy`

**Ubicación:** `app/Policies/ParentescoPolicy.php`

| Método | Permiso requerido | Descripción |
|--------|-------------------|-------------|
| `viewAny()` | `browse_parentescos` | Listar parentescos |
| `view()` | `read_parentescos` | Ver detalle de parentesco |
| `create()` | `add_parentescos` | Crear parentesco |
| `update()` | `edit_parentescos` | Editar parentesco |
| `delete()` | `delete_parentescos` | Eliminar parentesco |

### Permisos por Rol

| Rol | browse | read | add | edit | delete |
|-----|--------|------|-----|------|--------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| Operador | ✓ | ✓ | ✓ | ✓ | ✗ |
| Visitante | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## ✅ Requests

### StoreParentescoRequest

**Ubicación:** `app/Http/Requests/StoreParentescoRequest.php`

**Validación:**
- `nombre`: required, string, max:50, unique:parentescos

**Autorización:** `Gate::allows('create', Parentesco::class)`

### UpdateParentescoRequest

**Ubicación:** `app/Http/Requests/UpdateParentescoRequest.php`

**Validación:**
- `nombre`: required, string, max:50, unique:parentescos,nombre,{id}

**Autorización:** `Gate::allows('update', parentesco)`

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/parentescos/`

| Vista | Descripción |
|-------|-------------|
| `browse.blade.php` | Listado principal con búsqueda, paginación, botón crear |
| `list.blade.php` | Contenido tabla AJAX con badges de contadores |
| `edit_add.blade.php` | Formulario unificado para crear/editar |
| `read.blade.php` | Vista detalle con información completa |

---

## 🔗 Integración con otros Módulos

### 1. Módulo de Trámites (Wizard)

**Controlador:** `Admin\TramiteWizardController`

**Uso en Paso 3 - Adquirentes:**
Carga parentescos con tasas vigentes para el departamento del Beni, mapeando la tasa aplicable a cada parentesco.

### 2. Módulo de Cálculo ITGB

**Servicio:** `App\Services\IdtgbCalculator`

**Métodos optimizados:**
- `Tasa::findApplicableRate($depId, $parentescoId, $tipoId, $fecha)` - Centraliza lógica de búsqueda
- `Tasa::vigente($depId, $parId, $fecha, $tipoId)` - Prioriza tasas específicas sobre genéricas

### 3. Tabla adquirentes_tramites

La tabla pivote incluye el campo `parentesco_id` que vincula el adquirente con su tipo de parentesco.

### 4. Índice compuesto en tasas

**Migración:** `2026_01_17_234526_add_composite_index_to_tasas_table.php`

Índice: `[departamento_id, parentesco_id, tipo_transmision_id, vigente_desde]` optimiza búsquedas de tasas.

---

## 📚 Ejemplos de Uso

### Crear nuevo parentesco
```php
$parentesco = Parentesco::create(['nombre' => 'Padre']);
```

### Obtener parentescos con tasas
```php
$parentescos = Parentesco::with('tasas')->get();
```

### Verificar dependencias antes de eliminar
```php
if ($parentesco->tasas()->exists()) {
    throw new Exception('Tiene tasas asociadas');
}
if ($parentesco->adquirentesTramite()->exists()) {
    throw new Exception('En uso en trámites');
}
```

### Restaurar parentesco eliminado
```php
$parentesco = Parentesco::onlyTrashed()->find(1);
$parentesco->restore();
```

### Listado con contadores
```php
$parentescos = Parentesco::withCount(['tasas', 'adquirentesTramite'])
    ->orderBy('id', 'desc')
    ->paginate(10);
```

---

## 🔍 Consideraciones Importantes

### Reglas de Negocio
1. **Unicidad:** El nombre del parentesco debe ser único en todo el sistema
2. **Dependencias:** No se puede eliminar un parentesco si tiene tasas asociadas o está en uso en trámites
3. **Longitud máxima:** El nombre no puede exceder 50 caracteres
4. **Tasas vigentes:** Solo se muestran tasas vigentes en el wizard de trámites
5. **Requerido:** El parentesco es obligatorio para los adquirentes en trámites
6. **Soft Deletes:** La eliminación es suave, los registros pueden restaurarse
7. **Auditoría:** Se registra automáticamente quién crea y modifica cada parentesco

### Validaciones Implementadas
- **Crear:** Nombre requerido, único, máx 50 caracteres
- **Actualizar:** Nombre requerido, único (ignorando actual), máx 50 caracteres
- **Eliminar:** Verifica tasas asociadas, verifica uso en trámites, soft delete, logs de errores

---

## 📝 Guía para Desarrolladores

### Extender el módulo

**Agregar un nuevo campo (ej: descripcion):**

1. **Migración:** `php artisan make:migration add_descripcion_to_parentescos_table --table=parentescos`
2. **Modelo:** Agregar `'descripcion'` a `$fillable`
3. **Requests:** Agregar regla `'descripcion' => 'nullable|string|max:500'`
4. **Vistas:** Agregar campo textarea en `edit_add.blade.php`

### Buenas Prácticas
1. **Validación:** Usar Form Requests para validación centralizada
2. **Autorización:** Siempre verificar permisos en Policies
3. **Eager Loading:** Usar `with()` para relaciones (evitar N+1)
4. **Cache:** Considerar caché para listados que no cambian frecuentemente
5. **Logs:** Los errores se registran automáticamente en `destroy()`
6. **Soft Deletes:** Usar para mantener histórico
7. **Eventos del Modelo:** Registra automáticamente `created_by` y `updated_by`
8. **withCount:** Usar para mostrar contadores de relaciones en listados

### Comandos Útiles
```bash
# Crear nueva migración
php artisan make:migration add_field_to_parentescos_table --table=parentescos

# Crear controller
php artisan make:controller ParentescoController --resource

# Crear request
php artisan make:request StoreParentescoRequest

# Crear policy
php artisan make:policy ParentescoPolicy --model=Parentesco

# Ejecutar tests
php artisan test --filter ParentescoTest
```

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (Enero 2026) ✅

Todos los bugs y mejoras identificados en el análisis han sido implementados exitosamente. El módulo de Parentescos ahora cuenta con:

- ✅ Soft Deletes implementados
- ✅ Auditoría completa con campos `created_by` y `updated_by`
- ✅ Policy para autorizaciones
- ✅ Validación centralizada con Form Requests
- ✅ `withCount` para optimizar listados
- ✅ Protección contra eliminación de registros con dependencias
- ✅ Manejo de errores con Log

### 🐛 Bugs Corregidos ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Protección contra eliminación con dependencias | ✅ Corregido | `ParentescoController.php:destroy()` |
| 2 | Manejo de errores débil | ✅ Corregido | `ParentescoController.php:destroy()` (Log) |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Soft Deletes | Trait agregado, migración ejecutada, permite restaurar registros |
| 2 | Auditoría completa | Campos `created_by`, `updated_by` con eventos automáticos |
| 3 | Policy | `ParentescoPolicy` con permisos por acción |
| 4 | Validación centralizada | `StoreParentescoRequest` y `UpdateParentescoRequest` |
| 5 | withCount optimizado | `withCount(['tasas', 'adquirentesTramite'])` en listados |
| 6 | Índice compuesto en tasas | `[departamento_id, parentesco_id, tipo_transmision_id, vigente_desde]` |
| 7 | Método centralizado de tasas | `Tasa::findApplicableRate()` con lógica unificada |
| 8 | Badges dinámicos | Vista con contadores de relaciones y colores según estado |

### 📝 Historial de Cambios

### v2.0.0 (Enero 2026)

**Mejoras Implementadas:**
- ✅ Soft Deletes con migración `2026_01_17_233959_add_soft_deletes_to_parentescos_table.php`
- ✅ Auditoría con migración `2026_01_17_234227_add_audit_fields_to_parentescos_table.php`
- ✅ Policy `ParentescoPolicy` con permisos `browse`, `read`, `add`, `edit`, `delete`
- ✅ Form Requests `StoreParentescoRequest` y `UpdateParentescoRequest`
- ✅ Método `destroy()` con verificación de dependencias y logging
- ✅ Vista `list.blade.php` con `withCount()` y badges dinámicos

**Archivos Modificados/Creados:**
- `app/Models/Parentesco.php` - Agregado trait `SoftDeletes`, relaciones `createdBy`, `updatedBy`, eventos en `boot()`
- `app/Http/Controllers/ParentescoController.php` - Actualizado `destroy()` con validaciones y Log
- `app/Http/Requests/StoreParentescoRequest.php` - Creado con validación
- `app/Http/Requests/UpdateParentescoRequest.php` - Creado con validación
- `app/Policies/ParentescoPolicy.php` - Creado con permisos
- `resources/views/admin/parentescos/list.blade.php` - Actualizado con badges
- Migraciones: `2026_01_17_233959_add_soft_deletes_to_parentescos_table.php`, `2026_01_17_234227_add_audit_fields_to_parentescos_table.php`
- Migración en `tasas`: `2026_01_17_234526_add_composite_index_to_tasas_table.php`

**Beneficios:**
- Cálculos más precisos cuando existen tasas específicas y genéricas
- Previene errores legales en el cálculo del impuesto
- Código más mantenible y reutilizable
- Auditoría completa de cambios
- Protección contra eliminación de registros en uso
