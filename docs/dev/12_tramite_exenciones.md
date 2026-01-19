# Documentación Técnica: Módulo TrámiteExenciones

Este documento detalla la implementación técnica del módulo `TrámiteExenciones`, diseñado para gestionar las exenciones aplicadas a un trámite específico en el sistema.

## 1. Propósito del Módulo

El módulo `TrámiteExenciones` gestiona la relación muchos a muchos entre los `Trámites` y las `Exenciones`. Permite aplicar una o más exenciones a un trámite, registrando el monto específico que se deduce por cada una. Cada vez que se agrega o elimina una exención, el sistema recalcula automáticamente los montos totales del trámite.

## 2. Estructura de la Base de Datos

La relación se gestiona a través de una tabla pivote `tramite_exenciones`.

### Migración: `2025_09_22_122804_create_tramite_exenciones_table.php`

```php
Schema::create('tramite_exenciones', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tramite_id')->constrained();
    $table->foreignId('exencion_id')->constrained('exenciones');
    $table->decimal('monto_aplicado', 14, 2);
    $table->timestamps();
});
```

-   **`tramite_id`**: FK a la tabla `tramites`.
-   **`exencion_id`**: FK a la tabla `exenciones`.
-   **`monto_aplicado`**: El valor monetario específico que se descuenta del trámite en virtud de esta exención.

## 3. Modelos (Eloquent)

Se utiliza un modelo pivote (`TramiteExencion`) para facilitar la gestión directa de la relación.

### a. `App\Models\TramiteExencion`

Este es el modelo para la tabla pivote.

-   **Tabla**: `tramite_exenciones`
-   **Fillable**: `['tramite_id', 'exencion_id', 'monto_aplicado']`
-   **Relaciones**:
    -   `tramite()`: `belongsTo(Tramite::class)`
    -   `exencion()`: `belongsTo(Exencion::class)`

```php
// app/Models/TramiteExencion.php

class TramiteExencion extends Model
{
    // ...
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
-   **`create()`**: Muestra el formulario para agregar una nueva exención, listando solo las exenciones vigentes.
-   **`store(StoreTramiteExencionRequest $request, Tramite $tramite)`**:
    1.  Valida la solicitud usando `StoreTramiteExencionRequest`.
    2.  Crea una nueva entrada en `tramite_exenciones`.
    3.  **Acción Clave**: Invoca a `app(IdtgbCalculator::class)->calcular($tramite)` para recalcular los montos del trámite.
    4.  Toda la operación se ejecuta dentro de una transacción de base de datos (`DB::transaction`).
-   **`destroy(Tramite $tramite, TramiteExencion $item)`**:
    1.  Elimina la entrada de la tabla `tramite_exenciones`.
    2.  **Acción Clave**: Invoca nuevamente a `app(IdtgbCalculator::class)->calcular($tramite)` para actualizar los montos.
    3.  Se ejecuta dentro de una transacción.

## 6. Validación: `StoreTramiteExencionRequest`

**Archivo**: `app/Http/Requests/StoreTramiteExencionRequest.php`

Este FormRequest valida los datos para agregar una exención.

-   **`authorize()`**: Retorna `true`, la autorización real se delega a la Policy en el controlador.
-   **`rules()`**:
    -   `exencion_id`: Requerido y debe existir en la tabla `exenciones`.
    -   `monto_aplicado`: Requerido, numérico y mayor que cero.
-   **Validación Personalizada (`withValidator`)**:
    -   Asegura que la misma exención no se pueda aplicar dos veces al mismo trámite.

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
    -   `StoreTramiteExencionRequest` valida que la exención sea válida y no esté duplicada.
    -   `TramiteExencionPolicy` confirma que el usuario tiene permiso para crear.
    -   Se crea el registro en `tramite_exenciones`.
    -   Se llama a `IdtgbCalculator` para que el `Tramite` padre actualice sus totales.
    -   El usuario es redirigido a la lista de exenciones.
7.  El proceso de eliminación (`destroy`) sigue un flujo similar pero inverso.

---

## 10. ANÁLISIS DE CALIDAD: BUGS, MEJORAS Y OPTIMIZACIONES

### 10.1 BUGS CRÍTICOS IDENTIFICADOS

#### Bug #1: Race Condition en Validación de Duplicados
**Ubicación**: `app/Http/Requests/StoreTramiteExencionRequest.php:23-33`

**Problema**: La validación de duplicados en `withValidator` no es atómica. Dos usuarios pueden agregar simultáneamente la misma exención antes de que el primer registro se persista.

**Impacto**: Permite duplicados en producción si hay solicitudes concurrentes.

**Solución**:
```php
// En migración 2025_09_22_122804_create_tramite_exenciones_table.php agregar:
$table->unique(['tramite_id', 'exencion_id']);
```

---

#### Bug #2: No se Valida Monto Máximo de Exención
**Ubicación**: `app/Http/Requests/StoreTramiteExencionRequest.php:15-21`

**Problema**: El campo `monto_aplicado` solo valida `min:0.01` pero no verifica que no exceda el `monto_maximo` definido en el catálogo de exenciones.

**Impacto**: Permite aplicar montos superiores a los permitidos por la ley/reglamento.

**Solución**:
```php
// Agregar validación personalizada en withValidator:
$validator->after(function ($v) {
    $exencion = Exencion::find($this->input('exencion_id'));
    if ($exencion && $exencion->monto_maximo && $this->input('monto_aplicado') > $exencion->monto_maximo) {
        $v->errors()->add('monto_aplicado', "El monto no puede exceder {$exencion->monto_maximo}");
    }
});
```

---

#### Bug #3: Cálculo Manual de Monto Aplicado Propenso a Errores
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php:63-86`

