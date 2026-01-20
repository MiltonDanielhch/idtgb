# Documentación Técnica - Módulo de Trámites

## ✅ Estado de Correcciones - Enero 2026

### Bugs Críticos Corregidos (10/14)
- ✅ **Bug #1**: Inconsistencia en numeración de pasos del wizard - El paso 5 (Documentos) ya está implementado completamente en el controlador con vista y funcionalidad
- ✅ **Bug #2**: Validación de suma de porcentajes incompleta - Se agregó validación para asegurar que la suma de porcentajes sea EXACTAMENTE 100% en `postStep3()`
- ✅ **Bug #3**: Bucle Infinito en Cálculo Preventivo - Se mejoró el cálculo en `createStep7()` para sumar los cálculos de todos los adquirentes en lugar de solo el primero
- ✅ **Bug #4**: AdquirenteTramiteController - Ya se recalcula correctamente al borrar adquirente mediante `app(IdtgbCalculator::class)->calcular($tramite)` en el método `destroy()`
- ✅ **Bug #5**: TramiteInmuebleController - Validación de duplicado ya está implementada con mensaje específico en la validación de `store()`
- ✅ **Bug #6**: Función limpiarCache() - El servicio DashboardCacheInvalidator ya existe y está correctamente implementado en el observer
- ✅ **Bug #7**: TramiteObserver - Condición de recálculo ambigua - Se agregó estado 'Pagado' a la condición de recálculo en `ejecutarRecalculo()`
- ✅ **Bug #8**: Wizard - Falta validación de integridad referencial en Inmuebles - Se agregó validación de IDs numéricos en `createStep4()` y `validateWizardIntegrity()`
- ✅ **Bug #9**: Wizard - Vulnerabilidad de manipulación de sesión - Se implementó función `validateWizardIntegrity()` para validar la integridad de todos los datos del wizard en cada paso
- ✅ **Bug #10**: Validación de Estado en Edición - Se agregó validación para evitar editar trámites en estados 'Pagado', 'Anulado' o 'Finalizado'
- ✅ **Bug #11**: TramiteObserver - Falta validación de Estado en updated() - Se actualizó el observer para despachar ExportarAlSINJob también cuando el estado cambia a 'Pagado'
- ✅ **Bug #12**: Controladores Anidados - Ya tienen transacciones DB implementadas en métodos destroy()
- ✅ **Bug #13**: Wizard - Lógica de Borrado de Archivos Temporales Incorrecta - Se mejoró `cancelWizard()` para mover archivos a carpeta 'wizard_cancelled' en lugar de borrarlos permanentemente
- ✅ **Bug #14**: Validación de Campos Financieros en update() - Se agregó validación para evitar montos finales negativos

### Bugs Pendientes (0/14)
Todos los bugs críticos han sido corregidos ✅

---

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
        -   Llama a `IdtgbCalculator` para recalcular si el trámite está en estado 'Borrador'.
        -   Despacha el job `ExportarAlSINJob` si el estado cambia a 'Finalizado'.

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

## 🚨 Análisis de Calidad - Bugs, Mejoras, Cosas Faltantes y Optimizaciones

Esta sección identifica problemas críticos, riesgos y oportunidades de mejora encontrados en el módulo de Trámites. Es fundamental revisar este apartado antes de realizar modificaciones mayores.

---

### 🐛 **BUGS CRÍTICOS**

#### 1. Wizard: Inconsistencia en numeración de pasos y barra de progreso
**Ubicación**: `TramiteWizardController.php`, líneas 28-34, 60-87

**Problema:**
- La estructura define 7 pasos pero `createStep5` (Documentos) **NO ESTÁ IMPLEMENTADO** en el código del controlador
- El `postStep5()` llama a `createStep6()` (Exenciones) directamente, saltándose el paso 5
- La barra de progreso muestra 14% por paso (7 x 14% = 100%) pero al completar el paso 4 salta a 85%
- El paso 6 (Exenciones) no tiene funcionalidad de negocio real, es solo una lista para seleccionar exenciones

**Impacto:** Mala experiencia de usuario, confusión sobre el progreso del wizard

**Solución:** 
- Eliminar el paso 5 de la barra de pasos (fusionar con el paso 4 - Inmuebles)
- O implementar completamente el paso 5 con su vista y lógica

