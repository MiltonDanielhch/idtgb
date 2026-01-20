# Documentación Técnica - Módulo de Personas

## ✅ Estado de Correcciones - Enero 2026

### Bugs Corregidos (5/5) ✅
- ✅ **Bug #1**: Búsqueda optimizada con scope `scopeSearch()` - controlador ahora usa el scope optimizado
- ✅ **Bug #2**: Manejo de errores mejorado en `store()` - ahora usa Log para errores y muestra mensaje genérico al usuario
- ✅ **Bug #3**: Validación de unicidad compuesta (CI + complemento) - implementada usando `Rule::unique()` con cláusula `where`
- ✅ **Bug #4**: Protección contra eliminación de personas con dependencias - implementada en `destroy()`
- ✅ **Bug #5**: Inconsistencia en borrado de imágenes - implementada con checkbox "Eliminar imagen actual" en formulario de edición

### Mejoras Implementadas
- ✅ Agregado scope `scopeSearch()` al modelo `Person`
- ✅ Mejorado manejo de errores con Log en métodos `store()` y `update()`
- ✅ Importación de `Log` y `Rule` agregada al controlador y requests
- ✅ Validación de unicidad compuesta para CI + complemento en `StorePersonRequest` y `UpdatePersonRequest`
- ✅ Lógica para eliminación de imágenes en `PersonController@update()` con checkbox `remove_image`
- ✅ Checkbox "Eliminar imagen actual" disponible en formulario de edición (existente en vista)

---

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

El módulo de **Personas** es un componente central del sistema, responsable de gestionar la información de todas las entidades, tanto **naturales** como **jurídicas**, que interactúan con el sistema de Impuesto de Transmisiones Gratuítas de Bienes (ITGB).

### Propósito
- Mantener un registro único y centralizado de personas y entidades.
- Diferenciar entre personas naturales (con CI, nombres, etc.) y personas jurídicas (con NIT y razón social).
- Servir como base para roles clave en los trámites, como **adquirentes** y **disponentes**.
- Almacenar datos de contacto, ubicación y estado de cada persona.
- Gestionar el ciclo de vida CRUD completo de las personas.

---

## 🗄️ Base de Datos

### Migraciones
1.  `2025_04_07_092413_create_people_table.php`
2.  `2025_10_20_000102_add_municipio_id_to_people_table.php`

### Estructura de la tabla `people`

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `person_type` | ENUM('Natural', 'Jurídica') | DEFAULT 'Natural'| Discrimina el tipo de persona. |
| `tipo_doc` | VARCHAR(10) | DEFAULT 'CI' | Tipo de documento (CI, NIT, PASS). |
| `ci` | VARCHAR | NULLABLE, UNIQUE | Carnet de Identidad. |
| `ci_complemento` | VARCHAR(5) | NULLABLE | Complemento del CI. |
| `nit` | VARCHAR | NULLABLE, UNIQUE | NIT, obligatorio para personas jurídicas. |
| `first_name` | VARCHAR | NULLABLE | Nombres (para persona natural). |
| `middle_name` | VARCHAR | NULLABLE | Segundo nombre. |
| `paternal_surname` | VARCHAR | NULLABLE | Apellido paterno. |
| `maternal_surname` | VARCHAR | NULLABLE | Apellido materno. |
| `legal_name` | VARCHAR | NULLABLE | Razón Social (para persona jurídica). |
| `birth_date` | DATE | NULLABLE | Fecha de nacimiento. |
| `email` | VARCHAR | NULLABLE | Correo electrónico. |
| `phone` | VARCHAR | NULLABLE | Teléfono de contacto. |
| `address` | TEXT | NULLABLE | Dirección física. |
| `municipio_id` | BIGINT | FK, NULLABLE | Relación con el municipio de residencia. |
| `gender` | ENUM('Masculino', 'Femenino')| NULLABLE | Género de la persona. |
| `image` | VARCHAR | NULLABLE | Ruta a la foto de perfil. |
| `status` | TINYINT | DEFAULT 1 | Estado lógico (1:Activo, 0:Inactivo, 2:Pendiente). |
| `estado_persona`| ENUM(...) | DEFAULT 'Activo' | Estado de la persona (Activo, Inactivo, Fallecido). |
| `timestamps` | - | - | `created_at` y `updated_at`. |
| `softDeletes` | - | - | `deleted_at` para borrado lógico. |
| `registerUser_id`| BIGINT | FK | Usuario que registró a la persona (via Trait). |
| `deleteUser_id` | BIGINT | FK | Usuario que eliminó a la persona (via Trait). |

