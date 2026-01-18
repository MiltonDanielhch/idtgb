# Documentación Técnica - Módulo de Parentescos

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelo](#modelo)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Policies](#policies)
7. [Requests](#requests)
8. [Vistas](#vistas)
9. [Integración con otros módulos](#integración-con-otros-módulos)
10. [Ejemplos de Uso](#ejemplos-de-uso)
11. [Consideraciones Importantes](#consideraciones-importantes)
12. [Guía para Desarrolladores](#guía-para-desarrolladores)

---

## 🎯 Introducción

El módulo de **Parentescos** gestiona los tipos de relaciones familiares que pueden existir entre los adquirentes y disponentes en los trámites del Impuesto de Transmisiones Gratuítas de Bienes (ITGB). Este módulo es fundamental para calcular las tasas impositivas correspondientes según el tipo de parentesco.

### Propósito
- Mantener un catálogo de tipos de parentescos (Padre, Madre, Hermano, Hijos, etc.)
- Asociar tasas impositivas a cada parentesco por departamento
- Permitir la selección del parentesco al crear adquirentes en trámites
- Gestionar el ciclo de vida CRUD completo de parentescos
- Mantener auditoría de cambios y permitir recuperación de registros eliminados

### Importancia en el Sistema ITGB
El parentesco seleccionado para un adquirente determina directamente la tasa impositiva aplicable en el cálculo del ITGB. Diferentes parentescos tienen tasas diferentes según la legislación vigente en cada departamento.

---

## 🗄️ Base de Datos

### Estructura de la tabla: `parentescos`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre del parentesco (ej: "Padre", "Hermano") |
| `created_by` | BIGINT | FK → users.id, NULLABLE | Usuario que creó el registro |
| `updated_by` | BIGINT | FK → users.id, NULLABLE | Usuario que actualizó el registro |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |
| `deleted_at` | TIMESTAMP | NULLABLE | Fecha de eliminación suave |

**Relaciones:**
- Tiene muchos `Tasa` (un parentesco puede tener múltiples tasas asociadas por departamento)
- Es utilizado por la tabla pivote `adquirentes_tramites`
- Pertenece a `User` (created_by, updated_by)

**Migraciones aplicadas:**
- `2025_09_22_122721_create_parentescos_table.php` - Creación de tabla
- `2026_01_17_233959_add_soft_deletes_to_parentescos_table.php` - Soft Deletes
- `2026_01_17_234227_add_audit_fields_to_parentescos_table.php` - Campos de auditoría

---

## 🧩 Modelo

### Modelo: `Parentesco`

**Ubicación:** `app/Models/Parentesco.php`

**Traits:**
- `HasFactory`
- `SoftDeletes`

**Atributos:**
- `$table = 'parentescos'`
- `$fillable = ['nombre', 'created_by', 'updated_by']`

**Métodos de relación:**

```php
public function tasas()
{
    return $this->hasMany(Tasa::class);
}

public function adquirentesTramite()
{
    return $this->hasMany(AdquirenteTramite::class);
}

public function createdBy()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function updatedBy()
{
    return $this->belongsTo(User::class, 'updated_by');
}
```

**Eventos de modelo:**

```php
protected static function boot()
{
    parent::boot();

    static::creating(function ($model) {
        if (auth()->check()) {
            $model->created_by = auth()->id();
        }
    });

    static::updating(function ($model) {
        if (auth()->check()) {
            $model->updated_by = auth()->id();
        }
    });
}
```

**Uso del modelo:**

```php
// Obtener todos los parentescos (sin eliminados)
$parentescos = Parentesco::all();

// Incluir parentescos eliminados
$parentescos = Parentesco::withTrashed()->get();

// Solo parentescos eliminados
$parentescos = Parentesco::onlyTrashed()->get();

// Restaurar parentesco eliminado
$parentesco->restore();

// Obtener un parentesco específico
$parentesco = Parentesco::find(1);

// Obtener parentescos con sus tasas cargadas
$parentescos = Parentesco::with('tasas')->get();

// Obtener con contadores de relaciones
$parentescos = Parentesco::withCount(['tasas', 'adquirentesTramite'])->get();

// Crear un nuevo parentesco
Parentesco::create(['nombre' => 'Padre']);

// Actualizar un parentesco
$parentesco->update(['nombre' => 'Padre Biológico']);

// Buscar por nombre
$parentesco = Parentesco::where('nombre', 'Hermano')->first();

// Verificar si tiene tasas asociadas
if ($parentesco->tasas()->exists()) {
    // No se puede eliminar
}

// Verificar si está en uso en trámites
if ($parentesco->adquirentesTramite()->exists()) {
    // No se puede eliminar
}
```

---

## 🎮 Controlador

### Controlador: `ParentescoController`

**Ubicación:** `app/Http/Controllers/ParentescoController.php`

**Middleware:**
- `auth` - Requiere autenticación

### Métodos del Controlador

#### 1. `index()` - Vista principal
```
GET /admin/parentescos
```
- Muestra la vista `browse.blade.php` con tabla interactiva
- Requiere permiso: `browse_parentescos`
- Carga datos vía AJAX

#### 2. `list(Request $request)` - Listado AJAX
```
GET /admin/parentescos/ajax/list
```
- Retorna lista paginada de parentescos con contadores de relaciones
- Parámetros:
  - `search` (string): Filtro por nombre
  - `paginate` (int): Cantidad de registros por página (default: 10)
- Ordenamiento: ID descendente
- Incluye `withCount(['tasas', 'adquirentesTramite'])`

**Código del método:**

```php
public function list(Request $request)
{
    $this->authorize('viewAny', Parentesco::class);

    $search   = $request->get('search', '');
    $paginate = $request->get('paginate', 10);

    $parentescos = Parentesco::withCount(['tasas', 'adquirentesTramite'])
        ->when($search, function ($query) use ($search) {
            $query->where('nombre', 'like', '%' . $search . '%');
        })
        ->orderBy('id', 'desc')
        ->paginate($paginate);

    return view('admin.parentescos.list', compact('parentescos'));
}
```

#### 3. `show(Parentesco $parentesco)` - Ver detalle
```
GET /admin/parentescos/{parentesco}
```
- Muestra vista detallada `read.blade.php`
- Requiere permiso: `read_parentescos`

#### 4. `create()` - Formulario de creación
```
GET /admin/parentescos/create
```
- Muestra formulario `edit_add.blade.php`
- Requiere permiso: `add_parentescos`

#### 5. `store(StoreParentescoRequest $request)` - Guardar nuevo
```
POST /admin/parentescos
```
- Valida y crea nuevo parentesco
- Registra automáticamente `created_by`
- Redirige al listado con mensaje de éxito
- Requiere permiso: `add_parentescos`

**Código del método:**

```php
public function store(StoreParentescoRequest $request)
{
    Parentesco::create($request->validated());

    return redirect()->route('admin.parentescos.index')
        ->with(['message' => 'Parentesco creado.', 'alert-type' => 'success']);
}
```

#### 6. `edit(Parentesco $parentesco)` - Formulario de edición
```
GET /admin/parentescos/{parentesco}/edit
```
- Muestra formulario con datos existentes
- Requiere permiso: `edit_parentescos`

#### 7. `update(UpdateParentescoRequest $request, Parentesco $parentesco)` - Actualizar
```
PUT /admin/parentescos/{parentesco}
```
- Valida y actualiza parentesco
- Registra automáticamente `updated_by`
- Redirige al listado con mensaje de éxito
- Requiere permiso: `edit_parentescos`

**Código del método:**

```php
public function update(UpdateParentescoRequest $request, Parentesco $parentesco)
{
    $parentesco->update($request->validated());

    return redirect()->route('admin.parentescos.index')
        ->with(['message' => 'Parentesco actualizado.', 'alert-type' => 'success']);
}
```

#### 8. `destroy(Parentesco $parentesco)` - Eliminar (soft delete)
```
DELETE /admin/parentescos/{parentesco}
```
- Verifica que no tenga tasas asociadas antes de eliminar
- Verifica que no esté en uso en trámites antes de eliminar
- Realiza soft delete (no elimina permanentemente)
- Registra errores en logs
- Retorna error si tiene dependencias
- Requiere permiso: `delete_parentescos`

**Código del método:**

```php
public function destroy(Parentesco $parentesco)
{
    $this->authorize('delete', $parentesco);

    if ($parentesco->tasas()->exists()) {
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'No se puede eliminar: El parentesco tiene tasas asociadas.', 'alert-type' => 'error']);
    }

    if ($parentesco->adquirentesTramite()->exists()) {
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'No se puede eliminar: El parentesco está siendo utilizado en trámites existentes.', 'alert-type' => 'error']);
    }

    try {
        $parentesco->delete();
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco eliminado.', 'alert-type' => 'success']);
    } catch (\Exception $e) {
        Log::error("Error al eliminar Parentesco #{$parentesco->id}: " . $e->getMessage());
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Ocurrió un error inesperado al intentar eliminar el parentesco.', 'alert-type' => 'error']);
    }
}
```

---

## 🛣️ Rutas

### Rutas Definidas

**Ubicación:** `routes/web.php`

```php
Route::resource('parentescos', ParentescoController::class)
    ->names('admin.parentescos');

Route::get('parentescos/ajax/list', [ParentescoController::class, 'list'])
    ->name('admin.parentescos.ajax.list');
```

### Lista de Rutas

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| GET | `/admin/parentescos` | `admin.parentescos.index` | Listado principal |
| GET | `/admin/parentescos/create` | `admin.parentescos.create` | Formulario crear |
| POST | `/admin/parentescos` | `admin.parentescos.store` | Guardar nuevo |
| GET | `/admin/parentescos/{parentesco}` | `admin.parentescos.show` | Ver detalle |
| GET | `/admin/parentescos/{parentesco}/edit` | `admin.parentescos.edit` | Formulario editar |
| PUT/PATCH | `/admin/parentescos/{parentesco}` | `admin.parentescos.update` | Actualizar |
| DELETE | `/admin/parentescos/{parentesco}` | `admin.parentescos.destroy` | Eliminar (soft delete) |
| GET | `/admin/parentescos/ajax/list` | `admin.parentescos.ajax.list` | Listado AJAX |

**Middleware aplicado:**
- `loggin` - Autenticación de usuario
- `system` - Verificación de sistema

---

## 🔒 Policies

### Policy: `ParentescoPolicy`

**Ubicación:** `app/Policies/ParentescoPolicy.php`

### Métodos de Autorización

| Método | Permiso requerido | Descripción |
|--------|-------------------|-------------|
| `viewAny()` | `browse_parentescos` | Listar parentescos |
| `view()` | `read_parentescos` | Ver detalle de parentesco |
| `create()` | `add_parentescos` | Crear parentesco |
| `update()` | `edit_parentescos` | Editar parentesco |
| `delete()` | `delete_parentescos` | Eliminar parentesco |

**Código de la Policy:**

```php
class ParentescoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermission('browse_parentescos');
    }

    public function view(User $user, Parentesco $parentesco)
    {
        return $user->hasPermission('read_parentescos');
    }

    public function create(User $user)
    {
        return $user->hasPermission('add_parentescos');
    }

    public function update(User $user, Parentesco $parentesco)
    {
        return $user->hasPermission('edit_parentescos');
    }

    public function delete(User $user, Parentesco $parentesco)
    {
        return $user->hasPermission('delete_parentescos');
    }
}
```

### Permisos en Base de Datos

**Ubicación:** `database/seeders/PermissionsTableSeeder.php`

```php
'browse_parentescos' => 'Ver lista de parentescos',
'read_parentescos' => 'Ver detalles de un parentesco',
'edit_parentescos' => 'Editar información de parentescos',
'add_parentescos' => 'Agregar nuevos parentescos',
'delete_parentescos' => 'Eliminar parentescos',
```

### Permisos por Rol

| Rol | browse | read | add | edit | delete |
|-----|--------|------|-----|------|--------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| Operador | ✓ | ✓ | ✓ | ✓ | ✗ |
| Visitante | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## ✅ Requests

### StoreParentescoRequest

**Ubicación:** `app/Http/Requests/StoreParentescoRequest.php`

**Validación:**

```php
public function rules(): array
{
    return [
        'nombre' => 'required|string|max:50|unique:parentescos,nombre',
    ];
}
```

**Mensajes personalizados:**

```php
public function messages(): array
{
    return [
        'nombre.required' => 'El nombre del parentesco es obligatorio',
        'nombre.unique' => 'Este parentesco ya existe',
        'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
    ];
}
```

**Autorización:**

```php
public function authorize(): bool
{
    return Gate::allows('create', \App\Models\Parentesco::class);
}
```

### UpdateParentescoRequest

**Ubicación:** `app/Http/Requests/UpdateParentescoRequest.php`

**Validación:**

```php
public function rules(): array
{
    return [
        'nombre' => [
            'required',
            'string',
            'max:50',
            Rule::unique('parentescos')->ignore($this->route('parentesco'))
        ],
    ];
}
```

**Mensajes personalizados:**

```php
public function messages(): array
{
    return [
        'nombre.required' => 'El nombre del parentesco es obligatorio',
        'nombre.unique' => 'Este parentesco ya existe',
        'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
    ];
}
```

**Autorización:**

```php
public function authorize(): bool
{
    return Gate::allows('update', $this->route('parentesco'));
}
```

---

## 🎨 Vistas

### Estructura de Vistas

**Ubicación:** `resources/views/admin/parentescos/`

### 1. `browse.blade.php` - Listado principal

**Funcionalidades:**
- Interfaz de búsqueda y filtrado
- Tabla dinámica con carga AJAX
- Select de paginación (10, 25, 50, 100)
- Botón para crear nuevo parentesco
- Modal de confirmación para eliminar
- Auto-dismiss de alertas después de 5 segundos

### 2. `list.blade.php` - Contenido tabla AJAX

**Características:**
- Tabla con columnas: ID, Nombre, Tasas, Trámites, Acciones
- Badges con contadores de relaciones
- Colores dinámicos según cantidad de relaciones
- Botones de acción controlados por `@can` directives
- Paginación Laravel
- Estado vacío con imagen

**Ejemplo de código:**

```blade
<table class="table table-bordered table-hover">
    <thead>
        <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Tasas</th>
            <th>Trámites</th>
            <th class="text-right">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($parentescos as $p)
        <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->nombre }}</td>
            <td class="text-center">
                <span class="badge badge-info">{{ $p->tasas_count }}</span>
            </td>
            <td class="text-center">
                <span class="badge badge-{{ $p->adquirentes_tramite_count > 0 ? 'warning' : 'secondary' }}">
                    {{ $p->adquirentes_tramite_count }}
                </span>
            </td>
            <td class="text-right">
                @can('view', $p)
                    <a href="{{ route('admin.parentescos.show', $p) }}" class="btn btn-sm btn-warning">
                        <i class="voyager-eye"></i> Ver
                    </a>
                @endcan
                @can('update', $p)
                    <a href="{{ route('admin.parentescos.edit', $p) }}" class="btn btn-sm btn-primary">
                        <i class="voyager-edit"></i> Editar
                    </a>
                @endcan
                @can('delete', $p)
                    <button type="button"
                            class="btn btn-sm btn-danger"
                            onclick="deleteItem('{{ route('admin.parentescos.destroy', $p) }}', '{{ $p->nombre }}')"
                            data-toggle="modal"
                            data-target="#delete_modal">
                        <i class="voyager-trash"></i> Borrar
                    </button>
                @endcan
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="5">
                <h5 class="text-center">
                    <img src="{{ asset('images/empty.png') }}" width="120px" alt="">
                    <br><br>
                    No se encontraron parentescos
                </h5>
            </td>
        </tr>
        @endforelse
    </tbody>
</table>
```

### 3. `edit_add.blade.php` - Formulario crear/editar

**Características:**
- Formulario reutilizable para crear y editar
- Campo de texto `nombre` (máx 50 caracteres)
- Validación en tiempo real
- Auto-trim al perder foco
- Mensajes de ayuda al usuario
- Validación frontend adicional

### 4. `read.blade.php` - Vista detalle

**Características:**
- Información detallada del parentesco
- Muestra campos: ID, nombre, fechas, creador, actualizador
- Botones para editar o volver

---

## 🔗 Integración con otros Módulos

### 1. Módulo de Trámites (Wizard)

**Controlador:** `Admin\TramiteWizardController`
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php`

**Uso en Paso 3 - Adquirentes:**

```php
// Cargar parentescos con tasas para el departamento del Beni
$beni = \App\Models\Departamento::where('codigo', 'BE')->first();
$parentescos = Parentesco::with(['tasas' => function ($query) use ($beni) {
    if ($beni) {
        $query->where('departamento_id', $beni->id)
              ->where('vigente_desde', '<=', now())
              ->where(function ($q) {
                  $q->whereNull('vigente_hasta')
                    ->orWhere('vigente_hasta', '>=', now());
              });
    }
}])->get()->map(function ($parentesco) {
    $tasa = $parentesco->tasas->first();
    $parentesco->tasa_aplicable = $tasa ? $tasa->tasa : 0.00;
    return $parentesco;
});
```

### 2. Módulo de Cálculo ITGB

**Servicio:** `App\Services\IdtgbCalculator`
**Ubicación:** `app/Services/IdtgbCalculator.php`

**Métodos optimizados en modelo Tasa:**

```php
// Usar el método centralizado en lugar de queries dispersos
$tasaModel = Tasa::findApplicableRate($depId, $adq['parentesco_id'], $tipoId, $fPres);

// Método vigente ahora acepta parámetro opcional tipoTransmisionId
$tasa = Tasa::vigente($depId, $parId, $fecha, $tipoId);
// Prioriza tasas específicas sobre tasas genéricas
```

**Nuevo método en Tasa:**

```php
public static function findApplicableRate(int $departamentoId, int $parentescoId, int $tipoTransmisionId, ?string $fecha = null): ?self
{
    $fecha = $fecha ?? today()->toDateString();

    return self::where('departamento_id', $departamentoId)
               ->where('parentesco_id', $parentescoId)
               ->where('tipo_transmision_id', $tipoTransmisionId)
               ->where('vigente_desde', '<=', $fecha)
               ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                     ->orWhere('vigente_hasta', '>=', $fecha))
               ->latest('vigente_desde')
               ->first();
}
```

### 3. Tabla adquirentes_tramites

La tabla pivote `adquirentes_tramites` incluye el campo `parentesco_id`:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `tramite_id` | BIGINT | FK a trámites |
| `person_id` | BIGINT | FK a personas |
| `parentesco_id` | BIGINT | FK a parentescos |
| `porcentaje` | DECIMAL | Porcentaje de participación |
| `tasa_aplicada` | DECIMAL | Tasa calculada |
| `idtgb_proporcional` | DECIMAL | Monto calculado |

### 4. Índice compuesto en tasas

**Migración:** `2026_01_17_234526_add_composite_index_to_tasas_table.php`

```php
$table->index(['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'vigente_desde'], 'tasas_composite_index');
```

Este índice optimiza las búsquedas de tasas por departamento, parentesco, tipo de transmisión y vigencia.

---

## 📚 Ejemplos de Uso

### Ejemplo 1: Crear un nuevo parentesco

```php
// Vía Controller
$parentesco = Parentesco::create([
    'nombre' => 'Padre'
]);

// Vía Request
POST /admin/parentescos
{
    "nombre": "Padre"
}

// Vía tinker
php artisan tinker
>>> use App\Models\Parentesco;
>>> Parentesco::create(['nombre' => 'Padre']);
```

### Ejemplo 2: Obtener parentescos con tasas

```php
// Con todas las tasas
$parentescos = Parentesco::with('tasas')->get();

// Solo tasas vigentes del Beni
$beni = \App\Models\Departamento::where('codigo', 'BE')->first();
$parentescos = Parentesco::with(['tasas' => function ($query) use ($beni) {
    $query->where('departamento_id', $beni->id)
          ->where('vigente_desde', '<=', now())
          ->where(function ($q) {
              $q->whereNull('vigente_hasta')
                ->orWhere('vigente_hasta', '>=', now());
          });
}])->get();

// Mapear para agregar tasa aplicable
$parentescos = $parentescos->map(function ($parentesco) {
    $tasa = $parentesco->tasas->first();
    $parentesco->tasa_aplicable = $tasa ? $tasa->tasa : 0.00;
    return $parentesco;
});
```

### Ejemplo 3: Usar parentesco en cálculo

```php
use App\Services\IdtgbCalculator;

$calculator = app(IdtgbCalculator::class);

$liquidacion = $calculator->calculateEstimate(
    $baseImponible,
    $departamentoId,
    $parentescoId,  // <-- Parentesco del adquirente
    $tipoTransmisionId,
    $fechaTransmision,
    $fechaPresentacion,
    $fechaVencimiento
);
```

### Ejemplo 4: Verificar dependencias antes de eliminar

```php
$parentesco = Parentesco::find(1);

if ($parentesco->tasas()->exists()) {
    throw new Exception('El parentesco tiene tasas asociadas');
}

if ($parentesco->adquirentesTramite()->exists()) {
    throw new Exception('El parentesco está en uso en trámites');
}

$parentesco->delete(); // Soft delete
```

### Ejemplo 5: Restaurar parentesco eliminado

```php
// Encontrar parentesco eliminado
$parentesco = Parentesco::onlyTrashed()->find(1);

// Restaurar
$parentesco->restore();

// O con withTrashed
$parentesco = Parentesco::withTrashed()->find(1);
$parentesco->restore();
```

### Ejemplo 6: Listado con contadores

```php
$parentescos = Parentesco::withCount(['tasas', 'adquirentesTramite'])
    ->orderBy('id', 'desc')
    ->paginate(10);

foreach ($parentescos as $p) {
    echo $p->nombre . ': ';
    echo $p->tasas_count . ' tasas, ';
    echo $p->adquirentes_tramite_count . ' trámites';
}
```

---

## 🔍 Consideraciones Importantes

### Reglas de Negocio

1. **Unicidad:** El nombre del parentesco debe ser único en todo el sistema
2. **Dependencias:** No se puede eliminar un parentesco si tiene tasas asociadas o está en uso en trámites
3. **Longitud máxima:** El nombre no puede exceder 50 caracteres
4. **Tasas vigentes:** Solo se muestran tasas vigentes en el wizard de trámites
5. **Requerido:** El parentesco es obligatorio para los adquirentes en trámites
6. **Soft Deletes:** La eliminación es suave, los registros pueden restaurarse
7. **Auditoría:** Se registra automáticamente quién crea y modifica cada parentesco

### Validaciones Implementadas

**Al crear:**
- Nombre requerido
- Máximo 50 caracteres
- Debe ser único
- Registra `created_by` automáticamente

**Al actualizar:**
- Nombre requerido
- Máximo 50 caracteres
- Debe ser único (ignorando el registro actual)
- Registra `updated_by` automáticamente

**Al eliminar:**
- Verificar que no tenga tasas asociadas
- Verificar que no esté en uso en trámites
- Realiza soft delete
- Registra errores en logs

### Índices de Base de Datos

1. **Índice único:** `nombre` en tabla `parentescos`
2. **Índice compuesto:** `tasas_composite_index` en tabla `tasas` (departamento_id, parentesco_id, tipo_transmision_id, vigente_desde)
3. **Índices de foreign keys:** `created_by`, `updated_by`

---

## 📝 Guía para Desarrolladores

### Extender el módulo

#### 1. Agregar campos al modelo

**Paso 1:** Crear nueva migración

```bash
php artisan make:migration add_descripcion_to_parentescos_table --table=parentescos
```

**Paso 2:** Editar migración

```php
public function up(): void
{
    Schema::table('parentescos', function (Blueprint $table) {
        $table->text('descripcion')->nullable();
    });
}
```

**Paso 3:** Agregar a fillables

```php
protected $fillable = [
    'nombre',
    'descripcion',
    'created_by',
    'updated_by',
];
```

**Paso 4:** Actualizar Requests

```php
public function rules(): array
{
    return [
        'nombre' => 'required|string|max:50|unique:parentescos,nombre',
        'descripcion' => 'nullable|string|max:500',
    ];
}
```

**Paso 5:** Actualizar vistas

```blade
<div class="form-group">
    <label for="descripcion">Descripción</label>
    <textarea name="descripcion" id="descripcion" class="form-control" rows="3">
        {{ old('descripcion', optional($parentesco)->descripcion) }}
    </textarea>
</div>
```

#### 2. Agregar nuevas relaciones

```php
public function exenciones()
{
    return $this->belongsToMany(Exencion::class, 'exencion_parentesco');
}
```

#### 3. Implementar vista de papelera

**Crear ruta:**

```php
Route::get('parentescos/trashed', [ParentescoController::class, 'trashed'])
    ->name('admin.parentescos.trashed');
```

**Crear método en controlador:**

```php
public function trashed(Request $request)
{
    $this->authorize('viewAny', Parentesco::class);

    $search = $request->get('search', '');

    $parentescos = Parentesco::onlyTrashed()
        ->when($search, function ($query) use ($search) {
            $query->where('nombre', 'like', '%' . $search . '%');
        })
        ->orderBy('deleted_at', 'desc')
        ->paginate(10);

    return view('admin.parentescos.trashed', compact('parentescos'));
}
```

**Crear método restore:**

```php
public function restore($id)
{
    $this->authorize('delete', Parentesco::class);

    $parentesco = Parentesco::onlyTrashed()->findOrFail($id);
    $parentesco->restore();

    return redirect()->route('admin.parentescos.trashed')
        ->with(['message' => 'Parentesco restaurado.', 'alert-type' => 'success']);
}
```

### Buenas Prácticas

1. **Validación:** Usar Form Requests para validación centralizada y mantenible
2. **Autorización:** Siempre verificar permisos en Policies antes de ejecutar acciones
3. **Carga diferida:** Usar `with()` para relaciones en consultas de listado (N+1 problem)
4. **Cache:** Considerar caché para listados que no cambian frecuentemente
5. **Logs:** Los errores se registran automáticamente en `destroy()`
6. **Soft Deletes:** Usar soft deletes para mantener histórico
7. **Eventos:** Los eventos del modelo registran automáticamente `created_by` y `updated_by`
8. **withCount:** Usar `withCount()` para mostrar contadores de relaciones en listados

**Ejemplo de eager loading:**

```php
// Mal - N+1 queries
$parentescos = Parentesco::all();
foreach ($parentescos as $parentesco) {
    echo $parentesco->tasas->count(); // Query por cada parentesco
}

// Bien - 2 queries
$parentescos = Parentesco::with('tasas')->get();
foreach ($parentescos as $parentesco) {
    echo $parentesco->tasas->count(); // Ya cargado
}
```

**Ejemplo de cache:**

```php
use Illuminate\Support\Facades\Cache;

$parentescos = Cache::remember('parentescos.all', 3600, function () {
    return Parentesco::with('tasas')->get();
});
```

### Testing

Considerar crear tests para:

```php
<?php

namespace Tests\Feature;

use App\Models\Parentesco;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParentescoTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_index_requires_permission()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('admin.parentescos.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_create_parentesco()
    {
        $user = User::factory()->create();
        $user->givePermission('add_parentescos');

        $data = ['nombre' => 'Padre'];

        $response = $this->actingAs($user)
            ->post(route('admin.parentescos.store'), $data);

        $this->assertDatabaseHas('parentescos', [
            'nombre' => 'Padre',
            'created_by' => $user->id
        ]);
        $response->assertRedirect(route('admin.parentescos.index'));
    }

    /** @test */
    public function test_cannot_duplicate_parentesco()
    {
        $user = User::factory()->create();
        $user->givePermission('add_parentescos');

        Parentesco::create(['nombre' => 'Padre']);

        $data = ['nombre' => 'Padre'];

        $response = $this->actingAs($user)
            ->post(route('admin.parentescos.store'), $data);

        $response->assertSessionHasErrors('nombre');
    }

    /** @test */
    public function test_update_parentesco()
    {
        $user = User::factory()->create();
        $user->givePermission('edit_parentescos');

        $parentesco = Parentesco::create(['nombre' => 'Padre']);
        $data = ['nombre' => 'Padre Biológico'];

        $response = $this->actingAs($user)
            ->put(route('admin.parentescos.update', $parentesco), $data);

        $this->assertDatabaseHas('parentescos', [
            'id' => $parentesco->id,
            'nombre' => 'Padre Biológico',
            'updated_by' => $user->id
        ]);
    }

    /** @test */
    public function test_soft_delete_parentesco()
    {
        $user = User::factory()->create();
        $user->givePermission('delete_parentescos');

        $parentesco = Parentesco::create(['nombre' => 'Padre']);

        $response = $this->actingAs($user)
            ->delete(route('admin.parentescos.destroy', $parentesco));

        $this->assertSoftDeleted('parentescos', ['id' => $parentesco->id]);
        $this->assertDatabaseHas('parentescos', ['id' => $parentesco->id]);
    }

    /** @test */
    public function test_cannot_delete_with_tasas()
    {
        $user = User::factory()->create();
        $user->givePermission('delete_parentescos');

        $parentesco = Parentesco::create(['nombre' => 'Padre']);
        $parentesco->tasas()->create([
            'tasa' => 1.5,
            'departamento_id' => 1,
            'vigente_desde' => now()
        ]);

        $response = $this->actingAs($user)
            ->delete(route('admin.parentescos.destroy', $parentesco));

        $response->assertRedirect(route('admin.parentescos.index'));
        $response->assertSessionHas('alert-type', 'error');
        $this->assertDatabaseHas('parentescos', ['id' => $parentesco->id]);
        $this->assertNull($parentesco->deleted_at);
    }

    /** @test */
    public function test_cannot_delete_with_adquirentes()
    {
        $user = User::factory()->create();
        $user->givePermission('delete_parentescos');

        $parentesco = Parentesco::create(['nombre' => 'Padre']);

        $response = $this->actingAs($user)
            ->delete(route('admin.parentescos.destroy', $parentesco));

        $response->assertRedirect(route('admin.parentescos.index'));
        $response->assertSessionHas('alert-type', 'error');
        $this->assertDatabaseHas('parentescos', ['id' => $parentesco->id]);
        $this->assertNull($parentesco->deleted_at);
    }

    /** @test */
    public function test_search_functionality()
    {
        $user = User::factory()->create();
        $user->givePermission('browse_parentescos');

        Parentesco::create(['nombre' => 'Padre']);
        Parentesco::create(['nombre' => 'Madre']);
        Parentesco::create(['nombre' => 'Hermano']);

        $response = $this->actingAs($user)
            ->get(route('admin.parentescos.ajax.list', ['search' => 'Pad']));

        $response->assertStatus(200);
        $response->assertSee('Padre');
        $response->assertDontSee('Madre');
    }

    /** @test */
    public function test_ajax_list_pagination()
    {
        $user = User::factory()->create();
        $user->givePermission('browse_parentescos');

        Parentesco::factory()->count(15)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.parentescos.ajax.list', ['paginate' => 10]));

        $response->assertStatus(200);
    }
}
```

### Comandos Útiles

```bash
# Crear nueva migración
php artisan make:migration add_field_to_parentescos_table --table=parentescos

# Ejecutar migraciones
php artisan migrate

# Rollback última migración
php artisan migrate:rollback

# Crear controller
php artisan make:controller ParentescoController --resource

# Crear request
php artisan make:request StoreParentescoRequest
php artisan make:request UpdateParentescoRequest

# Crear policy
php artisan make:policy ParentescoPolicy --model=Parentesco

# Crear factory
php artisan make:factory ParentescoFactory

# Crear seeder
php artisan make:seeder ParentescoSeeder

# Ejecutar seeder
php artisan db:seed --class=ParentescoSeeder

# Ejecutar tests
php artisan test --filter ParentescoTest

# Limpiar cache
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
```

---

## 📊 Resumen de Mejoras Implementadas

### Bugs Corregidos ✅

1. **Protección contra eliminación de parentescos en uso**
   - Agregada relación `adquirentesTramite()` en modelo
   - Validación en `destroy()` antes de eliminar
   - Previene integridad referencial rota

2. **Manejo de errores mejorado**
   - Logging activado en `destroy()`
   - Mensajes de error más específicos
   - Facilita depuración de problemas

### Mejoras Implementadas 🚀

1. **Soft Deletes**
   - Migración creada y ejecutada
   - Trait agregado al modelo
   - Posibilidad de restaurar registros

2. **Listados optimizados**
   - `withCount(['tasas', 'adquirentesTramite'])` agregado
   - Vista actualizada con badges de contadores
   - Colores dinámicos según estado

3. **Auditoría completa**
   - Campos `created_by`, `updated_by` agregados
   - Eventos de modelo automáticos
   - Relaciones con usuarios

4. **Optimización de búsquedas de tasas**
   - Método `Tasa::findApplicableRate()` creado
   - Centraliza lógica de búsqueda
   - Incluye tipo de transmisión

5. **Índice compuesto en tasas**
   - Migración creada y ejecutada
   - Optimiza búsquedas por departamento, parentesco, tipo y vigencia
   - Mejora rendimiento general

**Última actualización:** Enero 2026

**Versión:** 2.0.0

## 📝 Historial de Cambios

### Versión 2.0.0 (Enero 2026)

**Mejoras en módulo de Tasas:**
- Método `Tasa::vigente()` actualizado para aceptar parámetro opcional `tipoTransmisionId`
- Ahora prioriza tasas específicas sobre tasas genéricas cuando se proporciona el tipo de transmisión
- Usa `orderBy('tipo_transmision_id', 'desc')` para asegurar el orden correcto
- Mejora en el método `Tasa::findApplicableRate()` con lógica centralizada

**Beneficios:**
- Cálculos más precisos cuando existen tasas específicas y genéricas
- Previene errores legales en el cálculo del impuesto
- Código más mantenible y reutilizable

---

