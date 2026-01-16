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

### Importancia en el Sistema ITGB
El parentesco seleccionado para un adquirente determina directamente la tasa impositiva aplicable en el cálculo del ITGB. Diferentes parentescos tienen tasas diferentes según la legislación vigente en cada departamento.

---

## 🗄️ Base de Datos

### Migración: `create_parentescos_table.php`

**Ubicación:** `database/migrations/2025_09_22_122721_create_parentescos_table.php`

**Estructura de la tabla:**

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre del parentesco (ej: "Padre", "Hermano") |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |

**Relaciones:**
- Tiene muchos `Tasa` (un parentesco puede tener múltiples tasas asociadas por departamento)
- Es utilizado por la tabla pivote `adquirentes_tramites`

**Ejemplo de SQL:**

```sql
CREATE TABLE parentescos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

---

## 🧩 Modelo

### Modelo: `Parentesco`

**Ubicación:** `app/Models/Parentesco.php`

**Atributos:**
- `$table = 'parentescos'`
- `$fillable = ['nombre']`

**Métodos de relación:**

```php
public function tasas()
{
    return $this->hasMany(Tasa::class);
}
```

**Uso del modelo:**

```php
// Obtener todos los parentescos
$parentescos = Parentesco::all();

// Obtener un parentesco específico
$parentesco = Parentesco::find(1);

// Obtener parentescos con sus tasas cargadas
$parentescos = Parentesco::with('tasas')->get();

// Crear un nuevo parentesco
Parentesco::create(['nombre' => 'Padre']);

// Actualizar un parentesco
$parentesco->update(['nombre' => 'Padre Biológico']);

// Buscar por nombre
$parentesco = Parentesco::where('nombre', 'Hermano')->first();

