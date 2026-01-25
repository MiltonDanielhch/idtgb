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
11. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

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
| `nro_operacion` | VARCHAR(50) | NULLABLE | Número de operación o referencia bancaria. |
| `banco` | VARCHAR(100) | NULLABLE | Banco donde se realizó el pago. |
| `qr_path` | VARCHAR(255) | NULLABLE | Ruta al archivo QR generado. |
| `conciliado_el` | TIMESTAMP | NULLABLE | Fecha de conciliación del pago. |
| `estado` | ENUM | ['Pendiente', 'Aplicado', 'Reversado'] | Estado del pago. |
| `created_by` | BIGINT | FK | Usuario que registró el pago. |
| `updated_by` | BIGINT | FK | Usuario que actualizó el pago. |
| `created_at` | TIMESTAMP | | Fecha de registro. |
| `updated_at` | TIMESTAMP | | Fecha de actualización. |
| `deleted_at` | TIMESTAMP | NULLABLE | Fecha de borrado lógico (SoftDeletes). |

**Relaciones:**
- `tramite_id` referencia a `tramites(id)` con `onDelete('cascade')` (generalmente).
- `created_by` referencia a `users(id)`.
- `updated_by` referencia a `users(id)`.

---

## 🧩 Modelo

### Modelo: `Pago`

**Ubicación:** `app/Models/Pago.php`

**Atributos:**
- `$table = 'pagos'`
- `$fillable`: `['tramite_id', 'fecha_pago', 'monto', 'codigo_barras', 'nro_operacion', 'conciliado_el', 'banco', 'estado', 'created_by', 'updated_by']`
- `$casts`:
    - `fecha_pago` => `datetime`
    - `conciliado_el` => `datetime`
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

// Auditoría de actualización
public function editor()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

**Métodos Helper:**
```php
public function estaAplicado(): bool
{
    return $this->estado === 'Aplicado';
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
    - **Lógica:** Valida que el trámite no esté en estado 'Pagado' antes de mostrar el formulario.
- `store(StorePagoRequest $request, Tramite $tramite)`:
    1. Valida los datos.
    2. Verifica que no exista un pago aplicado para el trámite.
    3. Inicia transacción de base de datos.
    4. Genera nombre único para el archivo QR usando timestamp y hash aleatorio.
    5. Crea el registro con estado 'Pendiente'.
    6. Si el monto del pago >= monto_final del trámite, actualiza estado del trámite a 'Pagado' y del pago a 'Aplicado' usando `withoutEvents()`.
    7. Genera y guarda el archivo QR.
    8. Commit de la transacción.
- `show(Tramite $tramite, Pago $pago)`: Muestra el detalle del pago y el comprobante.
- `destroy(Tramite $tramite, Pago $pago)`: Elimina el pago (SoftDelete) y revierte el saldo.
    - Valida que el pago no esté en estado 'Aplicado'.
    - Actualiza estado del pago a 'Reversado' y del trámite a 'Borrador'.
    - Usa transacción de base de datos.

**Lógica Destacada:**
- **Validación de Pagos Aplicados:** Verifica que no exista un pago aplicado antes de crear uno nuevo.
- **Automatización de Estados:** Cambia automáticamente el estado del trámite a 'Pagado' y del pago a 'Aplicado' cuando el pago cubre el monto final.
- **Uso de withoutEvents():** Evita bucles infinitos al actualizar el estado del trámite.
- **Generación de QR Única:** Usa timestamp y hash aleatorio para evitar colisiones de nombres.
- **Manejo de Transacciones:** Envuelve operaciones en transacciones para garantizar atomicidad.
- **Protección contra Reversión:** Impide reversar pagos en estado 'Aplicado'.

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
- `nro_operacion`: `nullable|string|max:50` (Requerido si no es Efectivo)
- `banco`: `nullable|string|max:100`

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/tramites/pagos/`

