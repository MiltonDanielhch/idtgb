# Bugs Críticos del Sistema ITGB - Consolidado para Priorización Inmediata

**Fuentes:** `docs/dev/System.md`, `docs/dev/Loggin.md`, `docs/dev/Install.md`, `docs/dev/dashboard_controller.md`, `docs/dev/dashboard_service.md`

## 🚨 CRÍTICOS - Corregir INMEDIATAMENTE (Bloquean funcionalidad)

### 1. Comando Install Puede Ejecutarse en Producción
**Archivos afectados:** `app/Console/Commands/Install.php`  
**Documentado en:** `docs/dev/Install.md:205-213` | `bugs_install.md:192-213`

**Problema:** No se verifica si estamos en entorno de producción antes de ejecutar `migrate:fresh`.

**Riesgo:** **PÉRDIDA TOTAL DE DATOS DE PRODUCCIÓN**

**Código vulnerable:**
```php
// Sin verificar APP_ENV
if ($this->confirm('¿Eliminar y recrear la base de datos?')) {
    $this->call('migrate:fresh');  // ❌ Destruye TODOS los datos
    $this->call('db:seed');
}
```

**Solución:**
```php
public function handle()
{
    if (app()->environment('production')) {
        $this->error('❌ ERROR CRÍTICO: Este comando no debe ejecutarse en producción');
        $this->error('   El comando migrate:fresh destruiría TODOS los datos de producción');
        $this->error('   Si realmente quieres hacer esto, usa: php artisan install:production --force');
        return 1;
    }
    // ... resto del código
}
```

**Prioridad:** 🔴 **URGENTE** - Bloquea seguridad de producción

---

### 2. Conexión SolucionDigital Inexistente Causa Errores 500
**Archivos afectados:** `resources/views/vendor/voyager/master.blade.php`, `config/database.php`  
**Documentado en:** `docs/dev/System.md:489-514` | `bugs_system.md:489-514`

**Problema:** La conexión está comentada en config pero el código sigue intentando usarla.

**Riesgo:** **ERRORES 500 EN PRODUCCIÓN**

**Código vulnerable:**
```php
// master.blade.php - Acceso sin verificar conexión
$aux = new Controller();
if($solucionDigital->isNotEmpty() && is_numeric($aux->payment_alert())) {  // ❌ Puede fallar
    // ...
}

// config/database.php - Conexión comentada pero referencia aún existe
//'solucionDigital' => [  // ❌ Comentario
//    'driver' => 'mysql',
//    // ...
//],

// Código intenta usar conexión
DB::connection('solucionDigital')->table('settings')->get();  // ❌ Error 500
```

**Solución:**
```php
// Verificar conexión antes de usarla
$solucionDigital = rescue(
    fn () => config('database.connections.solucionDigital') 
        ? DB::connection('solucionDigital')->table('settings')->get()
        : collect(),
    fn () => collect()  // Fallback seguro
);

// Verificar antes de acceder al método
if (is_callable([$aux, 'payment_alert'])) {
    $paymentAlertValue = $aux->payment_alert();
    // Usar valor...
}
```

**Prioridad:** 🔴 **URGENTE** - Causa errores 500 en producción

---

## 🚨 ALTA PRIORIDAD - Corregir ASAP (Riesgos de seguridad y datos)

### 3. Sanitización Superficial de Datos Sensibles en Logs
**Archivos afectados:** `app/Http/Middleware/Loggin.php`  
**Documentado en:** `docs/dev/Loggin.md:590-643` | `bugs_logging.md:590-643`

**Problema:** `request()->except()` solo excluye campos raíz. Datos anidados NO se excluyen.

**Riesgo:** **EXPOSICIÓN DE DATOS SENSIBLES (GDPR, PCI-DSS)**

**Código vulnerable:**
```php
'input' => request()->except(['password', '_token', '_method'])
```

**Ejemplo vulnerable:**
```json
{
    "user": {
        "name": "Juan",
        "password": "secret123"  // ❌ NO se excluye (anidado)
    },
    "payment": {
        "credit_card": "4111-1111-1111-1111",  // ❌ NO se excluye
        "cvv": "123"
    }
}
```

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

**Prioridad:** 🔴 **URGENTE** - Violación de GDPR/PCI-DSS

---

### 4. Datos Personales No Sanitizados en Logs
**Archivos afectados:** `app/Http/Middleware/Loggin.php`  
**Documentado en:** `docs/dev/Loggin.md:731-793` | `bugs_logging.md:731-793`

