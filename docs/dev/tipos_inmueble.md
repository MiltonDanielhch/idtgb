# Documentación Técnica - Módulo de Tipos de Inmueble

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

---

## 🎯 Introducción

El módulo de **Tipos de Inmueble** gestiona los diferentes tipos de clasificación de inmuebles que pueden ser registrados en el sistema del Impuesto de Transmisiones Gratuítas de Bienes (ITGB). Este módulo permite categorizar los inmuebles según su naturaleza, uso o características.

### Propósito
- Mantener un catálogo de tipos de inmuebles (Urbano, Rústico, Comercial, etc.)
- Permitir la clasificación de inmuebles al crear registros de propiedades
- Facilitar el filtrado y búsqueda de inmuebles por tipo
- Gestionar el ciclo de vida CRUD completo de tipos de inmueble

### Importancia en el Sistema ITGB
La clasificación del tipo de inmueble es fundamental para:
- Determinar el valor catastral y avalúo
- Aplicar tasas o exenciones específicas según el tipo
- Generar reportes estadísticos por categoría de inmueble
- Facilitar búsquedas en el sistema

---

## 🗄️ Base de Datos

### Migración: `create_tipos_inmueble_table.php`

**Ubicación:** `database/migrations/2025_09_22_122733_create_tipos_inmueble_table.php`

**Estructura de la tabla:**

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(50) | UNIQUE, NOT NULL | Nombre del tipo de inmueble (ej: "Urbano", "Rústico") |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |
| `updated_at` | TIMESTAMP | NULLABLE | Fecha de actualización |

**Relaciones:**
- Es referenciado por la tabla `inmuebles` a través de la FK `tipo_inmueble_id`

**Ejemplo de SQL:**

```sql
CREATE TABLE tipos_inmueble (
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

### Modelo: `TipoInmueble`

**Ubicación:** `app/Models/TipoInmueble.php`

**Atributos:**
- `$table = 'tipos_inmueble'`
- `$fillable = ['nombre']`

**⚠️ IMPORTANTE - BUG CONOCIDO:**

El modelo `TipoInmueble` **NO tiene relaciones definidas**, pero el controlador intenta usar una relación `inmuebles()` en el método `destroy()`.

**Código actual del modelo:**

```php
class TipoInmueble extends Model
{
    use HasFactory;

    protected $table = 'tipos_inmueble';

    protected $fillable = [
        'nombre',
    ];
}
```

**Código necesario para corregir el bug:**

```php
class TipoInmueble extends Model
{
    use HasFactory;

    protected $table = 'tipos_inmueble';

    protected $fillable = [
        'nombre',
    ];

    // RELACIÓN FALTANTE - AGREGAR ESTO:
    public function inmuebles()
    {
        return $this->hasMany(Inmueble::class);
    }
}
```

**Uso del modelo:**

```php
// Obtener todos los tipos de inmueble
$tipos = TipoInmueble::all();

// Obtener un tipo específico
$tipo = TipoInmueble::find(1);

// Buscar por nombre
$tipo = TipoInmueble::where('nombre', 'Urbano')->first();

// Crear un nuevo tipo
TipoInmueble::create(['nombre' => 'Urbano']);

// Actualizar un tipo
$tipo->update(['nombre' => 'Urbano Residencial']);

// Eliminar un tipo
$tipo->delete();

// Contar inmuebles por tipo (DESPUÉS DE AGREGAR LA RELACIÓN)
if ($tipo->inmuebles()->count() > 0) {
    // Tiene inmuebles asociados
}
```

**Modelo relacionado - `Inmueble`:**

```php
// app/Models/Inmueble.php
public function tipoInmueble()
{
    return $this->belongsTo(TipoInmueble::class);
}
```

---

## 🎮 Controlador

### Controlador: `TipoInmuebleController`

**Ubicación:** `app/Http/Controllers/TipoInmuebleController.php`

**Middleware:**
- `auth` - Requiere autenticación

### Métodos del Controlador

#### 1. `index()` - Vista principal
```
GET /admin/tipos-inmueble
```
- Muestra la vista `browse.blade.php` con tabla interactiva
- Nota en código: "En el futuro, aquí se pueden añadir autorizaciones con Policies"
- No requiere permiso específico

#### 2. `list()` - Listado AJAX
```
GET /admin/tipos-inmueble/ajax/list
```
- Retorna lista paginada de tipos de inmueble
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

    $data = TipoInmueble::when($search, fn($q) => $q->where('nombre', 'like', "%{$search}%"))
        ->orderByDesc('id')
        ->paginate($paginate);

    return view('admin.tipos-inmueble.list', compact('data'));
}
```

