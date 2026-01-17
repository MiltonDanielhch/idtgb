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
---

## 🐛 Posibles Bugs y Problemas Detectados

### 🔴 BUG CRÍTICO: Vista `read.blade.php` No Existe

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:45`

**Descripción:**
El método `show()` del controlador intenta renderizar la vista `admin.tramites.inmuebles.read` pero esta vista no existe en `resources/views/admin/tramites/inmuebles/`. Esto causará un error 404 si alguien intenta acceder a esta vista.

```php
// app/Http/Controllers/TramiteInmuebleController.php:45
public function show(Tramite $tramite, TramiteInmueble $item)
{
    $this->authorize('view', $item);
    return view('admin.tramites.inmuebles.read', compact('tramite', 'item')); // ❌ VISTA INEXISTENTE
}
```

**Archivo de rutas:** `routes/web.php:186-191` - No hay ruta definida para el método `show`, por lo que es inaccesible.

**Solución:**
1. Crear la vista `resources/views/admin/tramites/inmuebles/read.blade.php`
2. O eliminar el método `show()` del controlador si no se necesita
3. Si se necesita, agregar la ruta: `Route::get('/{item}', [TramiteInmuebleController::class, 'show'])->name('show');`

---

### 🟡 BUG DE SEGURIDAD: Falta Validación de Propiedad en `destroy()`

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:89-105`

**Descripción:**
El método `destroy()` no valida que el `$item` pertenezca realmente al `$tramite`. Esto podría permitir eliminar inmuebles de otros trámites si se conoce el ID del item.

```php
// app/Http/Controllers/TramiteInmuebleController.php:89
public function destroy(Tramite $tramite, TramiteInmueble $item)
{
    $this->authorize('delete', $item);
    // ❌ Falta validación: if ($item->tramite_id !== $tramite->id) { abort(404); }
    // ...
}
```

**Comparación con otros controladores:**
- `TramiteExencionController.php:93-95` ✅ TIENE la validación
- `AdquirenteTramiteController.php:122-124` ✅ TIENE la validación

**Solución:**
```php
public function destroy(Tramite $tramite, TramiteInmueble $item)
{
    $this->authorize('delete', $item);

    if ($item->tramite_id !== $tramite->id) {
        abort(404);
    }

    // Resto del código...
}
```

---

### 🟡 BUG DE LÓGICA DE NEGOCIO: No se Recalcula el Impuesto

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:60-86` (store) y `89-105` (destroy)

**Descripción:**
Al agregar o eliminar un inmueble de un trámite, NO se llama al servicio `IdtgbCalculator` para recalcular el impuesto. Esto es inconsistente con otros controladores anidados que sí lo hacen.

**Referencias a otros controladores:**
- `AdquirenteTramiteController.php:104` ✅ SÍ llama a `app(IdtgbCalculator::class)->calcular($tramite);`
- `AdquirenteTramiteController.php:132` ✅ SÍ llama a `app(IdtgbCalculator::class)->calcular($tramite);`
- `TramiteExencionController.php:75` ✅ SÍ llama a `app(IdtgbCalculator::class)->calcular($tramite);`
- `TramiteExencionController.php:100` ✅ SÍ llama a `app(IdtgbCalculator::class)->calcular($tramite);`

**Impacto:**
Los montos calculados del trámite (`base_imponible`, `total_idtgb`, `monto_final`) pueden quedar desactualizados cuando se agregan o eliminan inmuebles.

**Solución:**
```php
// En el método store, después de crear:
app(IdtgbCalculator::class)->calcular($tramite);

// En el método destroy, después de eliminar:
app(IdtgbCalculator::class)->calcular($tramite);
```

---

### 🟡 PROBLEMA DE CONSISTENCIA: Transacción Innecesaria en `destroy()`

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:93-96`

**Descripción:**
El método `destroy()` usa una transacción de base de datos para eliminar un solo registro, lo cual es innecesario y puede causar problemas de rendimiento. Las transacciones deberían usarse solo cuando se realizan múltiples operaciones atómicas.

```php
DB::beginTransaction();
try {
    $item->delete(); // ❌ Solo una operación, no necesita transacción
    DB::commit();
```

