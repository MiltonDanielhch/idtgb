# Documentación Técnica: Módulo AdquirenteTrámite

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelos](#modelos)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Policy](#policy)
7. [Requests](#requests)
8. [Vistas](#vistas)
9. [Integración](#integración)
10. [Guía para Desarrolladores](#guía-para-desarrolladores)

---

## 🎯 Introducción

El módulo `AdquirenteTrámite` gestiona la relación uno a muchos entre un `Trámite` y las `Person` que actúan como adquirentes. No es una tabla pivote estándar de muchos a muchos, sino una tabla dedicada que trata a cada adquirente de un trámite como una entidad única.

Almacena detalles cruciales para el cálculo de impuestos, como el parentesco, el porcentaje de participación en la adquisición y la tasa de impuesto aplicada a cada adquirente. Similar a otros módulos anidados, cualquier cambio en los adquirentes recalcula los totales del trámite.

---

## 🗄️ Base de Datos

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

**Campos:**
-   **`tramite_id`**: FK a la tabla `tramites`.
-   **`person_id`**: FK a la tabla `people`, identificando al adquirente.
-   **`parentesco_id`**: FK a la tabla `parentescos`, clave para determinar la tasa del impuesto.
-   **`tasa_aplicada`**: La tasa impositiva (%) que se aplicó a este adquirente.
-   **`porcentaje`**: El porcentaje de propiedad que este adquirente está recibiendo.
-   **`idtgb_proporcional`**: El monto del impuesto calculado para la porción de este adquirente.
-   **`es_beneficiario_exencion`**: Flag para indicar si el adquirente tiene alguna exención.
-   **`documento_sustento_exencion`**: Path al archivo que respalda la exención.

---

## 🧩 Modelos

### Modelo: `App\Models\AdquirenteTramite`

-   **Tabla**: `adquirentes_tramite`
-   **Fillable**: `['tramite_id', 'person_id', 'parentesco_id', 'tasa_aplicada', 'porcentaje', ...]`
-   **Relaciones**:
    -   `tramite()`: `belongsTo(Tramite::class)`
    -   `person()`: `belongsTo(Person::class, 'person_id')`
    -   `parentesco()`: `belongsTo(Parentesco::class)`

### Modelo: `App\Models\Tramite`

El modelo `Tramite` define la relación inversa `hasMany` y una relación `hasManyThrough` para acceder directamente a las personas.

```php
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

---

## 🎮 Controlador

### Controlador: `AdquirenteTramiteController`

**Ubicación:** `app/Http/Controllers/AdquirenteTramiteController.php`

**Métodos Principales:**

-   **`index()` y `list()`**: Muestran la lista de adquirentes para un trámite.
-   **`create()`**: Muestra el formulario para agregar un nuevo adquirente. Filtra las personas para no mostrar las que ya han sido agregadas al trámite.
-   **`store(StoreAdquirenteTramiteRequest $request, Tramite $tramite)`**:
    1.  Valida la solicitud.
    2.  Valida que el trámite tenga un departamento asignado.
    3.  Busca la `tasa` aplicable usando el método centralizado `Tasa::findApplicableRate()`.
    4.  Gestiona la subida del archivo de sustento de exención si existe.
    5.  Crea el registro `AdquirenteTramite`, dejando el `idtgb_proporcional` en 0.
    6.  Invoca a `app(IdtgbCalculator::class)->calcular($tramite)` para calcular y actualizar los montos.
    7.  Toda la operación se ejecuta dentro de una transacción de BD.
-   **`destroy(Tramite $tramite, AdquirenteTramite $item)`**:
    1.  Elimina el registro de `adquirentes_tramite`.
    2.  Si existe un documento de sustento, lo elimina del storage.
    3.  Vuelve a llamar a `IdtgbCalculator` para recalcular los montos del trámite.
    4.  Se ejecuta dentro de una transacción.

---

## 🛣️ Rutas

**Archivo**: `routes/web.php`

```php
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

---

## 🔒 Policy

### Policy: `AdquirenteTramitePolicy`

**Ubicación:** `app/Policies/AdquirenteTramitePolicy.php`

| Método | Permiso Requerido |
|---|---|
| `viewAny`: Requiere `browse_adquirentes_tramite`. |
| `view`: Requiere `read_adquirentes_tramite`. |
| `create`: Requiere `add_adquirentes_tramite`. |
| `delete`: Requiere `delete_adquirentes_tramite`. |

---

## ✅ Requests

### Request: `StoreAdquirenteTramiteRequest`

**Ubicación:** `app/Http/Requests/StoreAdquirenteTramiteRequest.php`

**Reglas de validación:**
-   `person_id`, `parentesco_id`: Requeridos y deben existir en sus respectivas tablas.
-   `porcentaje`: Requerido, numérico, entre 0.01 y 100.
-   `documento_sustento_exencion`: Opcional, debe ser un archivo (pdf, jpg, png) de máximo 2MB.

**Validación Personalizada:**
-   Asegura que la misma persona no se pueda agregar dos veces como adquirente en el mismo trámite.

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/tramites/adquirentes/`

-   **`browse.blade.php`**: Vista principal con lista de adquirentes.
-   **`list.blade.php`**: Tabla AJAX con adquirentes del trámite.
-   **`create.blade.php`**: Formulario para agregar nuevo adquirente.

---

## 🔗 Integración

### Servicio: `IdtgbCalculator`

**Ubicación:** `app/Services/IdtgbCalculator.php`

El método `calcular($tramite)` se invoca en `store` y `destroy` del controlador. Se encarga de:
1.  Recorrer todos los adquirentes del trámite.
2.  Calcular el `idtgb_proporcional` para cada uno basado en su porcentaje de participación y tasa.
3.  Sumar los proporcionales para obtener el `total_idtgb` del trámite.
4.  Actualizar todos los montos finales en el registro del `Tramite`.

---

## 📝 Guía para Desarrolladores

### Flujo de Operación

1.  Un usuario navega a la sección de adquirentes de un trámite.
2.  El `AdquirenteTramiteController` muestra los adquirentes existentes.
3.  El usuario hace clic en "Agregar Adquirente".
4.  Se presenta un formulario con una lista de personas (excluyendo las ya agregadas).
5.  Al enviar el formulario, el `store` del controlador valida los datos, busca la tasa correcta, crea el registro `AdquirenteTramite` y delega el cálculo de impuestos al servicio `IdtgbCalculator`.
6.  El trámite se actualiza y el usuario es redirigido.
7.  La eliminación sigue el flujo inverso, recalculando siempre los montos del trámite para mantener la consistencia.

### Puntos Clave

-   Este módulo siempre recalcula los impuestos del trámite cuando se agrega o elimina un adquirente.
-   La búsqueda de tasas está centralizada en `Tasa::findApplicableRate()`.
-   Se valida que el trámite tenga un departamento asignado antes de calcular tasas.
-   Todo el módulo está protegido por Policies y FormRequests.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (20 de enero de 2026) ✅

El módulo AdquirenteTramite ha sido mejorado con correcciones importantes para asegurar la integridad de los datos y el cálculo correcto de impuestos:

- ✅ Validación estricta de departamento antes de calcular tasas
- ✅ Centralización de búsqueda de tasas con `Tasa::findApplicableRate()`
- ✅ Manejo de transacciones de base de datos en operaciones de escritura
- ✅ Validación de pertenencia del item al trámite en operaciones de destrucción
- ✅ Autorización implementada en todos los métodos del controlador
- ✅ Recálculo automático de impuestos al agregar o eliminar adquirentes

### 🐛 Bugs Corregidos (2/2) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Falta validación de departamento al calcular tasas | ✅ Corregido | `AdquirenteTramiteController.php:79-83` |
| 2 | Búsqueda de tasas no centralizada y propensa a errores | ✅ Corregido | `AdquirenteTramiteController.php:85-92` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Validación estricta de departamento | Valida que el trámite tenga un departamento asignado antes de buscar tasas, lanza excepción específica si no existe |
| 2 | Centralización de búsqueda de tasas | Uso del método `Tasa::findApplicableRate()` en lugar de consulta directa, código más limpio y reutilizable |
| 3 | Manejo de transacciones DB | Implementado `DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()` para atomicidad en `store()` y `destroy()` |
| 4 | Validación de contexto | Se valida que el `AdquirenteTramite` pertenezca al `Tramite` antes de eliminar en `destroy()` |
| 5 | Autorización completa | Agregadas llamadas `authorize()` en todos los métodos del controlador |
| 6 | Recálculo automático de impuestos | Llamado a `IdtgbCalculator::calcular($tramite)` tras agregar/eliminar adquirentes |
| 7 | Validación de duplicados | Request `StoreAdquirenteTramiteRequest` valida que una persona no se agregue dos veces al mismo trámite |

### 📝 Historial de Cambios

### v2.0.0 (20 de enero de 2026)
**Correcciones Completadas (2/2):**
- ✅ Bug #1: Validación de departamento - Agregada validación estricta en `store()` línea 79-83
- ✅ Bug #2: Búsqueda de tasas - Centralizada con `Tasa::findApplicableRate()` línea 85-92

**Cambios en Código:**
- `app/Http/Controllers/AdquirenteTramiteController.php`:
  - Agregada validación de departamento antes de buscar tasas
  - Reemplazada consulta directa de tasas por `Tasa::findApplicableRate()`
  - Validación de pertenencia de item al trámite en `destroy()` línea 130-132
  - Manejo de transacciones DB con try-catch en `store()` y `destroy()`
- `app/Http/Requests/StoreAdquirenteTramiteRequest.php` - Validación personalizada para evitar duplicados de personas en el mismo trámite

---

**Última actualización:** 20 de enero de 2026
**Versión:** 2.0.0