**Problema**: El usuario debe ingresar manualmente el `monto_aplicado`. Si la exención es de tipo "porcentaje", el cálculo es manual y propenso a errores humanos.

**Impacto**: Montos incorrectos que afectan el cálculo final del trámite.

**Solución**:
```php
// Agregar en store() antes de crear el registro:
$exencion = Exencion::find($request->exencion_id);
$montoAplicado = $request->monto_aplicado;

if ($exencion->tipo === 'porcentaje') {
    $montoAplicado = round($tramite->base_imponible * ($exencion->valor / 100), 2);
}
```

---

#### Bug #4: Uso de now() en lugar de fecha de trámite
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php:55-56`

**Problema**: Al filtrar exenciones vigentes, se usa `now()` en lugar de `$tramite->fecha_presentacion`. Esto permite seleccionar exenciones que aún no estaban vigentes al momento del trámite.

**Impacto**: Permite aplicar exenciones retroactivamente incorrectamente.

**Solución**:
```php
// Reemplazar now() por:
$exenciones = Exencion::whereDate('vigente_desde', '<=', $tramite->fecha_presentacion)
    ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $tramite->fecha_presentacion))
    ->orderBy('nombre')
    ->get();
```

---

#### Bug #5: Falta Validación de que exención no exceda el impuesto
**Ubicación**: `app/Http/Requests/StoreTramiteExencionRequest.php:23-33`

**Problema**: No se valida que la suma de exenciones no exceda el total de impuesto a pagar.

**Impacto**: Montos negativos o cero en el trámite final.

**Solución**:
```php
$validator->after(function ($v) {
    $tramite = $this->route('tramite');
    $montoTotalExenciones = $tramite->tramiteExenciones()->sum('monto_aplicado') + $this->input('monto_aplicado');
    
    if ($montoTotalExenciones > $tramite->total_idtgb) {
        $v->errors()->add('monto_aplicado', 'Las exenciones exceden el total del impuesto');
    }
});
```

---

#### Bug #6: Falta manejo de excepción en IdtgbCalculator
**Ubicación**: `app/Services/IdtgbCalculator.php:51`

**Problema**: Se accede a `$tramite->inmuebles->first()->municipio` sin verificar que exista el inmueble. Si el trámite no tiene inmuebles, lanza error.

**Impacto**: Error fatal en producción si el trámite no tiene inmuebles asociados.

**Solución**:
```php
// Verificar que exista inmueble antes de acceder:
$primerInmueble = $tramite->inmuebles->first();
if (!$primerInmueble) {
    throw new \Exception('El trámite no tiene inmuebles asociados');
}
$departamentoId = $primerInmueble->municipio->provincia->departamento_id ?? 1;
```

---

#### Bug #7: IdtgbCalculator usa default departamento_id = 1 sin log
**Ubicación**: `app/Services/IdtgbCalculator.php:51` y `app/Services/IdtgbCalculator.php:74`

**Problema**: Cuando no hay inmueble o municipio, se usa hardcode `?? 1` (Beni) sin registrar warning ni log.

**Impacto**: Cálculos incorrectos sin notificación al usuario/administrador.

**Solución**:
```php
// Agregar logging:
if (!$primerInmueble) {
    Log::warning("Trámite {$tramite->id} sin inmuebles, usando departamento_id default (1)");
}
```

---

### 10.2 MEJORAS SUGERIDAS

#### Mejora #1: Soft Deletes para Auditoría
**Ubicación**: `app/Models/TramiteExencion.php`

**Sugerencia**: Implementar soft deletes para mantener historial de exenciones eliminadas.

**Implementación**:
```php
// Agregar al modelo:
use Illuminate\Database\Eloquent\SoftDeletes;

