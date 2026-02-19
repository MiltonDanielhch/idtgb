# Documentación Técnica: Módulo TrámiteExenciones

Este documento detalla la implementación técnica del módulo `TrámiteExenciones`, diseñado para gestionar las exenciones aplicadas a un trámite específico en el sistema.

## 1. Propósito del Módulo

El módulo `TrámiteExenciones` gestiona la relación muchos a muchos entre los `Trámites` y las `Exenciones`. Permite aplicar una o más exenciones a un trámite, registrando el monto específico que se deduce por cada una. Cada vez que se agrega o elimina una exención, el sistema recalcula automáticamente los montos totales del trámite.

## 2. Estructura de la Base de Datos

La relación se gestiona a través de una tabla pivote `tramite_exenciones` con índices optimizados y soporte para soft deletes.

### Migración Original: `2025_09_22_122804_create_tramite_exenciones_table.php`

```php
Schema::create('tramite_exenciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tramite_id')->constrained();
    $table->foreignId('exencion_id')->constrained('exenciones');
    $table->decimal('monto_aplicado', 14, 2);
    $table->timestamps();
});
```

### Migración de Corrección: `2025_02_19_000001_fix_exenciones_bugs.php`

```php
// Agrega índice único para prevenir duplicados (Bug #1)
Schema::table('tramite_exenciones', function (Blueprint $table) {
    $table->unique(['tramite_id', 'exencion_id'], 'unique_tramite_exencion');
    $table->softDeletes();
});

// Agrega soft deletes a exenciones (Bug #8)
Schema::table('exenciones', function (Blueprint $table) {
    $table->softDeletes();
});
```

-   **`tramite_id`**: FK a la tabla `tramites`.
-   **`exencion_id`**: FK a la tabla `exenciones`.
-   **`monto_aplicado`**: El valor monetario específico que se descuenta del trámite en virtud de esta exención.
-   **`deleted_at`**: Timestamp para soft deletes (mantiene historial de exenciones eliminadas).
-   **Índice único**: `unique(['tramite_id', 'exencion_id'])` previene race conditions al aplicar la misma exención dos veces.

## 3. Modelos (Eloquent)

Se utiliza un modelo pivote (`TramiteExencion`) para facilitar la gestión directa de la relación.

### a. `App\Models\TramiteExencion`

Este es el modelo para la tabla pivote.

-   **Tabla**: `tramite_exenciones`
-   **Fillable**: `['tramite_id', 'exencion_id', 'monto_aplicado']`
-   **Traits**: `SoftDeletes` (mantiene historial de exenciones eliminadas)
-   **Relaciones**:
    -   `tramite()`: `belongsTo(Tramite::class)`
    -   `exencion()`: `belongsTo(Exencion::class)`
-   **Métodos Helper**:
    -   `isVigente()`: Verifica si la exención está vigente para la fecha del trámite
    -   `getMontoFormateadoAttribute()`: Accessor para mostrar el monto formateado (Bs. X.XX)

```php
// app/Models/TramiteExencion.php

class TramiteExencion extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $table = 'tramite_exenciones';
    
    protected $fillable = [
        'tramite_id',
        'exencion_id',
        'monto_aplicado',
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function exencion()
    {
        return $this->belongsTo(Exencion::class);
    }
    
    public function isVigente(): bool
    {
        if (!$this->tramite || !$this->exencion) {
            return false;
        }
        return $this->exencion->isVigente($this->tramite->fecha_presentacion->toDateString());
    }
    
    public function getMontoFormateadoAttribute(): string
    {
        return 'Bs. ' . number_format($this->monto_aplicado, 2);
    }
}
```

### b. Relaciones en Modelos Relacionados

#### `App\Models\Tramite`

El modelo `Tramite` tiene dos relaciones clave para manejar las exenciones:

1.  `exenciones()`: Una relación estándar `belongsToMany` para obtener directamente la colección de `Exencion` aplicadas.
2.  `tramiteExenciones()`: Una relación `hasMany` con el modelo pivote `TramiteExencion`, que permite un control más granular (como la eliminación directa de una entrada pivote).

```php
// app/Models/Tramite.php

public function exenciones()
{
    return $this->belongsToMany(Exencion::class, 'tramite_exenciones')
                ->withPivot('monto_aplicado');
}

public function tramiteExenciones()
{
    return $this->hasMany(\App\Models\TramiteExencion::class);
}
```

#### `App\Models\Exencion`

El modelo `Exencion` tiene la relación inversa para encontrar todos los trámites asociados.

```php
// app/Models/Exencion.php

public function tramites()
{
    return $this->belongsToMany(Tramite::class, 'tramite_exenciones')
                ->withPivot('monto_aplicado')
                ->withTimestamps();
}
```

