# Documentación Técnica del Sistema de Loggin

## 1. Descripción General

El sistema de **Loggin** es un middleware de Laravel (`App\Http\Middleware\Loggin`) responsable de registrar todas las peticiones HTTP que llegan al sistema administrativo. Este middleware proporciona auditoría completa de las acciones realizadas por los usuarios en el panel de administración.

**Archivo principal:** `app/Http/Middleware/Loggin.php`

## 2. Cómo Funciona el Middleware Loggin

### 2.1. Flujo de Ejecución

```php
public function handle(Request $request, Closure $next)
{
    // 1. Excepción para rutas de logs (admin/compass)
    if (!str_contains(request()->url(), 'admin/compass')) {
        try {
            // 2. Verifica si el usuario NO es admin
            if(!Auth::user()->hasRole('admin')) {
                // Registra petición con datos del usuario
            }
        } catch (\Throwable $th) {
            // 3. Fallback: registra sin datos de usuario si hay error
        }
    }
    return $next($request);
}
```

### 2.2. Datos Registrados

El middleware registra los siguientes datos en cada petición:

**Con usuario autenticado:**
- `user_id`: ID del usuario autenticado
- `role`: Nombre del rol del usuario
- `name`: Nombre completo del usuario
- `email`: Email del usuario
- `ip`: Dirección IP del cliente
- `url`: URL completa de la petición
- `method`: Método HTTP (GET, POST, PUT, DELETE, etc.)
- `input`: Datos de entrada (excluyendo campos sensibles)

**Sin usuario autenticado (fallback):**
- `ip`: Dirección IP del cliente
- `url`: URL completa de la petición
- `method`: Método HTTP
- `input`: Datos de entrada

### 2.3. Campos Sensibles Excluidos

El middleware excluye del registro los siguientes campos de entrada:
- `password`
- `_token` (token CSRF)
- `_method` (para method spoofing)

```php
'input' => request()->except(['password', '_token', '_method'])
```

### 2.4. Excepciones de Registro

El middleware NO registra peticiones cuando:
- La URL contiene `'admin/compass'` (herramienta de debugging)
- El usuario tiene rol `'admin'` (para no saturar logs con acciones administrativas)

## 3. Registro del Middleware

### 3.1. Registro en Kernel.php

**Archivo:** `app/Http/Kernel.php`

```php
protected $routeMiddleware = [
    // ... otros middleware
    'loggin' => \App\Http\Middleware\Loggin::class,
    'system' => \App\Http\Middleware\System::class,
];
```

### 3.2. Aplicación en Rutas

**Archivo:** `routes/web.php:77`

El middleware se aplica a todas las rutas del panel de administración:

```php
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    // Todas las rutas del panel de administración
    Voyager::routes();
    Route::get('/', [DashboardController::class, 'index'])->name('voyager.dashboard');
    // ... más rutas
});
```

**Importante:** El middleware `loggin` se ejecuta ANTES que `system`.

## 4. Configuración de Canales de Logging

### 4.1. Canal 'requests'

**Archivo:** `config/logging.php:120-124`

El middleware utiliza un canal dedicado para las peticiones:

```php
'requests' => [
    'driver' => 'single',
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
],
```

### 4.2. Uso del Canal

```php
Log::channel('requests')->info('Petición HTTP al sistema.', $data);
```

### 4.3. Archivos de Log

- **Principal:** `storage/logs/requests.log`
- **Laravel general:** `storage/logs/laravel.log` (usado por System middleware)

## 5. Formato de los Logs

### 5.1. Estructura del Log

```
[timestamp] level.INFO: Mensaje {"data":json}
```

### 5.2. Ejemplo Real

```json
[2025-10-01 00:55:24] local.INFO: Petición HTTP al sistema. {
    "user_id": 2,
    "role": "administrador",
    "name": "Administrador",
    "email": "admin@admin.com",
    "ip": "127.0.0.1",
    "url": "http://127.0.0.1:8000/admin/people/create",
    "method": "GET",
    "input": []
}
```

### 5.3. Ejemplo con Datos de Formulario

```json
[2025-10-01 00:56:13] local.INFO: Petición HTTP al sistema. {
    "user_id": 2,
    "role": "administrador",
    "name": "Administrador",
    "email": "admin@admin.com",
    "ip": "127.0.0.1",
    "url": "http://127.0.0.1:8000/admin/people",
    "method": "POST",
    "input": {
        "person_type": "Natural",
        "tipo_doc": "CI",
        "ci": "Perferendis totam et",
        "ci_complemento": "Possimus perspiciat",
        "first_name": "Clinton",
        "middle_name": "Desiree Trevino",
        "paternal_surname": "Rogers",
        "maternal_surname": "Savage",
        "birth_date": "1975-11-09",
        "gender": "Masculino",
        "email": "gijijahoc@mailinator.com",
        "phone": "+1 (404) 494-4565",
        "address": "Laboris laboriosam",
        "status": "1",
        "estado_persona": "Fallecido"
    }
}
```

## 6. Interacción con Otros Middleware

### 6.1. Middleware System

**Archivo:** `app/Http/Middleware/System.php`

El middleware `System` también registra logs, pero con diferente propósito:

```php
Log::info('System MW', [
    'url'   => $request->fullUrl(),
    'user'  => optional(auth()->user())->only(['id', 'name', 'role_id']),
    'dev'   => setting('system.development'),
    'maint' => setting('configuracion.maintenance'),
]);

Log::info('System MW → PERMITIDO', ['url' => $request->fullUrl()]);
```

