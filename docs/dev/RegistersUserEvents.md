# Trait RegistersUserEvents

## Descripción

`RegistersUserEvents` es un trait de Laravel que automatiza el registro de auditoría en modelos Eloquent. Captura automáticamente quién crea o elimina registros, almacenando el ID del usuario, su rol y observaciones de eliminación.

**Ubicación del archivo:** `app/Traits/RegistersUserEvents.php`

## Funcionalidad

El trait registra eventos Eloquent `creating` y `deleting` para llenar automáticamente campos de auditoría:

| Campo | Evento | Descripción |
|-------|--------|-------------|
| `registerUser_id` | `creating` | ID del usuario autenticado que crea el registro |
| `registerRole` | `creating` | Nombre del rol del usuario que crea el registro |
| `deleteUser_id` | `deleting` | ID del usuario autenticado que elimina el registro |
| `deleteRole` | `deleting` | Nombre del rol del usuario que elimina el registro |
| `deleteObservation` | `deleting` | Observación de eliminación (desde request) |

## Código Fuente

```php
<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

trait RegistersUserEvents
{
    protected static function bootRegistersUserEvents()
    {
        static::creating(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                $model->registerUser_id = $user->id;
                $model->registerRole = $user->role->name;
            }
        });

        static::deleting(function ($model) {
            if (Auth::check()) {
                $user = Auth::user();
                $model->deleteUser_id = $user->id;
                $model->deleteRole = $user->role->name;
                $model->deleteObservation = request()->input('deleteObservation');

                $model->save();
            }
        });
    }
}
```

## Implementación

### 1. Uso en Modelo

Para habilitar el trait en un modelo:

```php
<?php

namespace App\Models;

use App\Traits\RegistersUserEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Person extends Model
{
    use RegistersUserEvents, SoftDeletes;

    protected $fillable = [
        // Campos del modelo
        'first_name',
        'last_name',
        // ... otros campos
        
        // Campos de auditoría
        'registerUser_id',
        'registerRole',
        'deleteUser_id',
        'deleteRole',
        'deleteObservation',
    ];
}
```

### 2. Migración de Base de Datos

La tabla debe incluir los campos de auditoría:

```php
Schema::create('people', function (Blueprint $table) {
    $table->id();
    // ... otros campos
    
    $table->timestamps();
    $table->foreignId('registerUser_id')->nullable()->constrained('users');
    $table->string('registerRole')->nullable();
    $table->softDeletes();
    $table->foreignId('deleteUser_id')->nullable()->constrained('users');
    $table->string('deleteRole')->nullable();
    $table->text('deleteObservation')->nullable();
});
```

### 3. Relaciones en Modelo

Se recomienda agregar relaciones para acceder a la información del usuario:

```php
public function registerUser()
{
    return $this->belongsTo(User::class, 'registerUser_id');
}

public function deleteUser()
{
    return $this->belongsTo(User::class, 'deleteUser_id');
}
```

### 4. Uso en Controlador

El controlador NO necesita asignar manualmente los campos de auditoría:

```php
public function store(StorePersonRequest $request)
{
    // Los campos registerUser_id y registerRole se asignan automáticamente
    Person::create($request->validated());
    
    return redirect()->route('admin.people.index');
}

public function destroy(Person $person)
{
    // Los campos deleteUser_id, deleteRole y deleteObservation
    // se asignan automáticamente antes del soft delete
    $person->delete();
    
    return redirect()->route('admin.people.index');
}
```

### 5. Envío de Observación de Eliminación

Para incluir una observación al eliminar, envíela en el request:

```html
<form action="{{ route('admin.people.destroy', $person) }}" method="POST">
    @csrf
    @method('DELETE')
    
    <textarea name="deleteObservation" placeholder="Motivo de eliminación"></textarea>
    
    <button type="submit">Eliminar</button>
</form>
```

## Modelos que Usan el Trait

| Modelo | Archivo |
|--------|---------|
| `Person` | `app/Models/Person.php` |
| `User` | `app/Models/User.php` |

## Requisitos

