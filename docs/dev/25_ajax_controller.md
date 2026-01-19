# AjaxController - Documentación Técnica

## Descripción General

El `AjaxController` es un controlador especializado en manejar operaciones asíncronas (AJAX) relacionadas con la gestión de personas en el sistema. Proporciona endpoints para búsqueda dinámica y creación rápida de personas, principalmente utilizados por componentes Select2 y modales de registro.

## Ubicación

`app/Http/Controllers/AjaxController.php`

## Middleware y Seguridad

### Protección de Rutas

```php
public function __construct(){
    $this->middleware('auth');
}
```

El controlador está protegido por middleware que garantizan:

1. **auth**: Requiere autenticación de usuario
2. **loggin**: Registra todas las peticiones HTTP en el log de solicitudes
3. **system**: Verifica modo mantenimiento y modo desarrollo

### Grupo de Rutas

Las rutas se definen en `routes/web.php` dentro del grupo `/admin`:

```php
Route::prefix('ajax')->group(function () {
    Route::get('/personList', [AjaxController::class, 'personList']);
    Route::post('/person/store', [AjaxController::class, 'personStore']);
});
```

## Rutas Disponibles

| Método | Ruta | Acción | Descripción |
|--------|------|--------|-------------|
| GET | `/admin/ajax/personList` | personList | Busca personas dinámicamente |
| POST | `/admin/ajax/person/store` | personStore | Crea una nueva persona |

## Métodos del Controlador

### personList()

**Endpoint**: `GET /admin/ajax/personList`

**Propósito**: Realiza búsqueda dinámica de personas para ser utilizada en componentes Select2.

**Parámetros**:
- `q` (query, opcional): Término de búsqueda

**Campos buscados**:
- `ci` (Carnet de Identidad)
- `phone` (Teléfono)
- `first_name` (Primer nombre)
- `middle_name` (Segundo nombre)
- `paternal_surname` (Apellido paterno)
- `maternal_surname` (Apellido materno)
- Combinaciones de nombres completos

**Filtros aplicados**:
- Excluye registros con `deleted_at` no nulo (soft deletes)

**Retorno**:
```json
[
    {
        "id": 1,
        "ci": "12345678",
        "first_name": "Juan",
        "middle_name": "Daniel",
        "paternal_surname": "Perez",
        "maternal_surname": "Ortiz",
        "phone": "76558214",
        "image": "path/to/image.jpg",
        ...
    }
]
```

**Implementación**:
```php
public function personList(){
    $q = request('q');
    $data = Person::OrWhereRaw($q ? "ci like '%$q%'" : 1)
                    ->OrWhereRaw($q ? "phone like '%$q%'" : 1)
                    ->OrWhereRaw($q ? "first_name like '%$q%'" : 1)
                    ->OrWhereRaw($q ? "middle_name like '%$q%'" : 1)
                    ->OrWhereRaw($q ? "paternal_surname like '%$q%'" : 1)
                    ->OrWhereRaw($q ? "maternal_surname like '%$q%'" : 1)
                    ->orWhere(function ($subQ) use ($q) {
                        $subQ->whereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, '')) like ?", ["%$q%"])
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(paternal_surname, ''), ' ', COALESCE(maternal_surname, '')) like ?", ["%$q%"])
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(middle_name, ''), ' ', COALESCE(paternal_surname, ''), ' ', COALESCE(maternal_surname, '')) like ?", ["%$q%"]);
                    })
                    ->where('deleted_at', null)
                    ->get();
    return response()->json($data);
}
```

---

### personStore(Request $request)

**Endpoint**: `POST /admin/ajax/person/store`

**Propósito**: Crea una nueva persona utilizando todos los datos enviados en la solicitud.

**Transacciones**: Utiliza transacciones de base de datos para garantizar atomicidad.

**Cuerpo de la solicitud**:
```json
{
    "person_type": "Natural",
    "tipo_doc": "CI",
    "ci": "12345678",
    "ci_complemento": "1A",
    "nit": null,
    "first_name": "Juan",
    "middle_name": "Daniel",
    "paternal_surname": "Perez",
    "maternal_surname": "Ortiz",
    "legal_name": null,
    "birth_date": "1990-01-15",
    "email": "juan@example.com",
    "phone": "76558214",
    "address": "Calle 18 de Noviembre #123",
    "municipio_id": 1,
    "gender": "Masculino",
    "image": null,
    "status": 1
}
```

**Retorno exitoso (200)**:
```json
{
    "person": {
        "id": 123,
        "ci": "12345678",
        "first_name": "Juan",
        ...
    }
}
```

**Retorno de error (500)**:
```json
{
    "error": "Mensaje de error detallado"
}
```

**Implementación**:
```php
public function personStore(Request $request){
    DB::beginTransaction();
    try {
        $person = Person::create($request->all());
        DB::commit();
        return response()->json(['person' => $person]);
    } catch (\Throwable $th) {
        DB::rollback();
        return response()->json(['error' => $th->getMessage()], 500);
    }
}
```