**Problema:** Se registran nombres y emails sin ofuscación ni sanitización.

**Riesgo:** **VIOLACIÓN DE PRIVACIDAD (GDPR)**

**Código vulnerable:**
```php
'user_id' => Auth::user()->id,
'role' => Auth::user()->role->name,
'name' => Auth::user()->name,  // ❌ Nombre completo en logs
'email' => Auth::user()->email,  // ❌ Email completo en logs
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

**Prioridad:** 🔴 **URGENTE** - Violación de GDPR

---

### 5. Cache Keys No Únicas por Usuario en Dashboard
**Archivos afectados:** `app/Services/DashboardService.php`  
**Documentado en:** `docs/dev/dashboard_service.md:478-488` | `bugs_dashboard.md:478-488`

**Problema:** El cache key no incluye información del usuario.

**Riesgo:** **FUGA DE INFORMACIÓN ENTRE USUARIOS**

**Código vulnerable:**
```php
$cachePrefix = 'dashboard:' . $range . ':' . $startDate->format('Ymd') . ':' . $endDate->format('Ymd');
```

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

**Prioridad:** 🔴 **URGENTE** - Fuga de información

---

### 6. División por Cero en Cálculo de Tendencias
**Archivos afectados:** `app/Services/DashboardService.php`  
**Documentado en:** `docs/dev/dashboard_service.md` (inferido de cálculo de tendencias)

**Problema:** Si `$previousValue` es 0, causará división por cero.

**Riesgo:** **ERROR 500 EN DASHBOARD**

**Código vulnerable:**
```php
// En el cálculo de tendencias
$percentage = (($currentValue - $previousValue) / $previousValue) * 100;
```

**Solución:**
```php
private function calculateTrend($currentValue, $previousValue): float
{
    if ($previousValue == 0) {
        return $currentValue > 0 ? 100 : 0;
    }
    
    return (($currentValue - $previousValue) / $previousValue) * 100;
}
```

**Prioridad:** 🟠 **ALTA** - Causa error 500

---

### 7. Falta de Manejo de Errores en Consultas de Dashboard
**Archivos afectados:** `app/Services/DashboardService.php`  
**Documentado en:** `docs/dev/dashboard_service.md` (todo el método getData)

**Problema:** Las consultas no están envueltas en try-catch.

**Riesgo:** **ERROR 500 EN DASHBOARD SIN MANEJO APROPIADO**

**Código vulnerable:**
```php
public function getData(Request $request): array
{
    // Sin try-catch
    $recaudadoPeriodo = Pago::where('estado', 'Aplicado')...->sum('monto');
    // Si falla, error 500 sin contexto
}
```

**Solución:**
```php
public function getData(Request $request): array
{
    try {
        $range = $request->input('range', 'month');
        $validRanges = ['today', 'week', 'month', 'year'];
        
        if (!in_array($range, $validRanges)) {
            throw new \InvalidArgumentException("Rango inválido: {$range}");
        }
        
        // ... resto del código de cálculo
        
        return $data;
        
    } catch (\Exception $e) {
        Log::error('Error en DashboardService', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'range' => $request->input('range', 'month'),
        ]);
        
        // Retornar valores por defecto para que el dashboard no falle completamente
        return $this->getDefaultDashboardData();
    }
}