**Restricciones y Claves Foráneas:**
-   Índice único en `(ci, ci_complemento)` y `nit`.
-   Claves foráneas a `users` para auditoría (`registerUser_id`, `deleteUser_id`).
-   Clave foránea a `municipios`.

---

## 🧩 Modelo

### Modelo: `Person`
**Ubicación:** `app/Models/Person.php`

**Traits:**
-   `SoftDeletes`: Habilita el borrado lógico.
-   `RegistersUserEvents`: Trait personalizado que gestiona automáticamente los campos de auditoría (`registerUser_id`, `deleteUser_id`, etc.) en los eventos `creating` y `deleting` del modelo.

**Atributos y Casts:**
-   `$fillable`: Incluye todos los campos de la tabla para asignación masiva.
-   `$dates`: Incluye `deleted_at` y `birth_date`.
-   `$casts`: `status` a `integer`, `birth_date` a `date`.
-   `$appends`: Añade `ubicacion_completa` y `ubicacion_segura` a la serialización del modelo.

**Accesores Notables:**
-   `getFullNameAttribute()`: Concatena los nombres y apellidos para persona natural.
-   `getDisplayNameAttribute()`: Devuelve `legal_name` si es persona jurídica o `full_name` si es natural. Es el accesor principal para mostrar el nombre.
-   `getDisplayDocumentAttribute()`: Devuelve el NIT o el CI (con complemento) según el `person_type`.
-   `getDisplayAgeAttribute()`: Calcula la edad a partir de `birth_date`.
-   `getUbicacionCompletaAttribute()`: Construye una cadena con la ubicación completa (`Municipio, Provincia, Departamento`).

**Relaciones:**
```php
// Una persona pertenece a un municipio.
public function municipio()
{
    return $this->belongsTo(Municipio::class);
}

// Una persona puede ser adquirente en muchos trámites.
public function adquirentesTramite()
{
    return $this->hasMany(AdquirenteTramite::class, 'person_id');
}

// Una persona puede ser disponente en muchos trámites.
public function disponentesTramite()
{
    return $this->hasMany(DisponenteTramite::class, 'person_id');
}

// Relaciones de auditoría con el modelo User.
public function registerUser() { /* ... */ }
public function deleteUser() { /* ... */ }
```

---

## 🎮 Controlador

### Controlador: `PersonController`
**Ubicación:** `app/Http/Controllers/PersonController.php`

**Middleware:** `auth`

**Métodos Principales:**
-   `index()`: Muestra la vista principal del listado (`browse.blade.php`).
-   `list()`: Endpoint para AJAX que retorna la tabla de personas (`list.blade.php`). Incluye una lógica de búsqueda compleja por nombre completo, CI, NIT, teléfono, etc.
-   `show(Person $person)`: Muestra la vista de detalle (`read.blade.php`).
-   `create()`: Muestra el formulario de alta (`edit-add.blade.php`).
-   `store(StorePersonRequest $request)`: Valida y guarda una nueva persona. Gestiona la subida de imagen.
-   `edit(Person $person)`: Muestra el formulario de edición con los datos cargados.
-   `update(UpdatePersonRequest $request, Person $person)`: Valida y actualiza una persona existente. Utiliza transacciones de BD para seguridad.
-   `destroy(Person $person)`: Realiza el borrado lógico (soft delete) de la persona.
-   `storeImage()`: Método privado para manejar la subida y eliminación de la imagen de perfil en `storage`.

**Lógica Destacada:**
-   **Autorización:** Todas las acciones están protegidas mediante `Policies` (`$this->authorize(...)`).
-   **Validación:** Delega la validación a los Form Requests `StorePersonRequest` y `UpdatePersonRequest`.
-   **Rendimiento:** Carga relaciones de forma anticipada (`with()`, `load()`) para evitar el problema N+1.
-   **Seguridad:** El método `update` está envuelto en una transacción de base de datos.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