## Modelo Relacionado

### Person

**Ubicación**: `app/Models/Person.php`

**Tabla**: `people`

**Campos fillable** (usados en `personStore`):
- `person_type`
- `tipo_doc`
- `ci`
- `ci_complemento`
- `nit`
- `first_name`
- `middle_name`
- `paternal_surname`
- `maternal_surname`
- `legal_name`
- `birth_date`
- `email`
- `phone`
- `address`
- `municipio_id`
- `gender`
- `image`
- `status`
- `estado_persona`
- `registerUser_id`
- `registerRole`
- `deleteUser_id`
- `deleteRole`
- `deleteObservation`

**Accesores útiles**:
- `$person->full_name`: Nombre completo combinado
- `$person->display_name`: Nombre de visualización (legal_name para jurídicas, full_name para naturales)
- `$person->display_document`: Documento de identificación formateado
- `$person->ubicacion_completa`: Ubicación geográfica completa
- `$person->display_image`: URL de la imagen con fallback

## Uso en Vistas

### Select2 para búsqueda de personas

**Variable global de URL** (definida en `resources/views/vendor/voyager/master.blade.php`):
```javascript
window.personListUrl = "{{ url('admin/ajax/personList') }}";
```

**Implementación en `public/js/include/person-select.js`**:
```javascript
$('#select-person_id').select2({
    placeholder: '<i class="fa fa-search"></i> Buscar...',
    minimumInputLength: 2,
    ajax: {
        url: window.personListUrl,
        delay: 250,
        processResults: function (data) {
            let results = [];
            data.map(data => {
                results.push({
                    ...data,
                    disabled: false
                });
            });
            return { results };
        },
        cache: true
    },
    templateResult: formatPersonResult,
    templateSelection: (opt) => {
        window.personSelected = opt;
        return opt.first_name ? 
            opt.first_name + ' ' + (opt.middle_name ? opt.middle_name + ' ' : '') + 
            opt.paternal_surname + (opt.maternal_surname ? ' ' + opt.maternal_surname : '') 
            : '<i class="fa fa-search"></i> Buscar... ';
    }
});
```

### Modal de registro rápido

**Ubicación**: `resources/views/partials/modal-registerPerson.blade.php`

```html
<form action="{{ url('admin/ajax/person/store') }}" id="create-form-person" method="POST">
    @csrf
    <!-- Campos del formulario -->
    <div class="form-group">
        <label>Primer Nombre</label>
        <input type="text" name="first_name" class="form-control" required>
    </div>
    <div class="form-group">
        <label>Apellido Paterno</label>
        <input type="text" name="paternal_surname" class="form-control" required>
    </div>
    <!-- Más campos... -->
    <button type="submit" class="btn btn-primary btn-save-person">Guardar</button>
</form>
```

### Uso en Wizard de Trámites

El wizard de trámites también utiliza el endpoint de búsqueda:

**En `create_step_2.blade.php` y `create_step_3.blade.php`**:
```javascript
$('#person-select').select2({
    theme: 'bootstrap',
    language: 'es',
    placeholder: 'Escriba nombre, CI o NIT para buscar...',
    minimumInputLength: 2,
    dropdownParent: $('#searchPersonModal'),
    ajax: {
        url: '{{ route("admin.tramites.wizard.ajax.personList") }}',
        dataType: 'json',
        delay: 300,
        data: function (params) {
            return { q: params.term };
        },
        processResults: function (data) {
            return { results: data.results };
        },
        cache: true
    }
});
```

## Migraciones de Base de Datos

### Tabla people

**Archivo**: `database/migrations/2025_04_07_092413_create_people_table.php`

Estructura principal:
```php
Schema::create('people', function (Blueprint $table) {
    $table->id();
    $table->enum('person_type', ['Natural', 'Jurídica'])->default('Natural');
    $table->string('tipo_doc', 10)->default('CI');
    $table->string('ci')->nullable();
    $table->string('ci_complemento', 5)->nullable();
    $table->string('nit')->nullable();
    $table->string('first_name')->nullable();
    $table->string('middle_name')->nullable();
    $table->string('paternal_surname')->nullable();
    $table->string('maternal_surname')->nullable();
    $table->string('legal_name')->nullable();
    $table->date('birth_date')->nullable();
    $table->string('email')->nullable();
    $table->string('phone')->nullable();
    $table->text('address')->nullable();
    $table->enum('gender', ['Masculino', 'Femenino'])->nullable();
    $table->string('image')->nullable();
    $table->tinyInteger('status')->default(1);
    $table->enum('estado_persona', ['Activo', 'Inactivo', 'Fallecido'])->default('Activo');
    $table->timestamps();
    $table->foreignId('registerUser_id')->nullable()->constrained('users');
    $table->string('registerRole')->nullable();
    $table->softDeletes();
    $table->foreignId('deleteUser_id')->nullable()->constrained('users');
    $table->string('deleteRole')->nullable();
    $table->text('deleteObservation')->nullable();
    $table->unique(['ci', 'ci_complemento']);
    $table->unique(['nit']);
});
```