#### 3. `create()` - Formulario de creación
```
GET /admin/tipos-inmueble/create
```
- Muestra formulario `edit-add.blade.php`
- No requiere permiso específico

#### 4. `store(Request $request)` - Guardar nuevo
```
POST /admin/tipos-inmueble
```
- Valida y crea nuevo tipo de inmueble
- Redirige al listado con mensaje de éxito
- Validación inline en el controlador

**Código del método:**

```php
public function store(Request $request)
{
    $validated = $request->validate([
        'nombre' => 'required|string|max:50|unique:tipos_inmueble,nombre',
    ]);

    TipoInmueble::create($validated);

    return redirect()->route('admin.tipos-inmueble.index')
        ->with(['message' => 'Tipo de Inmueble creado exitosamente.', 'alert-type' => 'success']);
}
```

#### 5. `show(TipoInmueble $tipoInmueble)` - Ver detalle
```
GET /admin/tipos-inmueble/{tipoInmueble}
```
- Muestra vista detallada `read.blade.php`
- No requiere permiso específico

#### 6. `edit(TipoInmueble $tipoInmueble)` - Formulario de edición
```
GET /admin/tipos-inmueble/{tipoInmueble}/edit
```
- Muestra formulario con datos existentes
- No requiere permiso específico

#### 7. `update(Request $request, TipoInmueble $tipoInmueble)` - Actualizar
```
PUT /admin/tipos-inmueble/{tipoInmueble}
```
- Valida y actualiza tipo de inmueble
- Redirige al listado con mensaje de éxito
- Validación inline en el controlador

**Código del método:**

```php
public function update(Request $request, TipoInmueble $tipoInmueble)
{
    $validated = $request->validate([
        'nombre' => 'required|string|max:50|unique:tipos_inmueble,nombre,' . $tipoInmueble->id,
    ]);

    $tipoInmueble->update($validated);

    return redirect()->route('admin.tipos-inmueble.index')
        ->with(['message' => 'Tipo de Inmueble actualizado exitosamente.', 'alert-type' => 'success']);
}
```

#### 8. `destroy(TipoInmueble $tipoInmueble)` - Eliminar
```
DELETE /admin/tipos-inmueble/{tipoInmueble}
```
- ⚠️ **BUG CONOCIDO:** Intenta verificar dependencias usando una relación que NO existe
- Debería verificar que no tenga inmuebles asociados antes de eliminar

**Código del método (CON BUG):**

```php
public function destroy(TipoInmueble $tipoInmueble)
{
    // Verificar si está en uso antes de borrar
    // ⚠️ ESTO FALLARÁ PORQUE NO EXISTE LA RELACIÓN inmuebles()
    if ($tipoInmueble->inmuebles()->exists()) {
        return back()->with([
            'message' => 'No se puede eliminar. El tipo de inmueble está siendo utilizado.',
            'alert-type' => 'error'
        ]);
    }

    $tipoInmueble->delete();

    return redirect()->route('admin.tipos-inmueble.index')
        ->with(['message' => 'Tipo de Inmueble eliminado.', 'alert-type' => 'success']);
}
```

**Código CORREGIDO (agregando la relación al modelo):**

```php
// En app/Models/TipoInmueble.php
public function inmuebles()
{
    return $this->hasMany(Inmueble::class);
}
```

---

## 🛣️ Rutas

### Rutas Definidas

**Ubicación:** `routes/web.php:109-110`

```php
Route::resource('tipos-inmueble', TipoInmuebleController::class)
    ->names('admin.tipos-inmueble')
    ->parameters(['tipos-inmueble' => 'tipoInmueble']);

Route::get('tipos-inmueble/ajax/list', [TipoInmuebleController::class, 'list'])
    ->name('admin.tipos-inmueble.ajax.list');
```