**Comparación:**
- `TramiteExencionController.php:97-101` ✅ También usa transacción innecesaria
- `AdquirenteTramiteController.php:126-133` ✅ Justifica el uso de transacción porque además elimina archivos

**Solución:**
Eliminar la transacción o justificarla si en el futuro se agregarán más operaciones (ej: actualizar estado del inmueble, registro de auditoría, etc.).

---

### 🟡 POSIBLE BUG: Validación de Estado del Inmueble

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:60-68`

**Descripción:**
No se valida el estado del inmueble antes de asociarlo al trámite. Probablemente no debería permitirse asociar inmuebles en estado `'Transferido'` o `'Baja'`.

```php
// app/Http/Controllers/TramiteInmuebleController.php:64
$request->validate([
    'inmueble_id' => 'required|exists:inmuebles,id|unique:...', // ❌ No valida estado_inmueble
], [
    'inmueble_id.unique' => 'El inmueble ya fue agregado a este trámite.',
]);
```

**Posibles estados del inmueble** (según `app/Models/Inmueble.php`):
- `'Activo'`
- `'Transferido'`
- `'Baja'`

**Solución:**
```php
$inmueble = Inmueble::findOrFail($request->inmueble_id);

if (!in_array($inmueble->estado_inmueble, ['Activo'])) {
    return back()->withInput()
        ->with(['message' => 'Solo se pueden agregar inmuebles en estado Activo.', 'alert-type' => 'error']);
}
```

---

## 🚀 Posibles Mejoras

### 1. **Agregar Campos de Auditoría**

**Ubicación:** 
- `database/migrations/2025_09_22_122800_create_tramite_inmuebles_table.php`
- `app/Models/TramiteInmueble.php`

**Descripción:**
Agregar los campos `created_by` y `updated_by` para rastrear quién agregó o eliminó inmuebles de un trámite. Esto es consistente con otros módulos del sistema.

**Implementación sugerida:**
```php
// Migración
$table->foreignId('created_by')->nullable()->constrained('users');
$table->foreignId('updated_by')->nullable()->constrained('users');

// Modelo
protected $fillable = [
    'tramite_id',
    'inmueble_id',
    'created_by',
    'updated_by',
];

public function creador()
{
    return $this->belongsTo(User::class, 'created_by');
}

public function editor()
{
    return $this->belongsTo(User::class, 'updated_by');
}

// Controlador
TramiteInmueble::create([
    'tramite_id' => $tramite->id,
    'inmueble_id' => $request->inmueble_id,
    'created_by' => auth()->id(),
    'updated_by' => auth()->id(),
]);
```

---

### 2. **Agregar Búsqueda y Paginación en el Formulario de Creación**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:49-58` y `resources/views/admin/tramites/inmuebles/create.blade.php`

**Descripción:**
Si hay muchos inmuebles disponibles, el `<select>` puede ser difícil de usar. Mejor implementar un select con búsqueda o una tabla con búsqueda y paginación.

**Implementación sugerida:**
- Usar AJAX para cargar inmuebles a medida que el usuario escribe
- O implementar un modal con tabla de búsqueda y selección
- Similar a cómo funciona la selección de personas en otros módulos

---

### 3. **Validar Estado del Trámite Antes de Agregar Inmuebles**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:60-86`

**Descripción:**
No se debería permitir agregar inmuebles a trámites en ciertos estados como `'Pagado'`, `'Anulado'` o `'Finalizado'`.

**Implementación sugerida:**
```php
if (in_array($tramite->estado, ['Pagado', 'Anulado', 'Finalizado'])) {
    return back()->withInput()
        ->with(['message' => 'No se pueden agregar inmuebles a un trámite en estado ' . $tramite->estado, 'alert-type' => 'error']);
}
```

---

### 4. **Agregar Campos Extra a la Tabla Pivote**

**Ubicación:** `database/migrations/2025_09_22_122800_create_tramite_inmuebles_table.php`

**Descripción:**
Según la documentación, puede ser útil agregar campos adicionales como:
- `porcentaje_propiedad`: Para indicar qué parte del inmueble se transfiere
- `valor_declarado`: Si el valor declarado es diferente al valor catastral
- `observaciones`: Para notas específicas de la asociación

