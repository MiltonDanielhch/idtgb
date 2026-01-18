# Documentación Técnica: Módulo AdquirenteTrámite

Este documento detalla la implementación técnica del módulo `AdquirenteTrámite`, que conecta las personas (adquirentes) con un trámite específico.

## 1. Propósito del Módulo

El módulo `AdquirenteTrámite` gestiona la relación uno a muchos entre un `Trámite` y las `Person` que actúan como adquirentes. No es una tabla pivote estándar de muchos a muchos, sino una tabla dedicada que trata a cada adquirente de un trámite como una entidad única.

Almacena detalles cruciales para el cálculo de impuestos, como el parentesco, el porcentaje de participación en la adquisición y la tasa de impuesto aplicada a cada adquirente. Similar a otros módulos anidados, cualquier cambio en los adquirentes recalcula los totales del trámite.

## 2. Estructura de la Base de Datos

La relación se materializa en la tabla `adquirentes_tramite`.

### Migración: `2025_09_22_122810_create_adquirentes_tramite_table.php`

```php
Schema::create('adquirentes_tramite', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tramite_id')->constrained()->cascadeOnDelete();
    $table->foreignId('person_id')->constrained('people');
    $table->foreignId('parentesco_id')->constrained('parentescos');
    $table->decimal('tasa_aplicada', 5, 2);
    $table->decimal('porcentaje', 5, 2);
    $table->decimal('idtgb_proporcional', 12, 2);
    $table->boolean('es_beneficiario_exencion')->default(false);
    $table->string('documento_sustento_exencion', 250)->nullable();
    $table->timestamps();
});
```

-   **`tramite_id`**: FK a la tabla `tramites`.
-   **`person_id`**: FK a la tabla `people`, identificando al adquirente.
-   **`parentesco_id`**: FK a la tabla `parentescos`, clave para determinar la tasa del impuesto.
-   **`tasa_aplicada`**: La tasa impositiva (%) que se aplicó a este adquirente.
-   **`porcentaje`**: El porcentaje de propiedad que este adquirente está recibiendo.
-   **`idtgb_proporcional`**: El monto del impuesto calculado para la porción de este adquirente.
-   **`es_beneficiario_exencion`**: Flag para indicar si el adquirente tiene alguna exención.
-   **`documento_sustento_exencion`**: Path al archivo que respalda la exención.

## 3. Modelos (Eloquent)

Se utiliza un modelo dedicado (`AdquirenteTramite`) que representa una entrada en la tabla `adquirentes_tramite`.

### a. `App\Models\AdquirenteTramite`

-   **Tabla**: `adquirentes_tramite`
-   **Fillable**: `['tramite_id', 'person_id', 'parentesco_id', 'tasa_aplicada', 'porcentaje', ...]`
-   **Relaciones**:
    -   `tramite()`: `belongsTo(Tramite::class)`
    -   `person()`: `belongsTo(Person::class, 'person_id')`
    -   `parentesco()`: `belongsTo(Parentesco::class)`

```php
// app/Models/AdquirenteTramite.php
class AdquirenteTramite extends Model
{
    // ...
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }

    public function parentesco()
    {
        return $this->belongsTo(Parentesco::class);
    }
}
```

### b. `App\Models\Tramite`

El modelo `Tramite` define la relación inversa `hasMany` y una relación `hasManyThrough` para acceder directamente a las personas.

```php
// app/Models/Tramite.php

// Relación directa con el modelo intermedio
public function adquirentes()
{
    return $this->hasMany(AdquirenteTramite::class);
}

// Relación para obtener las Personas directamente
public function personasAdquirentes()
{
    return $this->hasManyThrough(
        Person::class,
        AdquirenteTramite::class,
        'tramite_id', // FK en adquirentes_tramite
        'id',         // FK en people
        'id',         // Local key en tramites
        'person_id'   // FK en adquirentes_tramite que apunta a people
    );
}
```

## 4. Rutas (Web)

Las rutas para este módulo son anidadas dentro de `tramites` y siguen una convención RESTful.

**Archivo**: `routes/web.php`

```php
// routes/web.php

Route::prefix('admin')->group(function () {
    // ...
    Route::prefix('tramites/{tramite}/adquirentes')->name('admin.tramites.adquirentes.')->group(function () {
        Route::get('/', [AdquirenteTramiteController::class, 'index'])->name('index');
        Route::get('/ajax/list', [AdquirenteTramiteController::class, 'list'])->name('ajax.list');
        Route::get('/create', [AdquirenteTramiteController::class, 'create'])->name('create');
        Route::post('/', [AdquirenteTramiteController::class, 'store'])->name('store');
        Route::get('/{item}', [AdquirenteTramiteController::class, 'show'])->name('show');
        Route::delete('/{item}', [AdquirenteTramiteController::class, 'destroy'])->name('destroy');
    });
    // ...
});
```