### Adición de municipio_id

**Archivo**: `database/migrations/2025_10_20_000102_add_municipio_id_to_people_table.php`

```php
Schema::table('people', function (Blueprint $table) {
    $table->foreignId('municipio_id')->nullable()->constrained()->after('address');
});
```

## Seguridad Consideraciones

### Vulnerabilidades Potenciales

1. **SQL Injection**: El método `personList()` usa `OrWhereRaw()` con interpolación directa de strings. Aunque el parámetro `q` viene de la solicitud, debería usar parameterized queries:
   
   **Código actual (potencialmente inseguro)**:
   ```php
   ->OrWhereRaw($q ? "ci like '%$q%'" : 1)
   ```
   
   **Mejora sugerida**:
   ```php
   ->when($q, function ($query) use ($q) {
       return $query->where('ci', 'like', "%{$q}%")
                   ->orWhere('phone', 'like', "%{$q}%")
                   ->orWhere('first_name', 'like', "%{$q}%");
   })
   ```

2. **Mass Assignment**: `personStore()` usa `$request->all()` directamente. El modelo `Person` ya tiene `$fillable` definido, pero se debe asegurar que no haya campos sensibles no protegidos.

### Logging

Todas las peticiones a `AjaxController` son registradas por el middleware `Loggin`:
- Usuario autenticado
- IP del cliente
- URL solicitada
- Método HTTP
- Datos de entrada (excepto passwords y tokens)

## Ejemplos de Uso

### Ejemplo 1: Buscar personas desde frontend

```javascript
$.ajax({
    url: window.personListUrl,
    method: 'GET',
    data: { q: 'Juan' },
    success: function(data) {
        console.log(data);
        // Procesar resultados
    }
});
```

### Ejemplo 2: Crear persona desde modal

```javascript
$('#create-form-person').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: $(this).serialize(),
        success: function(response) {
            $('#modal-create-person').modal('hide');
            // Actualizar Select2 u otra interfaz
        },
        error: function(xhr) {
            alert('Error: ' + xhr.responseJSON.error);
        }
    });
});
```

## Consideraciones de Rendimiento

- **Caché**: Select2 utiliza caché en el cliente para reducir peticiones
- **Delay**: Retardo de 250-300ms antes de hacer la petición para evitar exceso de consultas
- **Minimum Input**: Requiere mínimo 2 caracteres antes de buscar
- **Soft Deletes**: La consulta excluye registros borrados lógicamente

## Dependencias

- `Illuminate\Http\Request`
- `App\Models\Person`
- `Illuminate\Support\Facades\DB`
- Select2 (jQuery plugin en frontend)

## Notas para Desarrolladores

1. El controlador está diseñado específicamente para operaciones AJAX y no retorna vistas HTML
2. Las rutas están bajo `/admin/ajax` para mantener organización y claridad
3. El método `personList()` podría optimizarse para usar Laravel's query builder en lugar de SQL raw
4. El método `personStore()` maneja transacciones pero podría mejorarse con validación de Request classes
5. Considerar añadir rate limiting para prevenir abuso de la API de búsqueda

---

## 🐛 Bugs y Problemas Identificados

### CRÍTICOS

#### 1. SQL Injection en `personList()`
**Ubicación**: `app/Http/Controllers/AjaxController.php:89-99`

**Descripción**: El método usa `OrWhereRaw()` con interpolación directa de strings sin sanitización. Aunque el parámetro viene de `request()`, no está debidamente protegido.

**Código vulnerable**:
```php
->OrWhereRaw($q ? "ci like '%$q%'" : 1)
->OrWhereRaw($q ? "phone like '%$q%'" : 1)
```

**Riesgo**: Un atacante podría inyectar código SQL malicioso si bypassa las validaciones frontend.

**Solución recomendada**:
```php
$q = request('q');
$data = Person::when($q, function ($query) use ($q) {
    return $query->where('ci', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")
                ->orWhere('first_name', 'like', "%{$q}%")
                ->orWhere('middle_name', 'like', "%{$q}%")
                ->orWhere('paternal_surname', 'like', "%{$q}%")
                ->orWhere('maternal_surname', 'like', "%{$q}%")
                ->orWhere('legal_name', 'like', "%{$q}%")
                ->orWhere('nit', 'like', "%{$q}%")
                ->orWhere(function ($subQ) use ($q) {
                    $subQ->where('first_name', 'like', "%{$q}%")
                         ->where('middle_name', 'like', "%{$q}%");
                });
})
->whereNull('deleted_at')
->limit(50)
->get(['id', 'ci', 'first_name', 'middle_name', 'paternal_surname', 'maternal_surname', 'legal_name', 'person_type', 'nit', 'phone', 'image']);
```

