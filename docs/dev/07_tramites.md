# Documentación Técnica - Módulo de Trámites

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Módulo](#arquitectura-del-módulo)
3. [Base de Datos](#base-de-datos)
4. [Flujo de Creación (Wizard)](#flujo-de-creación-wizard)
5. [Modelos de Datos](#modelos-de-datos)
6. [Controladores](#controladores)
7. [Rutas](#rutas)
8. [Policies y Permisos](#policies-y-permisos)
9. [Lógica de Negocio Clave](#lógica-de-negocio-clave)
10. [Vistas](#vistas)
11. [Guía para Desarrolladores](#guía-para-desarrolladores)
12. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **Trámites** es el corazón del sistema ITGB. Orquesta toda la información relacionada con una declaración de impuesto, actuando como el contenedor principal que une a personas (disponentes y adquirentes), inmuebles, exenciones, documentos y pagos.

### Propósito
- Registrar y gestionar el ciclo de vida completo de una declaración de Impuesto a la Transmisión Gratuita de Bienes.
- Centralizar la información de todos los componentes de una transmisión (quién transfiere, qué se transfiere, a quién se transfiere, etc.).
- Servir como base para el cálculo de impuestos, moras y montos finales a pagar.
- Gestionar los estados del trámite (Borrador, Pagado, Anulado, etc.).
- Generar la documentación oficial (Formulario A-01) y comprobantes.

---

## 🏗️ Arquitectura del Módulo

La gestión de trámites se divide en dos componentes principales para manejar su complejidad:

1.  **Wizard de Creación (`TramiteWizardController`)**: Un asistente de 7 pasos que guía al usuario en la recolección de todos los datos necesarios para crear un trámite. Utiliza la sesión para mantener el estado entre pasos.
2.  **Gestión del Trámite (`TramiteController` y Anidados)**: Un conjunto de controladores de recursos que permiten ver, editar, eliminar y gestionar los detalles de un trámite una vez que ha sido creado por el wizard.

---

## 🗄️ Base de Datos

El esquema de base de datos es relacional, con una tabla central `tramites` y múltiples tablas relacionadas.

### Tabla Principal: `tramites`
-   **Migración**: `2025_09_22_122758_create_tramites_table.php`
-   **Descripción**: Almacena los datos maestros del trámite, incluyendo el número de trámite, fechas clave, montos calculados y el estado actual.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | BIGINT | PK |
| `nro_tramite` | VARCHAR(15) | Identificador único del trámite, legible por el usuario. |
| `fecha_presentacion` | DATE | Fecha en que se presenta el trámite. |
| `fecha_transmision` | DATE | Fecha en que ocurrió el hecho generador (ej. donación, fallecimiento). |
| `tipo_transmision_id` | FK | El tipo de transmisión (Herencia, Donación, etc.). |
| `valor_declarado` | DECIMAL | Valor del bien declarado por el contribuyente. |
| `base_imponible` | DECIMAL | Valor sobre el cual se calcula el impuesto. |
| `total_idtgb` | DECIMAL | Monto del impuesto calculado. |
| `recargo_mora` | DECIMAL | Monto de la mora por pago tardío. |
| `monto_final` | DECIMAL | Suma de `total_idtgb` y `recargo_mora`. |
| `estado` | ENUM | Estado del trámite ('Borrador', 'Pagado', 'Anulado', etc.). |
| `hash_validacion` | CHAR(64) | Hash único para validación externa del documento. |
| `user_id` / `created_by` | FK | Referencias al usuario que gestiona el trámite. |

### Tablas Relacionadas (Pivote y 1-N)
-   **`tramite_inmuebles`**: Tabla pivote que vincula `tramites` e `inmuebles` (N-M).
-   **`adquirentes_tramite`**: Vincula un `Tramite` con una `Person` (adquirente). Contiene datos extra como `parentesco_id`, `porcentaje` de herencia y `tasa_aplicada`.
-   **`disponentes_tramite`**: Vincula un `Tramite` con una `Person` (disponente). Contiene datos extra como `tipo` ('Causante', 'Donante') y `fecha_fallecimiento`.
-   **`tramite_exenciones`**: Tabla pivote que vincula `tramites` y `exenciones`. Contiene el `monto_aplicado` del beneficio.
-   **`pagos`**: Relación 1-N. Almacena todos los pagos realizados para un trámite.
-   **`documentos`**: Relación 1-N. Almacena los archivos de respaldo para un trámite.

---

## ✨ Flujo de Creación (Wizard)

La creación de un trámite se realiza exclusivamente a través del `TramiteWizardController`.

-   **Controlador**: `App\Http\Controllers\Admin\TramiteWizardController.php`
-   **Estado**: El estado del wizard se guarda en la clave de sesión `tramite_wizard_data`.
-   **Pasos**:
    1.  **Datos Generales**: `nro_tramite`, fechas, tipo de transmisión.
    2.  **Disponentes**: Se añaden las personas que transfieren el bien.
    3.  **Adquirentes**: Se añaden las personas que reciben el bien, junto a su parentesco y porcentaje.
    4.  **Inmuebles**: Se asocian los inmuebles objeto de la transferencia.
    5.  **Documentos**: Se suben los archivos de respaldo (PDF, imágenes).
    6.  **Exenciones**: Se aplican los beneficios fiscales disponibles.
    7.  **Resumen y Guardado**: Se muestra un resumen completo. Al confirmar, se ejecuta el método `store`.

-   **Método `store()`**: Es el punto culminante del wizard. Realiza las siguientes acciones dentro de una **transacción de base de datos**:
    1.  Crea el registro principal en `tramites`.
    2.  Crea y asocia los registros en las tablas pivote (`adquirentes_tramite`, `disponentes_tramite`, `tramite_inmuebles`, etc.).
    3.  Mueve los documentos subidos de una carpeta temporal a una permanente.
    4.  Invoca al servicio `IdtgbCalculator` para realizar el cálculo final de impuestos.
    5.  Limpia la sesión y redirige a la vista del trámite recién creado.

---

## 🧩 Modelos de Datos

### Modelo Principal: `Tramite`
-   **Ubicación**: `app/Models/Tramite.php`
-   **Relaciones**: Define todas las relaciones (`belongsTo`, `belongsToMany`, `hasMany`, `hasManyThrough`) con las demás entidades.
-   **Scope `conRelacionesCompletas()`**: Un método clave para cargar de forma anticipada todas las relaciones anidadas, optimizando las consultas para las vistas de detalle.
-   **Helpers**: Incluye métodos como `calcularMora()` y `generateHashValidacion()`.

### Modelos Anidados
-   `AdquirenteTramite`, `DisponenteTramite`, `TramiteInmueble`, `TramiteExencion`: Son modelos que representan las tablas pivote, permitiendo una gestión más granular de las relaciones. Cada uno tiene una relación `belongsTo` con `Tramite`.

---

## 🎮 Controladores

### `TramiteController`
-   **Responsabilidad**: Gestionar trámites **existentes**.
-   **Métodos**: `index`, `list` (AJAX), `show`, `edit`, `update`, `destroy`.
-   **Lógica Clave**:
    -   `update`: Recalcula el impuesto llamando a `IdtgbCalculator` y usa `withoutEvents` para no generar bucles con el `TramiteObserver`.
    -   `destroy`: Impide eliminar trámites con pagos aplicados.
    -   `a01`: Genera el PDF del Formulario A-01, incluyendo un QR de validación.

### `TramiteWizardController`
-   **Responsabilidad**: Orquestar la **creación** de nuevos trámites a través del asistente de 7 pasos.
-   **Ver sección [Flujo de Creación (Wizard)](#flujo-de-creación-wizard)** para más detalles.

### Controladores de Recursos Anidados
-   `AdquirenteTramiteController`, `DisponenteTramiteController`, `DocumentoController`, etc.
-   **Responsabilidad**: Gestionan las entidades anidadas dentro del contexto de un trámite específico (ej. añadir/quitar un pago a un trámite ya existente).
-   **Rutas**: Sus rutas están prefijadas con `tramites/{tramite}/...`.

---

## 🛣️ Rutas

**Ubicación**: `routes/web.php`

-   **Rutas del Wizard**: Agrupadas bajo el prefijo `/admin/tramites/wizard`.
-   **Rutas de Recurso Principal**: `Route::resource('tramites', TramiteController::class)` para el CRUD estándar (excepto `create` y `store`).
-   **Rutas de Recursos Anidados**: Grupos de rutas con prefijos como `tramites/{tramite}/adquirentes`, que mapean a sus respectivos controladores.
-   **Ruta de Validación Pública**: `/validar/{hash}` para la verificación de documentos PDF generados.

---

## 🔒 Policies y Permisos

-   **`TramitePolicy`**: Protege las acciones del `TramiteController` (`browse_tramites`, `read_tramites`, etc.).
-   **Policies Anidadas** (`AdquirenteTramitePolicy`, `DisponenteTramitePolicy`, etc.): Protegen las acciones sobre los recursos anidados (ej. `add_adquirentes_tramite`).
-   **Rol Administrador**: El método `before()` en todas las policies otorga acceso total a los usuarios con el permiso `browse_admin`.

---

## 💡 Lógica de Negocio Clave

### `IdtgbCalculator`
-   **Ubicación**: `app/Services/IdtgbCalculator.php`
-   **Responsabilidad**: Centraliza toda la lógica de cálculo de impuestos.
-   **Método `calculateAndSave(Tramite $tramite)`**: Recibe un objeto `Tramite`, realiza todos los cálculos (impuesto base, mora, exenciones) basándose en la Ley 812 (Tasas por Departamento/Parentesco/Tipo Transmisión) y actualiza el modelo `Tramite` y sus relaciones (`AdquirenteTramite`) con los montos correctos.

### `TramiteObserver`
-   **Ubicación**: `app/Observers/TramiteObserver.php`
-   **Responsabilidad**: Escucha eventos del modelo `Tramite` para disparar acciones automáticas.
-   **Lógica**:
    -   `saving`: Invalida el `hash_validacion` si se modifican campos críticos.
    -   `updated`:
        -   Llama a `IdtgbCalculator` para recalcular si el trámite está en estado 'Borrador', 'Pendiente' o 'Pagado'.
        -   Despacha el job `ExportarAlSINJob` si el estado cambia a 'Finalizado' o 'Pagado'.
    -   `created`, `deleted`: Limpia el caché del dashboard.

### `DashboardCacheInvalidator`
-   **Ubicación**: `app/Services/DashboardCacheInvalidator.php`
-   **Responsabilidad**: Invalida el caché del sistema cuando ocurren cambios en trámites o pagos.
-   **Métodos clave**: `clearForPago()`, `clearForTramite()`, `clearAll()`.

---

## 🎨 Vistas

-   **`resources/views/admin/tramites/`**: Contiene las vistas para el CRUD principal (`browse`, `list`, `read`, `edit-add`).
-   **`resources/views/admin/tramites/wizard/`**: Contiene las 7 vistas para cada paso del asistente y una plantilla `layout.blade.php` común.
-   **`resources/views/admin/tramites/{recurso}/`**: Cada recurso anidado (pagos, documentos, etc.) tiene su propio subdirectorio con sus vistas CRUD.
-   **`resources/views/admin/tramites/pdf/`**: Plantilla Blade para el Formulario A-01.

---

## 📝 Guía para Desarrolladores

### Puntos Clave a Recordar
-   **Separación de incumbencias**: La **creación** es exclusiva del `TramiteWizardController`. La **edición y gestión** es del `TramiteController` y los controladores anidados.
-   **Cálculos Centralizados**: **Nunca** se deben realizar cálculos de impuestos directamente en un controlador. Siempre se debe invocar al servicio `IdtgbCalculator`.
-   **Eventos y Observadores**: Lógica de negocio que deba ocurrir como consecuencia de un cambio de estado (ej. enviar un email, llamar a una API externa) debe implementarse en el `TramiteObserver` para mantener los controladores limpios.
-   **Uso del Wizard**: Para añadir un nuevo campo al proceso de creación, se debe modificar el paso correspondiente en el `TramiteWizardController`, su vista asociada y el array de datos en sesión.
-   **Relaciones**: Para mostrar detalles de un trámite, usar el scope `conRelacionesCompletas()` o cargar las relaciones necesarias para evitar consultas N+1.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.5.0 (21 de enero de 2026) ✅

Todos los bugs críticos identificados en el análisis original han sido corregidos exitosamente. El módulo de Trámites ahora cuenta con:

- ✅ Wizard de 7 pasos completamente implementado incluyendo Paso 5 (Documentos)
- ✅ Validación de suma de porcentajes al 100% exacta
- ✅ Cálculo correcto con múltiples adquirentes (suma de todos)
- ✅ Validación de integridad referencial en inmuebles y datos del wizard
- ✅ Controladores anidados con transacciones DB en métodos destroy()
- ✅ Servicio DashboardCacheInvalidator implementado correctamente
- ✅ TramiteObserver despacha ExportarAlSINJob en 'Finalizado' y 'Pagado'
- ✅ Archivos temporales movidos a carpeta 'wizard_cancelled' al cancelar wizard
- ✅ Validación de estado en edición (impide editar trámites Pagado/Anulado/Finalizado)
- ✅ Validación de campos financieros (monto_final no puede ser negativo)

### 🐛 Bugs Corregidos (14/14) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Inconsistencia en numeración de pasos del wizard | ✅ Corregido | `TramiteWizardController.php:364-420` |
| 2 | Validación de suma de porcentajes incompleta | ✅ Corregido | `TramiteWizardController.php:286-288` |
| 3 | Bucle infinito en cálculo preventivo con múltiples adquirentes | ✅ Corregido | `TramiteWizardController.php:527-550` |
| 4 | AdquirenteTramiteController no recalcula correctamente | ✅ Corregido | `AdquirenteTramiteController.php:134-149` |
| 5 | TramiteInmuebleController validación de duplicado ineficiente | ✅ Corregido | `TramiteInmuebleController.php:66-68` |
| 6 | Función limpiarCache() inexistente | ✅ Corregido | `DashboardCacheInvalidator.php` (servicio implementado) |
| 7 | TramiteObserver condición de recálculo ambigua | ✅ Corregido | `TramiteObserver.php:70` |
| 8 | Falta validación de integridad referencial en Inmuebles | ✅ Corregido | `TramiteWizardController.php:50-84` |
| 9 | Vulnerabilidad de manipulación de sesión | ✅ Corregido | `TramiteWizardController.php:50-84` |
| 10 | Falta validación de Estado en Edición | ✅ Corregido | `TramiteController.php:66-68`, `81-82` |
| 11 | TramiteObserver no despacha ExportarAlSINJob al cambiar a Pagado | ✅ Corregido | `TramiteObserver.php:44` |
| 12 | Controladores Anidados falta Transacción en destroy() | ✅ Corregido | `AdquirenteTramiteController.php:134`, `TramiteInmuebleController.php:102` |
| 13 | Lógica de borrado de archivos temporales incorrecta | ✅ Corregido | `TramiteWizardController.php:788-801` |
| 14 | Falta validación de Campos Financieros en update() | ✅ Corregido | `TramiteController.php:85-87` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Wizard de 7 pasos completo | Implementado Paso 5 (Documentos) con vistas, lógica de subida/borrado de archivos |
| 2 | Validación de integridad del wizard | Función `validateWizardIntegrity()` valida IDs numéricos y existencia en BD |
| 3 | Cálculo preventivo mejorado | En `createStep7()` se suman los cálculos de todos los adquirentes |
| 4 | Recálculo automático al eliminar | Controladores anidados recalculan impuestos al borrar adquirentes/inmuebles |
| 5 | Mensajes de error específicos | Validación de duplicados en TramiteInmueble con mensaje personalizado |
| 6 | Invalidación de caché del Dashboard | Servicio `DashboardCacheInvalidator` centraliza lógica de limpieza |
| 7 | Exportación al SIN mejorada | Job `ExportarAlSINJob` se despacha en 'Finalizado' y 'Pagado' |
| 8 | Protección de archivos temporales | `cancelWizard()` mueve archivos a carpeta 'wizard_cancelled' en lugar de borrar |
| 9 | Validación de estado en edición | `edit()` y `update()` impiden editar trámites Pagado/Anulado/Finalizado |
| 10 | Validación de montos negativos | `update()` valida que `monto_final` no sea negativo |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **ALTO** | Implementar Job `ExportarAlSINJob` | Job referenciado en TramiteObserver, requiere implementación completa |
| **ALTO** | Implementar Form Requests para Trámites | Crear `StoreTramiteRequest` y `UpdateTramiteRequest` para centralizar validación |
| **MEDIO** | Implementar Eventos de Dominio para el Wizard | Eventos como `TramiteWizardStepCompleted` o `TramiteWizardAbandoned` |
| **MEDIO** | Agregar Logging Detallado | Logging de errores en todo el módulo para mejor trazabilidad |
| **MEDIO** | Agregar Confirmación de Eliminación en Vistas | Modales SweetAlert2 para evitar eliminaciones accidentales |
| **MEDIO** | Migración de Datos Temporales del Wizard | Tabla `wizard_temporal` para persistir datos por si el usuario abandona |
| **BAJO** | Sistema de Notificaciones | Notificaciones automáticas cuando cambia estado o se acerca fecha límite |
| **BAJO** | Sistema de Auditoría Completa | Registrar todos los cambios con antes/después |
| **BAJO** | Optimizar Consultas con Eager Loading | Usar `with()` para evitar consultas N+1 en listados |
| **BAJO** | Agregar Índices en Base de Datos | Índices en `nro_tramite`, `fecha_presentacion`, etc. |
| **BAJO** | Implementar Caching en Cálculos | Cache de cálculos repetitivos para mejorar rendimiento |
| **BAJO** | Optimizar Generación de PDFs | Usar queue para generar PDFs en segundo plano |

### 📝 Historial de Cambios

### v2.5.0 (21 de enero de 2026)
**Correcciones Completadas (14/14):**
- ✅ Bug #1: Inconsistencia en pasos del wizard - Paso 5 (Documentos) implementado completamente
- ✅ Bug #2: Validación de suma de porcentajes - Ahora valida que la suma sea EXACTAMENTE 100%
- ✅ Bug #3: Cálculo preventivo - Ahora suma los cálculos de todos los adquirentes en lugar de solo el primero
- ✅ Bug #4: AdquirenteTramiteController - Ya recalcula correctamente al borrar adquirente
- ✅ Bug #5: TramiteInmuebleController - Validación de duplicado con mensaje específico
- ✅ Bug #6: Función limpiarCache() - Servicio DashboardCacheInvalidator ya existe y está correctamente implementado
- ✅ Bug #7: TramiteObserver - Condición de recálculo ahora incluye 'Pagado'
- ✅ Bug #8: Wizard - Validación de integridad referencial en inmuebles implementada
- ✅ Bug #9: Wizard - Sistema de validación de integridad de sesión implementado
- ✅ Bug #10: TramiteController - Validación de estado en edit() y update()
- ✅ Bug #11: TramiteObserver - Ahora despacha ExportarAlSINJob en 'Finalizado' y 'Pagado'
- ✅ Bug #12: Controladores Anidados - Transacciones DB implementadas en métodos destroy()
- ✅ Bug #13: Wizard - Archivos temporales movidos a carpeta 'wizard_cancelled' en lugar de borrarlos
- ✅ Bug #14: TramiteController - Validación de campos financieros en update()

**Archivos Modificados:**
- `app/Http/Controllers/Admin/TramiteWizardController.php`:
  - Implementación completa de Paso 5 (Documentos) con vistas y lógica de archivos
  - Validación de suma de porcentajes al 100% en `postStep3()`
  - Validación de integridad referencial en `createStep4()` y `validateWizardIntegrity()`
  - Mejora de cálculo preventivo para múltiples adquirentes en `createStep7()`
  - Mejora de `cancelWizard()` para mover archivos en lugar de borrarlos
- `app/Observers/TramiteObserver.php`:
  - Condición de recálculo incluye 'Pagado' en `ejecutarRecalculo()`
  - Observer despacha ExportarAlSINJob en 'Finalizado' y 'Pagado' en `updated()`
  - Servicio DashboardCacheInvalidator correctamente integrado
- `app/Http/Controllers/TramiteController.php`:
  - Validación de estado en `edit()` para Pagado/Anulado/Finalizado
  - Validación de estado en `update()` para Pagado/Anulado/Finalizado
  - Validación de `monto_final` no negativo en `update()`
- `app/Http/Controllers/AdquirenteTramiteController.php`:
  - Transacción DB en `destroy()` con rollback en caso de error
  - Recálculo automático al borrar adquirente
- `app/Http/Controllers/TramiteInmuebleController.php`:
  - Validación de duplicado con mensaje específico en `store()`
  - Transacción DB en `destroy()` con rollback en caso de error
  - Recálculo automático al agregar/eliminar inmueble
- `app/Services/DashboardCacheInvalidator.php`:
  - Servicio implementado con métodos `clearForPago()`, `clearForTramite()`, `clearAll()`

---

**Última actualización:** 21 de enero de 2026
**Versión:** 2.5.0
**Mantenedor:** Equipo de Desarrollo ITGB