-   **`{tramite}`**: ID del `Tramite` padre.
-   **`{item}`**: ID de la instancia de `AdquirenteTramite`.

## 5. Controlador: `AdquirenteTramiteController`

**Archivo**: `app/Http/Controllers/AdquirenteTramiteController.php`

Gestiona el ciclo de vida de un adquirente dentro de un trámite.

-   **`index()` y `list()`**: Muestran la lista de adquirentes para un trámite.
-   **`create()`**: Muestra el formulario para agregar un nuevo adquirente. Filtra las personas para no mostrar las que ya han sido agregadas al trámite.
-   **`store(StoreAdquirenteTramiteRequest $request, Tramite $tramite)`**:
    1.  Valida la solicitud.
    2.  Valida que el trámite tenga un departamento asignado.
    3.  Busca la `tasa` aplicable usando el método centralizado `Tasa::findApplicableRate()`.
    4.  Gestiona la subida del archivo de sustento de exención si existe.
    5.  Crea el registro `AdquirenteTramite`, dejando el `idtgb_proporcional` en 0.
    6.  **Acción Clave**: Invoca a `app(IdtgbCalculator::class)->calcular($tramite)`, que se encarga de calcular y actualizar el `idtgb_proporcional` y los montos totales del trámite.
    7.  Toda la operación se ejecuta dentro de una transacción de BD.
-   **`destroy(Tramite $tramite, AdquirenteTramite $item)`**:
    1.  Elimina el registro de `adquirentes_tramite`.
    2.  Si existe un documento de sustento, lo elimina del storage.
    3.  **Acción Clave**: Vuelve a llamar a `IdtgbCalculator` para recalcular los montos del trámite.
    4.  Se ejecuta dentro de una transacción.

## 6. Validación: `StoreAdquirenteTramiteRequest`

**Archivo**: `app/Http/Requests/StoreAdquirenteTramiteRequest.php`

Valida los datos para agregar un adquirente.

-   **`rules()`**:
    -   `person_id`, `parentesco_id`: Requeridos y deben existir en sus respectivas tablas.
    -   `porcentaje`: Requerido, numérico, entre 0.01 y 100.
    -   `documento_sustento_exencion`: Opcional, debe ser un archivo (pdf, jpg, png) de máximo 2MB.
-   **Validación Personalizada (`withValidator`)**:
    -   Asegura que la misma persona no se pueda agregar dos veces como adquirente en el mismo trámite.

## 7. Autorización: `AdquirenteTramitePolicy`

**Archivo**: `app/Policies/AdquirenteTramitePolicy.php`

Define los permisos basados en Voyager.

-   `viewAny`: Requiere `browse_adquirentes_tramite`.
-   `view`: Requiere `read_adquirentes_tramite`.
-   `create`: Requiere `add_adquirentes_tramite`.
-   `delete`: Requiere `delete_adquirentes_tramite`.

## 8. Lógica de Negocio Crítica: `IdtgbCalculator`

**Servicio**: `app/Services/IdtgbCalculator.php`

Al igual que en otros módulos, este servicio es central. El método `calcular($tramite)` se invoca en `store` y `destroy` del controlador. Se encarga de:
1.  Recorrer todos los adquirentes del trámite.
2.  Calcular el `idtgb_proporcional` para cada uno basado en su porcentaje de participación y tasa.
3.  Sumar los proporcionales para obtener el `total_idtgb` del trámite.
4.  Actualizar todos los montos finales en el registro del `Tramite`.

## 9. Flujo de Operación (Resumen)

1.  Un usuario navega a la sección de adquirentes de un trámite.
2.  El `AdquirenteTramiteController` muestra los adquirentes existentes.
3.  El usuario hace clic en "Agregar Adquirente".
4.  Se presenta un formulario con una lista de personas (excluyendo las ya agregadas).
5.  Al enviar el formulario, el `store` del controlador valida los datos, busca la tasa correcta, crea el registro `AdquirenteTramite` y delega el cálculo de impuestos al servicio `IdtgbCalculator`.
6.  El trámite se actualiza y el usuario es redirigido.
7.  La eliminación sigue el flujo inverso, recalculando siempre los montos del trámite para mantener la consistencia.

## 10. Posibles Bugs, Mejoras y Optimizaciones

Basado en un análisis del código, se han identificado varias áreas de mejora y posibles problemas.

### a. Ausencia de Funcionalidad de Actualización (`Update`)

