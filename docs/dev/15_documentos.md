# Documentación Técnica - Módulo de Documentos

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

El módulo de **Documentos** gestiona el archivo digital asociado a un trámite. Permite adjuntar, clasificar y almacenar la evidencia documental requerida para sustentar la transmisión de bienes (ej. Testimonios, Folios Reales, Cédulas de Identidad, Minutas).

### Propósito
- Digitalizar y asociar archivos físicos a un trámite específico.
- Clasificar los documentos para facilitar su revisión (ej. "Identificación", "Título de Propiedad").
- Servir como repositorio centralizado de la documentación legal del trámite.

### Importancia en el Sistema ITGB
Es fundamental para la auditoría y validación del trámite. Un trámite no debería aprobarse o finalizarse sin los documentos de respaldo mínimos requeridos por normativa.

---

## 🗄️ Base de Datos

### Migración: `create_documentos_table.php`

**Tabla:** `documentos`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `tramite_id` | BIGINT | FK, NOT NULL | El trámite al que pertenece el documento. |
| `descripcion` | VARCHAR(255) | NOT NULL | Nombre o título descriptivo del documento. |
| `archivo_path` | VARCHAR(255) | NOT NULL | Ruta relativa de almacenamiento en el disco. |
| `tipo` | VARCHAR(50) | NULLABLE | Categoría del documento (opcional). |
| `created_by` | BIGINT | FK | Usuario que subió el documento. |
| `created_at` | TIMESTAMP | | Fecha de subida. |
| `updated_at` | TIMESTAMP | | Fecha de última modificación. |
| `deleted_at` | TIMESTAMP | NULLABLE | Fecha de borrado lógico (SoftDeletes). |

**Relaciones:**
- `tramite_id` referencia a `tramites(id)` con `onDelete('cascade')`.
- `created_by` referencia a `users(id)`.

---

## 🧩 Modelo

### Modelo: `Documento`

**Ubicación:** `app/Models/Documento.php`

**Atributos:**
- `$table = 'documentos'`
- `$fillable`: `['tramite_id', 'descripcion', 'archivo_path', 'tipo', 'created_by']`

**Relaciones:**

```php
// El documento pertenece a un trámite
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

### Controlador: `DocumentoController`

**Ubicación:** `app/Http/Controllers/DocumentoController.php`

Gestiona la subida y eliminación de archivos adjuntos a un trámite existente.

**Métodos Principales:**
- `index(Tramite $tramite)`: Muestra la vista principal de documentos del trámite.
- `list(Tramite $tramite)`: Endpoint AJAX que retorna la lista de documentos.
- `create(Tramite $tramite)`: Muestra el formulario de carga.
- `store(StoreDocumentoRequest $request, Tramite $tramite)`:
    1. Valida el archivo (mimes, tamaño).
    2. Sube el archivo al disco (generalmente `public` o `local`).
    3. Crea el registro en la base de datos.
- `show(Tramite $tramite, Documento $item)`: Permite visualizar o descargar el archivo.
- `destroy(Tramite $tramite, Documento $item)`: Elimina el registro y (debería) eliminar el archivo físico.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

Las rutas están anidadas bajo el recurso `tramites`, similar a otros submódulos.

```php
Route::prefix('tramites/{tramite}/documentos')->name('admin.tramites.documentos.')->group(function () {
    Route::get('/', [DocumentoController::class, 'index'])->name('index');
    Route::get('/ajax/list', [DocumentoController::class, 'list'])->name('ajax.list');
    Route::get('/create', [DocumentoController::class, 'create'])->name('create');
    Route::post('/', [DocumentoController::class, 'store'])->name('store');
    Route::get('/{item}', [DocumentoController::class, 'show'])->name('show');
    Route::delete('/{item}', [DocumentoController::class, 'destroy'])->name('destroy');
});
```

---

## 🔒 Policies y Permisos

### Policy: `DocumentoPolicy` (Inferido)

**Ubicación:** `app/Policies/DocumentoPolicy.php`

| Método | Permiso Voyager | Descripción |
|---|---|---|
| `viewAny` | `browse_documentos` | Ver listado de documentos. |
| `view` | `read_documentos` | Descargar/Ver archivo. |
| `create` | `add_documentos` | Subir nuevo documento. |
| `delete` | `delete_documentos` | Eliminar documento. |

---

## ✅ Requests

### `StoreDocumentoRequest`

**Ubicación:** `app/Http/Requests/StoreDocumentoRequest.php`

**Reglas de Validación Típicas:**
- `descripcion`: `required|string|max:255`
- `archivo`: `required|file|mimes:pdf,jpg,jpeg,png|max:10240` (Máx 10MB)
- `tipo`: `nullable|string`

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/tramites/documentos/`