**Implementación sugerida:**
```php
$table->decimal('porcentaje_propiedad', 5, 2)->nullable(); // Ej: 100.00, 50.00, 33.33
$table->decimal('valor_declarado', 14, 2)->nullable();
$table->text('observaciones')->nullable();
```

---

### 5. **Agregar Scopes al Modelo**

**Ubicación:** `app/Models/TramiteInmueble.php`

**Descripción:**
Agregar scopes útiles para consultas comunes.

**Implementación sugerida:**
```php
public function scopePorTramite($query, $tramiteId)
{
    return $query->where('tramite_id', $tramiteId);
}

public function scopePorInmueble($query, $inmuebleId)
{
    return $query->where('inmueble_id', $inmuebleId);
}

public function scopeConInmuebleCompleto($query)
{
    return $query->with(['inmueble.tipoInmueble', 'inmueble.municipio.provincia.departamento']);
}
```

---

### 6. **Implementar Request de Validación Dedicada**

**Ubicación:** Nuevo archivo `app/Http/Requests/StoreTramiteInmuebleRequest.php`

**Descripción:**
Extraer la lógica de validación del método `store` a una clase `FormRequest` para mejor organización y reutilización.

**Implementación sugerida:**
```php
class StoreTramiteInmuebleRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $tramiteId = $this->route('tramite')->id;
        
        return [
            'inmueble_id' => [
                'required',
                'exists:inmuebles,id',
                Rule::unique('tramite_inmuebles')
                    ->where('tramite_id', $tramiteId)
                    ->where('inmueble_id', $this->inmueble_id),
            ],
        ];
    }

    public function messages()
    {
        return [
            'inmueble_id.unique' => 'El inmueble ya fue agregado a este trámite.',
        ];
    }
}
```

---

### 7. **Agregar Indicador Visual de Inmuebles con Conflictos**

**Ubicación:** `resources/views/admin/tramites/inmuebles/list.blade.php`

**Descripción:**
Mostrar algún indicador si un inmueble está asociado a otros trámites activos, lo cual podría indicar un problema o un caso especial.

**Implementación sugerida:**
```php
// En el controlador, agregar el conteo
$data = TramiteInmueble::with(['inmueble'])
    ->withCount(['tramite' => function($q) {
        $q->whereNotIn('estado', ['Anulado']);
    }])
    ->where('tramite_id', $tramite->id)
    ->paginate($paginate);

// En la vista
@if($item->tramite_count > 1)
    <span class="badge badge-warning" title="Este inmueble está en {{ $item->tramite_count }} trámites">
        ⚠️ {{ $item->tramite_count }}
    </span>
@endif
```

---

### 8. **Mejorar la Ordenación en el Listado**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:35`

**Descripción:**
La ordenación actual `orderBy('id')` no es muy útil para el usuario. Mejor ordenar por catastro o fecha de creación.

**Implementación sugerida:**
```php
// Ordenar por catastro del inmueble
$data = TramiteInmueble::with(['inmueble'])
    ->where('tramite_id', $tramite->id)
    ->join('inmuebles', 'inmuebles.id', '=', 'tramite_inmuebles.inmueble_id')
    ->orderBy('inmuebles.catastro')
    ->select('tramite_inmuebles.*')
    ->paginate($paginate);
```

---

## ❓ Faltas y Aspectos No Implementados

### 1. **Sin Pruebas Unitarias o de Integración**

**Ubicación:** Directorio `tests/`

**Descripción:**
No existen pruebas para el módulo `TramiteInmueble`. Esto hace que sea difícil garantizar que el módulo funciona correctamente y que los cambios futuros no rompan funcionalidad existente.

**Pruebas sugeridas:**
- `TramiteInmuebleTest.php` con tests para:
  - Agregar inmueble a trámite
  - Prevenir duplicados
  - Eliminar inmueble de trámite
  - Validar permisos de usuario
  - Validar estados del inmueble
  - Validar estados del trámite

---

### 2. **Sin Registro de Auditoría**

**Ubicación:** Todo el módulo

**Descripción:**
No hay registro de qué usuario agregó o eliminó inmuebles, cuándo, y desde qué IP. Esto es importante para auditoría y solución de problemas.

**Solución sugerida:**
Implementar un sistema de auditoría (usando paquetes como `spatie/laravel-activitylog` o una tabla personalizada de auditoría).

