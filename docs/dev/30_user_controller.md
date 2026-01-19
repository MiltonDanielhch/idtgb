# UserController - Documentación Técnica

## 1. Información General

**Archivo:** `app/Http/Controllers/UserController.php`

**Namespace:** `App\Http\Controllers`

**Middleware:** `auth` (requiere autenticación)

**Propósito:** Gestión de usuarios del sistema, integrado con el panel de administración Voyager.

---

## 2. Dependencias

```php
use Illuminate\Http\Request;
use App\Models\Person;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
```

---

## 3. Constructor

```php
public function __construct()
{
    $this->middleware('auth');
}
```

Aplica middleware de autenticación a todos los métodos del controlador.

---

## 4. Métodos

### 4.1 `list()` - Listado de usuarios

**Ruta:** `GET /admin/users/ajax/list` → `voyager.users.ajax.list`

**Propósito:** Retorna la vista con el listado paginado de usuarios.

**Parámetros Request:**
- `search` (opcional): Término de búsqueda (busca en id, name, email)
- `paginate` (opcional): Cantidad de registros por página (default: 10)

**Lógica:**
1. Obtiene el rol del usuario autenticado
2. Aplica filtros de búsqueda si se proporciona `search`
3. Si el rol no es admin (id != 1), excluye usuarios con rol admin
4. Ordena por id descendente
5. Pagina los resultados
6. Retorna vista `vendor.voyager.users.list`

**Retorno:** Vista `vendor.voyager.users.list` con variable `$data`

**⚠️ Vulnerabilidad SQL Injection (líneas 38-40):**
```php
$query->OrWhereRaw($search ? "id = '$search'" : 1)
    ->OrWhereRaw($search ? "name like '%$search%'" : 1)
    ->OrWhereRaw($search ? "email like '%$search%'" : 1);
```
Usa interpolación directa de `$search` en query raw. **Solución:** Usar parámetros vinculados.

---

### 4.2 `store(Request $request)` - Crear usuario

**Ruta:** `POST /admin/users/store` → `voyager.users.store`

**Propósito:** Crea un nuevo usuario en el sistema.

**Parámetros Request:**
- `person_id` (requerido): ID de la persona asociada
- `email` (requerido): Email del usuario (único)
- `role_id` (requerido): ID del rol
- `password` (requerido): Contraseña

**Lógica:**
1. Verifica que el email no exista en la base de datos
2. Busca la persona activa correspondiente
3. Inicia transacción de base de datos
4. Crea usuario con:
   - `person_id`: ID de persona
   - `name`: Nombre de la persona (`first_name`)
   - `role_id`: Rol proporcionado
   - `email`: Email proporcionado
   - `avatar`: `'users/default.png'` (fijo)
   - `password`: Hash bcrypt del password
5. Commit y redirect con éxito
6. Rollback en caso de excepción

**Retorno:** Redirect a `voyager.users.index` con mensaje de éxito/error

**Validación:** Manual dentro del método (sin FormRequest)

---

### 4.3 `update(Request $request, $id)` - Actualizar usuario

**Ruta:** `PUT /admin/users/{id}` → `voyager.users.update`

**Propósito:** Actualiza el estado, rol y/o password de un usuario.

**Parámetros Request:**
- `status` (opcional): Checkbox para activar/desactivar
- `role_id` (opcional): Nuevo rol del usuario
- `password` (opcional): Nueva contraseña

**Lógica:**
1. Busca usuario por ID
2. Actualiza `status` a 1 si está presente, 0 si no
3. Si `role_id` está presente, actualiza el rol
4. Si `password` está presente, actualiza con hash bcrypt
5. Commit y redirect con éxito
6. Rollback en caso de excepción

**Retorno:** Redirect a `voyager.users.index` con mensaje de éxito/error

**⚠️ Falta de validación:** No hay validación de los datos de entrada

---

### 4.4 `destroy(Request $request, $id)` - Eliminar usuario

**Ruta:** `DELETE /admin/users/{id}/deleted` → `voyager.users.destroy`

**Propósito:** Realiza soft delete de un usuario (usando SoftDeletes).

**Parámetros URL:**
- `id`: ID del usuario a eliminar

**Lógica:**
1. Busca usuario que no esté previamente eliminado (`deleted_at = null`)
2. Aplica soft delete
3. Commit y redirect con éxito
4. Rollback en caso de excepción

**Retorno:** Redirect a `voyager.users.index` con mensaje de éxito/error

---

## 5. Rutas Definidas

Todas las rutas están dentro del grupo `Route::prefix('admin')` con middleware `['loggin', 'system']`:

| Método | URI | Nombre | Acción |
|--------|-----|--------|--------|
| GET | `/admin/users/ajax/list` | `voyager.users.ajax.list` | `list` |
| POST | `/admin/users/store` | `voyager.users.store` | `store` |
| PUT | `/admin/users/{id}` | `voyager.users.update` | `update` |
| DELETE | `/admin/users/{id}/deleted` | `voyager.users.destroy` | `destroy` |

