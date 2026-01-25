# Documentación Técnica - Módulo de Avalúos

## 📋 Tabla de Contenidos

1. Introducción
2. Base de Datos
3. Modelo
4. Controlador
5. Rutas
6. Policies y Permisos
7. Requests
8. Vistas
9. Integración con otros módulos
10. Guía para Desarrolladores
11. Análisis de Calidad y Mejoras (Bugs Detectados)

---

## 🎯 Introducción

El módulo de **Avalúos** gestiona las valoraciones técnicas y económicas de los inmuebles. Su función principal es proporcionar un valor actualizado y respaldado (por un perito o entidad fiscal) que sirva como referencia para el cálculo de la **Base Imponible** del Impuesto de Transmisiones Gratuítas de Bienes (ITGB).

### Propósito
- Registrar valoraciones periciales, comerciales o fiscales de un inmueble.
- Almacenar la evidencia digital (informes PDF/Imágenes) del avalúo.
- Controlar la vigencia de las valoraciones para asegurar que los impuestos se calculen sobre valores actuales.
- Vincular a los peritos (Personas) con las valoraciones realizadas.

### Importancia en el Sistema ITGB
El sistema compara automáticamente el **Valor Catastral** del inmueble con el valor del **Avalúo Vigente** (si existe) y el **Valor Declarado** por el contribuyente. Por ley, el impuesto se calcula sobre el mayor de estos valores. Por tanto, este módulo impacta directamente en la recaudación.

---

## 🗄️ Base de Datos

### Migración: `2025_09_22_122750_create_avaluos_table.php`

**Tabla:** `avaluos`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `inmueble_id` | BIGINT | FK, NOT NULL | El inmueble que está siendo valorado. |
| `perito_id` | BIGINT | FK, NULLABLE | La persona (perito) que realizó el avalúo. |
| `tipo_avaluo` | ENUM | ['Fiscal', 'Comercial', 'Pericial'] | El tipo de valoración. |
| `valor` | DECIMAL(14,2) | NOT NULL | El monto valorado en Bolivianos (Bs). |
| `fecha_avaluo` | DATE | NOT NULL | Fecha en que se realizó el avalúo. |
| `documento_path` | VARCHAR(255) | NULLABLE | Ruta al archivo de respaldo. |
| `estado` | ENUM | ['Vigente', 'Caducado'] | Estado de la valoración. |
| `created_by` | BIGINT | FK | Usuario que creó el registro. |
| `updated_by` | BIGINT | FK | Usuario que actualizó el registro. |
| `created_at` | TIMESTAMP | | Fecha de registro en el sistema. |
| `updated_at` | TIMESTAMP | | Fecha de última actualización. |

**Relaciones y Claves Foráneas:**
- `inmueble_id` referencia a `inmuebles(id)`.
- `perito_id` referencia a `people(id)`.
- `created_by`, `updated_by` referencia a `users(id)`.

---

## 🧩 Modelo

### Modelo: `Avaluo`

**Ubicación:** `app/Models/Avaluo.php`

**Atributos:**
- `$table = 'avaluos'`
- `$fillable`: `['inmueble_id', 'tipo_avaluo', 'fecha_avaluo', 'valor', 'perito_id', 'documento_path', 'estado', 'created_by', 'updated_by']`
- `$casts`:
    - `fecha_avaluo` => `date`
    - `valor` => `decimal:2`
    - `estado` => `string`

**Relaciones:**

```php
// El avalúo pertenece a un inmueble
public function inmueble()
{
    return $this->belongsTo(Inmueble::class);
}

// El avalúo fue realizado por una persona (Perito)
public function perito()
{
    return $this->belongsTo(Person::class, 'perito_id');
}

// Auditoría de creación
public function creador()
{
    return $this->belongsTo(User::class, 'created_by');
}

// Auditoría de edición
public function editor()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

**Helpers:**
- `public static function vigente(int $inmuebleId)`: Método estático que retorna el último avalúo con estado 'Vigente' para un inmueble dado.

---

## 🎮 Controlador

### Controlador: `AvaluoController`

**Ubicación:** `app/Http/Controllers/AvaluoController.php`

Gestiona el ciclo de vida de los avalúos.

**Métodos Principales:**
- `index()`: Muestra la vista principal del listado.
- `list()`: Endpoint AJAX que retorna la lista paginada. Permite filtrar por `catastro` del inmueble (vía relación) o por `inmueble_id` específico.
- `create()`: Muestra el formulario de alta. Carga listas de inmuebles y peritos (personas naturales).
- `store(StoreAvaluoRequest $request)`:
    1. Valida los datos.
    2. Sube el archivo `documento` al disco `public` en la carpeta `avaluos`.
    3. Crea el registro asignando `created_by` y `updated_by` al usuario actual.
- `update(UpdateAvaluoRequest $request, Avaluo $avaluo)`:
    1. Valida los datos.
    2. Si se sube un nuevo documento, elimina el anterior del disco y guarda el nuevo.
    3. Actualiza el registro y el campo `updated_by`.
- `destroy(Avaluo $avaluo)`: Elimina el archivo asociado del disco y borra el registro de la base de datos.
- `download(Avaluo $avaluo)`: Permite la descarga segura del archivo adjunto (`documento_path`).

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // ...
    Route::resource('avaluos', AvaluoController::class)->names('admin.avaluos');
    Route::get('avaluos/ajax/list', [AvaluoController::class, 'list'])->name('admin.avaluos.ajax.list');
    Route::get('avaluos/{avaluo}/download', [AvaluoController::class, 'download'])->name('admin.avaluos.download');
});
```

