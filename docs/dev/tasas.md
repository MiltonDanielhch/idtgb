# Documentación Técnica - Módulo de Tasas

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

El módulo de **Tasas** gestiona las tasas impositivas aplicables al Impuesto de Transmisiones Gratuítas de Bienes (ITGB) según el departamento, parentesco y tipo de transmisión. Este módulo es fundamental para el cálculo correcto del impuesto en cada trámite.

### Propósito
- Mantener un catálogo de tasas impositivas por departamento, parentesco y tipo de transmisión
- Permitir la vigencia temporal de tasas con fechas de inicio y fin
- Facilitar el cálculo automático del ITGB en trámites
- Gestionar el ciclo de vida CRUD completo de tasas

### Importancia en el Sistema ITGB
La tasa seleccionada determina directamente el monto del impuesto a pagar. Diferentes combinaciones de departamento, parentesco y tipo de transmisión pueden tener tasas diferentes según la legislación vigente.

---

## 🗄️ Base de Datos

### Migración: `create_tasas_table.php`

**Ubicación:** `database/migrations/2025_09_22_122739_create_tasas_table.php`

**Estructura de la tabla:**

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `departamento_id` | BIGINT | FK, NOT NULL | Departamento al que aplica la tasa |
| `parentesco_id` | BIGINT | FK, NOT NULL | Parentesco del adquirente |
| `tipo_transmision_id` | BIGINT | FK, NULLABLE | Tipo de transmisión (opcional) |
| `tasa` | DECIMAL(5,2) | NOT NULL | Porcentaje de tasa (0.00 a 99.99) |
| `vigente_desde` | DATE | NOT NULL | Fecha desde la que está vigente |
| `vigente_hasta` | DATE | NULLABLE | Fecha hasta la que está vigente (null = indefinido) |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |

**Relaciones:**
- Pertenece a `Departamento` (departamento_id)
- Pertenece a `Parentesco` (parentesco_id)
- Pertenece a `TipoTransmision` (tipo_transmision_id, opcional)

**Índice único compuesto:**

```php
$table->unique(
    ['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'vigente_desde'],
    'uq_tasas_dep_par_trans_vig'
);
```

Este índice asegura que no haya duplicados con la misma combinación de departamento, parentesco, tipo de transmisión y fecha de vigencia.

**Ejemplo de SQL:**

```sql
CREATE TABLE tasas (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    departamento_id BIGINT UNSIGNED NOT NULL,
    parentesco_id BIGINT UNSIGNED NOT NULL,
    tipo_transmision_id BIGINT UNSIGNED NULL,
    tasa DECIMAL(5,2) NOT NULL,
    vigente_desde DATE NOT NULL,
    vigente_hasta DATE NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    FOREIGN KEY (departamento_id) REFERENCES departamentos(id),
    FOREIGN KEY (parentesco_id) REFERENCES parentescos(id),
    FOREIGN KEY (tipo_transmision_id) REFERENCES tipos_transmision(id),
    UNIQUE KEY uq_tasas_dep_par_trans_vig (departamento_id, parentesco_id, tipo_transmision_id, vigente_desde)
);
```

---

## 🧩 Modelo

### Modelo: `Tasa`

**Ubicación:** `app/Models/Tasa.php`

**Atributos:**
- `$table = 'tasas'`
- `$fillable = ['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'tasa', 'vigente_desde', 'vigente_hasta']`

**Casts:**
- `tasa` → `decimal:2` (convierte a número con 2 decimales)
- `vigente_desde` → `date`
- `vigente_hasta` → `date`

**Relaciones:**

```php
// Una tasa pertenece a un departamento
public function departamento()
{
    return $this->belongsTo(Departamento::class);
}

// Una tasa pertenece a un parentesco
public function parentesco()
{
    return $this->belongsTo(Parentesco::class);
}

// Una tasa pertenece opcionalmente a un tipo de transmisión
public function tipoTransmision()
{
    return $this->belongsTo(TipoTransmision::class, 'tipo_transmision_id');
}
```

**Métodos Helper:**

#### `vigente(int $departamentoId, int $parentescoId, ?string $fecha = null): ?self`

Retorna la tasa vigente para un departamento, parentesco y fecha específicos.

```php
public static function vigente(int $departamentoId, int $parentescoId, ?string $fecha = null): ?self
{
    $fecha = $fecha ?? today()->toDateString();

    return self::where('departamento_id', $departamentoId)
               ->where('parentesco_id', $parentescoId)
               ->where('vigente_desde', '<=', $fecha)
               ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                     ->orWhere('vigente_hasta', '>=', $fecha))
               ->first();
}
```

**Uso del modelo:**