---

## 6. Modelos Relacionados

### User Model (`app/Models/User.php`)

**Extiende:** `TCG\Voyager\Models\User`

**Traits:** `HasApiTokens`, `HasFactory`, `Notifiable`, `SoftDeletes`, `RegistersUserEvents`

**Fillable:**
```php
protected $fillable = [
    'person_id', 'name', 'role_id', 'email', 'password', 'status',
    'registerUser_id', 'registerRole',
    'deleted_at', 'deleteUser_id', 'deleteRole', 'deleteObservation',
];
```

**Relaciones:**
- `person()`: BelongsTo a `Person` (person_id)

---

### Person Model (`app/Models/Person.php`)

**Tabla:** `people`

**Fillable:** Campos personales (tipo_doc, ci, nombres, apellidos, email, teléfono, dirección, etc.)

**Accesores útiles:**
- `full_name`: Nombre completo concatenado
- `display_name`: Nombre de visualización (razón social para jurídicas)
- `display_document`: CI o NIT según tipo
- `ubicacion_completa`: Ubicación geográfica completa

**Relaciones:**
- `municipio()`: BelongsTo a Municipio
- `registerUser()`: BelongsTo a User
- `deleteUser()`: BelongsTo a User
- `adquirentesTramite()`: HasMany
- `disponentesTramite()`: HasMany

---

## 7. Vistas Asociadas

### `list.blade.php` (`resources/views/vendor/voyager/users/list.blade.php`)

**Componentes:**
- Tabla con paginación
- Columnas: ID, Nombre, Email, Rol, Estado, Acciones
- Imagen de usuario (con crop `-cropped.`)
- Permisos basados en `hasPermission('read_users')`, `edit_users`, `delete_users`
- Integración con vistas de Voyager (`voyager.users.show`, `voyager.users.edit`)

**JavaScript:**
- Manejo de paginación con AJAX
- Función `list(page)` para recargar datos

---

### `edit-add.blade.php` (`resources/views/vendor/voyager/users/edit-add.blade.php`)

**Componentes:**
- Formulario de creación/edición
- Selección de persona con modal de registro rápido
- Campo email (deshabilitado en edición)
- Campo password (opcional en edición)
- Selector de rol (filtrado según rol del usuario actual)
- Toggle de estado (solo en edición)
- Upload de avatar
- Modales para registrar persona nueva

**Dependencias JS:**
- `js/include/person-select.js`
- `js/include/person-register.js`
- Bootstrap Toggle para checkbox estado

**Permisos:**
- `@can('editRoles', $dataTypeContent)` para mostrar selector de rol

---

## 8. Migración de Base de Datos

### `create_users_table.php` (2014_10_12_000000)

**Campos originales de Laravel:**
```php
$table->id();
$table->string('name');
$table->string('email')->unique();
$table->timestamp('email_verified_at')->nullable();
$table->string('password');
$table->rememberToken();
$table->timestamps();
```

**⚠️ Nota:** El modelo User define fillable adicionales (`person_id`, `role_id`, `status`, etc.) que no están en esta migración original. Probablemente existe una migración adicional o la tabla fue modificada manualmente.

---

## 9. Consideraciones de Seguridad

### Vulnerabilidades Identificadas

1. **SQL Injection en método `list()` (líneas 38-40)**
   - Interpolación directa de `$search` en query raw
   - **Solución recomendada:**
   ```php
   $query->when($search, function($q) use ($search) {
       $q->where('id', $search)
         ->orWhere('name', 'like', "%{$search}%")
         ->orWhere('email', 'like', "%{$search}%");
   });
   ```

2. **Falta de validación en método `update()` (líneas 82-102)**
   - No se valida que el usuario exista
   - No se valida el formato del password o email
   - **Solución recomendada:** Crear `UpdateUserRequest`

3. **No hay Policies definidas para User**
   - Solo se verifica permisos de Voyager (`hasPermission`)
   - No hay autorización granular a nivel de modelo

---

## 10. Mejoras Sugeridas

### 10.1 Implementar FormRequests

```php
// app/Http/Requests/StoreUserRequest.php
class StoreUserRequest extends FormRequest
{
    public function rules()
    {
        return [
            'person_id' => 'required|exists:people,id',
            'email' => 'required|email|unique:users,email',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|min:8',
        ];
    }
}

// app/Http/Requests/UpdateUserRequest.php
class UpdateUserRequest extends FormRequest
{
    public function rules()
    {
        $user = $this->route('id');
        return [
            'email' => 'sometimes|email|unique:users,email,'.$user,
            'role_id' => 'sometimes|exists:roles,id',
            'password' => 'sometimes|min:8',
        ];
    }
}
```

### 10.2 Implementar Policy

