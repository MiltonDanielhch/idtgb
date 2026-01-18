# Bugs del Sistema ITGB - Logging Middleware

**Fuente Principal:** `docs/dev/Loggin.md` (líneas 586-1115)

## 🐛 Bugs Críticos

### 1. Sanitización Superficial de Datos Sensibles
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:37,46`  
**Documentado en:** `docs/dev/Loggin.md:590-643`

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

**Coincidencia en:** Este bug también está relacionado con **System.md** (bug #3 de Master Blade) porque ambos problemas son sobre validación y sanitización de datos en vistas.

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

### 2. Filtro de URL 'admin/compass' Demasiado Amplio
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:25`  
**Documentado en:** `docs/dev/Loggin.md:646-677`

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

### 3. Posible NullPointerException en hasRole()
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:27`  
**Documentado en:** `docs/dev/Loggin.md:680-728`

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

### 4. Datos Personales No Sanitizados en Logs
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:30-33`  
**Documentado en:** `docs/dev/Loggin.md:731-793`

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

### 5. Archivo de Log Crece Indefinidamente (Riesgo de Llenado de Disco)
**Ubicación del bug:** `config/logging.php:120-124` y `storage/logs/requests.log`  
**Documentado en:** `docs/dev/Loggin.md:796-835`

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

**Coincidencia en:** Este problema también está documentado en **System.md** (bug #6) como el mismo problema de logs creciendo indefinidamente.

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

## 🔍 Problemas Menores

### 6. Sin Validación de Datos de Usuario
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:27-33`  
**Documentado en:** `docs/dev/Loggin.md:680-728`

**Problema:** No se valida que el usuario tenga datos válidos antes de registrarlos.

**Solución:**
```php
if (Auth::check()) {
    $user = Auth::user();
    
    // Validar datos mínimos
    $data = [
        'user_id' => $user->id ?? null,
        'role' => optional($user->role)->name ?? 'sin_rol',
        'email' => $user->email ?? 'no_email',
        // ...
    ];
}
```

---

### 7. Excepciones Generales Silenciadas
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:23-25`  
**Documentado en:** `docs/dev/Loggin.md:680-728`

**Problema:**
```php
catch (\Throwable $th) {
    // 3. Fallback: registra sin datos de usuario si hay error
}
```

El bloque catch no registra el error, solo ejecuta un fallback. Esto oculta errores reales en la lógica de logging.

**Solución:**
```php
catch (\Throwable $th) {
    Log::channel('requests')->error('Error al registrar log', [
        'error' => $th->getMessage(),
        'trace' => $th->getTraceAsString(),
        'url' => $request->url(),
    ]);
    
    // Fallback sin datos de usuario
    $data = $this->getFallbackData($request);
}
```

---

### 8. No Se Distinguen Niveles de Log
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:39,48`  
**Documentado en:** `docs/dev/Loggin.md:530-540`

**Problema:** Todos los logs se registran con el mismo nivel `info`, sin distinguir entre consultas normales, errores o advertencias.

**Solución:**
```php
if (in_array($request->method(), ['POST', 'PUT', 'DELETE'])) {
    Log::channel('requests')->warning('Acción de modificación', $data);
} else {
    Log::channel('requests')->info('Petición de consulta', $data);
}
```

---

## ❌ Faltas y Funcionalidades No Implementadas

### 9. Sin Logging de Errores y Excepciones
**Ubicación:** `app/Http/Middleware/Loggin.php`  
**Documentado en:** `docs/dev/Loggin.md:840-883`

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

### 10. Sin Correlación de Peticiones (Request ID)
**Ubicación:** `app/Http/Middleware/Loggin.php`  
**Documentado en:** `docs/dev/Loggin.md:886-911`

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

### 11. Sin Logging de Operaciones de Base de Datos
**Ubicación:** No implementado  
**Documentado en:** `docs/dev/Loggin.md:914-955`

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

### 12. Sin Logging de Exportación al SIN (Sistema de Impuestos Nacionales)
**Ubicación:** `app/Jobs/ExportarAlSINJob.php:43,56`  
**Documentado en:** `docs/dev/Loggin.md:958-1018`

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
            'file_path' => $filePath,
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

### 13. Sin Panel de Visualización de Logs
**Ubicación:** No implementado  
**Documentado en:** `docs/dev/Loggin.md:1021-1052`

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

### 14. Sin Alertas Automáticas para Errores
**Ubicación:** No implementado  
**Documentado en:** `docs/dev/Loggin.md:1055-1091`

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

## 📊 Resumen de Bugs por Archivo

| Archivo | Bugs | Prioridad |
|---------|------|-----------|
| `app/Http/Middleware/Loggin.php` | 1, 2, 3, 4, 6, 7, 8 | Alta |
| `config/logging.php` | 5 | Alta |
| `app/Jobs/ExportarAlSINJob.php` | 12 | Media |
| `app/Models/User.php` | 3 | Media |

---

## 📝 Archivos de Documentación Afectados

Para actualizar tu documentación después de corregir estos bugs, debes modificar:

1. **`docs/dev/Loggin.md`** - Líneas 586-1115 (eliminar bugs corregidos)
2. **`docs/dev/System.md`** - Líneas 546-575 (bug #5 coincidente sobre logs creciendo)
3. **`docs/dev/Install.md`** - Referencias a logging (falta #11 sobre queries)

---

## 🎯 Prioridad de Corrección

### 🔴 ALTA - Corregir ASAP
1. **Sanitización recursiva de datos sensibles** (#1)
2. **Filtro de URL 'admin/compass' más preciso** (#2)
3. **Manejar NullPointerException en hasRole()** (#3)
4. **Ofuscar datos personales** (#4)
5. **Implementar rotación de logs** (#5)

### 🟠 MEDIA - Corregir pronto
6. **Validación de datos de usuario** (#6)
7. **Registrar excepciones en catch** (#7)
8. **Distinguir niveles de log** (#8)
9. **Logging de errores y excepciones** (#9)
10. **Implementar Request ID para correlación** (#10)

### 🟢 BAJA - Puede esperar
11. **Logging de operaciones de base de datos** (#11)
12. **Mejorar logging de exportación al SIN** (#12)
13. **Panel de visualización de logs** (#13)
14. **Alertas automáticas para errores** (#14)

---

## 💡 Roadmap de Implementación

### Fase 1: Seguridad y Datos Sensibles (Semana 1-2)
- [ ] Implementar sanitización recursiva
- [ ] Corregir filtro de URL admin/compass
- [ ] Manejar NullPointerException en hasRole()
- [ ] Ofuscar datos personales en logs
- [ ] Implementar rotación de logs

### Fase 2: Calidad de Logs (Semana 3-4)
- [ ] Agregar validación de datos de usuario
- [ ] Registrar excepciones en catch
- [ ] Distinguir niveles de log
- [ ] Logging de errores y excepciones HTTP
- [ ] Implementar Request ID para correlación

### Fase 3: Funcionalidades Avanzadas (Semana 5-6)
- [ ] Logging de queries SQL
- [ ] Mejorar logging de exportación al SIN
- [ ] Panel de visualización de logs
- [ ] Alertas automáticas para errores

### Fase 4: Monitoreo y Optimización (Semana 7-8)
- [ ] Dashboard de métricas de logging
- [ ] Integración con herramientas externas (Sentry, Datadog)
- [ ] Tests automatizados para middleware de logging
- [ ] Documentación completa