**Diferencias clave:**
- `Loggin`: Auditoría de peticiones HTTP (quién hizo qué)
- `System`: Logs de flujo de autenticación y permisos (si se permitió acceso)

### 6.2. Orden de Ejecución

```
1. Middleware 'loggin' (registra entrada)
2. Middleware 'system' (verifica permisos)
3. Controlador (ejecuta lógica)
4. Respuesta
```

## 7. Observadores y Logging

### 7.1. TramiteObserver

**Archivo:** `app/Observers/TramiteObserver.php`

Aunque no usa directamente el middleware Loggin, el `TramiteObserver` proporciona auditoría de eventos de modelo:

```php
public function updated(Tramite $tramite): void
{
    // Recalcula montos
    $this->ejecutarRecalculo($tramite);
    
    // Exporta al SIN si finaliza
    if ($tramite->isDirty('estado') && $tramite->estado === 'Finalizado') {
        dispatch(new ExportarAlSINJob($tramite));
    }
    
    // Limpia caché
    $this->limpiarCache();
}
```

### 7.2. PagoObserver

**Archivo:** `app/Observers/PagoObserver.php`

```php
public function created(Pago $pago): void
{
    $this->clearDashboardCache();
}
```

**Nota:** Los observers no registran logs directamente, pero se complementan con el middleware Loggin para proporcionar auditoría completa.

## 8. Rutas Cubiertas por Loggin

Todas las rutas bajo `/admin/*` están cubiertas, incluyendo:

- Panel de administración (Voyager)
- Gestión de personas (`/admin/people`)
- Gestión de trámites (`/admin/tramites`)
- Gestión de inmuebles (`/admin/inmuebles`)
- Gestión de pagos (`/admin/pagos`)
- Reportes (`/admin/reportes`)
- Dashboard (`/admin/dashboard`)
- Y todas las demás rutas administrativas

## 9. Consideraciones de Seguridad

### 9.1. Datos Sensibles

**Actual:** El middleware excluye `password`, `_token`, y `_method`.

**Riesgo:** Otros datos sensibles como números de tarjeta, información financiera, o datos personales (CI, teléfono) SÍ se registran en los logs.

**Recomendación:** Implementar una lista negra más exhaustiva o sanitizar los datos antes de registrar.

### 9.2. Acceso a Logs

**Ubicación:** `storage/logs/requests.log`

**Consideraciones:**
- Los logs contienen información personal y potencialmente sensible
- Deben tener permisos restringidos en el servidor
- Implementar rotación de logs para no acumular datos antiguos indefinidamente
- Considerar cifrado de logs en entornos de producción

### 9.3. Cumplimiento GDPR/Privacidad

Los logs podrían contener:
- Nombres completos
- Emails
- Números de documento
- Teléfonos
- Direcciones

Esto requiere políticas de retención de datos y borrado seguro.

## 10. Casos de Uso

### 10.1. Auditoría de Acciones

Los logs permiten rastrear quién realizó qué acción:

```bash
# Buscar todas las acciones de un usuario específico
grep "user_id":2 storage/logs/requests.log

# Buscar modificaciones a un recurso específico
grep "\/admin\/people\/5" storage/logs/requests.log

# Buscar peticiones DELETE
grep "\"method\":\"DELETE\"" storage/logs/requests.log
```

### 10.2. Debugging

Los logs ayudan a identificar problemas de flujo:

```bash
# Ver las últimas 20 peticiones
tail -n 20 storage/logs/requests.log

# Ver peticiones fallidas (por IP o URL)
grep "127.0.0.1" storage/logs/requests.log | grep "POST"
```

### 10.3. Análisis de Uso

Es posible analizar patrones de uso:

```bash
# Contar peticiones por usuario
grep -o '"user_id":[0-9]*' storage/logs/requests.log | sort | uniq -c

# Ver los endpoints más visitados
grep -o '"url":"[^"]*"' storage/logs/requests.log | sort | uniq -c | sort -rn
```

## 11. Ejemplos de Implementación

### 11.1. Agregar Loggin a una Ruta Específica

```php
Route::post('/api/endpoint', [Controller::class, 'action'])
    ->middleware('loggin');
```

### 11.2. Excluir Ruta de Loggin

Las rutas que no estén en el grupo `admin` o que cumplan la excepción `admin/compass` no se registran:

```php
// Esta ruta NO se registra
Route::get('/public-page', [Controller::class, 'action']);

// Esta ruta SÍ se registra
Route::prefix('admin')->middleware(['loggin', 'system'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index']);
});
```

### 11.3. Registrar Manualmente un Log

En cualquier controlador o servicio:

```php
use Illuminate\Support\Facades\Log;

Log::channel('requests')->info('Evento personalizado', [
    'user_id' => auth()->id(),
    'accion' => 'Exportar datos',
    'parametros' => $request->all()
]);
```

## 12. Mejoras Sugeridas

### 12.1. Sanitización de Datos Sensibles

**Problema actual:** Solo se excluyen 3 campos.

**Solución propuesta:**