---

### 3. **Sin Validación de Negocio Compleja**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:60-86`

**Descripción:**
Faltan validaciones de negocio importantes:

1. **Validar que el trámite tiene al menos un inmueble antes de finalizar**
   - Un trámite de ITGB debería requerir al menos un inmueble

2. **Validar compatibilidad entre tipo de transmisión y tipo de inmueble**
   - Algunos tipos de transmisión pueden no aplicar a ciertos tipos de inmuebles

3. **Validar límites de cantidad de inmuebles**
   - Quizás debería haber un límite máximo por trámite (ej: 50 inmuebles)

---

### 4. **Sin Exportación de Datos**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php`

**Descripción:**
No hay función para exportar la lista de inmuebles de un trámite a Excel, CSV o PDF. Esto podría ser útil para reportes o respaldos.

**Implementación sugerida:**
```php
public function export(Tramite $tramite)
{
    $this->authorize('viewAny', TramiteInmueble::class);

    $inmuebles = TramiteInmueble::with(['inmueble'])
        ->where('tramite_id', $tramite->id)
        ->get();

    return Excel::download(new TramiteInmueblesExport($inmuebles), "inmuebles_tramite_{$tramite->nro_tramite}.xlsx");
}
```

---

### 5. **Sin Notificaciones o Eventos**

**Ubicación:** Todo el módulo

**Descripción:**
No hay eventos o notificaciones cuando se agregan o eliminan inmuebles. Esto podría ser útil para:
- Enviar notificaciones a supervisores
- Disparar acciones automáticas
- Registrar cambios en un log de auditoría

**Implementación sugerida:**
```php
// En el método store
event(new InmuebleAgregadoATramite($tramite, $inmueble, auth()->user()));

// En el método destroy
event(new InmuebleEliminadoDeTramite($tramite, $inmueble, auth()->user()));
```

---

### 6. **Sin API Endpoints**

**Ubicación:** `routes/api.php`

**Descripción:**
No hay endpoints de API para el módulo. Esto podría ser útil para:
- Integración con otros sistemas
- Aplicaciones móviles
- Webhooks
- Automatización

---

## ⚡ Optimizaciones Sugeridas

### 1. **Optimizar la Consulta de Inmuebles Disponibles**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:53`

**Descripción:**
La consulta `whereDoesntHave` puede ser ineficiente si hay muchos inmuebles en el sistema.

**Consulta actual:**
```php
$inmuebles = Inmueble::whereDoesntHave('tramiteInmuebles', fn($q) => $q->where('tramite_id', $tramite->id))
    ->orderBy('catastro')
    ->get();
```

**Optimización sugerida:**
```php
// Obtener IDs de inmuebles ya asociados
$excluidos = $tramite->inmuebles()->pluck('inmuebles.id');

// Usar whereNotIn que suele ser más eficiente
$inmuebles = Inmueble::whereNotIn('id', $excluidos)
    ->where('estado_inmueble', 'Activo')
    ->orderBy('catastro')
    ->get();
```

---

### 2. **Agregar Índices en la Base de Datos**

**Ubicación:** Nueva migración

**Descripción:**
Agregar índices para mejorar el rendimiento de las consultas más frecuentes.

**Índices sugeridos:**
```php
Schema::table('tramite_inmuebles', function (Blueprint $table) {
    $table->index(['tramite_id', 'inmueble_id']);
    $table->index('inmueble_id');
    $table->index('created_at');
});
```

---

### 3. **Implementar Caching para Inmuebles Disponibles**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:49-58`

**Descripción:**
Si la lista de inmuebles disponibles se consulta frecuentemente y cambia poco, podría implementarse caching.

**Implementación sugerida:**
```php
public function create(Tramite $tramite)
{
    $cacheKey = "inmuebles_disponibles_tramite_{$tramite->id}";
    
    $inmuebles = Cache::remember($cacheKey, 300, function() use ($tramite) {
        return Inmueble::whereDoesntHave('tramiteInmuebles', fn($q) => $q->where('tramite_id', $tramite->id))
            ->orderBy('catastro')
            ->get();
    });

    return view('admin.tramites.inmuebles.create', compact('tramite', 'inmuebles'));
}

// Invalidar el cache cuando se agrega o elimina un inmueble
public function store(...)
{
    // ... crear inmueble ...
    Cache::forget("inmuebles_disponibles_tramite_{$tramite->id}");
    // ...
}

public function destroy(...)
{
    // ... eliminar inmueble ...
    Cache::forget("inmuebles_disponibles_tramite_{$tramite->id}");
    // ...
}
```