// Verificar si tiene tasas asociadas
if ($parentesco->tasas()->count() > 0) {
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
- Retorna lista paginada de parentescos
- Parámetros:
  - `search` (string): Filtro por nombre
  - `paginate` (int): Cantidad de registros por página (default: 10)
- Ordenamiento: ID descendente

**Código del método:**

```php
public function list(Request $request)
{
    $this->authorize('viewAny', Parentesco::class);

    $search   = $request->get('search', '');
    $paginate = $request->get('paginate', 10);

    $parentescos = Parentesco::when($search, function ($query) use ($search) {
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

#### 8. `destroy(Parentesco $parentesco)` - Eliminar
```
DELETE /admin/parentescos/{parentesco}
```
- Verifica que no tenga tasas asociadas antes de eliminar
- Retorna error si tiene dependencias
- Requiere permiso: `delete_parentescos`

**Código del método:**

```php
public function destroy(Parentesco $parentesco)
{
    $this->authorize('delete', $parentesco);

    // Validación de dependencias antes de eliminar
    if ($parentesco->tasas()->count() > 0) {
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'No se puede eliminar: El parentesco tiene tasas asociadas.', 'alert-type' => 'error']);
    }

    try {
        $parentesco->delete();
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Parentesco eliminado.', 'alert-type' => 'success']);
    } catch (\Exception $e) {
        return redirect()->route('admin.parentescos.index')
            ->with(['message' => 'Error al eliminar el parentesco.', 'alert-type' => 'error']);
    }
}
```

---

## 🛣️ Rutas

### Rutas Definidas

**Ubicación:** `routes/web.php:100-101`

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
| DELETE | `/admin/parentescos/{parentesco}` | `admin.parentescos.destroy` | Eliminar |
| GET | `/admin/parentescos/ajax/list` | `admin.parentescos.ajax.list` | Listado AJAX |

**Middleware aplicado:**
- `loggin` - Autenticación de usuario
- `system` - Verificación de sistema

**Grupo de rutas:**
```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // Rutas de parentescos aquí
});
```

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

**Ubicación:** `database/seeders/PermissionsTableSeeder.php:79-83`

```php
'browse_parentescos' => 'Ver lista de parentescos',
'read_parentescos' => 'Ver detalles de un parentesco',
'edit_parentescos' => 'Editar información de parentescos',
'add_parentescos' => 'Agregar nuevos parentescos',
'delete_parentescos' => 'Eliminar parentescos',
```

**Tabla de permisos:** `permissions` con `table_name = 'parentescos'`

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

**Funcionalidades JavaScript:**
- Búsqueda con debounce (500ms)
- Paginación dinámica
- Loading con animación
- Manejo de errores AJAX

**Código JavaScript principal:**

```javascript
function list(page = 1) {
    let url = '/admin/parentescos/ajax/list';
    let search = $('#input-search').val() ? $('#input-search').val().trim() : '';

    // Mostrar loading
    $('#div-results').html(`
        <div class="text-center" style="padding: 40px">
            <i class="voyager-refresh voyager-2x loading-icon"></i>
            <br>Cargando...
        </div>
    `);

    $.ajax({
        url: `${url}?search=${encodeURIComponent(search)}&paginate=${countPage}&page=${page}`,
        type: 'get',
        success: function (response) {
            $('#div-results').html(response);
        },
        error: function (xhr) {
            console.error('Error:', xhr.responseText);
            $('#div-results').html(`
                <div class="alert alert-danger text-center">
                    <i class="voyager-warning"></i><br>
                    Error al cargar los datos.<br>
                    <button onclick="list(${page})" class="btn btn-xs btn-default mt-2">Reintentar</button>
                </div>
            `);
        }
    });
}
```

### 2. `list.blade.php` - Contenido tabla AJAX

**Características:**
- Tabla con columnas: ID, Nombre, Acciones
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
            <th class="text-right">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse($parentescos as $p)
        <tr>
            <td>{{ $p->id }}</td>
            <td>{{ $p->nombre }}</td>
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
            <td colspan="3">
                <h5 class="text-center">No se encontraron parentescos</h5>
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

**Ejemplo de código:**

```blade
<form action="{{ ($parentesco->exists ?? false)
        ? route('admin.parentescos.update', $parentesco)
        : route('admin.parentescos.store') }}"
      method="POST" id="parentesco-form">
    @csrf
    @if($parentesco->exists ?? false) @method('PUT') @endif

    <div class="form-group">
        <label for="nombre">Nombre <span class="required">*</span></label>
        <input type="text"
               name="nombre"
               id="nombre"
               class="form-control @error('nombre') is-invalid @enderror"
               placeholder="Ej: Padre, Madre, Hermano, etc."
               maxlength="50"
               value="{{ old('nombre', optional($parentesco)->nombre) }}"
               required
               autofocus>
        @error('nombre')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <small class="form-text text-muted">
            Máximo 50 caracteres. El nombre debe ser único en el sistema.
        </small>
    </div>

    <button type="submit" class="btn btn-primary">
        {{ ($parentesco->exists ?? false) ? 'Actualizar' : 'Guardar' }}
    </button>
</form>
```

**JavaScript de validación:**

```javascript
$('#nombre').on('blur', function() {
    $(this).val($(this).val().trim());
});

