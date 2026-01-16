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
```

<!--
[PROMPT_SUGGESTION]Genera el código para el PagoObserver que automatice el cambio de estado del Trámite a 'Pagado' cuando el saldo llegue a cero.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Crea una regla de validación personalizada en Laravel llamada 'MaxSaldoPendiente' para usar en el StorePagoRequest y evitar sobrepagos.[/PROMPT_SUGGESTION]
