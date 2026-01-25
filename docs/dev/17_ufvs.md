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

### Migración: `create_ufvs_table.php`

**Tabla:** `ufvs`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `fecha` | DATE | UNIQUE, NOT NULL | Fecha de vigencia del valor. |
| `valor` | DECIMAL(8,5) | NOT NULL | Valor de la UFV (5 decimales de precisión). |
| `created_at` | TIMESTAMP | | Fecha de registro. |
| `updated_at` | TIMESTAMP | | Fecha de actualización. |

**Índices:**
- `fecha`: Índice único para evitar duplicidad de valores para un mismo día.

**Nota:** La migración actual NO incluye campos de auditoría (`created_by`, `updated_by`).

---

## 🧩 Modelo

### Modelo: `Ufv`

**Ubicación:** `app/Models/Ufv.php`

**Atributos:**
- `$table = 'ufvs'`
- `$fillable`: `['fecha', 'valor']`
- `$casts`:
    - `fecha` => `date`
    - `valor` => `float`

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
- `store(Request $request)`: Guarda un registro individual. Valida que la fecha no sea futura y sea única.
- `import(Request $request)`: **Lógica Compleja**. Procesa archivos CSV/TXT para carga masiva.
    - **Detección de Delimitador:** Lee la primera línea para decidir si usar `,` o `;`.
    - **Detección de Fecha:** Intenta parsear `d/m/Y` y luego `Y-m-d`.
    - **Limpieza de Valor:** Elimina espacios, pipes `|` y convierte comas decimales en puntos.
    - **Validación de Duplicados:** Carga todas las fechas existentes en memoria para evitar consultas SQL por cada fila.
    - **Inserción Masiva:** Usa `Ufv::insert($array)` para alto rendimiento dentro de una transacción.
- `show(Ufv $ufv)`: Muestra el detalle de una UFV.

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
});
```

**Nota:** No existen rutas para editar (`edit`, `update`) ni eliminar (`destroy`) UFVs.

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
], [
    'fecha.unique' => 'Ya existe un valor UFV para esa fecha.',
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
  $ufvInicial = Ufv::getValorEnFecha($fechaHecho);
  $ufvFinal = Ufv::getValorEnFecha($fechaPago);
  ```

### 2. Trámites
- Al crear un trámite, se valida que existan las UFVs necesarias. Si no existen, el sistema suele impedir el registro o usar la última disponible (dependiendo de la regla de negocio).

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

### Puntos Clave a Recordar
- **Detección Automática de Delimitador:** El importador detecta automáticamente si el CSV usa `,` o `;` como delimitador.
- **Formatos de Fecha Soportados:** Soporta tanto `DD/MM/YYYY` como `YYYY-MM-DD`.
- **Búsqueda hacia Atrás:** `getValorEnFecha()` busca la UFV más reciente si la fecha exacta no existe.
- **Validación de Fecha Futura:** En `store()` se valida que la fecha no sea futura, pero NO en `import()`.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.0.0 (15 de enero de 2026) ⚠️

El módulo de UFVs tiene varios bugs y mejoras pendientes de implementación:

- ⚠️ Namespace duplicado en el controlador
- ⚠️ Campos de auditoría no implementados en migración
- ⚠️ Campos `created_by`, `updated_by` no están en fillable del modelo
- ⚠️ Campos de auditoría comentados en importación masiva
- ⚠️ No hay validación de fecha futura en import()
- ⚠️ Problema con conversión de separadores decimales
- ⚠️ Falta métodos edit(), update(), destroy()

### 🐛 Bugs Detectados