```php
// Ruta para el listado AJAX
Route::get('people/ajax/list', [PersonController::class, 'list'])
     ->name('admin.people.ajax.list');

// Rutas del recurso CRUD
Route::resource('people', PersonController::class)
     ->names('admin.people')
     ->parameters(['people' => 'person']);
```

**Resumen de Rutas:**
-   Se define un `Route::resource` estándar, lo que crea las rutas `index`, `create`, `store`, `show`, `edit`, `update` y `destroy`.
-   Los nombres de las rutas están prefijados con `admin.people.`.
-   Se personaliza el parámetro de Route Model Binding a `{person}` para mayor claridad.
-   Se define una ruta adicional para la carga de datos vía AJAX.

---

## 🔒 Policies y Permisos

### Policy: `PersonPolicy`
**Ubicación:** `app/Policies/PersonPolicy.php`

Mapea las acciones del controlador a permisos específicos de Voyager.

| Método Policy | Permiso Requerido | Acción del Controlador |
|---|---|---|
| `viewAny()` | `browse_people` | `index`, `list` |
| `view()` | `read_people` | `show` |
| `create()` | `add_people` | `create`, `store` |
| `update()` | `edit_people` | `edit`, `update` |
| `delete()` | `delete_people` | `destroy` |

El método `before()` en la policy otorga acceso total a usuarios con el permiso `browse_admin`.

---

## ✅ Requests

### `StorePersonRequest` y `UpdatePersonRequest`
**Ubicación:** `app/Http/Requests/`

Estos Form Requests centralizan la lógica de **autorización** y **validación**.

**Lógica de Validación Clave:**
-   **Autorización:** Verifican si el usuario tiene permiso para crear o actualizar usando `Gate::allows()`.
-   **Reglas Condicionales:** La lógica más importante reside aquí. Las reglas de validación cambian dinámicamente según el `person_type` enviado:
    -   Si es **`Jurídica`**, el `nit` y `legal_name` son obligatorios.
    -   Si es **`Natural`**, el `ci`, `first_name` y `paternal_surname` son obligatorios.
