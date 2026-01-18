# Mejoras y Optimizaciones de Rendimiento del Sistema ITGB

**Fuentes:** `docs/dev/System.md`, `docs/dev/Loggin.md`, `docs/dev/Install.md`, `docs/dev/dashboard_controller.md`, `docs/dev/dashboard_service.md`

## ⚡ Optimizaciones de Alto Impacto

### 1. Cachear Configuraciones de Settings
**Ubicación:** `app/Http/Middleware/System.php`  
**Documentado en:** `docs/dev/System.md:694-724`

**Problema actual:**
```php
// En cada request, se consulta la base de datos
$devMode = setting('system.development');
$maintMode = setting('configuracion.maintenance');
```

**Impacto:** Cada request que pasa por el middleware ejecuta 2 consultas SQL adicionales.

**Optimización:**
```php
public function handle(Request $request, Closure $next)
{
    $devMode = Cache::remember('system.development', 300, fn() => setting('system.development'));
    $maintMode = Cache::remember('configuracion.maintenance', 300, fn() => setting('configuracion.maintenance'));
    
    Log::info('System MW', [
        'url'   => $request->fullUrl(),
        'user'  => optional(auth()->user())->only(['id', 'name', 'role_id']),
        'dev'   => $devMode,
        'maint' => $maintMode,
    ]);
    
    return $next($request);
}

// Invalidar caché cuando se actualizan los settings
// En el controlador que actualiza settings
Cache::forget('system.development');
Cache::forget('configuracion.maintenance');
```

**Beneficio:**
- **Reducción de consultas:** 2 queries por request → 0 queries (cache hit)
- **Tiempo de respuesta:** Reducción de ~20-50ms por request
- **Carga de BD:** Reducción significativa en tablas de settings

**Prioridad:** Alta

---

### 2. Logging Asíncrono con Colas
**Ubicación:** `app/Http/Middleware/Loggin.php:39,48`  
**Documentado en:** `docs/dev/Loggin.md:1096-1154`

**Problema actual:**
```php
// Logging síncrono bloquea el proceso
Log::channel('requests')->info('Petición HTTP al sistema.', $data);
```

**Impacto:** Cada request espera a que el log se escriba en disco (~2-5ms).

**Optimización:**
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

// Configurar cola dedicada
// config/queue.php
'connections' => [
    'redis' => [
        'queue' => ['default', 'logs'],  // Cola dedicada para logs
    ],
],
```

**Beneficio:**
- **Latencia:** Reducción de ~2-5ms por request
- **Throughput:** Aumento de capacidad de peticiones simultáneas
- **Escalabilidad:** Mejor uso de recursos del servidor

**Prioridad:** Alta

---

### 3. Implementar Buffer de Logs en Memoria
**Ubicación:** `config/logging.php`  
**Documentado en:** `docs/dev/Loggin.md:1191-1208`

**Problema actual:**
```php
// Cada log escribe directamente a disco
'requests' => [
    'driver' => 'single',
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
],
```

**Impacto:** Muchos I/O de escritura pequeños (write amplification).

**Optimización:**
```php
'requests' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/requests.log'),
    'level'  => 'info',
    'days'   => 30,
    'buffer' => true,  // ✅ Buffer en memoria antes de escribir
    'buffer_size' => 100,  // Escribir cada 100 logs
],
```

**Beneficio:**
- **I/O:** Reducción de escrituras al disco en ~90%
- **Rendimiento:** Reducción de ~30% en operaciones de logging
- **Durabilidad:** Logs se escriben cuando el buffer se llena o el script termina

**Prioridad:** Alta

---

### 4. Lazy Loading de Gráficos en Dashboard
**Ubicación:** `app/Services/DashboardService.php`  
**Documentado en:** `mejoras_dashboard.md` (optimización #2)

**Problema actual:** Todos los gráficos se calculan simultáneamente, incluso si el usuario no los ve.

**Impacto:** Tiempo de respuesta innecesario para datos que no se usan.

**Optimización:**
```php
// En DashboardService
public function getCharts(Request $request): array
{
    $range = $request->input('range', 'month');
    $charts = $request->input('charts', []);
    
    $data = [];
    
    if (in_array('recaudacion', $charts)) {
        $data['recaudacion'] = $this->getRecaudacionChart($range);
    }
    
    if (in_array('tramites_tipo', $charts)) {
        $data['tramites_tipo'] = $this->getTramitesPorTipoChart($range);
    }
    
    if (in_array('tramites_estado', $charts)) {
        $data['tramites_estado'] = $this->getTramitesPorEstadoChart($range);
    }
    
    return $data;
}