- **`browse.blade.php`**: Contenedor principal.
- **`list.blade.php`**: Tabla AJAX. Muestra columnas como Descripción, Tipo, Fecha Subida, y botones de Ver/Descargar/Borrar.
- **`create.blade.php`**: Formulario de subida (Input file).
- **`read.blade.php`**: Vista de detalle, posiblemente con un `iframe` o `img` para previsualizar el documento.

---

## 🔗 Integración con otros módulos

### 1. Módulo de Trámites (Wizard)
- **Paso 5 (Documentos):** El `TramiteWizardController` maneja una versión simplificada de la carga de documentos durante la creación del trámite.
- Utiliza métodos como `addDocumento` y `removeDocumento` que guardan temporalmente los archivos o referencias en la sesión antes de persistirlos en la base de datos al finalizar el wizard.

### 2. Módulo de Avalúos
- Aunque los avalúos tienen su propio campo `documento_path`, conceptualmente son documentos. Sin embargo, se manejan en tablas separadas (`avaluos` vs `documentos`) para mantener la lógica de valoración aislada de la documentación general.

---

## 📝 Guía para Desarrolladores

### Almacenamiento
Los archivos se guardan típicamente en `storage/app/public/documentos/{tramite_id}/`.
- **Acceso:** Se debe usar `Storage::url($path)` para generar enlaces públicos.
- **Seguridad:** Si los documentos son sensibles (ej. CI, extractos bancarios), no deberían estar en el disco `public`. Deberían estar en `local` y servirse a través de una ruta protegida que verifique permisos (`DocumentoController@show`).

### Nomenclatura
Se recomienda normalizar los nombres de archivo al subir para evitar problemas con caracteres especiales.
Ejemplo: `time() . '_' . Str::slug($request->descripcion) . '.' . $extension`

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (20 de enero de 2026) ✅

El módulo de Documentos ha sido optimizado y cuenta con funcionalidades clave para la gestión de archivos:

- ✅ Autorización implementada en todos los métodos del controlador
- ✅ Manejo de transacciones de base de datos en operaciones de escritura
- ✅ Versionamiento automático de documentos (marca anteriores como no vigentes)
- ✅ Hash SHA-256 para integridad de archivos
- ✅ Auditoría completa con campos `created_by` y `updated_by`
- ✅ Validación de pertenencia del documento al trámite en operaciones de destrucción
- ✅ Soft deletes implementados
- ✅ Ordenamiento por versión descendente

### 🐛 Bugs Corregidos (4/4) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Falta de autorización en todos los métodos del controlador | ✅ Corregido | `DocumentoController.php:23,29,47,54,71,91,118` |
| 2 | Falta de manejo de transacciones en operaciones de escritura | ✅ Corregido | `DocumentoController.php:73-85, 124-137` |
| 3 | Validación de pertenencia del documento al trámite | ✅ Corregido | `DocumentoController.php:120-122` |
| 4 | Eliminación de archivo en caso de error en transacción | ✅ Corregido | `DocumentoController.php:108-110` |

### 🚀 Mejoras Implementadas ✅

| # | Mejora | Descripción |
|---|--------|-------------|
| 1 | Autorización completa | Llamadas `authorize()` en todos los métodos del controlador (index, list, show, create, store, destroy) |
| 2 | Transacciones DB | Implementado `DB::beginTransaction()`, `DB::commit()`, `DB::rollBack()` para atomicidad |
| 3 | Versionamiento automático | Documentos anteriores del mismo tipo se marcan como no vigentes, versión nueva se calcula automáticamente |
| 4 | Hash SHA-256 | Se calcula el hash SHA-256 de cada archivo subido para verificar integridad |
| 5 | Ordenamiento por versión | Los documentos se listan ordenados por versión descendente (más reciente primero) |
| 6 | Auditoría completa | Campos `created_by` y `updated_by` registrados en todos los documentos |
| 7 | Validación de pertenencia | Se valida que el documento pertenezca al trámite antes de eliminar |
| 8 | Manejo de errores robusto | Try-catch con eliminación de archivo en caso de error |
| 9 | Soft deletes | Implementado para mantener historial de documentos |
| 10 | Inicialización correcta de vista | Se inicializa un nuevo objeto Documento para evitar errores en la vista |

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **ALTA** | Ruta de descarga segura | Implementar método `download()` que sirva archivos privados verificando permisos y usando disco `local` |
| **MEDIA** | Validación de tipos de archivo | Reforzar reglas de MIME types para evitar spoofing |
| **MEDIA** | Categorización obligatoria | Crear tabla `tipos_documento` para validar requisitos obligatorios |
| **MEDIA** | Límite de tamaño por tipo | Diferentes límites según el tipo (5MB imágenes, 10MB PDFs) |
| **BAJO** | Previsualización en navegador | Permitir que el navegador abra PDFs/imágenes en nueva pestaña o modal |
| **BAJO** | Compresión automática | Implementar compresión de imágenes al upload con `Intervention Image` |
| **BAJO** | Firma digital | Permitir firmar digitalmente documentos PDF |
| **BAJO** | OCR para búsqueda | Implementar OCR en PDFs escaneados para búsqueda de contenido |
| **BAJO** | Notas/Comentarios | Agregar campo para notas sobre cada documento |
| **BAJO** | Workflow de aprobación | Implementar workflow para aprobación/rechazo de documentos |

