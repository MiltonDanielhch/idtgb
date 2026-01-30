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
11. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

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

### Migraciones
- `create_ufvs_table.php`
- `add_audit_fields_to_ufvs_table.php`

**Tabla:** `ufvs`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `fecha` | DATE | UNIQUE, NOT NULL | Fecha de vigencia del valor. |
| `valor` | DECIMAL(8,5) | NOT NULL | Valor de la UFV (5 decimales de precisión). |
| `created_at` | TIMESTAMP | | Fecha de registro. |
| `updated_at` | TIMESTAMP | | Fecha de actualización. |
| `created_by` | BIGINT | FK, NULLABLE | Usuario que creó el registro. |
| `updated_by` | BIGINT | FK, NULLABLE | Usuario que actualizó el registro. |


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

**Métodos Estáticos:**
```php
/**
 * Obtiene el valor de la UFV para una fecha específica.
 * Implementa lógica de búsqueda hacia atrás si la fecha exacta no existe.
 */
public static function getValorEnFecha($fecha)
{
    $ufv = self::where('fecha', '<=', $fecha)
               ->orderBy('fecha', 'desc')
               ->first();

    if (!$ufv) {
        return 1.00000;
    }

    return (float) $ufv->valor;
}
```

---

## 🎮 Controlador

### Controlador: `UfvController`

**Ubicación:** `app/Http/Controllers/UfvController.php`

Gestiona el ABM (Alta, Baja, Modificación) y la importación masiva.