### Lista de Rutas

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| GET | `/admin/tipos-inmueble` | `admin.tipos-inmueble.index` | Listado principal |
| GET | `/admin/tipos-inmueble/create` | `admin.tipos-inmueble.create` | Formulario crear |
| POST | `/admin/tipos-inmueble` | `admin.tipos-inmueble.store` | Guardar nuevo |
| GET | `/admin/tipos-inmueble/{tipoInmueble}` | `admin.tipos-inmueble.show` | Ver detalle |
| GET | `/admin/tipos-inmueble/{tipoInmueble}/edit` | `admin.tipos-inmueble.edit` | Formulario editar |
| PUT/PATCH | `/admin/tipos-inmueble/{tipoInmueble}` | `admin.tipos-inmueble.update` | Actualizar |
| DELETE | `/admin/tipos-inmueble/{tipoInmueble}` | `admin.tipos-inmueble.destroy` | Eliminar |
| GET | `/admin/tipos-inmueble/ajax/list` | `admin.tipos-inmueble.ajax.list` | Listado AJAX |

**Middleware aplicado:**
- `loggin` - Autenticación de usuario
- `system` - Verificación de sistema

**Parámetro de ruta personalizado:**
- En lugar de usar `{tipos_inmueble}` (que sería el default por Laravel), se usa `{tipoInmueble}` para mejorar la legibilidad

**Grupo de rutas:**
```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // Rutas de tipos de inmueble aquí
});
```

---

## 🔒 Permisos

### Permisos Generados

**Ubicación:** `database/seeders/PermissionsTableSeeder.php:117`

Los permisos se generan automáticamente usando el método `Permission::generateFor('tipos-inmueble')` de Voyager.

**Permisos creados:**

| Permiso | Descripción |
|---------|-------------|
| `browse_tipos-inmueble` | Ver lista de tipos de inmueble |
| `read_tipos-inmueble` | Ver detalles de un tipo de inmueble |
| `edit_tipos-inmueble` | Editar tipos de inmueble |
| `add_tipos-inmueble` | Agregar nuevos tipos de inmueble |
| `delete_tipos-inmueble` | Eliminar tipos de inmueble |

**NOTA IMPORTANTE:** Aunque los permisos existen en la base de datos, el controlador `TipoInmuebleController` **NO utiliza Policies** ni verifica permisos en sus métodos. Toda la autorización se maneja a través del middleware `auth` y el control de acceso implícito.

### Permisos por Rol

Los permisos son asignados a roles a través de la interfaz de Voyager o mediante seeders. La configuración típica sería:

| Rol | browse | read | add | edit | delete |
|-----|--------|------|-----|------|--------|
| Admin | ✓ | ✓ | ✓ | ✓ | ✓ |
| Operador | ✓ | ✓ | ✓ | ✓ | ✗ |
| Visitante | ✓ | ✓ | ✗ | ✗ | ✗ |

---

## 🎨 Vistas

### Estructura de Vistas

**Ubicación:** `resources/views/admin/tipos-inmueble/`

### 1. `browse.blade.php` - Listado principal

**Funcionalidades:**
- Interfaz de búsqueda y filtrado
- Tabla dinámica con carga AJAX
- Select de paginación (10, 25, 50, 100)
- Botón para crear nuevo tipo de inmueble
- Mensajes de éxito/error vía `@include('voyager::alerts')`
- Uso del plugin `loading()` de jQuery para mostrar estado de carga

### 2. `list.blade.php` - Contenido tabla AJAX

**Características:**
- Tabla con columnas: ID, Nombre, Creado en, Acciones
- Botones de acción sin verificación de permisos (todos visibles)
- Paginación Laravel
- Estado vacío cuando no hay registros
- Confirmación JavaScript al eliminar

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

@section('page_title', isset($tipoInmueble) ? 'Editar Tipo de Inmueble' : 'Añadir Tipo de Inmueble')

@section('content')
    <div class="page-content edit-add container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-bordered">
                    <form role="form"
                          action="{{ isset($tipoInmueble) ? route('admin.tipos-inmueble.update', $tipoInmueble->id) : route('admin.tipos-inmueble.store') }}"
                          method="POST">
                        @if(isset($tipoInmueble))
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
                                       placeholder="Ej: Urbano, Rústico"
                                       value="{{ old('nombre', $tipoInmueble->nombre ?? '') }}">
                            </div>
                        </div>

                        <div class="panel-footer">
                            <button type="submit" class="btn btn-primary save">Guardar</button>
                            <a href="{{ route('admin.tipos-inmueble.index') }}" class="btn btn-default">Cancelar</a>
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
- Muestra información detallada del tipo de inmueble
- Botón para volver al listado
- Título dinámico

---

## 🔗 Integración con otros Módulos

### 1. Módulo de Inmuebles

