# Documentación Técnica - ValidacionController

## Descripción General

El `ValidacionController` es un controlador público que permite la verificación de trámites mediante un hash único de validación. Este controlador no requiere autenticación y está diseñado para que ciudadanos externos puedan verificar la autenticidad de los documentos de trámites generados por el sistema.

## Ubicación

- **Archivo:** `app/Http/Controllers/ValidacionController.php`
- **Namespace:** `App\Http\Controllers`
- **Vista asociada:** `resources/views/validacion/show.blade.php`

## Rutas

| Método HTTP | Ruta | Nombre de ruta | Descripción |
|-------------|------|----------------|-------------|
| GET | `/validar/{hash}` | `tramite.validar` | Muestra la información de validación de un trámite |

**Definición en `routes/web.php:62`:**
```php
Route::get('/validar/{hash}', [ValidacionController::class, 'show'])->name('tramite.validar');
```

## Dependencias

- **Modelo:** `App\Models\Tramite`

## Métodos

### `show(string $hash)`

Muestra la información de un trámite a partir de su hash de validación.

**Ubicación:** `app/Http/Controllers/ValidacionController.php:15-20`

#### Parámetros
- `$hash` (string): El hash de validación SHA256 de 64 caracteres

#### Retorno
- `Illuminate\View\View`: Vista con la información del trámite

#### Lógica
1. Busca el trámite por el campo `hash_validacion`
2. Si no existe, lanza excepción `ModelNotFoundException` (404)
3. Retorna la vista `validacion.show` con el trámite cargado

```php
public function show(string $hash)
{
    $tramite = Tramite::where('hash_validacion', $hash)->firstOrFail();
    return view('validacion.show', compact('tramite'));
}
```

## Hash de Validación

### Generación

El hash de validación se genera automáticamente mediante el método `generateHashValidacion()` en el modelo `Tramite` (`app/Models/Tramite.php:158-168`):

```php
public function generateHashValidacion(): string
{
    if ($this->hash_validacion) {
        return $this->hash_validacion;
    }

    $this->hash_validacion = hash('sha256', 
        $this->id . '|' . 
        $this->nro_tramite . '|' . 
        now()->timestamp . '|' . 
        Str::random(10)
    );
    $this->save();

    return $this->hash_validacion;
}
```

**Componentes del hash:**
- ID del trámite
- Número de trámite
- Timestamp actual
- String aleatorio de 10 caracteres
- Algoritmo: SHA256 (64 caracteres hexadecimales)

### Estructura en Base de Datos

**Migración:** `database/migrations/2025_09_22_122758_create_tramites_table.php:27`

```php
$table->char('hash_validacion', 64)->unique()->nullable();
```

**Atributo fillable en modelo:** `app/Models/Tramite.php:32`

```php
protected $fillable = [
    // ...
    'hash_validacion',
];
```

**Casteo automático:** `app/Models/Tramite.php:45`

```php
protected $casts = [
    // ...
    'hash_validacion' => 'string',
];
```

## Uso del Hash

### Generación en PDF

El hash se genera automáticamente al crear el PDF del Formulario A01 (`app/Http/Controllers/TramiteController.php:121`):

```php
public function a01(Tramite $tramite)
{
    // Genera el hash de validación si no existe
    $hash = $tramite->hash_validacion ?: $tramite->generateHashValidacion();

    // Genera la URL de validación y el código QR
    $validation_url = route('tramite.validar', ['hash' => $hash]);
    $qr = base64_encode(QrCode::format('svg')->size(200)->generate($validation_url));

    // ...
}
```

### Invalidación Automática

Si se modifican campos críticos de un trámite que ya tiene hash, este se invalida automáticamente (`app/Observers/TramiteObserver.php:16-32`):

```php
public function saving(Tramite $tramite): void
{
    if ($tramite->exists && !empty($tramite->hash_validacion)) {
        $camposCriticos = [
            'base_imponible',
            'fecha_transmision',
            'tipo_transmision_id',
            'tipo_contribuyente'
        ];

        if ($tramite->isDirty($camposCriticos)) {
            $tramite->hash_validacion = null;
        }
    }
}
```

## Vista de Validación