class TramiteExencion extends Model
{
    use HasFactory, SoftDeletes;
    // ...
}
```

---

#### Mejora #2: Sistema de Auditoría
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php`

**Sugerencia**: Registrar quién agregó/eliminó cada exención con timestamps y usuario.

**Implementación**: Agregar campos a migración:
```php
$table->foreignId('created_by')->nullable()->constrained('users');
$table->foreignId('deleted_by')->nullable()->constrained('users');
$table->timestamp('deleted_at')->nullable();
```

---

#### Mejora #3: Validación de Vigencia vs Fecha de Trámite
**Ubicación**: `app/Http/Requests/StoreTramiteExencionRequest.php`

**Sugerencia**: Validar que la exención estaba vigente en la fecha del trámite, no actualmente.

**Implementación**:
```php
$validator->after(function ($v) {
    $tramite = $this->route('tramite');
    $exencion = Exencion::find($this->input('exencion_id'));
    
    if ($exencion && $exencion->vigente_desde->gt($tramite->fecha_presentacion)) {
        $v->errors()->add('exencion_id', 'La exención no estaba vigente en la fecha del trámite');
    }
    if ($exencion && $exencion->vigente_hasta && $exencion->vigente_hasta->lt($tramite->fecha_presentacion)) {
        $v->errors()->add('exencion_id', 'La exención ya no estaba vigente en la fecha del trámite');
    }
});
```

---

#### Mejora #4: Cálculo Automático de Monto en Frontend
**Ubicación**: `resources/views/admin/tramites/exenciones/create.blade.php`

**Sugerencia**: Agregar JavaScript para calcular automáticamente el monto cuando se selecciona una exención de tipo porcentaje.

**Beneficio**: Mejora UX y reduce errores humanos.

---

#### Mejora #5: API Endpoints para Exenciones
**Ubicación**: `routes/api.php`

**Sugerencia**: Exponer endpoints REST API para consumo externo/integraciones.

**Ejemplo**:
```php
Route::prefix('api')->middleware('auth:sanctum')->group(function () {
    Route::get('/tramites/{tramite}/exenciones', [TramiteExencionController::class, 'index']);
    Route::post('/tramites/{tramite}/exenciones', [TramiteExencionController::class, 'store']);
});
```