**Modelo:** `Inmueble`
**Ubicación:** `app/Models/Inmueble.php`

**Relación:**

```php
public function tipoInmueble()
{
    return $this->belongsTo(TipoInmueble::class);
}
```

**Campo en tabla `inmuebles`:**
- `tipo_inmueble_id` - FK a `tipos_inmueble(id)` con restricción de clave foránea

**Request de validación:**

```php
// app/Http/Requests/StoreInmuebleRequest.php
'tipo_inmueble_id' => 'required|exists:tipos_inmueble,id',

// app/Http/Requests/UpdateInmuebleRequest.php
'tipo_inmueble_id' => 'required|exists:tipos_inmueble,id',
```

**Uso en vistas de inmuebles:**

```blade
<!-- resources/views/admin/inmuebles/edit-add.blade.php -->
<select name="tipo_inmueble_id" class="form-control select2" required>
    @foreach($tipos_inmueble as $t)
        <option value="{{ $t->id }}" {{ old('tipo_inmueble_id', optional($inmueble)->tipo_inmueble_id) == $t->id ? 'selected' : '' }}>
            {{ $t->nombre }}
        </option>
    @endforeach
</select>
```

**Uso en AJAX de inmuebles:**

```php
// app/Http/Controllers/InmuebleController.php
'tipo_inmueble' => $inmueble->tipoInmueble->nombre ?? 'N/A',
```

### 2. TramiteWizard - Paso 4: Inmuebles

**Vista:** `resources/views/admin/tramites/wizard/create_step_4.blade.php`

**Uso:**

```blade
// Al agregar un inmueble dinámicamente
<strong>Tipo:</strong> ${data.tipo_inmueble || 'N/A'}<br>
```

### 3. Database Seeders

**Seeder:** `InmuebleSeeder`
**Ubicación:** `database/seeders/InmuebleSeeder.php`

**Uso:**

```php
'tipo_inmueble_id' => $tipo->id,
```

**Seeder:** `CalculadoraDemoSeeder`
**Ubicación:** `database/seeders/CalculadoraDemoSeeder.php`

**Uso:**

```php
'tipo_inmueble_id' => 1,
```

---

## 📚 Ejemplos de Uso

### Ejemplo 1: Crear un nuevo tipo de inmueble

```php
// Vía Controller
$tipo = TipoInmueble::create([
    'nombre' => 'Urbano'
]);

// Vía Request
POST /admin/tipos-inmueble
{
    "nombre": "Urbano"
}

// Vía tinker
php artisan tinker
>>> use App\Models\TipoInmueble;
>>> TipoInmueble::create(['nombre' => 'Rústico']);
```

### Ejemplo 2: Obtener tipos de inmueble con inmuebles

```php
// Con todos los inmuebles
$tipos = TipoInmueble::with('inmuebles')->get();

// Solo tipos que tienen inmuebles
$tiposConInmuebles = TipoInmueble::has('inmuebles')->get();

// Contar inmuebles por tipo
$conteoInmuebles = TipoInmueble::withCount('inmuebles')
    ->orderBy('inmuebles_count', 'desc')
    ->get();
```

### Ejemplo 3: Crear un inmueble con tipo de inmueble

```php
$inmueble = Inmueble::create([
    'complemento' => 'LT-123',
    'catastro' => 'CAT-2023-001',
    'tipo_inmueble_id' => 1, // ID de "Urbano"
    'municipio_id' => 1,
    'barrio_comunidad' => 'Centro',
    'direccion' => 'Calle Principal #123',
    'superficie_m2' => 150.50,
    'valor_catastral' => 500000.00,
    'matricula_rr' => 'RR-2023-001',
    'es_vivienda_unica_familiar' => true,
    'estado_inmueble' => 'Registrado',
]);
```

### Ejemplo 4: Listado con AJAX

```javascript
// JavaScript en browse.blade.php
function fetch_data(page, search, paginate) {
    $('#div-results').loading({message: 'Cargando...'});
    $.ajax({
        url: "/admin/tipos-inmueble/ajax/list",
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

### Ejemplo 5: Buscar inmuebles por tipo

```php
// Obtener todos los inmuebles de tipo "Urbano"
$urbano = TipoInmueble::where('nombre', 'Urbano')->first();
$inmueblesUrbanos = $urbano->inmuebles;