```php
// Obtener todas las tasas
$tasas = Tasa::all();

// Obtener una tasa específica
$tasa = Tasa::find(1);

// Obtener tasas con relaciones cargadas
$tasas = Tasa::with(['departamento', 'parentesco', 'tipoTransmision'])->get();

// Buscar tasa vigente hoy para el Beni y parentesco Padre
$beni = \App\Models\Departamento::where('codigo', 'BE')->first()->id;
$padre = \App\Models\Parentesco::where('nombre', 'Padre')->first()->id;
$tasa = Tasa::vigente($beni, $padre);

// Buscar tasa vigente para una fecha específica
$tasa = Tasa::vigente($depId, $parId, '2025-12-31');

// Crear una nueva tasa
Tasa::create([
    'departamento_id' => 1,
    'parentesco_id' => 1,
    'tipo_transmision_id' => null,
    'tasa' => 1.50,
    'vigente_desde' => '2025-01-01',
    'vigente_hasta' => null
]);

// Actualizar una tasa
$tasa->update(['tasa' => 2.00]);

// Eliminar una tasa
$tasa->delete();
```

---

## 🎮 Controlador

### Controlador: `TasaController`

**Ubicación:** `app/Http/Controllers/TasaController.php`

**Middleware:**
- `auth` - Requiere autenticación

### Métodos del Controlador

#### 1. `index()` - Vista principal
```
GET /admin/tasas
```
- Muestra la vista `browse.blade.php` con tabla interactiva
- Requiere permiso: `browse_tasas`
- Carga datos vía AJAX

#### 2. `list()` - Listado AJAX
```
GET /admin/tasas/ajax/list
```
- Retorna lista paginada de tasas con relaciones
- Parámetros:
  - `search` (string): Filtro por nombre de departamento, parentesco o valor de tasa
  - `paginate` (int): Cantidad de registros por página (default: 10)
- Ordenamiento: ID descendente
- Requiere permiso: `browse_tasas`

**Código del método:**

```php
public function list()
{
    $this->authorize('viewAny', Tasa::class);

    $search   = request('search');
    $paginate = request('paginate', 10);

    $data = Tasa::with(['departamento', 'parentesco', 'tipoTransmision'])
        ->when($search, fn($q) => $q->whereHas('departamento', fn($b) => $b->where('nombre', 'like', "%{$search}%"))
            ->orWhereHas('parentesco', fn($b) => $b->where('nombre', 'like', "%{$search}%"))
            ->orWhere('tasa', 'like', "%{$search}%"))
        ->orderByDesc('id')
        ->paginate($paginate);

    return view('admin.tasas.list', compact('data'));
}
```

#### 3. `show(Tasa $tasa)` - Ver detalle
```
GET /admin/tasas/{tasa}
```
- Muestra vista detallada `read.blade.php`
- Requiere permiso: `read_tasas`

#### 4. `create()` - Formulario de creación
```
GET /admin/tasas/create
```
- Muestra formulario `edit-add.blade.php`
- Carga departamentos, parentescos y tipos de transmisión
- Requiere permiso: `add_tasas`

**Código del método:**

```php
public function create()
{
    $this->authorize('create', Tasa::class);
    return view('admin.tasas.edit-add', [
        'tasa' => new Tasa(),
        'departamentos' => Departamento::orderBy('nombre')->get(),
        'parentescos'   => Parentesco::orderBy('nombre')->get(),
        'tipos'         => TipoTransmision::orderBy('nombre')->get(),
    ]);
}
```

#### 5. `store(StoreTasaRequest $request)` - Guardar nuevo
```
POST /admin/tasas
```
- Valida y crea nueva tasa
- Redirige al listado con mensaje de éxito
- Requiere permiso: `add_tasas`

**Código del método:**

```php
public function store(StoreTasaRequest $request)
{
    $this->authorize('create', Tasa::class);
    Tasa::create($request->validated());
    return redirect()->route('admin.tasas.index')
        ->with(['message' => 'Tasa creada.', 'alert-type' => 'success']);
}
```

#### 6. `edit(Tasa $tasa)` - Formulario de edición
```
GET /admin/tasas/{tasa}/edit
```
- Muestra formulario con datos existentes
- Carga departamentos, parentescos y tipos de transmisión
- Requiere permiso: `edit_tasas`

#### 7. `update(UpdateTasaRequest $request, Tasa $tasa)` - Actualizar
```
PUT /admin/tasas/{tasa}
```
- Valida y actualiza tasa
- Redirige al listado con mensaje de éxito
- Requiere permiso: `edit_tasas`

**Código del método:**

```php
public function update(UpdateTasaRequest $request, Tasa $tasa)
{
    $this->authorize('update', $tasa);
    $tasa->update($request->validated());
    return redirect()->route('admin.tasas.index')
        ->with(['message' => 'Tasa actualizada.', 'alert-type' => 'success']);
}
```

#### 8. `destroy(Tasa $tasa)` - Eliminar
```
DELETE /admin/tasas/{tasa}
```
- Elimina la tasa
- Redirige al listado con mensaje de éxito
- Requiere permiso: `delete_tasas`

**Código del método:**

```php
public function destroy(Tasa $tasa)
{
    $this->authorize('delete', $tasa);
    $tasa->delete();
    return redirect()->route('admin.tasas.index')
        ->with(['message' => 'Tasa eliminada.', 'alert-type' => 'success']);
}
```

---

## 🛣️ Rutas

### Rutas Definidas

**Ubicación:** `routes/web.php:112-113`

```php
Route::resource('tasas', TasaController::class)
    ->names('admin.tasas');

Route::get('tasas/ajax/list', [TasaController::class, 'list'])
    ->name('admin.tasas.ajax.list');
```

