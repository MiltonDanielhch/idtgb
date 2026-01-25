# AjaxController - Documentación Técnica

## 📋 Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Ubicación](#ubicación)
3. [Middleware y Seguridad](#middleware-y-seguridad)
4. [Rutas Disponibles](#rutas-disponibles)
5. [Métodos del Controlador](#métodos-del-controlador)
6. [Modelo Relacionado](#modelo-relacionado)
7. [Uso en Vistas](#uso-en-vistas)
8. [Migraciones de Base de Datos](#migraciones-de-base-de-datos)
9. [Seguridad Consideraciones](#seguridad-consideraciones)
10. [Ejemplos de Uso](#ejemplos-de-uso)
11. [Consideraciones de Rendimiento](#consideraciones-de-rendimiento)
12. [Dependencias](#dependencias)
13. [Notas para Desarrolladores](#notas-para-desarrolladores)
14. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

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

1. **SQL Injection**: El método `personList()` usa `OrWhereRaw()` con interpolación directa de strings. Aunque el parámetro `q` viene de la solicitud, debería usar parameterized queries.

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

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.0.0 (20 de mayo de 2025) ⚠️

El módulo `AjaxController` funciona correctamente para operaciones AJAX de búsqueda y creación de personas. Sin embargo, existen vulnerabilidades de seguridad críticas y oportunidades de mejora que deben abordarse urgentemente.

### 🐛 Bugs Conocidos (4/4) ❌

| # | Bug | Severidad | Estado | Ubicación |
|---|-----|-----------|--------|-----------|
| 1 | SQL Injection en `personList()` | Crítica | ❌ Pendiente | `AjaxController.php:17-26` |
| 2 | Ausencia de validación en `personStore()` | Alta | ❌ Pendiente | `AjaxController.php:36` |
| 3 | Modal sin `enctype` para imágenes | Media | ❌ Pendiente | `modal-registerPerson.blade.php:260` |
| 4 | Valores incorrectos en campo `gender` del modal | Media | ❌ Pendiente | `modal-registerPerson.blade.php:560-561` |

### 🚀 Mejoras Sugeridas (15)

| # | Mejora | Prioridad | Estado | Ubicación |
|---|--------|-----------|--------|-----------|
| 1 | Autorización por Policies | Alta | ⏳ Pendiente | `AjaxController.php:11-13` |
| 2 | Campos faltantes en modal (person_type, municipio_id, etc.) | Media | ⏳ Pendiente | `modal-registerPerson.blade.php` |
| 3 | Manejo de archivos en `personStore()` | Media | ⏳ Pendiente | `AjaxController.php:33-43` |
| 4 | Rate limiting para búsquedas | Media | ⏳ Pendiente | `routes/web.php:263` |
| 5 | Índices en campos buscados | Alta | ⏳ Pendiente | Migración nueva |
| 6 | Estandarizar formato de respuesta `{results: [...]}` | Media | ⏳ Pendiente | `AjaxController.php:30` |
| 7 | Agregar límite de resultados (limit 50) | Baja | ⏳ Pendiente | `AjaxController.php:29` |
| 8 | Agregar validación frontend en modal | Baja | ⏳ Pendiente | `modal-registerPerson.blade.php` |
| 9 | Búsqueda bilingüe/normalizada (case-insensitive) | Baja | ⏳ Pendiente | `AjaxController.php:17-26` |
| 10 | Caché para búsquedas frecuentes | Baja | ⏳ Pendiente | `AjaxController.php:15-31` |
| 11 | Logging específico de operaciones | Baja | ⏳ Pendiente | `AjaxController.php:33-43` |
| 12 | Filtros avanzados en búsqueda (tipo, estado, municipio) | Baja | ⏳ Pendiente | `AjaxController.php:15-31` |
| 13 | Tests automatizados para AjaxController | Alta | ⏳ Pendiente | `tests/Feature/` (nuevo) |
| 14 | Documentación de API (Swagger/OpenAPI) | Baja | ⏳ Pendiente | Documentación nueva |
| 15 | Internacionalización (i18n) | Baja | ⏳ Pendiente | `AjaxController.php:41` |

### ⚡ Optimizaciones Recomendadas (5)

| # | Optimización | Prioridad | Estado | Ubicación |
|---|--------------|-----------|--------|-----------|
| 1 | Eliminar SQL Raw y usar Query Builder | Crítica | ⏳ Pendiente | `AjaxController.php:17-26` |
| 2 | Índices de base de datos en campos buscados | Alta | ⏳ Pendiente | Migración nueva |
| 3 | Optimizar campos seleccionados (select específicos) | Media | ⏳ Pendiente | `AjaxController.php:29` |
| 4 | Eager loading para relaciones (municipio) | Baja | ⏳ Pendiente | `AjaxController.php:17-26` |
| 5 | Full-Text Search para búsqueda avanzada | Baja | ⏳ Pendiente | Migración + controlador |

### 📝 Detalle de Bugs Principales

#### Bug #1: SQL Injection en `personList()`

**Severidad:** CRÍTICA
**Ubicación:** `app/Http/Controllers/AjaxController.php:17-26`

El método usa `OrWhereRaw()` con interpolación directa de strings sin sanitización. Aunque el parámetro `q` viene de `request()`, no está debidamente protegido contra inyección SQL.

**Código vulnerable:**
```php
->OrWhereRaw($q ? "ci like '%$q%'" : 1)
->OrWhereRaw($q ? "phone like '%$q%'" : 1)
```

**Riesgo:** Un atacante podría inyectar código SQL malicioso si bypassa las validaciones frontend.

**Solución recomendada:**
```php
public function personList(){
    $q = request('q');
    $data = Person::when($q, function ($query) use ($q) {
        return $query->where('ci', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('first_name', 'like', "%{$q}%")
                    ->orWhere('middle_name', 'like', "%{$q}%")
                    ->orWhere('paternal_surname', 'like', "%{$q}%")
                    ->orWhere('maternal_surname', 'like', "%{$q}%")
                    ->orWhere('legal_name', 'like', "%{$q}%")
                    ->orWhere('nit', 'like', "%{$q}%");
    })
    ->whereNull('deleted_at')
    ->limit(50)
    ->get(['id', 'ci', 'first_name', 'middle_name', 'paternal_surname', 'maternal_surname', 'legal_name', 'person_type', 'nit', 'phone', 'image']);
    return response()->json(['results' => $data]);
}
```

---

#### Bug #2: Ausencia de Validación en `personStore()`

**Severidad:** ALTA
**Ubicación:** `app/Http/Controllers/AjaxController.php:36`

El método usa `$request->all()` directamente sin pasar por un Request Class de validación. Esto permite crear registros con datos inválidos o potencialmente peligrosos.

**Código vulnerable:**
```php
$person = Person::create($request->all());
```

**Problemas:**
- No se validan los tipos de datos
- No se verifican unicidad de CI/NIT
- No se validan emails o teléfonos
- No hay validación de longitud de campos
- No hay validación condicional según tipo de persona

**Solución recomendada:**
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

**Request class existente:** `app/Http/Requests/StorePersonRequest.php`

---

### 📊 Resumen de Prioridades

#### 🔴 URGENTE (Resolver inmediatamente)
1. **Bug #1:** SQL Injection en personList() - Vulnerabilidad de seguridad crítica
2. **Bug #2:** Ausencia de validación en personStore() - Permite datos inválidos
3. **Optimización #1:** Eliminar SQL Raw y usar Query Builder

#### 🟠 ALTA
1. **Mejora #1:** Autorización por Policies - Cualquier usuario autenticado puede buscar/crear personas
2. **Mejora #5:** Índices de base de datos - Mejora rendimiento de búsquedas
3. **Mejora #13:** Tests automatizados - Falta cobertura de tests

#### 🟡 MEDIA
1. **Bug #3:** Modal sin enctype para imágenes - Impide subida de fotos
2. **Bug #4:** Valores incorrectos en campo gender - Error de datos
3. **Mejora #2:** Campos faltantes en modal - Solo permite personas naturales
4. **Mejora #3:** Manejo de archivos en personStore()
5. **Mejora #4:** Rate limiting para búsquedas
6. **Mejora #6:** Estandarizar formato de respuesta

#### 🟢 BAJA
1. **Mejoras #7-12,14-15:** Mejoras funcionales y de experiencia
2. **Optimizaciones #3-5:** Optimizaciones de rendimiento

### 📝 Historial de Cambios

### v1.0.0 (20 de mayo de 2025)
**Versión inicial:**
- Implementación de `AjaxController` con métodos `personList()` y `personStore()`
- Búsqueda dinámica de personas para Select2
- Creación rápida de personas vía AJAX
- Integración con middleware de autenticación y logging

**Bugs conocidos identificados:**
- SQL Injection en personList() (vulnerabilidad crítica)
- Ausencia de validación en personStore()
- Modal sin enctype para imágenes
- Valores incorrectos en campo gender del modal

---

## 📚 Documentación Relacionada

- **Personas:** `docs/dev/01_people.md`
- **StorePersonRequest:** `app/Http/Requests/StorePersonRequest.php`
- **PersonPolicy:** `app/Policies/PersonPolicy.php`
- **Wizard de Trámites:** `docs/dev/07_tramites.md` (sección de wizard)
- **Person Select JS:** `public/js/include/person-select.js`
- **Modal Registro Persona:** `resources/views/partials/modal-registerPerson.blade.php`