1. **Autenticación:** El usuario debe estar autenticado (`Auth::check()`)
2. **Modelo con SoftDeletes:** Se requiere `SoftDeletes` para usar el evento `deleting`
3. **Relación User->Role:** El modelo User debe tener una relación `role` con un atributo `name`

## Eventos Registrados

### Evento `creating`

Se ejecuta ANTES de insertar el registro en la base de datos.

```php
static::creating(function ($model) {
    if (Auth::check()) {
        $user = Auth::user();
        $model->registerUser_id = $user->id;
        $model->registerRole = $user->role->name;
    }
});
```

**Casos:**
- Creación de nuevo registro
- Replicación de modelo (NO se ejecuta en `update`)

### Evento `deleting`

Se ejecuta ANTES de realizar el soft delete.

```php
static::deleting(function ($model) {
    if (Auth::check()) {
        $user = Auth::user();
        $model->deleteUser_id = $user->id;
        $model->deleteRole = $user->role->name;
        $model->deleteObservation = request()->input('deleteObservation');

        $model->save();
    }
});
```

**Casos:**
- Soft delete del registro
- El método `save()` se llama para persistir los campos antes de la eliminación

## Limitaciones

1. **Solo crea/elimina:** No rastrea actualizaciones (`updated_by`)
2. **No historial de cambios:** No guarda qué campos cambiaron ni valores anteriores
3. **Requiere autenticación:** Si no hay usuario autenticado, no se llenan los campos
4. **No funciona en actualizaciones:** El trait solo maneja `creating` y `deleting`

## Ejemplos de Consulta

### Obtener registros creados por un usuario específico

```php
$peopleCreatedByUser = Person::where('registerUser_id', $userId)->get();
```

### Obtener registros eliminados por un usuario específico

```php
$peopleDeletedByUser = Person::onlyTrashed()
    ->where('deleteUser_id', $userId)
    ->get();
```

### Obtener todos los registros con información del creador

```php
$people = Person::with('registerUser')->get();

foreach ($people as $person) {
    echo $person->registerUser->name;
}
```

### Filtrar por rol del creador

```php
$peopleByAdmin = Person::where('registerRole', 'admin')->get();
```

## Caso de Uso Típico

### Flujo de Creación

1. Usuario autenticado como `admin` crea una persona
2. El trait `creating` captura el evento
3. Se asigna automáticamente:
   - `registerUser_id = 1` (ID del admin)
   - `registerRole = 'admin'` (Nombre del rol)
4. Se guarda el registro en la base de datos

### Flujo de Eliminación

1. Usuario autenticado como `operator` elimina una persona
2. Se envía el parámetro `deleteObservation` con "Duplicado"
3. El trait `deleting` captura el evento
4. Se asigna automáticamente:
   - `deleteUser_id = 2` (ID del operator)
   - `deleteRole = 'operator'` (Nombre del rol)
   - `deleteObservation = 'Duplicado'`
5. Se hace `save()` para persistir los campos
6. Se aplica el soft delete

## Referencias en el Código

| Ubicación | Descripción |
|-----------|-------------|
| `app/Traits/RegistersUserEvents.php:9-29` | Implementación del trait |
| `app/Models/Person.php:12` | Uso del trait en Person |
| `app/Models/User.php:17` | Uso del trait en User |
| `app/Http/Controllers/PersonController.php:87-98` | Store sin asignación manual |
| `app/Http/Controllers/PersonController.php:138-143` | Destroy sin asignación manual |
| `database/migrations/2025_04_07_092413_create_people_table.php:43-48` | Campos de auditoría en migración |

---

## 🐛 Bugs Conocidos y Problemas Potenciales

### BUG #1: Error al guardar en evento `deleting`

**Ubicación:** `app/Traits/RegistersUserEvents.php:19-28`

**Descripción:**
El evento `deleting` se ejecuta ANTES del soft delete. Llamar a `save()` dentro de este evento puede causar problemas porque el modelo ya está marcado para eliminación.

```php
static::deleting(function ($model) {
    if (Auth::check()) {
        // ...
        $model->save(); // Error: ya se está eliminando, no se puede guardar
    }
});
```