**Archivo:** `resources/views/validacion/show.blade.php`

### Características
- Diseño responsivo con Bootstrap 5.3.2
- Logo de la Gobernación del Beni
- Alerta verde indicando validación exitosa
- Información del trámite:
  - Número de trámite
  - Fecha de presentación
  - Tipo de transmisión
  - Adquirente principal
  - Monto del impuesto
  - Estado actual (con badge de color)
- Footer con fecha de generación

### Variables Disponibles en la Vista
- `$tramite`: Instancia del modelo `Tramite` con todas sus relaciones

## Seguridad

### Características de Seguridad
1. **Hash único:** Cada trámite tiene un hash único de 64 caracteres
2. **Índice único:** La base de datos garantiza que no haya hashes duplicados
3. **Invalidación automática:** Cambios en datos críticos invalidan el hash
4. **Sin autenticación requerida:** La validación es pública por diseño
5. **Validación de modelo:** `firstOrFail()` retorna 404 si el hash no existe

### Consideraciones de Seguridad
- El hash contiene información sensible (ID y nro_tramite) pero está encriptada con SHA256
- El timestamp y string aleatorio previenen colisiones de hash
- No se expone información adicional del trámite más allá de lo necesario para verificación

## Ejemplo de Uso

### URL de Validación
```
https://dominio.com/validar/a1b2c3d4e5f6... (64 caracteres hexadecimales)
```

### Generar URL en Código
```php
$hash = $tramite->generateHashValidacion();
$url = route('tramite.validar', ['hash' => $hash]);
```

### Generar Código QR
```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$hash = $tramite->generateHashValidacion();
$url = route('tramite.validar', ['hash' => $hash]);
$qr = QrCode::format('svg')->size(200)->generate($url);
```

## Relaciones del Modelo Tramite Utilizadas

En la vista de validación se accede a las siguientes relaciones:

- `tipoTransmision` → Tipo de transmisión del trámite
- `adquirentes` → Lista de adquirentes del trámite
- `adquirentes.person` → Persona del adquirente
- `adquirentes.person.display_name` o `full_name` → Nombre de la persona

## Estados de Trámite

Los posibles estados mostrados en la validación son:

- **Borrador** (gris)
- **Pagado** (verde)
- **Observado** (amarillo)
- **Anulado** (rojo)
- **Finalizado** (azul)

## Políticas de Autorización

Este controlador **NO** utiliza policies de autorización ya que es una ruta pública destinada a verificación externa de documentos. No requiere autenticación ni permisos específicos.

## Testing

### Casos de Prueba Recomendados

1. **Validación exitosa:** Verificar trámite con hash válido
2. **Hash inválido:** Intentar validar con hash inexistente (debe retornar 404)
3. **Hash vacío:** Intentar validar con string vacío
4. **Vista renderizada:** Verificar que la vista muestra correctamente los datos del trámite
5. **Colores de estado:** Verificar que cada estado tiene el color correcto en la UI

## Referencias Relacionadas

- **Modelo Tramite:** `app/Models/Tramite.php`
- **Controlador Tramite:** `app/Http/Controllers/TramiteController.php` (método `a01`)
- **Observer Tramite:** `app/Observers/TramiteObserver.php` (invalidación de hash)
- **Migración:** `database/migrations/2025_09_22_122758_create_tramites_table.php`
- **Vista PDF:** `resources/views/admin/tramites/pdf/a01.blade.php`
- **Rutas:** `routes/web.php:62`
- **Documentación adicional:** `docs/dev/documentos.md:828`

---

## ⚠️ Bugs Identificados

### 1. Campo `tipo_contribuyente` inexistente en Observer

**Severidad:** ALTA  
**Ubicación:** `app/Observers/TramiteObserver.php:25`

El observer intenta invalidar el hash si cambia el campo `tipo_contribuyente`, pero este campo NO existe en la tabla `tramites` según la migración `database/migrations/2025_09_22_122758_create_tramites_table.php`.

**Problema:**
```php
// En TramiteObserver.php
$camposCriticos = [
    'base_imponible',
    'fecha_transmision',
    'tipo_transmision_id',
    'tipo_contribuyente'  // ❌ Este campo no existe
];

if ($tramite->isDirty($camposCriticos)) {
    $tramite->hash_validacion = null;
}
```