## 4. Rutas (Web)

Las rutas para este módulo son anidadas dentro de `tramites` y siguen una convención RESTful.

**Archivo**: `routes/web.php`

```php
// routes/web.php

Route::prefix('admin')->group(function () {
    // ...
    Route::prefix('tramites/{tramite}/exenciones')->name('admin.tramites.exenciones.')->group(function () {
        Route::get('/', [TramiteExencionController::class, 'index'])->name('index');
        Route::get('/ajax/list', [TramiteExencionController::class, 'list'])->name('ajax.list');
        Route::get('/create', [TramiteExencionController::class, 'create'])->name('create');
        Route::post('/', [TramiteExencionController::class, 'store'])->name('store');
        Route::get('/{item}', [TramiteExencionController::class, 'show'])->name('show');
        Route::delete('/{item}', [TramiteExencionController::class, 'destroy'])->name('destroy');
    });
    // ...
});
```

-   El parámetro `{tramite}` corresponde al ID del `Tramite` padre.
-   El parámetro `{item}` corresponde al ID de la instancia de `TramiteExencion`.

## 5. Controlador: `TramiteExencionController`

**Archivo**: `app/Http/Controllers/TramiteExencionController.php`

Este controlador gestiona el ciclo de vida de una exención aplicada a un trámite.

-   **`index()` y `list()`**: Muestran la lista de exenciones aplicadas para un trámite. `list()` es para la carga vía AJAX.
-   **`create()`**: Muestra el formulario para agregar una nueva exención, listando solo las exenciones vigentes para la **fecha de presentación del trámite** (no usa `now()`).
-   **`store(StoreTramiteExencionRequest $request, Tramite $tramite)`**:
    1.  Valida la solicitud usando `StoreTramiteExencionRequest` (incluye validaciones de monto máximo, vigencia y suma vs impuesto).
    2.  Calcula automáticamente el monto a aplicar usando `calcularMontoExencion()` según el tipo de exención (porcentaje o monto fijo).
    3.  Crea una nueva entrada en `tramite_exenciones` con el monto calculado.
    4.  **Acción Clave**: Invoca a `app(IdtgbCalculator::class)->calcular($tramite)` para recalcular los montos del trámite.
    5.  Toda la operación se ejecuta dentro de una transacción de base de datos (`DB::transaction`).
-   **`destroy(Tramite $tramite, TramiteExencion $item)`**:
    1.  Elimina la entrada de la tabla `tramite_exenciones`.
    2.  **Acción Clave**: Invoca nuevamente a `app(IdtgbCalculator::class)->calcular($tramite)` para actualizar los montos.
    3.  Se ejecuta dentro de una transacción.

## 6. Validación: `StoreTramiteExencionRequest`

**Archivo**: `app/Http/Requests/StoreTramiteExencionRequest.php`

Este FormRequest valida los datos para agregar una exención con múltiples capas de seguridad.

-   **`authorize()`**: Usa `Gate::allows('create', TramiteExencion::class)` para verificar permisos.
-   **`rules()`**:
    -   `exencion_id`: Requerido y debe existir en la tabla `exenciones`.
    -   `monto_aplicado`: Requerido, numérico y mayor que cero.
-   **Validación Personalizada (`withValidator`)**:
    -   **Unicidad**: Asegura que la misma exención no se pueda aplicar dos veces al mismo trámite (protegido también por índice único en BD).
    -   **Monto máximo**: Valida que el monto aplicado no exceda el `monto_maximo` definido en la exención.
    -   **Vigencia**: Valida que la exención estuviera vigente en la fecha de presentación del trámite (no usa `now()`).
    -   **Suma vs Impuesto**: Valida que la suma total de exenciones aplicadas no exceda el impuesto calculado del trámite.

## 7. Autorización: `TramiteExencionPolicy`

**Archivo**: `app/Policies/TramiteExencionPolicy.php`

Define quién puede realizar acciones sobre las `TramiteExencion`. Se basa en los permisos de Voyager.

-   `viewAny`: Requiere permiso `browse_tramite_exenciones`.
-   `view`: Requiere permiso `read_tramite_exenciones`.
-   `create`: Requiere permiso `add_tramite_exenciones`.
-   `delete`: Requiere permiso `delete_tramite_exenciones`.
-   `before`: Otorga acceso total a los usuarios con el permiso `browse_admin`.

## 8. Lógica de Negocio Crítica: `IdtgbCalculator`

**Servicio**: `app/Services/IdtgbCalculator.php`

Aunque este servicio tiene una lógica más amplia, es un componente fundamental del flujo de `TramiteExencion`.