**Impacto:** Medio - Puede causar errores inesperados o que los campos de auditoría no se guarden correctamente.

**Solución recomendada:**
```php
static::deleting(function ($model) {
    if (Auth::check()) {
        $user = Auth::user();
        \DB::table($model->getTable())
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

### BUG #2: Posible NullPointerException en `$user->role`

**Ubicación:** `app/Traits/RegistersUserEvents.php:15,23`

**Descripción:**
Si el usuario autenticado no tiene un rol asignado (`$user->role` es null), el código `$user->role->name` lanzará una excepción.

```php
$user = Auth::user();
$model->registerRole = $user->role->name; // Error si role es null
```

**Impacto:** Alto - Puede causar que falle cualquier creación o eliminación de registros.

**Solución recomendada:**
```php
static::creating(function ($model) {
    if (Auth::check()) {
        $user = Auth::user();
        $model->registerUser_id = $user->id;
        $model->registerRole = $user->role?->name ?? 'unknown';
    }
});
```

---

### BUG #3: Inconsistencia de nombres de campos de auditoría

**Ubicación:** Múltiples archivos

**Descripción:**
Existe una inconsistencia en el proyecto sobre los nombres de campos de auditoría:

| Estilo A (RegistersUserEvents) | Estilo B (Manual) |
|--------------------------------|-------------------|
| `registerUser_id` | `created_by` |
| `registerRole` | No se guarda rol |
| `deleteUser_id` | No se usa (hard delete) |
| `deleteRole` | No se guarda rol |

**Modelos con Estilo A:**
- `Person` - usa trait `RegistersUserEvents`
- `User` - usa trait `RegistersUserEvents`

**Modelos con Estilo B:**
- `Tramite` - campos `created_by`, `updated_by` (asignación manual)
- `Pago` - campos `created_by`, `updated_by` (asignación manual)
- `Avaluo` - campos `created_by`, `updated_by` (asignación manual)

**Impacto:** Alto - Dificulta la estandarización de consultas de auditoría y reportes.

**Solución recomendada:**
1. Definir un estándar único para toda la aplicación
2. Crear un trait mejorado que soporte ambos eventos (`creating`, `updating`, `deleting`)
3. Migrar los modelos actuales al estándar elegido

---

### BUG #4: No se manejan actualizaciones (`updated_by`)

**Ubicación:** `app/Traits/RegistersUserEvents.php:9-29`

**Descripción:**
El trait NO rastrea quién actualiza un registro. Los modelos `Tramite`, `Pago`, `Avaluo` tienen que asignar manualmente `updated_by` en cada controlador:

```php
// Ejemplo en TramiteController.php:78
$tramite->update([
    // ... otros campos
    'updated_by' => auth()->id(), // Asignación manual repetitiva
]);
```

**Ubicaciones donde se hace manualmente:**
- `app/Http/Controllers/TramiteController.php:78`
- `app/Http/Controllers/PagoController.php:83`
- `app/Http/Controllers/UfvController.php:70`
- `app/Http/Controllers/AvaluoController.php:78,108`
- `app/Http/Controllers/AdquirenteTramiteController.php:101`
- `app/Http/Controllers/DisponenteTramiteController.php:82`
- `app/Http/Controllers/DocumentoController.php:100`

**Impacto:** Medio - Código repetitivo, propenso a errores, fácil olvidar asignar el campo.

**Solución recomendada:**
Agregar evento `updating` al trait:
```php
static::updating(function ($model) {
    if (Auth::check()) {
        $user = Auth::user();
        $model->updated_by = $user->id;
    }
});
```

---

## 💡 Mejoras Sugeridas

### MEJORA #1: Validar campos en `$fillable`

**Ubicación:** `app/Traits/RegistersUserEvents.php:9-29`

**Descripción:**
El trait asume que los campos de auditoría están en `$fillable`. Si un desarrollador no los agrega, se ignorará silenciosamente.

**Solución recomendada:**
```php
protected static function bootRegistersUserEvents()
{
    static::creating(function ($model) {
        if (Auth::check()) {
            $user = Auth::user();
            if (in_array('registerUser_id', $model->getFillable())) {
                $model->registerUser_id = $user->id;
            }
            if (in_array('registerRole', $model->getFillable())) {
                $model->registerRole = $user->role?->name ?? 'unknown';
            }
        }
    });
}
```

---

### MEJORA #2: Usar ID de rol en lugar de nombre

**Ubicación:** `app/Traits/RegistersUserEvents.php:15,23`

**Descripción:**
El rol se guarda como string (`registerRole = 'admin'`). Si el nombre del rol cambia en el futuro, los registros antiguos quedarán con el nombre incorrecto.

**Solución recomendada:**
```php
// Guardar ID de rol en lugar de nombre
$table->foreignId('registerRoleId')->nullable()->constrained('roles');
$table->foreignId('deleteRoleId')->nullable()->constrained('roles');