**Impacto:**
- El observer nunca detecta cambios en un campo inexistente
- Si se planeaba usar este campo en el futuro, no se está protegiendo correctamente

**Solución:**
- Si el campo no se usa: Eliminar `'tipo_contribuyente'` del array `$camposCriticos`
- Si el campo debería existir: Crear una migración para agregar el campo

---

### 2. Sin validación de longitud de hash en el controlador

**Severidad:** MEDIA  
**Ubicación:** `app/Http/Controllers/ValidacionController.php:15-20`

El controlador no valida que el hash tenga 64 caracteres antes de realizar la consulta a la base de datos.

**Problema:**
```php
public function show(string $hash)
{
    $tramite = Tramite::where('hash_validacion', $hash)->firstOrFail();
    return view('validacion.show', compact('tramite'));
}
```

**Impacto:**
- Consultas innecesarias a la base de datos con hashes inválidos
- No se aprovecha el rechazo temprano de hashes malformados

**Solución:**
```php
public function show(string $hash)
{
    if (strlen($hash) !== 64) {
        abort(404, 'Hash de validación inválido');
    }
    
    $tramite = Tramite::where('hash_validacion', $hash)->firstOrFail();
    return view('validacion.show', compact('tramite'));
}
```

---

### 3. Posible error 500 si no existe la relación tipoTransmision

**Severidad:** MEDIA  
**Ubicación:** `resources/views/validacion/show.blade.php:54`

La vista accede directamente a `$tramite->tipoTransmision->nombre` sin verificar que la relación existe.

**Problema:**
```php
<dt class="col-sm-4">Tipo de Transmisión</dt>
<dd class="col-sm-8">{{ $tramite->tipoTransmision->nombre }}</dd>
```

**Impacto:**
- Error 500 si la relación no existe o fue eliminada
- Experiencia de usuario poor para ciudadanos verificando documentos

**Solución:**
```php
<dt class="col-sm-4">Tipo de Transmisión</dt>
<dd class="col-sm-8">{{ optional($tramite->tipoTransmision)->nombre ?? 'No especificado' }}</dd>
```

---

### 4. No se validan trámites en estado específico

**Severidad:** BAJA  
**Ubicación:** `app/Http/Controllers/ValidacionController.php:15-20`

El controlador muestra información de trámites en cualquier estado, incluyendo "Anulado" o "Borrador", lo cual podría no ser el comportamiento deseado.

**Problema:**
- Se pueden validar trámites en estado "Borrador" (incompletos)
- Se pueden validar trámites "Anulados" (ya no válidos)

**Solución:**
```php
public function show(string $hash)
{
    if (strlen($hash) !== 64) {
        abort(404, 'Hash de validación inválido');
    }
    
    $tramite = Tramite::where('hash_validacion', $hash)
        ->whereIn('estado', ['Pagado', 'Finalizado'])
        ->firstOrFail();
    
    return view('validacion.show', compact('tramite'));
}
```

---

### 5. No se usa `whereHashValidacion()` como scope

**Severidad:** BAJA  
**Ubicación:** `app/Http/Controllers/ValidacionController.php:17`

El modelo `Tramite` no tiene un scope para búsquedas por hash, lo que reduce la reutilización y legibilidad del código.

**Solución recomendada:**
```php
// En Tramite.php
public function scopeByHash($query, string $hash)
{
    return $query->where('hash_validacion', $hash);
}

// En ValidacionController.php
$tramite = Tramite::byHash($hash)->firstOrFail();
```

---

## 💡 Mejoras Sugeridas

### 1. Agregar rate limiting a la ruta de validación

**Ubicación:** `routes/web.php:62`  
**Prioridad:** ALTA

La ruta pública `/validar/{hash}` no tiene límite de peticiones, lo que permite fuerza bruta para encontrar hashes válidos.