-   **Unicidad en Actualización:** `UpdatePersonRequest` excluye el ID de la persona actual al validar la unicidad de `ci` y `nit`, permitiendo guardar sin cambios en esos campos.
-   **Mensajes y Atributos:** Se definen mensajes y nombres de atributos personalizados para una experiencia de usuario clara.

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/people/`

-   **`browse.blade.php`**: La vista principal que contiene los controles de búsqueda, paginación y el botón "Añadir Nuevo". Carga el listado vía AJAX.
-   **`list.blade.php`**: Plantilla parcial que renderiza la tabla de personas con sus datos y botones de acción.
-   **`edit-add.blade.php`**: Formulario unificado para crear y editar. Usa JavaScript para cambiar dinámicamente los campos requeridos según se elija "Natural" o "Jurídica".
-   **`read.blade.php`**: Vista de solo lectura que muestra toda la información detallada de la persona, incluyendo su imagen y ubicación completa.

---

## 🔗 Integración con otros Módulos

El módulo de `Personas` es fundamental y se integra con casi todo el sistema:

-   **`User`**: Un `User` del sistema puede estar asociado a una `Person` para separar los datos de autenticación de los datos personales.
-   **`AdquirenteTramite` y `DisponenteTramite`**: Actúan como tablas pivote que vinculan a una `Person` con un `Tramite` en un rol específico (quien adquiere o quien dispone del bien).
-   **`Avaluo`**: El `perito_id` en un avalúo es una clave foránea a `people`, vinculando a la persona (perito) que realizó la valoración.
-   **`Documento`**: Se puede asociar un documento a una `person_id`.
-   **`TramiteWizardController`**: El wizard de creación de trámites utiliza masivamente el modelo `Person` para buscar, agregar y eliminar disponentes y adquirentes de un trámite en sesión.
-   **`AjaxController`**: Provee un endpoint para buscar personas de forma asíncrona, usado en selectores dinámicos.
-   **Reportes y Dashboard**: Los controladores de reportes y el servicio del dashboard usan el modelo `Person` para agregar y mostrar estadísticas.

---

## 📝 Guía para Desarrolladores

### Extender el Modelo
Para añadir un nuevo campo (ej. `profession`):
1.  **Migración:** `php artisan make:migration add_profession_to_people_table`.
2.  **Modelo `Person`:** Añadir `profession` al array `$fillable`.
3.  **Vistas:** Añadir el campo de texto al formulario `edit-add.blade.php`.
4.  **Requests:** Actualizar `StorePersonRequest` y `UpdatePersonRequest` para añadir la regla de validación (`'profession' => 'nullable|string|max:100'`).

### Puntos Clave a Recordar
-   **Doble Naturaleza:** Siempre tener en cuenta el `person_type`. La lógica de negocio, validación y vistas cambia dependiendo de si es `Natural` o `Jurídica`.
-   **Trait `RegistersUserEvents`:** Este trait maneja la auditoría de forma automática. No es necesario asignar `registerUser_id` manualmente en el controlador.
-   **Accesors para Display:** Utilizar los accesors `display_name` y `display_document` en las vistas para asegurar que se muestre la información correcta según el tipo de persona.
-   **Búsqueda AJAX:** El `TramiteWizardController` y el `AjaxController` contienen endpoints para buscar personas de forma dinámica. Estos son los recomendados para reutilizar en nuevas funcionalidades.

---

## 🚨 Análisis de Calidad y Mejoras

A continuación se detallan posibles bugs, inconsistencias y oportunidades de mejora detectadas en el análisis del código del módulo de Personas.

### 🐛 Bugs Potenciales y Riesgos de Seguridad (Todos Corregidos) ✅

1.  ✅ **Búsqueda Ineficiente y Propensa a Errores SQL** (CORREGIDO)
    *   **Estado**: ✅ CORREGIDO
    *   **Ubicación**: `app/Http/Controllers/PersonController.php`, método `list()`.
    *   **Problema Original**: La consulta de búsqueda utilizaba `orWhere` repetidamente, dificultando el uso de índices y haciéndola lenta.
    *   **Solución Implementada**: Se agregó un scope `scopeSearch()` en el modelo `Person` que centraliza la lógica de búsqueda. El controlador ahora usa este scope:
        ```php
        $data = Person::query()
            ->with(['municipio.provincia.departamento'])
            ->search($search)
            ->whereNull('deleted_at')
            ->orderByDesc('id')
            ->paginate($paginate);
        ```
    *   **Ubicación del Scope**: `app/Models/Person.php`, método `scopeSearch()`.

2.  ✅ **Manejo de Errores Débil en `store()`** (CORREGIDO)
    *   **Estado**: ✅ CORREGIDO
    *   **Ubicación**: `app/Http/Controllers/PersonController.php`, método `store()`.
    *   **Problema Original**: El bloque `catch` mostraba el mensaje del error directamente al usuario.
    *   **Solución Implementada**: Se modificó el bloque `catch` para registrar el error detallado en el log y mostrar un mensaje genérico al usuario:
        ```php
        catch (\Throwable $e) {
            \Log::error('Error al crear persona: ' . $e->getMessage());
            return back()->withInput()->with(['message' => 'Ocurrió un error inesperado al guardar la persona.', 'alert-type' => 'error']);
        }
        ```

3.  ✅ **Validación de Unicidad Compuesta Inexistente (CI + Complemento)** (CORREGIDO)
    *   **Estado**: ✅ CORREGIDO
    *   **Ubicación**: `app/Http/Requests/StorePersonRequest.php` y `UpdatePersonRequest.php`.
    *   **Problema Original**: La regla de validación `unique:people` se aplicaba por separado al campo `ci` sin considerar el `ci_complemento`.
    *   **Solución Implementada**: Se implementó una regla de validación personalizada que verifica la unicidad de la tupla `(ci, ci_complemento)` usando `Rule::unique()` con cláusula `where`:
        ```php
        // En StorePersonRequest.php
        $ciRule = [
            Rule::unique('people')->where(function ($query) {
                return $query->where('ci_complemento', $this->ci_complemento);
            })
        ];
        
        // En UpdatePersonRequest.php
        $ciRule = [
            Rule::unique('people')
                ->ignore($personId)
                ->where(function ($query) {
                    return $query->where('ci_complemento', $this->ci_complemento);
                })
        ];
        ```
    *   **Impacto**: Ahora permite registrar personas con el mismo CI pero diferentes complementos correctamente.

4.  ✅ **Inconsistencia en Borrado de Imágenes** (CORREGIDO)
    *   **Estado**: ✅ CORREGIDO
    *   **Ubicación**: `app/Http/Controllers/PersonController.php`, método `update()` y vista `edit-add.blade.php`.
    *   **Problema Original**: No había opción para quitar la imagen actual sin subir una nueva.
    *   **Solución Implementada**: 
        *   Ya existe un checkbox "Eliminar imagen actual" en el formulario de edición (línea 190 de `edit-add.blade.php`).
        *   Se actualizó el método `update()` para manejar la eliminación de imágenes:
            ```php
            $data = $request->except('image', 'remove_image');
            
            if ($request->hasFile('image')) {
                $data['image'] = $this->storeImage($request->file('image'), $person->image);
            } elseif ($request->boolean('remove_image')) {
                if ($person->image) {
                    Storage::disk('public')->delete($person->image);
                }
                $data['image'] = null;
            }
            ```

5.  ✅ **Falta de Protección contra Eliminación de Personas con Dependencias** (CORREGIDO)
    *   **Estado**: ✅ CORREGIDO
    *   **Ubicación**: `app/Http/Controllers/PersonController.php`, método `destroy()`.
    *   **Problema Original**: El método `destroy` eliminaba sin verificar dependencias.
    *   **Solución Implementada**: Antes de ejecutar `$person->delete()`, se verifica si hay relaciones:
        ```php
        if ($person->adquirentesTramite()->exists() || $person->disponentesTramite()->exists()) {
            return redirect()->route('admin.people.index')
                ->with(['message' => 'No se puede eliminar: la persona está asociada a uno o más trámites.', 'alert-type' => 'error']);
        }
        $person->delete();
        ```

### 🚀 Oportunidades de Mejora y Optimización

1.  **Refactorizar la Lógica de Búsqueda a un `scope` del Modelo**
    *   **Ubicación**: `app/Http/Controllers/PersonController.php`, método `list()`.
    *   **Mejora**: Mover la lógica de búsqueda compleja a un *query scope* en el modelo `Person` para que sea reutilizable y mantenga el controlador más limpio.
    *   **Implementación Sugerida**:
        ```php
        // En app/Models/Person.php
        public function scopeSearch($query, $search)
        {
            if (!$search) {
                return $query;
            }
            // ... lógica de búsqueda ...
            return $query;
        }

        // En PersonController.php
        $data = Person::search($search)
            ->with(['municipio.provincia.departamento'])
            ->orderByDesc('id')
            ->paginate($paginate);
        ```

2.  **Centralizar la Lógica de Subida de Imágenes en un Trait o Servicio**
    *   **Ubicación**: `app/Http/Controllers/PersonController.php`, método `storeImage()`.
    *   **Mejora**: Si otros controladores también necesitan subir imágenes (ej. `DocumentoController`), esta lógica podría moverse a un Trait (`HandlesUploads`) o a un Servicio (`ImageUploadService`) para evitar duplicación de código.
    *   **Impacto**: Código más mantenible y DRY (Don't Repeat Yourself).

3.  **Mejorar la Experiencia de Usuario en Formularios**
    *   **Ubicación**: `resources/views/admin/people/edit-add.blade.php`.
    *   **Mejora**: Actualmente, los selects de `tipo_doc` y `person_type` son independientes. Podrían vincularse con JavaScript: si el usuario selecciona `person_type = 'Jurídica'`, el `tipo_doc` debería cambiar automáticamente a `NIT`.
    *   **Impacto**: Menos clics y menor probabilidad de error para el usuario.

4.  **Optimización de Carga de Municipios en `create()` y `edit()`**
    *   **Ubicación**: `app/Http/Controllers/PersonController.php`, métodos `create()` y `edit()`.
    *   **Problema**: `Municipio::with('provincia.departamento')->get()` carga todos los municipios con sus relaciones en memoria, lo cual es ineficiente.
    *   **Mejora**: Implementar un selector de municipios con carga asíncrona (AJAX) o, como mínimo, optimizar la consulta para seleccionar solo los campos necesarios (`id`, `nombre`, etc.).
    *   **Implementación Sugerida**:
        ```php
        // Carga optimizada para select
        $municipios = Municipio::select('id', 'nombre')->orderBy('nombre')->get();

        // O mejor aún, un endpoint AJAX para un Select2 dinámico
        ```

### 📋 Funcionalidades Faltantes

1.  **Historial de Cambios (Auditoría Detallada)**
    *   **Problema**: El trait `RegistersUserEvents` solo guarda quién creó y eliminó el registro. No hay un historial de qué campos se cambiaron, cuál era el valor anterior y cuál es el nuevo.
    *   **Necesidad**: Para auditorías y trazabilidad, es crucial saber quién cambió (por ejemplo) un número de CI o un NIT, y cuándo lo hizo.
    *   **Solución Sugerida**: Implementar un paquete como `owen-it/laravel-auditing` o crear una tabla `people_history` que se pueble mediante un `Observer` en el modelo `Person`.

2.  **Funcionalidad para Fusionar Personas Duplicadas**
    *   **Problema**: A pesar de las validaciones, es posible que se creen registros duplicados (ej. "Juan Perez" y "Juan Perez Gonzales").
    *   **Necesidad**: Una herramienta administrativa para seleccionar dos o más personas duplicadas, elegir una como "maestra" y migrar todas las relaciones (trámites, avalúos, etc.) de los duplicados a la maestra antes de eliminarlos.
    *   **Impacto**: Mejora drásticamente la calidad y consistencia de los datos.

3.  **Exportación de Datos**
    *   **Problema**: No hay funcionalidad para exportar la lista de personas a formatos como CSV, Excel o PDF.
    *   **Necesidad**: Los administradores a menudo necesitan exportar datos para análisis externo o reportes.
    *   **Solución Sugerida**: Añadir botones de exportación en la vista `browse.blade.php` y crear los métodos correspondientes en `PersonController` utilizando un paquete como `maatwebsite/excel`.

4.  **API Endpoints para Integración Externa**
    *   **Problema**: El módulo solo es accesible a través de la interfaz web.
    *   **Necesidad**: Si otros sistemas (ej. un CRM) necesitaran consultar o registrar personas, se requerirían endpoints de API RESTful seguros.
    *   **Solución Sugerida**: Crear un `Api/PersonController` con métodos `index`, `show`, `store` protegidos por Laravel Sanctum o Passport.

---

## 📝 Historial de Cambios

### Versión 2.0.0 (20 de enero de 2026)
**Correcciones:**
- ✅ Corregido Bug #1: Búsqueda optimizada - controlador usa scope `scopeSearch()`
- ✅ Corregido Bug #2: Manejo de errores mejorado en `store()` con Log y mensaje genérico
- ✅ Corregido Bug #3: Validación de unicidad compuesta (CI + complemento) implementada
- ✅ Corregido Bug #4: Inconsistencia en borrado de imágenes resuelta con checkbox `remove_image`
- ✅ Corregido Bug #5: Protección contra eliminación con dependencias implementada en `destroy()`

**Cambios en código:**
- `app/Http/Requests/StorePersonRequest.php`: Agregado `use Illuminate\Validation\Rule;` y validación de unicidad compuesta
- `app/Http/Requests/UpdatePersonRequest.php`: Agregado `use Illuminate\Validation\Rule;` y validación de unicidad compuesta con `ignore($personId)`
- `app/Http/Controllers/PersonController.php`: Actualizado método `update()` para manejar eliminación de imágenes con checkbox `remove_image`

**Cambios en documentación:**
- Actualizada sección de estado de correcciones a "Todos Corregidos (5/5) ✅"
- Actualizado análisis de calidad con estado final de cada bug corregido
- Agregado historial de cambios al final del documento