-   El método `calcular($tramite)` es invocado por el `TramiteExencionController` en los métodos `store` y `destroy`.
-   **Función**: Recalcula los valores monetarios del trámite (como `total_idtgb`, `monto_final`, etc.) basándose en el estado actual de sus relaciones, incluidas las exenciones. La eliminación o adición de una exención dispara este recálculo para mantener la integridad de los datos financieros del trámite.

## 9. Flujo de Operación (Resumen)

1.  Un usuario navega a la sección de exenciones de un trámite (`admin/tramites/{id}/exenciones`).
2.  El `TramiteExencionController@index` muestra las exenciones ya aplicadas.
3.  El usuario hace clic en "Agregar Exención".
4.  `TramiteExencionController@create` le presenta un formulario con las exenciones vigentes.
5.  El usuario envía el formulario.
6.  `TramiteExencionController@store` recibe la petición:
    -   `StoreTramiteExencionRequest` valida que la exención sea válida, no esté duplicada, respete el monto máximo, esté vigente para la fecha del trámite, y que la suma no exceda el impuesto.
    -   `TramiteExencionPolicy` confirma que el usuario tiene permiso para crear.
    -   Se calcula automáticamente el monto según el tipo de exención (porcentaje sobre base imponible o monto fijo), aplicando el límite máximo si existe.
    -   Se crea el registro en `tramite_exenciones`.
    -   Se llama a `IdtgbCalculator` para que el `Tramite` padre actualice sus totales.
    -   El usuario es redirigido a la lista de exenciones.
7.  El proceso de eliminación (`destroy`) sigue un flujo similar pero inverso.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.1.0 (19 Febrero 2026) ✅

El módulo TramiteExenciones está **funcional y listo para producción**. Todos los bugs críticos identificados han sido corregidos:

- ✅ Autorización implementada en todos los métodos del controlador
- ✅ Manejo de transacciones de base de datos en operaciones de escritura
- ✅ Recálculo automático de impuestos al agregar o eliminar exenciones
- ✅ Validación de unicidad de exenciones en el mismo trámite (con índice único en BD)
- ✅ Validación de pertenencia del item al trámite en operaciones de destrucción
- ✅ Uso de fecha de presentación del trámite en lugar de `now()`
- ✅ Índice único compuesto en la base de datos (previene race condition)
- ✅ Validación de monto máximo de exención
- ✅ Validación de que exención no exceda el impuesto calculado
- ✅ Cálculo automático de monto según tipo (porcentaje/monto fijo)
- ✅ SoftDeletes implementado para mantener historial

### 🐛 Bugs Corregidos (7/7) ✅

| # | Bug | Estado | Ubicación | Solución Aplicada |
|---|-----|--------|-----------|-------------------|
| 1 | Falta de autorización en métodos del controlador | ✅ Corregido | `TramiteExencionController.php` | Llamadas `$this->authorize()` en todos los métodos |
| 2 | Falta de manejo de transacciones en operaciones de escritura | ✅ Corregido | `TramiteExencionController.php` | Implementado `DB::transaction` en store y destroy |
| 3 | Race condition en validación de duplicados | ✅ Corregido | `database/migrations/2025_02_19_000001_fix_exenciones_bugs.php` | Índice único `unique(['tramite_id', 'exencion_id'])` |
| 4 | No se valida monto máximo de exención | ✅ Corregido | `StoreTramiteExencionRequest.php:withValidator()` | Validación `$montoAplicado > $exencion->monto_maximo` |
| 5 | Cálculo manual de monto aplicado propenso a errores | ✅ Corregido | `TramiteExencionController.php:calcularMontoExencion()` | Método privado que calcula automáticamente según tipo |
| 6 | Uso de `now()` en lugar de fecha de trámite | ✅ Corregido | `TramiteExencionController.php:create()` | Usa `$tramite->fecha_presentacion` |
| 7 | Falta validación de que exención no exceda el impuesto | ✅ Corregido | `StoreTramiteExencionRequest.php:withValidator()` | Validación `$nuevaSuma > $impuestoCalculado` |

### 🐛 Bugs Pendientes (0/0) ✅