// O usando la relación inversa
$inmueblesUrbanos = Inmueble::whereHas('tipoInmueble', function($query) {
    $query->where('nombre', 'Urbano');
})->get();
```

### Ejemplo 6: Verificar dependencias antes de eliminar

```php
$tipo = TipoInmueble::find(1);

// ⚠️ ESTO FALLARÁ HASTA QUE SE AGREGUE LA RELACIÓN AL MODELO
if ($tipo->inmuebles()->exists()) {
    throw new Exception('El tipo de inmueble está en uso');
}

// Alternativa temporal (consulta directa):
if (Inmueble::where('tipo_inmueble_id', $tipo->id)->exists()) {
    throw new Exception('El tipo de inmueble está en uso');
}

$tipo->delete();
```

---

## 🔍 Consideraciones Importantes

### ⚠️ BUG CONOCIDO - RELACIÓN FALTANTE

**Problema:**
- El controlador intenta usar `$tipoInmueble->inmuebles()->exists()` en el método `destroy()`
- El modelo `TipoInmueble` **NO tiene la relación `inmuebles()` definida**
- Esto causará un error al intentar eliminar un tipo de inmueble

**Solución:**

**Paso 1:** Agregar la relación al modelo `TipoInmueble`

```php
// app/Models/TipoInmueble.php
use App\Models\Inmueble;

class TipoInmueble extends Model
{
    use HasFactory;

    protected $table = 'tipos_inmueble';

    protected $fillable = [
        'nombre',
    ];

    // AGREGAR ESTE MÉTODO:
    public function inmuebles()
    {
        return $this->hasMany(Inmueble::class);
    }
}
```

**Paso 2:** Verificar que el método `destroy()` funcione correctamente

```bash
php artisan tinker
>>> $tipo = App\Models\TipoInmueble::find(1);
>>> $tipo->inmuebles()->count();
# Debería retornar el número de inmuebles asociados
```

### Reglas de Negocio

1. **Unicidad:** El nombre del tipo de inmueble debe ser único en todo el sistema
2. **Dependencias:** No se puede eliminar un tipo de inmueble si tiene inmuebles asociados (una vez corregido el bug)
3. **Longitud máxima:** El nombre no puede exceder 50 caracteres
4. **Requerido:** El tipo de inmueble es obligatorio para los inmuebles

### Validaciones Implementadas

**Al crear:**
- Nombre requerido
- Máximo 50 caracteres
- Debe ser único

**Al actualizar:**
- Nombre requerido
- Máximo 50 caracteres
- Debe ser único (ignorando el registro actual)

### Ícono en Menú

**Ubicación:** `database/seeders/IdtgbMenuAppendSeeder.php:31`

```php
['title' => 'Tipos de Inmueble',
 'route' => 'admin.tipos-inmueble.index',
 'icon_class' => 'fa-solid fa-house-chimney',
 'order' => 6]
```

### Mensajes de Éxito/Error

**Éxito:**
- "Tipo de Inmueble creado exitosamente."
- "Tipo de Inmueble actualizado exitosamente."
- "Tipo de Inmueble eliminado."

**Error (una vez corregido el bug):**
- "No se puede eliminar. El tipo de inmueble está siendo utilizado."

---

## 📝 Guía para Desarrolladores

### Corregir el Bug del Módulo

#### Paso 1: Agregar la relación al modelo

```php
// app/Models/TipoInmueble.php
<?php

namespace App\Models;

use App\Models\Inmueble;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoInmueble extends Model
{
    use HasFactory;

    protected $table = 'tipos_inmueble';

    protected $fillable = [
        'nombre',
    ];

    public function inmuebles()
    {
        return $this->hasMany(Inmueble::class);
    }
}
```

#### Paso 2: Probar la corrección

```bash
php artisan tinker
>>> $tipo = App\Models\TipoInmueble::first();
>>> $tipo->inmuebles()->count();
# Debería mostrar el número de inmuebles
>>> $tipo->inmuebles;
# Debería mostrar la colección de inmuebles
```

---

## 📞 Soporte y Mantenimiento

Para consultas o reportar issues relacionados con el módulo de Tipos de Inmueble, contactar al equipo de desarrollo o revisar la documentación del sistema ITGB.

**Documentación relacionada:**
- Documentación del módulo de Inmuebles
- Documentación del módulo de Trámites

**⚠️ CRÍTICO:** Corregir el bug de la relación faltante en el modelo `TipoInmueble` antes de usar el módulo en producción.

**Última actualización:** Enero 2026

**Versión:** 1.0.0