```php
// app/Policies/UserPolicy.php
class UserPolicy
{
    public function viewAny(User $user)
    {
        return $user->hasPermission('browse_users');
    }

    public function create(User $user)
    {
        return $user->hasPermission('add_users');
    }

    public function update(User $user, User $targetUser)
    {
        // No permitir editar usuarios con rol superior
        return $user->hasPermission('edit_users') 
            && $targetUser->role_id >= $user->role_id;
    }

    public function delete(User $user, User $targetUser)
    {
        return $user->hasPermission('delete_users')
            && $targetUser->id !== $user->id // No auto-eliminación
            && $targetUser->role_id >= $user->role_id;
    }
}
```

### 10.3 Refactorizar búsqueda segura

```php
public function list()
{
    $rol_id = Auth::user()->role_id;
    $search = request('search');
    $paginate = request('paginate', 10);

    $query = User::with(['person'])
        ->when($search, function($q) use ($search) {
            $q->where('id', $search)
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })
        ->when($rol_id != 1, function($q) {
            $q->where('role_id', '!=', 1);
        })
        ->orderBy('id', 'DESC');

    $data = $query->paginate($paginate);
    
    return view('vendor.voyager.users.list', compact('data'));
}
```

---

## 11. Integración con Voyager

El controlador extiende la funcionalidad del panel Voyager:

- Las vistas personalizadas (`list.blade.php`, `edit-add.blade.php`) sobrescriben las vistas por defecto de Voyager
- Las rutas están bajo el prefijo `/admin` dentro del mismo grupo de rutas de Voyager
- El modelo User extiende `TCG\Voyager\Models\User`
- El sistema de permisos de Voyager se usa en las vistas (`hasPermission()`)

---

## 12. Flujo Completo de Usuario

```
1. Registro de Persona (PersonController)
   ↓
2. Creación de Usuario (UserController::store)
   - Selecciona persona existente
   - Define email, password y rol
   ↓
3. Listado (UserController::list)
   - Filtra por búsqueda
   - Paginación
   ↓
4. Edición (UserController::update)
   - Cambio de estado
   - Cambio de rol
   - Cambio de password
   ↓
5. Eliminación (UserController::destroy)
   - Soft delete
```

---

## 13. Referencias

- **Controlador:** `app/Http/Controllers/UserController.php`
- **Modelo:** `app/Models/User.php`
- **Rutas:** `routes/web.php` (líneas 250-255)
- **Vistas:** `resources/views/vendor/voyager/users/*.blade.php`
- **Migración:** `database/migrations/2014_10_12_000000_create_users_table.php`
- **Modelo Persona:** `app/Models/Person.php`
- **Vista lista:** `resources/views/vendor/voyager/users/list.blade.php`
- **Vista formulario:** `resources/views/vendor/voyager/users/edit-add.blade.php`

---

## 14. Análisis Completo: Bugs, Mejoras, Faltas y Optimizaciones

### 14.1 Bugs Críticos

#### BUG #1: SQL Injection en búsqueda de usuarios
**Ubicación:** `app/Http/Controllers/UserController.php:38-40`

**Descripción:**
```php
$query->OrWhereRaw($search ? "id = '$search'" : 1)
    ->OrWhereRaw($search ? "name like '%$search%'" : 1)
    ->OrWhereRaw($search ? "email like '%$search%'" : 1);
```

**Impacto:** Crítico - Permite inyección SQL a través del parámetro `search`. Un atacante podría:
- Leer datos de la base de datos
- Modificar o eliminar datos
- Bypass de autenticación
- Obtener información sensible

**Exploit de ejemplo:**
```
search = ' OR 1=1 UNION SELECT 1,2,3,4,5,6 FROM users-- -
```

**Solución:**
```php
$query->when($search, function($q) use ($search) {
    $q->where('id', $search)
      ->orWhere('name', 'like', "%{$search}%")
      ->orWhere('email', 'like', "%{$search}%");
});
```

**Nota:** Este mismo bug existe en `RoleController.php:30-32`

---

#### BUG #2: Posible error en actualización de contraseña vacía
**Ubicación:** `app/Http/Controllers/UserController.php:97-102`

**Descripción:**
```php
if($request->password)
{
    $user->update([
        'password' => bcrypt($request->password)
    ]);
}
```

**Impacto:** Medio - Si se envía una contraseña vacía (string vacío), la condición `if($request->password)` no se cumple, pero si se envía `null` o el campo existe pero está vacío, podría causar comportamiento inesperado.

**Solución:**
```php
if(!empty($request->password) && strlen($request->password) >= 8) {
    $user->update([
        'password' => bcrypt($request->password)
    ]);
}
```

---

#### BUG #3: Error potencial si el usuario no existe en update/destroy
**Ubicación:** `app/Http/Controllers/UserController.php:86`, `117`

**Descripción:**
```php
// En update
$user = User::where('id', $id)->first();
$user->update([...]); // Si $user es null, esto causa error

// En destroy
$user = User::where('id', $id)->where('deleted_at', null)->first();
$user->delete(); // Si $user es null, esto causa error
```