- **`browse.blade.php`**: Contenedor principal. Muestra el resumen de la deuda (Total, Pagado, Saldo).
- **`list.blade.php`**: Tabla AJAX. Columnas: Fecha, Método, Referencia, Monto, Estado, Acciones.
- **`create.blade.php`**: Formulario. Muestra una alerta con el saldo pendiente actual.
- **`read.blade.php`**: Vista de detalle con previsualización del comprobante y código QR.

---

## 🔗 Integración con otros módulos

### 1. Módulo de Trámites
- El modelo `Tramite` tiene la relación `hasMany(Pago::class)`.
- **Cálculo de Saldos:**
  ```php
  // En Tramite.php (sugerido para implementación futura)
  public function getTotalPagadoAttribute() {
      return $this->pagos()->where('estado', 'Aplicado')->sum('monto');
  }

  public function getSaldoPendienteAttribute() {
      return max(0, $this->monto_final - $this->total_pagado);
  }
  ```

### 2. Dashboard
- Los pagos se utilizan para calcular la **Recaudación Diaria/Mensual** en el `DashboardController`.

### 3. Observers
- `PagoObserver`: Maneja la invalidación de caché del dashboard cuando se crea o elimina un pago.

---

## 📝 Guía para Desarrolladores

### Flujo de Registro
1. El usuario accede a `admin/tramites/{id}/pagos`.
2. El sistema verifica si `saldo_pendiente > 0`. Si es 0, oculta el botón de "Crear Pago".
3. Al guardar, el `PagoController` verifica que el trámite no esté en estado 'Pagado'.
4. Si el monto del pago cubre el monto final, el sistema actualiza automáticamente el estado del trámite a 'Pagado'.

### Manejo de Archivos
Los códigos QR se guardan en `storage/app/public/tramites/{tramite_id}/pagos/`. Es buena práctica organizar las carpetas por ID de trámite para evitar directorios con miles de archivos.

### Puntos Clave a Recordar
- **Validación de Estado:** Siempre verificar el estado del trámite antes de permitir crear pagos.
- **withoutEvents():** Usar al actualizar el estado del trámite para evitar bucles con observers.
- **Transacciones:** Envolver operaciones en transacciones para garantizar atomicidad.
- **QR Único:** Usar timestamp + hash aleatorio para evitar colisiones de nombres.
- **Protección de Pagos Aplicados:** No permitir reversión de pagos en estado 'Aplicado'.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (20 de enero de 2026) ✅

Todos los bugs identificados en el análisis original han sido corregidos exitosamente. El módulo de Pagos ahora cuenta con:

- ✅ Validación para evitar pagos aplicados duplicados
- ✅ Uso de withoutEvents() para evitar bucles en actualizaciones
- ✅ Automatización de estado del trámite y del pago
- ✅ Generación de QR único con timestamp y hash aleatorio
- ✅ Validación de estado del trámite al crear pago
- ✅ Validación de reversión de pagos aplicados
- ✅ Manejo de transacciones DB en store() y destroy()
- ✅ Campos de auditoría created_by y updated_by

### 🐛 Bugs Corregidos (8/8) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Pagos aplicados duplicados | ✅ Corregido | `PagoController.php:68-72` |
| 2 | Bucle infinito al actualizar estado del trámite | ✅ Corregido | `PagoController.php:96-98` |
| 3 | Estado automático del trámite y del pago | ✅ Corregido | `PagoController.php:93-100` |
| 4 | Generación de QR único | ✅ Corregido | `PagoController.php:86-90` |
| 5 | Contenido del QR con información completa | ✅ Corregido | `PagoController.php:106-111` |
| 6 | Validación de estado al crear pago | ✅ Corregido | `PagoController.php:54-57` |
| 7 | Validación de reversión de pagos aplicados | ✅ Corregido | `PagoController.php:137-139` |
| 8 | Manejo de transacciones en create y destroy | ✅ Corregido | `PagoController.php:74-124, 135-153` |

### 🚀 Mejoras Implementadas ✅