*No hay bugs pendientes. Todos los problemas identificados han sido resueltos.*

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Autorización completa | Llamadas `authorize()` en todos los métodos del controlador |
| 2 | Transacciones DB | Implementado `DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()` para atomicidad |
| 3 | Recálculo automático de impuestos | Llamado a `IdtgbCalculator::calcular($tramite)` tras agregar/eliminar exenciones |
| 4 | Validación de unicidad | Request valida que la exención no se agregue dos veces al mismo trámite |
| 5 | Validación de contexto | Se valida que el `TramiteExencion` pertenezca al `Tramite` antes de eliminar |
| 6 | Cálculo automático de montos | Método `calcularMontoExencion()` calcula según tipo (porcentaje/monto fijo) y aplica monto máximo |
| 7 | Validación de vigencia | Usa fecha de presentación del trámite para validar vigencia de exenciones |
| 8 | Validación de exceso | Valida que suma de exenciones no exceda el impuesto calculado |
| 9 | SoftDeletes | Implementado en modelo para mantener historial de exenciones eliminadas |
| 10 | Índice único en BD | Previene duplicados a nivel de base de datos (race condition) |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **ALTA** | Sistema de tests automatizados | Crear pruebas unitarias y de integración para validación, cálculo y autorizaciones |
| **ALTA** | Documento de sustento obligatorio | Requerir documento que justifique la exención (certificado, decreto, etc.) |
| **MEDIA** | Sistema de auditoría | Agregar campos `created_by`, `deleted_by` con timestamps y usuarios |
| **MEDIA** | Cálculo automático de monto en frontend | Agregar JavaScript para calcular automáticamente monto cuando es porcentaje |
| **MEDIA** | Validación de exclusividad | Validar que ciertas exenciones sean mutuamente excluyentes (ej: dos de tipo "discapacidad") |
| **BAJO** | Caching de exenciones vigentes | Implementar cache para consultas de exenciones vigentes |
| **BAJO** | API endpoints | Exponer endpoints REST API para consumo externo/integraciones |
| **BAJO** | Notificaciones por email | Enviar notificaciones cuando se aplica/quita exención en trámites críticos |
| **BAJO** | Optimizar múltiples recálculos | Implementar cálculo diferido o batch para evitar recalcular en cada operación |
| **BAJO** | Reportes de exenciones | Crear reportes de exenciones más aplicadas, montos totales, por período/tipo |

**Mejoras Ya Implementadas (v1.1.0):**
- ✅ Índice único compuesto `unique(['tramite_id', 'exencion_id'])` - Previene race conditions
- ✅ Soft Deletes - Mantiene historial de exenciones eliminadas
- ✅ Validación de vigencia vs fecha de trámite - Usa fecha de presentación del trámite |

### 📝 Historial de Cambios

### v1.1.0 (19 Febrero 2026) ✅
**Corrección de Bugs Críticos:**
- Todos los 7 bugs identificados han sido corregidos
- El módulo está listo para producción

**Bugs Corregidos en esta Versión (5 adicionales):**
- Bug #3: Race condition - Agregado índice único `unique_tramite_exencion` en BD
- Bug #4: Validación de monto máximo - Implementada en `StoreTramiteExencionRequest`
- Bug #5: Cálculo automático - Método `calcularMontoExencion()` en controlador
- Bug #6: Fecha de vigencia - Usa `$tramite->fecha_presentacion` correctamente
- Bug #7: Validación suma vs impuesto - Implementada en Request

**Archivos Modificados:**
- `database/migrations/2025_02_19_000001_fix_exenciones_bugs.php` - Nueva migración con índices y soft deletes
- `app/Models/TramiteExencion.php` - SoftDeletes + helpers `isVigente()` y `getMontoFormateadoAttribute()`
- `app/Models/Exencion.php` - Métodos helper `isVigente()` y `calcularMonto()`
- `app/Http/Requests/StoreTramiteExencionRequest.php` - Validaciones mejoradas (monto máximo, vigencia, suma)
- `app/Http/Controllers/TramiteExencionController.php` - Cálculo automático y uso de fecha de trámite

### v1.0.0 (Enero 2026)
**Estado Inicial:**
- Documentación técnica completa del módulo TramiteExenciones
- Identificación de 2 bugs corregidos y 5 bugs pendientes
- Identificación de 5 mejoras implementadas y 12 mejoras futuras sugeridas

**Archivos Documentados:**
- `app/Http/Controllers/TramiteExencionController.php`
- `app/Models/TramiteExencion.php`
- `app/Models/Tramite.php`
- `app/Models/Exencion.php`
- `app/Http/Requests/StoreTramiteExencionRequest.php`
- `app/Policies/TramiteExencionPolicy.php`
- `app/Services/IdtgbCalculator.php`
- `database/migrations/2025_09_22_122751_create_exenciones_table.php`
- `database/migrations/2025_09_22_122804_create_tramite_exenciones_table.php`
- `resources/views/admin/tramites/exenciones/browse.blade.php`
- `resources/views/admin/tramites/exenciones/list.blade.php`
- `resources/views/admin/tramites/exenciones/create.blade.php`
- `routes/web.php`

**Bugs Corregidos en v1.0.0 (2/7):**
- ✅ Autorización implementada en todos los métodos
- ✅ Transacciones DB implementadas
- ✅ Recálculo automático de impuestos
- ⚠️ 5 bugs pendientes para v1.1.0