$('#parentesco-form').on('submit', function() {
    const nombre = $('#nombre').val().trim();
    $('#nombre').val(nombre);

    if (!nombre) {
        alert('El nombre del parentesco es obligatorio');
        $('#nombre').focus();
        return false;
    }
});
```

### 4. `read.blade.php` - Vista detalle

**Características:**
- Información detallada del parentesco
- Muestra campos: ID, nombre, fechas
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

**Vista:** `admin/tramites/wizard/create_step_3.blade.php`

- Select2 para elegir parentesco al agregar adquirente
- Muestra la tasa aplicable junto al parentesco
- Validación: `parentesco_id` es requerido y debe existir

**Validación al agregar adquirente:**

```php
$request->validate([
    'person_id' => 'required|exists:people,id',
    'parentesco_id' => 'required|exists:parentescos,id',
    'porcentaje' => 'required|numeric|min:0.01|max:100',
]);
```

**Al guardar el trámite:**

```php
$tramite->adquirentes()->create([
    'person_id' => $adqData['person_id'],
    'parentesco_id' => $adqData['parentesco_id'],
    'tasa_aplicada' => 0,
    'porcentaje' => $adqData['porcentaje'] ?? 0,
    'idtgb_proporcional' => 0,
    'es_beneficiario_exencion' => false
]);
```

### 2. Módulo de Cálculo ITGB

**Servicio:** `App\Services\IdtgbCalculator`
**Ubicación:** `app/Services/IdtgbCalculator.php`

**Uso en cálculo:**

```php
// Se usa parentesco_id para calcular la tasa aplicable
$tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);
```

**Ejemplo de uso en el servicio:**

```php
$adquirentesData = collect($wizardData['step3']['adquirentes'])->map(function($adq) {
    return [
        'parentesco_id' => $adq['parentesco_id'],
        'porcentaje' => $adq['porcentaje'],
    ];
})->all();
```

### 3. Calculadora Beni

**Controlador:** `CalculadoraBeniController`
**Ubicación:** `app/Http/Controllers/CalculadoraBeniController.php`

**Uso:**

```php
$parentescos = Parentesco::all();
```

**Validación:**

```php
'parentesco_id' => 'required|exists:parentescos,id',
```

### 4. Tabla adquirentes_tramites

La tabla pivote `adquirentes_tramites` incluye el campo `parentesco_id`:

| Campo | Tipo | Descripción |
|-------|------|-------------|
| `tramite_id` | BIGINT | FK a trámites |
| `person_id` | BIGINT | FK a personas |
| `parentesco_id` | BIGINT | FK a parentescos |
| `porcentaje` | DECIMAL | Porcentaje de participación |
| `tasa_aplicada` | DECIMAL | Tasa calculada |
| `idtgb_proporcional` | DECIMAL | Monto calculado |

### 5. Vistas de reportes

**Vistas que muestran parentescos:**
- `admin/tramites/adquirentes/read.blade.php:43` - Muestra parentesco del adquirente
- `admin/tramites/wizard/create_step_3.blade.php:97` - Selector de parentesco
- `admin/tramites/wizard/create_step_7.blade.php` - Resumen con parentescos

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

if ($parentesco->tasas()->count() > 0) {
    // No se puede eliminar
    throw new Exception('El parentesco tiene tasas asociadas');
}

$parentesco->delete();
```

### Ejemplo 5: Listado con AJAX

```javascript
// JavaScript en browse.blade.php
function list(page = 1) {
    let url = '/admin/parentescos/ajax/list';
    let search = $('#input-search').val().trim();
    let paginate = $('#select-paginate').val();

    $.ajax({
        url: `${url}?search=${encodeURIComponent(search)}&paginate=${paginate}&page=${page}`,
        type: 'get',
        success: function (response) {
            $('#div-results').html(response);
        }
    });
}
```

### Ejemplo 6: Formulario de creación

```blade
<!-- resources/views/admin/parentescos/edit_add.blade.php -->
<form action="{{ route('admin.parentescos.store') }}" method="POST">
    @csrf

    <div class="form-group">
        <label for="nombre">Nombre <span class="required">*</span></label>
        <input type="text"
               name="nombre"
               id="nombre"
               class="form-control"
               placeholder="Ej: Padre, Madre, Hermano, etc."
               maxlength="50"
               required
               autofocus>
        <small class="form-text text-muted">
            Máximo 50 caracteres. El nombre debe ser único en el sistema.
        </small>
    </div>

    <button type="submit" class="btn btn-primary">
        Guardar
    </button>
</form>
```

---

## 🔍 Consideraciones Importantes

### Reglas de Negocio

1. **Unicidad:** El nombre del parentesco debe ser único en todo el sistema
2. **Dependencias:** No se puede eliminar un parentesco si tiene tasas asociadas
3. **Longitud máxima:** El nombre no puede exceder 50 caracteres
4. **Tasas vigentes:** Solo se muestran tasas vigentes en el wizard de trámites
5. **Requerido:** El parentesco es obligatorio para los adquirentes en trámites

### Validaciones Implementadas

**Al crear:**
- Nombre requerido
- Máximo 50 caracteres
- Debe ser único

**Al actualizar:**
- Nombre requerido
- Máximo 50 caracteres
- Debe ser único (ignorando el registro actual)

**Al eliminar:**
- Verificar que no tenga tasas asociadas
- Capturar excepciones de base de datos

### Permisos por Rol