### Lista de Rutas

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| GET | `/admin/tasas` | `admin.tasas.index` | Listado principal |
| GET | `/admin/tasas/create` | `admin.tasas.create` | Formulario crear |
| POST | `/admin/tasas` | `admin.tasas.store` | Guardar nuevo |
| GET | `/admin/tasas/{tasa}` | `admin.tasas.show` | Ver detalle |
| GET | `/admin/tasas/{tasa}/edit` | `admin.tasas.edit` | Formulario editar |
| PUT/PATCH | `/admin/tasas/{tasa}` | `admin.tasas.update` | Actualizar |
| DELETE | `/admin/tasas/{tasa}` | `admin.tasas.destroy` | Eliminar |
| GET | `/admin/tasas/ajax/list` | `admin.tasas.ajax.list` | Listado AJAX |

**Middleware aplicado:**
- `loggin` - Autenticación de usuario
- `system` - Verificación de sistema

**Grupo de rutas:**
```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // Rutas de tasas aquí
});
```

---

## 🔒 Policies

### Policy: `TasaPolicy`

**Ubicación:** `app/Policies/TasaPolicy.php`

### Métodos de Autorización

| Método | Permiso requerido | Descripción |
|--------|-------------------|-------------|
| `viewAny()` | `browse_tasas` | Listar tasas |
| `view()` | `read_tasas` | Ver detalle de tasa |
| `create()` | `add_tasas` | Crear tasa |
| `update()` | `edit_tasas` | Editar tasa |
| `delete()` | `delete_tasas` | Eliminar tasa |
| `before()` | `browse_admin` | Bypass para admin |

**Código de la Policy:**

```php
class TasaPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user){
        return $user->hasPermission('browse_tasas');
    }

    public function view(User $user, Tasa $tasa){
        return $user->hasPermission('read_tasas');
    }

    public function create(User $user){
        return $user->hasPermission('add_tasas');
    }

    public function update(User $user, Tasa $tasa){
        return $user->hasPermission('edit_tasas');
    }

    public function delete(User $user, Tasa $tasa){
        return $user->hasPermission('delete_tasas');
    }

    public function before(User $user, $ability)
    {
        return $user->hasPermission('browse_admin') ?: null;
    }
}
```

### Permisos en Base de Datos

**Ubicación:** `database/seeders/PermissionsTableSeeder.php`

```php
Permission::generateFor('tasas');
```

**Permisos creados:**

| Permiso | Descripción |
|---------|-------------|
| `browse_tasas` | Ver lista de tasas |
| `read_tasas` | Ver detalles de una tasa |
| `edit_tasas` | Editar tasas |
| `add_tasas` | Agregar nuevas tasas |
| `delete_tasas` | Eliminar tasas |

### Permisos por Rol

| Rol | browse | read | add | edit | delete |
|-----|--------|------|-----|------|--------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| Operador | ✓ | ✓ | ✓ | ✓ | ✗ |
| Visitante | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## ✅ Requests

### StoreTasaRequest

**Ubicación:** `app/Http/Requests/StoreTasaRequest.php`

**Validación:**

```php
public function rules()
{
    return [
        'departamento_id'      => 'required|exists:departamentos,id',
        'parentesco_id'        => 'required|exists:parentescos,id',
        'tipo_transmision_id'  => 'nullable|exists:tipos_transmision,id',
        'tasa'                 => 'required|numeric|min:0|max:99.99',
        'vigente_desde'        => 'required|date',
        'vigente_hasta'        => 'nullable|date|after_or_equal:vigente_desde',
    ];
}
```

**Autorización:**

```php
public function authorize()
{
    return Gate::allows('create', \App\Models\Tasa::class);
}
```

**Reglas de validación:**
- `departamento_id`: requerido, debe existir en tabla departamentos
- `parentesco_id`: requerido, debe existir en tabla parentescos
- `tipo_transmision_id`: opcional, si se indica debe existir en tabla tipos_transmision
- `tasa`: requerido, numérico, mínimo 0, máximo 99.99
- `vigente_desde`: requerido, debe ser una fecha válida
- `vigente_hasta`: opcional, si se indica debe ser una fecha válida mayor o igual a vigente_desde

### UpdateTasaRequest

**Ubicación:** `app/Http/Requests/UpdateTasaRequest.php`

**Validación:**

```php
public function rules()
{
    return [
        'departamento_id'      => 'required|exists:departamentos,id',
        'parentesco_id'        => 'required|exists:parentescos,id',
        'tipo_transmision_id'  => 'nullable|exists:tipos_transmision,id',
        'tasa'                 => 'required|numeric|min:0|max:99.99',
        'vigente_desde'        => 'required|date',
        'vigente_hasta'        => 'nullable|date|after_or_equal:vigente_desde',
    ];
}
```

**Autorización:**

```php
public function authorize()
{
    return Gate::allows('update', $this->route('tasa'));
}
```

---

## 🎨 Vistas

### Estructura de Vistas

**Ubicación:** `resources/views/admin/tasas/`

### 1. `browse.blade.php` - Listado principal