### 📝 Historial de Cambios

### v2.0.0 (20 de enero de 2026)
**Correcciones Completadas (4/4):**
- ✅ Bug #1: Autorización - Agregadas llamadas `authorize()` en todos los métodos del controlador
- ✅ Bug #2: Transacciones - Implementado manejo de transacciones en `store()` y `destroy()`
- ✅ Bug #3: Validación de pertenencia - Agregada validación en `destroy()` línea 120-122
- ✅ Bug #: Limpieza de archivos - Implementada eliminación de archivo en caso de error

**Archivos Modificados/Creados:**
- `app/Http/Controllers/DocumentoController.php`:
  - Agregados `authorize()` en todos los métodos: `index()`, `list()`, `show()`, `create()`, `store()`, `destroy()`
  - Implementadas transacciones DB con try-catch en `store()` y `destroy()`
  - Agregada validación de pertenencia de documento al trámite en `destroy()` línea 120-122
  - Implementada eliminación de archivo en caso de error en `store()` línea 108-110
  - Implementado versionamiento automático en `store()` líneas 82-99
  - Implementado cálculo de hash SHA-256 en `store()` línea 78-79
  - Implementado ordenamiento por versión descendente en `list()` línea 37-39

- `app/Models/Documento.php`:
  - Agregados campos `hash_sha256`, `version`, `vigente` al fillable
  - Agregados casts para `version` (integer) y `vigente` (boolean)
  - Agregado helper `estaVigente()` para verificar vigencia
  - Agregado helper `marcaComoCaducado()` para actualizar estado

**Beneficios:**
- Seguridad mejorada con autorización completa
- Integridad de datos garantizada con versionamiento automático
- Trazabilidad completa de quién creó/actualizó cada documento
- Integridad de archivos garantizada con verificación SHA-256
- Mantenimiento de historial de documentos con soft deletes y versionamiento
- Código más robusto con manejo de transacciones y errores

## 🐛 BUGS CRÍTICOS Y DE ALTA PRIORIDAD

### 1. Vulnerabilidad SQL Injection en AjaxController
**Ubicación:** `app/Http/Controllers/AjaxController.php:17-27`
**Severidad:** CRÍTICA

**Problema:**
```php
$q = Person::OrWhereRaw($q ? "ci like '%$q%'" : 1)
    ->OrWhereRaw($q ? "phone like '%$q%'" : 1)
    ->OrWhereRaw($q ? "first_name like '%$q%'" : 1)
```
El input del usuario se concatena directamente en consultas SQL sin sanitización, permitiendo ataques SQL injection.

**Solución:**
```php
$q = Person::when($q, fn($query) => $query
    ->where('ci', 'like', "%{$q}%")
    ->orWhere('phone', 'like', "%{$q}%")
    ->orWhere('first_name', 'like', "%{$q}%")
);
```

---

### 2. SQL Injection en UserController
**Ubicación:** `app/Http/Controllers/UserController.php:38-44`
**Severidad:** CRÍTICA

**Problema:**
```php
$query->OrWhereRaw($search ? "id = '$search'" : 1)
->OrWhereRaw($search ? "name like '%$search%'" : 1)
->OrWhereRaw($search ? "email like '%$search%'" : 1);
```

**Solución:** Igual que el anterior, usar métodos seguros de Eloquent.

---

### 3. Verificación Nula Faltante en TramiteWizardController
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php:454-455`
**Severidad:** ALTA

**Problema:**
```php
$inmuebleRef = Inmueble::with('municipio.provincia')->find($wizardData['step4']['inmuebles'][0]);
$depId = $inmuebleRef->municipio->provincia->departamento_id;
```
No verifica si `$inmuebleRef`, `municipio` o `provincia` existen antes de acceder a `departamento_id`.

**Solución:**
```php
$inmuebleRef = Inmueble::with('municipio.provincia')->find($wizardData['step4']['inmuebles'][0] ?? null);
if (!$inmuebleRef || !$inmuebleRef->municipio || !$inmuebleRef->municipio->provincia) {
    throw new \Exception('No se puede determinar el departamento del inmueble.');
}
$depId = $inmuebleRef->municipio->provincia->departamento_id;
```

---

### 4. Posible Null Pointer en Person Model
**Ubicación:** `app/Models/Person.php:155-163`
**Severidad:** MEDIA

**Problema:**
```php
$ubicacion = $this->municipio->nombre;
if ($this->municipio->provincia) {
    $ubicacion .= ', ' . $this->municipio->provincia->nombre;
}
```
Si `$this->municipio` es null, se produce error.

**Solución:**
```php
$ubicacion = optional($this->municipio)->nombre ?? 'Ubicación no especificada';
$ubicacion .= optional(optional($this->municipio)->provincia)->nombre 
    ? ', ' . optional($this->municipio)->provincia->nombre 
    : '';