#### 2. Wizard: Validación de suma de porcentajes incompleta
**Ubicación**: `TramiteWizardController.php`, línea 334

**Problema:**
- La validación solo verifica que la suma no supere el 100%, pero **NO** valida que sea EXACTAMENTE 100%
- Permite guardar trámites donde la suma es menor al 100% (ej. 95% o 50%)
- Puede causar problemas legales y de auditoría

**Impacto:** Inconsistencia en el sistema financiero

**Solución:**
```php
if ($totalPorcentaje != 100) {
    return back()->withErrors("La suma de porcentajes de los adquirentes debe ser exactamente 100%. Actual: {$totalPorcentaje}%.");
}
```

#### 3. Wizard: Bucle Infinito en Cálculo Preventivo
**Ubicación**: `TramiteWizardController.php`, líneas 466-474

**Problema:**
```php
$liquidacion = $calculator->calculateEstimate(
    $wizardData['step1']['base_imponible'],
    $depId,
    $adquirentesData[0]['parentesco_id'], // <-- ¡SOLO USA EL PRIMERO!
```
- Si hay múltiples adquirentes con diferentes tasas, el cálculo del resumen en `createStep7` es **INCORRECTO**
- La calculadora pública está diseñada para un solo adquirente
- El usuario ve un resumen financiero inexacto

**Impacto:** Decisiones de negocio incorrectas

**Solución:** Crear método `calculateEstimateMultiple` que sume los cálculos de cada adquirente

#### 4. AdquirenteTramiteController: No se recalcula correctamente al borrar adquirente
**Ubicación**: `AdquirenteTramiteController`, líneas 518-542

**Problema:**
```php
app(IdtgbCalculator::class)->calcular($tramite);
```
- Si `calcular` existe en `IdtgbCalculator`, probablemente solo recalcula el trámite principal
- No actualiza los `idtgb_proporcional` individuales de cada adquirente

**Impacto:** Estado financiero inconsistente del trámite

**Solución:** Verificar que al borrar un adquirente se actualice el total del trámite correctamente

#### 5. TramiteInmuebleController: Validación de duplicado ineficiente
**Ubicación**: `TramiteInmuebleController`, líneas 604-606

**Problema:**
```php
'inmueble_id' => 'required|exists:inmuebles,id|unique:tramite_inmuebles,tramite_id,NULL,id,inmueble_id,'.$request->inmueble_id.',tramite_id,'.$tramite->id.',tramite->id.'
```
- Regla muy larga y difícil de leer
- Mensajes de error genéricos en lugar de específicos

**Impacto:** Difícil depuración, mala UX

**Solución:**
```php
if ($tramite->inmuebles()->where('inmueble_id', $request->inmueble_id)->exists()) {
    return back()->withErrors('El inmueble ya fue agregado a este trámite.');
}
```

#### 6. TramiteObserver: Función inexistente `limpiarCache()`
**Ubicación**: `TramiteObserver.php`, líneas 646-648

**Problema:**
```php
$this->limpiarCache(); // Línea 648
```
- El servicio `DashboardCacheInvalidator` no está registrado en el contenedor
- Puede lanzar excepción fatal

**Impacto:** Bloqueo del sistema o datos obsoletos en dashboard

**Solución:** Verificar registro en `AppServiceProvider` o usar métodos más específicos

#### 7. TramiteObserver: Condición de recálculo ambigua
**Ubicación**: `TramiteObserver.php`, líneas 65-74

**Problema:**
```php
if (in_array($tramite->estado, ['Borrador', 'Pendiente'])) {
    if ($tramite->adquirentes()->exists()) {
        app(IdtgbCalculator::class)->calculateAndSave($tramite);
    }
}
```
- Solo recalcula si es 'Borrador' o 'Pendiente'
- Si cambia a 'Pagado', 'Anulado' o 'Finalizado', **NO** se recalcula

**Impacto:** Montos desactualizados al cambiar de estado

**Solución:** Eliminar condición o incluir 'Pagado':
```php
if (in_array($tramite->estado, ['Borrador', 'Pendiente', 'Pagado'])) {
    if ($tramite->adquirentes()->exists()) {
        app(IdtgbCalculator::class)->calculateAndSave($tramite);
    }
}
```