**Impacto:** Medio - Error 500 si el ID no existe.

**Solución:**
```php
// En update
$user = User::findOrFail($id); // Lanza 404 si no existe

// En destroy
$user = User::where('id', $id)->where('deleted_at', null)->firstOrFail();
```

---

#### BUG #4: Posible auto-eliminación de usuario
**Ubicación:** `app/Http/Controllers/UserController.php:113-125`

**Descripción:** No se verifica que el usuario no esté intentando eliminarse a sí mismo.

**Impacto:** Medio - Un usuario podría eliminarse a sí mismo, causando problemas de consistencia y pérdida de sesión.

**Solución:**
```php
if($id == Auth::id()) {
    return redirect()->route('voyager.users.index')
        ->with(['message' => 'No puedes eliminar tu propio usuario.', 'alert-type' => 'error']);
}
```

---

#### BUG #5: Trait RegistersUserEvents - Error al guardar en evento deleting
**Ubicación:** `app/Traits/RegistersUserEvents.php:19-28`

**Descripción:**
```php
static::deleting(function ($model) {
    if (Auth::check()) {
        // ...
        $model->save(); // Error: ya se está eliminando, no se puede guardar
    }
});
```

**Impacto:** Medio - El evento `deleting` se ejecuta ANTES del soft delete. Llamar a `save()` dentro de este evento puede causar problemas porque el modelo ya está marcado para eliminación.

**Solución:**
```php
static::deleting(function ($model) {
    if (Auth::check()) {
        $user = Auth::user();
        \DB::table('users')
            ->where('id', $model->id)
            ->update([
                'deleteUser_id' => $user->id,
                'deleteRole' => $user->role->name,
                'deleteObservation' => request()->input('deleteObservation'),
            ]);
    }
});
```

---

### 14.2 Bugs de Seguridad

#### BUG #6: Falta de verificación de permisos en controlador
**Ubicación:** `app/Http/Controllers/UserController.php` (todos los métodos)

**Descripción:** El controlador solo tiene middleware `auth`, pero no verifica permisos específicos (browse_users, add_users, edit_users, delete_users).

**Impacto:** Alto - Cualquier usuario autenticado podría crear, editar o eliminar usuarios.

**Solución:**
```php
public function list()
{
    $this->authorize('viewAny', User::class);
    // ...
}

public function store(Request $request)
{
    $this->authorize('create', User::class);
    // ...
}

public function update(Request $request, $id)
{
    $user = User::findOrFail($id);
    $this->authorize('update', $user);
    // ...
}

public function destroy(Request $request, $id)
{
    $user = User::findOrFail($id);
    $this->authorize('delete', $user);
    // ...
}
```

---

#### BUG #7: Posible escalación de privilegios
**Ubicación:** `app/Http/Controllers/UserController.php:91-96`

**Descripción:** Un usuario podría cambiar su propio rol a uno superior (ej. de 'user' a 'admin').

**Impacto:** Alto - Escalación de privilegios.

**Solución:**
```php
if($request->role_id)
{
    // Verificar que el usuario tenga permiso para asignar este rol
    $currentRole = Auth::user()->role_id;
    $targetRole = $request->role_id;
    
    if($targetRole < $currentRole) {
        return redirect()->back()
            ->with(['message' => 'No puedes asignar un rol superior al tuyo.', 'alert-type' => 'error']);
    }
    
    $user->update(['role_id' => $request->role_id]);
}
```

---

#### BUG #8: No se verifica que la persona esté activa en store
**Ubicación:** `app/Http/Controllers/UserController.php:57`

**Descripción:**
```php
$person = Person::where('deleted_at', null)->where('status', 1)->where('id', $request->person_id)->first();
// No se valida que $person exista antes de usarlo
User::create([
    'name' => $person->first_name, // Error si $person es null
    // ...
]);
```

**Impacto:** Medio - Error si la persona no existe.

**Solución:**
```php
$person = Person::where('deleted_at', null)
    ->where('status', 1)
    ->where('id', $request->person_id)
    ->firstOrFail(); // Lanza 404 si no existe
```

---

### 14.3 Falta de Validación

#### BUG #9: No se usa FormRequest para validación
**Ubicación:** `app/Http/Controllers/UserController.php:50-80`, `82-111`

**Descripción:** La validación se hace manualmente dentro de los métodos o no existe en absoluto.

**Impacto:** Medio - Datos inválidos pueden entrar al sistema.

**Solución:** Crear `StoreUserRequest` y `UpdateUserRequest` (ver sección 10.1).

---

#### BUG #10: No se valida unicidad de email correctamente en store
**Ubicación:** `app/Http/Controllers/UserController.php:52-56`

**Descripción:**
```php
$data = User::where('email', $request->email)->first();
if($data)
{
    return redirect()->route('voyager.users.index')->with(['message' => 'El correo ya existe.', 'alert-type' => 'warning    ']);
}
```