---

#### Mejora #6: Caching de Exenciones Vigentes
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php:55-58`

**Sugerencia**: Implementar cache para las consultas de exenciones vigentes.

**Implementación**:
```php
$cacheKey = "exenciones:vigentes:{$tramite->fecha_presentacion->toDateString()}";
$exenciones = Cache::remember($cacheKey, 3600, function () use ($tramite) {
    return Exencion::whereDate('vigente_desde', '<=', $tramite->fecha_presentacion)
        ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $tramite->fecha_presentacion))
        ->orderBy('nombre')
        ->get();
});
```

---

#### Mejora #7: Notificaciones por Email
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php:75-77`

**Sugerencia**: Enviar notificaciones cuando se aplica/quita una exención en trámites críticos.

---

### 10.3 FALTAS IDENTIFICADAS

#### Falta #1: Sin Tests Automatizados
**Ubicación**: `tests/Feature/TramiteExencionTest.php` (NO EXISTE)

**Detalles**: No hay pruebas unitarias ni de integración para:
- Validación de duplicados
- Cálculo de montos
- Actualización de totales del trámite
- Autorizaciones
- Escenarios edge-case

**Impacto**: Alto riesgo de regressión en cambios futuros.

---

#### Falta #2: Sin Validación de Exclusividad
**Ubicación**: Catálogo de exenciones

**Detalles**: No hay lógica para validar que ciertas exenciones sean mutuamente excluyentes (ej: no se pueden aplicar dos exenciones de tipo "discapacidad" juntas).

**Impacto**: Posible aplicación indebida de múltiples exenciones.

---

#### Falta #3: Sin Documento de Sustento Obligatorio
**Ubicación**: `app/Http/Requests/StoreTramiteExencionRequest.php`

**Detalles**: No se requiere documento que justifique la exención (certificado de discapacidad, decreto, etc.).

**Impacto**: Riesgo legal y de auditoría.

---

#### Falta #4: Sin Aprobación de Exención
**Ubicación**: Flujo de trabajo actual

**Detalles**: Las exenciones se aplican automáticamente sin workflow de aprobación por supervisor/administrador.

**Impacto**: Exenciones pueden aplicarse sin revisión.

---

#### Falta #5: Sin Historial de Cambios en Monto
**Ubicación**: `tramite_exenciones` table

**Detalles**: Si el `monto_aplicado` se modifica posteriormente, no hay registro del cambio (valor anterior, usuario, fecha).

**Impacto**: Imposible auditoría de modificaciones.

---

#### Falta #6: Sin Reportes de Exenciones
**Ubicación**: Sistema de reportes

**Detalles**: No hay reportes/análisis de:
- Exenciones más aplicadas
- Monto total de exenciones por período
- Exenciones por tipo de trámite
- Auditoría de exenciones aplicadas

---

### 10.4 OPTIMIZACIONES

#### Optimización #1: Query N+1 en list()
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php:34-38`

**Problema**: La consulta `with(['exencion'])` es correcta, pero falta indexación.

**Solución**: Agregar índices en migración:
```php
$table->index(['tramite_id', 'exencion_id']);
$table->index('exencion_id');
```

---

#### Optimización #2: Evitar Múltiples Cálculos
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php:75`

**Problema**: `IdtgbCalculator::calcular()` se llama en cada operación de exención. Si se agregan 3 exenciones en wizard, se recalcula 3 veces.

**Solución**: Implementar cálculo diferido o batch:
```php
// En wizard, marcar para recalcular solo al final:
$tramite->needs_recalculation = true;
$tramite->save();

// En paso final:
if ($tramite->needs_recalculation) {
    app(IdtgbCalculator::class)->calcular($tramite);
}
```

---

#### Optimización #3: Usar Eager Loading en Relaciones
**Ubicación**: `app/Models/Tramite.php`