```php
public function handle(Request $request, Closure $next)
{
    // Lista negra de campos sensibles
    $sensitiveFields = [
        'password', '_token', '_method',
        'credit_card', 'cvv', 'expiry',
        'bank_account', 'routing_number',
        'ssn', 'social_security',
        // Agregar más según necesidad
    ];

    $sanitizedInput = $request->except($sensitiveFields);

    // Opcional: ofuscar datos parciales
    foreach ($sanitizedInput as $key => $value) {
        if (in_array($key, ['ci', 'phone', 'email'])) {
            $sanitizedInput[$key] = $this->maskSensitiveData($value);
        }
    }

    // Resto del código...
}

private function maskSensitiveData($value): string
{
    if (is_string($value) && strlen($value) > 4) {
        return substr($value, 0, 2) . '***' . substr($value, -2);
    }
    return '***';
}
```

### 12.2. Rotación de Logs

**Problema actual:** El archivo `requests.log` crece indefinidamente.

**Solución propuesta en `config/logging.php`:**

```php
'requests' => [
    'driver' => 'daily',  // Cambiar de 'single' a 'daily'
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,  // Mantener logs por 30 días
],
```

### 12.3. Agregar Metadata Adicional

Mejorar el contexto del log:

```php
$data = [
    'user_id' => Auth::user()->id,
    'role' => Auth::user()->role->name,
    'name' => Auth::user()->name,
    'email' => Auth::user()->email,
    'ip' => request()->ip(),
    'url' => request()->url(),
    'method' => request()->method(),
    'input' => request()->except(['password', '_token', '_method']),
    
    // Nueva metadata
    'user_agent' => request()->userAgent(),
    'referer' => request()->header('referer'),
    'timestamp' => now()->toIso8601String(),
];
```

### 12.4. Implementar Levels de Log

Usar diferentes niveles según la acción:

```php
if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
    Log::channel('requests')->warning('Acción de modificación', $data);
} else {
    Log::channel('requests')->info('Petición de consulta', $data);
}
```

### 12.5. Logging Asíncrono

Para no afectar el rendimiento, usar logging asíncrono con colas:

```php
use Illuminate\Support\Facades\Log;
use App\Jobs\LogHttpRequestJob;

// En el middleware
dispatch(new LogHttpRequestJob($data));

// En App/Jobs/LogHttpRequestJob.php
public function handle()
{
    Log::channel('requests')->info('Petición HTTP al sistema.', $this->data);
}
```

### 12.6. Agregar ID de Sesión

Para correlacionar múltiples peticiones de la misma sesión:

```php
$data = [
    // ... otros datos
    'session_id' => session()->getId(),
];
```

## 13. Troubleshooting

### 13.1. Los Logs No Se Generan

**Causas posibles:**
1. El middleware no está aplicado a la ruta
2. El usuario tiene rol 'admin'
3. La URL contiene 'admin/compass'
4. Permisos de escritura en `storage/logs/`

**Solución:**
```bash
# Verificar permisos
ls -la storage/logs/

# Dar permisos de escritura
chmod -R 775 storage/logs/
```

### 13.2. Logs Vacíos o Incompletos

**Verificar:**
```bash
# Ver si el archivo tiene contenido
cat storage/logs/requests.log

# Ver si el canal está configurado correctamente
php artisan tinker
>>> config('logging.channels.requests')
```

### 13.3. Rendimiento Afectado

Si el middleware impacta el rendimiento:
1. Implementar logging asíncrono (ver sección 12.5)
2. Reducir la cantidad de datos registrados
3. Usar rotación de logs con `daily` en lugar de `single`
4. Considerar usar un canal diferente para peticiones de alta frecuencia

## 14. Herramientas de Análisis

### 14.1. Ver Logs en Tiempo Real

```bash
tail -f storage/logs/requests.log
```

### 14.2. Buscar por Fecha

```bash
grep "2025-10-01" storage/logs/requests.log
```

### 14.3. Extracción de Eventos Específicos

```bash
# Extrar todos los POST
grep '"method":"POST"' storage/logs/requests.log > posts.log

# Extraer por usuario
grep '"user_id":2' storage/logs/requests.log > user_2.log
```

### 14.4. Integración con ELK Stack

Para análisis avanzado, considerar integrar con ELK (Elasticsearch, Logstash, Kibana):

```php
// config/logging.php
'elasticsearch' => [
    'driver' => 'monolog',
    'handler' => Monolog\Handler\ElasticSearchHandler::class,
    'handler_with' => [
        'elastic' => [
            'hosts' => [
                ['host' => 'localhost', 'port' => 9200],
            ],
        ],
        'index' => 'laravel_logs',
    ],
],
```

## 15. Referencias

- **Middleware principal:** `app/Http/Middleware/Loggin.php`
- **Middleware System:** `app/Http/Middleware/System.php`
- **Configuración de logging:** `config/logging.php`
- **Registro de middleware:** `app/Http/Kernel.php:69`
- **Rutas protegidas:** `routes/web.php:77`
- **Archivo de logs:** `storage/logs/requests.log`
- **Documentación Laravel Logging:** https://laravel.com/docs/logging
- **Documentación Laravel Middleware:** https://laravel.com/docs/middleware

---

## 16. ANÁLISIS DETALLADO: Bugs, Faltas, Optimizaciones y Mejoras

### 16.1. BUGS CRÍTICOS IDENTIFICADOS

#### 🔴 BUG #1: Sanitización Superficial de Datos Sensibles
**Ubicación:** `app/Http/Middleware/Loggin.php:37,46`

**Problema:**
```php
'input' => request()->except(['password', '_token', '_method'])
```

El uso de `request()->except()` solo excluye campos en el nivel raíz. Si hay datos anidados con información sensible, estos no se excluyen.