---

## 🔒 Policies y Permisos

### Policy: `AvaluoPolicy`

**Ubicación:** `app/Policies/AvaluoPolicy.php`

| Método | Permiso Voyager | Descripción |
|---|---|---|
| `viewAny` | `browse_avaluos` | Ver listado. |
| `view` | `read_avaluos` | Ver detalle y descargar archivo. |
| `create` | `add_avaluos` | Crear nuevo avalúo. |
| `update` | `edit_avaluos` | Editar existente. |
| `delete` | `delete_avaluos` | Eliminar avalúo. |

El método `before` otorga acceso total a usuarios con permiso `browse_admin`.

---

## ✅ Requests

### `StoreAvaluoRequest`

**Ubicación:** `app/Http/Requests/StoreAvaluoRequest.php`

**Reglas de Validación:**
- `inmueble_id`: `required|exists:inmuebles,id`
- `tipo_avaluo`: `required|in:Fiscal,Comercial,Pericial`
- `fecha_avaluo`: `required|date`
- `valor`: `required|numeric|min:0`
- `perito_id`: `nullable|exists:people,id`
- `documento`: `nullable|file|mimes:pdf,jpg,png|max:5120` (Máx 5MB)
- `estado`: `in:Vigente,Caducado`

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/avaluos/`

- **`browse.blade.php`**: Contenedor principal del listado.
- **`list.blade.php`**: Tabla cargada vía AJAX. Muestra columnas como Inmueble (Catastro), Perito, Tipo, Valor, Fecha y Estado.
- **`edit-add.blade.php`**: Formulario de creación/edición.
    - Select de Inmuebles.
    - Select de Peritos (filtrado por `person_type = 'Natural'`).
    - Input de archivo para el documento de respaldo.
- **`read.blade.php`**: Vista de detalle.

---

## 🔗 Integración con otros módulos

### 1. Módulo de Inmuebles
- El modelo `Inmueble` tiene la relación `hasMany(Avaluo::class)`.
- Se utiliza el helper `Avaluo::vigente($inmuebleId)` para obtener la valoración actual.

### 2. Módulo de Trámites (Cálculo de Impuestos)
- El servicio `IdtgbCalculator` (o lógica equivalente) debe consultar los avalúos para determinar la base imponible.
- **Lógica de Selección de Base Imponible**:
  ```php
  $valorCatastral = $inmueble->valor_catastral;
  $avaluoVigente = Avaluo::vigente($inmueble->id);
  $valorAvaluo = $avaluoVigente ? $avaluoVigente->valor : 0;
  $valorDeclarado = $tramite->valor_declarado;

  $baseImponible = max($valorCatastral, $valorAvaluo, $valorDeclarado);
  ```

### 3. Módulo de Personas
- Se utiliza para registrar a los peritos tasadores (`perito_id`). El controlador filtra para mostrar solo personas naturales.

---

## 📝 Guía para Desarrolladores

### Obtener el avalúo vigente
Para obtener el avalúo vigente de un inmueble, se debe usar el método estático del modelo:

```php
use App\Models\Avaluo;