**Impacto:** Bajo - No hay race condition protection. Dos usuarios simultáneamente podrían crear el mismo email.

**Solución:**
```php
// En el FormRequest
public function rules()
{
    return [
        'email' => 'required|email|unique:users,email',
    ];
}
```

---

### 14.4 Problemas de Código

#### BUG #11: Código comentado obsoleto
**Ubicación:** `app/Http/Controllers/UserController.php:19-25`, `42`

**Descripción:** Hay métodos comentados que nunca se usan, lo que confunde sobre el flujo real.

**Impacto:** Bajo - Mantiene código muerto.

**Solución:** Eliminar código comentado.

---

#### BUG #12: Hardcode de ruta de avatar
**Ubicación:** `app/Http/Controllers/UserController.php:67`

**Descripción:**
```php
'avatar' => 'users/default.png',
```

**Impacto:** Bajo - No es configurable y asume que la imagen existe.

**Solución:**
```php
'avatar' => config('voyager.user.default_avatar', 'users/default.png'),
```

---

#### BUG #13: Variable `$person` no usada en update
**Ubicación:** No existe, pero en `store` se obtiene `$person` de Person pero no se verifica si tiene los campos necesarios.

**Impacto:** Bajo - Si `$person` no tiene `first_name`, fallará.

**Solución:**
```php
'name' => $person->first_name ?? $person->display_name ?? 'Usuario',
```

---

### 14.5 Problemas de Base de Datos

#### BUG #14: Inconsistencia entre migraciones y modelo
**Ubicación:** `database/migrations/2014_10_12_000000_create_users_table.php`, `2025_04_07_092414_update_user_table.php`

**Descripción:** La migración original de Laravel no incluye `person_id`, `role_id`, `status`, etc. Estos se agregan en una migración posterior pero no está documentado claramente.

**Impacto:** Bajo - Puede causar confusión al entender el esquema.

**Solución:** Documentar claramente en el archivo de migración qué campos se agregan y por qué.

---

#### BUG #15: Falta de índices en campos frecuentemente consultados
**Ubicación:** `database/migrations/2025_04_07_092414_update_user_table.php`

**Descripción:** Los campos `email`, `person_id`, `role_id`, `status` se consultan frecuentemente pero no tienen índices explícitos (excepto `email` que ya tiene unique).

**Impacto:** Medio - Puede causar problemas de rendimiento en tablas grandes.

**Solución:**
```php
$table->index('person_id');
$table->index('role_id');
$table->index('status');
$table->index(['status', 'role_id']); // Índice compuesto para filtros comunes
```

---

### 14.6 Problemas en Frontend

#### BUG #16: Error sintáctico en list.blade.php
**Ubicación:** `resources/views/vendor/voyager/users/list.blade.php:63`

**Descripción:** El LSP detecta errores de sintaxis en la línea 63 (probablemente comillas mal escapadas en JavaScript).

**Impacto:** Bajo - Puede causar errores de JavaScript.

**Solución:** Revisar y corregir la sintaxis del JavaScript en la vista.

---

#### BUG #17: Variable global `personSelected` no controlada
**Ubicación:** `public/js/include/person-select.js:1, 37`

**Descripción:**
```javascript
var personSelected; // Variable global, puede causar conflictos
window.personSelected = opt; // Guarda en variable global
```

**Impacto:** Bajo - Variables globales pueden causar conflictos con otros scripts.

**Solución:** Usar closures o namespaces:
```javascript
(function() {
    let personSelected;
    // ...
})();
```

---

#### BUG #18: Modal no tiene validación de cierre
**Ubicación:** `public/js/include/person-register.js:22`

**Descripción:** El modal se cierra automáticamente después de guardar, pero no valida si hubo error.

**Solución:**
```javascript
$.post(form.attr('action'), $(this).serialize(), function(data){
    if(data.person.id){
        toastr.success('Usuario creado', 'Éxito');
        form[0].reset();
        $('#modal-create-person').modal('hide');
        // Actualizar select2
        $('#select-person_id').trigger('change');
    }else{
        toastr.error(data.error, 'Error');
        // No cerrar modal en caso de error
    }
})
```

---

### 14.7 Mejoras Sugeridas

#### MEJORA #1: Implementar Resource Controller estándar
**Ubicación:** `app/Http/Controllers/UserController.php`

**Descripción:** El controlador actual no sigue el patrón estándar de Laravel Resource Controller.

**Beneficio:** Mayor consistencia con el resto de la aplicación.

**Implementación:**
```php
Route::resource('users', UserController::class)->names('admin.users');
```

---

#### MEJORA #2: Agregar búsqueda por persona
**Ubicación:** `app/Http/Controllers/UserController.php:28-47`

**Descripción:** Actualmente la búsqueda solo busca en `id`, `name`, `email`. No busca en los datos de la persona asociada.