```

---

### 5. Falta Validación en Update de UserController
**Ubicación:** `app/Http/Controllers/UserController.php:82-102`
**Severidad:** ALTA

**Problema:** El método update no tiene reglas de validación definidas, usa directamente `$request->all()`.

**Solución:**
```php
$request->validate([
    'status' => 'sometimes|boolean',
    'role_id' => 'sometimes|exists:roles,id',
    'password' => 'sometimes|string|min:8',
]);
```

---

### 6. Archivos No Verificados Antes de Eliminar
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php:614-655`
**Severidad:** MEDIA

**Problema:** Se verifica la existencia del archivo pero no hay manejo de error si no existe, puede dejar datos en estado parcial.

**Solución:** Agregar manejo de errores o rollback si el archivo está faltante.

---

### 7. Declaración de Namespace Duplicada
**Ubicación:** `app/Http/Controllers/UfvController.php:1-6`
**Severidad:** BAJA

**Problema:**
```php
namespace App\Http\Controllers;
// app/Http/Controllers/UfvController.php
namespace App\Http\Controllers;
```

**Solución:** Eliminar la línea duplicada.

---

## 🚫 CARACTERÍSTICAS FALTANTES

### 1. Autorización Faltante en Rutas API
**Ubicación:** `routes/api.php:17-19`
**Severidad:** ALTA

**Problema:** Los endpoints API solo requieren autenticación, sin autorización basada en roles.

**Solución:** Agregar middleware de roles o verificaciones de autorización:
```php
Route::middleware(['auth:sanctum', 'role:admin'])->group(function () {
    Route::apiResource('users', UserController::class);
});
```

---

### 2. Índices de Base de Datos Faltantes
**Ubicación:** `database/migrations/` (múltiples archivos)
**Severidad:** MEDIA

**Problema:** No hay índices definidos para columnas consultadas frecuentemente:
- `ci` y `ci_complemento` en tabla `persons`
- `nit` en tabla `persons`
- `email` en tabla `users`
- `nro_tramite` en tabla `tramites`
- `estado` en tabla `tramites`
- `fecha_presentacion` en tabla `tramites`
- `tipo_transmision_id` en tabla `tramites`

**Solución:** Agregar índices en migraciones:
```php
$table->index(['ci', 'ci_complemento']);
$table->index('estado');
$table->index('fecha_presentacion');
$table->index('tipo_transmision_id');
```

---

### 3. Validación de Tipos de Documento Faltante
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php:335-338`
**Severidad:** MEDIA

**Problema:** Los tipos de documentos están hardcodeados en un array sin validación contra una tabla de referencia.

**Solución:** Crear tabla `tipos_documento` o usar enum en migración:
```php
Schema::create('tipos_documento', function (Blueprint $table) {
    $table->id();
    $table->string('nombre');
    $table->boolean('requerido')->default(false);
    $table->timestamps();
});
```

---

### 4. Restricción Única Faltante en Números de Operación
**Ubicación:** `database/migrations/2025_09_22_122826_create_pagos_table.php`
**Severidad:** MEDIA

**Problema:** No hay restricción única en `nro_operacion` por banco o rango de fechas.

**Solución:**
```php
$table->unique(['nro_operacion', 'banco_id', 'fecha_operacion']);
```

---

### 5. Soft Deletes Faltantes en Tablas Críticas
**Ubicación:** Varias migraciones
**Severidad:** MEDIA

**Problema:** Las tablas `tramites`, `pagos`, `inmuebles` no tienen soft deletes pero contienen datos críticos.

**Solución:** Agregar `softDeletes()` a las migraciones y usar el trait `SoftDeletes` en los modelos:
```php
Schema::table('tramites', function (Blueprint $table) {
    $table->softDeletes();
});
```

---

### 6. Audit Trail Faltante para Pago
**Ubicación:** `app/Models/Pago.php`
**Severidad:** BAJA

**Problema:** Los campos de auditoría (`created_by`, `updated_by`) existen en la migración pero no están en el array `$fillable` del modelo.

**Solución:**
```php
protected $fillable = [
    // ... otros campos
    'created_by',
    'updated_by',
];
```

---

### 7. Comentario DEBUG Dejado en Código
**Ubicación:** `app/Http/Controllers/Admin/DashboardController.php:34`
**Severidad:** BAJA

**Problema:**
```php
// dd('DashboardController@index called'); // DEBUG
```

**Solución:** Eliminar el comentario de debug.

---

## ⚡ OPTIMIZACIONES

### 1. Problema N+1 en PersonController
**Ubicación:** `app/Http/Controllers/PersonController.php:35-38, 50-74`
**Severidad:** MEDIA

**Problema:** Eager loading no siempre consistente.

**Solución:** Asegurar que todas las consultas usen `with()`:
```php
$data = Person::query()
    ->with(['municipio.provincia.departamento'])
    ->paginate($paginate);