**Problema**: Al cargar exenciones de un trámite, se pueden hacer queries adicionales si no se usa `with()`.

**Solución**: Ya implementado en scope `conRelacionesCompletas()`.

---

#### Optimización #4: Paginación en create()
**Ubicación**: `app/Http/Controllers/TramiteExencionController.php:55-58`

**Problema**: Si hay muchas exenciones vigentes, el dropdown puede ser muy largo.

**Solución**: Implementar búsqueda AJAX en frontend.

---

#### Optimización #5: Reducir Consultas en IdtgbCalculator
**Ubicación**: `app/Services/IdtgbCalculator.php:40-44`

**Problema**: Se crea un array de exenciones desde la colección, luego se suma. Se puede optimizar.

**Solución**:
```php
$totalExenciones = $tramite->tramiteExenciones()->sum('monto_aplicado');
```

---

### 10.5 UBICACIÓN DE ARCHIVOS RELACIONADOS

#### Modelos
- `app/Models/TramiteExencion.php:8-34` - Modelo pivote
- `app/Models/Tramite.php:79-88` - Relaciones con exenciones
- `app/Models/Exencion.php:96-103` - Relación inversa

#### Controladores
- `app/Http/Controllers/TramiteExencionController.php:1-112` - CRUD completo
- `app/Http/Controllers/Admin/TramiteWizardController.php:6-7` - Paso 6 del wizard

#### Requests
- `app/Http/Requests/StoreTramiteExencionRequest.php:1-35` - Validación de creación

#### Policies
- `app/Policies/TramiteExencionPolicy.php:1-45` - Autorizaciones

#### Migraciones
- `database/migrations/2025_09_22_122804_create_tramite_exenciones_table.php:1-23` - Tabla pivote
- `database/migrations/2025_09_22_122751_create_exenciones_table.php:1-22` - Catálogo de exenciones

#### Servicios
- `app/Services/IdtgbCalculator.php:25-92` - Recálculo de trámite con exenciones
- `app/Services/IdtgbCalculator.php:136-144` - Lógica de aplicación de exenciones

#### Vistas
- `resources/views/admin/tramites/exenciones/browse.blade.php` - Listado principal
- `resources/views/admin/tramites/exenciones/list.blade.php` - Tabla AJAX
- `resources/views/admin/tramites/exenciones/create.blade.php` - Formulario de creación
- `resources/views/admin/tramites/wizard/create_step_6.blade.php` - Paso 6 del wizard

#### Rutas
- `routes/web.php:117-124` - Rutas anidadas para exenciones

---

### 10.6 MATRIZ DE PRIORIDAD

| ISSUE | TIPO | PRIORIDAD | COMPLEJIDAD | UBICACIÓN |
|-------|------|-----------|-------------|-----------|
| Race condition duplicados | BUG | ALTA | Baja | `StoreTramiteExencionRequest:23` |
| Validación monto máximo | BUG | ALTA | Baja | `StoreTramiteExencionRequest:15` |
| Cálculo manual montos | BUG | ALTA | Media | `TramiteExencionController:63` |
| now() vs fecha trámite | BUG | MEDIA | Baja | `TramiteExencionController:55` |
| Exención > impuesto | BUG | MEDIA | Media | `StoreTramiteExencionRequest:23` |
| Error sin inmuebles | BUG | MEDIA | Baja | `IdtgbCalculator:51` |
| Tests automatizados | FALTA | ALTA | Alta | `tests/Feature/` |
| Documento sustento | FALTA | ALTA | Media | `StoreTramiteExencionRequest` |
| Soft deletes | MEJORA | MEDIA | Baja | `TramiteExencion` |
| Sistema auditoría | MEJORA | MEDIA | Alta | Módulo completo |
| Caching exenciones | OPT | BAJA | Baja | `TramiteExencionController:55` |
| Evitar recálculos múltiples | OPT | MEDIA | Media | `TramiteExencionController:75` |