**Implementación:**
```php
$data = User::with(['person'])
    ->where(function($query) use ($search){
        $query->where('id', 'like', "%{$search}%")
            ->orWhere('name', 'like', "%{$search}%")
            ->orWhere('email', 'like', "%{$search}%")
            ->orWhereHas('person', function($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('paternal_surname', 'like', "%{$search}%")
                  ->orWhere('ci', 'like', "%{$search}%");
            });
    })
    // ...
```

---

#### MEJORA #3: Agregar bulk actions (acciones masivas)
**Ubicación:** Nuevo método en UserController

**Descripción:** Permitir activar/desactivar múltiples usuarios a la vez.

**Implementación:**
```php
public function bulkUpdate(Request $request)
{
    $this->authorize('updateAny', User::class);
    
    $validated = $request->validate([
        'user_ids' => 'required|array',
        'user_ids.*' => 'exists:users,id',
        'action' => 'required|in:activate,deactivate,delete',
        'observation' => 'required_if:action,delete|string|max:500',
    ]);
    
    switch($validated['action']) {
        case 'activate':
            User::whereIn('id', $validated['user_ids'])->update(['status' => 1]);
            break;
        case 'deactivate':
            User::whereIn('id', $validated['user_ids'])->update(['status' => 0]);
            break;
        case 'delete':
            User::whereIn('id', $validated['user_ids'])
                ->where('id', '!=', Auth::id()) // No auto-eliminación
                ->delete();
            break;
    }
    
    return redirect()->back()->with(['message' => 'Acción completada.', 'alert-type' => 'success']);
}
```

---

#### MEJORA #4: Agregar exportación de usuarios
**Ubicación:** Nuevo método en UserController

**Descripción:** Permitir exportar la lista de usuarios a Excel/PDF.

**Implementación:**
```php
public function export(Request $request)
{
    $this->authorize('viewAny', User::class);
    
    $users = User::with(['person'])
        ->when($request->search, function($q) use ($request) {
            $q->where('name', 'like', "%{$request->search}%")
              ->orWhere('email', 'like', "%{$request->search}%");
        })
        ->orderBy('id', 'DESC')
        ->get();
    
    return (new UsersExport($users))->download('usuarios.xlsx');
}
```

---

#### MEJORA #5: Agregar notificaciones por email
**Ubicación:** Eventos de User

**Descripción:** Enviar email cuando se crea un usuario nuevo o se restablece contraseña.

**Implementación:**
```php
// Event Listener
class SendUserCreatedNotification
{
    public function handle(UserCreated $event)
    {
        $user = $event->user;
        $password = $event->password; // Necesario pasarlo en el evento
        
        Mail::to($user->email)->send(new NewUserMail($user, $password));
    }
}

// En UserController::store después de crear el usuario
UserCreated::dispatch($user, $request->password);
```

---

#### MEJORA #6: Implementar Two-Factor Authentication (2FA)
**Ubicación:** Nuevo sistema en UserController

**Descripción:** Agregar autenticación de dos factores para mayor seguridad.

**Implementación:**
```php
public function enable2FA(Request $request)
{
    $user = Auth::user();
    
    $google2fa = app('pragmarx.google2fa');
    $secret = $google2fa->generateSecretKey();
    
    $user->update([
        'google2fa_secret' => encrypt($secret),
        'google2fa_enabled' => true,
    ]);
    
    $qrCodeUrl = $google2fa->getQRCodeUrl(
        config('app.name'),
        $user->email,
        $secret
    );
    
    return view('auth.2fa.enable', compact('qrCodeUrl'));
}
```

---

#### MEJORA #7: Agregar auditoría completa con Laravel Auditing
**Ubicación:** Paquete `owen-it/laravel-auditing`

**Descripción:** Registrar todos los cambios en usuarios con quién hizo el cambio y cuándo.

**Implementación:**
```php
// En modelo User
use OwenIt\Auditing\Contracts\Auditable;

class User extends \TCG\Voyager\Models\User implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    
    protected $auditInclude = [
        'name', 'email', 'role_id', 'status', 'person_id'
    ];
}
```

---

### 14.8 Faltas y Cosas por Implementar

#### FALTA #1: No hay método show() implementado
**Ubicación:** `app/Http/Controllers/UserController.php`

**Descripción:** La vista `list.blade.php` tiene un enlace a `voyager.users.show` pero el método `show()` no existe en el controlador (está comentado).

**Ubicación del enlace:** `resources/views/vendor/voyager/users/list.blade.php:53`

**Solución:** Implementar método `show()` o eliminar el enlace de la vista.

---

#### FALTA #2: No hay verificación de contraseña actual al cambiarla
**Ubicación:** `app/Http/Controllers/UserController.php:97-102`

**Descripción:** Para cambiar la contraseña de un usuario, no se requiere la contraseña actual (excepto que sea el propio usuario).