```

---

### 2. Consulta Ineficiente en TramiteController
**Ubicación:** `app/Http/Controllers/TramiteController.php:35-38`
**Severidad:** BAJA

**Problema:** Falta eager loading para relaciones accedidas frecuentemente.

**Solución:**
```php
$data = Tramite::with(['tipoTransmision', 'user', 'inmuebles.tipoInmueble', 'adquirentes.person'])
    ->when($search, fn($q) => $q->where('nro_tramite', 'like', "%{$search}%"))
    ->orderByDesc('id')
    ->paginate($paginate);
```

---

### 3. Problema N+1 en AdquirenteTramiteController
**Ubicación:** `app/Http/Controllers/AdquirenteTramiteController.php:79-84`
**Severidad:** MEDIA

**Problema:** Se obtienen datos relacionados dentro del controlador.

**Solución:** Eager load relationships en la consulta principal o método store.

---

### 4. Paginación Faltante en Listas Grandes
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php:680-710, 719-730`
**Severidad:** BAJA

**Problema:** `Person::whereIn()->get()` sin paginación en datasets potencialmente grandes.

**Solución:**
```php
$personas = Person::whereIn('id', array_merge($disponentesIds, $adquirentesIds))
    ->paginate(20);
```

---

### 5. Invalidación de Cache No Implementada
**Ubicación:** `app/Services/IdtgbCalculator.php:222-236`
**Severidad:** BAJA

**Problema:** El cache se establece pero nunca se invalida cuando cambian los valores de tasa.

**Solución:** Implementar invalidación de cache en eventos del modelo Tasa:
```php
static::saved(function($tasa) {
    Cache::forget("tasa:{$tasa->departamento_id}:{$tasa->parentesco_id}:*");
});
```

---

### 6. Archivo Muy Grande: TramiteWizardController
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php` (735 líneas)
**Severidad:** BAJA

**Problema:** El controlador es demasiado grande y maneja demasiadas responsabilidades.

**Solución:** Dividir en múltiples controladores o servicios:
```php
// Separar lógica de cada paso en servicios dedicados
class TramiteWizardStep1Service { ... }
class TramiteWizardStep2Service { ... }
class TramiteWizardStep3Service { ... }
class TramiteWizardStep4Service { ... }
class TramiteWizardStep5Service { ... }
```

---

### 7. Select * Innecesario
**Ubicación:** `app/Http/Controllers/PersonController.php:52`
**Severidad:** BAJA

**Problema:** Usar `select('*')` con `selectRaw()` es redundante.

**Solución:** Eliminar `select('*')` o listar explícitamente las columnas.

---

## 🎨 PROBLEMAS DE CALIDAD DE CÓDIGO

### 1. Números Mágicos en IdtgbCalculator
**Ubicación:** `app/Services/IdtgbCalculator.php:180, 182, 188`
**Severidad:** BAJA

**Problema:**
```php
$r = 0.04;
if ($aniosMora > 4) $r = 0.06;
if ($aniosMora > 7) $r = 0.10;

$cantUfvMulta = ($tipoContribuyente === 'Jurídica') ? 100 : 50;
```

**Solución:** Definir como constantes o valores de configuración:
```php
private const INTERES_RATE_ANIO_1_4 = 0.04;
private const INTERES_RATE_ANIO_5_7 = 0.06;
private const INTERES_RATE_ANIO_8_PLUS = 0.10;
private const MULTA_UFV_NATURAL = 50;
private const MULTA_UFV_JURIDICA = 100;
```

---

### 2. Strings Mágicos Repetidos
**Ubicación:** Múltiples archivos
**Severidad:** BAJA

**Problema:** Valores de estado hardcodeados repetidamente:
```php
'estado' => ['Borrador', 'Pagado', 'Observado', 'Anulado', 'Finalizado']
```

**Solución:** Usar enums de PHP 8.1+ o constantes de clase:
```php
class TramiteStatus {
    const BORRADOR = 'Borrador';
    const PAGADO = 'Pagado';
    const OBSERVADO = 'Observado';
    const ANULADO = 'Anulado';
    const FINALIZADO = 'Finalizado';
}
```

---

### 3. Nombres Inconsistentes
**Ubicación:** `app/Models/Person.php:74-93`
**Severidad:** BAJA

**Problema:** Ambos accessors `full_name` y `nombreCompleto` existen con comportamientos ligeramente diferentes.

**Solución:** Estandarizar en un solo accessor y eliminar el otro.

---

### 4. Método Largo: Store en Wizard
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php:552-695`
**Severidad:** MEDIA

