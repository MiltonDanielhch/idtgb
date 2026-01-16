# Documentación Técnica - Módulo de UFVs

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

El módulo de **UFVs (Unidad de Fomento de Vivienda)** gestiona el historial diario de valores de este índice económico boliviano. Su función principal es proveer la tasa de cambio oficial para realizar cálculos tributarios, actualizaciones de deuda y mantenimiento de valor conforme a la normativa vigente.

### Propósito
- Registrar el valor de la UFV para cada día del año.
- Permitir la carga masiva de valores históricos mediante archivos CSV/TXT.
- Servir como fuente de verdad para el `IdtgbCalculator`.

### Importancia en el Sistema ITGB
Sin datos actualizados de UFV, el sistema **no puede calcular impuestos** correctamente. Si un usuario intenta registrar un trámite con fecha de hoy y no existe la UFV del día, el sistema bloqueará la operación.

---

## 🗄️ Base de Datos

### Tabla: `ufvs`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `fecha` | DATE | UNIQUE, NOT NULL | Fecha de vigencia del valor. |
| `valor` | DECIMAL(10,5) | NOT NULL | Valor de la UFV (5 decimales de precisión). |
| `created_by` | BIGINT | FK | Usuario que registró el valor. |
| `updated_by` | BIGINT | FK | Usuario que actualizó el valor. |
| `created_at` | TIMESTAMP | | Fecha de registro. |
| `updated_at` | TIMESTAMP | | Fecha de actualización. |

**Índices:**
- `fecha`: Índice único para evitar duplicidad de valores para un mismo día.

---

## 🧩 Modelo

### Modelo: `Ufv`

**Ubicación:** `app/Models/Ufv.php`

**Atributos:**
- `$table = 'ufvs'`
- `$fillable`: `['fecha', 'valor', 'created_by', 'updated_by']`
- `$casts`:
    - `fecha` => `date`
    - `valor` => `decimal:5`

---

## 🎮 Controlador

### Controlador: `UfvController`

**Ubicación:** `app/Http/Controllers/UfvController.php`

Gestiona el ABM (Alta, Baja, Modificación) y la importación masiva.

**Métodos Principales:**
- `index()`: Muestra la vista principal.
- `list()`: Endpoint AJAX para el listado paginado. Permite filtrar por fecha.
- `create()`: Muestra el formulario de carga manual unitaria.
- `store(Request $request)`: Guarda un registro individual. Valida que la fecha no sea futura y sea única.
- `import(Request $request)`: **Lógica Compleja**. Procesa archivos CSV/TXT para carga masiva.
    - **Detección de Delimitador:** Lee la primera línea para decidir si usar `,` o `;`.
    - **Detección de Fecha:** Intenta parsear `d/m/Y` y luego `Y-m-d`.
    - **Limpieza de Valor:** Elimina espacios, pipes `|` y convierte comas decimales en puntos.
    - **Validación de Duplicados:** Carga todas las fechas existentes en memoria para evitar consultas SQL por cada fila.
    - **Inserción Masiva:** Usa `Ufv::insert($array)` para alto rendimiento dentro de una transacción.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::prefix('ufvs')->name('admin.ufvs.')->group(function () {
    Route::get('/', [UfvController::class, 'index'])->name('index');
    Route::get('/list', [UfvController::class, 'list'])->name('ajax.list');
    Route::get('/create', [UfvController::class, 'create'])->name('create');
    Route::post('/', [UfvController::class, 'store'])->name('store');
    Route::post('/import', [UfvController::class, 'import'])->name('import'); // Ruta especial para CSV
    Route::get('/{ufv}', [UfvController::class, 'show'])->name('show');
});
```

---

## 🔒 Policies y Permisos

### Policy: `UfvPolicy` (Inferido)

| Método | Permiso Voyager | Descripción |
|---|---|---|
| `viewAny` | `browse_ufvs` | Ver listado. |
| `view` | `read_ufvs` | Ver detalle. |
| `create` | `add_ufvs` | Crear o Importar UFVs. |

---

## ✅ Requests

No utiliza clases `FormRequest` separadas. La validación se realiza "inline" en el controlador.

**Validación en `store`:**
```php
$request->validate([
    'fecha' => 'required|date|before_or_equal:today|unique:ufvs,fecha',
    'valor' => 'required|numeric|min:0.00001',
]);
```

**Validación en `import`:**
```php
$request->validate([
    'archivo' => 'required|file|mimes:csv,txt|max:2048',
]);
```

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/ufvs/`