- ✅ **Validación de Pagos Aplicados:** Previene que se registren múltiples pagos aplicados para el mismo trámite.
- ✅ **Evitación de Bucles:** Uso de `withoutEvents()` evita interacciones no deseadas con observers.
- ✅ **Automatización de Estados:** Cambio automático de estado del trámite a 'Pagado' y del pago a 'Aplicado' cuando corresponde.
- ✅ **Generación Segura de QR:** Nombres de archivo únicos usando timestamp + hash aleatorio.
- ✅ **Validación de Estado:** Impide crear pagos para trámites ya pagados.
- ✅ **Protección de Pagos Aplicados:** Impide reversar pagos en estado 'Aplicado'.
- ✅ **Transacciones Robustas:** Uso de transacciones garantiza atomicidad de operaciones.
- ✅ **Auditoría Completa:** Registra `created_by` y `updated_by` en todos los pagos.

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **ALTO** | Validación de sobrepago | Implementar regla `max:saldo_pendiente` en StorePagoRequest |
| **ALTO** | Validación de unicidad en nro_operación | Evitar registrar el mismo número de operación en múltiples pagos |
| **MEDIO** | Implementar funcionalidad de conciliación | Método para marcar pagos como conciliados (campo `conciliado_el`) |
| **MEDIO** | Añadir `total_pagado` y `saldo_pendiente` en modelo Tramite | Atributos de acceso para cálculos centralizados |
| **MEDIO** | Validación de unicidad de nro_operación | Evitar duplicación de números de operación bancaria |
| **BAJO** | Eliminar archivo QR al reversar pago | Limpieza de archivos huérfanos en el sistema de archivos |
| **BAJO** | Reporte de cierre de caja | Listado de pagos del día agrupados por método de pago |
| **BAJO** | Eager loading en list() | Cargar relación `creador` para evitar N+1 queries |
| **BAJO** | Implementar Observer para estado del trámite | Mover lógica de actualización desde controlador |
| **BAJO** | Añadir índices en base de datos | Mejorar rendimiento en búsquedas frecuentes |
| **BAJO** | Implementar caching para saldos | Reducir carga en base de datos |
| **BAJO** | Validación dinámica en StorePagoRequest | Añadir validación condicional para `banco` y `nro_operacion` |
| **BAJO** | Exportar pagos a Excel/PDF | Funcionalidad para exportar listados |
| **BAJO** | Notificaciones de pagos | Email o notificaciones en el sistema |
| **BAJO** | Validación de Banco en BD | Mover lista de bancos a tabla de base de datos |
| **BAJO** | Historial de cambios de estado | Tabla `pago_historial` con auditoría |

### 📝 Historial de Cambios

### v2.0.0 (20 de enero de 2026)
**Correcciones Completadas (8/8):**
- ✅ Bug #1: Validación para evitar pagos aplicados duplicados - Agregado chequeo en `store()`
- ✅ Bug #2: Bucle infinito al actualizar estado del trámite - Implementado `withoutEvents()`
- ✅ Bug #3: Estado automático del trámite y del pago - Implementada lógica automática
- ✅ Bug #4: Generación de QR único - Usando timestamp + hash aleatorio
- ✅ Bug #5: Contenido del QR con información completa - JSON con tramite, pago_id, monto, fecha
- ✅ Bug #6: Validación de estado al crear pago - Verifica que trámite no esté 'Pagado'
- ✅ Bug #7: Validación de reversión de pagos aplicados - Impide reversión de 'Aplicado'
- ✅ Bug #8: Manejo de transacciones en create y destroy - DB::beginTransaction() con try-catch

**Archivos Modificados:**
- `app/Http/Controllers/PagoController.php` - Agregadas validaciones, transacciones, automatización de estados
- `app/Models/Pago.php` - Agregada relación `editor()` y método `estaAplicado()`
- `app/Observers/PagoObserver.php` - Manejo de caché del dashboard