**Funcionalidades:**
- Interfaz de búsqueda y filtrado
- Tabla dinámica con carga AJAX
- Select de paginación (10, 25, 50, 100)
- Botón para crear nueva tasa (controlado por permiso)
- Mensajes de éxito/error vía `@include('voyager::alerts')`
- Debounce de 500ms en búsqueda

**JavaScript:**

```javascript
window.countPage = 10;
window.list = function (page = 1) {
    $('#div-results').loading({message: 'Cargando...'});
    const url = '{{ route("admin.tasas.ajax.list") }}';
    const search = $('#input-search').val() || '';
    $.get(url, {search, paginate: window.countPage, page})
     .done(res => {
         $('#div-results').html(res);
     })
     .fail(xhr => console.error(xhr))
     .always(() => $('#div-results').loading('toggle'));
};

$(function () {
    window.list();
    $('#input-search').on('keyup', e => { if (e.which === 13) window.list(); })
                     .on('input',  () => { clearTimeout(window.t); window.t = setTimeout(window.list, 500); });
    $('#select-paginate').change(function () { window.countPage = $(this).val(); window.list(); });
});
```

### 2. `list.blade.php` - Contenido tabla AJAX

**Características:**
- Tabla con columnas: ID, Departamento, Parentesco, Tipo Transmisión, Tasa (%), Vigente Desde, Vigente Hasta, Acciones
- Badge para mostrar el valor de tasa
- Botones de acción controlados por `@can` directives
- Paginación Laravel
- Estado vacío con imagen cuando no hay registros

**Ejemplo de código:**

```blade
<table class="table table-hover table-voyager">
    <thead>
        <tr>
            <th>ID</th>
            <th>Departamento</th>
            <th>Parentesco</th>
            <th>Tipo Transmisión</th>
            <th class="text-center">Tasa (%)</th>
            <th class="text-center">Vigente Desde</th>
            <th class="text-center">Vigente Hasta</th>
            <th class="text-right">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $t)
            <tr>
                <td>{{ $t->id }}</td>
                <td>{{ $t->departamento->nombre }}</td>
                <td>{{ $t->parentesco->nombre }}</td>
                <td>{{ $t->tipoTransmision->nombre ?? '-' }}</td>
                <td class="text-center">
                    <span class="badge badge-primary">{{ number_format($t->tasa, 2) }}</span>
                </td>
                <td class="text-center">{{ $t->vigente_desde->format('d/m/Y') }}</td>
                <td class="text-center">
                    {{ $t->vigente_hasta?->format('d/m/Y') ?? '-' }}
                </td>
                <td class="text-right">
                    @can('view', $t)
                        <a href="{{ route('admin.tasas.show', $t) }}" class="btn btn-sm btn-warning">
                            <i class="voyager-eye"></i> Ver
                        </a>
                    @endcan
                    @can('update', $t)
                        <a href="{{ route('admin.tasas.edit', $t) }}" class="btn btn-sm btn-primary">
                            <i class="voyager-edit"></i> Editar
                        </a>
                    @endcan
                    @can('delete', $t)
                        <form action="{{ route('admin.tasas.destroy', $t) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('¿Borrar esta tasa?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                <i class="voyager-trash"></i> Borrar
                            </button>
                        </form>
                    @endcan
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="8">
                    <h5 class="text-center">No hay resultados</h5>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
```

### 3. `edit-add.blade.php` - Formulario crear/editar

**Características:**
- Formulario reutilizable para crear y editar
- Select2 para departamentos, parentescos y tipos de transmisión
- Campo numérico para tasa con validación (0.00 a 99.99)
- Campos de fecha para vigencia (desde y hasta)
- Validación en el request
- Título dinámico según modo (crear/editar)

**Ejemplo de código:**

```blade
<form action="{{ ($tasa->exists ?? false)
        ? route('admin.tasas.update', $tasa)
        : route('admin.tasas.store') }}"
      method="POST">
    @csrf
    @if($tasa->exists ?? false) @method('PUT') @endif

    <div class="row">
        {{-- Departamento --}}
        <div class="col-md-3">
            <label>Departamento <span class="required">*</span></label>
            <select name="departamento_id" class="form-control select2" required>
                <option value="">Elija...</option>
                @foreach($departamentos as $d)
                    <option value="{{ $d->id }}"
                        {{ old('departamento_id', optional($tasa)->departamento_id) == $d->id ? 'selected' : '' }}>
                        {{ $d->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Parentesco --}}
        <div class="col-md-3">
            <label>Parentesco <span class="required">*</span></label>
            <select name="parentesco_id" class="form-control select2" required>
                <option value="">Elija...</option>
                @foreach($parentescos as $p)
                    <option value="{{ $p->id }}"
                        {{ old('parentesco_id', optional($tasa)->parentesco_id) == $p->id ? 'selected' : '' }}>
                        {{ $p->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tipo Transmisión --}}
        <div class="col-md-3">
            <label>Tipo Transmisión</label>
            <select name="tipo_transmision_id" class="form-control select2">
                <option value="">Ninguno</option>
                @foreach($tipos as $t)
                    <option value="{{ $t->id }}"
                        {{ old('tipo_transmision_id', optional($tasa)->tipo_transmision_id) == $t->id ? 'selected' : '' }}>
                        {{ $t->nombre }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Tasa --}}
        <div class="col-md-3">
            <label>Tasa (%) <span class="required">*</span></label>
            <input type="number" step="0.01" min="0" max="99.99"
                   name="tasa" class="form-control"
                   value="{{ old('tasa', optional($tasa)->tasa) }}" required>
        </div>

        {{-- Vigente Desde --}}
        <div class="col-md-6">
            <label>Vigente Desde <span class="required">*</span></label>
            <input type="date" name="vigente_desde" class="form-control"
                   value="{{ old('vigente_desde', optional($tasa)->vigente_desde?->format('Y-m-d')) }}" required>
        </div>

        {{-- Vigente Hasta --}}
        <div class="col-md-6">
            <label>Vigente Hasta</label>
            <input type="date" name="vigente_hasta" class="form-control"
                   value="{{ old('vigente_hasta', optional($tasa)->vigente_hasta?->format('Y-m-d')) }}">
        </div>
    </div>
</form>
```