**Problema:** El método `store()` tiene 144 líneas y maneja demasiada lógica.

**Solución:** Extraer en métodos más pequeños o clases de servicio:
```php
public function store(Request $request)
{
    return DB::transaction(function () use ($request) {
        $this->validateWizardData($request);
        $wizardData = $request->session()->get('wizard_data');
        
        $tramite = $this->createTramite($wizardData);
        $this->processInmuebles($wizardData, $tramite);
        $this->processDisponentes($wizardData, $tramite);
        $this->processAdquirentes($wizardData, $tramite);
        $this->processDocumentos($wizardData, $tramite);
        $this->calculateIdtgb($tramite);
        
        return redirect()->route('admin.tramites.show', $tramite);
    });
}
```

---

### 5. Código Comentado
**Ubicación:** `app/Http/Controllers/UserController.php:19-25, 42, 151-154`
**Severidad:** BAJA

**Problema:** Bloques grandes de código comentado que deberían eliminarse.

**Solución:** Eliminar o mover al historial de versiones.

---

### 6. Código Duplicado en Controladores
**Ubicación:** Múltiples archivos de controladores
**Severidad:** BAJA

**Problema:** Patrones similares repetidos en controladores (index, list, create, store, destroy).

**Solución:** Usar traits o controlador base con métodos comunes:
```php
trait HasStandardCrudOperations
{
    public function index(Request $request, $parentId = null)
    {
        $query = $this->model::query();
        
        if ($parentId) {
            $query->where($this->parentKey, $parentId);
        }
        
        return $query->paginate($request->per_page ?? 15);
    }
    // ... otros métodos comunes
}
```

---

### 7. Manejo Inconsistente de Errores
**Ubicación:** Varias controladores
**Severidad:** MEDIA

**Problema:** Algunos métodos usan `catch (\Throwable $e)`, otros usan `catch (\Exception $e)`.

**Solución:** Estandarizar en `\Throwable` para consistencia.

---

## 🔒 PREOCUPACIONES DE SEGURIDAD

### 1. Vulnerabilidad Mass Assignment en PersonController
**Ubicación:** `app/Http/Controllers/PersonController.php:87-103`
**Severidad:** ALTA

**Problema:** Usar `$request->except()` sin validación adecuada, y el modelo Person tiene un array `$fillable` extenso.

**Solución:** Usar validación FormRequest apropiada y solo pasar datos validados.

---

### 2. Uploads de Archivos Sin Validación
**Ubicación:** `app/Http/Controllers/StorageController.php:82-151`
**Severidad:** ALTA

**Problema:** El método `store_image()` no valida tipos de archivo apropiadamente más allá de verificar validez.

**Solución:** Agregar validación explícita de MIME type:
```php
$request->validate([
    'file' => 'required|file|mimes:jpeg,png,jpg,avif,webp|max:5120',
]);
```

---

### 3. Protección CSRF Faltante en Endpoints AJAX
**Ubicación:** `routes/web.php:262-265`
**Severidad:** MEDIA

**Problema:** Las rutas AJAX pueden ser vulnerables a CSRF si no se manejan apropiadamente.