---

#### 2. Ausencia de Validación en `personStore()`
**Ubicación**: `app/Http/Controllers/AjaxController.php:161-171`

**Descripción**: El método usa `$request->all()` directamente sin pasar por un Request Class de validación. Esto permite crear registros con datos inválidos o potencialmente peligrosos.

**Código vulnerable**:
```php
$person = Person::create($request->all());
```

**Problemas**:
- No se validan los tipos de datos
- No se verifican unicidad de CI/NIT
- No se validan emails o teléfonos
- No hay validación de longitud de campos
- No hay validación condicional según tipo de persona

**Solución recomendada**:
```php
use App\Http\Requests\StorePersonRequest;

public function personStore(StorePersonRequest $request){
    DB::beginTransaction();
    try {
        $data = $request->validated();
        $data['registerUser_id'] = auth()->id();
        $data['registerRole'] = auth()->user()->role->name ?? null;
        
        $person = Person::create($data);
        DB::commit();
        return response()->json(['person' => $person]);
    } catch (\Throwable $th) {
        DB::rollback();
        return response()->json(['error' => $th->getMessage()], 500);
    }
}
```

**Request class existente**: `app/Http/Requests/StorePersonRequest.php`

---

#### 3. Modal de Registro sin `enctype` para Imágenes
**Ubicación**: `resources/views/partials/modal-registerPerson.blade.php:1`

**Descripción**: El formulario no tiene `enctype="multipart/form-data"`, lo que impide la subida de imágenes de perfil.

**Código actual**:
```html
<form action="{{ url('admin/ajax/person/store') }}" id="create-form-person" method="POST">
```

**Solución**:
```html
<form action="{{ url('admin/ajax/person/store') }}" id="create-form-person" method="POST" enctype="multipart/form-data">
```

**Problema adicional**: El modal no incluye el campo para subir imagen. Debería agregar:
```html
<div class="form-group">
    <label for="image">Foto de Perfil</label>
    <input type="file" name="image" class="form-control" accept="image/*">
</div>
```

---

#### 4. Valores Incorrectos en Campo `gender` del Modal
**Ubicación**: `resources/views/partials/modal-registerPerson.blade.php:44-48`

**Descripción**: Los valores del select están en minúsculas pero la base de datos espera mayúsculas (`'Masculino'`, `'Femenino'`).

**Código actual**:
```html
<option value="masculino">Masculino</option>
<option value="femenino">Femenino</option>
```

**Solución**:
```html
<option value="Masculino">Masculino</option>
<option value="Femenino">Femenino</option>
```

---

### MODERADOS

#### 5. Ausencia de Autorización por Policies
**Ubicación**: `app/Http/Controllers/AjaxController.php`

**Descripción**: El constructor solo verifica autenticación pero no autorización específica. Cualquier usuario autenticado puede buscar y crear personas.

**Código actual**:
```php
public function __construct(){
    $this->middleware('auth');
}
```

**Policy existente**: `app/Policies/PersonPolicy.php`

**Solución recomendada**:
```php
public function __construct(){
    $this->middleware('auth');
    $this->middleware('can:browse_people')->only(['personList']);
    $this->middleware('can:add_people')->only(['personStore']);
}
```

---

#### 6. Falta de Campos Esenciales en Modal
**Ubicación**: `resources/views/partials/modal-registerPerson.blade.php`

**Campos faltantes**:
1. `person_type`: No hay selector entre Natural/Jurídica
2. `tipo_doc`: No hay selector tipo de documento
3. `municipio_id`: No hay selector de municipio
4. `nit`: No hay campo para NIT (persona jurídica)
5. `legal_name`: No hay campo para razón social (persona jurídica)

**Problema**: El modal solo permite crear personas naturales, pero el endpoint recibe `$request->all()`.

**Solución**: Agregar lógica condicional en el modal para mostrar campos según tipo de persona, o usar una Request Validation que rechaze campos inválidos.

---

#### 7. Duplicación de Código - Dos Métodos de Búsqueda Similares
**Ubicaciones**:
- `app/Http/Controllers/AjaxController.php:15-31` (`personList`)
- `app/Http/Controllers/Admin/TramiteWizardController.php:708-733` (`ajaxPersonList`)

**Descripción**: Existen dos implementaciones similares para buscar personas con diferencias inconsistentes.

**Diferencias**:

| Aspecto | AjaxController | TramiteWizardController |
|---------|----------------|------------------------|
| Método | SQL Raw (`OrWhereRaw`) | Query Builder (`where`) |
| Límite de resultados | ❌ Sin límite | ✅ Limit 20 |
| Campos seleccionados | ❌ `get()` (todos) | ✅ `get([...])` (seleccionados) |
| Formato de respuesta | Array directo | `{results: [...]}` |
| Busca en `nit` | ❌ No | ✅ Sí |
| Busca en `legal_name` | ❌ No | ✅ Sí |
| Usa accesores del modelo | ❌ No | ✅ Sí |