### 4. `read.blade.php` - Vista detalle

**Características:**
- Información detallada de la tasa
- Muestra todos los campos incluyendo relaciones
- Badge para el valor de tasa
- Muestra fechas de creación y actualización
- Botones para editar y volver (controlados por permiso)

---

## 🔗 Integración con otros Módulos

### 1. Servicio IdtgbCalculator

**Ubicación:** `app/Services/IdtgbCalculator.php`

**Método `tasaVigente`:**

```php
private function tasaVigente($depId, $parId, $fecha)
{
    return Tasa::where('departamento_id', $depId)
        ->where('parentesco_id', $parId)
        ->where('vigente_desde', '<=', $fecha)
        ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $fecha))
        ->first();
}
```

Este método se usa para obtener la tasa aplicable al calcular el ITGB para un trámite.

### 2. Módulo de Adquirentes en Trámites

**Controlador:** `AdquirenteTramiteController`

**Uso al agregar adquirente:**

```php
$tasa = Tasa::where('departamento_id', $tramite->inmueble->municipio->provincia->departamento_id)
    ->where('parentesco_id', $request->parentesco_id)
    ->where('tipo_transmision_id', $tramite->tipo_transmision_id)
    ->whereDate('vigente_desde', '<=', $tramite->fecha_presentacion)
    ->where(fn($q) => $q->whereNull('vigente_hasta')->orWhereDate('vigente_hasta', '>=', $tramite->fecha_presentacion))
    ->value('tasa') ?? 0;

AdquirenteTramite::create([
    'tramite_id' => $tramite->id,
    'person_id' => $request->person_id,
    'parentesco_id' => $request->parentesco_id,
    'tasa_aplicada' => $tasa,
    // ...
]);
```

### 3. TramiteWizard - Paso 3: Adquirentes

**Controlador:** `Admin\TramiteWizardController`

**Carga de parentescos con tasas:**

```php
$beni = \App\Models\Departamento::where('codigo', 'BE')->first();
$parentescos = Parentesco::with(['tasas' => function ($query) use ($beni) {
    if ($beni) {
        $query->where('departamento_id', $beni->id)
              ->where('vigente_desde', '<=', now())
              ->where(function ($q) {
                  $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', now());
              });
    }
}])->get()->map(function ($parentesco) {
    $tasa = $parentesco->tasas->first();
    $parentesco->tasa_aplicable = $tasa ? $tasa->tasa : 0.00;
    return $parentesco;
});
```

Esto permite mostrar en el wizard la tasa aplicable para cada parentesco del departamento del Beni.

### 4. Relaciones con otros modelos

**Departamento:**
- Tiene muchas tasas

```php
// app/Models/Departamento.php
public function tasas()
{
    return $this->hasMany(Tasa::class);
}
```

**Parentesco:**
- Tiene muchas tasas

```php
// app/Models/Parentesco.php
public function tasas()
{
    return $this->hasMany(Tasa::class);
}
```

**TipoTransmision:**
- Tiene muchas tasas

```php
// app/Models/TipoTransmision.php
public function tasas()
{
    return $this->hasMany(Tasa::class);
}
```

---

## 📚 Ejemplos de Uso

### Ejemplo 1: Crear una nueva tasa

```php
// Vía Controller
$tasa = Tasa::create([
    'departamento_id' => 1, // Beni
    'parentesco_id' => 1,    // Padre
    'tipo_transmision_id' => null,
    'tasa' => 1.50,
    'vigente_desde' => '2025-01-01',
    'vigente_hasta' => null
]);

// Vía Request
POST /admin/tasas
{
    "departamento_id": 1,
    "parentesco_id": 1,
    "tipo_transmision_id": null,
    "tasa": 1.50,
    "vigente_desde": "2025-01-01",
    "vigente_hasta": null
}

// Vía tinker
php artisan tinker
>>> use App\Models\Tasa;
>>> Tasa::create([
...     'departamento_id' => 1,
...     'parentesco_id' => 1,
...     'tasa' => 1.50,
...     'vigente_desde' => '2025-01-01'
... ]);
```

### Ejemplo 2: Buscar tasa vigente