**Ejemplo vulnerable:**
```json
{
    "user": {
        "name": "Juan",
        "password": "secret123"  // ❌ NO se excluye porque está anidado
    },
    "payment": {
        "credit_card": "4111-1111-1111-1111",  // ❌ NO se excluye
        "cvv": "123"
    }
}
```

**Impacto:** Datos sensibles (contraseñas, tarjetas, tokens financieros) pueden aparecer en `storage/logs/requests.log`.

**Solución:**
```php
private function sanitizeInput(array $input): array
{
    $sensitiveKeys = ['password', '_token', '_method', 'credit_card', 'cvv', 
                      'expiry', 'bank_account', 'routing_number', 'ssn'];
    
    return $this->recursiveSanitize($input, $sensitiveKeys);
}

private function recursiveSanitize($data, $sensitiveKeys): mixed
{
    if (!is_array($data)) {
        return $data;
    }
    
    foreach ($data as $key => $value) {
        if (in_array($key, $sensitiveKeys)) {
            $data[$key] = '***REDACTED***';
        } elseif (is_array($value)) {
            $data[$key] = $this->recursiveSanitize($value, $sensitiveKeys);
        }
    }
    
    return $data;
}
```

---

#### 🔴 BUG #2: Filtro de URL 'admin/compass' Demasiado Amplio
**Ubicación:** `app/Http/Middleware/Loggin.php:25`

**Problema:**
```php
if (!str_contains(request()->url(), 'admin/compass') ) {
```

Usa `str_contains()` que es demasiado permisivo. Cualquier URL que contenga "admin/compass" en cualquier parte no se registrará, incluyendo URLs legítimas como:
- `/admin/some-compass-page`
- `/admin/compass-related`

**Impacto:** Pérdida de logs para rutas administrativas legítimas.

**Solución:**
```php
// Opción 1: Match exacto de ruta
if (request()->path() === 'admin/compass') {
    return $next($request);
}

// Opción 2: Expresión regular más precisa
if (preg_match('#^/admin/compass(/.*)?$#', request()->path())) {
    return $next($request);
}

// Opción 3: Usar el método is() de Laravel
if ($request->is('admin/compass*')) {
    return $next($request);
}
```

---

#### 🔴 BUG #3: Posible NullPointerException en hasRole()
**Ubicación:** `app/Http/Middleware/Loggin.php:27`

**Problema:**
```php
if(!Auth::user()->hasRole('admin'))
```

El código asume que `Auth::user()->role` existe y tiene el método `name`. Si el usuario no tiene rol asignado o si la relación `role` no está cargada, puede causar error.

**Evidencia en User.php:**
```php
// app/Models/User.php:15
class User extends \TCG\Voyager\Models\User
{
    // No se carga explícitamente la relación 'role'
}
```

**Impacto:** El middleware fallará silenciosamente en el bloque catch y registrará sin datos de usuario, ocultando el error real.

**Solución:**
```php
if (Auth::check()) {
    $user = Auth::user();
    
    // Verificar que el rol exista
    if ($user->role_id && $user->role) {
        if (!$user->hasRole('admin')) {
            // Registrar con datos de usuario
            $data = [
                'user_id' => $user->id,
                'role' => optional($user->role)->name,
                'name' => $user->name,
                'email' => $user->email,
                // ...
            ];
        }
    } else {
        // Usuario sin rol asignado
        Log::channel('requests')->warning('Usuario sin rol asignado', [
            'user_id' => $user->id,
            'email' => $user->email,
            'url' => $request->url()
        ]);
    }
}
```

---

#### 🔴 BUG #4: Datos Personales No Sanitizados en Logs
**Ubicación:** `app/Http/Middleware/Loggin.php:30-33`

**Problema:**
```php
'user_id' => Auth::user()->id,
'role' => Auth::user()->role->name,
'name' => Auth::user()->name,
'email' => Auth::user()->email,
```

Se registran datos personales (nombre, email) sin sanitización ni ofuscación. Esto es una violación potencial de GDPR y leyes de protección de datos.

**Evidencia en logs reales:**
```json
{
    "name": "Administrador",
    "email": "admin@admin.com"
}
```

**Impacto:** 
- Riesgo de privacidad si los logs son accedidos por personal no autorizado
- Incumplimiento de regulaciones de protección de datos

**Solución:**
```php
// Opción 1: Ofuscar datos personales
$data = [
    'user_id' => $user->id,
    'role' => $user->role->name,
    'name' => $this->maskName($user->name),
    'email' => $this->maskEmail($user->email),
    // ...
];

private function maskName(string $name): string
{
    $parts = explode(' ', $name);
    if (count($parts) > 1) {
        return $parts[0] . ' ' . str_repeat('*', 3);
    }
    return substr($name, 0, 2) . '***';
}

private function maskEmail(string $email): string
{
    $parts = explode('@', $email);
    if (count($parts) === 2) {
        $name = substr($parts[0], 0, 2) . '***';
        return $name . '@' . $parts[1];
    }
    return '***@***.com';
}

// Opción 2: No registrar datos personales, solo IDs
$data = [
    'user_id' => $user->id,
    'role_id' => $user->role_id,
    // No incluir nombre ni email
];
```

---

#### 🟠 BUG #5: Archivo de Log Crece Indefinidamente (Riesgo de Llenado de Disco)
**Ubicación:** `config/logging.php:120-124` y `storage/logs/requests.log`

**Problema actual:**
```php
'requests' => [
    'driver' => 'single',  // ❌ Archivo único, crece indefinidamente
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
],
```