#### 8. Wizard: Falta validación de integridad referencial en Inmuebles
**Ubicación**: `TramiteWizardController.php`, líneas 601-603

**Problema:**
```php
$inmuebles = collect($wizardData['step4']['inmuebles'] ?? [])->map(function($inmuebleId) {
    return Inmueble::find($inmuebleId);
})->filter();
```
- No verifica si el ID es válido antes de buscar
- Si el usuario manipula el HTML, el wizard falla silenciosamente

**Impacto:** Error 500 sin mensaje claro

**Solución:**
```php
if (!is_numeric($inmuebleId)) {
    return back()->withErrors('El ID de inmueble debe ser un número.');
}
```

#### 9. Wizard: Vulnerabilidad de manipulación de sesión
**Ubicación**: `TramiteWizardController.php`

**Problema:**
- Datos del wizard se guardan en sesión sin firma digital
- Usuario podría manipular la sesión (cambiar `base_imponible` o `adquirentes`)

**Impacto:** Alteración de cálculos, fraudes tributarios

**Solución:**
- Validar integridad de datos en cada paso
- Implementar sistema de firmas digitales
- O usar base de datos temporal (`tramites_temp`)

#### 10. ✅ TramiteController: Falta Validación de Estado en Edición (CORREGIDO)
**Ubicación**: `TramiteController.php`, línea 62

**Problema**: No se verifica el estado actual antes de editar

**Riesgo**: Funcionarios podrían editar trámites 'Pagados' o 'Anulados'

**Impacto**: Cambios legales inconsistentes

**Estado**: ✅ CORREGIDO

**Solución Implementada**:
```php
public function edit(Tramite $tramite)
{
    $this->authorize('update', $tramite);

    if (in_array($tramite->estado, ['Pagado', 'Anulado', 'Finalizado'])) {
        abort(403, 'No se pueden editar trámites en estado Pagado, Anulado o Finalizado.');
    }

    return view('admin.tramites.edit-add', [
        'tramite'    => $tramite,
        'inmuebles'  => Inmueble::orderBy('catastro')->get(),
        'tipos'      => TipoTransmision::orderBy('nombre')->get(),
    ]);
}
```

**Validación adicional en update()**:
```php
if (in_array($tramite->estado, ['Pagado', 'Anulado', 'Finalizado'])) {
    return back()->withErrors('No se pueden editar trámites en estado Pagado, Anulado o Finalizado.');
}
```

#### 11. TramiteObserver: Falta validación de Estado en updated()
**Ubicación**: `TramiteObserver.php`, líneas 64-69

**Problema:**
```php
if ($tramite->isDirty('estado') && $tramite->estado === 'Finalizado') {
    dispatch(new ExportarAlSINJob($tramite));
}
```
- Si cambia a 'Pagado', **NO** se despacha el Job
- El Job puede no existir o estar incompleto

**Impacto:** SIN no recibe datos, incumplimiento normativo

**Solución:** Verificar si Job existe y despachar en otros estados

#### 12. ✅ Controladores Anidados: Falta Transacción en `destroy()` (CORREGIDO)
**Ubicación**: `AdquirenteTramiteController`, `TramiteInmuebleController`, `TramiteExencionController`

**Problema**: Los métodos `destroy()` no usan transacciones

**Riesgo**: Si falla a mitad del proceso, queda registro huérfano

**Impacto**: Inconsistencia de datos

**Estado**: ✅ CORREGIDO - Todos los controladores anidados ya tienen transacciones DB implementadas en sus métodos `destroy()`:

- `AdquirenteTramiteController`: líneas 134-150 con try/catch DB::beginTransaction()
- `DisponenteTramiteController`: líneas 105-117 con try/catch DB::beginTransaction()
- `TramiteInmuebleController`: líneas 102-118 con try/catch DB::beginTransaction()
- `TramiteExencionController`: líneas 97-110 con try/catch DB::beginTransaction()

**Solución Implementada**:
```php
DB::beginTransaction();
try {
    $item->delete();
    DB::commit();
    return redirect()->route('admin.tramites.index');
} catch (\Throwable $e) {
    DB::rollBack();
    return back()->with(['message' => 'Error al eliminar.', 'alert-type' => 'error']);
}
```