---

### 4. **Optimizar Carga Eager Loading**

**Ubicación:** `app/Http/Controllers/TramiteInmuebleController.php:32`

**Descripción:**
El método `list` carga la relación `inmueble`, pero podría cargar también relaciones adicionales para evitar consultas N+1 en la vista.

**Optimización sugerida:**
```php
$data = TramiteInmueble::with([
    'inmueble.tipoInmueble',
    'inmueble.municipio.provincia.departamento'
])
->where('tramite_id', $tramite->id)
->when($search, fn($q) => $q->whereHas('inmueble', fn($sq) => $sq->where('catastro', 'like', "%{$search}%")))
->orderBy('id')
->paginate($paginate);
```

---

### 5. **Implementar Lazy Loading de la Tabla**

**Ubicación:** `resources/views/admin/tramites/inmuebles/browse.blade.php` y `list.blade.php`

**Descripción:**
En lugar de cargar todos los inmuebles al inicio, implementar carga diferida o virtual scrolling cuando hay muchos registros.

**Implementación sugerida:**
Usar una librería de virtual scrolling como `vue-virtual-scroller` o implementar infinite scroll con AJAX.

---

## 📊 Resumen de Ubicación de Archivos

### Archivos del Módulo TramiteInmueble

| Archivo | Ubicación | Estado |
|---------|-----------|--------|
| Migración | `database/migrations/2025_09_22_122800_create_tramite_inmuebles_table.php` | ✅ Existe |
| Modelo | `app/Models/TramiteInmueble.php` | ✅ Existe |
| Controlador | `app/Http/Controllers/TramiteInmuebleController.php` | ⚠️ Tiene bugs |
| Policy | `app/Policies/TramiteInmueblePolicy.php` | ✅ Existe |
| Rutas | `routes/web.php` (líneas 186-191) | ✅ Existen |
| Vista Browse | `resources/views/admin/tramites/inmuebles/browse.blade.php` | ✅ Existe |
| Vista List | `resources/views/admin/tramites/inmuebles/list.blade.php` | ✅ Existe |
| Vista Create | `resources/views/admin/tramites/inmuebles/create.blade.php` | ✅ Existe |
| Vista Read | `resources/views/admin/tramites/inmuebles/read.blade.php` | ❌ NO EXISTE |

### Archivos Relacionados

| Archivo | Ubicación | Uso en este módulo |
|---------|-----------|-------------------|
| Modelo Tramite | `app/Models/Tramite.php` | Define relación `belongsToMany` (líneas 69-77) |
| Modelo Inmueble | `app/Models/Inmueble.php` | Define relación `hasMany` con `TramiteInmueble` (líneas 36-39) |
| Servicio IdtgbCalculator | `app/Services/IdtgbCalculator.php` | NO SE USA (debería usarse en store/destroy) |

## 🔍 Checklist de Acciones Recomendadas

### 🔴 Urgente (Prioridad Alta)
- [ ] **Crear vista `read.blade.php` o eliminar método `show()`**
- [ ] **Agregar validación de propiedad en `destroy()`**
- [ ] **Llamar a `IdtgbCalculator` en `store()` y `destroy()`**

### 🟡 Importante (Prioridad Media)
- [ ] **Validar estado del inmueble antes de asociarlo**
- [ ] **Validar estado del trámite antes de permitir cambios**
- [ ] **Agregar campos de auditoría (`created_by`, `updated_by`)**
- [ ] **Implementar Request de validación dedicada**

### 🟢 Recomendado (Prioridad Baja)
- [ ] **Agregar pruebas unitarias**
- [ ] **Implementar sistema de auditoría**
- [ ] **Agregar función de exportación**
- [ ] **Implementar eventos y notificaciones**
- [ ] **Agregar API endpoints**
- [ ] **Optimizar consultas con índices**
- [ ] **Implementar caching**
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