| Rol | browse | read | add | edit | delete |
|-----|--------|------|-----|------|--------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| Operador | ✓ | ✓ | ✓ | ✓ | ✗ |
| Visitante | ✓ | ✓ | ✗ | ✗ | ✗ |

### Ícono en Menú

**Ubicación:** `database/seeders/IdtgbMenuAppendSeeder.php:29`

```php
['title' => 'Parentescos',
 'route' => 'admin.parentescos.index',
 'icon_class' => 'fa-solid fa-people-group',
 'order' => 4]
```

### Mensajes de Éxito/Error

**Éxito:**
- "Parentesco creado."
- "Parentesco actualizado."
- "Parentesco eliminado."

**Error:**
- "No se puede eliminar: El parentesco tiene tasas asociadas."
- "Error al eliminar el parentesco."

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
// app/Models/Parentesco.php
protected $fillable = [
    'nombre',
    'descripcion',
];
```

**Paso 4:** Actualizar Requests

```php
// app/Http/Requests/StoreParentescoRequest.php
'nombre' => 'required|string|max:50|unique:parentescos,nombre',
'descripcion' => 'nullable|string|max:500',
```

**Paso 5:** Actualizar vistas

```blade
<!-- resources/views/admin/parentescos/edit_add.blade.php -->
<div class="form-group">
    <label for="descripcion">Descripción</label>
    <textarea name="descripcion" id="descripcion" class="form-control" rows="3">{{ old('descripcion', optional($parentesco)->descripcion) }}</textarea>
</div>
```

#### 2. Agregar nuevas relaciones

```php
// app/Models/Parentesco.php
public function adquirentes()
{
    return $this->hasManyThrough(Person::class, AdquirenteTramite::class);
}

public function exenciones()
{
    return $this->belongsToMany(Exencion::class, 'exencion_parentesco');
}
```

#### 3. Modificar lógica de eliminación

**Implementar Soft Deletes:**

```bash
php artisan make:migration add_soft_deletes_to_parentescos_table --table=parentescos
```

```php
// app/Models/Parentesco.php
use Illuminate\Database\Eloquent\SoftDeletes;

class Parentesco extends Model
{
    use HasFactory, SoftDeletes;

    // ...
}
```

**Actualizar método destroy:**

```php
public function destroy(Parentesco $parentesco)
{
    $this->authorize('delete', $parentesco);

    $parentesco->delete(); // Soft delete

    return redirect()->route('admin.parentescos.index')
        ->with(['message' => 'Parentesco eliminado (soft delete).', 'alert-type' => 'success']);
}
```

### Buenas Prácticas

1. **Validación:** Usar Form Requests para validación centralizada y mantenible
2. **Autorización:** Siempre verificar permisos en Policies antes de ejecutar acciones
3. **Carga diferida:** Usar `with()` para relaciones en consultas de listado (N+1 problem)
4. **Cache:** Considerar caché para listados que no cambian frecuentemente
5. **Logs:** Implementar logs de auditoría para cambios en parentescos
6. **Soft Deletes:** Usar soft deletes para mantener histórico
7. **Eventos:** Usar modelos de eventos para lógica adicional al crear/actualizar/eliminar

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
    return Parentesco::all();
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

        $this->assertDatabaseHas('parentescos', $data);
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

        $this->assertDatabaseHas('parentescos', $data);
    }

    /** @test */
    public function test_delete_without_tasas()
    {
        $user = User::factory()->create();
        $user->givePermission('delete_parentescos');

        $parentesco = Parentesco::create(['nombre' => 'Padre']);

        $response = $this->actingAs($user)
            ->delete(route('admin.parentescos.destroy', $parentesco));

        $this->assertDatabaseMissing('parentescos', ['id' => $parentesco->id]);
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

## 📞 Soporte y Mantenimiento

Para consultas o reportar issues relacionados con el módulo de Parentescos, contactar al equipo de desarrollo o revisar la documentación del sistema ITGB.

**Documentación relacionada:**
- Documentación del módulo de Trámites
- Documentación del servicio IdtgbCalculator
- Documentación del módulo de Tasas

**Última actualización:** Enero 2026

**Versión:** 1.0.0
