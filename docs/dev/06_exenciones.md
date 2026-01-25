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
7. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

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
-   **Regla de Negocio:** Utiliza el método `withValidator` para añadir una validación personalizada que impide aplicar la misma exención dos veces al mismo trámite.

    ```php
    $validator->after(function ($v) {
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

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.0.0 (21 de enero de 2026) ⚠️

El módulo de Exenciones está **funcional pero requiere correcciones críticas** antes de ponerse en producción. Se han identificado 8 bugs de alta y media prioridad que deben ser abordados.

- ⚠️ Validación de monto máximo NO implementada
- ⚠️ Race condition en validación de duplicados
- ⚠️ Usa `now()` en lugar de fecha de presentación del trámite
- ⚠️ Autorización inconsistente en UpdateExencionRequest
- ⚠️ Cálculo manual de montos propenso a errores
- ⚠️ Sin validación de suma de exenciones vs impuesto
- ⚠️ Sin soft deletes implementados
- ❌ Sin tests unitarios y de integración

### 🐛 Bugs Corregidos (0/8) ⚠️

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Race Condition en Validación de Duplicados | ⚠️ Pendiente | `StoreTramiteExencionRequest.php:withValidator()` |
| 2 | Monto Aplicado Excede el Monto Máximo | ⚠️ Pendiente | `StoreTramiteExencionRequest.php` |
| 3 | Falta Validación de Fecha de Vigencia vs Fecha de Trámite | ⚠️ Pendiente | `TramiteExencionController.php:create()` |
| 4 | Inconsistencia en Autorización de Update | ⚠️ Pendiente | `UpdateExencionRequest.php:authorize()` |
| 5 | Cálculo Incorrecto de Exenciones Porcentuales | ⚠️ Pendiente | `TramiteExencionController.php:store()` |
| 6 | Monto Aplicado como 0 en Wizard | ⚠️ Pendiente | `TramiteWizardController.php` |
| 7 | No se Valida que la Suma de Exenciones no Exceda el Impuesto | ⚠️ Pendiente | `TramiteExencionController.php:store()` |
| 8 | Soft Deletes No Implementados | ⚠️ Pendiente | `Exencion.php`, `TramiteExencion.php` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Estado | Descripción |
|---|--------|--------|-------------|
| 1 | Arreglos en exención | ✅ Implementada | Commit 4629f44 - Ajustes menores en vistas y controlador |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **ALTA** | Índice Único Compuesto | Agregar `unique(['tramite_id', 'exencion_id'])` en `tramite_exenciones` |
| **ALTA** | Validación de Monto Máximo | Validar que `monto_aplicado` no exceda `monto_maximo` de la exención |
| **ALTA** | Cálculo Automático de Monto | Calcular automáticamente monto según tipo (porcentaje/monto fijo) |
| **ALTA** | Usar Fecha Presentación | Cambiar `now()` por `$tramite->fecha_presentacion` en validación de vigencia |
| **ALTA** | Corregir Autorización | Implementar autorización correcta en `UpdateExencionRequest` |
| **ALTA** | Validación Suma vs Impuesto | Validar que suma de exenciones no exceda impuesto calculado |
| **ALTA** | Suite de Tests | Crear tests unitarios y de integración para el módulo |
| **MEDIA** | Validación de Vigencia vs Trámite | Usar fecha de presentación del trámite en lugar de `now()` |
| **MEDIA** | Historial de Cambios | Implementar tracking de cambios en exenciones del catálogo |
| **MEDIA** | Caching de Exenciones Vigentes | Implementar cache para consultas frecuentes de exenciones |
| **MEDIA** | Índices de Rendimiento | Agregar índices en `vigente_desde`, `vigente_hasta`, `tipo`, `nombre` |
| **BAJA** | Soft Deletes | Implementar soft deletes en lugar de borrado físico |
| **BAJA** | Campo `activo` | Agregar campo booleano para desactivar temporalmente exenciones |
| **BAJA** | Notificación de Vencimiento | Comando/Job para notificar exenciones próximas a vencer |
| **BAJA** | Bulk Apply de Exenciones | Permitir aplicar múltiples exenciones en una operación |
| **BAJA** | Previsualización de Impacto | Mostrar en tiempo real cómo afecta la exención al monto total |
| **BAJA** | Exportación de Datos | Implementar exportación de catálogo y exenciones aplicadas |
| **BAJA** | Auditoría Completa | Registrar quién aplicó qué exención a qué trámite |

### ⚡ Optimizaciones de Rendimiento

| Estado | Optimización | Descripción |
|--------|--------------|-------------|
| ✅ Implementado | Paginación del Lado del Servidor | Ya se usa `paginate()` en listados |
| ✅ Implementado | Eager Loading de Relaciones | Ya se usa `with(['exencion'])` en `list()` |
| ⚠️ Pendiente | Índices en Base de Datos | Agregar índices en `vigente_desde`, `vigente_hasta`, `tipo`, `nombre` |
| ⚠️ Pendiente | Caching de Exenciones Vigentes | Implementar cache para exenciones vigentes consultadas frecuentemente |

### 📝 Faltantes y Cosas por Implementar

| Prioridad | Faltante | Descripción |
|-----------|----------|-------------|
| **ALTA** | Tests Unitarios y de Integración | Crear `ExencionTest`, `TramiteExencionTest`, `ExencionPolicyTest`, `ExencionVigenciaTest`, `ExencionCalculoTest` |
| **ALTA** | Documentación de API Endpoints | Documentar todos los endpoints con métodos, parámetros, respuestas, códigos de error |
| **MEDIA** | Validación de Reglas de Negocio Completas | Validar compatibilidad de exenciones, estado del trámite, etc. |
| **BAJA** | Observer para Recalcular Trámites | Observer que recalcule trámites al modificar/eliminar exenciones del catálogo |
| **BAJA** | Internationalization (i18n) | Usar archivos de idioma para facilitar traducciones futuras |
| **BAJA** | Migraciones de Datos | Crear migraciones de datos si se implementan cambios en el esquema |

### 🔍 Issues Específicos por Archivo

**`app/Models/Exencion.php`**
- Estado: ✅ Generalmente correcto
- El scope `vigente()` está bien implementado
- Los casts están correctamente definidos
- Mejoras: Agregar accessor para valor formateado, método para calcular monto automáticamente

**`app/Models/TramiteExencion.php`**
- Estado: ✅ Correcto
- Modelo simple, bien estructurado
- Mejoras: Agregar método helper `isVigente()`, accessor para monto formateado

**`app/Http/Controllers/ExencionController.php`**
- Estado: ✅ Con mejoras necesarias
- CRUD estándar bien implementado
- Validación de borrado correcta
- Mejoras: Implementar soft deletes, agregar logging más detallado

**`app/Http/Controllers/TramiteExencionController.php`**
- Estado: ⚠️ Requiere mejoras importantes
- Manejo de transacciones correcto
- Integración con IdtgbCalculator correcta
- Mejoras necesarias: Cambiar `now()` por `$tramite->fecha_presentacion`, implementar cálculo automático de monto, validar monto máximo, validar suma vs impuesto

**`app/Http/Requests/StoreExencionRequest.php`**
- Estado: ✅ Correcto
- Validación básica correcta
- Autorización implementada con Gate
- Mejoras: Agregar validación de reglas de negocio específicas

**`app/Http/Requests/UpdateExencionRequest.php`**
- Estado: ❌ Problema crítico
- El método `authorize()` devuelve siempre `true`
- Solución: Implementar autorización correcta con Gate

**`app/Http/Requests/StoreTramiteExencionRequest.php`**
- Estado: ⚠️ Necesita mejoras
- Validación básica correcta
- Validación de duplicados implementada (con race condition)
- Mejoras necesarias: Implementar índice único en BD, agregar validación de monto máximo, agregar validación de suma total vs impuesto

**`app/Services/IdtgbCalculator.php`**
- Estado: ✅ Lógica simplificada
- El cálculo de exenciones es simple (resta el monto aplicado)
- Mejoras: Considerar si las exenciones se restan del impuesto calculado o de la base imponible, implementar lógica más sofisticada si se requieren reglas especiales

### 📝 Historial de Cambios

### v1.0.0 (21 de enero de 2026)

**Estado Inicial:**
- Documentación técnica completa del módulo de Exenciones
- Identificación de 8 bugs de alta y media prioridad
- Arreglos menores implementados en commit 4629f44

**Bugs Identificados (0/8 corregidos):**
- Bug #1: Race condition en validación de duplicados - Pendiente índice único en BD
- Bug #2: Validación de monto máximo - Pendiente implementación en Request
- Bug #3: Fecha de vigencia vs fecha de trámite - Pendiente reemplazar `now()` por `$tramite->fecha_presentacion`
- Bug #4: Inconsistencia en autorización - Pendiente corregir `UpdateExencionRequest::authorize()`
- Bug #5: Cálculo automático de montos - Pendiente implementación en controlador
- Bug #6: Monto 0 en wizard - Pendiente cálculo antes de crear registro
- Bug #7: Validación de suma vs impuesto - Pendiente implementación en controlador
- Bug #8: Soft deletes - Pendiente implementar trait y migración

**Archivos Analizados:**
- `app/Models/Exencion.php` - Modelo de catálogo
- `app/Models/TramiteExencion.php` - Modelo de aplicación a trámites
- `app/Http/Controllers/ExencionController.php` - Controlador CRUD catálogo
- `app/Http/Controllers/TramiteExencionController.php` - Controlador aplicación a trámites
- `app/Http/Requests/StoreExencionRequest.php` - Request creación catálogo
- `app/Http/Requests/UpdateExencionRequest.php` - Request actualización catálogo
- `app/Http/Requests/StoreTramiteExencionRequest.php` - Request aplicación a trámite
- `database/migrations/2025_09_22_122751_create_exenciones_table.php` - Migración catálogo
- `database/migrations/2025_09_22_122804_create_tramite_exenciones_table.php` - Migración aplicación

**Recomendación:**
NO poner en producción sin abordar primero:
1. Validación de monto máximo
2. Índice único en base de datos para prevenir duplicados
3. Cálculo automático de montos
4. Corrección de autorización en UpdateExencionRequest
5. Validación de suma de exenciones vs impuesto
6. Suite de pruebas completa