```php
// Buscar tasa vigente hoy
$beni = \App\Models\Departamento::where('codigo', 'BE')->first()->id;
$padre = \App\Models\Parentesco::where('nombre', 'Padre')->first()->id;

$tasa = Tasa::vigente($beni, $padre);

if ($tasa) {
    echo "Tasa aplicable: " . $tasa->tasa . "%";
} else {
    echo "No hay tasa vigente para esta combinación";
}

// Buscar tasa vigente para una fecha específica
$tasa = Tasa::vigente($depId, $parId, '2025-12-31');
```

### Ejemplo 3: Obtener tasas por departamento con relaciones

```php
// Tasas del Beni con todas las relaciones cargadas
$beni = \App\Models\Departamento::where('codigo', 'BE')->first();
$tasasBeni = Tasa::with(['departamento', 'parentesco', 'tipoTransmision'])
    ->where('departamento_id', $beni->id)
    ->orderBy('vigente_desde', 'desc')
    ->get();

// Tasas vigentes hoy del Beni
$tasasVigentes = Tasa::where('departamento_id', $beni->id)
    ->where('vigente_desde', '<=', now())
    ->where(function ($q) {
        $q->whereNull('vigente_hasta')
          ->orWhere('vigente_hasta', '>=', now());
    })
    ->with(['parentesco', 'tipoTransmision'])
    ->get();
```

### Ejemplo 4: Listado con AJAX

```javascript
// JavaScript en browse.blade.php
function list(page = 1) {
    $('#div-results').loading({message: 'Cargando...'});
    const url = '/admin/tasas/ajax/list';
    const search = $('#input-search').val() || '';
    const paginate = $('#select-paginate').val();

    $.get(url, {search, paginate, page})
        .done(res => {
            $('#div-results').html(res);
        })
        .fail(xhr => {
            console.error('Error:', xhr.responseText);
            toastr.error('Error al obtener los datos.');
        })
        .always(() => {
            $('#div-results').loading('toggle');
        });
}
```

### Ejemplo 5: Calcular ITGB usando tasas

```php
use App\Services\IdtgbCalculator;

$calculator = app(IdtgbCalculator::class);

$liquidacion = $calculator->calculateEstimate(
    $baseImponible,      // Base imponible
    $departamentoId,     // ID del departamento
    $parentescoId,       // ID del parentesco del adquirente
    $tipoTransmisionId,  // ID del tipo de transmisión
    $fechaTransmision,   // Fecha de transmisión
    $fechaPresentacion,  // Fecha de presentación
    $fechaVencimiento    // Fecha de vencimiento
);

// El servicio IdtgbCalculator usa el método tasaVigente internamente
// para obtener la tasa aplicable según departamento y parentesco
```

### Ejemplo 6: Actualizar una tasa con nueva vigencia

```php
$tasa = Tasa::find(1);

// Terminar vigencia de tasa anterior
$tasa->update([
    'vigente_hasta' => '2025-12-31'
]);

// Crear nueva tasa con vigencia desde el día siguiente
Tasa::create([
    'departamento_id' => $tasa->departamento_id,
    'parentesco_id' => $tasa->parentesco_id,
    'tipo_transmision_id' => $tasa->tipo_transmision_id,
    'tasa' => 2.00, // Nueva tasa
    'vigente_desde' => '2026-01-01',
    'vigente_hasta' => null
]);
```

### Ejemplo 7: Consulta de tasas por parentesco

```php
// Obtener todas las tasas para el parentesco "Padre" en todos los departamentos
$padre = \App\Models\Parentesco::where('nombre', 'Padre')->first();

$tasasPadre = Tasa::with(['departamento'])
    ->where('parentesco_id', $padre->id)
    ->orderBy('departamento_id')
    ->orderBy('vigente_desde', 'desc')
    ->get();

foreach ($tasasPadre as $tasa) {
    echo "{$tasa->departamento->nombre}: {$tasa->tasa}% (vigente desde {$tasa->vigente_desde->format('d/m/Y')})\n";
}
```

---

## 🔍 Consideraciones Importantes

### Reglas de Negocio

1. **Unicidad:** No puede haber duplicados con la misma combinación de departamento, parentesco, tipo de transmisión y fecha de vigencia
2. **Departamento:** El departamento es obligatorio y define la jurisdicción de la tasa
3. **Parentesco:** El parentesco es obligatorio y afecta directamente el cálculo del impuesto
4. **Tipo de Transmisión:** Es opcional. Si se especifica, la tasa solo aplica a ese tipo de transmisión
5. **Tasa:** Debe estar entre 0.00 y 99.99
6. **Vigencia:**
   - `vigente_desde` es obligatorio
   - `vigente_hasta` es opcional (null = vigente indefinidamente)
   - `vigente_hasta` debe ser mayor o igual a `vigente_desde`
7. **Cálculo:** La tasa se aplica al valor de la base imponible para calcular el ITGB

### Validaciones Implementadas

**Al crear:**
- Departamento: requerido, debe existir
- Parentesco: requerido, debe existir
- Tipo de transmisión: opcional, si se indica debe existir
- Tasa: requerido, numérico, entre 0 y 99.99
- Vigente desde: requerido, fecha válida
- Vigente hasta: opcional, si se indica debe ser fecha válida y >= vigente desde