**Solución:**
```php
if($request->password)
{
    // Si es el propio usuario, verificar contraseña actual
    if($id == Auth::id() && !Hash::check($request->current_password, $user->password)) {
        return redirect()->back()
            ->with(['message' => 'La contraseña actual es incorrecta.', 'alert-type' => 'error']);
    }
    
    $user->update([
        'password' => bcrypt($request->password)
    ]);
}
```

---

#### FALTA #3: No hay reseteo de contraseña
**Ubicación:** No implementado

**Descripción:** No hay funcionalidad para restablecer contraseña si el usuario la olvida.

**Solución:**
```php
public function sendResetLink(Request $request)
{
    $request->validate(['email' => 'required|email']);
    
    $status = Password::sendResetLink($request->only('email'));
    
    return $status === Password::RESET_LINK_SENT
        ? back()->with(['message' => 'Enlace enviado.', 'alert-type' => 'success'])
        : back()->withErrors(['email' => __($status)]);
}
```

---

#### FALTA #4: No hay filtro por rol en listado
**Ubicación:** `app/Http/Controllers/UserController.php:28-47`

**Descripción:** El método `list()` permite búsqueda pero no filtrado por rol.

**Solución:**
```php
public function list()
{
    $rol_id = Auth::user()->role_id;
    $search = request('search');
    $paginate = request('paginate', 10);
    $filter_role = request('filter_role'); // Nuevo parámetro
    
    $query = User::with(['person'])
        ->when($search, function($q) use ($search) {
            $q->where('id', 'like', "%{$search}%")
              ->orWhere('name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        })
        ->when($filter_role, function($q) use ($filter_role) {
            $q->where('role_id', $filter_role);
        })
        ->when($rol_id != 1, function($q) {
            $q->where('role_id', '!=', 1);
        })
        ->orderBy('id', 'DESC');
    
    $data = $query->paginate($paginate);
    
    return view('vendor.voyager.users.list', compact('data'));
}
```

---

#### FALTA #5: No hay filtro por estado en listado
**Ubicación:** Similar a FALTA #4

**Descripción:** No se puede filtrar usuarios por estado (activo/inactivo).

**Solución:**
```php
$filter_status = request('filter_status');

$query->when($filter_status !== null, function($q) use ($filter_status) {
    $q->where('status', $filter_status);
});
```

---

#### FALTA #6: No hay validación de fortaleza de contraseña
**Ubicación:** `app/Http/Controllers/UserController.php:68`

**Descripción:** No se verifica que la contraseña cumpla con requisitos mínimos de seguridad.

**Solución:**
```php
// En StoreUserRequest o UpdateUserRequest
public function rules()
{
    return [
        'password' => [
            'required',
            'min:8',
            'regex:/[a-z]/',      // al menos una letra minúscula
            'regex:/[A-Z]/',      // al menos una letra mayúscula
            'regex:/[0-9]/',      // al menos un número
            'regex:/[@$!%*#?&]/', // al menos un carácter especial
        ],
    ];
}
```

---

#### FALTA #7: No hay límite de intentos de login
**Ubicación:** Configuración global de Laravel

**Descripción:** No se implementa throttling para evitar ataques de fuerza bruta.

**Solución:** Ya está implementado en `RouteLoginController` pero debería revisarse.

---

#### FALTA #8: No hay vista de perfil de usuario
**Ubicación:** No implementado

**Descripción:** Los usuarios no pueden ver/editar su propio perfil (excepto por Voyager).

**Solución:** Crear ruta y vista para perfil de usuario:
```php
Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
```

---

### 14.9 Optimizaciones

#### OPTIMIZACIÓN #1: Usar eager loading correctamente
**Ubicación:** `app/Http/Controllers/UserController.php:36`

**Descripción:** Se usa `with(['person'])` que está bien, pero en la vista `list.blade.php` también se accede a `role` que no está cargado.

**Solución:**
```php
$data = User::with(['person', 'role'])
    ->where(...)
    ->paginate($paginate);
```

Luego en la vista eliminar:
```php
@php
    $rol = TCG\Voyager\Models\Role::where('id', $item->role_id)->first();
@endphp
```

Y usar directamente:
```php
{{ $item->role->name ?? 'Sin Permiso' }}
```

---

#### OPTIMIZACIÓN #2: Cachear roles frecuentemente accedidos
**Ubicación:** `app/Http/Controllers/UserController.php:80-83`

**Descripción:** En cada request de edición se consulta la tabla de roles.

**Solución:**
```php
$roles = Cache::remember('roles.list', 3600, function() use ($rol_id) {
    return Role::whereRaw($rol_id != 1 ? 'id != 1' : 1)->get();
});
```

---

#### OPTIMIZACIÓN #3: Usar route model binding
**Ubicación:** `app/Http/Controllers/UserController.php:82, 113`

**Descripción:** En lugar de buscar por ID manualmente, usar inyección de modelo.