**Evidencia:**
```bash
$ ls -lh storage/logs/requests.log
-rw-r--r-- 1 user 197121 485K Jan 16 18:15 storage/logs/requests.log
$ wc -l storage/logs/requests.log
2021 storage/logs/requests.log
```

El archivo ya tiene 485KB con 2021 líneas. En producción con miles de usuarios, puede llegar a gigabytes en semanas.

**Impacto:**
- Llenado del disco del servidor
- Dificultad para analizar logs grandes
- Lentitud en operaciones de lectura/escritura
- Riesgo de pérdida de logs por límite de disco

**Solución:**
```php
// config/logging.php:120
'requests' => [
    'driver' => 'daily',        // ✅ Crear un archivo por día
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,             // ✅ Mantener logs por 30 días
    'tap'    => [App\Logging\CustomizeFormatter::class],  // Formato JSON para análisis
],
```

---

### 16.2. FALTAS Y FUNCIONALIDADES NO IMPLEMENTADAS

#### ❌ FALTA #1: Sin Logging de Errores y Excepciones
**Ubicación:** `app/Http/Middleware/Loggin.php`

**Problema:** El middleware solo registra peticiones exitosas (`info` level). No hay logging de:
- Peticiones fallidas (4xx, 5xx)
- Excepciones durante el procesamiento
- Tiempos de respuesta lentos

**Evidencia en otros archivos:**
Varios controladores tienen logs de errores comentados:
- `app/Http/Controllers/UfvController.php:175`
- `app/Http/Controllers/ParentescoController.php:98`
- `app/Http/Controllers/ExencionController.php:100`

**Solución:**
```php
public function handle(Request $request, Closure $next)
{
    $startTime = microtime(true);
    $response = $next($request);
    
    // Calcular tiempo de respuesta
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    $logData = array_merge($this->getLogData($request), [
        'status_code' => $response->getStatusCode(),
        'duration_ms' => $duration,
    ]);
    
    // Logging basado en código de estado
    if ($response->getStatusCode() >= 500) {
        Log::channel('requests')->error('Server Error', $logData);
    } elseif ($response->getStatusCode() >= 400) {
        Log::channel('requests')->warning('Client Error', $logData);
    } elseif ($duration > 1000) {
        Log::channel('requests')->warning('Slow Request', $logData);
    } else {
        Log::channel('requests')->info('Petición HTTP al sistema', $logData);
    }
    
    return $response;
}
```

---

#### ❌ FALTA #2: Sin Correlación de Peticiones (Request ID)
**Ubicación:** `app/Http/Middleware/Loggin.php`

**Problema:** Cada petición se registra individualmente sin un identificador único. Es imposible:
- Seguir el flujo de una petición a través de múltiples servicios
- Correlacionar logs del middleware con logs de controladores
- Trazar una transacción completa

**Solución:**
```php
public function handle(Request $request, Closure $next)
{
    // Generar o recuperar Request ID
    $requestId = $request->header('X-Request-ID') ?? (string) Str::uuid();
    $request->headers->set('X-Request-ID', $requestId);
    
    $data = array_merge($this->getLogData($request), [
        'request_id' => $requestId,
        // ... otros datos
    ]);
    
    // Disponible en controladores: $request->header('X-Request-ID')
    return $next($request);
}
```

---

#### ❌ FALTA #3: Sin Logging de Operaciones de Base de Datos
**Ubicación:** No implementado

**Problema:** No hay registro de queries SQL ejecutados, lo cual es crucial para:
- Debugging de problemas de rendimiento
- Auditoría de consultas sensibles
- Identificar N+1 queries

**Solución:**
```php
// app/Providers/EventServiceProvider.php
protected $listen = [
    'Illuminate\Database\Events\QueryExecuted' => [
        'App\Listeners\LogQueryExecuted',
    ],
];

// app/Listeners/LogQueryExecuted.php
public function handle(QueryExecuted $event)
{
    if (config('logging.log_queries')) {
        Log::channel('queries')->info('SQL Query', [
            'sql' => $event->sql,
            'bindings' => $event->bindings,
            'time' => $event->time,
            'connection' => $event->connectionName,
        ]);
    }
}
```

**Configuración:**
```php
// config/logging.php
'queries' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/queries.log'),
    'level'  => 'debug',
    'days'   => 7,
],
```

---

#### ❌ FALTA #4: Sin Logging de Exportación al SIN (Sistema de Impuestos Nacionales)
**Ubicación:** `app/Jobs/ExportarAlSINJob.php:43,56`

**Evidencia:**
```php
// Línea 43
Log::info("Iniciando exportación a SIN para trámite: {$this->tramite->nro_tramite}");

// Línea 56
Log::info("Archivo CSV generado: " . $filePath);

// Línea 119: Warning sin contexto
Log::warning("Exportación a SIN: Credenciales FTP no configuradas. El archivo no será enviado.");
```

**Problema:** No hay logging de:
- Éxito/fallo de la subida FTP
- Respuestas del servidor SIN
- Reintentos
- Detalles de errores

**Solución:**
```php
// app/Jobs/ExportarAlSINJob.php
public function handle(): void
{
    $requestId = (string) Str::uuid();
    
    Log::channel('sin')->info('Inicio exportación SIN', [
        'request_id' => $requestId,
        'tramite' => $this->tramite->nro_tramite,
        'user_id' => $this->tramite->user_id,
    ]);
    
    try {
        // ... generación del CSV ...
        
        Log::channel('sin')->info('CSV generado', [
            'request_id' => $requestId,
            'archivo_path' => $filePath,
            'file_size' => filesize($filePath),
        ]);
        
        // ... subida FTP ...
        
        Log::channel('sin')->info('Exportación exitosa', [
            'request_id' => $requestId,
            'ftp_path' => $ftpPath,
        ]);
        
    } catch (\Exception $e) {
        Log::channel('sin')->error('Error en exportación', [
            'request_id' => $requestId,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    }
}
```