**Problema**: Inconsistencia en la API de búsqueda. Diferentes partes de la aplicación usan diferentes endpoints con comportamientos distintos.

**Solución recomendada**: Extraer lógica a un Service o Scope compartido:

```php
// app/Services/PersonSearchService.php
class PersonSearchService
{
    public function search(string $term = '', int $limit = 50): Collection
    {
        return Person::where(function($query) use ($term) {
                if ($term) {
                    $query->where('ci', 'like', "%{$term}%")
                          ->orWhere('phone', 'like', "%{$term}%")
                          ->orWhere('first_name', 'like', "%{$term}%")
                          ->orWhere('middle_name', 'like', "%{$term}%")
                          ->orWhere('paternal_surname', 'like', "%{$term}%")
                          ->orWhere('maternal_surname', 'like', "%{$term}%")
                          ->orWhere('legal_name', 'like', "%{$term}%")
                          ->orWhere('nit', 'like', "%{$term}%");
                }
            })
            ->whereNull('deleted_at')
            ->limit($limit)
            ->get(['id', 'ci', 'first_name', 'middle_name', 'paternal_surname', 'maternal_surname', 'legal_name', 'person_type', 'nit', 'phone', 'image']);
    }
}
```

---

### LEVES

#### 8. Ausencia de Manejo de Archivos en `personStore()`
**Ubicación**: `app/Http/Controllers/AjaxController.php:161-171`

**Descripción**: El método no tiene lógica para manejar la subida y almacenamiento de imágenes.

**Solución**:
```php
use Illuminate\Support\Facades\Storage;

public function personStore(StorePersonRequest $request){
    DB::beginTransaction();
    try {
        $data = $request->validated();
        
        // Manejo de imagen
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $path = $image->store('people', 'public');
            $data['image'] = $path;
        }
        
        $data['registerUser_id'] = auth()->id();
        $data['registerRole'] = auth()->user()->role->name ?? null;
        
        $person = Person::create($data);
        DB::commit();
        return response()->json(['person' => $person]);
    } catch (\Throwable $th) {
        DB::rollback();
        return response()->json(['error' => $th->getMessage()], 500);
    }
}
```

---

#### 9. Sin Rate Limiting para Búsquedas
**Ubicación**: `routes/web.php:263` y configuración de rutas

**Descripción**: Las rutas AJAX no tienen límite de peticiones, lo que puede causar abuso o sobrecarga del servidor.

**Solución recomendada** (en `app/Providers/RouteServiceProvider.php`):
```php
RateLimiter::for('ajax-search', function (Request $request) {
    return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
});
```

Luego en rutas:
```php
Route::middleware(['auth', 'throttle:ajax-search'])
    ->prefix('ajax')
    ->group(function () {
        Route::get('/personList', [AjaxController::class, 'personList']);
        Route::post('/person/store', [AjaxController::class, 'personStore']);
    });
```

---

#### 10. Sin Índices en Campos Buscados
**Ubicación**: `database/migrations/2025_04_07_092413_create_people_table.php`

**Descripción**: Los campos frecuentemente buscados (`ci`, `phone`, `first_name`, etc.) no tienen índices de base de datos, afectando el rendimiento.

**Solución**: Crear nueva migración:
```php
// database/migrations/[timestamp]_add_indexes_to_people_table.php
public function up()
{
    Schema::table('people', function (Blueprint $table) {
        $table->index('ci');
        $table->index('phone');
        $table->index('first_name');
        $table->index('paternal_surname');
        $table->index('nit');
        $table->index('email');
    });
}
```

---

## 💡 Mejoras Recomendadas

### 1. Estandarizar Formato de Respuesta
**Ubicación**: `app/Http/Controllers/AjaxController.php:30,38`

**Problema**: Inconsistencia en el formato de respuesta con otros endpoints (ej. TramiteWizardController retorna `{results: [...]}`).

**Solución**:
```php
public function personList(){
    $q = request('q');
    $data = $this->searchPersons($q);
    return response()->json(['results' => $data]);
}
```

---

### 2. Agregar Paginación a `personList()`
**Ubicación**: `app/Http/Controllers/AjaxController.php:15-31`

**Descripción**: Actualmente retorna todos los resultados coincidentes, lo cual puede ser ineficiente con bases de datos grandes.

**Solución**:
```php
$data = Person::/* ... consulta ... */->limit(50)->paginate(20);
return response()->json([
    'results' => $data->items(),
    'pagination' => [
        'more' => $data->hasMorePages()
    ]
]);
```

---

### 3. Agregar Validación Frontend en Modal
**Ubicación**: `resources/views/partials/modal-registerPerson.blade.php`

