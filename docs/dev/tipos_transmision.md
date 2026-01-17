# Documentación Técnica - Módulo de Tipos de Transmisión

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelo](#modelo)
4. [Controlador](#controlador)
5. [Rutas](#rutas)
6. [Permisos](#permisos)
7. [Vistas](#vistas)
8. [Integración con otros módulos](#integración-con-otros-módulos)
9. [Ejemplos de Uso](#ejemplos-de-uso)
10. [Consideraciones Importantes](#consideraciones-importantes)
11. [Guía para Desarrolladores](#guía-para-desarrolladores)
12. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **Tipos de Transmisión** gestiona los diferentes tipos de transferencias de bienes que pueden ocurrir en el contexto del Impuesto de Transmisiones Gratuítas de Bienes (ITGB). Este módulo es fundamental para clasificar y calcular correctamente las tasas impositivas según la naturaleza de la transmisión.

### Propósito
- Mantener un catálogo de tipos de transmisiones (Herencia, Donación, Legado, etc.)
- Permitir la selección del tipo de transmisión al crear trámites
- Establecer tasas impositivas específicas según el tipo de transmisión
- Gestionar el ciclo de vida CRUD completo de tipos de transmisión

### Importancia en el Sistema ITGB
El tipo de transmisión seleccionado determina la base imponible y la tasa aplicable en el cálculo del ITGB. Diferentes tipos de transmisión pueden tener condiciones especiales, exenciones o recargos según la legislación vigente.

---

## 🗄️ Base de Datos

### Migración: `create_tipos_transmision_table.php`

**Ubicación:** `database/migrations/2025_09_22_122727_create_tipos_transmision_table.php`

**Estructura de la tabla:**

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre del tipo de transmisión (ej: "Herencia", "Donación") |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |

**Relaciones:**
- Tiene muchos `Tasa` (un tipo puede tener múltiples tasas asociadas)
- Tiene muchos `Tramite` (un tipo puede ser usado en múltiples trámites)

**Ejemplo de SQL:**

```sql
CREATE TABLE tipos_transmision (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);
```

**Índices:**
- Primary key en `id`
- Unique key en `nombre`

---

## 🧩 Modelo

### Modelo: `TipoTransmision`

**Ubicación:** `app/Models/TipoTransmision.php`

**Atributos:**
- `$table = 'tipos_transmision'`
- `$fillable = ['nombre']`

**Relaciones:**

```php
// Un tipo de transmisión tiene muchas tasas
public function tasas()
{
    return $this->hasMany(Tasa::class);
}

// Un tipo de transmisión tiene muchos trámites
public function tramites()
{
    return $this->hasMany(Tramite::class);
}
```

**Uso del modelo:**

```php
// Obtener todos los tipos de transmisión
$tipos = TipoTransmision::all();

// Obtener un tipo específico
$tipo = TipoTransmision::find(1);

// Obtener tipos con sus tasas cargadas
$tipos = TipoTransmision::with('tasas')->get();

// Obtener tipos con sus trámites
$tipos = TipoTransmision::with('tramites')->get();

// Crear un nuevo tipo
TipoTransmision::create(['nombre' => 'Herencia']);

// Actualizar un tipo
$tipo->update(['nombre' => 'Herencia Testimonial']);

// Buscar por nombre
$tipo = TipoTransmision::where('nombre', 'Donación')->first();

// Verificar si tiene tasas asociadas
if ($tipo->tasas()->count() > 0) {
    // No se puede eliminar
}

// Verificar si tiene trámites asociados
if ($tipo->tramites()->count() > 0) {
    // No se puede eliminar
}
```

**Ejemplos de consultas avanzadas:**

```php
// Obtener tipos de transmisión que tienen tasas activas
$tiposConTasas = TipoTransmision::whereHas('tasas', function ($query) {
    $query->where('vigente_desde', '<=', now())
          ->where(function ($q) {
              $q->whereNull('vigente_hasta')
                ->orWhere('vigente_hasta', '>=', now());
          });
})->get();

// Contar trámites por tipo de transmisión
$conteoTramites = TipoTransmision::withCount('tramites')
    ->orderBy('tramites_count', 'desc')
    ->get();
```

---

## 🎮 Controlador

### Controlador: `TipoTransmisionController`

**Ubicación:** `app/Http/Controllers/TipoTransmisionController.php`

**Middleware:**
- `auth` - Requiere autenticación

### Métodos del Controlador

#### 1. `index()` - Vista principal
```
GET /admin/tipos-transmision
```
- Muestra la vista `browse.blade.php` con tabla interactiva
- No requiere permiso específico (middleware auth solo)

#### 2. `list()` - Listado AJAX
```
GET /admin/tipos-transmision/ajax/list
```
- Retorna lista paginada de tipos de transmisión
- Parámetros:
  - `search` (string): Filtro por nombre
  - `paginate` (int): Cantidad de registros por página (default: 10)
- Ordenamiento: ID descendente

**Código del método:**

```php
public function list()
{
    $search = request('search');
    $paginate = request('paginate', 10);

    $data = TipoTransmision::when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
        ->orderByDesc('id')
        ->paginate($paginate);

    return view('admin.tipos-transmision.list', compact('data'));
}
```

#### 3. `create()` - Formulario de creación
```
GET /admin/tipos-transmision/create
```
- Muestra formulario `edit-add.blade.php`
- No requiere permiso específico

#### 4. `store(Request $request)` - Guardar nuevo
```
POST /admin/tipos-transmision
```
- Valida y crea nuevo tipo de transmisión
- Redirige al listado con mensaje de éxito
- Validación inline en el controlador

**Código del método:**

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre',
    ]);

    TipoTransmision::create($validated);

    return redirect()->route('admin.tipos-transmision.index')
        ->with(['message' => 'Tipo de Transmisión creado exitosamente.', 'alert-type' => 'success']);
}
```

#### 5. `show(TipoTransmision $tipoTransmision)` - Ver detalle
```
GET /admin/tipos-transmision/{tipoTransmision}
```
- Muestra vista detallada `read.blade.php`
- No requiere permiso específico

#### 6. `edit(TipoTransmision $tipoTransmision)` - Formulario de edición
```
GET /admin/tipos-transmision/{tipoTransmision}/edit
```
- Muestra formulario con datos existentes
- No requiere permiso específico

#### 7. `update(Request $request, TipoTransmision $tipoTransmision)` - Actualizar
```
PUT /admin/tipos-transmision/{tipoTransmision}
```
- Valida y actualiza tipo de transmisión
- Redirige al listado con mensaje de éxito
- Validación inline en el controlador

**Código del método:**

```php
public function update(Request $request, TipoTransmision $tipoTransmision)
{
    $validated = $request->validate([
        'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre,' . $tipoTransmision->id,
    ]);

    $tipoTransmision->update($validated);

    return redirect()->route('admin.tipos-transmision.index')
        ->with(['message' => 'Tipo de Transmisión actualizado exitosamente.', 'alert-type' => 'success']);
}
```

#### 8. `destroy(TipoTransmision $tipoTransmision)` - Eliminar
```
DELETE /admin/tipos-transmision/{tipoTransmision}
```
- Verifica que no tenga tasas ni trámites asociados antes de eliminar
- Retorna error si tiene dependencias

**Código del método:**

```php
public function destroy(TipoTransmision $tipoTransmision)
{
    // Verificar si está en uso antes de borrar
    if ($tipoTransmision->tasas()->exists() || $tipoTransmision->tramites()->exists()) {
        return back()->with([
            'message' => 'No se puede eliminar. El tipo de transmisión está siendo utilizado en tasas o trámites.',
            'alert-type' => 'error'
        ]);
    }

    $tipoTransmision->delete();

    return redirect()->route('admin.tipos-transmision.index')
        ->with(['message' => 'Tipo de Transmisión eliminado.', 'alert-type' => 'success']);
}
```

**Validación de dependencias:**
- Verifica si tiene tasas asociadas
- Verifica si tiene trámites asociados
- Bloquea eliminación si hay dependencias activas

---

## 🛣️ Rutas

### Rutas Definidas

**Ubicación:** `routes/web.php:103-104`

```php
Route::resource('tipos-transmision', TipoTransmisionController::class)
    ->names('admin.tipos-transmision')
    ->parameters(['tipos-transmision' => 'tipoTransmision']);

Route::get('tipos-transmision/ajax/list', [TipoTransmisionController::class, 'list'])
    ->name('admin.tipos-transmision.ajax.list');
```

### Lista de Rutas

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| GET | `/admin/tipos-transmision` | `admin.tipos-transmision.index` | Listado principal |
| GET | `/admin/tipos-transmision/create` | `admin.tipos-transmision.create` | Formulario crear |
| POST | `/admin/tipos-transmision` | `admin.tipos-transmision.store` | Guardar nuevo |
| GET | `/admin/tipos-transmision/{tipoTransmision}` | `admin.tipos-transmision.show` | Ver detalle |
| GET | `/admin/tipos-transmision/{tipoTransmision}/edit` | `admin.tipos-transmision.edit` | Formulario editar |
| PUT/PATCH | `/admin/tipos-transmision/{tipoTransmision}` | `admin.tipos-transmision.update` | Actualizar |
| DELETE | `/admin/tipos-transmision/{tipoTransmision}` | `admin.tipos-transmision.destroy` | Eliminar |
| GET | `/admin/tipos-transmision/ajax/list` | `admin.tipos-transmision.ajax.list` | Listado AJAX |

**Middleware aplicado:**
- `loggin` - Autenticación de usuario
- `system` - Verificación de sistema

**Parámetro de ruta personalizado:**
- En lugar de usar `{tipos_transmision}` (que sería el default por Laravel), se usa `{tipoTransmision}` para mejorar la legibilidad

**Grupo de rutas:**
```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // Rutas de tipos de transmisión aquí
});
```

---

## 🔒 Permisos

### Permisos Generados

**Ubicación:** `database/seeders/PermissionsTableSeeder.php:114`

Los permisos se generan automáticamente usando el método `Permission::generateFor('tipos-transmision')` de Voyager.

**Permisos creados:**

| Permiso | Descripción |
|---------|-------------|
| `browse_tipos-transmision` | Ver lista de tipos de transmisión |
| `read_tipos-transmision` | Ver detalles de un tipo de transmisión |
| `edit_tipos-transmision` | Editar tipos de transmisión |
| `add_tipos-transmision` | Agregar nuevos tipos de transmisión |
| `delete_tipos-transmision` | Eliminar tipos de transmisión |

**NOTA IMPORTANTE:** Aunque los permisos existen en la base de datos, el controlador `TipoTransmisionController` **NO utiliza Policies** ni verifica permisos en sus métodos. Toda la autorización se maneja a través del middleware `auth` y el control de acceso implícito.

### Permisos por Rol

Los permisos son asignados a roles a través de la interfaz de Voyager o mediante seeders. La configuración típica sería:

| Rol | browse | read | add | edit | delete |
|-----|--------|------|-----|------|--------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| Operador | ✓ | ✓ | ✓ | ✓ | ✗ |
| Visitante | ✓ | ✓ | ✗ | ✗ | ✗ |

### Verificación de Permisos

**En vistas Blade:**

```blade
@can('add_tipos-transmision')
    <a href="{{ route('admin.tipos-transmision.create') }}" class="btn btn-success">
        <i class="voyager-plus"></i> Añadir nuevo
    </a>
@endcan
```

**Nota:** Las vistas actuales no verifican permisos específicos, por lo que todos los usuarios autenticados pueden ver y usar todas las funcionalidades.

---

## 🎨 Vistas

### Estructura de Vistas

**Ubicación:** `resources/views/admin/tipos-transmision/`

### 1. `browse.blade.php` - Listado principal

**Funcionalidades:**
- Interfaz de búsqueda y filtrado
- Tabla dinámica con carga AJAX
- Select de paginación (10, 25, 50, 100)
- Botón para crear nuevo tipo de transmisión
- Mensajes de éxito/error vía `@include('voyager::alerts')`

**Código JavaScript:**

```javascript
$(document).ready(function() {
    let page = 1;
    let search = '';
    let paginate = 10;

    function fetch_data(page, search, paginate) {
        $('#div-results').loading({message: 'Cargando...'});
        $.ajax({
            url: "{{ route('admin.tipos-transmision.ajax.list') }}",
            data: { page, search, paginate },
            success: function(data) {
                $('#div-results').html(data);
                $('#div-results').loading('toggle');
            },
            error: function() {
                $('#div-results').loading('toggle');
                toastr.error('Error al obtener los datos.');
            }
        });
    }

    fetch_data(page, search, paginate);

    $('#input-search').on('keyup', function() {
        search = $(this).val();
        fetch_data(1, search, paginate);
    });

    $('#select-paginate').on('change', function() {
        paginate = $(this).val();
        fetch_data(1, search, paginate);
    });

    $(document).on('click', '.pagination a', function(event) {
        event.preventDefault();
        page = $(this).attr('href').split('page=')[1];
        fetch_data(page, search, paginate);
    });
});
```

### 2. `list.blade.php` - Contenido tabla AJAX

**Características:**
- Tabla con columnas: ID, Nombre, Creado en, Acciones
- Botones de acción sin verificación de permisos (todos visibles)
- Paginación Laravel
- Estado vacío cuando no hay registros
- Confirmación JavaScript al eliminar

**Ejemplo de código:**

```blade
<table class="table table-hover">
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Creado en</th>
            <th class="actions text-right">Acciones</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($data as $item)
            <tr>
                <td>{{ $item->id }}</td>
                <td>{{ $item->nombre }}</td>
                <td>{{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y H:i') }}</td>
                <td>
                    <a href="{{ route('admin.tipos-transmision.show', $item->id) }}" title="Ver" class="btn btn-sm btn-warning view">
                        <i class="voyager-eye"></i> <span class="hidden-xs hidden-sm">Ver</span>
                    </a>
                    <a href="{{ route('admin.tipos-transmision.edit', $item->id) }}" title="Editar" class="btn btn-sm btn-primary edit">
                        <i class="voyager-edit"></i> <span class="hidden-xs hidden-sm">Editar</span>
                    </a>
                    <form action="{{ route('admin.tipos-transmision.destroy', $item->id) }}" method="POST" style="display: inline;" onsubmit="return confirm('¿Estás seguro de que quieres eliminar este registro?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" title="Borrar" class="btn btn-sm btn-danger delete">
                            <i class="voyager-trash"></i> <span class="hidden-xs hidden-sm">Borrar</span>
                        </button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="text-center">No se encontraron registros.</td>
            </tr>
        @endforelse
    </tbody>
</table>
```

### 3. `edit-add.blade.php` - Formulario crear/editar

**Características:**
- Formulario reutilizable para crear y editar
- Campo de texto `nombre` (máx 50 caracteres)
- Validación en el controlador
- Muestra errores de validación
- Título dinámico según modo (crear/editar)

**Ejemplo de código:**

```blade
@extends('voyager::master')

@section('page_title', isset($tipoTransmision) ? 'Editar Tipo de Transmisión' : 'Añadir Tipo de Transmisión')

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form"
                          action="{{ isset($tipoTransmision) ? route('admin.tipos-transmision.update', $tipoTransmision->id) : route('admin.tipos-transmision.store') }}"
                          method="POST">
                        @if(isset($tipoTransmision))
                            @method('PUT')
                        @endif
                        @csrf

                        <div class="panel-body">
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul>
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="form-group">
                                <label for="nombre">Nombre</label>
                                <input type="text" class="form-control" name="nombre" id="nombre"
                                       placeholder="Ej: Herencia, Donación, Legado"
                                       value="{{ old('nombre', $tipoTransmision->nombre ?? '') }}">
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Guardar</button>
                            <a href="{{ route('admin.tipos-transmision.index') }}" class="btn btn-default">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop
```

### 4. `read.blade.php` - Vista detalle

**Características:**
- Muestra información detallada del tipo de transmisión
- Botón para volver al listado
- Título dinámico

---

## 🔗 Integración con otros Módulos

### 1. Módulo de Trámites

**Modelo:** `Tramite`
**Ubicación:** `app/Models/Tramite.php`

**Relación:**

```php
public function tipoTransmision()
{
    return $this->belongsTo(TipoTransmision::class, 'tipo_transmision_id');
}
```

**Campo en tabla `tramites`:**
- `tipo_transmision_id` - FK a `tipos_transmision(id)` con restricción de clave foránea

**Request de validación:**

```php
// app/Http/Requests/StoreTramiteRequest.php
'tipo_transmision_id' => 'required|exists:tipos_transmision,id',

// app/Http/Requests/UpdateTramiteRequest.php
'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
```

**Uso en vistas de trámites:**

```blade
<!-- resources/views/admin/tramites/edit-add.blade.php -->
<select name="tipo_transmision_id" class="form-control select2" required>
    @foreach($tipos_transmision as $t)
        <option value="{{ $t->id }}" {{ old('tipo_transmision_id', optional($tramite)->tipo_transmision_id) == $t->id ? 'selected' : '' }}>
            {{ $t->nombre }}
        </option>
    @endforeach
</select>
```

### 2. Módulo de TramiteWizard

**Controlador:** `Admin\TramiteWizardController`
**Ubicación:** `app/Http/Controllers/Admin/TramiteWizardController.php`

**Uso en Paso 1 - Datos Generales:**

```php
public function createStep1(Request $request)
{
    $this->initializeWizard($request);
    $tiposTransmision = TipoTransmision::all();

    return view('admin.tramites.wizard.create_step_1', [
        'tiposTransmision' => $tiposTransmision,
        // ...
    ]);
}
```

**Validación al guardar Paso 1:**

```php
$request->validate([
    'nro_tramite' => 'required|string|max:15|unique:tramites,nro_tramite',
    'fecha_presentacion' => 'required|date',
    'fecha_transmision' => 'required|date|before_or_equal:fecha_presentacion',
    'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
    'valor_declarado' => 'required|numeric|min:0',
    'base_imponible' => 'required|numeric|min:0',
    'observaciones' => 'nullable|string|max:500',
]);
```

**Uso en vista del Paso 1:**

```blade
<!-- resources/views/admin/tramites/wizard/create_step_1.blade.php -->
<div class="form-group">
    <label for="tipo_transmision_id">Tipo de Transmisión <span class="required">*</span></label>
    <select name="tipo_transmision_id" class="form-control select2" required>
        @foreach($tiposTransmision as $tipo)
            <option value="{{ $tipo->id }}" {{ (old('tipo_transmision_id', $wizardData['step1']['tipo_transmision_id'] ?? '') == $tipo->id) ? 'selected' : '' }}>
                {{ $tipo->nombre }}
            </option>
        @endforeach
    </select>
</div>
```

### 3. Módulo de Tasas

**Modelo:** `Tasa`
**Ubicación:** `app/Models/Tasa.php`

**Relación:**

```php
public function tipoTransmision()
{
    return $this->belongsTo(TipoTransmision::class, 'tipo_transmision_id');
}
```

**Campo en tabla `tasas`:**
- `tipo_transmision_id` - FK nullable a `tipos_transmision(id)`
- Parte del índice único compuesto: `['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'vigente_desde']`

**Request de validación:**

```php
// app/Http/Requests/StoreTasaRequest.php
'tipo_transmision_id' => 'nullable|exists:tipos_transmision,id',

// app/Http/Requests/UpdateTasaRequest.php
'tipo_transmision_id' => 'nullable|exists:tipos_transmision,id',
```

**Uso en vistas de tasas:**

```blade
<!-- resources/views/admin/tasas/edit-add.blade.php -->
<select name="tipo_transmision_id" class="form-control select2">
    @foreach($tipos_transmision as $t)
        <option value="{{ $t->id }}" {{ old('tipo_transmision_id', optional($tasa)->tipo_transmision_id) == $t->id ? 'selected' : '' }}>
            {{ $t->nombre }}
        </option>
    @endforeach
</select>
```

### 4. Calculadora Beni

**Controlador:** `CalculadoraBeniController`
**Ubicación:** `app/Http/Controllers/CalculadoraBeniController.php`

**Uso:**

```php
public function formulario()
{
    $parentescos = Parentesco::all();
    $tipos_transmision = TipoTransmision::all();

    return view('calculadora_beni_interactivo', [
        'parentescos' => $parentescos,
        'tipos_transmision' => $tipos_transmision,
        'nro_tramite' => null
    ]);
}
```

**Validación y conversión:**

```php
$request->validate([
    'tipo_transmision' => 'required|string',
    // ...
]);

$tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;
```

**Uso en vista de calculadora:**

```blade
<!-- resources/views/calculadora_beni_interactivo.blade.php -->
<div class="form-group">
    <label for="tipo_transmision">Tipo de Transmisión *</label>
    <select name="tipo_transmision" class="form-select" required>
        @foreach($tipos_transmision as $tipo)
            <option value="{{ $tipo->nombre }}">{{ $tipo->nombre }}</option>
        @endforeach
    </select>
</div>
```

### 5. Servicio IdtgbCalculator

**Servicio:** `IdtgbCalculator`
**Ubicación:** `app/Services/IdtgbCalculator.php`

**Uso en cálculo:**

```php
$tramite->tipo_transmision_id; // Se utiliza para cálculo

// Se usa parentesco_id y tipo_transmision_id para calcular la tasa aplicable
$tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $tipoId, $fPres);
```

### 6. Módulo de Reportes

**Controlador:** `ReporteController`
**Ubicación:** `app/Http/Controllers/ReporteController.php`

**Agrupación por tipo de transmisión:**

```php
->select('tipo_transmision_id', DB::raw('count(*) as cantidad'), DB::raw('sum(monto_final) as total'))
->groupBy('tipo_transmision_id')
```

### 7. Dashboard Service

**Servicio:** `DashboardService`
**Ubicación:** `app/Services/DashboardService.php`

**Estadísticas por tipo de transmisión:**

```php
->join('tipos_transmision', 'tramites.tipo_transmision_id', '=', 'tipos_transmision.id')
->select('tipos_transmision.nombre as tipo', DB::raw('count(tramites.id) as total'))
->groupBy('tipos_transmision.nombre')
```

### 8. Observador de Tramites

**Observador:** `TramiteObserver`
**Ubicación:** `app/Observers/TramiteObserver.php`

**Uso al crear/update:**

```php
'tipo_transmision_id',
```

### 9. PDF de Formulario A01

**Vista:** `resources/views/pdf/form_a01_beni_interactivo.blade.php`

```blade
<p><strong>Tipo de Transmisión:</strong> {{ $tipo_transmision }}</p>
```

**Vista de cálculo estimado:** `resources/views/pdf/calculo_estimado_beni.blade.php`

```blade
<p><strong>Tipo de Transmisión:</strong> {{ $tipo_transmision_nombre }}</p>

@if($tipo_transmision == 'Entre vivos')
    <!-- Lógica específica para Entre vivos -->
@endif
```

---

## 📚 Ejemplos de Uso

### Ejemplo 1: Crear un nuevo tipo de transmisión

```php
// Vía Controller
$tipo = TipoTransmision::create([
    'nombre' => 'Herencia'
]);

// Vía Request
POST /admin/tipos-transmision
{
    "nombre": "Herencia"
}

// Vía tinker
php artisan tinker
>>> use App\Models\TipoTransmision;
>>> TipoTransmision::create(['nombre' => 'Legado']);
```

### Ejemplo 2: Obtener tipos de transmisión con tasas

```php
// Con todas las tasas
$tipos = TipoTransmision::with('tasas')->get();

// Solo tipos que tienen tasas activas
$tiposConTasasActivas = TipoTransmision::whereHas('tasas', function ($query) {
    $query->where('vigente_desde', '<=', now())
          ->where(function ($q) {
              $q->whereNull('vigente_hasta')
                ->orWhere('vigente_hasta', '>=', now());
          });
})->get();
```

### Ejemplo 3: Verificar dependencias antes de eliminar

```php
$tipo = TipoTransmision::find(1);

if ($tipo->tasas()->exists() || $tipo->tramites()->exists()) {
    // No se puede eliminar
    throw new Exception('El tipo de transmisión está en uso');
}

$tipo->delete();
```

### Ejemplo 4: Listado con AJAX

```javascript
// JavaScript en browse.blade.php
function fetch_data(page, search, paginate) {
    $('#div-results').loading({message: 'Cargando...'});
    $.ajax({
        url: "/admin/tipos-transmision/ajax/list",
        data: { page, search, paginate },
        success: function(data) {
            $('#div-results').html(data);
            $('#div-results').loading('toggle');
        },
        error: function() {
            $('#div-results').loading('toggle');
            toastr.error('Error al obtener los datos.');
        }
    });
}
```

### Ejemplo 5: Buscar por nombre y obtener ID

```php
$tipo = TipoTransmision::where('nombre', 'Donación')->first();

if ($tipo) {
    $tipoId = $tipo->id;
    // Usar el ID en cálculos
} else {
    // Manejar error
}
```

### Ejemplo 6: Contar trámites por tipo de transmisión

```php
$estadisticas = TipoTransmision::withCount('tramites')
    ->orderBy('tramites_count', 'desc')
    ->get();

foreach ($estadisticas as $tipo) {
    echo "{$tipo->nombre}: {$tipo->tramites_count} trámites\n";
}
```

### Ejemplo 7: Crear un trámite con tipo de transmisión

```php
$tramite = Tramite::create([
    'nro_tramite' => 'TRM-2026-0001',
    'fecha_presentacion' => now(),
    'fecha_transmision' => now()->subDays(10),
    'tipo_transmision_id' => 1, // ID de "Herencia"
    'valor_declarado' => 100000,
    'base_imponible' => 100000,
    'total_idtgb' => 0,
    'recargo_mora' => 0,
    'monto_final' => 0,
    'ufv_aplicada' => 1.00000,
    'estado' => 'Borrador',
    'fecha_vencimiento' => now()->addDays(30),
    'observaciones' => 'Trámite de prueba',
    'user_id' => auth()->id(),
    'created_by' => auth()->id(),
    'updated_by' => auth()->id(),
]);
```

---

## 🔍 Consideraciones Importantes

### Reglas de Negocio

1. **Unicidad:** El nombre del tipo de transmisión debe ser único en todo el sistema
2. **Dependencias:** No se puede eliminar un tipo de transmisión si tiene tasas o trámites asociados
3. **Longitud máxima:** El nombre no puede exceder 50 caracteres
4. **Opcional en Tasas:** En el modelo `Tasa`, `tipo_transmision_id` es nullable (puede no especificarse)
5. **Requerido en Trámites:** En el modelo `Tramite`, `tipo_transmision_id` es obligatorio

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
- Verificar que no tenga trámites asociados
- Bloquear eliminación si hay dependencias

### Diferencias con otros módulos

**Comparación con Parentesco:**

| Aspecto | Parentesco | TipoTransmision |
|---------|------------|-----------------|
| Usa Policies | ✓ | ✗ |
| Usa Form Requests | ✓ | ✗ |
| Validación en Controller | ✗ | ✓ |
| Verificación de permisos en vistas | ✓ | ✗ |

### Ícono en Menú

**Ubicación:** `database/seeders/IdtgbMenuAppendSeeder.php:30`

```php
['title' => 'Tipos de Transmisión',
 'route' => 'admin.tipos-transmision.index',
 'icon_class' => 'fa-solid fa-arrow-right-arrow-left',
 'order' => 5]
```

### Mensajes de Éxito/Error

**Éxito:**
- "Tipo de Transmisión creado exitosamente."
- "Tipo de Transmisión actualizado exitosamente."
- "Tipo de Transmisión eliminado."

**Error:**
- "No se puede eliminar. El tipo de transmisión está siendo utilizado en tasas o trámites."

---

## 📝 Guía para Desarrolladores

### Extender el módulo

#### 1. Agregar campos al modelo

**Paso 1:** Crear nueva migración

```bash
php artisan make:migration add_descripcion_to_tipos_transmision_table --table=tipos_transmision
```

**Paso 2:** Editar migración

```php
public function up(): void
{
    Schema::table('tipos_transmision', function (Blueprint $table) {
        $table->text('descripcion')->nullable();
        $table->boolean('activo')->default(true);
    });
}
```

**Paso 3:** Agregar a fillables

```php
// app/Models/TipoTransmision.php
protected $fillable = [
    'nombre',
    'descripcion',
    'activo',
];
```

**Paso 4:** Actualizar validaciones en el controlador

```php
// app/Http/Controllers/TipoTransmisionController.php

// En store()
$validated = $request->validate([
    'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre',
    'descripcion' => 'nullable|string|max:500',
    'activo' => 'boolean',
]);

// En update()
$validated = $request->validate([
    'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre,' . $tipoTransmision->id,
    'descripcion' => 'nullable|string|max:500',
    'activo' => 'boolean',
]);
```

**Paso 5:** Actualizar vistas

```blade
<!-- resources/views/admin/tipos-transmision/edit-add.blade.php -->
<div class="form-group">
    <label for="nombre">Nombre</label>
    <input type="text" class="form-control" name="nombre" id="nombre"
           placeholder="Ej: Herencia, Donación, Legado"
           value="{{ old('nombre', $tipoTransmision->nombre ?? '') }}">
</div>

<div class="form-group">
    <label for="descripcion">Descripción</label>
    <textarea class="form-control" name="descripcion" id="descripcion" rows="3">{{ old('descripcion', $tipoTransmision->descripcion ?? '') }}</textarea>
</div>

<div class="form-group">
    <div class="checkbox">
        <label>
            <input type="checkbox" name="activo" {{ old('activo', $tipoTransmision->activo ?? 1) ? 'checked' : '' }}>
            Activo
        </label>
    </div>
</div>
```

#### 2. Implementar Policies (Recomendado)

**Paso 1:** Crear la Policy

```bash
php artisan make:policy TipoTransmisionPolicy --model=TipoTransmision
```

**Paso 2:** Implementar métodos

```php
// app/Policies/TipoTransmisionPolicy.php
class TipoTransmisionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->hasPermission('browse_tipos-transmision');
    }

    public function view(User $user, TipoTransmision $tipoTransmision)
    {
        return $user->hasPermission('read_tipos-transmision');
    }

    public function create(User $user)
    {
        return $user->hasPermission('add_tipos-transmision');
    }

    public function update(User $user, TipoTransmision $tipoTransmision)
    {
        return $user->hasPermission('edit_tipos-transmision');
    }

    public function delete(User $user, TipoTransmision $tipoTransmision)
    {
        return $user->hasPermission('delete_tipos-transmision');
    }
}
```

**Paso 3:** Registrar la Policy

```php
// app/Providers/AuthServiceProvider.php
protected $policies = [
    TipoTransmision::class => TipoTransmisionPolicy::class,
];
```

**Paso 4:** Actualizar el controlador

```php
// app/Http/Controllers/TipoTransmisionController.php

public function index()
{
    $this->authorize('viewAny', TipoTransmision::class);
    return view('admin.tipos-transmision.browse');
}

public function show(TipoTransmision $tipoTransmision)
{
    $this->authorize('view', $tipoTransmision);
    return view('admin.tipos-transmision.read', compact('tipoTransmision'));
}

public function create()
{
    $this->authorize('create', TipoTransmision::class);
    return view('admin.tipos-transmision.edit-add');
}

public function edit(TipoTransmision $tipoTransmision)
{
    $this->authorize('update', $tipoTransmision);
    return view('admin.tipos-transmision.edit-add', compact('tipoTransmision'));
}

public function destroy(TipoTransmision $tipoTransmision)
{
    $this->authorize('delete', $tipoTransmision);
    // Resto del código...
}
```

#### 3. Implementar Form Requests (Recomendado)

**Paso 1:** Crear Form Requests

```bash
php artisan make:request StoreTipoTransmisionRequest
php artisan make:request UpdateTipoTransmisionRequest
```

**Paso 2:** Implementar Store Request

```php
// app/Http/Requests/StoreTipoTransmisionRequest.php
class StoreTipoTransmisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('create', TipoTransmision::class);
    }

    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:50|unique:tipos_transmision,nombre',
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo de transmisión es obligatorio',
            'nombre.unique' => 'Este tipo de transmisión ya existe',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
        ];
    }
}
```

**Paso 3:** Implementar Update Request

```php
// app/Http/Requests/UpdateTipoTransmisionRequest.php
class UpdateTipoTransmisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('update', $this->route('tipoTransmision'));
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:50',
                Rule::unique('tipos_transmision')->ignore($this->route('tipoTransmision'))
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del tipo de transmisión es obligatorio',
            'nombre.unique' => 'Este tipo de transmisión ya existe',
            'nombre.max' => 'El nombre no puede tener más de 50 caracteres',
        ];
    }
}
```

**Paso 4:** Actualizar el controlador

```php
// app/Http/Controllers/TipoTransmisionController.php
use App\Http\Requests\StoreTipoTransmisionRequest;
use App\Http\Requests\UpdateTipoTransmisionRequest;

public function store(StoreTipoTransmisionRequest $request)
{
    TipoTransmision::create($request->validated());
    // Resto del código...
}

public function update(UpdateTipoTransmisionRequest $request, TipoTransmision $tipoTransmision)
{
    $tipoTransmision->update($request->validated());
    // Resto del código...
}
```

### Buenas Prácticas

1. **Validación:** Usar Form Requests en lugar de validación inline en el controlador
2. **Autorización:** Implementar Policies para verificar permisos en cada acción
3. **Carga diferida:** Usar `with()` para relaciones en consultas de listado
4. **Cache:** Considerar caché para listados que no cambian frecuentemente
5. **Soft Deletes:** Implementar soft deletes para mantener histórico
6. **Eventos:** Usar modelos de eventos para lógica adicional al crear/actualizar/eliminar
7. **Verificación de dependencias:** Verificar siempre dependencias antes de eliminar

**Ejemplo de eager loading:**

```php
// Mal - N+1 queries
$tipos = TipoTransmision::all();
foreach ($tipos as $tipo) {
    echo $tipo->tramites()->count(); // Query por cada tipo
}

// Bien - 2 queries
$tipos = TipoTransmision::with('tramites')->get();
foreach ($tipos as $tipo) {
    echo $tipo->tramites->count(); // Ya cargado
}
```

**Ejemplo de cache:**

```php
use Illuminate\Support\Facades\Cache;

$tipos = Cache::remember('tipos_transmision.all', 3600, function () {
    return TipoTransmision::all();
});
```

### Testing

Considerar crear tests para:

```php
<?php

namespace Tests\Feature;

use App\Models\TipoTransmision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TipoTransmisionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function test_index_returns_view()
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)
            ->get(route('admin.tipos-transmision.index'));

        $response->assertStatus(200);
        $response->assertViewIs('admin.tipos-transmision.browse');
    }

    /** @test */
    public function test_create_tipo_transmision()
    {
        $user = User::factory()->create();

        $data = ['nombre' => 'Herencia'];

        $response = $this->actingAs($user)
            ->post(route('admin.tipos-transmision.store'), $data);

        $this->assertDatabaseHas('tipos_transmision', $data);
        $response->assertRedirect(route('admin.tipos-transmision.index'));
        $response->assertSessionHas('alert-type', 'success');
    }

    /** @test */
    public function test_cannot_duplicate_tipo_transmision()
    {
        $user = User::factory()->create();

        TipoTransmision::create(['nombre' => 'Herencia']);

        $data = ['nombre' => 'Herencia'];

        $response = $this->actingAs($user)
            ->post(route('admin.tipos-transmision.store'), $data);

        $response->assertSessionHasErrors('nombre');
    }

    /** @test */
    public function test_update_tipo_transmision()
    {
        $user = User::factory()->create();

        $tipo = TipoTransmision::create(['nombre' => 'Herencia']);
        $data = ['nombre' => 'Herencia Testimonial'];

        $response = $this->actingAs($user)
            ->put(route('admin.tipos-transmision.update', $tipo), $data);

        $this->assertDatabaseHas('tipos_transmision', $data);
        $response->assertRedirect(route('admin.tipos-transmision.index'));
    }

    /** @test */
    public function test_delete_without_dependencies()
    {
        $user = User::factory()->create();

        $tipo = TipoTransmision::create(['nombre' => 'Legado']);

        $response = $this->actingAs($user)
            ->delete(route('admin.tipos-transmision.destroy', $tipo));

        $this->assertDatabaseMissing('tipos_transmision', ['id' => $tipo->id]);
        $response->assertRedirect(route('admin.tipos-transmision.index'));
    }

    /** @test */
    public function test_cannot_delete_with_tasas()
    {
        $user = User::factory()->create();

        $tipo = TipoTransmision::create(['nombre' => 'Herencia']);
        $tipo->tasas()->create([
            'tasa' => 1.5,
            'departamento_id' => 1,
            'parentesco_id' => 1,
            'vigente_desde' => now()
        ]);

        $response = $this->actingAs($user)
            ->delete(route('admin.tipos-transmision.destroy', $tipo));

        $response->assertRedirect();
        $response->assertSessionHas('alert-type', 'error');
        $this->assertDatabaseHas('tipos_transmision', ['id' => $tipo->id]);
    }

    /** @test */
    public function test_cannot_delete_with_tramites()
    {
        $user = User::factory()->create();

        $tipo = TipoTransmision::create(['nombre' => 'Herencia']);

        $tramite = \App\Models\Tramite::factory()->create([
            'tipo_transmision_id' => $tipo->id
        ]);

        $response = $this->actingAs($user)
            ->delete(route('admin.tipos-transmision.destroy', $tipo));

        $response->assertRedirect();
        $response->assertSessionHas('alert-type', 'error');
        $this->assertDatabaseHas('tipos_transmision', ['id' => $tipo->id]);
    }

    /** @test */
    public function test_search_functionality()
    {
        $user = User::factory()->create();

        TipoTransmision::create(['nombre' => 'Herencia']);
        TipoTransmision::create(['nombre' => 'Donación']);
        TipoTransmision::create(['nombre' => 'Legado']);

        $response = $this->actingAs($user)
            ->get(route('admin.tipos-transmision.ajax.list', ['search' => 'Her']));

        $response->assertStatus(200);
        $response->assertSee('Herencia');
        $response->assertDontSee('Donación');
    }

    /** @test */
    public function test_ajax_list_pagination()
    {
        $user = User::factory()->create();

        TipoTransmision::factory()->count(15)->create();

        $response = $this->actingAs($user)
            ->get(route('admin.tipos-transmision.ajax.list', ['paginate' => 10]));

        $response->assertStatus(200);
    }

    /** @test */
    public function test_relationship_with_tramites()
    {
        $tipo = TipoTransmision::create(['nombre' => 'Herencia']);

        $tramite = \App\Models\Tramite::factory()->create([
            'tipo_transmision_id' => $tipo->id
        ]);

        $this->assertCount(1, $tipo->tramites);
        $this->assertEquals($tramite->id, $tipo->tramites->first()->id);
    }

    /** @test */
    public function test_relationship_with_tasas()
    {
        $tipo = TipoTransmision::create(['nombre' => 'Herencia']);

        $tasa = \App\Models\Tasa::factory()->create([
            'tipo_transmision_id' => $tipo->id
        ]);

        $this->assertCount(1, $tipo->tasas);
        $this->assertEquals($tasa->id, $tipo->tasas->first()->id);
    }
}
```

### Comandos Útiles

```bash
# Crear nueva migración
php artisan make:migration add_field_to_tipos_transmision_table --table=tipos_transmision

# Ejecutar migraciones
php artisan migrate

# Rollback última migración
php artisan migrate:rollback

# Crear controller
php artisan make:controller TipoTransmisionController --resource

# Crear policy
php artisan make:policy TipoTransmisionPolicy --model=TipoTransmision

# Crear factory
php artisan make:factory TipoTransmisionFactory

# Crear seeder
php artisan make:seeder TipoTransmisionSeeder

# Ejecutar seeder
php artisan db:seed --class=TipoTransmisionSeeder

# Ejecutar tests
php artisan test --filter TipoTransmisionTest

# Limpiar cache
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# Ver modelos y relaciones
php artisan model:show TipoTransmision
```

---

## 📞 Soporte y Mantenimiento

Para consultas o reportar issues relacionados con el módulo de Tipos de Transmisión, contactar al equipo de desarrollo o revisar la documentación del sistema ITGB.

**Documentación relacionada:**
- Documentación del módulo de Trámites
- Documentación del módulo de Tasas
- Documentación del servicio IdtgbCalculator
- Documentación del módulo de Parentescos

**Última actualización:** Enero 2026

**Versión:** 1.0.0
---
## 🚨 Análisis de Calidad y Mejoras

A continuación se detallan posibles bugs, inconsistencias y oportunidades de mejora detectadas en el análisis del código del módulo de Tipos de Transmisión. Este módulo es muy similar al de `Parentescos` y comparte varias de sus fortalezas y debilidades.

### 🐛 Inconsistencias y Riesgos Potenciales

1.  **Falta de Autorización Basada en Roles (Policies)**
    *   **Ubicación**: `app/Http/Controllers/TipoTransmisionController.php` (todos los métodos).
    *   **Problema**: A diferencia del módulo `ParentescoController`, este controlador no invoca a una `Policy` para autorizar las acciones (`$this->authorize(...)`). Aunque los permisos existen en la base de datos (`browse_tipos-transmision`, `add_tipos-transmision`, etc.), no se están aplicando en el backend.
    *   **Impacto**: **Cualquier usuario autenticado**, sin importar su rol, puede crear, editar y eliminar tipos de transmisión, simplemente accediendo a las URLs correspondientes. Esto es un **riesgo de seguridad y de integridad de datos significativo**.
    *   **Solución Crítica**: Implementar una `TipoTransmisionPolicy` y registrarla en `AuthServiceProvider`, luego llamar a `$this->authorize(...)` en cada método del controlador, tal como se hace en `ParentescoController`.

2.  **Validación de Datos en el Controlador**
    *   **Ubicación**: `app/Http/Controllers/TipoTransmisionController.php`, métodos `store()` y `update()`.
    *   **Problema**: La lógica de validación (`$request->validate(...)`) está directamente en el controlador. Esto es una inconsistencia con módulos más robustos como `Parentesco` que utilizan clases `FormRequest` (`StoreParentescoRequest`, `UpdateParentescoRequest`).
    *   **Impacto**: Dificulta la reutilización de la lógica de validación (ej. en una API) y mezcla responsabilidades en el controlador.
    *   **Solución Sugerida**: Crear `StoreTipoTransmisionRequest` y `UpdateTipoTransmisionRequest` para encapsular las reglas de validación y la autorización, manteniendo el controlador más limpio.

### 🚀 Oportunidades de Mejora y Optimización

1.  **Estandarización del Código**
    *   **Problema**: El código de este módulo es una versión simplificada de otros módulos CRUD, pero carece de las abstracciones (Policies, FormRequests) que se consideran una mejor práctica en Laravel y que están presentes en otras partes del sistema.
    *   **Mejora**: Refactorizar el `TipoTransmisionController` para que utilice `Policies` y `FormRequests`, alineándolo con la arquitectura del `ParentescoController`. Esto mejoraría la mantenibilidad y seguridad general del proyecto.

2.  **Añadir Conteo de Dependencias en la Vista de Listado**
    *   **Ubicación**: `resources/views/admin/tipos-transmision/list.blade.php`.
    *   **Mejora**: El método `destroy` ya comprueba si un tipo de transmisión está en uso. Sería muy útil para el administrador ver esta información directamente en la tabla de listado. Se podría añadir un contador de "Trámites Asociados" y "Tasas Asociadas".
    *   **Implementación Sugerida**:
        ```php
        // En TipoTransmisionController@list
        $data = TipoTransmision::withCount(['tasas', 'tramites'])
            ->when(...) // resto de la consulta

        // En la vista list.blade.php
        // ...
        <th>Trámites</th>
        <th>Tasas</th>
        // ...
        <td><span class="badge badge-info">{{ $item->tramites_count }}</span></td>
        <td><span class="badge badge-primary">{{ $item->tasas_count }}</span></td>
        // ...
        ```

3.  **Implementar `SoftDeletes` para Recuperación de Datos**
    *   **Problema**: La eliminación de un tipo de transmisión es permanente (`$tipoTransmision->delete()`). Si se borra por error un tipo que no tenía dependencias, la única forma de recuperarlo es desde un backup de la base de datos.
    *   **Mejora**: Añadir el trait `SoftDeletes` al modelo `TipoTransmision` para que los registros se marquen como eliminados en lugar de ser borrados permanentemente.
    *   **Impacto**: Proporciona una capa de seguridad contra la eliminación accidental de datos maestros.

### 📋 Funcionalidades Faltantes

1.  **Auditoría de Cambios**
    *   **Problema**: Al igual que en el módulo `Parentesco`, no se guarda un historial de quién creó, actualizó o eliminó un tipo de transmisión.
    *   **Necesidad**: Para un sistema tributario, es fundamental poder auditar todos los cambios en los datos maestros que afectan los cálculos.
    *   **Solución Sugerida**: Implementar un sistema de logging de actividad, ya sea simple (con columnas `created_by`, `updated_by`) o avanzado (usando un paquete como `spatie/laravel-activitylog`).

2.  **Traducciones y Localización**
    *   **Problema**: Todos los textos, como los mensajes de éxito/error, están fijos en español en el controlador.
    *   **Necesidad**: Para que la aplicación sea escalable a otros idiomas o regiones.
    *   **Solución Sugerida**: Mover todas las cadenas de texto a los archivos de idioma de Laravel en `lang/` y usar la función `__('key')` para recuperarlas.