-   **Problema**: El módulo carece de una funcionalidad para editar un `AdquirenteTramite` existente. Si un usuario comete un error (por ejemplo, asigna un porcentaje incorrecto o un parentesco equivocado), la única solución es eliminar el registro y volver a crearlo. Esto es ineficiente y propenso a errores.
-   **Mejora Sugerida**:
    1.  **Añadir Ruta de Edición**: Implementar una ruta `GET /tramites/{tramite}/adquirentes/{item}/edit` que muestre un formulario pre-rellenado con los datos del adquirente.
    2.  **Añadir Ruta de Actualización**: Implementar una ruta `PUT/PATCH /tramites/{tramite}/adquirentes/{item}` que valide y guarde los cambios.
    3.  **Crear `UpdateAdquirenteTramiteRequest`**: Para manejar la lógica de validación en la actualización.
    4.  **Implementar `edit()` y `update()` en `AdquirenteTramiteController`**: Estos métodos gestionarían la lógica de negocio, incluyendo la recalculaición de impuestos a través de `IdtgbCalculator` después de guardar los cambios.
    5.  **Añadir Permiso en `AdquirenteTramitePolicy`**: Incluir una política `update` para controlar quién puede editar.

### b. Refactorización del Servicio `IdtgbCalculator`

-   **Problema**: El método `performCalculation` dentro de `app/Services/IdtgbCalculator.php` es extenso y complejo. Contiene múltiples responsabilidades, como calcular el impuesto base, intereses, multas y actualizar varios modelos. Esto dificulta su lectura, mantenimiento y la creación de pruebas unitarias.
-   **Mejora Sugerida**:
    -   **Dividir el Método**: Refactorizar `performCalculation` en métodos más pequeños y especializados con una única responsabilidad. Por ejemplo:
        -   `calculateBaseTax(Tramite $tramite)`
        -   `calculateInterest(Tramite $tramite)`
        -   `calculatePenalties(Tramite $tramite)`
        -   `updateAcquirerValues(Tramite $tramite)`
        -   `updateTramiteTotals(Tramite $tramite)`
    -   Esto mejoraría la claridad y permitiría probar cada parte del cálculo de forma aislada.

### c. Optimización de Bucles Redundantes

-   **Problema**: El método `calculateAndSave` en `IdtgbCalculator` itera sobre los adquirentes (`$tramite->adquirentes`) varias veces para diferentes propósitos. En trámites con muchos adquirentes, esto puede generar una sobrecarga innecesaria.
-   **Mejora Sugerida**:
    -   **Consolidar Bucles**: Unificar las operaciones en un solo bucle siempre que sea posible. Por ejemplo, mientras se itera para calcular el `idtgb_proporcional` de cada adquirente, se puede ir sumando el total para el trámite en la misma iteración, en lugar de hacerlo en un bucle separado posterior.

### d. Centralización de Lógica de Búsqueda de `Tasa`

-   **Estado**: ✅ IMPLEMENTADO
-   **Mejora**: La lógica de búsqueda de tasas ha sido centralizada en el método `Tasa::findApplicableRate()`.
-   **Beneficios**:
    -   Código más limpio y mantenible
    -   Lógica reutilizable en otros controladores
    -   Fácil de probar y modificar en un solo lugar
    -   Ejemplo de uso:
        ```php
        $tasaModel = Tasa::findApplicableRate(
            $departamentoId,
            $parentescoId,
            $tipoTransmisionId,
            $fechaPresentacion
        );
        ```

### e. Validación Estricta de Departamento

-   **Estado**: ✅ IMPLEMENTADO
-   **Mejora**: El controlador ahora valida que el trámite tenga un departamento asignado antes de buscar tasas.
-   **Beneficios**:
    -   Detección temprana de errores de integridad de datos
    -   Prevención de cálculos incorrectos silenciosos
    -   Mensajes de error claros para el usuario
    -   Implementación:
        ```php
        $departamentoId = $tramite->inmueble->municipio->provincia->departamento_id ?? null;

        if (!$departamentoId) {
            throw new \Exception("El trámite no tiene un departamento asignado. No se puede calcular la tasa.");
        }
        ```

---

**Última actualización:** Enero 2026

**Versión:** 2.0.0

## 📝 Historial de Cambios

### Versión 2.0.0 (Enero 2026)

**Mejoras Implementadas:**
1. ✅ **Validación estricta de departamento**
   - Ahora se valida que el trámite tenga un departamento asignado antes de buscar tasas
   - Se lanza excepción específica si no hay departamento
   - Prevención de cálculos incorrectos silenciosos

2. ✅ **Centralización de búsqueda de tasas**
   - Uso del método `Tasa::findApplicableRate()` en lugar de consulta directa
   - Código más limpio y mantenible
   - Lógica reutilizable en otros controladores

3. ✅ **Mejor manejo de errores**
   - Mensajes de error más específicos
   - Validaciones más robustas

---