---

#### ❌ FALTA #5: Sin Panel de Visualización de Logs
**Ubicación:** No implementado

**Problema:** Los logs solo son accesibles por línea de comandos. No hay:
- Interfaz web para consultar logs
- Filtros por usuario, fecha, tipo de acción
- Búsqueda avanzada
- Exportación de logs

**Solución:** Implementar un controlador para ver logs:
```php
// app/Http/Controllers/LogViewerController.php
class LogViewerController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('view logs');
        
        $logs = LogFile::parse('requests.log')
            ->filter([
                'user_id' => $request->user_id,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
                'method' => $request->method,
            ])
            ->paginate(50);
        
        return view('admin.logs.index', compact('logs'));
    }
}
```

---

#### ❌ FALTA #6: Sin Alertas Automáticas para Errores
**Ubicación:** No implementado

**Problema:** Cuando ocurren errores (5xx, excepciones críticas), no hay notificación automática.

**Solución:**
```php
// app/Logging/AlertChannel.php
class AlertChannel
{
    public function __invoke($level, $message, $context)
    {
        if ($level === 'error' || $level === 'critical') {
            // Enviar email
            Mail::to(config('logging.alert_email'))
                ->send(new ErrorAlert($message, $context));
            
            // Enviar a Slack
            Log::channel('slack')->error($message, $context);
        }
    }
}

// config/logging.php
'channels' => [
    'stack' => [
        'driver' => 'stack',
        'channels' => ['single', 'alerts'],
        'ignore_exceptions' => false,
    ],
    'alerts' => [
        'driver' => 'custom',
        'via' => App\Logging\AlertChannel::class,
    ],
],
```

---

### 16.3. OPTIMIZACIONES NECESARIAS

#### ⚡ OPTIMIZACIÓN #1: Logging Asíncrono con Colas
**Ubicación:** `app/Http/Middleware/Loggin.php:39,48`

**Problema actual:**
```php
Log::channel('requests')->info('Petición HTTP al sistema.', $data);
```

El logging es síncrono, lo que bloquea el proceso de la petición y afecta el rendimiento.

**Impacto:**
- Latencia adicional en cada petición (~2-5ms por log)
- Contención de I/O en disco
- Escalabilidad limitada

**Solución:**
```php
// Crear Job
// app/Jobs/LogHttpRequestJob.php
class LogHttpRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public array $data;
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }
    
    public function handle(): void
    {
        Log::channel('requests')->info('Petición HTTP al sistema.', $this->data);
    }
}

// Modificar middleware
public function handle(Request $request, Closure $next)
{
    $response = $next($request);
    
    // Enviar a cola en lugar de logging síncrono
    $data = $this->getLogData($request, $response);
    LogHttpRequestJob::dispatch($data)->onQueue('logs');
    
    return $response;
}
```

**Configuración de cola:**
```php
// config/queue.php
'connections' => [
    'redis' => [
        'queue' => ['default', 'logs'],  // Cola dedicada para logs
    ],
],
```

---

#### ⚡ OPTIMIZACIÓN #2: Reducir Cantidad de Datos Registrados
**Ubicación:** `app/Http/Middleware/Loggin.php:29-37`

**Problema:** Se registran demasiados datos innecesarios en cada petición.