**Implementación:**
```php
// En routes/web.php
Route::get('/validar/{hash}', [ValidacionController::class, 'show'])
    ->name('tramite.validar')
    ->middleware('throttle:60,1'); // 60 solicitudes por minuto por IP

// O agregar en RouteServiceProvider.php
RateLimiter::for('validacion', function (Request $request) {
    return Limit::perMinute(30)->by($request->ip());
});

// En routes/web.php
Route::get('/validar/{hash}', [ValidacionController::class, 'show'])
    ->name('tramite.validar')
    ->middleware('throttle:validacion');
```

---

### 2. Crear endpoint API JSON para validación

**Ubicación:** `routes/api.php` (nuevo)  
**Prioridad:** MEDIA

Solo existe la vista HTML, sería útil tener una API JSON para integración con otros sistemas o aplicaciones móviles.

**Implementación:**
```php
// En routes/api.php
Route::get('/validar/{hash}', [ValidacionController::class, 'apiShow'])
    ->name('api.tramite.validar');

// En ValidacionController.php
public function apiShow(string $hash)
{
    if (strlen($hash) !== 64) {
        return response()->json(['error' => 'Hash inválido'], 404);
    }
    
    $tramite = Tramite::where('hash_validacion', $hash)->firstOrFail();
    
    return response()->json([
        'valido' => true,
        'tramite' => [
            'nro_tramite' => $tramite->nro_tramite,
            'fecha_presentacion' => $tramite->fecha_presentacion->format('Y-m-d'),
            'tipo_transmision' => $tramite->tipoTransmision->nombre,
            'adquirente_principal' => optional($tramite->adquirentes->first()?->person)->full_name,
            'monto_impuesto' => (float) $tramite->total_idtgb,
            'estado' => $tramite->estado,
        ]
    ]);
}
```

---

### 3. Agregar logging de intentos de validación

**Ubicación:** `app/Http/Controllers/ValidacionController.php:15-20`  
**Prioridad:** MEDIA

Registrar qué hashes se consultan (exitosos y fallidos) para detectar intentos de ataque o patrones sospechosos.

**Implementación:**
```php
use Illuminate\Support\Facades\Log;

public function show(string $hash)
{
    if (strlen($hash) !== 64) {
        Log::warning('Intento de validación con hash inválido', [
            'hash' => $hash,
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent()
        ]);
        abort(404, 'Hash de validación inválido');
    }
    
    $tramite = Tramite::where('hash_validacion', $hash)->firstOrFail();
    
    Log::info('Trámite validado exitosamente', [
        'tramite_id' => $tramite->id,
        'nro_tramite' => $tramite->nro_tramite,
        'ip' => request()->ip()
    ]);
    
    return view('validacion.show', compact('tramite'));
}
```

---

### 4. Mejorar la vista con metadatos SEO y Open Graph

**Ubicación:** `resources/views/validacion/show.blade.php:1-6`  
**Prioridad:** BAJA

Agregar metadatos para mejor apariencia al compartir en redes sociales.

**Implementación:**
```html
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificación de Trámite {{ $tramite->nro_tramite }} - G.A.D. BENI</title>
    
    <!-- Open Graph -->
    <meta property="og:title" content="Trámite Validado - {{ $tramite->nro_tramite }}">
    <meta property="og:description" content="Trámite validado exitosamente en el sistema del G.A.D. Beni">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ request()->url() }}">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Trámite Validado - {{ $tramite->nro_tramite }}">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <!-- ... -->
</head>
```

---

### 5. Agregar indicador visual cuando el hash fue regenerado

**Ubicación:** `app/Models/Tramite.php` y vista  
**Prioridad:** BAJA

Mostrar en la vista cuándo fue generada/actualizada la validación para dar contexto al usuario.

**Implementación:**
```php
// En Tramite.php
protected $fillable = [
    // ...
    'hash_validacion',
    'hash_generado_at',
];

// En generateHashValidacion()
public function generateHashValidacion(): string
{
    if ($this->hash_validacion) {
        return $this->hash_validacion;
    }

    $this->hash_validacion = hash('sha256', 
        $this->id . '|' . 
        $this->nro_tramite . '|' . 
        now()->timestamp . '|' . 
        Str::random(10)
    );
    $this->hash_generado_at = now();
    $this->save();

    return $this->hash_validacion;
}

// En la vista
@if($tramite->hash_generado_at)
    <small class="text-muted">Validación generada: {{ $tramite->hash_generado_at->format('d/m/Y H:i') }}</small>
@endif
```