- **`browse.blade.php`**: Contenedor principal. Incluye el formulario de **Importación Masiva** (input file) y el botón de "Crear Manual".
- **`list.blade.php`**: Tabla AJAX. Muestra Fecha y Valor.
- **`create.blade.php`**: Formulario simple para carga unitaria.
- **`read.blade.php`**: Vista de detalle.

---

## 🔗 Integración con otros módulos

### 1. Servicio de Cálculo (`IdtgbCalculator`)
- Este es el consumidor principal. Al calcular un impuesto, el servicio busca la UFV de la `fecha_transmision` y la UFV de la `fecha_pago` (o fecha actual) para determinar el mantenimiento de valor.
- **Lógica típica:**
  ```php
  $ufvInicial = Ufv::where('fecha', $fechaHecho)->value('valor');
  $ufvFinal = Ufv::where('fecha', $fechaPago)->value('valor');
  ```

### 2. Trámites
- Al crear un trámite, se valida que existan las UFVs necesarias. Si no existen, el sistema suele impedir el registro o usar la última disponible (dependiendo de la regla de negocio, aunque lo estricto es requerir la del día).

---

## 📝 Guía para Desarrolladores

### Formato de Archivo de Importación
El importador es flexible, pero el formato ideal es **CSV** (delimitado por coma o punto y coma) sin cabeceras o con cabeceras ignoradas:

```csv
2025-01-01;2.50123
2025-01-02;2.50130
...
```

También soporta formato de fecha `dd/mm/yyyy`.

### Obtener UFV actual
```php
$valorHoy = Ufv::where('fecha', now()->format('Y-m-d'))->value('valor');
if (!$valorHoy) {
    // Manejar error: UFV no registrada
}
```

---

## 🚨 Análisis de Calidad y Mejoras

A continuación se detallan posibles bugs, riesgos y oportunidades de mejora detectadas en el código del `UfvController`.

### 🐛 Posibles Bugs / Riesgos

1.  **Limpieza de Valor Numérico (Riesgo Medio):**
    -   **Código:** `$valor = str_replace([' ', '|', ','], ['', '', '.'], $valor);`
    -   **Problema:** Esta lógica asume que la coma `,` siempre es un separador decimal. Si un usuario sube un CSV donde el separador de miles es coma (ej: `2,300.50`), esto se convertirá en `2.300.50`, lo cual no es numérico y fallará o se truncará.
    -   **Solución:** Implementar una limpieza más robusta o exigir un formato estricto (punto para decimales, sin separador de miles).

2.  **Validación Inline (Deuda Técnica):**
    -   **Problema:** La validación está dentro del controlador. Si se crea una API para cargar UFVs externamente, se tendrá que duplicar la lógica.
    -   **Solución:** Mover a `StoreUfvRequest` y `ImportUfvRequest`.

3.  **Inserción Masiva sin Timestamps (Riesgo Bajo):**
    -   **Observación:** `Ufv::insert($toInsert)` es eficiente, pero no dispara eventos de Eloquent (Observers). Si en el futuro se agrega un log de auditoría vía Observer, la importación masiva no quedará registrada detalle a detalle.
    -   **Nota:** Actualmente el código agrega manualmente `created_at` y `updated_at` al array, lo cual es correcto para `insert()`.

### 🚀 Mejoras y Optimizaciones

1.  **Automatización con API del BCB:**
    -   **Mejora:** El Banco Central de Bolivia (BCB) suele publicar estos datos. Se podría crear un **Comando Artisan** (`ufv:fetch`) que haga *scraping* o consuma un servicio web oficial para cargar la UFV del día automáticamente cada mañana, eliminando la carga manual.

2.  **Validación de Huecos (Gaps):**
    -   **Mejora:** El sistema no alerta si faltan días. Sería útil un widget en el Dashboard que diga "Faltan UFVs para los siguientes días del mes actual".

3.  **Manejo de Errores en Importación:**
    -   **Mejora:** Actualmente, si hay un error en una fila (ej. fecha inválida), esa fila se salta y se agregan al array `$errors`. Al final se muestra un mensaje `warning`. Sería ideal generar un archivo de "filas rechazadas" descargable para que el usuario sepa exactamente qué corregir sin tener que buscar en el mensaje de alerta.
```

<!--
[PROMPT_SUGGESTION]Crea el código para el comando de consola 'ufv:fetch' que mencionaste en las mejoras, simulando una conexión a un servicio externo para obtener la UFV del día.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Genera un Test Unitario para el método 'import' del UfvController, probando específicamente la detección de delimitadores y formatos de fecha.[/PROMPT_SUGGESTION]
