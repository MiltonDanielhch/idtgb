# Documentación Técnica - Módulo de Personas

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

**Scopes:**
-   `scopeSearch($query, $search)`: Centraliza la lógica de búsqueda optimizada por nombre completo, CI, NIT, teléfono, etc.

---

## 🎮 Controlador

### Controlador: `PersonController`
**Ubicación:** `app/Http/Controllers/PersonController.php`

**Middleware:** `auth`

**Métodos Principales:**
-   `index()`: Muestra la vista principal del listado (`browse.blade.php`).
-   `list()`: Endpoint para AJAX que retorna la tabla de personas (`list.blade.php`). Utiliza el scope `scopeSearch()` para búsquedas optimizadas.
-   `show(Person $person)`: Muestra la vista de detalle (`read.blade.php`).
-   `create()`: Muestra el formulario de alta (`edit-add.blade.php`).
-   `store(StorePersonRequest $request)`: Valida y guarda una nueva persona. Gestiona la subida de imagen. Maneja errores con Log.
-   `edit(Person $person)`: Muestra el formulario de edición con los datos cargados.
-   `update(UpdatePersonRequest $request, Person $person)`: Valida y actualiza una persona existente. Maneja la eliminación de imágenes mediante checkbox `remove_image`. Usa transacciones de BD.
-   `destroy(Person $person)`: Realiza el borrado lógico (soft delete) de la persona. Verifica que no tenga dependencias (trámites asociados).
-   `storeImage()`: Método privado para manejar la subida y eliminación de la imagen de perfil en `storage`.

**Lógica Destacada:**
-   **Autorización:** Todas las acciones están protegidas mediante `Policies` (`$this->authorize(...)`).
-   **Validación:** Delega la validación a los Form Requests `StorePersonRequest` y `UpdatePersonRequest`.
-   **Rendimiento:** Carga relaciones de forma anticipada (`with()`, `load()`) para evitar el problema N+1. Usa scope `scopeSearch()` para búsquedas optimizadas.
-   **Seguridad:** El método `update` está envuelto en una transacción de base de datos.
-   **Manejo de Errores:** En `store()` usa Log para registrar errores y muestra mensajes genéricos al usuario.
-   **Protección de Dependencias:** En `destroy()` verifica que no existan trámites asociados antes de eliminar.

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
-   **Unicidad Compuesta (CI + Complemento):** Implementada usando `Rule::unique()` con cláusula `where` para verificar la unicidad de la tupla `(ci, ci_complemento)`. En `UpdatePersonRequest` se excluye el ID de la persona actual.
-   **Mensajes y Atributos:** Se definen mensajes y nombres de atributos personalizados para una experiencia de usuario clara.

**Validación de Unicidad Compuesta:**
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

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/people/`

-   **`browse.blade.php`**: La vista principal que contiene los controles de búsqueda, paginación y el botón "Añadir Nuevo". Carga el listado vía AJAX.
-   **`list.blade.php`**: Plantilla parcial que renderiza la tabla de personas con sus datos y botones de acción.
-   **`edit-add.blade.php`**: Formulario unificado para crear y editar. Usa JavaScript para cambiar dinámicamente los campos requeridos según se elija "Natural" o "Jurídica". Incluye checkbox "Eliminar imagen actual" para edición.
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
-   **Scope de Búsqueda:** Usar el scope `Person::search($search)` para búsquedas optimizadas en lugar de construir la consulta manualmente.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v2.0.0 (20 de enero de 2026) ✅

Todos los bugs identificados en el análisis original han sido corregidos exitosamente. El módulo de Personas ahora cuenta con:

- ✅ Búsqueda optimizada con scope `scopeSearch()` en el modelo
- ✅ Manejo de errores mejorado con Log en `store()` y `update()`
- ✅ Validación de unicidad compuesta (CI + complemento) implementada
- ✅ Protección contra eliminación de personas con dependencias
- ✅ Eliminación de imágenes con checkbox `remove_image`

### 🐛 Bugs Corregidos (5/5) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Búsqueda ineficiente y propensa a errores SQL | ✅ Corregido | `PersonController.php:list()`, `Person.php:scopeSearch()` |
| 2 | Manejo de errores débil en `store()` | ✅ Corregido | `PersonController.php:store()` |
| 3 | Validación de unicidad compuesta (CI + complemento) | ✅ Corregido | `StorePersonRequest.php`, `UpdatePersonRequest.php` |
| 4 | Protección contra eliminación con dependencias | ✅ Corregido | `PersonController.php:destroy()` |
| 5 | Inconsistencia en borrado de imágenes | ✅ Corregido | `PersonController.php:update()`, `edit-add.blade.php` |

### 🚀 Mejoras Implementadas ✅

- ✅ **Scope `scopeSearch()`**: Centraliza la lógica de búsqueda en el modelo `Person`, haciéndola reutilizable y optimizada.
- ✅ **Manejo de Errores con Log**: Los métodos `store()` y `update()` registran errores detallados en Log y muestran mensajes genéricos al usuario.
- ✅ **Validación de Unicidad Compuesta**: Permite registrar personas con el mismo CI pero diferentes complementos correctamente.
- ✅ **Protección de Integridad Referencial**: Verifica que no existan trámites asociados antes de eliminar una persona.
- ✅ **Eliminación de Imágenes**: El método `update()` maneja correctamente el checkbox `remove_image` para eliminar imágenes existentes.

### 📝 Historial de Cambios

### v2.0.0 (20 de enero de 2026)
**Correcciones Completadas (5/5):**
- ✅ Bug #1: Búsqueda optimizada - controlador ahora usa scope `scopeSearch()`
- ✅ Bug #2: Manejo de errores mejorado en `store()` con Log y mensaje genérico
- ✅ Bug #3: Validación de unicidad compuesta (CI + complemento) implementada
- ✅ Bug #4: Protección contra eliminación de dependencias implementada en `destroy()`
- ✅ Bug #5: Inconsistencia en borrado de imágenes resuelta con checkbox `remove_image`

**Cambios en Código:**
- `app/Models/Person.php`: Agregado scope `scopeSearch()` para búsquedas optimizadas
- `app/Http/Requests/StorePersonRequest.php`: Agregado `use Illuminate\Validation\Rule;` y validación de unicidad compuesta
- `app/Http/Requests/UpdatePersonRequest.php`: Agregado `use Illuminate\Validation\Rule;` y validación de unicidad compuesta con `ignore($personId)`
- `app/Http/Controllers/PersonController.php`: 
  - Actualizado método `list()` para usar scope `search()`
  - Mejorado manejo de errores en `store()` con Log
  - Actualizado método `update()` para manejar eliminación de imágenes con checkbox `remove_image`
  - Agregada verificación de dependencias en `destroy()`
