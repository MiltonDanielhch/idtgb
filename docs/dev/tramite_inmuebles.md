# Documentación Técnica - Módulo de Inmuebles del Trámite

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelos Relacionados](#modelos-relacionados)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Policies y Permisos](#policies-y-permisos)
7. [Requests y Validación](#requests-y-validación)
8. [Vistas](#vistas)
9. [Flujo de Trabajo](#flujo-de-trabajo)
10. [Guía para Desarrolladores](#guía-para-desarrolladores)

---

## 🎯 Introducción

El módulo de **Inmuebles del Trámite (`TramiteInmueble`)** es un sub-módulo anidado dentro de la gestión de **Trámites**. Su única responsabilidad es administrar la relación muchos-a-muchos entre un trámite y los inmuebles que son objeto de la transmisión de bienes.

### Propósito
-   Permitir a los operadores **asociar uno o más inmuebles** a un trámite existente.
-   Permitir a los operadores **desvincular inmuebles** de un trámite.
-   Mostrar de forma clara y paginada los inmuebles que forman parte de un trámite.
-   Servir como la tabla pivote que conecta la información central del trámite con el catálogo de inmuebles.

Este módulo no gestiona la creación o edición de los inmuebles en sí (eso lo hace el `InmuebleController`), sino únicamente la **relación** entre ellos y un trámite específico.

---

## 🗄️ Base de Datos

### Migración: `2025_09_22_122800_create_tramite_inmuebles_table.php`

**Tabla:** `tramite_inmuebles`

Esta es una tabla pivote clásica de Laravel.

| Campo | Tipo | Atributos | Descripción |
|---|---|---|---|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único de la relación. |
| `tramite_id` | BIGINT | FK, NOT NULL | Apunta al `id` de la tabla `tramites`. |
| `inmueble_id` | BIGINT | FK, NOT NULL | Apunta al `id` de la tabla `inmuebles`. |
| `created_at` | TIMESTAMP | | Fecha de creación de la asociación. |
| `updated_at` | TIMESTAMP | | Fecha de última actualización. |

**Restricciones:**
-   `tramite_id` y `inmueble_id` están configurados con `cascadeOnDelete`, lo que significa que si un trámite o un inmueble es eliminado, la fila correspondiente en esta tabla pivote también se eliminará automáticamente.
-   La validación a nivel de aplicación previene que se inserte la misma combinación de `tramite_id` e `inmueble_id` más de una vez.

---

## 🧩 Modelos Relacionados

### Modelo Pivote: `TramiteInmueble`
-   **Ubicación:** `app/Models/TramiteInmueble.php`
-   **Descripción:** Modelo simple que representa la tabla pivote.

```php
class TramiteInmueble extends Model
{
    protected $table = 'tramite_inmuebles';

    protected $fillable = [
        'tramite_id',
        'inmueble_id',
    ];

    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function inmueble()
    {
        return $this->belongsTo(Inmueble::class);
    }
}
```

### Modelo Principal: `Tramite`
-   **Ubicación:** `app/Models/Tramite.php`
-   **Descripción:** Define la relación `belongsToMany` que es la base de este módulo.

```php
// En app/Models/Tramite.php
public function inmuebles()
{
    return $this->belongsToMany(
        Inmueble::class,
        'tramite_inmuebles',
        'tramite_id',
        'inmueble_id'
    );
}
```

---

## 🎮 Controlador

### Controlador: `TramiteInmuebleController`
-   **Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php`
-   **Middleware:** `auth`
-   **Responsabilidad:** Gestionar las operaciones CRUD para la asociación de inmuebles a un trámite específico. Siempre opera en el contexto de un `Tramite` que se inyecta por ruta.

**Métodos Principales:**
-   `index(Tramite $tramite)`: Muestra la vista principal (`browse.blade.php`) que contiene la interfaz para listar y buscar los inmuebles del trámite.
-   `list(Tramite $tramite)`: Endpoint para AJAX que devuelve la tabla paginada (`list.blade.php`) de los inmuebles asociados. Permite buscar por número de catastro.
-   `create(Tramite $tramite)`: Muestra un formulario (`create.blade.php`) con un selector para elegir un inmueble. El selector se puebla únicamente con inmuebles que **aún no están asociados** a ese trámite.
-   `store(Request $request, Tramite $tramite)`: Valida y crea la nueva asociación en la tabla `tramite_inmuebles`. Redirige al `index` con un mensaje de éxito.
-   `destroy(Tramite $tramite, TramiteInmueble $item)`: Elimina el registro de la tabla pivote, desvinculando el inmueble del trámite.

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php`

Las rutas están anidadas dentro del prefijo de trámites para mantener el contexto.

```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // ...
    Route::prefix('tramites/{tramite}/inmuebles')->name('admin.tramites.inmuebles.')->group(function () {
        Route::get('/', [TramiteInmuebleController::class, 'index'])->name('index');
        Route::get('/ajax/list', [TramiteInmuebleController::class, 'list'])->name('ajax.list');
        Route::get('/create', [TramiteInmuebleController::class, 'create'])->name('create');
        Route::post('/', [TramiteInmuebleController::class, 'store'])->name('store');
        Route::delete('/{item}', [TramiteInmuebleController::class, 'destroy'])->name('destroy');
    });
    // ...
});
```

| Método | URI | Nombre |
|---|---|---|
| GET | `/admin/tramites/{tramite}/inmuebles` | `admin.tramites.inmuebles.index` |
| GET | `/admin/tramites/{tramite}/inmuebles/ajax/list` | `admin.tramites.inmuebles.ajax.list`|
| GET | `/admin/tramites/{tramite}/inmuebles/create` | `admin.tramites.inmuebles.create` |
| POST | `/admin/tramites/{tramite}/inmuebles` | `admin.tramites.inmuebles.store` |
| DELETE | `/admin/tramites/{tramite}/inmuebles/{item}`| `admin.tramites.inmuebles.destroy`|

---

## 🔒 Policies y Permisos

### Policy: `TramiteInmueblePolicy`
-   **Ubicación:** `app/Policies/TramiteInmueblePolicy.php`
-   **Descripción:** Controla qué usuarios pueden realizar acciones en este módulo. Se basa en los permisos de Voyager.

| Método Policy | Permiso Requerido |
|---|---|
| `viewAny()` | `browse_tramite_inmuebles` |
| `view()` | `read_tramite_inmuebles` |
| `create()` | `add_tramite_inmuebles` |
| `update()` | `edit_tramite_inmuebles` |
| `delete()` | `delete_tramite_inmuebles` |

El método `before()` otorga acceso total a los administradores (`browse_admin`).

---

## ✅ Requests y Validación

No existen clases `FormRequest` dedicadas para este módulo. La validación se realiza "inline" en el método `store` del `TramiteInmuebleController`.

**Reglas de Validación Clave (`store`):**
```php
$request->validate([
    'inmueble_id' => 'required|exists:inmuebles,id|unique:tramite_inmuebles,tramite_id,NULL,id,inmueble_id,'.$request->inmueble_id.',tramite_id,'.$tramite->id,
], [
    'inmueble_id.unique' => 'El inmueble ya fue agregado a este trámite.',
]);
```
-   **`required`**: Asegura que se seleccione un inmueble.
-   **`exists:inmuebles,id`**: Valida que el inmueble seleccionado exista en la base de datos.
-   **`unique`**: La regla más importante. Impide que el mismo `inmueble_id` sea agregado dos veces al mismo `tramite_id`.

---

## 🎨 Vistas

**Ubicación:** `resources/views/admin/tramites/inmuebles/`

-   **`browse.blade.php`**: Es la página de aterrizaje del módulo. Contiene el título, los botones de acción ("Agregar Inmueble", "Volver al trámite") y la interfaz para la búsqueda y paginación AJAX.
-   **`list.blade.php`**: Plantilla parcial que renderiza la tabla `<table>` con los inmuebles asociados. Muestra detalles clave del inmueble (catastro, tipo, superficie, valor) y el botón para eliminar la asociación.
-   **`create.blade.php`**: Contiene el formulario de alta. Su elemento principal es un `<select>` (mejorado con `select2`) que lista los inmuebles disponibles para ser añadidos.

---

## 🔄 Flujo de Trabajo

1.  El operador navega a la vista de detalle de un trámite y hace clic en la sección o botón "Administrar Inmuebles".
2.  Es dirigido a `admin.tramites.inmuebles.index`, donde ve la lista de inmuebles ya asociados.
3.  El operador hace clic en "Agregar Inmueble".
4.  El sistema le muestra el formulario de `create.blade.php`, con un selector que contiene todos los inmuebles del sistema **excepto** los que ya están vinculados a ese trámite.
5.  El operador selecciona un inmueble y envía el formulario.
6.  `TramiteInmuebleController@store` valida los datos, crea el registro en `tramite_inmuebles` y redirige de nuevo al listado.
7.  Si el operador desea quitar un inmueble, hace clic en el botón de borrar en la fila correspondiente, lo que dispara una petición `DELETE` a `TramiteInmuebleController@destroy`.

---

## 📝 Guía para Desarrolladores

### Puntos Clave
-   Este es un módulo **anidado**. Toda su lógica depende de un `$tramite` que se pasa por la URL.
-   La lógica de negocio principal para evitar duplicados está en la regla de validación `unique` del método `store`.
-   La consulta para obtener inmuebles "disponibles" en el método `create` es un buen ejemplo de cómo usar `whereDoesntHave` para filtrar resultados basados en una relación.
-   La autorización está correctamente implementada usando Policies, por lo que cualquier nueva acción debe ser añadida a `TramiteInmueblePolicy`.

### Extender el Módulo
Si se necesitara añadir un campo a la tabla pivote (por ejemplo, `porcentaje_propiedad` para indicar qué parte de un inmueble se transfiere en este trámite):
1.  **Migración:** Crear una nueva migración para añadir la columna `porcentaje_propiedad` (DECIMAL, nullable) a la tabla `tramite_inmuebles`.
2.  **Modelo `TramiteInmueble`:** Añadir `porcentaje_propiedad` al array `$fillable`.
3.  **Controlador `TramiteInmuebleController`:**
    -   En `store`, añadir la validación para el nuevo campo (ej. `'porcentaje_propiedad' => 'required|numeric|min:0.01|max:100'`).
    -   Pasar el nuevo valor en el `create()` del modelo.
4.  **Vistas:**
    -   Añadir el campo `porcentaje_propiedad` al formulario en `create.blade.php`.
    -   Mostrar el valor del `porcentaje_propiedad` en una nueva columna en la tabla de `list.blade.php`.
5.  **Modelo `Tramite`:** Para acceder al nuevo campo pivote fácilmente, actualizar la relación `inmuebles()`:
    ```php
    public function inmuebles()
    {
        return $this->belongsToMany(...)
                    ->withPivot('porcentaje_propiedad'); // <-- Añadir esto
    }
    ```