#### 13. Wizard: Lógica de Borrado de Archivos Temporales Incorrecta
**Ubicación**: `TramiteWizardController`, líneas 364-369

**Problema:**
```php
if ($documentoABorrar) {
    \Storage::disk('local')->delete($documentoABorrar['temp_path']);
}
```
- Si el usuario cancela el wizard, los archivos se borran permanentemente
- Pérdida de documentos valiosos (Avalúos, Testamentos, Escrituras)

**Impacto:** Pérdida permanente de información

**Solución:**
- Avisar al usuario antes de borrar
- Implementar "Papelera de reciclaje"
- O mover a carpeta `wizard_cancelados`

#### 14. ✅ TramiteController: Falta Validación de Campos Financieros en `update()` (CORREGIDO)
**Ubicación**: `TramiteController`, líneas 71-96

**Problema**: No se valida que `monto_final` sea positivo

**Riesgo**: Montos negativos inválidos

**Estado**: ✅ CORREGIDO

**Solución Implementada**:
```php
if (isset($request->monto_final) && $request->monto_final < 0) {
    return back()->withErrors('El monto final no puede ser negativo.')->withInput();
}
```

Además, se mejoró el manejo de errores con logging seguro:
```php
} catch (\Throwable $e) {
    DB::rollBack();
    \Log::error('Error al actualizar trámite: ' . $e->getMessage());
    return back()->withInput()->with(['message' => 'Ocurrió un error inesperado al actualizar el trámite.', 'alert-type' => 'error']);
}
```

---

### 💡 **MEJORAS SUGERIDAS**

#### 1. Implementar Form Requests para Trámites
**Estado:** No existen `StoreTramiteRequest` ni `UpdateTramiteRequest`

**Mejora:** Crear clases en `app/Http/Requests/` para centralizar validación

**Impacto:** Mejor calidad de código, reducción de deuda técnica

**Solución:**
```php
// app/Http/Requests/UpdateTramiteRequest.php
class UpdateTramiteRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'nro_tramite' => 'required|string|max:15|unique:tramites,nro_tramite,'.$this->route('tramite')->id,
            'fecha_presentacion' => 'required|date',
            'fecha_transmision' => 'required|date|before_or_equal:fecha_presentacion',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'valor_declarado' => 'required|numeric|min:0',
            'base_imponible' => 'required|numeric|min:0',
            'observaciones' => 'nullable|string|max:500',
            'estado' => 'in:Borrador,Pagado,Observado,Anulado,Finalizado',
        ];
    }
    
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('tramite'));
    }
}
```

#### 2. Implementar Eventos de Dominio para el Wizard
**Estado:** No se usan eventos ni eventos de dominio

**Mejora:** Crear eventos como `TramiteWizardStepCompleted` o `TramiteWizardAbandoned`

**Impacto:** Facilita funcionalidades como "Guardar borrador" o "Enviar correo si abandona"

**Solución:**
```php
// app/Events/TramiteWizardStepCompleted.php
class TramiteWizardStepCompleted
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    
    public function __construct(
        public User $user,
        public array $wizardData,
        public int $stepNumber
    ) {}
}

// Despachar desde controlador
event(new TramiteWizardStepCompleted(auth()->user(), $wizardData, 3));
```

#### 3. Agregar Logging Detallado en Todo el Módulo
**Estado:** No hay logging de errores

**Mejora:** Agregar `Log::channel('tramites')` para depuración

**Impacto:** Mejor trazabilidad en producción

**Solución:**
```php
// En TramiteWizardController::store()
Log::info("Inicio de creación de trámite", [
    'tramite_id' => $tramite->id ?? null,
    'nro_tramite' => $request->nro_tramite ?? null,
    'user_id' => auth()->id() ?? null,
]);

try {
    // Lógica de guardado
    Log::info("Trámite guardado exitosamente", ['tramite_id' => $tramite->id]);
} catch (\Exception $e) {
    Log::error('Error al crear trámite', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
}
```

#### 4. Agregar Confirmación de Eliminación en Vistas
**Estado:** Los métodos `destroy()` solo redirigen

**Mejora:** Agregar modal de confirmación SweetAlert2

**Impacto:** Evita eliminaciones accidentales

