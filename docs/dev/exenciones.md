# Documentación Técnica - Módulo de Exenciones

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Estructura del Módulo](#estructura-del-módulo)
3. [Módulo Principal: `Exenciones`](#módulo-principal-exenciones)
    - [Base de Datos](#base-de-datos-exencion)
    - [Modelo](#modelo-exencion)
    - [Controlador](#controlador-exencion)
    - [Rutas](#rutas-exencion)
    - [Policies y Permisos](#policies-y-permisos-exencion)
    - [Requests](#requests-exencion)
    - [Vistas](#vistas-exencion)
4. [Módulo Anidado: `TramiteExenciones`](#módulo-anidado-tramiteexenciones)
    - [Base de Datos](#base-de-datos-tramiteexencion)
    - [Modelo](#modelo-tramiteexencion)
    - [Controlador](#controlador-tramiteexencion)
    - [Rutas](#rutas-tramiteexencion)
    - [Policies y Permisos](#policies-y-permisos-tramiteexencion)
    - [Requests](#requests-tramiteexencion)
    - [Vistas](#vistas-tramiteexencion)
5. [Integración y Flujo de Trabajo](#integración-y-flujo-de-trabajo)
6. [Guía para Desarrolladores](#guía-para-desarrolladores)

---

## 🎯 Introducción

El módulo de **Exenciones** gestiona los beneficios fiscales que pueden aplicarse a un trámite del Impuesto de Transmisiones Gratuítas de Bienes (ITGB). Permite tanto la administración del catálogo de exenciones disponibles como su aplicación específica a cada trámite.

### Propósito
- **Catálogo de Exenciones:** Mantener un registro maestro de todas las exenciones fiscales, sus tipos (porcentaje o monto fijo), valor y período de vigencia.
- **Aplicación a Trámites:** Permitir que los operadores apliquen una o más de estas exenciones a un trámite específico, registrando el monto exacto del beneficio.
- **Recálculo Automático:** Asegurar que el monto total del impuesto de un trámite se recalcule automáticamente cada vez que se añade o elimina una exención.

---

## 🏗️ Estructura del Módulo

Este módulo se compone de dos partes principales que trabajan en conjunto:

1.  **Exenciones (`Exencion`):** Es el **catálogo maestro**. Aquí se definen todas las exenciones que existen en el sistema (ej: "Exención por discapacidad", "Exención para cónyuge"). Es un recurso CRUD estándar.
2.  **Exenciones del Trámite (`TramiteExencion`):** Es la **aplicación práctica**. Representa la relación entre un `Trámite` y una `Exencion`, indicando que un beneficio específico fue aplicado a un trámite en particular. Es un recurso anidado dentro de cada trámite.

---

## 1️⃣ Módulo Principal: `Exenciones`

Esta sección detalla el CRUD para administrar el catálogo maestro de exenciones.

### Base de Datos (`Exencion`)

-   **Migración:** `2025_09_22_122751_create_exenciones_table.php`
-   **Tabla:** `exenciones`

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `nombre` | VARCHAR(100) | NOT NULL | Nombre descriptivo de la exención. |
| `descripcion` | TEXT | | Explicación detallada de la exención. |
| `tipo` | ENUM('porcentaje', 'monto_fijo') | | Define si la exención es un % o un monto fijo en Bs. |
| `valor` | DECIMAL(10,2) | NOT NULL | El valor numérico de la exención (ej: 100 para 100% o 5000 para 5000 Bs). |
| `monto_maximo` | DECIMAL(14,2) | NULLABLE | Límite máximo en Bs. que puede descontar la exención (si aplica). |
| `vigente_desde` | DATE | NOT NULL | Fecha de inicio de la vigencia. |
| `vigente_hasta` | DATE | NULLABLE | Fecha de fin de vigencia (null para indefinido). |

### Modelo (`Exencion`)

-   **Ubicación:** `app/Models/Exencion.php`
-   **Casts:** `valor`, `monto_maximo` a `decimal:2`; `vigente_desde`, `vigente_hasta` a `date`.
-   **Relaciones:**
    -   `tramites()`: Relación `belongsToMany` con `Tramite` a través de la tabla pivote `tramite_exenciones`.
-   **Scopes:**
    -   `scopeVigente($query, ?string $fecha = null)`: Filtra las exenciones que están activas en una fecha determinada (por defecto, hoy).

### Controlador (`ExencionController`)

-   **Ubicación:** `app/Http/Controllers/ExencionController.php`
-   Gestiona el CRUD del catálogo de exenciones.
-   **Métodos:** `index`, `list` (AJAX), `show`, `create`, `store`, `edit`, `update`, `destroy`.
-   **Validación de Borrado:** El método `destroy` verifica si la exención está siendo utilizada en algún trámite (`$exencion->tramites()->exists()`) y bloquea la eliminación si es así.

### Rutas (`Exencion`)

-   **Ubicación:** `routes/web.php`
-   Definido como un `Route::resource` estándar.

| Método | URI | Nombre |
|---|---|---|
| GET | `/admin/exenciones` | `admin.exenciones.index` |
| GET | `/admin/exenciones/ajax/list` | `admin.exenciones.ajax.list`|
| POST | `/admin/exenciones` | `admin.exenciones.store` |
| GET | `/admin/exenciones/{exencion}`| `admin.exenciones.show` |
| PUT/PATCH| `/admin/exenciones/{exencion}`| `admin.exenciones.update` |
| DELETE | `/admin/exenciones/{exencion}`| `admin.exenciones.destroy`|

### Policies y Permisos (`Exencion`)

-   **Policy:** `app/Policies/ExencionPolicy.php`
-   Mapea las acciones a los siguientes permisos de Voyager:
    -   `browse_exenciones`
    -   `read_exenciones`
    -   `add_exenciones`
    -   `edit_exenciones`
    -   `delete_exenciones`

### Requests (`Exencion`)

-   **Ubicación:** `app/Http/Requests/`
-   **`StoreExencionRequest` / `UpdateExencionRequest`**:
    -   Autorización basada en `Gate::allows('create', ...)` y `Gate::allows('update', ...)`.
    -   Valida los campos como `nombre` (único), `tipo` (in:porcentaje,monto_fijo), `valor` (numérico), y fechas de vigencia.

### Vistas (`Exencion`)

-   **Ubicación:** `resources/views/admin/exenciones/`
-   Vistas estándar de Voyager para un CRUD (`browse`, `list`, `edit-add`, `read`), con carga de datos mediante AJAX.

---

## 2️⃣ Módulo Anidado: `TramiteExenciones`

Esta sección detalla el CRUD para **aplicar y quitar** exenciones a un trámite específico.

### Base de Datos (`TramiteExencion`)

-   **Migración:** `2025_09_22_122804_create_tramite_exenciones_table.php`
-   **Tabla (Pivote):** `tramite_exenciones`

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `tramite_id`| BIGINT | FK, NOT NULL | El trámite al que se aplica la exención. |
| `exencion_id`| BIGINT | FK, NOT NULL | La exención que se está aplicando. |
| `monto_aplicado`| DECIMAL(14,2)| NOT NULL | El monto **calculado en Bs.** que se descontó en este trámite. |

### Modelo (`TramiteExencion`)

-   **Ubicación:** `app/Models/TramiteExencion.php`
-   Representa la tabla pivote.
-   **Relaciones:**
    -   `tramite()`: Relación `belongsTo` con `Tramite`.
    -   `exencion()`: Relación `belongsTo` con `Exencion`.

### Controlador (`TramiteExencionController`)

-   **Ubicación:** `app/Http/Controllers/TramiteExencionController.php`
-   Gestiona las exenciones aplicadas a **un solo trámite**.
-   **Integración Clave:** Llama al servicio `IdtgbCalculator` después de `store` y `destroy` para recalcular el impuesto total del trámite.
-   **Métodos:**
    -   `index` / `list`: Muestran las exenciones ya aplicadas al trámite.
    -   `create`: Muestra un formulario para elegir entre las exenciones **vigentes**.
    -   `store`: Aplica la exención al trámite y recalcula el total.
    -   `destroy`: Quita la exención del trámite y recalcula el total.

### Rutas (`TramiteExencion`)

-   **Ubicación:** `routes/web.php`
-   Definido como un grupo de rutas anidado.

```php
Route::prefix('tramites/{tramite}/exenciones')->name('admin.tramites.exenciones.')->group(function () {
    Route::get('/', [TramiteExencionController::class, 'index'])->name('index');
    Route::get('/ajax/list', [TramiteExencionController::class, 'list'])->name('ajax.list');
    Route::get('/create', [TramiteExencionController::class, 'create'])->name('create');
    Route::post('/', [TramiteExencionController::class, 'store'])->name('store');
    Route::get('/{item}', [TramiteExencionController::class, 'show'])->name('show');
    Route::delete('/{item}', [TramiteExencionController::class, 'destroy'])->name('destroy');
});
```

### Policies y Permisos (`TramiteExencion`)

-   **Policy:** `app/Policies/TramiteExencionPolicy.php`
-   Mapea las acciones a los siguientes permisos:
    -   `browse_tramite_exenciones`
    -   `read_tramite_exenciones`
    -   `add_tramite_exenciones`
    -   `delete_tramite_exenciones`

### Requests (`TramiteExencion`)

-   **Ubicación:** `app/Http/Requests/StoreTramiteExencionRequest.php`
-   Valida la aplicación de una exención a un trámite.
-   **Regla de Negocio Crítica:** Utiliza el método `withValidator` para añadir una validación personalizada que impide aplicar la misma exención dos veces al mismo trámite.

    ```php
    $validator->after(function ($v) {
        // ...
        if ($tramite->exenciones()->where('exencion_id', $exencionId)->exists()) {
            $v->errors()->add('exencion_id', 'Esta exención ya fue aplicada al trámite.');
        }
    });
    ```

### Vistas (`TramiteExencion`)

-   **Ubicación:** `resources/views/admin/tramites/exenciones/`
-   Vistas para gestionar las exenciones dentro del contexto de un trámite (`browse`, `list`, `create`, `read`).

---

## ⚙️ Integración y Flujo de Trabajo

1.  Un **Administrador** crea una nueva exención en el catálogo a través del menú "Exenciones" (Ej: "Beneficio por Ley 123", 10% de descuento).
2.  Un **Operador** está trabajando en un `Trámite` específico.
3.  Dentro de la vista de detalle del trámite, el operador va a la sección de "Exenciones Aplicadas".
4.  Hace clic en "Aplicar Exención". El sistema le muestra una lista de todas las exenciones **vigentes** del catálogo.
5.  El operador selecciona la "Beneficio por Ley 123" y especifica el `monto_aplicado` (el cálculo del 10% en Bs.).
6.  Al guardar, `TramiteExencionController@store` crea el registro en la tabla `tramite_exenciones`.
7.  Inmediatamente después, el controlador invoca a `IdtgbCalculator` para que recalcule los totales del `Trámite`, aplicando el descuento de la nueva exención.
8.  El operador es redirigido al listado de exenciones del trámite, donde ahora ve el nuevo beneficio aplicado y los montos del trámite actualizados.

---

## 📝 Guía para Desarrolladores

### Extender el Módulo
Para añadir un nuevo tipo de exención que dependa de una condición (ej. `requiere_documento_respaldo`):
1.  **Migración:** Añadir un campo booleano `requiere_documento` a la tabla `exenciones`.
2.  **Modelo `Exencion`:** Añadir `requiere_documento` al array `$fillable` y `$casts`.
3.  **Vistas `admin/exenciones`:** Añadir el checkbox al formulario `edit-add.blade.php`.
4.  **Requests `Exencion`:** Actualizar `StoreExencionRequest` y `UpdateExencionRequest` para validar el nuevo campo.
5.  **Vistas `admin/tramites/exenciones`:** En `create.blade.php`, usar JavaScript para mostrar un campo de carga de archivos si la exención seleccionada tiene `requiere_documento = true`.

### Puntos Clave a Recordar
-   **Dos contextos:** Siempre diferencia si estás trabajando en el catálogo (`ExencionController`) o en la aplicación a un trámite (`TramiteExencionController`).
-   **Servicio `IdtgbCalculator`:** Es el punto central de la lógica de negocio para los cálculos. Cualquier cambio que afecte los montos de un trámite debe invocar este servicio para mantener la consistencia.
-   **Vigencia:** El sistema filtra correctamente las exenciones vigentes al momento de aplicarlas. Asegúrate de que las fechas de vigencia en el catálogo sean correctas.
-   **Validación de duplicados:** La lógica para prevenir la doble aplicación de una exención reside en `StoreTramiteExencionRequest`.