// En el trait
$model->registerRoleId = $user->role_id;
$model->deleteRoleId = $user->role_id;
```

---

### MEJORA #3: Soporte para múltiples guardias de autenticación

**Ubicación:** `app/Traits/RegistersUserEvents.php:12,20`

**Descripción:**
El trait usa `Auth::user()` que asume el guard por defecto (`web`). Si el proyecto usa API tokens u otros guards, no funcionará.

**Solución recomendada:**
```php
static::creating(function ($model) {
    if (Auth::guard(config('auth.defaults.guard'))->check()) {
        $user = Auth::guard(config('auth.defaults.guard'))->user();
        // ...
    }
});
```

---

### MEJORA #4: Logging cuando no hay usuario autenticado

**Ubicación:** `app/Traits/RegistersUserEvents.php:11,19`

**Descripción:**
Cuando no hay usuario autenticado, los campos de auditoría quedan vacíos sin ninguna advertencia.

**Solución recomendada:**
```php
static::creating(function ($model) {
    if (Auth::check()) {
        $user = Auth::user();
        $model->registerUser_id = $user->id;
        $model->registerRole = $user->role?->name ?? 'unknown';
    } else {
        \Log::warning("RegistersUserEvents: No authenticated user when creating {$model->getTable()}", [
            'model' => get_class($model),
            'attributes' => $model->toArray(),
        ]);
    }
});
```

---

## ⚠️ Cosas Faltantes

### FALTA #1: No hay tests para el trait

**Ubicación:** `tests/` (no existen tests)

**Descripción:**
No hay pruebas unitarias o de integración que verifiquen que el trait funciona correctamente.

**Solución recomendada:**
```php
// tests/Unit/RegistersUserEventsTest.php
class RegistersUserEventsTest extends TestCase
{
    public function test_fills_audit_fields_on_create()
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        
        $person = Person::create([
            'person_type' => 'Natural',
            'ci' => '12345678',
            'first_name' => 'Juan',
            'paternal_surname' => 'Perez',
        ]);
        
        $this->assertEquals($user->id, $person->registerUser_id);
        $this->assertEquals($user->role->name, $person->registerRole);
    }
}
```

---

### FALTA #2: No hay documentación en el código

**Ubicación:** `app/Traits/RegistersUserEvents.php`

**Descripción:**
El trait no tiene comentarios PHPDoc explicando su propósito, requisitos y uso.

**Solución recomendada:**
```php
<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;

/**
 * Trait RegistersUserEvents
 * 
 * Automatiza el registro de auditoría en modelos Eloquent.
 * Captura automáticamente quién crea o elimina registros.
 * 
 * @package App\Traits
 * @author Sistema
 */