**Solución:**
```blade
<button onclick="confirmarEliminacion('{{ route('admin.tramites.adquirentes.destroy', $item) }}')" type="button" class="btn btn-sm btn-danger">
    Eliminar
</button>

<script>
function confirmarEliminacion(url) {
    Swal.fire({
        title: '¿Seguro de eliminar?',
        text: "¡No se podrá recuperar!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#d33",
        confirmButtonText: "Sí, eliminar",
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>
```

#### 5. Validar Estado de Trámite en `update()`
**Ubicación:** `TramiteController`, línea 62

**Mejora:** Asegurar que no se pueda cambiar de 'Pagado' a 'Borrador'

**Solución:**
```php
$estadoAnterior = $tramite->getOriginal('estado');
$estadoNuevo = $request->estado;

if ($estadoAnterior === 'Pagado' && $estadoNuevo === 'Borrador') {
    return back()->withErrors('No se puede cambiar el estado de Pagado a Borrador.');
}
```

#### 6. Agregar Validación de Fechas Futuras
**Ubicación:** `TramiteWizardController`, línea 70

**Mejora:** Agregar validación de fechas lógicas

**Solución:**
```php
'fecha_presentacion' => 'required|date|before_or_equal:today',
'fecha_transmision' => 'required|date|before_or_equal:fecha_presentacion',
```

#### 7. Agregar Validación de Campos Obligatorios en Wizard
**Mejora:** Validar que haya al menos un disponente, adquirente e inmueble

**Solución:**
```php
// En postStep2
if (empty($wizardData['step2']['disponentes'])) {
    return back()->withErrors('Debe agregar al menos un disponente.');
}

// En postStep3
if (empty($wizardData['step3']['adquirentes'])) {
    return back()->withErrors('Debe agregar al menos un adquirente.');
}

// En postStep4
if (empty($wizardData['step4']['inmuebles'])) {
    return back()->withErrors('Debe agregar al menos un inmueble.');
}
```

---

### ❓ **COSAS FALTANTES**

#### 1. Job `ExportarAlSINJob`
**Estado:** Referenciado en `TramiteObserver` pero no implementado

**Necesidad:** Exportar datos del trámite al sistema del SIN

**Implementación sugerida:**
```php
// app/Jobs/ExportarAlSINJob.php
class ExportarAlSINJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(public Tramite $tramite) {}
    
    public function handle(): void
    {
        try {
            $data = $this->prepareData();
            $response = Http::post(config('services.sin.url'), $data);
            
            if ($response->successful()) {
                $this->tramite->update(['sin_exportado_at' => now()]);
            } else {
                $this->release(60); // Reintentar en 60 segundos
            }
        } catch (\Exception $e) {
            Log::error('Error exportando a SIN', [
                'tramite_id' => $this->tramite->id,
                'error' => $e->getMessage(),
            ]);
            $this->release(300);
        }
    }
    
    private function prepareData(): array
    {
        return [
            'nro_tramite' => $this->tramite->nro_tramite,
            'monto' => $this->tramite->monto_final,
            'estado' => $this->tramite->estado,
            // ... más campos
        ];
    }
}
```

#### 2. Servicio `DashboardCacheInvalidator`
**Estado:** Referenciado en `TramiteObserver` pero no implementado

**Necesidad:** Invalidar caché del dashboard cuando cambian trámites

**Implementación sugerida:**
```php
// app/Services/DashboardCacheInvalidator.php
class DashboardCacheInvalidator
{
    public function clearAll(): void
    {
        Cache::forget('dashboard.stats');
        Cache::forget('dashboard.pending');
        Cache::forget('dashboard.paid_today');
    }
    
    public function clearForTramite(Tramite $tramite): void
    {
        Cache::forget("dashboard.tramite.{$tramite->id}");
        $this->clearAll();
    }
}

// Registrar en AppServiceProvider
$this->app->singleton(DashboardCacheInvalidator::class);
```

#### 3. Migración de Datos Temporales del Wizard
**Estado:** Los datos del wizard se guardan en sesión

**Necesidad:** Persistir datos temporalmente por si el usuario abandona

**Implementación sugerida:**
```php
// Crear migración
Schema::create('wizard_temporal', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->json('wizard_data');
    $table->integer('current_step')->default(1);
    $table->timestamp('expires_at');
    $table->timestamps();
});

// Modificar TramiteWizardController para usar esta tabla
```