**Solución:** Asegurar que todas las peticiones AJAX incluyan el token CSRF:
```javascript
// En JavaScript
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

---

### 4. Datos Sensibles en Logs
**Ubicación:** `app/Http/Middleware/Loggin.php:37-38`
**Severidad:** MEDIA

**Problema:** Logging de datos de entrada potencialmente sensibles (excluyendo solo password, token, method).

**Solución:** Excluir más campos sensibles (SSN, datos financieros, info personal):
```php
'input' => request()->except([
    'password', '_token', '_method', 
    'ci', 'nit', 'email', 'phone',
    'nro_cuenta', 'nro_tarjeta'
]),
```

---

### 5. Posible Path Traversal en Upload de Archivos
**Ubicación:** `app/Http/Controllers/DocumentoController.php:76`
**Severidad:** MEDIA

**Problema:** La ruta del archivo se construye desde input del usuario sin sanitización apropiada (aunque el método `store()` de Laravel mitiga esto).

**Solución:** Validación adicional para el nombre del archivo y contenido:
```php
$fileName = time() . '_' . Str::random(10) . '.' . $request->file('archivo')->getClientOriginalExtension();
$path = $request->file('archivo')->storeAs('documentos/' . $tramite->id, $fileName, 'local');
```

---

### 6. Configuración de Sesión Débil
**Ubicación:** `.env.example:24`
**Severidad:** BAJA

**Problema:** `SESSION_LIFETIME=120` es de 2 horas, puede ser muy largo para operaciones sensibles.

**Solución:** Considerar reducir a 30-60 minutos para datos financieros.

---

### 7. Modo Debug Habilitado en Ejemplo de Producción
**Ubicación:** `.env.example:4`
**Severidad:** MEDIA

**Problema:** `APP_DEBUG=true` en el ejemplo podría llevar a despliegues en producción con debug habilitado.

**Solución:** Cambiar a `APP_DEBUG=false` en el ejemplo.

---

### 8. Credenciales de Base de Datos por Defecto
**Ubicación:** `config/database.php:51-53`
**Severidad:** BAJA

**Problema:** Las credenciales por defecto `forge/forge` están en config.

**Solución:** Asegurar que estas sean sobrescritas por variables de entorno en producción.

---

### 9. Posible XSS en Renderizado Markdown
**Ubicación:** `routes/web.php:65-72`
**Severidad:** MEDIA

**Problema:** Contenido markdown controlado por el usuario renderizado a HTML sin sanitización.

**Solución:** Usar HTMLPurifier o eliminar tags peligrosos:
```php
$htmlContent = Str::markdown($markdownContent, [
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);
```

---

### 10. Acceso No Restringido a Archivos via URL de Validación
**Ubicación:** `app/Http/Controllers/ValidacionController.php:15-20`
**Severidad:** MEDIA

**Problema:** Ruta pública muestra todos los datos del trámite con solo validación de hash (que es predecible).

**Solución:** Agregar rate limiting y/o expirar enlaces de validación:
```php
Route::get('/validar/{hash}', [ValidacionController::class, 'show'])
    ->name('tramite.validar')
    ->middleware('throttle:10,1'); // Limitar a 10 peticiones por minuto
```

---

## 📊 RESUMEN DE PROBLEMAS POR SEVERIDAD

### Críticos (5):
1. SQL Injection en AjaxController
2. SQL Injection en UserController  
3. Vulnerabilidades Mass Assignment
4. Uploads de archivos sin validación
5. XSS en renderizado markdown

### Alta Prioridad (8):
1. Verificaciones nulas faltantes en wizard
2. Autorización faltante en rutas API
3. Validación faltante en actualizaciones de usuario
4. Consultas N+1 ineficientes
5. Métodos de controlador grandes
6. Manejo inconsistente de errores
7. Modo debug en example env
8. Índices de base de datos faltantes

### Media Prioridad (15):
1. Riesgos de null pointer
2. Soft deletes faltantes
3. Invalidación de cache faltante
4. Logging de datos sensibles
5. Preocupaciones de path traversal
6. Rate limiting faltante
7. Números/strings mágicos
8. Nombres inconsistentes
9. Código comentado
10. Y más...

### Baja Prioridad (12+):
1. Namespaces duplicados
2. Comentarios de debug
3. Items TODO
4. Problemas de estilo de código
5. Oportunidades menores de optimización
6. Y más...

---

## 💡 RECOMENDACIONES ADICIONALES

### Para el Módulo de Documentos Específicamente:

1. **Implementar DocumentObserver**: Crear un observer que elimine automáticamente el archivo físico cuando se elimine el registro de la base de datos.

2. **Ruta de Descarga Segura**: Implementar método `download` en DocumentoController que sirva archivos privados verificando permisos.

3. **Validación de Contenido de Archivo**: Verificar que los archivos PDF/JPG sean válidos usando librerías como `fileinfo`.

4. **Límite de Tamaño por Tipo**: Diferentes límites según el tipo (ej. 5MB para imágenes, 10MB para PDFs).

5. **Versión de Documentos**: Permitir versionamiento de documentos para mantener historial de cambios.

6. **Compresión Automática**: Implementar compresión de imágenes al upload usando `Intervention Image`.

7. **Firma Digital**: Implementar capacidad de firmar digitalmente documentos PDF.

8. **OCR para Búsqueda**: Implementar OCR en PDFs escaneados para permitir búsqueda de contenido.

9. **Notas/Comentarios**: Agregar campo para notas o comentarios sobre cada documento.

10. **Workflow de Aprobación**: Implementar workflow para aprobación/rechazo de documentos subidos.

---

## 🚨 Análisis de Calidad y Mejoras v2.0.0

### 🐛 Bugs Corregidos ✅

1. **Falta de autorización en todos los métodos del controlador**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php`
   - **Corrección:** Se agregaron llamadas a `authorize()` en todos los métodos
   - **Métodos actualizados:**
     - `index()` - authorize('viewAny', Documento::class)
     - `list()` - authorize('viewAny', Documento::class)
     - `show()` - authorize('view', $item)
     - `create()` - authorize('create', Documento::class)
     - `store()` - authorize('create', Documento::class)
     - `destroy()` - authorize('delete', $item)

2. **Falta de manejo de transacciones en operaciones de escritura**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:73-113, 124-137`
   - **Corrección:** Se implementó manejo de transacciones DB en métodos `store()` y `destroy()`
   - **Código:**
     ```php
     DB::beginTransaction();
     try {
         // Operaciones
         DB::commit();
     } catch (\Throwable $e) {
         DB::rollBack();
         return back()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
     }
     ```

3. **Validación de pertenencia del documento al trámite**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:120-122`
   - **Corrección:** Se valida que el documento pertenezca al trámite antes de eliminar
   - **Código:**
     ```php
     if ($item->tramite_id !== $tramite->id) {
         abort(404);
     }
     ```

4. **Eliminación de archivo en caso de error en transacción**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:108-110`
   - **Corrección:** Se elimina el archivo subido si ocurre un error durante la creación del registro
   - **Código:**
     ```php
     } catch (\Throwable $e) {
         DB::rollBack();
         if (isset($path)) Storage::disk('public')->delete($path);
         return back()->withInput()->with(['message' => $e->getMessage(), 'alert-type' => 'error']);
     }
     ```

### 🚀 Mejoras Implementadas 🚀

1. **Versionamiento automático de documentos**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:82-99`
   - **Implementación:** Al crear un documento, el sistema automáticamente:
     - Marca como no vigentes los documentos anteriores del mismo tipo
     - Calcula la siguiente versión automáticamente
     - Asigna la versión nueva al documento creado
   - **Código:**
     ```php
     // Marcar anteriores del mismo tipo como no vigentes
     Documento::where('tramite_id', $tramite->id)
         ->where('tipo_doc', $request->tipo_doc)
         ->update(['vigente' => false]);

     // Obtener siguiente versión
     $version = Documento::where('tramite_id', $tramite->id)
         ->where('tipo_doc', $request->tipo_doc)
         ->max('version') + 1;

     Documento::create([
         // ...
         'version' => $version,
         'vigente' => true,
         // ...
     ]);
     ```