**Descripción**: El modal solo tiene atributos HTML `required` pero no validación JavaScript.

**Solución**: Usar Laravel Form Request Validation con AJAX y mostrar errores en el modal:
```javascript
$('#create-form-person').on('submit', function(e) {
    e.preventDefault();
    
    $.ajax({
        url: $(this).attr('action'),
        method: 'POST',
        data: new FormData(this),
        processData: false,
        contentType: false,
        success: function(response) {
            $('#modal-create-person').modal('hide');
            // Mostrar toast de éxito
        },
        error: function(xhr) {
            if (xhr.status === 422) {
                // Mostrar errores de validación
                let errors = xhr.responseJSON.errors;
                $.each(errors, function(field, messages) {
                    // Mostrar mensajes en el formulario
                });
            } else {
                alert('Error: ' + xhr.responseJSON.error);
            }
        }
    });
});
```

---

### 4. Implementar Búsqueda Bilingüe/Normalizada
**Ubicación**: `app/Http/Controllers/AjaxController.php:15-31`

**Descripción**: La búsqueda actual es case-sensitive y no maneja acentos ni caracteres especiales.

**Solución**:
```php
use Illuminate\Support\Str;

$q = Str::lower(request('q')); // Normalizar a minúsculas

$data = Person::when($q, function ($query) use ($q) {
    return $query->whereRaw('LOWER(ci) LIKE ?', ["%{$q}%"])
                ->orWhereRaw('LOWER(first_name) LIKE ?', ["%{$q}%"])
                // ... más campos
                ->orWhereRaw('UNACCENT(first_name) ILIKE ?', ["%{$q}%"]); // PostgreSQL
});
```

Para MySQL:
```php
->orWhereRaw('REPLACE(REPLACE(REPLACE(first_name, "Á", "A"), "É", "E"), "Í", "I") LIKE ?', ["%{$q}%"])
```

---

### 5. Agregar Caché para Búsquedas Frecuentes
**Ubicación**: `app/Http/Controllers/AjaxController.php:15-31`

**Descripción**: Búsquedas repetidas del mismo término pueden cachearse para mejorar rendimiento.

**Solución**:
```php
use Illuminate\Support\Facades\Cache;

public function personList(){
    $q = request('q');
    $cacheKey = 'person_search:' . md5($q);
    
    $data = Cache::remember($cacheKey, 300, function () use ($q) {
        return Person::/* ... consulta ... */->limit(50)->get();
    });
    
    return response()->json($data);
}
```

**Invalidate cache**:
```php
// En personStore(), después de crear
Cache::forget('person_search:' . md5($request->ci));
Cache::forget('person_search:' . md5($request->first_name));
```

---

### 6. Agregar Logging Específico de Operaciones
**Ubicación**: `app/Http/Controllers/AjaxController.php:161-171`

**Descripción**: No hay logging específico de cuando se crea una persona por AJAX, solo el logging general del middleware.

**Solución**:
```php
use Illuminate\Support\Facades\Log;

public function personStore(StorePersonRequest $request){
    DB::beginTransaction();
    try {
        $person = Person::create($request->validated());
        DB::commit();
        
        Log::info('Persona creada vía AJAX', [
            'person_id' => $person->id,
            'ci' => $person->ci,
            'user_id' => auth()->id(),
            'ip' => request()->ip()
        ]);
        
        return response()->json(['person' => $person]);
    } catch (\Throwable $th) {
        DB::rollback();
        
        Log::error('Error creando persona vía AJAX', [
            'error' => $th->getMessage(),
            'user_id' => auth()->id(),
            'ip' => request()->ip()
        ]);
        
        return response()->json(['error' => $th->getMessage()], 500);
    }
}
```

---

### 7. Agregar Filtros Avanzados en Búsqueda
**Ubicación**: `app/Http/Controllers/AjaxController.php:15-31`

**Descripción**: La búsqueda actual no permite filtrar por tipo de persona, estado, municipio, etc.

**Solución**: Ampliar parámetros de búsqueda:
```php
public function personList(Request $request){
    $q = $request->input('q');
    $type = $request->input('type'); // Natural/Jurídica
    $status = $request->input('status'); // 1, 0, 2
    $municipio = $request->input('municipio_id');
    
    $data = Person::when($q, function ($query) use ($q) {
            return $query->where('ci', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('first_name', 'like', "%{$q}%");
        })
        ->when($type, function ($query) use ($type) {
            return $query->where('person_type', $type);
        })
        ->when($status, function ($query) use ($status) {
            return $query->where('status', $status);
        })
        ->when($municipio, function ($query) use ($municipio) {
            return $query->where('municipio_id', $municipio);
        })
        ->whereNull('deleted_at')
        ->limit(50)
        ->get();
    
    return response()->json($data);
}
```

---

### 8. Implementar Soft Delete en Búsqueda
**Ubicación**: `app/Http/Controllers/AjaxController.php:100`

