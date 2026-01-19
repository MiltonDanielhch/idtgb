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

**Última actualización:** Enero 2026
**Versión:** 2.0.0

## 📝 Historial de Cambios

### Versión 2.0.0 (Enero 2026)

**Bugs Corregidos:**
1. ✅ Protección contra eliminación de tasas en uso
2. ✅ Validación de unicidad en actualización
3. ✅ Priorización de tasas específicas
4. ✅ Eliminación de hardcoded departamento_id

**Mejoras Implementadas:**
- Código más robusto y seguro
- Mejor manejo de errores con mensajes específicos
- Centralización de lógica de búsqueda de tasas
- Prevención de errores legales y financieros
