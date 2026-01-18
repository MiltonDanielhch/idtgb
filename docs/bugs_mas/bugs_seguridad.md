# Bugs de Seguridad del Sistema ITGB

**Fuentes:** `docs/dev/System.md`, `docs/dev/Loggin.md`, `docs/dev/Install.md`, `docs/dev/dashboard_controller.md`, `docs/dev/dashboard_service.md`

## 🐛 Bugs Críticos de Seguridad

### 1. Sanitización Superficial de Datos Sensibles en Logs
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:37,46`  
**Documentado en:** `docs/dev/Loggin.md:590-643`

**Problema:**
```php
'input' => request()->except(['password', '_token', '_method'])
```

El uso de `request()->except()` solo excluye campos en el nivel raíz. Datos anidados con información sensible NO se excluyen.

**Ejemplo vulnerable:**
```json
{
    "user": {
        "name": "Juan",
        "password": "secret123"  // ❌ NO se excluye
    },
    "payment": {
        "credit_card": "4111-1111-1111-1111",  // ❌ NO se excluye
        "cvv": "123"
    }
}
```

**Impacto:** Datos sensibles (contraseñas, tarjetas, tokens financieros) pueden aparecer en `storage/logs/requests.log`.

**Riesgo de seguridad:**
- **CATEGORÍA:** Alto
- **VULNERABILIDAD:** Exposición de datos personales
- **CUMPLIMIENTO:** Violación potencial de GDPR, PCI-DSS
- **FRECUENCIA:** Cada petición se logea

**Solución:**
```php
private function sanitizeInput(array $input): array
{
    $sensitiveKeys = ['password', '_token', '_method', 'credit_card', 'cvv', 
                      'expiry', 'bank_account', 'routing_number', 'ssn', 'token'];
    
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

### 2. Datos Personales No Sanitizados en Logs
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:30-33`  
**Documentado en:** `docs/dev/Loggin.md:731-793`

**Problema:**
```php
'user_id' => Auth::user()->id,
'role' => Auth::user()->role->name,
'name' => Auth::user()->name,
'email' => Auth::user()->email,
```

Se registran datos personales (nombre, email) sin sanitización ni ofuscación.

**Riesgo de seguridad:**
- **CATEGORÍA:** Alto
- **VULNERABILIDAD:** Exposición de datos personales
- **CUMPLIMIENTO:** Violación potencial de GDPR, Ley de Protección de Datos Personales
- **CONFIDENCIALIDAD:** Cualquier persona con acceso a logs puede ver nombres y emails

**Evidencia en logs reales:**
```json
{
    "name": "Administrador",
    "email": "admin@admin.com",
    "role": "administrador"
}
```

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

### 3. Comando Install Puede Ejecutarse en Producción
**Ubicación del bug:** `app/Console/Commands/Install.php:23`  
**Documentado en:** `docs/dev/Install.md:205-213`

**Problema:** No se verifica si estamos en entorno de producción antes de ejecutar `migrate:fresh`.

**Riesgo de seguridad:**
- **CATEGORÍA:** CRÍTICO
- **VULNERABILIDAD:** Pérdida de datos (Data Loss)
- **IMPACTO:** Destrucción completa de la base de datos de producción
- **FRECUENCIA:** Si se ejecuta por error

**Código vulnerable:**
```php
// Sin verificar APP_ENV
if ($this->confirm('¿Eliminar y recrear la base de datos?')) {
    $this->call('migrate:fresh');
    $this->call('db:seed');
}
```

**Solución:**
```php
public function handle()
{
    // Bloquear operaciones destructivas en producción
    if (app()->environment('production')) {
        $this->error('❌ ERROR CRÍTICO: Este comando no debe ejecutarse en producción');
        $this->error('   El comando migrate:fresh destruiría TODOS los datos de producción');
        $this->error('   Si realmente quieres hacer esto, usa: php artisan install:production --force');
        return 1;
    }
    
    // ... resto del código
}
```

---

### 4. Validación Inexistente del Parámetro `range`
**Ubicación del bug:** `app/Http/Controllers/Admin/DashboardController.php`, método `index`  
**Documentado en:** `docs/dev/dashboard_controller.md:706-737`

**Problema:**
```php
public function index(Request $request)
{
    // No valida si el parámetro 'range' es válido.
    // Si alguien inyecta `?range=hoy`, el sistema puede fallar o mostrar datos incorrectos.
    $data = $this->dashboardService->getData($request);
    
    return view('vendor.voyager.index', $data);
}
```

**Riesgo de seguridad:**
- **CATEGORÍA:** Medio
- **VULNERABILIDAD:** Inyección de parámetros (Parameter Injection)
- **IMPACTO:** Posible ejecución de código no controlado o exposición de datos incorrectos
- **CUMPLIMIENTO:** Falta de validación de entrada (Input Validation)

**Solución:**
```php
public function index(Request $request)
{
    // Validar que el rango sea válido
    $validRanges = ['today', 'week', 'month', 'year'];
    
    if (!in_array($request->input('range', 'month'), $validRanges)) {
        abort(400, 'Rango de fechas inválido. Opciones: ' . implode(', ', $validRanges));
    }
    
    $data = $this->dashboardService->getData($request);
    
    return view('vendor.voyager.index', $data);
}
```

---

### 5. Cache Keys No Únicas por Usuario
**Ubicación del bug:** `app/Services/DashboardService.php`, generación de cache keys  
**Documentado en:** `docs/dev/dashboard_service.md:478-488`

**Problema:**
```php
$cachePrefix = 'dashboard:' . $range . ':' . $startDate->format('Ymd') . ':' . $endDate->format('Ymd');
```

El prefijo de caché no incluye información del usuario.

**Riesgo de seguridad:**
- **CATEGORÍA:** Alto
- **VULNERABILIDAD:** Fuga de información entre usuarios (Information Disclosure)
- **IMPACTO:** Usuario podría ver datos de otro usuario con diferente rol
- **CONFIDENCIALIDAD:** Violación del principio de separación de datos por usuario

**Ejemplo vulnerable:**
```php
// Usuario Admin solicita dashboard con range='year'
$cacheKey = 'dashboard:year:20250101:20251231';

// Usuario Regular solicita dashboard con mismo range
// Obtiene los MISMOS datos del cache (puede incluir información admin)
```

**Solución:**
```php
// Incluir información del usuario en el cache key
$cachePrefix = 'dashboard:' . 
                 $range . ':' . 
                 $startDate->format('Ymd') . ':' . 
                 $endDate->format('Ymd') . ':' .
                 optional(auth()->user())->id ?? 'guest' . ':' .
                 optional(auth()->user())?->role_id ?? 'guest';
```

---

### 6. Falta de Protección CSRF en Frontend
**Ubicación del bug:** `resources/views/vendor/voyager/dashboard/index.blade.php`  
**Documentado en:** `docs/dev/dashboard_controller.md:426-488` (JavaScript)

**Problema:**
```javascript
fetch('/admin/dashboard/fetchData?range=' + range, {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
})
```

El código depende de que exista el meta tag `csrf-token`, pero no verifica que exista.

**Riesgo de seguridad:**
- **CATEGORÍA:** Medio
- **VULNERABILIDAD:** Falta de protección CSRF potencial
- **IMPACTO:** Posible solicitud sin token CSRF si el meta tag no existe
- **CUMPLIMIENTO:** OWASP Top 10 - Broken Access Control

**Solución:**
```javascript
const csrfToken = document.querySelector('meta[name="csrf-token"]');

if (!csrfToken) {
    console.error('CSRF token no encontrado');
    toastr.error('Error de seguridad: Token CSRF no disponible');
    return;
}

fetch('/admin/dashboard/fetchData?range=' + range, {
    method: 'GET',
    headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': csrfToken.content
    }
})
```

---

## 🔍 Problemas Menores de Seguridad

### 7. Logs Crecen Indefinidamente
**Ubicación del bug:** `config/logging.php:120-124`  
**Documentado en:** `docs/dev/Loggin.md:796-835`

**Problema:**
```php
'requests' => [
    'driver' => 'single',  // ❌ Archivo único, crece indefinidamente
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
],
```

**Riesgo de seguridad:**
- **CATEGORÍA:** Bajo
- **VULNERABILIDAD:** Llenado de disco (DoS)
- **IMPACTO:** El sistema puede dejar de funcionar si el disco se llena
- **CUMPLIMIENTO:** Falta de retención y rotación de logs (PCI-DSS Requirement 10.7)

**Solución:**
```php
'requests' => [
    'driver' => 'daily',        // ✅ Crear un archivo por día
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,             // ✅ Mantener logs por 30 días
    'tap'    => [App\Logging\CustomizeFormatter::class],  // Formato JSON para análisis
],
```

---

### 8. Falta de Rate Limiting en Dashboard
**Ubicación del bug:** `routes/web.php` (inferido)  
**Documentado en:** `docs/dev/dashboard_controller.md:254-275`

**Problema:** No hay límite de peticiones para `/admin/dashboard/fetchData`.

**Riesgo de seguridad:**
- **CATEGORÍA:** Medio
- **VULNERABILIDAD:** DoS (Denial of Service)
- **IMPACTO:** Un atacante puede abrumar el servidor con peticiones
- **CUMPLIMIENTO:** OWASP Top 10 - Insufficient Rate Limiting

**Solución:**
```php
Route::middleware(['auth', 'system', 'throttle:60,1'])  // 60 peticiones por minuto
    ->get('/admin/dashboard/fetchData', [DashboardController::class, 'fetchData'])
    ->name('admin.dashboard.fetchData');