trait RegistersUserEvents
{
    /**
     * Boot the trait.
     * 
     * Registra los eventos creating y deleting para llenar
     * automáticamente los campos de auditoría.
     * 
     * @return void
     */
    protected static function bootRegistersUserEvents()
    {
        // ...
    }
}
```

---

### FALTA #3: No hay historial de cambios completo

**Ubicación:** N/A (funcionalidad faltante)

**Descripción:**
El trait solo guarda quién creó o eliminó el registro, pero no:
- Qué campos cambiaron
- Cuál era el valor anterior
- Cuándo se hizo el cambio
- Lista de todas las modificaciones

**Solución recomendada:**
Implementar un paquete de auditoría completo como:
- `spatie/laravel-activitylog`
- `owen-it/laravel-auditing`

---

### FALTA #4: No se maneja el caso de eliminación masiva

**Ubicación:** `app/Traits/RegistersUserEvents.php:19-28`

**Descripción:**
Si se usa `Person::whereIn('id', [1,2,3])->delete()` (hard delete masivo), el evento `deleting` NO se ejecuta para cada modelo.

**Solución recomendada:**
Prohibir eliminación masiva o usar un enfoque diferente:
```php
// En el modelo, sobrescribir el método delete
public function delete()
{
    if (static::isDeletingMassively()) {
        throw new \Exception('Massive deletion is not supported for audit purposes');
    }
    return parent::delete();
}
```

---

## ⚡ Optimizaciones

### OPTIMIZACIÓN #1: Usar Query Builder para actualizar en evento deleting

**Ubicación:** `app/Traits/RegistersUserEvents.php:26`

**Descripción:**
Llamar a `$model->save()` en el evento `deleting` es ineficiente porque se está preparando para eliminar el registro.

**Solución actual (lenta):**
```php
$model->save(); // Ejecuta UPDATE completo
```

**Solución optimizada:**
```php
\DB::table($model->getTable())
    ->where('id', $model->id)
    ->update([
        'deleteUser_id' => $user->id,
        'deleteRole' => $user->role?->name ?? 'unknown',
        'deleteObservation' => request()->input('deleteObservation'),
    ]);
```

---

### OPTIMIZACIÓN #2: Cachear la información del usuario

**Ubicación:** `app/Traits/RegistersUserEvents.php:12-16,20-24`

**Descripción:**
En cada operación se llama a `Auth::user()` y se accede a `role->name`. Se puede cachear esta información en el request.

**Solución recomendada:**
```php
protected static function bootRegistersUserEvents()
{
    static::creating(function ($model) {
        $auditInfo = self::getAuditInfo();
        if ($auditInfo) {
            $model->registerUser_id = $auditInfo['user_id'];
            $model->registerRole = $auditInfo['role_name'];
        }
    });
    
    // ... similar para deleting
}

private static function getAuditInfo(): ?array
{
    static $cache = null;
    
    if ($cache !== null) {
        return $cache;
    }
    
    if (!Auth::check()) {
        $cache = null;
        return null;
    }
    
    $user = Auth::user();
    $cache = [
        'user_id' => $user->id,
        'role_name' => $user->role?->name ?? 'unknown',
    ];
    
    return $cache;
}
```

---

## 📊 Resumen de Problemas

| Categoría | Cantidad | Prioridad |
|-----------|----------|-----------|
| Bugs críticos | 3 | Alta |
| Mejoras sugeridas | 4 | Media |
| Cosas faltantes | 4 | Variable |
| Optimizaciones | 2 | Baja |

---

## 🎯 Recomendaciones de Acción

### Inmediato (Prioridad Alta)
1. ✅ Corregir el bug de `$model->save()` en evento `deleting`
2. ✅ Agregar validación de nulabilidad en `$user->role`
3. ✅ Estandarizar los nombres de campos de auditoría en toda la aplicación

### Corto plazo (Prioridad Media)
4. Agregar soporte para evento `updating`
5. Crear tests unitarios para el trait
6. Agregar documentación PHPDoc al trait

### Largo plazo (Prioridad Baja)
7. Considerar migrar a un paquete de auditoría completo
8. Optimizar el caché de información del usuario
9. Mejorar el manejo de eliminación masiva

---

## 🔗 Referencias Relacionadas

- Documentación del bug: `docs/dev/UserController.md:539-568`
- Modelos inconsistentes: `app/Models/Tramite.php`, `app/Models/Pago.php`, `app/Models/Avaluo.php`
- Controladores con asignación manual: Buscar `'updated_by' => auth()->id()` en `app/Http/Controllers/`
- Migraciones de auditoría: `database/migrations/*_create_*_table.php` (busca `created_by`, `updated_by`)
