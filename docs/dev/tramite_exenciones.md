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