$avaluo = Avaluo::vigente($inmueble_id);
if ($avaluo) {
    // Usar $avaluo->valor
}
```

### Gestión de Archivos
El controlador maneja automáticamente la subida y reemplazo de archivos en el disco `public`. Al eliminar un avalúo, el archivo físico también se elimina para no dejar residuos.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (20 de enero de 2026) ✅

El módulo de Avalúos ha sido optimizado y cuenta con funcionalidades clave para la gestión de valoraciones:

- ✅ Autorización implementada en todos los métodos del controlador
- ✅ Auditoría completa con campos `created_by` y `updated_by`
- ✅ Gestión de archivos con eliminación física al borrar registros
- ✅ Seguridad en descargas de archivos con verificación de permisos
- ✅ Limpieza de archivos huérfanos al actualizar documentos
- ✅ Eager loading en listados para evitar N+1 queries
- ✅ Helper estático `vigente()` para obtener avalúos vigentes por inmueble

### 🐛 Bugs Corregidos (5/5) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Falta de autorización en todos los métodos del controlador | ✅ Corregido | `AvaluoController.php:22,29,51,58,68,88,98,118,132` |
| 2 | Falta de campos de auditoría en registros | ✅ Corregido | `AvaluoController.php:75-79, 106-109` |
| 3 | Descarga de archivos sin verificación de permisos | ✅ Corregido | `AvaluoController.php:132` |
| 4 | Archivos físicos no eliminados al borrar registros | ✅ Corregido | `AvaluoController.php:120-122` |
| 5 | Archivos antiguos no eliminados al actualizar | ✅ Corregido | `AvaluoController.php:100-104` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Autorización completa | Llamadas `authorize()` en todos los métodos del controlador (index, list, show, create, store, edit, update, destroy, download) |
| 2 | Auditoría completa de cambios | Registro de usuario creador (created_by) en todas las creaciones y usuario actualizador (updated_by) en todas las actualizaciones |
| 3 | Seguridad en descargas de archivos | Implementación de authorize() en método download() con verificación de existencia del archivo |
| 4 | Limpieza de archivos huérfanos | Eliminación automática de archivos físicos al borrar registros y archivos anteriores al actualizar |
| 5 | Eager loading en listados | Carga relaciones en consultas para evitar N+1 queries |
| 6 | Helper estático vigente() | Método `Avaluo::vigente($inmuebleId)` para obtener el último avalúo vigente de un inmueble |
| 7 | Manejo de errores robusto | Bloque try-catch en método list() con respuesta JSON para errores |
| 8 | Filtrado de peritos por tipo de persona | Controlador filtra solo personas naturales para ser peritos |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **MEDIA** | Validación de tipo de perito | Refinar regla de validación para asegurar que el perito sea persona `Natural` y no `Jurídica` |
| **MEDIA** | Automatización de caducidad | Crear comando programado que cambie el estado a 'Caducado' automáticamente cuando el avalúo supera el año de antigüedad |
| **MEDIA** | Validación de estado según fecha | Implementar lógica de negocio en `store()` y `update()` para calcular el estado automáticamente según la fecha |
| **BAJO** | Scope para eager loading de vigentes | Convertir helper `vigente()` a scope o relación en modelo `Inmueble` para evitar problemas N+1 en listados |
| **BAJO** | Validación de tamaño de archivo | Considerar aumentar el tamaño máximo de archivo de 5MB si es insuficiente para informes periciales |
| **BAJO** | Soft Deletes | Implementar soft deletes para permitir recuperación de avalúos eliminados |
| **BAJO** | Importación/Exportación masiva | Sistema para importar/exportar avalúos desde CSV/Excel |
| **BAJO** | Notificaciones de cambios | Sistema de alertas cuando se modifican avalúos que afectan trámites activos |

### 📝 Historial de Cambios

### v2.0.0 (20 de enero de 2026)
**Correcciones Completadas (5/5):**
- ✅ Bug #1: Autorización - Agregadas llamadas `authorize()` en todos los métodos del controlador
- ✅ Bug #2: Auditoría - Implementados campos `created_by` y `updated_by` en `store()` y `update()`
- ✅ Bug #3: Descarga segura - Agregada verificación de permisos en método `download()`
- ✅ Bug #4: Limpieza de archivos - Implementada eliminación física en `destroy()` línea 120-122
- ✅ Bug #5: Gestión de actualizaciones - Implementada eliminación de archivos anteriores en `update()` línea 100-104

**Archivos Modificados:**
- `app/Http/Controllers/AvaluoController.php`:
  - Agregados `authorize()` en todos los métodos: `index()`, `list()`, `show()`, `create()`, `store()`, `edit()`, `update()`, `destroy()`, `download()`
  - Implementados campos de auditoría `created_by` y `updated_by` en `store()` línea 75-79
  - Implementados campos de auditoría `updated_by` en `update()` línea 106-109
  - Agregada verificación de existencia de archivo en `download()` línea 134-136
  - Implementada eliminación de archivo físico en `destroy()` línea 120-122
  - Implementada eliminación de archivo anterior en `update()` línea 100-104
  - Agregado bloque try-catch en `list()` línea 28-45

**Beneficios:**
- Seguridad mejorada con autorización completa
- Trazabilidad completa de quién creó/actualizó cada avalúo
- Previene acumulación de archivos huérfanos en el sistema de archivos
- Mayor seguridad en descargas con verificación de permisos
- Mensajes de error más claros para el usuario final