```

---

### 9. Falta de Verificación de Permisos en Dashboard
**Ubicación del bug:** `app/Http/Controllers/Admin/DashboardController.php`  
**Documentado en:** `docs/dev/dashboard_controller.md:148-177`

**Problema:** Solo se usa middleware `auth`, no se verifica el rol específico.

**Riesgo de seguridad:**
- **CATEGORÍA:** Medio
- **VULNERABILIDAD:** Broken Access Control
- **IMPACTO:** Cualquier usuario autenticado podría acceder al dashboard
- **CUMPLIMIENTO:** OWASP Top 10 - Broken Access Control

**Solución:**
```php
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('can:view dashboard');
}

// En AuthServiceProvider
public function boot()
{
    Gate::define('view dashboard', function ($user) {
        return in_array($user->role_id, [1, 2, 3]); // Solo roles específicos
    });
}
```

---

### 10. Excepciones Silenciadas Sin Logging
**Ubicación del bug:** `app/Http/Middleware/Loggin.php:23-25`  
**Documentado en:** `docs/dev/Loggin.md:680-728`

**Problema:**
```php
catch (\Throwable $th) {
    // 3. Fallback: registra sin datos de usuario si hay error
}
```

El bloque catch no registra el error, solo ejecuta un fallback.

**Riesgo de seguridad:**
- **CATEGORÍA:** Bajo
- **VULNERABILIDAD:** Falta de logging de errores de seguridad
- **IMPACTO:** Errores de seguridad no son monitoreados ni reportados
- **CUMPLIMIENTO:** Falta de auditoría (PCI-DSS Requirement 10.2.1)

**Solución:**
```php
catch (\Throwable $th) {
    Log::channel('security')->error('Error al registrar log', [
        'error' => $th->getMessage(),
        'trace' => $th->getTraceAsString(),
        'url' => $request->url(),
        'user_id' => optional(auth()->user())->id,
    ]);
    
    // Fallback sin datos de usuario
    $data = $this->getFallbackData($request);
}
```

---

## 📊 Resumen de Bugs de Seguridad por Severidad

| Severidad | Cantidad | Bugs |
|-----------|---------|-------|
| **CRÍTICA** | 1 | 3 - Install en producción |
| **ALTA** | 4 | 1 - Sanitización superficial<br>2 - Datos personales en logs<br>5 - Cache keys por usuario<br>6 - Protección CSRF |
| **MEDIA** | 4 | 4 - Validación range<br>8 - Rate limiting<br>9 - Verificación permisos<br>10 - Excepciones silenciadas |
| **BAJA** | 1 | 7 - Logs infinitos |
| **TOTAL** | **10** | - |

---

## 📊 Resumen de Bugs de Seguridad por Categoría OWASP

| Categoría OWASP | Cantidad | Bugs |
|------------------|---------|-------|
| **A01:2021 - Broken Access Control** | 2 | 9 - Permisos en Dashboard<br>4 - Validación de range |
| **A02:2021 - Cryptographic Failures** | 1 | 2 - Datos personales en logs |
| **A03:2021 - Injection** | 1 | 4 - Inyección de parámetros |
| **A04:2021 - Insecure Design** | 2 | 5 - Cache keys no únicas<br>6 - Protección CSRF |
| **A05:2021 - Security Misconfiguration** | 3 | 1 - Sanitización superficial<br>7 - Logs infinitos<br>10 - Excepciones silenciadas |
| **A07:2021 - Identification and Authentication Failures** | 1 | 3 - Install en producción |
| **A10:2021 - Server-Side Request Forgery (SSRF)** | 0 | - |

---

## 📊 Resumen de Bugs por Archivo

| Archivo | Bugs | Severidad |
|---------|------|-----------|
| `app/Http/Middleware/Loggin.php` | 1, 2, 10 | Alta / Alta / Baja |
| `app/Console/Commands/Install.php` | 3 | CRÍTICA |
| `app/Http/Controllers/Admin/DashboardController.php` | 4, 9 | Media / Media |
| `app/Services/DashboardService.php` | 5 | Alta |
| `resources/views/vendor/voyager/dashboard/index.blade.php` | 6 | Media |
| `config/logging.php` | 7 | Baja |
| `routes/web.php` | 8 | Media |

---

## 📝 Archivos de Documentación Afectados

Para actualizar tu documentación después de corregir estos bugs, debes modificar:

1. **`docs/dev/System.md`** - Referencias a configuración y seguridad
2. **`docs/dev/Loggin.md`** - Líneas 590-835 (bugs #1, #2, #7, #10)
3. **`docs/dev/Install.md`** - Líneas 205-213 (bug #3)
4. **`docs/dev/dashboard_controller.md`** - Líneas 704-937 (bugs #4, #6, #9)
5. **`docs/dev/dashboard_service.md`** - Líneas 478-488 (bug #5)

---

## 🎯 Prioridad de Corrección

### 🔴 CRÍTICA - Corregir INMEDIATAMENTE
1. **Protección para producción en Install** (#3) - Riesgo de pérdida de datos

### 🔴 ALTA - Corregir ASAP
2. **Sanitización recursiva de datos sensibles** (#1) - Violación de GDPR/PCI-DSS
3. **Ofuscar datos personales en logs** (#2) - Violación de GDPR
4. **Cache keys únicas por usuario** (#5) - Fuga de información
5. **Protección CSRF en frontend** (#6) - OWASP Top 10

### 🟠 MEDIA - Corregir pronto
6. **Validación del parámetro range** (#4) - OWASP A01
7. **Rate limiting en dashboard** (#8) - OWASP A07
8. **Verificación de permisos en dashboard** (#9) - OWASP A01
9. **Registrar excepciones en catch** (#10) - Falta de auditoría

### 🟢 BAJA - Corrección recomendada
10. **Implementar rotación de logs** (#7) - PCI-DSS Requirement 10.7

---

## 💡 Roadmap de Implementación

### Fase 1: Crítica y Alta Prioridad (Día 1-2)
- [ ] Bloquear Install en producción
- [ ] Implementar sanitización recursiva de logs
- [ ] Ofuscar datos personales en logs
- [ ] Implementar cache keys únicas por usuario
- [ ] Mejorar protección CSRF en frontend

### Fase 2: Media Prioridad (Día 3-4)
- [ ] Validar parámetro range
- [ ] Implementar rate limiting en dashboard
- [ ] Agregar verificación de permisos
- [ ] Registrar excepciones de seguridad

### Fase 3: Mejoras (Día 5)
- [ ] Implementar rotación de logs
- [ ] Agregar auditoría de accesos
- [ ] Implementar alertas de seguridad
- [ ] Documentación de cambios

---

## 🔐 Recomendaciones de Seguridad Adicionales

### 1. Implementar HTTP Headers de Seguridad
```php
// app/Http/Middleware/SecurityHeaders.php
public function handle($request, Closure $next)
{
    $response = $next($request);
    
    $response->headers->set('X-Content-Type-Options', 'nosniff');
    $response->headers->set('X-Frame-Options', 'DENY');
    $response->headers->set('X-XSS-Protection', '1; mode=block');
    $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    $response->headers->set('Content-Security-Policy', "default-src 'self'");
    
    return $response;
}
```

### 2. Implementar Auditoría de Accesos
```php
// Crear tabla de auditoría
Schema::create('security_audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->nullable();
    $table->string('action');
    $table->string('ip_address');
    $table->text('user_agent');
    $table->string('route');
    $table->integer('status_code');
    $table->json('input')->nullable();
    $table->timestamps();
});
```

### 3. Implementar Monitorización de Seguridad
```php
// Crear comando de verificación
php artisan security:check