**Datos innecesarios:**
- `input` completo en peticiones GET (generalmente vacío o solo parámetros de filtrado)
- `name` y `email` (ver Bug #4)
- Datos de sesión

**Solución:**
```php
private function getLogData(Request $request, $response): array
{
    $data = [
        'user_id' => optional(auth()->user())->id,
        'ip' => $request->ip(),
        'url' => $request->url(),
        'method' => $request->method(),
        'status' => $response->getStatusCode(),
        'duration' => $this->getDuration($request),
    ];
    
    // Solo incluir input en métodos que modifican datos
    if (in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
        $data['input'] = $this->sanitizeInput($request->all());
    }
    
    return $data;
}
```

---

#### ⚡ OPTIMIZACIÓN #3: Buffer de Logs en Memoria
**Ubicación:** `config/logging.php`

**Problema:** Cada log escribe directamente a disco, causando muchos I/O.

**Solución:**
```php
// config/logging.php
'requests' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,
    'buffer' => true,  // ✅ Buffer en memoria antes de escribir
    'buffer_size' => 100,  // Escribir cada 100 logs
],
```

---

#### ⚡ OPTIMIZACIÓN #4: Compresión de Logs Antiguos
**Ubicación:** `config/logging.php`

**Solución:**
```php
'requests' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,
    'compress' => true,  // ✅ Comprimir logs de días anteriores con gzip
],
```

---

#### ⚡ OPTIMIZACIÓN #5: Logging Condicional por Entorno
**Ubicación:** `app/Http/Middleware/Loggin.php`

**Problema:** En producción, el mismo nivel de logging puede ser excesivo.

**Solución:**
```php
public function handle(Request $request, Closure $next)
{
    $response = $next($request);
    
    // Solo registrar logs detallados en desarrollo o staging
    if (!app()->environment('production')) {
        $this->logRequest($request, $response);
    } else {
        // En producción, solo registrar errores o eventos importantes
        if ($response->getStatusCode() >= 400 || $this->shouldLogInProduction($request)) {
            $this->logRequest($request, $response);
        }
    }
    
    return $response;
}

private function shouldLogInProduction(Request $request): bool
{
    // En producción, registrar: POST, PUT, DELETE, DELETE
    return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
}
```

---

#### ⚡ OPTIMIZACIÓN #6: Usar Formato JSON Structured Logging
**Ubicación:** `config/logging.php`

**Problema:** El formato actual es texto plano, difícil de analizar automáticamente.

**Evidencia del formato actual:**
```
[2025-10-01 00:55:24] local.INFO: Petición HTTP al sistema. {"user_id":2,"role":"administrador"...}
```

**Solución:**
```php
// app/Logging/CustomizeFormatter.php
class CustomizeFormatter
{
    public function __invoke($logger)
    {
        foreach ($logger->getHandlers() as $handler) {
            $handler->setFormatter(new JsonFormatter());
        }
    }
}

// config/logging.php
'requests' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,
    'tap'    => [App\Logging\CustomizeFormatter::class],  // Formato JSON puro
],
```

**Resultado:**
```json
{
    "timestamp": "2025-10-01T00:55:24.000Z",
    "level": "info",
    "message": "Petición HTTP al sistema",
    "context": {
        "user_id": 2,
        "role": "administrador",
        "url": "http://127.0.0.1:8000/admin/people/create"
    }
}
```

---

### 16.4. FALTA DE BUENAS PRÁCTICAS

#### 📚 FALTA DE DOCUMENTACIÓN

**Problema:** No hay documentación interna sobre:
- Qué información debe y no debe registrarse
- Política de retención de logs
- Procedimientos para analizar logs en caso de incidentes

**Solución:** Crear `docs/operations/logging-policy.md`

```markdown
# Política de Logging del Sistema

## Información que DEBE registrarse
- ID de usuario
- Acciones de modificación (POST, PUT, DELETE, PATCH)
- Errores y excepciones
- Tiempo de respuesta

## Información que NO DEBE registrarse
- Contraseñas
- Tokens de autenticación
- Números de tarjeta de crédito
- Datos financieros completos
- Datos personales en texto plano

## Política de retención
- Logs de requests: 30 días
- Logs de errores: 90 días
- Logs de auditoría: 365 días
```

---

#### 📚 FALTA DE PRUEBAS UNITARIAS

**Ubicación:** No existe `tests/Middleware/LogginTest.php`

**Pruebas necesarias:**
```php
// tests/Middleware/LogginTest.php
class LogginTest extends TestCase
{
    public function test_it_logs_requests_for_non_admin_users()
    {
        $user = User::factory()->create(['role_id' => 2]); // No admin
        
        $this->actingAs($user)
            ->post('/admin/people', ['name' => 'Test'])
            ->assertStatus(200);
            
        $this->assertLogged('info', function ($message, $context) {
            return $context['user_id'] === $user->id;
        });
    }
    
    public function test_it_does_not_log_admin_requests()
    {
        $admin = User::factory()->admin()->create();
        
        $this->actingAs($admin)
            ->get('/admin/dashboard');
            
        // No debería haber logs de este usuario
        Log::shouldReceive('info')->never();
    }
    
    public function test_it_sanitizes_sensitive_data()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->post('/admin/users', [
                'name' => 'Test',
                'password' => 'secret123',
                'credit_card' => '4111111111111111'
            ]);
            
        $this->assertLogged('info', function ($message, $context) {
            return !isset($context['input']['password']) 
                && !isset($context['input']['credit_card']);
        });
    }
}
```

---

#### 📚 FALTA DE MONITOREO DE SISTEMA DE LOGGING

**Problema:** No hay monitoreo de:
- Tamaño de archivos de log
- Errores de escritura en logs
- Espacio disponible en disco
- Latencia de escritura de logs

**Solución:**
```php
// app/Console/Commands/CheckLogsHealth.php
class CheckLogsHealth extends Command
{
    protected $signature = 'logs:check-health';
    
    public function handle()
    {
        $logFile = storage_path('logs/requests.log');
        $sizeInMB = filesize($logFile) / 1024 / 1024;
        
        if ($sizeInMB > 500) {
            Log::critical('Log file too large', [
                'file' => $logFile,
                'size_mb' => $sizeInMB
            ]);
            // Alerta por email
        }
        
        $diskFree = disk_free_space(storage_path());
        if ($diskFree < 1024 * 1024 * 1024) { // Menos de 1GB
            Log::critical('Low disk space for logs', [
                'free_mb' => $diskFree / 1024 / 1024
            ]);
        }
    }
}

// Agregar a schedule
// app/Console/Kernel.php
protected function schedule(Schedule $schedule)
{
    $schedule->command('logs:check-health')->daily();
}
```

---

### 16.5. FALTA DE SEGURIDAD

#### 🔒 FALTA DE CIFRADO DE LOGS

**Problema:** Los logs se almacenan en texto plano sin cifrado. Si alguien obtiene acceso al servidor, puede leer toda la información sensible.

**Solución:**
```php
// config/logging.php
'requests' => [
    'driver' => 'single',
    'path'   => storage_path('logs/requests.log.gpg'),  // Cifrado con GPG
    'level'  => 'info',
    'formatter' => Monolog\Formatter\LineFormatter::class,
    'encrypt_with' => [
        'type' => 'gpg',
        'recipient' => env('LOG_GPG_KEY_ID'),
    ],
],
```

---

#### 🔒 FALTA DE AUTENTICACIÓN PARA ACCESO A LOGS

**Problema:** Cualquiera con acceso al servidor o a través de Voyager puede ver los logs.

**Solución:**
```php
// app/Http/Middleware/CanAccessLogs.php
class CanAccessLogs
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->user() || !auth()->user()->can('access_logs')) {
            abort(403, 'No tienes permiso para acceder a los logs');
        }
        
        return $next($request);
    }
}

// Aplicar a rutas de logs
Route::prefix('admin/logs')
    ->middleware(['auth', 'can:access_logs'])
    ->group(function () {
        Route::get('/', [LogViewerController::class, 'index']);
    });
```

---

#### 🔒 FALTA DE AUDITORÍA DE ACCESO A LOGS

**Problema:** No hay registro de quién accede a los logs y qué ve.

**Solución:**
```php
// app/Http/Middleware/LogLogAccess.php
class LogLogAccess
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        Log::channel('log_access')->info('Log access', [
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'endpoint' => $request->path(),
            'params' => $request->all(),
        ]);
        
        return $response;
    }
}
```

---

### 16.6. FALTA DE INTEGRACIÓN CON HERRAMIENTAS EXTERNAS

#### 🔗 FALTA DE INTEGRACIÓN CON SISTEMAS DE MONITOREO

**Herramientas a considerar:**
- **Sentry:** Para tracking de errores en tiempo real
- **Datadog:** Para análisis de logs y métricas
- **Loggly:** Para centralización de logs
- **Papertrail:** Para streaming de logs

**Ejemplo con Sentry:**
```php
// config/logging.php
'sentry' => [
    'driver' => 'monolog',
    'handler' => Sentry\Monolog\Handler::class,
    'level' => 'warning',
    'bubble' => true,
],

// Agregar al stack
'stack' => [
    'driver' => 'stack',
    'channels' => ['single', 'sentry'],
    'ignore_exceptions' => false,
],
```

---

#### 🔗 FALTA DE DASHBOARD DE MÉTRICAS

**Métricas que faltan:**
- Peticiones por minuto/hora/día
- Top usuarios más activos
- Endpoints más lentos
- Tasa de errores (4xx, 5xx)
- Tiempos de respuesta promedio

**Solución:**
```php
// app/Services/LogAnalyticsService.php
class LogAnalyticsService
{
    public function getMetrics(Carbon $from, Carbon $to): array
    {
        return [
            'total_requests' => $this->countRequests($from, $to),
            'avg_response_time' => $this->getAvgResponseTime($from, $to),
            'error_rate' => $this->getErrorRate($from, $to),
            'top_users' => $this->getTopUsers($from, $to, 10),
            'top_endpoints' => $this->getTopEndpoints($from, $to, 10),
            'slow_requests' => $this->getSlowRequests($from, $to, 1000),
        ];
    }
}
```

---

### 16.7. RESUMEN DE ARCHIVOS A MODIFICAR

| Archivo | Tipo de Cambio | Prioridad |
|---------|---------------|-----------|
| `app/Http/Middleware/Loggin.php` | Corregir bugs críticos #1, #2, #3, #4 | 🔴 Alta |
| `config/logging.php` | Implementar rotación de logs (Bug #5) | 🔴 Alta |
| `app/Jobs/LogHttpRequestJob.php` | Crear job para logging asíncrono | 🟠 Media |
| `config/logging.php` | Agregar canal 'queries' | 🟠 Media |
| `config/logging.php` | Agregar canal 'sin' para exportaciones | 🟠 Media |
| `app/Console/Commands/CheckLogsHealth.php` | Crear comando de monitoreo | 🟠 Media |
| `app/Logging/CustomizeFormatter.php` | Formato JSON estructurado | 🟠 Media |
| `app/Http/Controllers/LogViewerController.php` | Panel de visualización | 🟢 Baja |
| `tests/Middleware/LogginTest.php` | Pruebas unitarias | 🟢 Baja |
| `docs/operations/logging-policy.md` | Política de logging | 🟢 Baja |

---

### 16.8. ROADMAP DE IMPLEMENTACIÓN

#### Fase 1: Corrección de Bugs Críticos (Semana 1-2)
- [ ] Implementar sanitización recursiva de datos sensibles
- [ ] Corregir filtro de URL 'admin/compass'
- [ ] Manejar NullPointerException en hasRole()
- [ ] Ofuscar datos personales
- [ ] Implementar rotación de logs

#### Fase 2: Optimizaciones (Semana 3-4)
- [ ] Implementar logging asíncrono con colas
- [ ] Reducir cantidad de datos registrados
- [ ] Implementar buffer de logs en memoria
- [ ] Configurar compresión de logs
- [ ] Formato JSON structured logging

#### Fase 3: Faltas y Mejoras (Semana 5-6)
- [ ] Logging de errores y excepciones
- [ ] Implementar Request ID para correlación
- [ ] Logging de queries de BD
- [ ] Mejorar logging de exportación al SIN
- [ ] Alertas automáticas para errores

#### Fase 4: Funcionalidades Avanzadas (Semana 7-8)
- [ ] Panel de visualización de logs
- [ ] Dashboard de métricas
- [ ] Integración con Sentry/Datadog
- [ ] Pruebas unitarias
- [ ] Documentación completa