2. **Hash SHA-256 para integridad de archivos**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:78-79`
   - **Implementación:** Se calcula el hash SHA-256 de cada archivo subido para verificar integridad
   - **Código:**
     ```php
     $hash = hash_file('sha256', Storage::disk('public')->path($path));
     ```

3. **Ordenamiento por versión descendente**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:37`
   - **Implementación:** Los documentos se listan ordenados por versión descendente (más reciente primero)
   - **Código:**
     ```php
     ->orderBy('version', 'desc')
     ->orderBy('id')
     ```

4. **Campos de auditoría completos**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:99-100`
   - **Implementación:** Se registran `created_by` y `updated_by` en todos los documentos
   - **Código:**
     ```php
     'created_by' => auth()->id(),
     'updated_by' => auth()->id(),
     ```

5. **Uso de FormRequest dedicado**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:69`
   - **Implementación:** Se usa `StoreDocumentoRequest` para centralizar la validación
   - **Beneficio:** Separación de responsabilidades y código más limpio

6. **Inicialización correcta de vista create**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:63-64`
   - **Implementación:** Se inicializa un nuevo objeto Documento para evitar errores en la vista
   - **Código:**
     ```php
     // ✅ ESTA LÍNEA ES OBLIGATORIA
     $item = new Documento();
     ```

7. **Marcado lógico como no vigente en lugar de eliminación física**
   - **Ubicación:** `app/Http/Controllers/DocumentoController.php:127`
   - **Implementación:** En lugar de eliminar el registro, se marca como no vigente
   - **Beneficio:** Mantiene historial de documentos para auditoría

### 📝 Historial de Cambios
### Versión 2.0.0 (Enero 2026)
**Correcciones:**
- Agregado authorize() en todos los métodos del controlador (index, list, show, create, store, destroy)
- Implementado manejo de transacciones DB en store() y destroy()
- Agregada validación de pertenencia de documento al trámite en destroy()
- Implementada eliminación de archivo en caso de error en transacción
- Agregada verificación de integridad con hash SHA-256
- Implementado versionamiento automático de documentos
- Implementado ordenamiento por versión descendente
- Agregados campos de auditoría created_by y updated_by
- Implementado uso de FormRequest StoreDocumentoRequest
- Cambiada eliminación física por marcado lógico (vigente = false) en destroy()