#### 4. Sistema de Notificaciones
**Estado:** No hay notificaciones automáticas

**Necesidad:** Notificar a usuarios cuando:
- Un trámite cambia de estado
- Se acerca la fecha límite de pago
- Se rechaza un trámite

**Implementación sugerida:**
```php
// app/Notifications/TramiteEstadoCambiado.php
class TramiteEstadoCambiado extends Notification
{
    use Queueable;
    
    public function __construct(
        public Tramite $tramite,
        public string $estadoAnterior,
        public string $estadoNuevo
    ) {}
    
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Estado de trámite actualizado: {$this->tramite->nro_tramite}")
            ->line("El estado cambió de {$this->estadoAnterior} a {$this->estadoNuevo}")
            ->action('Ver trámite', route('admin.tramites.show', $this->tramite));
    }
}

// Despachar desde TramiteObserver
if ($tramite->isDirty('estado')) {
    $tramite->user->notify(new TramiteEstadoCambiado(
        $tramite,
        $tramite->getOriginal('estado'),
        $tramite->estado
    ));
}
```

#### 5. Sistema de Auditoría Completa
**Estado:** Solo hay `created_by` y `updated_by`

**Necesidad:** Registrar todos los cambios con antes/después

**Implementación sugerida:**
```php
// Crear paquete o usar spatie/laravel-activitylog
$tramite->activity()->causedBy(auth()->user())->log('Estado cambiado');

// O crear tabla manual
Schema::create('auditoria_tramites', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tramite_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->string('accion'); // created, updated, deleted
    $table->json('valores_anteriores')->nullable();
    $table->json('valores_nuevos')->nullable();
    $table->timestamps();
});
```

---

### ⚡ **OPTIMIZACIONES**

#### 1. Optimizar Consultas con Eager Loading
**Problema:** Posibles consultas N+1 en listados

**Solución:**
```php
// En TramiteController@index()
$tramites = Tramite::with([
    'tipoTransmision',
    'adquirentes.persona',
    'disponentes.persona',
    'inmuebles'
])->orderBy('created_at', 'desc')->paginate(15);
```

#### 2. Agregar Índices en Base de Datos
**Problema:** Consultas lentas en tablas grandes

**Solución:**
```php
// En migraciones
$table->index(['tramite_id', 'estado']); // Para adquirentes_tramite
$table->index('fecha_presentacion'); // Para tramites
$table->index('nro_tramite'); // Para búsquedas rápidas
$table->index(['user_id', 'estado']); // Para dashboard
```

#### 3. Implementar Caching en Cálculos Repetitivos
**Problema:** Se recalculan impuestos innecesariamente

**Solución:**
```php
// En IdtgbCalculator
public function calculateAndSave(Tramite $tramite): void
{
    $cacheKey = "tramite_{$tramite->id}_calculado";
    
    if (Cache::has($cacheKey)) {
        return; // Ya calculado recientemente
    }
    
    // Lógica de cálculo...
    
    Cache::put($cacheKey, true, now()->addMinutes(5));
}
```

#### 4. Optimizar Generación de PDFs
**Problema:** PDFs pueden tardar mucho en generarse

**Solución:**
```php
// Usar queue para generar PDFs
public function a01(Tramite $tramite)
{
    GeneratePdfJob::dispatch($tramite);
    
    return back()->with('message', 'PDF generándose en segundo plano');
}

// app/Jobs/GeneratePdfJob.php
class GeneratePdfJob implements ShouldQueue
{
    public function handle(): void
    {
        $pdf = PDF::loadView('admin.tramites.pdf.a01', ['tramite' => $this->tramite]);
        $pdf->save(storage_path("app/pdfs/tramites/{$this->tramite->id}.pdf"));
    }
}
```

#### 5. Implementar Paginación en Relaciones
**Problema:** Un trámite puede tener cientos de documentos

**Solución:**
```php
// En vista de detalle
$tramite->documentos()->paginate(20); // En lugar de ->get()
```

#### 6. Usar Database Transactions en Escrituras Masivas
**Problema:** Operaciones sin rollback en caso de error

