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

A continuación se detallan posibles bugs, riesgos de seguridad y oportunidades de mejora.

### 🐛 Posibles Bugs / Riesgos

1.  **Archivos Huérfanos (Riesgo Medio):**
    -   **Problema:** Al eliminar un registro de la base de datos (`destroy`), es común olvidar borrar el archivo físico del disco (`Storage::delete($path)`). Esto acumula basura en el servidor.
    -   **Solución:** Asegurar que el método `destroy` o un `Observer` (evento `deleted`) elimine el archivo físico.

2.  **Seguridad de Archivos (Riesgo Alto):**
    -   **Problema:** Si se usa el disco `public`, cualquiera con la URL puede ver el documento sin estar logueado. Documentos como Testimonios o Cédulas de Identidad son sensibles.
    -   **Solución:** Mover el almacenamiento al disco `local` (privado) y crear una ruta de descarga que valide `auth` y `DocumentoPolicy` antes de hacer un `return Storage::download(...)`.

3.  **Validación de Tipos (Riesgo Bajo):**
    -   **Problema:** Permitir cualquier extensión o no validar el contenido real del archivo (MIME type spoofing).
    -   **Solución:** Reforzar reglas: `mimes:pdf,jpg,png`.

### 🚀 Mejoras y Optimizaciones

1.  **Previsualización en el Navegador:**
    -   **Mejora:** En lugar de forzar la descarga, permitir que el navegador abra los PDFs o imágenes en una nueva pestaña (`target="_blank"`) o en un modal dentro del sistema.

2.  **Categorización Obligatoria:**
    -   **Mejora:** Crear un catálogo de `Tipos de Documento` (ej. "CI", "Folio Real", "Plano") y obligar al usuario a seleccionar uno al subir. Esto permitiría validar automáticamente si un trámite tiene todos los requisitos (ej. "No se puede finalizar sin Folio Real").

3.  **Compresión de Imágenes:**
    -   **Mejora:** Si los usuarios suben fotos de 10MB desde celulares, el servidor se llenará rápido. Implementar una compresión automática (ej. `Intervention Image`) al subir imágenes.
```

<!--
[PROMPT_SUGGESTION]Genera el código para un DocumentoObserver que se encargue de eliminar automáticamente el archivo físico del disco cuando se elimine el registro de la base de datos.[/PROMPT_SUGGESTION]
[PROMPT_SUGGESTION]Crea el código para el método 'download' en el DocumentoController que sirva archivos privados de forma segura verificando permisos.[/PROMPT_SUGGESTION]