private function getDefaultDashboardData(): array
{
    return [
        'kpiLabel' => 'Este Mes',
        'recaudadoPeriodo' => 0,
        'tramitesPeriodo' => 0,
        'tramitesFinalizadosPeriodo' => 0,
        'tramitesPendientes' => 0,
        'tendencias' => [
            'recaudacion' => ['percentage' => 0, 'comparacion' => 1],
            'tramites' => ['percentage' => 0, 'comparacion' => 1],
            'finalizados' => ['percentage' => 0, 'comparacion' => 1],
            'pendientes' => ['percentage' => 0, 'comparacion' => 1],
        ],
        'ultimostramitesHtml' => '<div class="alert alert-warning">No se pudieron cargar los datos</div>',
        'recaudacionPeriodoData' => collect(),
        'tramitesPorTipo' => collect(),
        'tramitesPorEstado' => collect(),
        'comparacionAnualData' => ['actual' => array_fill(0, 12, 0), 'anterior' => array_fill(0, 12, 0)],
    ];
}
```

**Prioridad:** 🟠 **ALTA** - Estabilidad del dashboard

---

## 🟠 MEDIA PRIORIDAD - Corregir pronto (Problemas de rendimiento y usabilidad)

### 8. Archivo de Log Crece Indefinidamente
**Archivos afectados:** `config/logging.php`  
**Documentado en:** `docs/dev/Loggin.md:796-835` | `bugs_logging.md:796-835`

**Problema:** Driver `single` sin rotación.

**Riesgo:** **LLENADO DE DISCO (DoS)**

**Código vulnerable:**
```php
'requests' => [
    'driver' => 'single',  // ❌ Archivo único, crece indefinidamente
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
],
```

**Solución:**
```php
'requests' => [
    'driver' => 'daily',        // ✅ Crear un archivo por día
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,             // ✅ Mantener logs por 30 días
    'tap'    => [App\Logging\CustomizeFormatter::class],
],
```

**Prioridad:** 🟠 **ALTA** - Riesgo de llenado de disco

---

### 9. Falta de Validación de Entorno en Install Command
**Archivos afectados:** `app/Console/Commands/Install.php`  
**Documentado en:** `docs/dev/Install.md:205-213` | `bugs_install.md:205-213`

**Problema:** Mismo que bug #1 (es el mismo problema)

**Prioridad:** 🟠 **ALTA** - Ya cubierto en bug #1

---

### 10. Inconsistencia de Fechas en Comparación Anual
**Archivos afectados:** `app/Services/DashboardService.php`  
**Documentado en:** `docs/dev/dashboard_service.md:422-447`

**Problema:** Si no hay datos para un mes, el array tiene huecos.

**Riesgo:** **GRÁFICOS CON ERRORES DE RENDERIZADO**

**Código vulnerable:**
```php
// Huecos en el array
$actual = [15000, null, 18500, 12000];  // ❌ Mes 2 sin datos
```

**Solución:**
```php
// Usar array_replace para llenar huecos con 0
$comparacionAnualData = [
    'actual' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioActual)),
    'anterior' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioAnterior)),
];
```

**Prioridad:** 🟠 **ALTA** - Corrige visualización de gráficos

---

## 📊 Resumen de Bugs por Severidad

| Severidad | Cantidad | Números |
|-----------|---------|---------|
| **🔴 CRÍTICO** | 2 | 1, 2 |
| **🔴 URGENTE** | 3 | 3, 4, 5 |
| **🟠 ALTA** | 5 | 6, 7, 8, 10 |
| **🟢 MEDIA** | - | - |
| **TOTAL** | **10** | - |

---

## 📊 Resumen de Bugs por Categoría

| Categoría | Cantidad | Bugs |
|-----------|---------|-------|
| **Seguridad** | 4 | 1, 3, 4, 5 |
| **Estabilidad** | 3 | 2, 6, 7 |
| **Rendimiento** | 1 | 8 |
| **Usabilidad** | 2 | 9, 10 |
| **TOTAL** | **10** | - |

---

## 📊 Resumen de Bugs por Archivo

| Archivo | Bugs | Severidad |
|---------|------|-----------|
| `app/Console/Commands/Install.php` | 1 | CRÍTICA |
| `resources/views/vendor/voyager/master.blade.php` | 1 | CRÍTICA |
| `config/database.php` | 1 | CRÍTICA |
| `app/Http/Middleware/Loggin.php` | 2 | URGENTE (seguridad) |
| `app/Services/DashboardService.php` | 3 | URGENTE + ALTA |
| `config/logging.php` | 1 | ALTA (rendimiento) |
| `app/Http/Controllers/Admin/DashboardController.php` | - | - |

---

## 📝 Archivos de Documentación Afectados

Para actualizar tu documentación después de corregir estos bugs, debes modificar:

1. **`docs/dev/System.md`** - Eliminar referencias a bugs corregidos
2. **`docs/dev/Loggin.md`** - Líneas 590-835 (bugs #3, #4, #8)
3. **`docs/dev/Install.md`** - Líneas 205-213 (bug #1, #9)
4. **`docs/dev/dashboard_controller.md`** - Sección de seguridad
5. **`docs/dev/dashboard_service.md`** - Sección de cálculos y cache

---

## 🎯 Plan de Acción Inmediato (Próximas 24-48 horas)

### 🚨 HOY - Corregir los 2 bugs CRÍTICOS
- [ ] **Bloquear Install en producción** (bug #1) - 30 min
- [ ] **Arreglar conexión SolucionDigital** (bug #2) - 1 hora

### 🔴 ESTA SEMANA - Corregir los 3 bugs URGENTES
- [ ] **Sanitización recursiva de logs** (bug #3) - 2 horas
- [ ] **Ofuscar datos personales en logs** (bug #4) - 1 hora
- [ ] **Cache keys únicas por usuario** (bug #5) - 30 min

### 🟠 PRÓXIMA SEMANA - Corregir los 5 bugs ALTA prioridad
- [ ] **División por cero en tendencias** (bug #6) - 30 min
- [ ] **Manejo de errores en Dashboard** (bug #7) - 2 horas
- [ ] **Rotación de logs** (bug #8) - 30 min
- [ ] **Validación de entorno** (bug #9) - Ya cubierto en #1
- [ ] **Comparación anual con array_replace** (bug #10) - 30 min

---

## 🔥 Estimación de Tiempo Total

| Categoría | Cantidad | Tiempo Estimado |
|-----------|---------|-----------------|
| **CRÍTICOS** | 2 | 1.5 horas |
| **URGENTES** | 3 | 3.5 horas |
| **ALTA** | 5 | 3.5 horas |
| **TOTAL** | **10** | **~8.5 horas** |

---

## 📋 Checklist de Validación Después de Correcciones

### Bugs CRÍTICOS
- [ ] Verificar que Install falla en producción con mensaje claro
- [ ] Verificar que master.blade no causa errores 500

### Bugs URGENTES
- [ ] Verificar que logs no contienen passwords/tarjetas/cvv
- [ ] Verificar que nombres/emails están ofuscados o ausentes
- [ ] Verificar que cada usuario tiene su propio cache en dashboard

### Bugs ALTA
- [ ] Verificar que dashboard no da error 500 con datos nuevos
- [ ] Verificar que gráficos se renderizan correctamente con huecos
- [ ] Verificar que logs se rotan diariamente
- [ ] Verificar que se genera dashboard-default-data en errores

---

## 🚨 Matriz de Riesgos

| Bug | Probabilidad | Impacto | Riesgo Total | Prioridad |
|-----|-------------|---------|--------------|-----------|
| #1 - Install en prod | Baja | CRÍTICO | 🟠 MEDIO | CRÍTICA |
| #2 - SolucionDigital | Alta | ALTO | 🔴 ALTO | CRÍTICA |
| #3 - Sanitización logs | Alta | CRÍTICO | 🔴 ALTO | URGENTE |
| #4 - Datos personales | Alta | CRÍTICO | 🔴 ALTO | URGENTE |
| #5 - Cache keys | Media | ALTO | 🟠 MEDIO | URGENTE |
| #6 - División cero | Media | ALTO | 🟠 MEDIO | ALTA |
| #7 - Manejo errores | Media | MEDIO | 🟢 BAJO | ALTA |
| #8 - Logs infinitos | Alta | ALTO | 🔴 ALTO | ALTA |
| #9 - Validación entorno | Baja | CRÍTICO | 🟠 MEDIO | ALTA |
| #10 - Fechas comparación | Baja | MEDIO | 🟢 BAJO | ALTA |

---

## 💡 Recomendaciones de Implementación

### 1. Implementar todos los cambios en una rama separada
```bash
git checkout -b bugfixes-critical
```

### 2. Crear pruebas para cada bug antes de corregir
```bash
php artisan make:test InstallCommandTest
php artisan make:test LogginMiddlewareTest
php artisan make:test DashboardServiceTest
```

### 3. Usar feature flags para cambios riesgosos
```php
if (config('features.secure_logging_enabled')) {
    // Nuevo código seguro
} else {
    // Código anterior (fallback)
}
```

### 4. Implementar monitoreo para detectar regresiones
```php
Log::warning('Bug detected', [
    'bug_id' => 1,
    'description' => 'Install command executed in production',
    'user_id' => auth()->id(),
    'ip' => request()->ip(),
]);
```

### 5. Documentar todos los cambios en CHANGELOG.md
```markdown
## [UNRELEASED] - 2026-01-17

### Added
- Protection for install command in production
- Recursive sanitization of sensitive data in logs

### Changed
- Dashboard cache keys now include user information
- Logging configuration uses daily rotation

### Fixed
- Fixed SolucionDigital connection causing 500 errors
- Fixed division by zero in trend calculations
- Fixed date gaps in annual comparison charts

### Security
- Masked personal data (names, emails) in logs
- Redacted sensitive nested data in request logs
- Implemented user-specific caching for dashboard
```