// Verificar:
// - Versiones vulnerables de dependencias
// - Permisos incorrectos en archivos
// - Configuraciones expuestas
// - Excepciones frecuentes
```

### 4. Implementar Política de Retención de Logs
```php
// config/logging.php
'requests' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,  // 30 días (PCI-DSS Requirement 10.7)
    'tap'    => [App\Logging\CustomizeFormatter::class],
],
```

---

## 📋 Checklist de Cumplimiento

### PCI-DSS
- [ ] Requirement 10.2: Logs contienen todos los accesos del sistema
- [ ] Requirement 10.3: Logs incluyen usuario, fecha, tipo de evento
- [ ] Requirement 10.7: Retención de logs por al menos 1 año (3 meses accesibles)
- [ ] Requirement 10.7.2: Logs inmutables (no se pueden modificar)

### GDPR
- [ ] Artículo 32: Seguridad del procesamiento (encryption, pseudonymization)
- [ ] Artículo 33: Notificación de brecha de seguridad
- [ ] Artículo 35: Minimización de datos (solo registrar lo necesario)

### OWASP Top 10 2021
- [ ] A01: Broken Access Control - Verificar permisos en cada endpoint
- [ ] A03: Injection - Validar todas las entradas del usuario
- [ ] A04: Insecure Design - Revisar arquitectura de cache
- [ ] A05: Security Misconfiguration - Configurar headers de seguridad
- [ ] A07: Identification and Authentication - Implementar MFA si es necesario
