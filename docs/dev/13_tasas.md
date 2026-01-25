# Documentación Técnica - Módulo de Tasas

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelo](#modelo)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Policies](#policies)
7. [Requests](#requests)
8. [Vistas](#vistas)
9. [Integración](#integración)
10. [Guía para Desarrolladores](#guía-para-desarrolladores)

---

## 🎯 Introducción

El módulo de **Tasas** gestiona las tasas impositivas aplicables al Impuesto de Transmisiones Gratuítas de Bienes (ITGB) según el departamento, parentesco y tipo de transmisión.

### Propósito
- Mantener un catálogo de tasas impositivas por departamento, parentesco y tipo de transmisión
- Permitir la vigencia temporal de tasas con fechas de inicio y fin
- Facilitar el cálculo automático del ITGB en trámites
- Gestionar el ciclo de vida CRUD completo de tasas

### Importancia
La tasa seleccionada determina directamente el monto del impuesto a pagar. Diferentes combinaciones de departamento, parentesco y tipo de transmisión pueden tener tasas diferentes según la legislación vigente.

---

## 🗄️ Base de Datos

### Migración: `create_tasas_table.php`

**Ubicación:** `database/migrations/2025_09_22_122739_create_tasas_table.php`

**Estructura de la tabla:**

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `departamento_id` | BIGINT | FK, NOT NULL | Departamento al que aplica la tasa |
| `parentesco_id` | BIGINT | FK, NOT NULL | Parentesco del adquirente |
| `tipo_transmision_id` | BIGINT | FK, NULLABLE | Tipo de transmisión (opcional) |
| `tasa` | DECIMAL(5,2) | NOT NULL | Porcentaje de tasa (0.00 a 99.99) |
| `vigente_desde` | DATE | NOT NULL | Fecha desde la que está vigente |
| `vigente_hasta` | DATE | NULLABLE | Fecha hasta la que está vigente (null = indefinido) |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |

**Relaciones:**
- Pertenece a `Departamento` (departamento_id)
- Pertenece a `Parentesco` (parentesco_id)
- Pertenece a `TipoTransmision` (tipo_transmision_id, opcional)

**Índice único compuesto:**
```php
$table->unique(
    ['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'vigente_desde'],
    'uq_tasas_dep_par_trans_vig'
);
```

---

## 🧩 Modelo

### Modelo: `Tasa`

**Ubicación:** `app/Models/Tasa.php`

**Atributos:**
- `$table = 'tasas'`
- `$fillable = ['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'tasa', 'vigente_desde', 'vigente_hasta']`

**Casts:**
- `tasa` → `decimal:2`
- `vigente_desde` → `date`
- `vigente_hasta` → `date`

**Relaciones:**
```php
public function departamento()
{
    return $this->belongsTo(Departamento::class);
}

public function parentesco()
{
    return $this->belongsTo(Parentesco::class);
}

public function tipoTransmision()
{
    return $this->belongsTo(TipoTransmision::class, 'tipo_transmision_id');
}
```

**Métodos Helper:**

### `vigente(int $departamentoId, int $parentescoId, ?string $fecha = null, ?int $tipoTransmisionId = null): ?self`

Retorna la tasa vigente para un departamento, parentesco y fecha específicos. Si se especifica `tipoTransmisionId`, prioriza tasas específicas sobre genéricas.

### `findApplicableRate(int $departamentoId, int $parentescoId, ?int $tipoTransmisionId, string $fecha): ?self`

Método centralizado para buscar tasas aplicables. Prioriza tasas específicas sobre genéricas.

---

## 🎮 Controlador

### Controlador: `TasaController`

**Ubicación:** `app/Http/Controllers/TasaController.php`

**Middleware:** `auth`

### Métodos del Controlador

| Método | Descripción | Permiso |
|--------|-------------|---------|
| `index()` | Vista principal | `browse_tasas` |
| `list()` | Listado AJAX | `browse_tasas` |
| `show(Tasa $tasa)` | Ver detalle | `read_tasas` |
| `create()` | Formulario crear | `add_tasas` |
| `store(StoreTasaRequest $request)` | Guardar nuevo | `add_tasas` |
| `edit(Tasa $tasa)` | Formulario editar | `edit_tasas` |
| `update(UpdateTasaRequest $request, Tasa $tasa)` | Actualizar | `edit_tasas` |
| `destroy(Tasa $tasa)` | Eliminar | `delete_tasas` |

**Validación en destroy():**
Verifica que la tasa no esté siendo utilizada en trámites antes de eliminar.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::resource('tasas', TasaController::class)->names('admin.tasas');
Route::get('tasas/ajax/list', [TasaController::class, 'list'])->name('admin.tasas.ajax.list');
```

| Método | URI | Nombre |
|--------|-----|--------|
| GET | `/admin/tasas` | `admin.tasas.index` |
| GET | `/admin/tasas/create` | `admin.tasas.create` |
| POST | `/admin/tasas` | `admin.tasas.store` |
| GET | `/admin/tasas/{tasa}` | `admin.tasas.show` |
| GET | `/admin/tasas/{tasa}/edit` | `admin.tasas.edit` |
| PUT/PATCH | `/admin/tasas/{tasa}` | `admin.tasas.update` |
| DELETE | `/admin/tasas/{tasa}` | `admin.tasas.destroy` |
| GET | `/admin/tasas/ajax/list` | `admin.tasas.ajax.list` |

---

## 🔒 Policies

### Policy: `TasaPolicy`

**Ubicación:** `app/Policies/TasaPolicy.php`

| Método | Permiso requerido | Descripción |
|--------|-------------------|-------------|
| `viewAny()` | `browse_tasas` | Listar tasas |
| `view()` | `read_tasas` | Ver detalle de tasa |
| `create()` | `add_tasas` | Crear tasa |
| `update()` | `edit_tasas` | Editar tasa |
| `delete()` | `delete_tasas` | Eliminar tasa |
| `before()` | `browse_admin` | Bypass para admin |

---

## ✅ Requests

### StoreTasaRequest

**Reglas de validación:**
```php
'departamento_id' => 'required|exists:departamentos,id',
'parentesco_id' => 'required|exists:parentescos,id',
'tipo_transmision_id' => 'nullable|exists:tipos_transmision,id',
'tasa' => 'required|numeric|min:0|max:99.99',
'vigente_desde' => 'required|date',
'vigente_hasta' => 'nullable|date|after_or_equal:vigente_desde',
```

### UpdateTasaRequest

**Reglas de validación:**
- Mismas reglas que `StoreTasaRequest`
- Excluye el ID actual en la validación de unicidad

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/tasas/`

- **`browse.blade.php`**: Vista principal con búsqueda y filtrado.
- **`list.blade.php`**: Tabla AJAX con tasas.
- **`edit-add.blade.php`**: Formulario crear/editar.
- **`read.blade.php`**: Vista detalle.

---

## 🔗 Integración

### Servicio IdtgbCalculator

**Ubicación:** `app/Services/IdtgbCalculator.php`

El servicio usa el método `tasaVigente()` para obtener la tasa aplicable al calcular el ITGB.

### Módulo de Adquirentes en Trámites

**Controlador:** `AdquirenteTramiteController`

Usa `Tasa::findApplicableRate()` para buscar la tasa aplicable al agregar un adquirente.

### TramiteWizard - Paso 3: Adquirentes

**Controlador:** `Admin\TramiteWizardController`

Carga parentescos con sus tasas correspondientes para mostrar en el wizard.

---

## 📝 Guía para Desarrolladores

### Buenas Prácticas

1. **Validación:** Usar Form Requests para validación centralizada
2. **Autorización:** Verificar permisos en Policies antes de ejecutar acciones
3. **Carga diferida:** Usar `with()` para relaciones en consultas de listado
4. **Vigencia:** Siempre verificar que las tasas usadas estén vigentes en la fecha del trámite

### Ejemplos de Uso

**Crear una nueva tasa:**
```php
Tasa::create([
    'departamento_id' => 1,
    'parentesco_id' => 1,
    'tipo_transmision_id' => null,
    'tasa' => 1.50,
    'vigente_desde' => '2025-01-01',
    'vigente_hasta' => null
]);
```

**Buscar tasa vigente:**
```php
$tasa = Tasa::vigente($departamentoId, $parentescoId);
```

**Calcular ITGB usando tasas:**
```php
use App\Services\IdtgbCalculator;

$calculator = app(IdtgbCalculator::class);
$liquidacion = $calculator->calculateEstimate(
    $baseImponible,
    $departamentoId,
    $parentescoId,
    $tipoTransmisionId,
    $fechaTransmision,
    $fechaPresentacion,
    $fechaVencimiento
);
```

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (20 de enero de 2026) ✅

El módulo de Tasas ha sido optimizado y cuenta con funcionalidades clave para el cálculo correcto del ITGB:

- ✅ Índice único compuesto implementado para integridad de datos
- ✅ Métodos centralizados para búsqueda de tasas vigentes
- ✅ Protección contra eliminación de tasas en uso en trámites
- ✅ Priorización de tasas específicas sobre genéricas
- ✅ Autorización implementada en todos los métodos del controlador
- ✅ Validación de unicidad compuesta con vigencia temporal

### 🐛 Bugs Corregidos (4/4) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Race condition en validación de unicidad | ✅ Corregido | Migración `2026_01_17_234526_add_composite_index...` |
| 2 | Falta validación de uso al eliminar tasas | ✅ Corregido | `TasaController.php:97-104` |
| 3 | Búsqueda ineficiente de tasas aplicables | ✅ Corregido | `Tasa.php:65-77` |
| 4 | Falta priorización de tasas específicas | ✅ Corregido | `Tasa.php:65-77` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Índice único compuesto | Índice `[departamento_id, parentesco_id, tipo_transmision_id, vigente_desde]` para evitar duplicados |
| 2 | Método `vigente()` centralizado | Busca tasa vigente por departamento y parentesco, con prioridad de específicas |
| 3 | Método `findApplicableRate()` centralizado | Método unificado para buscar tasas aplicables usado por IdtgbCalculator |
| 4 | Validación de uso en destroy() | Verifica que la tasa no esté en uso en trámites antes de eliminar |
| 5 | Priorización de tasas específicas | Tasas con `tipo_transmision_id` tienen prioridad sobre genéricas (null) |
| 6 | Autorización completa | Llamadas `authorize()` en todos los métodos del controlador |
| 7 | Eager loading en listados | Carga relaciones en consultas para evitar N+1 queries |
| 8 | Validación de unicidad temporal | Índice compuesto incluye fecha de vigencia para permitir mismas combinaciones en diferentes períodos |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **MEDIO** | Sistema de versionado de tasas | Permitir historial completo de cambios en tasas con auditoría de modificaciones |
| **MEDIO** | Validación de vigencia no superpuesta | Prevenir que tasas del mismo tipo tengan períodos de vigencia que se superpongan |
| **MEDIO** | Caching de tasas vigentes | Implementar cache para tasas frecuentemente usadas en cálculos |
| **BAJO** | Soft Deletes | Implementar soft deletes para permitir recuperación de tasas eliminadas |
| **BAJO** | Importación/Exportación masiva | Sistema para importar/exportar tasas desde CSV/Excel |
| **BAJO** | Validación de rangos de tasas | Asegurar que tasas estén dentro de rangos legales (ej: 0% a 99.99%) |
| **BAJO** | Notificaciones de cambios | Sistema de alertas cuando se modifican tasas que afectan trámites activos |

### 📝 Historial de Cambios

### v2.0.0 (20 de enero de 2026)
**Correcciones Completadas (4/4):**
- ✅ Bug #1: Race condition - Agregado índice único compuesto en migración `2026_01_17_234526_add_composite_index_to_tasas_table.php`
- ✅ Bug #2: Validación de uso - Implementada verificación en `destroy()` línea 97-104 de `TasaController.php`
- ✅ Bug #3: Búsqueda ineficiente - Centralizada con método `findApplicableRate()` en `Tasa.php`
- ✅ Bug #4: Priorización - Implementada lógica de priorización de tasas específicas sobre genéricas

**Archivos Modificados/Creados:**
- `app/Models/Tasa.php`:
  - Agregado método estático `vigente()` para búsqueda de tasas vigentes con priorización
  - Agregado método estático `findApplicableRate()` para búsqueda centralizada de tasas aplicables
  - Métodos implementan lógica de priorización: tasas específicas (`tipo_transmision_id`) sobre genéricas (`null`)
- `app/Http/Controllers/TasaController.php`:
  - Agregado método `destroy()` con verificación de uso en trámites
  - Validación busca tasas en uso a través de relaciones con AdquirenteTramite → Tramite → Inmueble → Municipio → Provincia → Departamento
- `database/migrations/2026_01_17_234526_add_composite_index_to_tasas_table.php` - Nueva migración de índice compuesto

**Beneficios:**
- Prevención de duplicados a nivel de base de datos con índice compuesto
- Código más mantenible con métodos centralizados de búsqueda de tasas
- Prevención de eliminación de tasas en uso que afectarían trámites existentes
- Cálculos más precisos cuando existen tasas específicas y genéricas
- Previene errores legales y financieros en el cálculo del impuesto