// En DashboardController
public function fetchCharts(Request $request)
{
    $data = $this->dashboardService->getCharts($request);
    
    return response()->json($data);
}
```

**Beneficio:**
- **Tiempo de carga inicial:** Reducción de ~50-70%
- **Experiencia de usuario:** El dashboard carga más rápido
- **Recursos:** Menos consultas SQL innecesarias

**Prioridad:** Alta

---

### 5. Paralelizar Seeders en Install Command
**Ubicación:** `app/Console/Commands/Install.php:25`  
**Documentado en:** `docs/dev/Install.md:529-540`

**Problema actual:** Los seeders se ejecutan secuencialmente.

**Impacto:** Tiempo de instalación largo en proyectos grandes.

**Optimización:**
```php
// Usar procesos en background o queue system
public function handle()
{
    // ... pasos iniciales ...
    
    if ($this->confirm('¿Eliminar y recrear la base de datos?')) {
        $this->call('migrate:fresh');
        
        // Ejecutar seeders en paralelo usando dispatch
        dispatch(new SeedVoyagerJob());
        dispatch(new SeedUsersJob());
        dispatch(new SeedRolesJob());
        dispatch(new SeedPeopleJob());
        dispatch(new SeedTasasJob());
        
        $this->info('Seeders enviados a cola. Ejecuta: php artisan queue:work');
    }
    
    // ... resto de pasos ...
}

// app/Jobs/SeedVoyagerJob.php
class SeedVoyagerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function handle(): void
    {
        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\VoyagerDatabaseSeeder',
            '--force' => true
        ]);
    }
}
```

**Beneficio:**
- **Tiempo de instalación:** Reducción de ~60-80%
- **Escalabilidad:** Uso eficiente de múltiples núcleos de CPU
- **UX:** El comando responde más rápido

**Prioridad:** Alta

---

### 6. Implementar Cache Tags para Invalidación Granular
**Ubicación:** `app/Services/DashboardService.php`  
**Documentado en:** `mejoras_dashboard.md` (optimización #1)

**Problema actual:**
```php
// Cache key único pero no se puede invalidar selectivamente
$cachePrefix = 'dashboard:' . $range . ':' . $startDate->format('Ymd') . ':' . $endDate->format('Ymd');
```

**Impacto:** Cuando se crea un pago, se debe invalidar todo el cache del dashboard.

**Optimización:**
```php
// En DashboardService
public function getData(Request $request): array
{
    $range = $request->input('range', 'month');
    $validRanges = ['today', 'week', 'month', 'year'];
    
    if (!in_array($range, $validRanges)) {
        throw new \InvalidArgumentException("Rango inválido: {$range}");
    }
    
    // Usar cache tags para invalidación granular
    return cache()->tags(['dashboard', "dashboard:{$range}"])->remember(
        "dashboard:{$range}:data",
        now()->addMinutes(5),
        fn() => $this->calculateDashboardData($range)
    );
}

// Invalidación en PagoObserver
public function created(Pago $pago)
{
    // Invalidar solo tags de dashboard (más eficiente que flush all)
    cache()->tags('dashboard')->flush();
}

// Invalidación granular por rango
public function created(Pago $pago)
{
    // Invalidar solo el cache del rango actual
    $currentRange = $this->getCurrentRange();
    cache()->tags(['dashboard', "dashboard:{$currentRange}"])->flush();
}
```

**Beneficio:**
- **Invalidación eficiente:** Solo se invalida lo necesario
- **Cache hits:** Mayor tasa de hits de caché
- **Rendimiento:** Menos recálculos de datos

**Prioridad:** Alta

---

### 7. Usar Database Aggregations en lugar de PHP
**Ubicación:** `app/Services/DashboardService.php`  
**Documentado en:** `docs/dev/dashboard_service.md:323-341`

**Problema actual:** Algunos cálculos se hacen en PHP después de obtener datos.

**Impacto:** Overhead innecesario de Eloquent y PHP.

**Optimización:**
```php
// Actual (menos eficiente)
$tramitesPeriodo = Tramite::whereBetween('created_at', [$startDate, $endDate])->count();

// Mejor (más eficiente)
$tramitesPeriodo = DB::table('tramites')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->count();

// Más eficiente aún con índice compuesto
$tramitesPeriodo = DB::table('tramites')
    ->whereBetween('created_at', [$startDate, $endDate])
    ->where('estado', '!=', 'Anulado')
    ->count();