---

## ❌ Funcionalidades Faltantes

### 1. No hay tests automatizados

**Ubicación:** `tests/Feature/`  
**Prioridad:** ALTA

No existe ningún test para `ValidacionController`, lo que pone en riesgo la estabilidad del sistema.

**Tests recomendados:**
```php
// tests/Feature/ValidacionControllerTest.php
class ValidacionControllerTest extends TestCase
{
    public function test_valida_tramite_con_hash_correcto()
    {
        $tramite = Tramite::factory()->create([
            'hash_validacion' => hash('sha256', 'test')
        ]);

        $response = $this->get("/validar/{$tramite->hash_validacion}");
        $response->assertStatus(200);
        $response->assertSee($tramite->nro_tramite);
    }

    public function test_retorna_404_con_hash_inexistente()
    {
        $response = $this->get('/validar/' . str_repeat('a', 64));
        $response->assertStatus(404);
    }

    public function test_rechaza_hash_con_longitud_incorrecta()
    {
        $response = $this->get('/validar/hash_corto');
        $response->assertStatus(404);
    }

    public function test_no_valida_tramites_anulados()
    {
        $tramite = Tramite::factory()->create([
            'estado' => 'Anulado',
            'hash_validacion' => hash('sha256', 'test')
        ]);

        $response = $this->get("/validar/{$tramite->hash_validacion}");
        $response->assertStatus(404);
    }
}
```

---

### 2. No hay notificación al invalidar hash

**Prioridad:** MEDIA

Cuando se invalida un hash por cambio de datos críticos, no se notifica al funcionario que generó el PDF anterior.

**Solución:**
```php
// En TramiteObserver.php
public function saving(Tramite $tramite): void
{
    if ($tramite->exists && !empty($tramite->hash_validacion)) {
        $camposCriticos = ['base_imponible', 'fecha_transmision', 'tipo_transmision_id'];

        if ($tramite->isDirty($camposCriticos)) {
            // Notificar al creador del trámite
            $tramite->creador?->notify(new HashInvalidadoNotification($tramite));
            
            $tramite->hash_validacion = null;
        }
    }
}

// Crear notification: app/Notifications/HashInvalidadoNotification.php
```

---

### 3. No hay historial de cambios de hash

**Prioridad:** BAJA

No se registra cuándo y por qué se invalidó un hash, lo cual es útil para auditoría.

**Solución:** Crear una tabla `tramite_hash_historial`:
```php
Schema::create('tramite_hash_historial', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tramite_id')->constrained();
    $table->char('hash_anterior', 64)->nullable();
    $table->json('campos_modificados')->nullable();
    $table->foreignId('modificado_por')->nullable()->constrained('users');
    $table->timestamp('fecha_invalidacion');
    $table->timestamps();
});
```

---

### 4. No hay funcionalidad de regeneración manual de hash

**Prioridad:** MEDIA

Si el hash se invalida, no hay una forma fácil de regenerarlo desde la interfaz administrativa.

**Solución:** Agregar botón en la vista de detalle del trámite:
```php
// En TramiteController.php
public function regenerarHash(Tramite $tramite)
{
    $this->authorize('update', $tramite);
    
    $tramite->hash_validacion = null;
    $hash = $tramite->generateHashValidacion();
    
    return back()->with([
        'message' => "Hash regenerado: {$hash}",
        'alert-type' => 'success'
    ]);
}

// Ruta
Route::post('tramites/{tramite}/regenerar-hash', [TramiteController::class, 'regenerarHash'])
    ->name('admin.tramites.regenerar-hash');
```

---

### 5. No hay verificación de firma digital

**Prioridad:** BAJA

El hash de validación no está firmado criptográficamente, por lo que un atacante con acceso a la base de datos podría generar hashes válidos.

**Solución:** Usar firmas digitales con una llave privada:
```php
public function generateHashValidacion(): string
{
    if ($this->hash_validacion) {
        return $this->hash_validacion;
    }

    $data = $this->id . '|' . $this->nro_tramite . '|' . now()->timestamp;
    $signature = sodium_crypto_sign($data, config('app.signing_key'));
    $this->hash_validacion = bin2hex($signature);
    $this->save();

    return $this->hash_validacion;
}
```

