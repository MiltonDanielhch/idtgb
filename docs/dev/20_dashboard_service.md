# Documentación Técnica - Servicio DashboardService

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Servicio](#arquitectura-del-servicio)
3. [Métodos Públicos](#métodos-públicos)
4. [Cálculo de KPIs](#cálculo-de-kpis)
5. [Generación de Gráficos](#generación-de-gráficos)
6. [Sistema de Cache](#sistema-de-cache)
7. [Uso del Servicio](#uso-del-servicio)
8. [Ejemplos de Código](#ejemplos-de-código)
9. [Consideraciones Importantes](#consideraciones-importantes)

---

## 🎯 Introducción

El **DashboardService** es el servicio central encargado de proveer todos los datos estadísticos, indicadores clave de rendimiento (KPIs) y datos para gráficos del dashboard del sistema ITGB. Este servicio centraliza toda la lógica de cálculo de estadísticas, garantizando consistencia entre diferentes vistas y mejorando el rendimiento mediante el uso inteligente de cache.

### Propósito
- Centralizar la lógica de cálculo de estadísticas del dashboard
- Proveer KPIs dinámicos por rango de fecha (hoy, semana, mes, año)
- Calcular tendencias comparativas vs período anterior
- Generar datos para gráficos (recaudación, tipos de trámite, estados)
- Optimizar el rendimiento mediante cache inteligente (5 minutos TTL)

### Importancia en el Sistema ITGB
El dashboard es la vista principal de operación para administradores y supervisores. Sin este servicio:
- Cada vista necesitaría sus propias consultas, duplicando lógica
- El rendimiento degradaría sin cache
- No habría una fuente única de verdad para las estadísticas
- Los datos podrían ser inconsistentes entre diferentes secciones

---

## 🏗️ Arquitectura del Servicio

### Ubicación
**Archivo:** `app/Services/DashboardService.php`

### Dependencias
```php
use App\Models\Pago;
use App\Models\Tramite;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
```

### Estructura del Servicio
```
DashboardService
├─ Métodos Públicos
│  ├─ getData(Request $request)            → Obtiene todos los datos del dashboard
│  └─ getJsonData(Request $request)       → Obtiene datos formateados para JSON
│
├─ Métodos Privados de Cálculo
│  ├─ Cálculo de KPIs por rango (actual y anterior)
│  ├─ Generación de datos para gráficos
│  ├─ Obtención de últimos trámites
│  └─ Cálculo de tendencias
│
└─ Métodos Auxiliares
   └─ fillDateGaps() → Rellena huecos en colecciones de fechas
```

### Flujo de Datos
```
DashboardController
    ↓
getData(request)
    ↓
1. Determinar rango de fechas (hoy/semana/mes/año)
2. Calcular KPIs del período actual (con cache)
3. Calcular KPIs del período anterior (con cache)
4. Generar datos para gráficos
5. Obtener últimos trámites
6. Calcular tendencias comparativas
    ↓
Retorno de array con todos los datos
```

---

## 🎮 Métodos Públicos

### 1. `getData(Request $request)` - Método Principal

**Descripción:** Obtiene todos los datos del dashboard para un rango de fechas específico.

**Firma:**
```php
public function getData(Request $request): array
```

**Parámetros:**
- `$request`: HTTP Request con el parámetro `range` (default: 'month')

**Retorno:**
- `array`: Arreglo asociativo con todos los KPIs, tendencias, gráficos y datos de trámites

**Proceso:**

1. **Determinación del rango:**
```php
$range = $request->input('range', 'month');
// Valores aceptados: 'today', 'week', 'month', 'year'
```

2. **Cálculo de fechas para el rango actual:**
```php
switch ($range) {
    case 'today':
        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();
        $prevStartDate = $now->copy()->subDay()->startOfDay();
        $prevEndDate = $now->copy()->subDay()->endOfDay();
        break;
    case 'week':
        $startDate = $now->copy()->startOfWeek();
        $endDate = $now->copy()->endOfWeek();
        $prevStartDate = $now->copy()->subWeek()->startOfWeek();
        $prevEndDate = $now->copy()->subWeek()->endOfWeek();
        break;
    case 'year':
        $startDate = $now->copy()->startOfYear();
        $endDate = $now->copy()->endOfYear();
        $prevStartDate = $now->copy()->subYear()->startOfYear();
        $prevEndDate = $now->copy()->subYear()->endOfYear();
        break;
    case 'month':
    default:
        $startDate = $now->copy()->startOfMonth();
        $endDate = $now->copy()->endOfMonth();
        $prevStartDate = $now->copy()->subMonth()->startOfMonth();
        $prevEndDate = $now->copy()->subMonth()->endOfMonth();
        break;
}
```

3. **Obtención de KPIs (con cache):**
```php
$recaudadoPeriodo = cache()->remember($cachePrefix . ':recaudadoPeriodo', $ttl, function() use ($startDate, $endDate) {
    return Pago::where('estado', 'Aplicado')
        ->whereBetween('fecha_pago', [$startDate, $endDate])
        ->sum('monto');
});

$tramitesPeriodo = cache()->remember($cachePrefix . ':tramitesPeriodo', $ttl, function() use ($startDate, $endDate) {
    return Tramite::whereBetween('created_at', [$startDate, $endDate])->count();
});

$tramitesFinalizadosPeriodo = cache()->remember($cachePrefix . ':tramitesFinalizadosPeriodo', $ttl, function() use ($startDate, $endDate) {
    return Tramite::where('estado', 'Finalizado')
        ->whereBetween('updated_at', [$startDate, $endDate])
        ->count();
});

$tramitesPendientes = cache()->remember($cachePrefix . ':tramitesPendientes', $ttl, function() {
    return Tramite::whereIn('estado', ['Borrador', 'Observado'])->count();
});
```

4. **Obtención de datos del período anterior para tendencias:**
```php
$recaudadoPeriodoAnterior = cache()->remember($cachePrefix . ':recaudadoPeriodoAnterior', $ttl, function() use ($prevStartDate, $prevEndDate) {
    return Pago::where('estado', 'Aplicado')
        ->whereBetween('fecha_pago', [$prevStartDate, $prevEndDate])
        ->sum('monto');
});

$tramitesPeriodoAnterior = cache()->remember($cachePrefix . ':tramitesPeriodoAnterior', $ttl, function() use ($prevStartDate, $prevEndDate) {
    return Tramite::whereBetween('created_at', [$prevStartDate, $prevEndDate])->count();
});

// ... similar para otros KPIs anteriores
```

5. **Generación de gráficos y tendencias:**
```php
$tramitesPorEstado = cache()->remember($cachePrefix . ':tramitesPorEstado', $ttl, function() use ($startDate, $endDate) {
    return Tramite::whereBetween('created_at', [$startDate, $endDate])
        ->select('estado', DB::raw('count(*) as total'))
        ->groupBy('estado')
        ->pluck('total', 'estado')
        ->toArray();
});

$tramitesPorTipo = cache()->remember($cachePrefix . ':tramitesPorTipo', $ttl, function() use ($startDate, $endDate) {
    return Tramite::whereBetween('tramites.created_at', [$startDate, $endDate])
        ->join('tipos_transmision', 'tramites.tipo_transmision_id', '=', 'tipos_transmision.id')
        ->select('tipos_transmision.nombre as tipo', DB::raw('count(tramites.id) as total'))
        ->groupBy('tipos_transmision.nombre')
        ->pluck('total', 'tipo')
        ->toArray();
});

$recaudacionGrouped = cache()->remember($cachePrefix . ':recaudacionGrouped', $ttl, function() use ($startDate, $endDate) {
    return Pago::select(DB::raw('SUM(monto) as total'), DB::raw("DATE_FORMAT(fecha_pago, '$dateFormat') as period"))
        ->where('estado', 'Aplicado')
        ->whereBetween('fecha_pago', [$startDate, $endDate])
        ->groupBy('period')
        ->pluck('total', 'period')
        ->toArray();
});
```

6. **Obtención de últimos trámites:**
```php
$ultimosTramites = cache()->remember($cachePrefix . ':ultimosTramites', $ttl, function() {
    return Tramite::with(['disponentes.person', 'inmuebles'])
        ->latest()
        ->take(5)
        ->get()
        ->map(function($tramite) {
            $firstDisponente = $tramite->disponentes->first();
            $person = $firstDisponente ? $firstDisponente->person : null;
            
            return [
                'id' => $tramite->id,
                'nro_tramite' => $tramite->nro_tramite,
                'contribuyente' => $person ? $person->display_name ?? $person->full_name ?? 'N/A' : 'N/A',
                'created_at' => $tramite->created_at->format('d/m/Y'),
                'monto_final' => $tramite->monto_final,
                'estado' => $tramite->estado
            ];
        })
        ->toArray();
});
```

7. **Cálculo de tendencias comparativas:**
```php
$trends = [
    'recaudacion' => ['percentage' => $this->calculateTrend($recaudadoPeriodo, $recaudadoPeriodoAnterior)],
    'tramites' => ['percentage' => $this->calculateTrend($tramitesPeriodo, $tramitesPeriodoAnterior)],
    'finalizados' => ['percentage' => $this->calculateTrend($tramitesFinalizadosPeriodo, $tramitesFinalizadosPeriodoAnterior)],
    'pendientes' => ['percentage' => $this->calculateTrend($tramitesPendientes, $tramitesPendientesAnterior)],
];
```

8. **Renderizado de HTML para tabla:**
```php
$ultimosTramitesHtml = view('vendor.voyager.partials.dashboard-tramites-table', ['ultimosTramites' => $ultimosTramites])->render();
```

**Retorno final:**
```php
return [
    'kpiLabel' => $kpiLabel,
    'recaudadoPeriodo' => $recaudadoPeriodo,
    'tramitesPeriodo' => $tramitesPeriodo,
    'tramitesFinalizadosPeriodo' => $tramitesFinalizadosPeriodo,
    'tramitesPendientes' => $tramitesPendientes,
    'trends' => $trends,
    'ultimosTramitesHtml' => $ultimosTramitesHtml,
    'recaudacionPeriodoData' => $recaudacionPeriodoData,
    'tramitesPorTipo' => $tramitesPorTipo,
    'tramitesPorEstado' => $tramitesPorEstado,
    'comparacionAnualData' => $comparacionAnualData,
];
```

---

### 2. `getJsonData(Request $request)` - Método para AJAX

**Descripción:** Obtiene los datos del dashboard formateados para respuesta JSON (usado por el DashboardController).

**Firma:**
```php
public function getJsonData(Request $request): array
```

**Proceso:**
1. Llama a `$this->getData($request)`
2. Formatea los números con separadores de miles
3. Convierte colecciones de fechas a arreglos simples
4. Prepara datos para gráficos Chart.js u otras librerías

**Formateo de datos:**
```php
return [
    'kpiLabel' => $data['kpiLabel'],
    'recaudadoPeriodoFormatted' => number_format($data['recaudadoPeriodo'], 2, ',', '.'),
    'tramitesPeriodoFormatted' => number_format($data['tramitesPeriodo'], 0, ',', '.'),
    'tramitesFinalizadosPeriodoFormatted' => number_format($data['tramitesFinalizadosPeriodo'], 0, ',', '.'),
    'tramitesPendientesFormatted' => number_format($data['tramitesPendientes'], 0, ',', '.'),
    'trends' => $data['trends'],
    'ultimosTramitesHtml' => $data['ultimosTramitesHtml'],
    'recaudacionPeriodoData' => [
        'labels' => $data['recaudacionPeriodoData']->keys()->map(fn($item) => Carbon::parse($item)->format('d M'))->values(),
        'values' => $data['recaudacionPeriodoData']->values(),
    ],
    'tramitesPorTipo' => [
        'labels' => $data['tramitesPorTipo']->keys(),
        'values' => $data['tramitesPorTipo']->values(),
    ],
    'tramitesPorEstado' => [
        'labels' => $data['tramitesPorEstado']->keys(),
        'values' => $data['tramitesPorEstado']->values(),
    ],
    'comparacionAnualData' => $data['comparacionAnualData'],
];
```

---

## 📊 Cálculo de KPIs

### KPIs Principales

| KPI | Descripción | Fuente de Datos |
|------|------------|----------------|
| **Recaudación Período** | Suma de montos de pagos aplicados en el rango | `pago.monto` WHERE estado='Aplicado' |
| **Trámites Período** | Cantidad de trámites creados en el rango | `tramites.created_at` BETWEEN |
| **Trámites Finalizados Período** | Trámites en estado 'Finalizado' en el rango | `tramites.estado='Finalizado'` AND `tramites.updated_at` BETWEEN |
| **Trámites Pendientes** | Trámites en estado 'Borrador' u 'Observado' (acumulado) | `tramites.estado IN ('Borrador', 'Observado')` |

### Cálculo de KPIs

```php
// Recaudación en el período actual
$recaudadoPeriodo = Pago::where('estado', 'Aplicado')
    ->whereBetween('fecha_pago', [$startDate, $endDate])
    ->sum('monto');

// Trámites creados en el período actual
$tramitesPeriodo = Tramite::whereBetween('created_at', [$startDate, $endDate])
    ->count();

// Trámites finalizados en el período actual
$tramitesFinalizadosPeriodo = Tramite::where('estado', 'Finalizado')
    ->whereBetween('updated_at', [$startDate, $endDate])
    ->count();

// Trámites pendientes (acumulado, independiente del rango)
$tramitesPendientes = Tramite::whereIn('estado', ['Borrador', 'Observado'])
    ->count();
```

---

## 📈 Generación de Gráficos

### 1. Gráfico de Recaudación (Temporal)

**Propósito:** Muestra la recaudación a lo largo del rango seleccionado.

**Formato de datos:**
- **Por día** (meses): Agrupado por `Y-m-d`
- **Por mes** (años): Agrupado por `Y-m`

```php
$recaudacionGrouped = Pago::select(
        DB::raw('SUM(monto) as total'),
        DB::raw("DATE_FORMAT(fecha_pago, '$dateFormat') as period"))
    ->where('estado', 'Aplicado')
    ->whereBetween('fecha_pago', [$startDate, $endDate])
    ->groupBy('period')
    ->pluck('total', 'period')
    ->toArray();
```

**Ejemplo de resultado (rango mes actual):**
```php
[
    '2025-01' => 15000.00,
    '2025-02' => 18500.00,
    '2025-03' => 12000.00,
]
```

### 2. Gráfico de Trámites por Tipo de Transmisión

**Propósito:** Muestra la distribución de trámites por tipo (Herencia, Donación, etc.).

```php
$tramitesPorTipo = Tramite::whereBetween('tramites.created_at', [$startDate, $endDate])
    ->join('tipos_transmision', 'tramites.tipo_transmision_id', '=', 'tipos_transmision.id')
    ->select('tipos_transmision.nombre as tipo', DB::raw('count(tramites.id) as total'))
    ->groupBy('tipos_transmision.nombre')
    ->pluck('total', 'tipo')
    ->toArray();
```

**Ejemplo de resultado:**
```php
[
    'Herencia' => 150,
    'Donación' => 45,
    'Legado' => 12,
]
```

### 3. Gráfico de Trámites por Estado

**Propósito:** Muestra el estado actual de los trámites del rango.

```php
$tramitesPorEstado = Tramite::whereBetween('created_at', [$startDate, $endDate])
    ->select('estado', DB::raw('count(*) as total'))
    ->groupBy('estado')
    ->pluck('total', 'estado')
    ->toArray();
```

**Ejemplo de resultado:**
```php
[
    'Borrador' => 25,
    'Pagado' => 18,
    'Finalizado' => 12,
    'Observado' => 5,
]
```

### 4. Comparación Anual (Actual vs Anterior)

**Propósito:** Muestra la evolución año tras año de la recaudación.

```php
// Recaudación del año actual
$recaudacionAnioActual = Pago::select(DB::raw('SUM(monto) as total'))
    ->where('estado', 'Aplicado')
    ->whereYear('fecha_pago', now()->year)
    ->groupBy(DB::raw('MONTH(fecha_pago) as mes'))
    ->orderBy('mes')
    ->pluck('total', 'mes')
    ->all();

// Recaudación del año anterior
$recaudacionAnioAnterior = Pago::select(DB::raw('SUM(monto) as total'))
    ->where('estado', 'Aplicado')
    ->whereYear('fecha_pago', now()->subYear()->year)
    ->groupBy(DB::raw('MONTH(fecha_pago) as mes'))
    ->orderBy('mes')
    ->pluck('total', 'mes')
    ->all();

// Formatear datos para comparación
$comparacionAnualData = [
    'actual' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioActual)),
    'anterior' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioAnterior)),
];
```

---

## 💾 Sistema de Cache

### Estrategia de Cache

**Prefijo:** `dashboard:{range}:{fechaInicio}:{fechaFin}`

**TTL (Time To Live):** 5 minutos desde la generación del caché

**Propósito:** El cache permite optimizar consultas complejas que de otra forma se ejecutarían en cada petición.

### Claves de Cache

| Clave | Descripción |
|-------|-------------|
| `:recaudadoPeriodo` | Recaudación del período actual |
| `:tramitesPeriodo` | Trámites creados en el período |
| `:tramitesFinalizadosPeriodo` | Trámites finalizados en el período |
| `:tramitesPendientes` | Trámites pendientes (acumulado) |
| `:recaudadoPeriodoAnterior` | Recaudación del período anterior |
| `:tramitesPeriodoAnterior` | Trámites creados en período anterior |
| `:tramitesFinalizadosPeriodoAnterior` | Trátes finalizados en período anterior |
| `:tramitesPorEstado` | Datos para gráfico de estados |
| `:tramitesPorTipo` | Datos para gráfico de tipos de transmisión |
| `:recaudacionGrouped` | Datos para gráfico temporal |
| `:ultimosTramites` | Últimos 5 trámites |

### Implementación

```php
$cachePrefix = 'dashboard:' . $range . ':' . $startDate->format('Ymd') . ':' . $endDate->format('Ymd');
$ttl = now()->addMinutes(5);

// Uso típico
$resultado = cache()->remember($cachePrefix . ':clave', $ttl, function() use ($param1, $param2) {
    // Consulta compleja
    return Modelo::where(...)->...->get();
});
```

### Invalidation de Cache

El servicio no tiene un método de invalidación propia. La invalidación se maneja externamente a través de:

1. **DashboardCacheInvalidator:** Servicio especializado para invalidar cache de manera inteligente.
2. **PagosObserver:** Invalida caché cuando se crean/actualizan pagos.
3. **TramiteObserver:** Invalida caché cuando se modifican trámites.

---

## 🔗 Uso del Servicio

### Desde el DashboardController

**Ubicación:** `app/Http/Controllers/Admin/DashboardController.php`

```php
public function index()
{
    return view('vendor.voyager.index');
}

public function fetchData(Request $request)
{
    $data = app(DashboardService::class)->getData($request);
    return response()->json($data);
}
```

### Desde Otros Controladores

Cualquier controlador que necesite mostrar estadísticas puede usar el servicio:

```php
use App\Services\DashboardService;

public function miVista()
{
    $service = app(DashboardService::class);
    $data = $service->getData(request()->merge(['range' => 'month']));
    
    return view('mi.vista', compact('data'));
}
```

### Directamente en Vistas

```blade
@php
use App\Services\DashboardService;
$service = app(DashboardService::class);
$data = $service->getData(new Illuminate\Http\Request(['range' => 'week']));
@endphp
```

---

## 💻 Ejemplos de Código

### Ejemplo 1: Obtener KPIs del mes actual

```php
$service = app(DashboardService::class);
$request = new Request(['range' => 'month']);

$data = $service->getData($request);

echo "Recaudación mes actual: Bs. " . number_format($data['recaudadoPeriodo'], 2, ',', '.');
echo "Trámites creados: " . $data['tramitesPeriodo'];
echo "Trámites finalizados: " . $data['tramitesFinalizadosPeriodo'];
echo "Trámites pendientes: " . $data['tramitesPendientes'];
```

### Ejemplo 2: Obtener datos para gráficos

```php
$service = app(DashboardService::class);
$request = new Request(['range' => 'year']);

$data = $service->getData($request);

// Gráfico de recaudación mensual
$recaudacionPorMes = collect($data['recaudacionPeriodoData']);
echo "<pre>";
print_r($recaudacionPorMes);
echo "</pre>";

// Gráfico por tipo de transmisión
$tramitesPorTipo = collect($data['tramitesPorTipo']);
echo "<pre>";
print_r($tramitesPorTipo);
echo "</pre>";
```

### Ejemplo 3: Comparar año actual vs anterior

```php
$service = app(DashboardService::class);
$request = new Request(['range' => 'year']);

$data = $service->getData($request);

$comparacion = $data['comparacionAnualData'];

echo "Enero - Actual: Bs. " . number_format($comparacion['actual'][0], 2, ',', '.');
echo "Enero - Anterior: Bs. " . number_format($comparacion['anterior'][0], 2, ',', '.');
```

### Ejemplo 4: Verificar tendencias

```php
$service = app(DashboardService::class);
$request = new Request(['range' => 'month']);

$data = $service->getData($request);

$tendencias = $data['trends'];

echo "Tendencia recaudación: " . $trendencias['recaudacion']['percentage'] . "%\n";
echo "Tendencia trámites: " . $trendencias['tramites']['percentage'] . "%\n";
echo "Tendencia finalizados: " . $trendencias['finalizados']['percentage'] . "%\n";
```

### Ejemplo 5: Invalidar Cache desde Observers

```php
use App\Services\DashboardCacheInvalidator;

// En PagoObserver.php
public function created(Pago $pago)
{
    app(DashboardCacheInvalidator::class)->clearForPago($pago);
}

// En TramiteObserver.php
public function updated(Tramite $tramite)
{
    if ($tramite->isDirty(['estado', 'monto_final', 'total_idtgb'])) {
        app(DashboardCacheInvalidator::class)->clearForTramite($tramite);
    }
}
```

### Ejemplo 6: Obtener datos JSON para AJAX

```javascript
// En vista Blade
fetch('/admin/dashboard/data?range=month')
    .then(response => response.json())
    .then(data => {
        // data.kpiLabel = 'Este Mes'
        // data.recaudacionPeriodoFormatted = '15,000.00 Bs.'
        // data.trends = { recaudacion: {...}, tramites: {...}, ... }
        
        // Actualizar UI
        document.getElementById('kpiLabel').textContent = data.kpiLabel;
        document.getElementById('recaudacion').textContent = data.recaudadoPeriodoFormatted;
        
        // Renderizar gráficos
        renderChart('recaudacionChart', data.recaudacionPeriodoData);
        renderChart('tiposChart', data.tramitesPorTipo);
    });
```

### Ejemplo 7: Usar directamente el servicio desde Tinker

```bash
php artisan tinker
```

```php
>>> use App\Services\DashboardService;
>>> $service = app(DashboardService::class);
>>> $data = $service->getData(new Illuminate\Http\Request(['range' => 'week']));
>>> print_r($data);
=> [
    'kpiLabel' => 'Esta Semana',
    'recaudadoPeriodo' => 5000.00,
    'tramitesPeriodo' => 12,
    'tramitesFinalizadosPeriodo' => 8,
    'tramitesPendientes' => 145,
    'trends' => [...],
    'ultimosTramitesHtml' => '<table>...</table>',
    ...
]
```

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.0.0 (22 de enero de 2026) ✅

El servicio `DashboardService` está implementado correctamente y sigue buenas prácticas:

- ✅ Centralización de toda lógica de cálculo de estadísticas
- ✅ Caché inteligente con TTL de 5 minutos
- ✅ Rangos dinámicos (today, week, month, year)
- ✅ Tendencias comparativas vs período anterior
- ✅ Datos formateados para JSON y vistas
- ✅ Método `fillDateGaps()` para rellenar huecos en gráficos temporales
- ✅ Separación entre `getData()` y `getJsonData()`
- ✅ Integración con `DashboardCacheInvalidator` corregida

**Nota:** Los bugs críticos de `DashboardCacheInvalidator` han sido corregidos en v2.0.0, lo cual asegura que este servicio funcione correctamente al invalidar la caché.

### 🐛 Bugs Identificados (0/0) ✅

No se han identificado bugs críticos en el servicio actual.

### 🚀 Mejoras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **MEDIA** | Manejo de errores | Agregar try-catch en consultas de base de datos |
| **MEDIA** | Logging | Agregar logs de operaciones para debugging |
| **MEDIA** | Validar datos antes de consultar | Validar que haya datos antes de calcular |
| **BAJA** | Tests unitarios | Agregar tests unitarios para todos los métodos |
| **BAJA** | Cache tags | Usar cache tags para invalidación más eficiente |
| **BAJA** | Batch invalidation | Implementar invalidación por lotes para mejor rendimiento |
| **BAJA** | Paginación en gráficos | Implementar paginación para grandes volúmenes de datos |
| **BAJA** | Métricas de rendimiento | Agregar monitoreo de tiempos de ejecución |

### 📝 Mejora de Manejo de Errores (Prioridad MEDIA)

```php
// app/Services/DashboardService.php

public function getData(Request $request): array
{
    try {
        // ... lógica actual ...
        
        return $finalData;
    } catch (\Exception $e) {
        Log::error('Error al obtener datos del dashboard', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'range' => $request->input('range', 'month'),
        ]);
        
        // Retornar datos vacíos en caso de error
        return [
            'kpiLabel' => 'Error',
            'recaudadoPeriodo' => 0,
            'tramitesPeriodo' => 0,
            // ... otros campos con valores por defecto ...
        ];
    }
}
```

### 📝 Mejora de Logging (Prioridad MEDIA)

```php
// app/Services/DashboardService.php

use Illuminate\Support\Facades\Log;

public function getData(Request $request): array
{
    $range = $request->input('range', 'month');
    $startTime = microtime(true);
    
    // ... lógica actual ...
    
    $executionTime = (microtime(true) - $startTime) * 1000;
    
    if ($executionTime > 1000) {
        Log::warning('Dashboard response slow', [
            'execution_time_ms' => $executionTime,
            'range' => $range,
            'cache_keys' => $cachePrefix,
        ]);
    }
    
    return $finalData;
}
```

### 📝 Historial de Cambios

### v1.0.0 (22 de enero de 2026)
**Estado Inicial:**
- ✅ Servicio `DashboardService` implementado
- ✅ Método `getData()` para obtener todos los datos del dashboard
- ✅ Método `getJsonData()` para obtener datos formateados para JSON
- ✅ Caché inteligente con TTL de 5 minutos
- ✅ Rangos dinámicos (today, week, month, year)
- ✅ Tendencias comparativas vs período anterior
- ✅ Generación de gráficos (recaudación, tipos, estados)
- ✅ Tabla de últimos trámites
- ✅ Método `fillDateGaps()` para rellenar huecos en gráficos temporales
- ✅ Comparación anual (actual vs anterior)

**Archivos Existentes:**
- `app/Services/DashboardService.php` - Servicio principal de datos del dashboard

**Beneficios de Implementación:**
- Centralización de toda lógica de estadísticas
- Mejor rendimiento mediante caché
- Datos consistentes en todas las vistas
- Fácil extensión para nuevas métricas

---

## ⚠️ Consideraciones Importantes

### 1. Dependencia de Fechas en el Sistema

**Fecha de referencia:** Todos los cálculos dependen de la fecha actual (`Carbon::now()`). Asegúrate de que:
- El servidor tenga la fecha y hora correcta (timezone)
- Los datos de UFVs estén actualizados para las fechas de consulta
- La hora del servidor coincida con la zona horaria del negocio

### 2. Estados de Trámites

**Estados considerados:**

| Estado | Significado en KPIs |
|--------|----------------------|
| `Aplicado` | Pagos aplicados (usado en recaudación) |
| `Borrador` | Trámites en proceso (pendiente) |
| `Observado` | Trámites con observaciones (pendiente) |
| `Finalizado` | Trámites completados (para conteos) |
| `Pagado` | Pagado total recibido |
| `Anulado` | No se considera en cálculos |

**Trámites pendientes:** Son los que están en estado 'Borrador' u 'Observado', independientemente del rango de fechas.

### 3. Rangos de Fecha Disponibles

| Rango | Período | Fechas del Período Actual |
|-------|---------|-------------------------|
| `today` | Hoy | Desde inicio del día actual hasta fin del día actual |
| `week` | Esta Semana | Desde lunes de esta semana hasta domingo de esta semana |
| `month` | Este Mes | Desde día 1 del mes actual hasta último día del mes actual |
| `year` | Este Año | Desde 1 de enero hasta 31 de diciembre |

### 4. Cache Inconsistente

**Riesgo:** El cache se invalida externamente. Si los datos cambian pero el cache no se invalida:
- El dashboard mostrará datos obsoletos
- Los KPIs serán incorrectos

**Solución:** Siempre usar `DashboardCacheInvalidator` después de modificaciones que afecten estadísticas.

### 5. Formato de Moneda

**Formato:** Todos los montos monetarios se muestran con:
- 2 decimales
- Separador de miles: `,`
- Separador decimal: `.`

```php
number_format($monto, 2, ',', '.')
// Ej: number_format(15000.50, 2, ',', '.') → '15,000.50'
```

### 6. Rendimiento y Escalabilidad

**Consideraciones:**
- El cache mejora el rendimiento pero consume memoria
- Para grandes volúmenes de datos, considerar paginación en gráficos
- Las consultas con `whereBetween` en fechas son indexadas, por lo que son eficientes
- Las consultas de conteo (`count(*)`) pueden ser costosas en tablas muy grandes

### 7. Zonas Horarias

**Importante:** Asegúrate de que:
- El servidor tenga la zona horaria correcta
- Las fechas de `fecha_pago` y `created_at` estén en la misma zona horaria
- Las comparaciones de períodos (`subMonth()`, `subYear()`) sean correctas

### 8. Manejo de Errores

**Actualmente:** El servicio no maneja excepciones explícitamente. Si una consulta falla:
- Se lanzará una excepción 500
- El usuario verá un error genérico

**Mejora sugerida:** Implementar manejo de errores con `try-catch` y logs.

### 9. Validez de Datos

**Asumiciones:**
- Existen registros en la base de datos
- Los estados de trámites son válidos
- Los montos son positivos o cero
- Las fechas de pago son razonables

**Mejora sugerida:** Agregar validaciones básicas antes de consultar.

---

## 🎯 Conclusión

El servicio **DashboardService** es el corazón del sistema de estadísticas del sistema ITGB. Centraliza toda la lógica de cálculo de KPIs, garantizando:

1. **Consistencia:** Una sola fuente de verdad para todas las estadísticas
2. **Rendimiento:** Cache inteligente de 5 minutos para consultas frecuentes
3. **Flexibilidad:** Rangos dinámicos (hoy, semana, mes, año)
4. **Comparatividad:** Tendencias automáticas vs período anterior
5. **Visualización:** Datos pre-formateados para múltiples tipos de gráficos

El servicio es crítico para la toma de decisiones administrativas y permite que administradores y supervisores tengan una visión clara del rendimiento del sistema.

**Última actualización:** Enero 2026

**Versión:** 1.0.0

**Mantenedor:** Equipo de Desarrollo ITGB