```

**Beneficio:**
- **Velocidad:** Reducción de ~20-30% en consultas de conteo
- **Memoria:** Menos uso de memoria (no se crean objetos Eloquent)
- **Escalabilidad:** Mejor rendimiento con grandes volúmenes de datos

**Prioridad:** Media

---

### 8. Debouncing para Fetches del Dashboard
**Ubicación:** Frontend JavaScript (dashboard view)  
**Documentado en:** `mejoras_dashboard.md` (optimización #5)

**Problema actual:** El usuario puede cambiar el selector de rango múltiples veces rápidamente.

**Impacto:** Múltiples requests innecesarios al servidor.

**Optimización:**
```javascript
// En resources/views/vendor/voyager/dashboard/index.blade.php
let debounceTimer;

function fetchDashboard(range) {
    clearTimeout(debounceTimer);
    
    debounceTimer = setTimeout(() => {
        fetch('/admin/dashboard/fetchData?range=' + range, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            updateDashboard(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }, 300); // Esperar 300ms antes de ejecutar
}

// Event listener
rangeSelect.addEventListener('change', function() {
    fetchDashboard(this.value);
});
```

**Beneficio:**
- **Requests:** Reducción de ~70% en requests repetitivos
- **Carga del servidor:** Menos procesamiento innecesario
- **Experiencia de usuario:** Respuestas más rápidas y consistentes

**Prioridad:** Alta

---

### 9. Implementar Caché de Configuraciones de Voyager
**Ubicación:** Después de `app/Console/Commands/Install.php:34`  
**Documentado en:** `docs/dev/Install.md:541-550`

**Problema actual:** Las configuraciones de Voyager no están en caché después de la instalación.

**Impacto:** Primeras cargas del panel son más lentas.

**Optimización:**
```php
// Después de publicar assets de Voyager
$this->call('config:cache');
$this->call('route:cache');
```

**Beneficio:**
- **Tiempo de primera carga:** Reducción de ~40-60%
- **Rendimiento:** Mejora general del panel de administración
- **Escalabilidad:** Menos consultas de configuración

**Prioridad:** Media

---

### 10. Implementar Eager Loading de Relaciones
**Ubicación:** `app/Services/DashboardService.php:211-231`  
**Documentado en:** `docs/dev/dashboard_service.md:209-231`

**Problema actual:** Ya se usa eager loading, pero puede mejorarse.

**Optimización adicional:**
```php
// Actual (bueno, pero puede mejorarse)
$ultimosTramites = Tramite::with(['disponentes.person', 'inmuebles'])
    ->latest()
    ->take(5)
    ->get();

// Mejor: solo seleccionar campos necesarios
$ultimosTramites = Tramite::with([
        'disponentes.person:id,nombre,apellido_paterno,apellido_materno,display_name,full_name',
        'inmuebles:id,direccion,municipio_id'
    ])
    ->select(['id', 'nro_tramite', 'created_at', 'monto_final', 'estado', 'inmueble_id'])
    ->latest()
    ->take(5)
    ->get();
```

**Beneficio:**
- **Transferencia de datos:** Reducción de ~30-50% en datos transmitidos
- **Memoria:** Menos uso de memoria en PHP
- **Velocidad:** Consultas más rápidas (menos columnas)

**Prioridad:** Media

---

## 📊 Métricas de Rendimiento Esperadas

### Antes de las Optimizaciones
| Métrica | Valor Actual | Umbral de Alerta |
|---------|--------------|------------------|
| Tiempo de respuesta dashboard (index) | ~1200ms | > 2000ms |
| Tiempo de respuesta dashboard (fetch) | ~800ms | > 1500ms |
| Consultas SQL por request | ~15-20 | > 30 |
| Memoria por request | ~80MB | > 150MB |
| Tiempo de instalación | ~300s | > 600s |
| Logs escritos por segundo | ~50-100 | > 500 |

### Después de las Optimizaciones
| Métrica | Valor Esperado | Mejora |
|---------|--------------|--------|
| Tiempo de respuesta dashboard (index) | ~400ms | **-67%** |
| Tiempo de respuesta dashboard (fetch) | ~200ms | **-75%** |
| Consultas SQL por request | ~3-5 | **-75%** |
| Memoria por request | ~40MB | **-50%** |
| Tiempo de instalación | ~60s | **-80%** |
| Logs escritos por segundo | ~5-10 (buffered) | **-90%** |

---

## 📊 Resumen de Optimizaciones por Categoría

| Categoría | Optimizaciones | Prioridad Alta | Prioridad Media | Prioridad Baja |
|-----------|---------------|-----------------|-----------------|---------------|
| **Caching** | 3 | 2 | 1 | 0 |
| **Logging** | 3 | 2 | 1 | 0 |
| **Database** | 2 | 1 | 1 | 0 |
| **Frontend** | 1 | 1 | 0 | 0 |
| **Instalación** | 1 | 1 | 0 | 0 |
| **Total** | 10 | 7 | 3 | 0 |

---

## 📝 Archivos a Crear o Modificar

### Archivos Nuevos a Crear:
1. `app/Jobs/LogHttpRequestJob.php`
2. `app/Jobs/SeedVoyagerJob.php`
3. `app/Jobs/SeedUsersJob.php`
4. `app/Jobs/SeedRolesJob.php`
5. `app/Jobs/SeedPeopleJob.php`
6. `app/Jobs/SeedTasasJob.php`
7. `app/Observers/PagoObserver.php` (para invalidación de cache)
8. `app/Observers/TramiteObserver.php` (para invalidación de cache)
9. `app/Console/Commands/WarmDashboardCache.php`
10. `app/Logging/CustomizeFormatter.php`

### Archivos a Modificar:
1. `app/Http/Middleware/System.php` - Implementar caché de settings
2. `app/Http/Middleware/Loggin.php` - Implementar logging asíncrono
3. `app/Services/DashboardService.php` - Implementar cache tags, lazy loading, eager loading
4. `app/Console/Commands/Install.php` - Paralelizar seeders, cachear configs
5. `app/Http/Controllers/Admin/DashboardController.php` - Implementar lazy loading
6. `resources/views/vendor/voyager/dashboard/index.blade.php` - Implementar debouncing
7. `config/logging.php` - Configurar buffer de logs
8. `config/queue.php` - Configurar cola dedicada para logs
9. `config/cache.php` - Configurar tags de cache
10. `routes/web.php` - Agregar rutas para gráficos lazy

---

## 📊 Resumen de Impacto Esperado

### Porcentaje de Mejora por Optimización

| # | Optimización | Mejora Esperada | Categoría |
|---|-------------|-----------------|-----------|
| 1 | Cachear configuraciones de settings | -20-50ms/request | Caching |
| 2 | Logging asíncrono con colas | -2-5ms/request | Logging |
| 3 | Buffer de logs en memoria | -90% I/O disk | Logging |
| 4 | Lazy loading de gráficos | -50-70% tiempo carga | Database |
| 5 | Paralelizar seeders | -60-80% tiempo install | Instalación |
| 6 | Cache tags para invalidación | +30% cache hits | Caching |
| 7 | Database aggregations | -20-30% consultas | Database |
| 8 | Debouncing para fetches | -70% requests | Frontend |
| 9 | Caché de configuraciones Voyager | -40-60% primera carga | Caching |
| 10 | Eager loading mejorado | -30-50% datos | Database |

---

## 📝 Archivos de Documentación Afectados

Para actualizar tu documentación después de implementar estas optimizaciones, debes modificar:

1. **`docs/dev/System.md`** - Líneas 694-778 (optimizaciones de caché)
2. **`docs/dev/Loggin.md`** - Líneas 1094-1257 (optimizaciones de logging)
3. **`docs/dev/Install.md`** - Líneas 529-593 (optimizaciones de instalación)
4. **`docs/dev/dashboard_controller.md`** - Sección de rendimiento
5. **`docs/dev/dashboard_service.md`** - Sección de optimización de BD

---

## 🎯 Prioridad de Implementación

### 🔴 ALTA - Implementar ASAP (Mayor impacto)
1. **Cachear configuraciones de settings** (Optimización #1)
2. **Logging asíncrono con colas** (Optimización #2)
3. **Buffer de logs en memoria** (Optimización #3)
4. **Lazy loading de gráficos** (Optimización #4)
5. **Paralelizar seeders** (Optimización #5)
6. **Cache tags para invalidación** (Optimización #6)
7. **Debouncing para fetches** (Optimización #8)

### 🟠 MEDIA - Implementar pronto
8. **Database aggregations** (Optimización #7)
9. **Caché de configuraciones Voyager** (Optimización #9)
10. **Eager loading mejorado** (Optimización #10)

---

## 💡 Roadmap de Implementación

### Fase 1: Caching y Logging (Semana 1-2)
- [ ] Cachear configuraciones de settings
- [ ] Implementar logging asíncrono con colas
- [ ] Configurar buffer de logs en memoria
- [ ] Configurar cola dedicada para logs
- [ ] Implementar cache tags para invalidación

### Fase 2: Optimización de Database (Semana 3)
- [ ] Implementar lazy loading de gráficos
- [ ] Usar database aggregations
- [ ] Mejorar eager loading con select específicos
- [ ] Agregar índices necesarios
- [ ] Configurar cache warming

### Fase 3: Optimización de Instalación (Semana 4)
- [ ] Paralelizar seeders con jobs
- [ ] Cachear configuraciones de Voyager
- [ ] Optimizar vendor:publish
- [ ] Implementar progreso visual
- [ ] Validar dependencias eficientemente

### Fase 4: Optimización de Frontend (Semana 5)
- [ ] Implementar debouncing para fetches
- [ ] Optimizar carga de JavaScript
- [ ] Implementar lazy loading de imágenes
- [ ] Minificar assets
- [ ] Configurar headers de cache

---

## 📈 Métricas de Monitoreo

### Key Performance Indicators (KPIs)

#### Dashboard
```php
// En DashboardService
public function getData(Request $request): array
{
    $startTime = microtime(true);
    
    try {
        $data = $this->calculateDashboardData($request);
        
        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        
        // Monitorear si es lento
        if ($executionTime > 1000) {
            Log::warning('Dashboard response slow', [
                'execution_time_ms' => $executionTime,
                'range' => $request->input('range'),
                'cache_hit' => $this->wasFromCache(),
            ]);
        }
        
        // Incluir métricas en la respuesta
        $data['meta'] = [
            'execution_time_ms' => $executionTime,
            'cached' => $this->wasFromCache(),
            'timestamp' => now()->toISOString(),
        ];
        
        return $data;
        
    } catch (\Exception $e) {
        Log::error('Dashboard error', [
            'error' => $e->getMessage(),
            'execution_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);
        throw $e;
    }
}
```

#### Logging
```php
// Monitorear tiempo de logging
$loggingTime = microtime(true) - $requestStartTime;

if ($loggingTime > 50) {  // > 50ms
    Log::channel('performance')->warning('Slow logging operation', [
        'logging_time_ms' => $loggingTime,
        'request_url' => $request->url(),
    ]);
}
```

#### Database
```php
// Habilitar query logging en desarrollo
if (config('app.debug')) {
    DB::listen(function ($query) {
        if ($query->time > 100) {  // > 100ms
            Log::channel('performance')->warning('Slow query detected', [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time_ms' => $query->time,
            ]);
        }
    });
}
```

---

## 🔧 Herramientas de Análisis de Rendimiento

### 1. Laravel Debugbar (Desarrollo)
```bash
composer require barryvdh/laravel-debugbar --dev
```

### 2. Laravel Telescope (Desarrollo/Producción)
```bash
composer require laravel/telescope
php artisan telescope:install
php artisan migrate
```

### 3. Blackfire (Profiling en producción)
```bash
composer require blackfire/php-sdk
```

### 4. New Relic/APM (Monitoreo en tiempo real)
```bash
composer require newrelic/newrelic
```

### 5. Clockwork (Timeline de requests)
```bash
composer require itsgoingd/clockwork
```

---

## 📊 Tests de Carga Recomendados

### 1. Apache Benchmark (ab)
```bash
# Test de carga simple
ab -n 1000 -c 10 http://localhost:8000/admin/dashboard

# Test con parámetros
ab -n 1000 -c 10 "http://localhost:8000/admin/dashboard/fetchData?range=month"
```

### 2. wrk (High performance)
```bash
# Test de 10K requests con 100 conexiones
wrk -t4 -c100 -d30s http://localhost:8000/admin/dashboard
```

### 3. JMeter (Escenarios complejos)
```xml
<!-- Test plan JMeter para dashboard -->
<TestPlan>
  <ThreadGroup>
    <HTTPSamplerProxy guiclass="HttpTestSampleGui">
      <stringProp name="HTTPSampler.domain">localhost</stringProp>
      <stringProp name="HTTPSampler.port">8000</stringProp>
      <stringProp name="HTTPSampler.path">/admin/dashboard</stringProp>
    </HTTPSamplerProxy>
  </ThreadGroup>
</TestPlan>
```

### 4. Artisan Command para Tests
```php
// app/Console/Commands/BenchmarkDashboard.php
class BenchmarkDashboard extends Command
{
    protected $signature = 'benchmark:dashboard {requests=1000} {concurrency=10}';
    protected $description = 'Ejecutar benchmark del dashboard';
    
    public function handle()
    {
        $requests = $this->argument('requests');
        $concurrency = $this->argument('concurrency');
        
        $this->info("Ejecutando benchmark: {$requests} requests, {$concurrency} concurrentes");
        
        // Implementar benchmark
        // ...
    }
}
```