**Descripción**: Actualmente excluye manualmente registros borrados (`where('deleted_at', null)`) en lugar de usar el trait del modelo.

**Código actual**:
```php
->where('deleted_at', null)
```

**Solución**: Eliminar esa línea y usar el scope del modelo:
```php
// El trait SoftDeletes ya excluye automáticamente
// No se necesita where('deleted_at', null)
```

---

## ⚠️ Cosas que Faltan

### 1. Tests de Unitarios y de Integración
**Ubicación**: No existen tests para este controlador

**Solución recomendada**: Crear tests en `tests/Feature/AjaxControllerTest.php`:
```php
public function test_person_list_requires_authentication()
{
    $response = $this->get('/admin/ajax/personList');
    $response->assertRedirect('/login');
}

public function test_person_list_searches_by_ci()
{
    $user = User::factory()->create();
    $person = Person::factory()->create(['ci' => '12345678']);
    
    $response = $this->actingAs($user)
                     ->get('/admin/ajax/personList?q=12345678');
    
    $response->assertStatus(200)
             ->assertJsonFragment(['ci' => '12345678']);
}

public function test_person_store_creates_person()
{
    $user = User::factory()->create();
    
    $response = $this->actingAs($user)
                     ->post('/admin/ajax/person/store', [
                         'person_type' => 'Natural',
                         'ci' => '87654321',
                         'first_name' => 'Test',
                         'paternal_surname' => 'User',
                     ]);
    
    $response->assertStatus(200)
             ->assertJsonStructure(['person']);
    
    $this->assertDatabaseHas('people', ['ci' => '87654321']);
}
```

---

### 2. Documentación de API (Swagger/OpenAPI)
**Ubicación**: No existe documentación de API

**Solución**: Usar packages como `darkaonline/l5-swagger` o `knuckleswtf/scribe` para generar documentación automática de la API.

---

### 3. Internacionalización (i18n)
**Ubicación**: Mensajes de error en español hardcoded

**Código actual**:
```php
return response()->json(['error' => $th->getMessage()], 500);
```

**Solución**: Usar archivos de lenguaje:
```php
return response()->json([
    'error' => __('person.create_error'),
    'message' => $th->getMessage()
], 500);
```

---

### 4. Métricas y Monitoreo
**Ubicación**: No existe monitoreo de uso

**Solución**: Agregar tracking de métricas:
```php
use Illuminate\Support\Facades\Cache;

public function personList(){
    $q = request('q');
    
    // Métrica: búsquedas por día
    Cache::increment('searches:person:' . date('Y-m-d'));
    
    // Métrica: términos más buscados
    if ($q) {
        Cache::increment('search_terms:' . md5($q));
    }
    
    // ... resto del código
}
```

---

### 5. Eventos y Listeners
**Ubicación**: No se disparan eventos al crear personas

**Solución**:
```php
// app/Events/PersonCreated.php
class PersonCreated
{
    public $person;
    
    public function __construct(Person $person)
    {
        $this->person = $person;
    }
}

// En personStore()
public function personStore(StorePersonRequest $request){
    DB::beginTransaction();
    try {
        $person = Person::create($request->validated());
        DB::commit();
        
        event(new PersonCreated($person));
        
        return response()->json(['person' => $person]);
    } catch (\Throwable $th) {
        DB::rollback();
        return response()->json(['error' => $th->getMessage()], 500);
    }
}

// Listener para notificaciones, logs, etc.
// app/Listeners/LogPersonCreation.php
public function handle(PersonCreated $event)
{
    // Enviar notificación, actualizar estadísticas, etc.
}
```

---

## 🚀 Optimizaciones de Rendimiento

### 1. Eager Loading para Relaciones
**Ubicación**: `app/Http/Controllers/AjaxController.php:15-31`

**Descripción**: Si se necesitan datos de relaciones (ej. municipio), debería hacer eager loading.

**Solución**:
```php
$data = Person::/* ... consulta ... */
    ->with('municipio.provincia.departamento')
    ->limit(50)
    ->get();
```

---

### 2. Optimizar Campos Seleccionados
**Ubicación**: `app/Http/Controllers/AjaxController.php:100`

**Descripción**: Actualmente selecciona todos los campos con `get()`, pero para Select2 solo se necesitan algunos.

**Solución**:
```php
->get(['id', 'ci', 'first_name', 'middle_name', 'paternal_surname', 
       'maternal_surname', 'legal_name', 'person_type', 'nit', 'phone', 'image'])
```

---

### 3. Usar Full-Text Search para Búsqueda Avanzada
**Ubicación**: `app/Http/Controllers/AjaxController.php:15-31`

**Descripción**: Para búsquedas más eficientes con grandes volúmenes de datos.