**Solución:**
```php
public function update(Request $request, User $user)
{
    $this->authorize('update', $user);
    
    DB::beginTransaction();
    try {
        $user->update([
            'status' => $request->status ? 1 : 0,
        ]);
        // ...
    }
}

public function destroy(User $user)
{
    $this->authorize('delete', $user);
    
    DB::beginTransaction();
    try {
        $user->delete();
        // ...
    }
}
```

Luego en las rutas:
```php
Route::put('/users/{user}', [UserController::class, 'update']);
Route::delete('/users/{user}', [UserController::class, 'destroy']);
```

---

#### OPTIMIZACIÓN #4: Paginación por AJAX optimizada
**Ubicación:** `resources/views/vendor/voyager/users/list.blade.php:98-110`

**Descripción:** La paginación actual recarga toda la página parcialmente.

**Solución:** Usar Laravel Livewire o Inertia.js para mejor UX.

---

#### OPTIMIZACIÓN #5: Implementar debounce en búsqueda
**Ubicación:** `public/js/include/person-select.js:16`

**Descripción:** La búsqueda se ejecuta en cada keystroke después de 2 caracteres.

**Solución:** Ya tiene `quietMillis: 250` que implementa debounce. Podría aumentarse a 500 para menos consultas.

---

#### OPTIMIZACIÓN #6: Usar select2 con carga diferida
**Ubicación:** `public/js/include/person-select.js:3-46`

**Descripción:** El select2 carga todas las personas. Para listas grandes esto es ineficiente.

**Solución:** Ya implementado con AJAX. Bien.

---

### 14.10 Problemas de Arquitectura

#### ARQUITECTURA #1: Mismo SQL injection en RoleController
**Ubicación:** `app/Http/Controllers/RoleController.php:29-33`

**Descripción:** El bug de SQL injection de UserController también existe en RoleController.

**Solución:** Aplicar la misma solución que BUG #1.

---

#### ARQUITECTURA #2: Falta de separación de responsabilidades
**Ubicación:** `app/Http/Controllers/UserController.php`

**Descripción:** El controlador hace demasiadas cosas: validación, lógica de negocio, persistencia.

**Solución:** Implementar Service Layer:
```php
class UserService
{
    public function createUser(array $data): User
    {
        // Lógica de negocio aquí
    }
    
    public function updateUser(User $user, array $data): User
    {
        // Lógica de negocio aquí
    }
}

class UserController extends Controller
{
    public function store(StoreUserRequest $request, UserService $service)
    {
        $user = $service->createUser($request->validated());
        return redirect()->route('voyager.users.index')
            ->with(['message' => 'Registrado exitosamente.', 'alert-type' => 'success']);
    }
}
```

---

#### ARQUITECTURA #3: Middleware System hace demasiado
**Ubicación:** `app/Http/Middleware/System.php`

**Descripción:** El middleware maneja mantenimiento, desarrollo, licencia y logging. Hace demasiado.

**Solución:** Separar en múltiples middlewares:
- `CheckMaintenanceMode`
- `CheckDevelopmentMode`
- `CheckLicense`
- `LogRequest`

---

### 14.11 Checklist de Prioridad

| Prioridad | Item | Ubicación | Tipo |
|-----------|------|-----------|------|
| 🔴 CRÍTICO | SQL Injection en búsqueda | UserController:38-40 | Bug |
| 🔴 CRÍTICO | SQL Injection en RoleController | RoleController:29-33 | Bug |
| 🔴 CRÍTICO | Falta de verificación de permisos | UserController (todos) | Bug |
| 🟠 ALTO | Posible escalación de privilegios | UserController:91-96 | Bug |
| 🟠 ALTO | Auto-eliminación permitida | UserController:113-125 | Bug |
| 🟠 ALTO | Error si usuario no existe | UserController:86, 117 | Bug |
| 🟡 MEDIO | Error en Trait deleting | RegistersUserEvents:26 | Bug |
| 🟡 MEDIO | Falta de índices en BD | 2025_04_07_092414_update_user_table | Mejora |
| 🟡 MEDIO | Optimización eager loading | UserController:36 | Optimización |
| 🟢 BAJO | Código comentado obsoleto | UserController:19-25 | Limpieza |
| 🟢 BAJO | Variable global en JS | person-select.js:1 | Limpieza |
| 🟢 BAJO | Implementar método show() | No implementado | Falta |

---

## 15. Recursos Adicionales

### Archivos Relacionados

- **Trait de auditoría:** `app/Traits/RegistersUserEvents.php`
- **Migración actualización:** `database/migrations/2025_04_07_092414_update_user_table.php`
- **Middleware Loggin:** `app/Http/Middleware/Loggin.php`
- **Middleware System:** `app/Http/Middleware/System.php`
- **Controlador de Roles:** `app/Http/Controllers/RoleController.php`
- **JavaScript selección persona:** `public/js/include/person-select.js`
- **JavaScript registro persona:** `public/js/include/person-register.js`
- **Modal registro persona:** `resources/views/partials/modal-registerPerson.blade.php`