---

## ⚡ Optimizaciones

### 1. Optimizar consulta de base de datos con eager loading

**Ubicación:** `app/Http/Controllers/ValidacionController.php:17`  
**Prioridad:** MEDIA

El controlador no carga las relaciones necesarias, lo que causa el problema N+1.

**Antes:**
```php
$tramite = Tramite::where('hash_validacion', $hash)->firstOrFail();
```

**Después:**
```php
$tramite = Tramite::with(['tipoTransmision', 'adquirentes.person'])
    ->where('hash_validacion', $hash)
    ->firstOrFail();
```

---

### 2. Agregar índice para búsquedas de hash

**Ubicación:** Migración nueva  
**Prioridad:** ALTA

Aunque ya existe índice unique, asegurarse de que esté optimizado:

```php
// En migración de corrección
Schema::table('tramites', function (Blueprint $table) {
    $table->index('hash_validacion');
});
```

**Nota:** El índice `unique()` ya crea un índice B-tree, pero es bueno verificar que esté en producción.

---

### 3. Implementar caché de trámites validados frecuentemente

**Ubicación:** `app/Http/Controllers/ValidacionController.php:15-20`  
**Prioridad:** BAJA

Los trámites más validados pueden cachearse para reducir carga de base de datos.

**Implementación:**
```php
use Illuminate\Support\Facades\Cache;

public function show(string $hash)
{
    if (strlen($hash) !== 64) {
        abort(404, 'Hash de validación inválido');
    }
    
    $tramite = Cache::remember("tramite:{$hash}", 3600, function() use ($hash) {
        return Tramite::with(['tipoTransmision', 'adquirentes.person'])
            ->where('hash_validacion', $hash)
            ->firstOrFail();
    });
    
    return view('validacion.show', compact('tramite'));
}

// Invalidar caché al actualizar trámite
// En TramiteObserver.php
public function updated(Tramite $tramite): void
{
    if ($tramite->hash_validacion) {
        Cache::forget("tramite:{$tramite->hash_validacion}");
    }
}
```

---

### 4. Usar route model binding para hash

**Ubicación:** `app/Http/Controllers/ValidacionController.php`  
**Prioridad:** BAJA

Usar route model binding para código más limpio:

**Implementación:**
```php
// En RouteServiceProvider.php
public function boot()
{
    parent::boot();

    Route::bind('hash', function ($value) {
        if (strlen($value) !== 64) {
            abort(404);
        }
        return Tramite::where('hash_validacion', $value)->firstOrFail();
    });
}

// En ValidacionController.php
public function show(Tramite $tramite)
{
    return view('validacion.show', compact('tramite'));
}
```

---

### 5. Prevenir timing attacks en validación

**Ubicación:** `app/Http/Controllers/ValidacionController.php:17`  
**Prioridad:** BAJA

Usar hash_equals para prevenir timing attacks cuando se comparan hashes (aunque en este caso es una búsqueda de base de datos, no una comparación directa).

---

## 📊 Resumen de Prioridades

### URGENTE (Resolver pronto):
1. ❌ Bug: Campo `tipo_contribuyente` inexistente en Observer
2. ⚡ Optimización: Verificar índice de hash en producción
3. ❌ Funcionalidad faltante: Crear tests automatizados

### ALTA:
1. 💡 Mejora: Agregar rate limiting
2. 💡 Mejora: Agregar logging de intentos
3. ❌ Funcionalidad faltante: Notificación al invalidar hash

### MEDIA:
1. ❌ Bug: Validación de longitud de hash
2. ❌ Bug: Validar relaciones en vista
3. ⚡ Optimización: Eager loading
4. 💡 Mejora: Crear endpoint API JSON
5. ❌ Funcionalidad faltante: Regeneración manual de hash

### BAJA:
1. 💡 Mejora: Metadatos SEO/Open Graph
2. ❌ Funcionalidad faltante: Historial de cambios de hash
3. ❌ Funcionalidad faltante: Verificación de firma digital
4. ⚡ Optimización: Implementar caché
5. ⚡ Optimización: Route model binding