**Al actualizar:**
- Mismas validaciones que al crear

### Índice Único

El índice único compuesto `uq_tasas_dep_par_trans_vig` previene:
- Duplicar la misma combinación de departamento, parentesco, tipo de transmisión y fecha de vigencia
- Esto permite múltiples tasas para la misma combinación siempre que tengan diferentes fechas de vigencia

### Ícono en Menú

**Ubicación:** `database/seeders/IdtgbMenuAppendSeeder.php:30`

```php
['title' => 'Tasas',
 'route' => 'admin.tasas.index',
 'icon_class' => 'fa-solid fa-percent',
 'order' => 7]
```

### Mensajes de Éxito/Error

**Éxito:**
- "Tasa creada."
- "Tasa actualizada."
- "Tasa eliminada."

---

## 📝 Guía para Desarrolladores

### Extender el módulo

#### 1. Agregar histórico de cambios a tasas

**Paso 1:** Crear nueva migración

```bash
php artisan make:migration create_tasa_historicos_table
```

**Paso 2:** Editar migración

```php
public function up(): void
{
    Schema::create('tasa_historicos', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tasa_id')->constrained();
        $table->foreignId('user_id')->constrained();
        $table->decimal('tasa_anterior', 5, 2);
        $table->decimal('tasa_nueva', 5, 2);
        $table->text('motivo')->nullable();
        $table->timestamps();
    });
}
```

**Paso 3:** Crear modelo

```bash
php artisan make:model TasaHistorico
```

**Paso 4:** Actualizar Tasa model para registrar cambios

```php
// app/Models/Tasa.php
protected static function boot()
{
    parent::boot();

    static::updated(function ($tasa) {
        if ($tasa->isDirty('tasa')) {
            TasaHistorico::create([
                'tasa_id' => $tasa->id,
                'user_id' => auth()->id(),
                'tasa_anterior' => $tasa->getOriginal('tasa'),
                'tasa_nueva' => $tasa->tasa,
                'motivo' => request()->motivo ?? 'Actualización manual'
            ]);
        }
    });
}
```

#### 2. Implementar Soft Deletes

**Paso 1:** Crear migración

```bash
php artisan make:migration add_soft_deletes_to_tasas_table --table=tasas
```

**Paso 2:** Editar migración

```php
public function up(): void
{
    Schema::table('tasas', function (Blueprint $table) {
        $table->softDeletes();
    });
}
```

**Paso 3:** Agregar trait al modelo

```php
// app/Models/Tasa.php
use Illuminate\Database\Eloquent\SoftDeletes;

class Tasa extends Model
{
    use HasFactory, SoftDeletes;

    // ...
}
```

#### 3. Agregar notificación al cambiar tasas

```php
// app/Notifications/TasaModificada.php
class TasaModificada extends Notification
{
    use Queueable;

    private $tasa;

    public function __construct(Tasa $tasa)
    {
        $this->tasa = $tasa;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Tasa modificada en el sistema')
            ->line("La tasa para {$this->tasa->parentesco->nombre} en {$this->tasa->departamento->nombre} ha sido modificada.")
            ->line("Nueva tasa: {$this->tasa->tasa}%")
            ->action('Ver tasas', url('/admin/tasas'));
    }
}
```

### Buenas Prácticas

1. **Validación:** Usar Form Requests para validación centralizada
2. **Autorización:** Verificar permisos en Policies antes de ejecutar acciones
3. **Carga diferida:** Usar `with()` para relaciones en consultas de listado (evitar N+1)
4. **Vigencia:** Siempre verificar que las tasas usadas estén vigentes en la fecha del trámite
5. **Histórico:** Considerar mantener un histórico de cambios en tasas para auditoría
6. **Testing:** Crear tests para asegurar que el cálculo de tasas sea correcto
7. **Soft Deletes:** Usar soft deletes para mantener histórico de tasas eliminadas

**Ejemplo de eager loading:**

```php
// Mal - N+1 queries
$tasas = Tasa::all();
foreach ($tasas as $tasa) {
    echo $tasa->departamento->nombre; // Query por cada tasa
}

// Bien - 2 queries
$tasas = Tasa::with(['departamento', 'parentesco', 'tipoTransmision'])->get();
foreach ($tasas as $tasa) {
    echo $tasa->departamento->nombre; // Ya cargado
}
```

**Ejemplo de verificación de vigencia:**

```php
// Siempre usar el método vigente() para obtener tasas aplicables
$tasa = Tasa::vigente($depId, $parId, $fechaTramite);

if (!$tasa) {
    throw new Exception('No existe una tasa vigente para esta combinación en la fecha del trámite');
}
```

### Testing

Considerar crear tests para:

```php
<?php

namespace Tests\Feature;

use App\Models\Tasa;
use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TasaTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_index_requires_permission()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('admin.tasas.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function test_create_tasa()
    {
        $user = User::factory()->create();
        $user->givePermission('add_tasas');

        $departamento = Departamento::factory()->create();
        $parentesco = Parentesco::factory()->create();

        $data = [
            'departamento_id' => $departamento->id,
            'parentesco_id' => $parentesco->id,
            'tipo_transmision_id' => null,
            'tasa' => 1.50,
            'vigente_desde' => '2025-01-01',
            'vigente_hasta' => null
        ];

        $response = $this->actingAs($user)
            ->post(route('admin.tasas.store'), $data);

        $this->assertDatabaseHas('tasas', $data);
        $response->assertRedirect(route('admin.tasas.index'));
        $response->assertSessionHas('alert-type', 'success');
    }

    /** @test */
    public function test_tasa_must_be_between_0_and_99_99()
    {
        $user = User::factory()->create();
        $user->givePermission('add_tasas');

        $departamento = Departamento::factory()->create();
        $parentesco = Parentesco::factory()->create();

        $data = [
            'departamento_id' => $departamento->id,
            'parentesco_id' => $parentesco->id,
            'tasa' => 100.00, // Fuera de rango
            'vigente_desde' => '2025-01-01',
        ];

        $response = $this->actingAs($user)
            ->post(route('admin.tasas.store'), $data);

        $response->assertSessionHasErrors('tasa');
    }

    /** @test */
    public function test_update_tasa()
    {
        $user = User::factory()->create();
        $user->givePermission('edit_tasas');

        $tasa = Tasa::factory()->create();
        $data = ['tasa' => 2.00];

        $response = $this->actingAs($user)
            ->put(route('admin.tasas.update', $tasa), $data);

        $this->assertDatabaseHas('tasas', ['id' => $tasa->id, 'tasa' => 2.00]);
        $response->assertRedirect(route('admin.tasas.index'));
    }

    /** @test */
    public function test_delete_tasa()
    {
        $user = User::factory()->create();
        $user->givePermission('delete_tasas');

        $tasa = Tasa::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('admin.tasas.destroy', $tasa));

        $this->assertDatabaseMissing('tasas', ['id' => $tasa->id]);
        $response->assertRedirect(route('admin.tasas.index'));
    }

    /** @test */
    public function test_search_functionality()
    {
        $user = User::factory()->create();
        $user->givePermission('browse_tasas');

        $departamento = Departamento::factory()->create(['nombre' => 'Beni']);
        $parentesco = Parentesco::factory()->create(['nombre' => 'Padre']);

        Tasa::factory()->create([
            'departamento_id' => $departamento->id,
            'parentesco_id' => $parentesco->id,
            'tasa' => 1.50
        ]);

        $response = $this->actingAs($user)
            ->get(route('admin.tasas.ajax.list', ['search' => 'Beni']));

        $response->assertStatus(200);
        $response->assertSee('Beni');
    }

    /** @test */
    public function test_vigente_method_returns_current_rate()
    {
        $departamento = Departamento::factory()->create();
        $parentesco = Parentesco::factory()->create();

        // Tasa vigente
        Tasa::factory()->create([
            'departamento_id' => $departamento->id,
            'parentesco_id' => $parentesco->id,
            'tasa' => 1.50,
            'vigente_desde' => '2025-01-01',
            'vigente_hasta' => null
        ]);

        $tasa = Tasa::vigente($departamento->id, $parentesco->id);

        $this->assertNotNull($tasa);
        $this->assertEquals(1.50, $tasa->tasa);
    }

    /** @test */
    public function test_vigente_method_returns_null_when_no_rate()
    {
        $departamento = Departamento::factory()->create();
        $parentesco = Parentesco::factory()->create();

        // Tasa expirada
        Tasa::factory()->create([
            'departamento_id' => $departamento->id,
            'parentesco_id' => $parentesco->id,
            'tasa' => 1.50,
            'vigente_desde' => '2020-01-01',
            'vigente_hasta' => '2021-01-01'
        ]);

        $tasa = Tasa::vigente($departamento->id, $parentesco->id);

        $this->assertNull($tasa);
    }
}
```

### Comandos Útiles

```bash
# Crear nueva migración
php artisan make:migration add_field_to_tasas_table --table=tasas

# Ejecutar migraciones
php artisan migrate

# Rollback última migración
php artisan migrate:rollback

# Crear controller
php artisan make:controller TasaController --resource

# Crear policy
php artisan make:policy TasaPolicy --model=Tasa

# Crear requests
php artisan make:request StoreTasaRequest
php artisan make:request UpdateTasaRequest

# Crear factory
php artisan make:factory TasaFactory

# Crear seeder
php artisan make:seeder TasaSeeder

# Ejecutar seeder
php artisan db:seed --class=TasaSeeder

# Ejecutar tests
php artisan test --filter TasaTest

# Limpiar cache
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# Ver modelo
php artisan model:show Tasa
```

---

## 📞 Soporte y Mantenimiento

Para consultas o reportar issues relacionados con el módulo de Tasas, contactar al equipo de desarrollo o revisar la documentación del sistema ITGB.

**Documentación relacionada:**
- Documentación del módulo de Trámites
- Documentación del servicio IdtgbCalculator
- Documentación del módulo de Departamentos
- Documentación del módulo de Parentescos
- Documentación del módulo de Tipos de Transmisión

**Última actualización:** Enero 2026

**Versión:** 1.0.0