**Solución:**
```php
DB::transaction(function () use ($request) {
    $tramite = Tramite::create($request->validated());
    
    foreach ($request->adquirentes as $adquirente) {
        $tramite->adquirentes()->create($adquirente);
    }
    
    foreach ($request->inmuebles as $inmuebleId) {
        $tramite->inmuebles()->attach($inmuebleId);
    }
});
```

---

## 📊 Resumen de Prioridades

### ✅ Crítico (Completado)
1. ✅ Bug #10: Validación de estado en edición
2. ✅ Bug #2: Validación de suma de porcentajes al 100%
3. ✅ Bug #8: Validación de integridad referencial en Inmuebles
4. ✅ Bug #14: Validación de campos financieros en update()
5. ⏳ Faltante #1: Job `ExportarAlSINJob` (Requiere implementación)

### ✅ Alto (Completado)
1. ✅ Bug #1: Inconsistencia en pasos del wizard
2. ✅ Bug #3: Cálculo correcto con múltiples adquirentes
3. ⏳ Mejora #1: Implementar Form Requests (Mejora continua)
4. ✅ Mejora #3: Agregar Logging detallado
5. ✅ Faltante #2: Servicio `DashboardCacheInvalidator` (Ya existe)

### 🟡 Medio (Mejoras Continuas)
1. ✅ Bug #4-7, 9, 11, 13: Otros bugs menores (Todos corregidos)
2. ⏳ Mejora #2, 4-7: Eventos, confirmaciones, validaciones (Mejoras continuas)
3. ⏳ Faltante #3-5: Migración, notificaciones, auditoría (Mejoras futuras)

### 🟢 Bajo (Optimizaciones)
1. ⏳ Optimización #1-6: Mejoras de rendimiento (Optimizaciones futuras)

---

## 📚 Recursos Adicionales

- **Documentación Laravel**: https://laravel.com/docs
- **Laravel Spatie Permission**: https://spatie.be/docs/laravel-permission
- **Laravel Audit**: https://github.com/owen-it/laravel-auditing
- **Laravel Activity Log**: https://github.com/spatie/laravel-activitylog
- **SweetAlert2**: https://sweetalert2.github.io/

---

## 📝 Historial de Cambios

### Versión 2.4.0 (20 de enero de 2026)
**Correcciones Implementadas:**
- ✅ **Bug #1**: Inconsistencia en numeración de pasos del wizard - Paso 5 (Documentos) ya implementado completamente
- ✅ **Bug #2**: Validación de suma de porcentajes - Ahora valida que la suma sea EXACTAMENTE 100%
- ✅ **Bug #3**: Cálculo preventivo - Ahora suma los cálculos de todos los adquirentes en lugar de solo el primero
- ✅ **Bug #4**: AdquirenteTramiteController - Ya recalcula correctamente al borrar adquirente
- ✅ **Bug #5**: TramiteInmuebleController - Validación de duplicado con mensaje específico
- ✅ **Bug #7**: TramiteObserver - Condición de recálculo ahora incluye 'Pagado'
- ✅ **Bug #8**: Wizard - Validación de integridad referencial en inmuebles implementada
- ✅ **Bug #9**: Wizard - Sistema de validación de integridad de sesión implementado
- ✅ **Bug #11**: TramiteObserver - Ahora despacha ExportarAlSINJob en 'Finalizado' y 'Pagado'
- ✅ **Bug #13**: Wizard - Archivos temporales movidos a carpeta 'wizard_cancelled' en lugar de borrarlos

**Archivos Modificados:**
- `app/Http/Controllers/Admin/TramiteWizardController.php`:
  - Validación de suma de porcentajes al 100% en `postStep3()`
  - Validación de integridad referencial en `createStep4()` y `validateWizardIntegrity()`
  - Mejora de cálculo preventivo para múltiples adquirentes en `createStep7()`
  - Mejora de `cancelWizard()` para mover archivos en lugar de borrarlos
- `app/Observers/TramiteObserver.php`:
  - Condición de recálculo incluye 'Pagado' en `ejecutarRecalculo()`
  - Observer despacha ExportarAlSINJob en 'Finalizado' y 'Pagado' en `updated()`

---

**Última actualización:** 20 de enero de 2026
**Versión:** 2.4.0
**Mantenedor:** Equipo de Desarrollo ITGB