**Solución** (MySQL):
```php
// Agregar índice FULLTEXT en migración
$table->fullText(['first_name', 'middle_name', 'paternal_surname', 'maternal_surname']);

// En consulta
$data = Person::whereRaw("MATCH(first_name, middle_name, paternal_surname, maternal_surname) AGAINST(? IN BOOLEAN MODE)", [$q])
    ->whereNull('deleted_at')
    ->limit(50)
    ->get();
```

---

### 4. Usar Queue para Operaciones Pesadas
**Ubicación**: `app/Http/Controllers/AjaxController.php:161-171`

**Descripción**: Si en el futuro se agregan operaciones pesadas (procesamiento de imagen, notificaciones, etc.), usar queues.

**Solución**:
```php
use App\Jobs\ProcessPersonImage;
use Illuminate\Support\Facades\Bus;

public function personStore(StorePersonRequest $request){
    DB::beginTransaction();
    try {
        $data = $request->validated();
        $person = Person::create($data);
        DB::commit();
        
        // Procesar imagen en background
        if ($request->hasFile('image')) {
            Bus::dispatch(new ProcessPersonImage($person, $request->file('image')));
        }
        
        return response()->json(['person' => $person]);
    } catch (\Throwable $th) {
        DB::rollback();
        return response()->json(['error' => $th->getMessage()], 500);
    }
}
```

---

### 5. Optimizar Transacciones
**Ubicación**: `app/Http/Controllers/AjaxController.php:161-171`

**Descripción**: Actualmente hace `DB::beginTransaction()` pero la operación es simple (un solo `create`). La transacción adds overhead innecesario.

**Solución**:
```php
// Para operaciones simples, la transacción es opcional
// Solo usarla si hay múltiples operaciones o relaciones que crear

public function personStore(StorePersonRequest $request){
    try {
        $person = Person::create($request->validated());
        return response()->json(['person' => $person]);
    } catch (\Throwable $th) {
        return response()->json(['error' => $th->getMessage()], 500);
    }
}
```

---

### 6. Usar Database Indexing Compound
**Ubicación**: `database/migrations/2025_04_07_092413_create_people_table.php`

**Descripción**: Para búsquedas que frecuentemente combinan múltiples campos.

**Solución**:
```php
// Nueva migración
Schema::table('people', function (Blueprint $table) {
    $table->index(['status', 'person_type']); // Búsqueda por estado y tipo
    $table->index(['deleted_at', 'status']); // Para soft deletes + filtro activos
    $table->index('created_at'); // Para ordenamiento
});
```

---

## 📊 Resumen de Prioridades

| Prioridad | Ítem | Tipo | Impacto | Esfuerzo |
|-----------|------|------|---------|----------|
| 🔴 CRÍTICA | SQL Injection en personList | Bug | Alto | Bajo |
| 🔴 CRÍTICA | Validación en personStore | Bug | Alto | Bajo |
| 🔴 CRÍTICA | Autorización por Policies | Mejora | Alto | Bajo |
| 🟡 MODERADA | Duplicación de código | Mejora | Medio | Medio |
| 🟡 MODERADA | Índices de base de datos | Optimización | Alto | Bajo |
| 🟡 MODERADA | Campos faltantes en modal | Bug/Falta | Medio | Medio |
| 🟢 BAJA | Rate limiting | Mejora | Medio | Bajo |
| 🟢 BAJA | Tests | Falta | Alto | Alto |
| 🟢 BAJA | Caché de búsquedas | Optimización | Medio | Bajo |
| 🟢 BAJA | Documentación de API | Falta | Medio | Alto |

---

## 🔍 Recursos Adicionales

### Archivos Relacionados
- `app/Http/Controllers/AjaxController.php` - Controlador principal
- `app/Http/Controllers/Admin/TramiteWizardController.php` - Búsqueda alternativa de personas
- `app/Models/Person.php` - Modelo Person
- `app/Http/Requests/StorePersonRequest.php` - Validación para crear personas
- `app/Policies/PersonPolicy.php` - Política de autorización
- `resources/views/partials/modal-registerPerson.blade.php` - Modal de registro
- `public/js/include/person-select.js` - JavaScript para Select2
- `routes/web.php` - Definición de rutas

### Rutas Relevantes
```
GET  /admin/ajax/personList          - Buscar personas
POST /admin/ajax/person/store         - Crear persona
GET  /admin/tramites/wizard/ajax/person-list - Búsqueda alternativa (Wizard)
GET  /admin/people/ajax/list         - Listado AJAX completo (PersonController)
```

### Documentación Laravel
- [Query Builder](https://laravel.com/docs/11.x/queries)
- [Eloquent ORM](https://laravel.com/docs/11.x/eloquent)
- [Form Requests](https://laravel.com/docs/11.x/validation#form-request-validation)
- [Policies](https://laravel.com/docs/11.x/authorization)
- [Middleware](https://laravel.com/docs/11.x/middleware)
- [Caching](https://laravel.com/docs/11.x/cache)