| # | Bug | Severidad | Estado | Ubicación |
|---|-----|-----------|--------|-----------|
| 1 | Namespace duplicado en UfvController | Baja | ⚠️ Pendiente | `UfvController.php:4-5` |
| 2 | Campos `created_by`, `updated_by` no en fillable | Media | ⚠️ Pendiente | `Ufv.php:14-17` |
| 3 | Campos de auditoría comentados en import() | Alta | ⚠️ Pendiente | `UfvController.php:151-152` |
| 4 | Mutación inesperada de fecha en UfvApiSeeder | Media | ⚠️ Pendiente | `UfvApiSeeder.php:26` |
| 5 | Conversión inadecuada de separadores decimales | Alta | ⚠️ Pendiente | `UfvController.php:134` |
| 6 | Inconsistencia en cast del modelo (`float` vs `decimal:5`) | Baja | ⚠️ Pendiente | `Ufv.php:21` |
| 7 | Faltan métodos edit(), update(), destroy() | Media | ⚠️ Pendiente | `UfvController.php` |
| 8 | No hay validación de fecha futura en import() | Media | ⚠️ Pendiente | `UfvController.php:import()` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Detección automática de delimitador | El importador detecta `,` o `;` automáticamente |
| 2 | Detección inteligente de formato de fecha | Soporta `DD/MM/YYYY` y `YYYY-MM-DD` |
| 3 | Inserción masiva optimizada | Usa `Ufv::insert()` para alto rendimiento |
| 4 | Validación de duplicados | Carga fechas existentes en memoria para verificar |
| 5 | Método `getValorEnFecha()` | Búsqueda hacia atrás si fecha exacta no existe |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **CRÍTICO** | Fix namespace duplicado | Eliminar la segunda declaración de namespace en UfvController |
| **CRÍTICO** | Implementar campos de auditoría | Agregar `created_by`, `updated_by` a migración y fillable |
| **CRÍTICO** | Validación de fecha futura en import() | Prevenir importación de UFVs con fechas futuras |
| **ALTO** | Arreglar conversión de separadores decimales | Distinguir entre separador decimal y de miles |
| **MEDIO** | Request Classes separados | Crear StoreUfvRequest y ImportUfvRequest |
| **MEDIO** | Implementar métodos edit(), update(), destroy() | CRUD completo para UFVs |
| **MEDIO** | Scopes en el modelo | `scopeHastaFecha()`, `scopeEnRango()`, etc. |
| **MEDIO** | Accesor para valor formateado | `getValorFormateadoAttribute()` |
| **MEDIO** | Observer para auditoría automática | Registrar `created_by`, `updated_by` automáticamente |
| **MEDIO** | Detección de huecos en fechas | Alertar sobre fechas faltantes |
| **BAJO** | Sistema de colas para importación | Usar Jobs para archivos grandes |
| **BAJO** | Reporte de errores descargable | Archivo con filas rechazadas |
| **BAJO** | Comando Artisan para fetch automático | `ufv:fetch` para obtener UFVs del BCB |
| **BAJO** | Widget de Dashboard | Mostrar estado de UFVs (última actualización, huecos) |
| **BAJO** | Caching de UFVs frecuentes | Reducir consultas a BD |
| **BAJO** | Tests unitarios y de integración | Cobertura de tests para el módulo |
| **BAJO** | Exportación de UFVs a CSV/Excel | Permitir respaldar y compartir datos |
| **BAJO** | Soft deletes | Permitir restaurar UFVs eliminadas |
| **BAJO** | Compresión de archivos de importación | Soportar .zip, .gz |

### 📝 Historial de Cambios

### v1.0.0 (15 de enero de 2026)
**Estado Inicial del Análisis:**

**Implementaciones Actuales:**
- ✅ Detección automática de delimitador en importación
- ✅ Detección inteligente de formato de fecha (DD/MM/YYYY o YYYY-MM-DD)
- ✅ Inserción masiva optimizada con `Ufv::insert()`
- ✅ Validación de duplicados en importación
- ✅ Método estático `getValorEnFecha()` con búsqueda hacia atrás

**Bugs Detectados (8):**
- ⚠️ Bug #1: Namespace duplicado en UfvController (líneas 4-5)
- ⚠️ Bug #2: Campos `created_by`, `updated_by` no están en fillable del modelo
- ⚠️ Bug #3: Campos de auditoría comentados en import() masiva
- ⚠️ Bug #4: Mutación inesperada de fecha en UfvApiSeeder.php
- ⚠️ Bug #5: Conversión inadecuada de separadores decimales (todas las comas a puntos)
- ⚠️ Bug #6: Inconsistencia en cast del modelo (`float` vs `decimal:5`)
- ⚠️ Bug #7: Faltan métodos CRUD completos (edit, update, destroy)
- ⚠️ Bug #8: No hay validación de fecha futura en import()

**Notas Adicionales:**
- La migración actual NO incluye campos de auditoría (`created_by`, `updated_by`)
- No existen Request Classes separados para validación
- El controlador intenta asignar `created_by` y `updated_by` en `store()` pero estos campos no existen en la migración
- No hay tests unitarios ni de integración para el módulo

**Próximos Pasos Recomendados:**
1. Crear migración para agregar campos de auditoría a la tabla `ufvs`
2. Agregar `created_by`, `updated_by` al fillable del modelo
3. Eliminar namespace duplicado en el controlador
4. Descomentar líneas de auditoría en import()
5. Implementar validación de fecha futura en import()
6. Mejorar conversión de separadores decimales
7. Crear Request Classes para validación centralizada
8. Implementar métodos edit(), update(), destroy()