**Métodos Principales:**
- `index()`: Muestra la vista principal.
- `list()`: Endpoint AJAX para el listado paginado. Permite filtrar por fecha.
- `create()`: Muestra el formulario de carga manual unitaria.
- `store(Request $request)`: Guarda un registro individual.
- `show(Ufv $ufv)`: Muestra el detalle de una UFV.
- `edit(Ufv $ufv)`: Muestra el formulario de edición.
- `update(Request $request, Ufv $ufv)`: Actualiza un registro existente.
- `destroy(Ufv $ufv)`: Elimina un registro.
- `import(Request $request)`: **Lógica Compleja**. Procesa archivos CSV/TXT para carga masiva.
    - **Detección de Delimitador:** Lee la primera línea para decidir si usar `,` o `;`.
    - **Detección de Fecha:** Intenta parsear `d/m/Y` y luego `Y-m-d`.
    - **Validación de Fecha Futura:** Previene la importación de valores con fecha futura.
    - **Limpieza de Valor:** Maneja separadores de miles y decimales.
    - **Validación de Duplicados:** Carga todas las fechas existentes en memoria para evitar consultas SQL por cada fila.
    - **Auditoría:** Asigna `created_by` y `updated_by`.
    - **Inserción Masiva:** Usa `Ufv::insert($array)` para alto rendimiento.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::prefix('ufvs')->name('admin.ufvs.')->group(function () {
    Route::get('/', [UfvController::class, 'index'])->name('index');
    Route::get('/list', [UfvController::class, 'list'])->name('ajax.list');
    Route::get('/create', [UfvController::class, 'create'])->name('create');
    Route::post('/', [UfvController::class, 'store'])->name('store');
    Route::post('/import', [UfvController::class, 'import'])->name('import');
    Route::get('/{ufv}', [UfvController::class, 'show'])->name('show');
    Route::get('/{ufv}/edit', [UfvController::class, 'edit'])->name('edit');
    Route::put('/{ufv}', [UfvController::class, 'update'])->name('update');
    Route::delete('/{ufv}', [UfvController::class, 'destroy'])->name('destroy');
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
| `update` | `edit_ufvs` | Editar una UFV. |
| `delete` | `delete_ufvs` | Eliminar una UFV. |

---

## ✅ Requests

No utiliza clases `FormRequest` separadas. La validación se realiza "inline" en el controlador.

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/ufvs/`

- **`browse.blade.php`**: Contenedor principal.
- **`list.blade.php`**: Tabla AJAX.
- **`create.blade.php`**: Formulario simple para carga unitaria.
- **`edit.blade.php`**: Formulario para edición.
- **`read.blade.php`**: Vista de detalle.

---

## 🔗 Integración con otros módulos

El consumidor principal es el servicio `IdtgbCalculator` que utiliza `Ufv::getValorEnFecha()` para los cálculos de mantenimiento de valor.

---

## 📝 Guía para Desarrolladores

### Formato de Archivo de Importación
El importador es flexible, pero el formato ideal es **CSV** (delimitado por coma o punto y coma) sin cabeceras.

```csv
2025-01-01;2,50123
2025-01-02;2,50130
...
```

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.1.0 (29 de enero de 2026) ✅

Se han corregido todos los bugs reportados y se han implementado mejoras clave de funcionalidad y robustez.

### 🐛 Bugs Corregidos

| # | Bug | Severidad | Estado |
|---|-----|-----------|--------|
| 1 | Namespace duplicado en UfvController | Baja | ✅ Corregido |
| 2 | Campos `created_by`, `updated_by` no en fillable | Media | ✅ Corregido |
| 3 | Campos de auditoría comentados en import() | Alta | ✅ Corregido |
| 4 | Mutación inesperada de fecha en UfvApiSeeder | Media | ✅ Corregido |
| 5 | Conversión inadecuada de separadores decimales | Alta | ✅ Corregido |
| 6 | Inconsistencia en cast del modelo (`float` vs `decimal:5`) | Baja | ✅ Corregido |
| 7 | Faltan métodos edit(), update(), destroy() | Media | ✅ Corregido |
| 8 | No hay validación de fecha futura en import() | Media | ✅ Corregido |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Detección automática de delimitador | El importador detecta `,` o `;` automáticamente. |
| 2 | Detección inteligente de formato de fecha | Soporta `DD/MM/YYYY` y `YYYY-MM-DD`. |
| 3 | Inserción masiva optimizada | Usa `Ufv::insert()` para alto rendimiento. |
| 4 | Validación de duplicados en memoria | Evita consultas repetitivas a la BD durante la importación. |
| 5 | Método `getValorEnFecha()` | Implementa búsqueda hacia atrás si la fecha exacta no existe. |
| 6 | **Implementación de campos de auditoría** | Se agregaron `created_by` y `updated_by` a la tabla `ufvs`. |
| 7 | **CRUD completo para UFVs** | Se añadieron los métodos `edit`, `update` y `destroy`. |
| 8 | **Validación de fecha futura en importación** | El sistema ahora rechaza registros de UFV con fechas futuras. |
| 9 | **Corrección de manejo de decimales** | Se mejoró la lógica para interpretar correctamente valores con comas decimales. |
| 10 | **Modelo y controlador robustos** | Se solucionaron inconsistencias y malas prácticas en el código. |


### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **MEDIO** | Request Classes separados | Crear `StoreUfvRequest` y `ImportUfvRequest` para centralizar la validación. |
| **MEDIO** | Scopes en el modelo | Crear scopes como `scopeHastaFecha()`, `scopeEnRango()` para consultas más limpias. |
| **MEDIO** | Observer para auditoría automática | Usar un `Observer` para registrar `created_by` y `updated_by` automáticamente. |
| **BAJO** | Sistema de colas para importación | Usar `Jobs` para procesar archivos de importación grandes en segundo plano. |
| **BAJO** | Comando Artisan para fetch automático | `ufv:fetch` para obtener UFVs desde una API externa (ej. BCB). |
| **BAJO** | Tests unitarios y de integración | Añadir cobertura de tests para asegurar la fiabilidad del módulo. |

### 📝 Historial de Cambios

### v1.1.0 (29 de enero de 2026)
**Estado: Estable**

**Cambios y Correcciones:**
- ✅ **Bug #1:** Se eliminó el `namespace` duplicado en `UfvController`.
- ✅ **Bug #2 y #6:** Se actualizaron los atributos `fillable` y `casts` en el modelo `Ufv`.
- ✅ **Bug #3, #5, #8:** Se mejoró el método `import()` para incluir campos de auditoría, validación de fecha futura y una correcta conversión de decimales.
- ✅ **Bug #4:** Se corrigió la mutación de fecha en `UfvApiSeeder` usando `copy()`.
- ✅ **Bug #7:** Se implementó el CRUD completo con los métodos `edit`, `update`, `destroy` y sus respectivas rutas.
- ✅ **Base de Datos:** Se añadió la migración para los campos de auditoría `created_by` y `updated_by`.

### v1.0.0 (15 de enero de 2026)
**Estado Inicial del Análisis:**

**Implementaciones Originales:**
- Detección automática de delimitador en importación.
- Detección inteligente de formato de fecha.
- Inserción masiva optimizada.
- Validación de duplicados en memoria.
- Método `getValorEnFecha()` con búsqueda hacia atrás.

**Bugs Detectados:**
- 8 bugs mayores y menores que afectaban la integridad, seguridad y funcionalidad del módulo.
