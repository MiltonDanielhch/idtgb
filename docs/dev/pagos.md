# Documentación Técnica - Módulo de Pagos

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelo](#modelo)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Policies y Permisos](#policies-y-permisos)
7. [Requests](#requests)
8. [Vistas](#vistas)
9. [Integración con otros módulos](#integración-con-otros-módulos)
10. [Guía para Desarrolladores](#guía-para-desarrolladores)
11. [Análisis de Calidad y Mejoras (Bugs Detectados)](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **Pagos** gestiona el registro y control de los abonos monetarios realizados por los contribuyentes para saldar el Impuesto de Transmisiones Gratuítas de Bienes (ITGB). Funciona como un sub-módulo anidado dentro de un Trámite.

### Propósito
- Registrar pagos parciales o totales asociados a un trámite.
- Almacenar la evidencia del pago (comprobantes de depósito, transferencias, QR).
- Llevar el control del saldo pendiente de un trámite.
- Validar que los montos ingresados correspondan a la deuda tributaria.

### Importancia en el Sistema ITGB
Es el paso final del flujo tributario. Un trámite no puede considerarse "Concluido" o "Pagado" hasta que la suma de los registros en este módulo cubra el `monto_final` calculado.

---

## 🗄️ Base de Datos

### Migración: `create_pagos_table.php`

**Tabla:** `pagos`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `tramite_id` | BIGINT | FK, NOT NULL | El trámite al que se abona el pago. |
| `monto` | DECIMAL(14,2) | NOT NULL | Cantidad pagada en Bolivianos (Bs). |
| `fecha_pago` | DATE | NOT NULL | Fecha en que se realizó la transacción bancaria. |
| `metodo_pago` | ENUM | ['Efectivo', 'Depósito', 'Transferencia', 'QR'] | Forma de pago. |
| `nro_comprobante` | VARCHAR(50) | NULLABLE | Número de operación o referencia bancaria. |
| `comprobante_path` | VARCHAR(255) | NULLABLE | Ruta al archivo de respaldo (imagen/pdf). |
| `observaciones` | TEXT | NULLABLE | Notas adicionales del cajero/operador. |
| `created_by` | BIGINT | FK | Usuario que registró el pago. |
| `created_at` | TIMESTAMP | | Fecha de registro. |
| `updated_at` | TIMESTAMP | | Fecha de actualización. |
| `deleted_at` | TIMESTAMP | NULLABLE | Fecha de borrado lógico (SoftDeletes). |

**Relaciones:**
- `tramite_id` referencia a `tramites(id)` con `onDelete('cascade')` (generalmente).
- `created_by` referencia a `users(id)`.

---

## 🧩 Modelo

### Modelo: `Pago`

**Ubicación:** `app/Models/Pago.php`

**Atributos:**
- `$table = 'pagos'`
- `$fillable`: `['tramite_id', 'monto', 'fecha_pago', 'metodo_pago', 'nro_comprobante', 'comprobante_path', 'observaciones', 'created_by']`
- `$casts`:
    - `fecha_pago` => `date`
    - `monto` => `decimal:2`

**Relaciones:**

```php
// El pago pertenece a un trámite
public function tramite()
{
    return $this->belongsTo(Tramite::class);
}

// Auditoría de creación
public function creador()
{
    return $this->belongsTo(User::class, 'created_by');
}
```

---

## 🎮 Controlador

### Controlador: `PagoController`

**Ubicación:** `app/Http/Controllers/PagoController.php`

Gestiona las operaciones CRUD de pagos, siempre en el contexto de un trámite padre.

**Métodos Principales:**
- `index(Tramite $tramite)`: Muestra la vista principal de pagos para un trámite específico.
- `list(Tramite $tramite)`: Endpoint AJAX que retorna la tabla de pagos realizados.
- `create(Tramite $tramite)`: Muestra el formulario de registro de pago.
    - **Lógica:** Debería calcular el `saldo_pendiente` (`tramite->monto_final - tramite->pagos->sum('monto')`) para sugerirlo en el formulario.
- `store(StorePagoRequest $request, Tramite $tramite)`:
    1. Valida los datos.
    2. Sube el archivo de comprobante si existe.
    3. Crea el registro.
    4. **Lógica de Negocio:** Verifica si con este pago se salda la deuda total para actualizar el estado del trámite (si aplica).
- `show(Tramite $tramite, Pago $pago)`: Muestra el detalle del pago y el comprobante.
- `destroy(Tramite $tramite, Pago $pago)`: Elimina el pago (SoftDelete) y revierte el saldo.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

Las rutas están anidadas bajo el recurso `tramites`.

```php
Route::prefix('tramites/{tramite}/pagos')->name('admin.tramites.pagos.')->group(function () {
    Route::get('/', [PagoController::class, 'index'])->name('index');
    Route::get('/ajax/list', [PagoController::class, 'list'])->name('ajax.list');
    Route::get('/create', [PagoController::class, 'create'])->name('create');
    Route::post('/', [PagoController::class, 'store'])->name('store');
    Route::get('/{pago}', [PagoController::class, 'show'])->name('show');
    Route::delete('/{pago}', [PagoController::class, 'destroy'])->name('destroy');
});
```

---

## 🔒 Policies y Permisos

### Policy: `PagoPolicy`

**Ubicación:** `app/Policies/PagoPolicy.php`

| Método | Permiso Voyager | Descripción |
|---|---|---|
| `viewAny` | `browse_pagos` | Ver listado de pagos del trámite. |
| `view` | `read_pagos` | Ver detalle del comprobante. |
| `create` | `add_pagos` | Registrar nuevo pago. |
| `delete` | `delete_pagos` | Anular/Eliminar pago. |

> **Nota:** Generalmente no se permite `edit/update` en pagos por razones de auditoría financiera. Si hay error, se elimina y se crea uno nuevo.

---

## ✅ Requests

### `StorePagoRequest`

**Ubicación:** `app/Http/Requests/StorePagoRequest.php`

**Reglas de Validación:**
- `monto`: `required|numeric|min:0.1`
    - *Validación condicional recomendada:* `max:saldo_pendiente` (para no pagar de más).
- `fecha_pago`: `required|date|before_or_equal:today`
- `metodo_pago`: `required|in:Efectivo,Depósito,Transferencia,QR`
- `nro_comprobante`: `nullable|string|max:50` (Requerido si no es Efectivo)
- `comprobante`: `nullable|file|mimes:pdf,jpg,png|max:2048`

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/tramites/pagos/`

- **`browse.blade.php`**: Contenedor principal. Muestra el resumen de la deuda (Total, Pagado, Saldo).
- **`list.blade.php`**: Tabla AJAX. Columnas: Fecha, Método, Referencia, Monto, Acciones.
- **`create.blade.php`**: Formulario. Muestra una alerta con el saldo pendiente actual.
- **`read.blade.php`**: Vista de detalle con previsualización del comprobante.

---

## 🔗 Integración con otros módulos

### 1. Módulo de Trámites
- El modelo `Tramite` tiene la relación `hasMany(Pago::class)`.
- **Cálculo de Saldos:**
  ```php
  // En Tramite.php
  public function getTotalPagadoAttribute() {
      return $this->pagos()->sum('monto');
  }

  public function getSaldoPendienteAttribute() {
      return $this->monto_final - $this->total_pagado;
  }
  ```

### 2. Dashboard
- Los pagos se utilizan para calcular la **Recaudación Diaria/Mensual** en el `DashboardController`.

---

## 📝 Guía para Desarrolladores

### Flujo de Registro
1. El usuario accede a `admin/tramites/{id}/pagos`.
2. El sistema verifica si `saldo_pendiente > 0`. Si es 0, oculta el botón de "Crear Pago".
3. Al guardar, el `PagoController` debe verificar que el trámite no esté anulado.

### Manejo de Archivos
Los comprobantes se guardan en `storage/app/public/pagos/{tramite_id}/`. Es buena práctica organizar las carpetas por ID de trámite para evitar directorios con miles de archivos.

---

## 🚨 Análisis de Calidad y Mejoras

A continuación se detallan posibles bugs, riesgos financieros y oportunidades de mejora detectadas.

### 🐛 Posibles Bugs / Riesgos Críticos

1.  **Sobrepago (Riesgo Alto):**
    -   **Problema:** Si no se valida en el backend (`StorePagoRequest` o Controller) que `monto <= saldo_pendiente`, un usuario podría registrar pagos que excedan la deuda total, generando inconsistencias contables.
    -   **Solución:** Implementar una regla de validación personalizada o chequeo en el controlador:
        ```php
        if ($request->monto > $tramite->saldo_pendiente) {
            return back()->withErrors(['monto' => 'El monto excede el saldo pendiente.']);
        }
        ```

2.  **Modificación de Trámites Pagados (Riesgo Medio):**
    -   **Problema:** Si un trámite ya tiene pagos registrados, ¿se permite editar los datos del trámite (ej. cambiar el avalúo)? Si se cambia el `monto_final` después de haber recibido pagos, el saldo pendiente será incorrecto o negativo.
    -   **Solución:** Bloquear la edición de campos sensibles en `TramiteController` si `$tramite->pagos()->exists()`.

3.  **Eliminación de Pagos Conciliados (Riesgo Medio):**
    -   **Problema:** El método `destroy` permite borrar pagos. Si el dinero ya ingresó a caja y se concilió, borrar el registro en el sistema crea un hueco financiero.
    -   **Solución:** Solo permitir borrar pagos del día actual o implementar un sistema de "Anulación de Pago" que mantenga el registro pero con monto 0 y estado "Anulado", requiriendo un motivo.

### 🚀 Mejoras y Optimizaciones

1.  **Automatización de Estado del Trámite:**
    -   **Mejora:** Usar un `PagoObserver` (created/deleted).
        -   Al crear pago: Si `total_pagado >= monto_final`, cambiar `tramite->estado = 'Pagado'`.
        -   Al borrar pago: Si `total_pagado < monto_final` y estado era 'Pagado', regresarlo a 'Pendiente' o 'En Proceso'.

2.  **Validación de Nro Comprobante Único:**
    -   **Mejora:** Evitar que se registre el mismo número de comprobante bancario dos veces (incluso en diferentes trámites) para prevenir fraudes o errores de digitación.
    -   `'nro_comprobante' => 'unique:pagos,nro_comprobante'`

    3.  **Reporte de Cierre de Caja:**
    -   **Mejora:** Crear un reporte específico que liste todos los pagos recibidos por el usuario actual (`created_by`) en el día, agrupados por método de pago, para facilitar el arqueo de caja.

4.  **Optimización de Consultas:**
    -   **Mejora:** En `PagoController@store`, se está haciendo una consulta `Pago::where('tramite_id', $tramite->id)->where('estado', 'Aplicado')->exists()` que podría optimizarse usando `$tramite->pagos()->where('estado', 'Aplicado')->exists()` para aprovechar la relación existente.

### ⚠️ Inconsistencias entre Documentación y Código Real

1.  **Discrepancia en Campos de BD:**
    -   **Ubicación:** `docs/dev/pagos.md:40-53` vs `database/migrations/2025_09_22_122826_create_pagos_table.php:14-29`
    -   **Problema:** La documentación menciona campos como `metodo_pago` (ENUM), `comprobante_path`, `observaciones`, `softDeletes`, pero la migración real tiene `qr_path`, `nro_operacion`, `conciliado_el`, `banco`, `estado`.
    -   **Impacto:** La documentación está desactualizada y no refleja la implementación real del sistema.

2.  **Métodos Faltantes en Modelo Tramite:**
    -   **Ubicación:** `docs/dev/pagos.md:183-192`
    -   **Problema:** La documentación menciona métodos `getTotalPagadoAttribute()` y `getSaldoPendienteAttribute()` en el modelo `Tramite`, pero estos NO existen en `app/Models/Tramite.php`.
    -   **Ubicación:** `app/Models/Tramite.php:101-104` (Solo existe la relación `pagos()`)
    -   **Impacto:** El sistema no tiene una forma centralizada de calcular el saldo pendiente, lo que lleva a cálculos duplicados o inconsistentes en diferentes controladores.

3.  **Campo `codigo_barras` No Implementado:**
    -   **Ubicación:** `app/Models/Pago.php:18`
    -   **Problema:** El campo `codigo_barras` está en el `$fillable` del modelo pero NO existe en la migración de la base de datos.
    -   **Impacto:** Si se intenta guardar este campo, se producirá un error de SQL.

4.  **Campo `qr_path` No en Fillable:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php:90`
    -   **Problema:** Se asigna `$pago->qr_path` en el controlador pero `qr_path` NO está en el `$fillable` del modelo `Pago` (líneas 14-25).
    -   **Impacto:** Aunque puede funcionar si se asigna antes de crear, es inconsistente con el patrón de Laravel.

### 🐛 Bugs Adicionales Detectados

5.  **Error Potencial con timestamp de fecha_pago:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php:89`
    -   **Problema:** `$pago->fecha_pago->timestamp` asume que `fecha_pago` siempre está definido. Si el request no lo incluye o es null, se producirá un error "Attempt to read property "timestamp" on null".
    -   **Solución:** Validar que `fecha_pago` existe antes de acceder a su timestamp, o usar el valor del request directamente.

6.  **Relación Incorrecta en Vista:**
    -   **Ubicación:** `resources/views/admin/tramites/pagos/read.blade.php:94`
    -   **Problema:** La vista intenta acceder a `$pago->user->name` pero el modelo define la relación como `creador()` que apunta a `User`.
    -   **Ubicación correcta:** `app/Models/Pago.php:39-42`
    -   **Solución:** Cambiar `$pago->user` por `$pago->creador` o `$pago->editor`.

7.  **No Se Elimina Archivo QR al Reversar Pago:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php:127-153`
    -   **Problema:** Al reversar un pago (método `destroy`), se cambia el estado a 'Reversado' pero NO se elimina el archivo QR generado del storage.
    -   **Impacto:** Acumulación de archivos huérfanos en el sistema de archivos.

8.  **Falta Validación de Estado del Trámite:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php:64-124`
    -   **Problema:** Se valida que el trámite no esté 'Pagado' (línea 54) pero NO se valida si está 'Finalizado' o 'Anulado'.
    -   **Impacto:** Se podrían registrar pagos para trámites ya finalizados o anulados, lo cual es incorrecto desde el punto de vista de negocio.

9.  **Ausencia de Validación de Unicidad en Nro Operación:**
    -   **Ubicación:** `app/Http/Requests/StorePagoRequest.php:20`
    -   **Problema:** `nro_operacion` no tiene validación de unicidad, permitiendo registrar el mismo número de operación en múltiples pagos.
    -   **Impacto:** Riesgo de duplicación de pagos y dificultad para auditorías bancarias.

10. **Manejo de Errores Incompleto en Generación de QR:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php:112`
    -   **Problema:** Si la librería `QrCode` falla o no está instalada, el error es capturado genéricamente pero no hay manejo específico para este caso.
    -   **Impacto:** Mensajes de error confusos para el usuario.

11. **Falta de Validación de Sobrepago:**
    -   **Ubicación:** `app/Http/Requests/StorePagoRequest.php:19`
    -   **Problema:** No se valida que el `monto` del pago no exceda el `monto_final` del trámite.
    -   **Impacto:** Posibilidad de registrar pagos mayores a la deuda, causando inconsistencias contables.

12. **No Se Calcula `conciliado_el`:**
    -   **Ubicación:** `database/migrations/2025_09_22_122826_create_pagos_table.php:21`
    -   **Problema:** El campo `conciliado_el` existe en la BD pero nunca se asigna en el código actual.
    -   **Ubicación:** Se muestra en la vista pero nunca se actualiza (líneas 80-89 de `read.blade.php`)
    -   **Impacto:** Funcionalidad de conciliación no implementada.

### 🏗️ Faltas de Implementación

13. **Sin Funcionalidad de Conciliación:**
    -   **Ubicación:** `database/migrations/2025_09_22_122826_create_pagos_table.php:21`
    -   **Falta:** No hay ningún método o proceso para marcar un pago como "conciliado" (actualizar el campo `conciliado_el`).
    -   **Sugerencia:** Implementar un método `conciliar(Pago $pago)` en el controlador o un comando para conciliación masiva.

14. **Sin Migración de Pagos Parciales:**
    -   **Falta:** No hay soporte para pagos parciales. El código asume que un pago cubre el `monto_final` completo (línea 93 de `PagoController.php`).
    -   **Sugerencia:** Implementar lógica para permitir múltiples pagos parciales hasta completar el saldo.

15. **Sin Auditoría Detallada:**
    -   **Falta:** No hay logs de auditoría que registren quién modificó un pago, cuándo y qué campos cambiaron.
    -   **Sugerencia:** Implementar un paquete como `spatie/laravel-activitylog` o crear una tabla de auditoría personalizada.

16. **Sin Validación de Método de Pago:**
    -   **Ubicación:** `app/Http/Requests/StorePagoRequest.php:21`
    -   **Falta:** La migración original mencionaba un campo `metodo_pago` con ENUM, pero no existe validación para esto en el código actual.
    -   **Sugerencia:** Definir claramente qué métodos de pago son válidos y validarlos.

17. **Sin Gestión de Comprobantes (Archivos):**
    -   **Falta:** La documentación menciona `comprobante_path` para almacenar archivos PDF/imágenes de comprobantes bancarios, pero no está implementado en el código actual.
    -   **Ubicación:** `docs/dev/pagos.md:48`
    -   **Sugerencia:** Implementar upload de archivos de comprobantes con validación de tipos y tamaño.

### 🔧 Optimizaciones Recomendadas

18. **Centralizar Cálculo de Saldos:**
    -   **Ubicación:** `app/Models/Tramite.php`
    -   **Mejora:** Implementar los atributos de acceso `total_pagado` y `saldo_pendiente` como menciona la documentación.
    -   **Código sugerido:**
        ```php
        public function getTotalPagadoAttribute()
        {
            return $this->pagos()->where('estado', 'Aplicado')->sum('monto');
        }

        public function getSaldoPendienteAttribute()
        {
            return max(0, $this->monto_final - $this->total_pagado);
        }
        ```

19. **Optimizar Consultas con Eager Loading:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php:33`
    -   **Mejora:** En el método `list`, cargar la relación `creador` si se va a mostrar en la tabla para evitar N+1 queries.
    -   **Código:** `Pago::with('creador')->where('tramite_id', $tramite->id)`

20. **Implementar Observer para Estado del Trámite:**
    -   **Mejora:** Mover la lógica de actualización del estado del trámite desde `PagoController` (líneas 93-99) a un `PagoObserver`.
    -   **Beneficio:** Código más limpio y siguiendo el patrón de Laravel.

21. **Añadir Índices en Base de Datos:**
    -   **Ubicación:** `database/migrations/2025_09_22_122826_create_pagos_table.php`
    -   **Mejora:** Añadir índices para mejorar el rendimiento en búsquedas frecuentes.
    -   **Índices sugeridos:**
        - `index(['tramite_id', 'estado'])` - Para consultas de pagos por trámite y estado
        - `index(['fecha_pago', 'estado'])` - Para reportes de recaudación por fecha
        - `index('created_by')` - Para reportes de cierre de caja

22. **Implementar Caching para Saldos:**
    -   **Mejora:** Usar caché para el cálculo de `total_pagado` y `saldo_pendiente` que se calculan frecuentemente en las vistas.
    -   **Beneficio:** Reducir la carga en la base de datos.

23. **Validación Dinámica en StorePagoRequest:**
    -   **Ubicación:** `app/Http/Requests/StorePagoRequest.php`
    -   **Mejora:** Añadir validación condicional para `banco` y `nro_operacion` (requeridos cuando no es efectivo).

### 🔐 Consideraciones de Seguridad

24. **Falta Validación de CSRF en AJAX:**
    -   **Ubicación:** `resources/views/admin/tramites/pagos/browse.blade.php:78`
    -   **Riesgo:** Las llamadas AJAX deben incluir el token CSRF para prevenir ataques.
    -   **Estado:** Parece manejado por Laravel automáticamente, pero vale la pena verificar.

25. **Sin Rate Limiting:**
    -   **Riesgo:** No hay límite de solicitudes para crear pagos, lo que podría permitir abusos o ataques de fuerza bruta.
    -   **Sugerencia:** Implementar rate limiting en las rutas de pagos.

26. **Validación de Fecha Futura:**
    -   **Ubicación:** `app/Http/Requests/StorePagoRequest.php:18`
    -   **Buenas prácticas:** Ya existe validación `before_or_equal:now`, lo cual es correcto para evitar pagos con fechas futuras.

### 📊 Sugerencias de Funcionalidades Adicionales

27. **Exportar Pagos a Excel/PDF:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php`
    -   **Sugerencia:** Añadir métodos para exportar el listado de pagos de un trámite a formato Excel o PDF.

28. **Notificaciones de Pagos:**
    -   **Sugerencia:** Implementar notificaciones (email o en el sistema) cuando un pago es registrado o conciliado.

29. **Validación de Banco:**
    -   **Ubicación:** `app/Http/Controllers/PagoController.php:59`
    -   **Sugerencia:** Mover la lista de bancos a una tabla de base de datos en lugar de estar harcodeada en el controlador para facilitar su mantenimiento.

30. **Historial de Cambios de Estado de Pago:**
    -   **Sugerencia:** Implementar una tabla `pago_historial` que registre todos los cambios de estado de un pago con auditoría.

---

## 📍 Resumen de Ubicaciones Clave

| Componente | Ubicación | Notas |
|------------|-----------|-------|
| Migración BD | `database/migrations/2025_09_22_122826_create_pagos_table.php` | Campos reales implementados |
| Modelo | `app/Models/Pago.php` | Falta `qr_path` en fillable, tiene `codigo_barras` sin migración |
| Controlador | `app/Http/Controllers/PagoController.php` | Lógica de estado del trámite manual |
| Request | `app/Http/Requests/StorePagoRequest.php` | Faltan validaciones críticas |
| Policy | `app/Policies/PagoPolicy.php` | Permisos implementados correctamente |
| Observer | `app/Observers/PagoObserver.php` | Solo maneja caché del dashboard |
| Vistas | `resources/views/admin/tramites/pagos/` | Bug en relación `$pago->user` |
| Modelo Tramite | `app/Models/Tramite.php` | Falta métodos de cálculo de saldos |
| Dashboard Service | `app/Services/DashboardService.php` | Usa pagos para reportes |
| Cache Invalidator | `app/Services/DashboardCacheInvalidator.php` | Invalidación correcta |
| Rutas | `routes/web.php:230-237` | Rutas anidadas correctamente |

```

<!--
[PROMPT_SUGGESTION]Genera el código para el PagoObserver que automatice el cambio de estado del Trámite a 'Pagado' cuando el saldo llegue a cero.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Crea una regla de validación personalizada en Laravel llamada 'MaxSaldoPendiente' para usar en el StorePagoRequest y evitar sobrepagos.[/PROMPT_SUGGESTION]
