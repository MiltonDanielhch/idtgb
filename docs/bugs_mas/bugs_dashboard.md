# Bugs del Sistema ITGB - Dashboard (Controller + Service)

**Fuente Principal:** `docs/dev/dashboard_controller.md` (líneas 704-937) y `docs/dev/dashboard_service.md`

## 🐛 Bugs Críticos

### 1. Validación Inexistente del Parámetro `range`
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

**Impacto:** 
- Posibles errores de Carbon::parse() con fechas inválidas
- Datos incorrectos mostrados al usuario
- Exposición a inyección de parámetros

**Coincidencia en:** Este bug está relacionado con **dashboard_service.md** porque el servicio también asume que el rango es válido sin validar.

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

### 2. Posible División por Cero en Cálculo de Tendencias
**Ubicación del bug:** `app/Services/DashboardService.php`, método `calculateTrend()`  
**Documentado en:** `docs/dev/dashboard_service.md` (inferido de cálculo de tendencias en línea 35-41)

**Problema:**
```php
// En el cálculo de tendencias
$percentage = (($currentValue - $previousValue) / $previousValue) * 100;
```

Si `$previousValue` es 0, esto causará una división por cero.

**Impacto:**
- Error 500 en el dashboard
- Datos de tendencias no mostrados
- El usuario no puede comparar períodos

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

---

### 3. Falta de Manejo de Errores en Consultas
**Ubicación del bug:** `app/Services/DashboardService.php`  
**Documentado en:** `docs/dev/dashboard_service.md` (todo el método `getData()`)

**Problema:** Las consultas a la base de datos no están envueltas en bloques `try-catch`. Si una consulta falla por cualquier razón:

- Conexión perdida
- Tabla inexistente
- Query mal formada
- Timeout

El servicio lanzará una excepción 500 sin manejo adecuado.

**Impacto:**
- Error 500 en el dashboard
- Mensaje de error genérico sin contexto
- Dificultad de debugging para desarrolladores

**Coincidencia en:** Este problema también está mencionado en **dashboard_controller.md** como riesgo en la sección de "Consideraciones Importantes".

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

---

### 4. Inconsistencia de Fechas en Cálculo de Comparación Anual
**Ubicación del bug:** `app/Services/DashboardService.php`, cálculo de comparación anual  
**Documentado en:** `docs/dev/dashboard_service.md` (líneas 422-447)

**Problema:**
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
```

Si no hay datos para un mes específico en cualquiera de los años, el array tendrá huecos que pueden causar errores en el gráfico.

**Ejemplo vulnerable:**
```php
$actual = [15000, null, 18500, 12000]; // Mes 2 sin datos
$anterior = [12000, 14000, null, 10000]; // Mes 3 sin datos

// Esto causará errores al renderizar el gráfico
```

**Solución:**
```php
// Usar array_replace para llenar huecos con 0
$recaudacionAnioActual = Pago::select(DB::raw('SUM(monto) as total'))
    ->where('estado', 'Aplicado')
    ->whereYear('fecha_pago', now()->year)
    ->groupBy(DB::raw('MONTH(fecha_pago) as mes'))
    ->orderBy('mes')
    ->pluck('total', 'mes')
    ->all();

$recaudacionAnioAnterior = Pago::select(DB::raw('SUM(monto) as total'))
    ->where('estado', 'Aplicado')
    ->whereYear('fecha_pago', now()->subYear()->year)
    ->groupBy(DB::raw('MONTH(fecha_pago) as mes'))
    ->orderBy('mes')
    ->pluck('total', 'mes')
    ->all();

// Rellenar meses faltantes con 0
$comparacionAnualData = [
    'actual' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioActual)),
    'anterior' => array_values(array_replace(array_fill(1, 12, 0), $recaudacionAnioAnterior)),
];
```

---

## 🔍 Problemas Importantes

### 5. Relaciones No Cargadas en Últimos Trámites
**Ubicación del bug:** `app/Services/DashboardService.php` (obtención de últimos trámites)  
**Documentado en:** `docs/dev/dashboard_service.md:209-231`

**Problema:**
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
                'contribuyente' => $person ? $person->display_name ?? $person->full_name ?? 'N/A' : 'N/A',
                // ...
            ];
        })
        ->toArray();
});
```

Si las relaciones `disponentes`, `person`, o `inmuebles` no existen o están corruptas, se causará un error.

**Impacto:**
- Error 500 al cargar el dashboard
- Tabla de últimos trámites no muestra datos
- Posible N+1 query problem si las relaciones no están cargadas correctamente

**Solución:**
```php
$ultimosTramites = cache()->remember($cachePrefix . ':ultimosTramites', $ttl, function() {
    return Tramite::with(['disponentes.person', 'inmuebles'])
        ->latest()
        ->take(5)
        ->get()
        ->map(function($tramite) {
            try {
                $firstDisponente = $tramite->disponentes->first();
                $person = $firstDisponente ? $firstDisponente->person : null;
                
                return [
                    'id' => $tramite->id,
                    'nro_tramite' => $tramite->nro_tramite,
                    'contribuyente' => $person 
                        ? ($person->display_name ?? $person->full_name ?? 'N/A')
                        : 'N/A',
                    'created_at' => $tramite->created_at->format('d/m/Y'),
                    'monto_final' => $tramite->monto_final ?? 0,
                    'estado' => $tramite->estado,
                ];
            } catch (\Exception $e) {
                // Si falla un trámite específico, mostrar datos mínimos
                return [
                    'id' => $tramite->id,
                    'nro_tramite' => $tramite->nro_tramite ?? 'N/A',
                    'contribuyente' => 'N/A',
                    'created_at' => optional($tramite->created_at)->format('d/m/Y') ?? 'N/A',
                    'monto_final' => 0,
                    'estado' => $tramite->estado ?? 'Desconocido',
                ];
            }
        })
        ->toArray();
});
```

---

### 6. Formato de Fecha Incorrecto en Gráficos
**Ubicación del bug:** `app/Services/DashboardService.php`, método `getJsonData()`  
**Documentado en:** `docs/dev/dashboard_service.md:291-295`

**Problema:**
```php
'recaudacionPeriodoData' => [
    'labels' => $data['recaudacionPeriodoData']->keys()->map(fn($item) => Carbon::parse($item)->format('d M'))->values(),
    'values' => $data['recaudacionPeriodoData']->values(),
],
```

El formato de fecha depende de cómo se agrupan los datos, pero no se ajusta según el rango seleccionado. Por ejemplo:
- Para `today`: debería mostrar horas
- Para `week`: debería mostrar días
- Para `month`: debería mostrar días/semanas
- Para `year`: debería mostrar meses

**Actual:** Todos usan el mismo formato.

**Solución:**
```php
public function getJsonData(Request $request): array
{
    $data = $this->getData($request);
    $range = $request->input('range', 'month');
    
    // Determinar formato de fecha según el rango
    $dateFormat = match($range) {
        'today' => 'H:i',
        'week' => 'd M',
        'month' => 'd M',
        'year' => 'M Y',
        default => 'd M',
    };
    
    return array_merge($data, [
        'recaudadoPeriodoFormatted' => number_format($data['recaudadoPeriodo'], 2, ',', '.'),
        'tramitesPeriodoFormatted' => number_format($data['tramitesPeriodo'], 0, ',', '.'),
        'tramitesFinalizadosPeriodoFormatted' => number_format($data['tramitesFinalizadosPeriodo'], 0, ',', '.'),
        'tramitesPendientesFormatted' => number_format($data['tramitesPendientes'], 0, ',', '.'),
        'tendencias' => $data['tendencias'],
        'ultimostramitesHtml' => $data['ultimostramitesHtml'],
        'recaudacionPeriodoData' => [
            'labels' => $data['recaudacionPeriodoData']->keys()->map(fn($item) => Carbon::parse($item)->format($dateFormat))->values(),
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
    ]);
}
```

---

### 7. Cache Keys No Únicas por Usuario
**Ubicación del bug:** `app/Services/DashboardService.php`, generación de cache keys  
**Documentado en:** `docs/dev/dashboard_service.md:478-488`

**Problema:**
```php
$cachePrefix = 'dashboard:' . $range . ':' . $startDate->format('Ymd') . ':' . $endDate->format('Ymd');
$ttl = now()->addMinutes(5);
```

El prefijo de caché no incluye información del usuario. Si dos usuarios con diferentes roles o permisos acceden al dashboard:
- Podrían ver datos de otros usuarios
- El caché podría mostrar información incorrecta

**Impacto:**
- Posible fuga de información entre usuarios
- Datos inconsistentes entre usuarios
- Violación de privacidad

**Solución:**
```php
// Incluir información del usuario en el cache key
$cachePrefix = 'dashboard:' . 
                 $range . ':' . 
                 $startDate->format('Ymd') . ':' . 
                 $endDate->format('Ymd') . ':' .
                 optional(auth()->user())->id ?? 'guest';
```

---

## 📊 Resumen de Bugs por Archivo

| Archivo | Bugs | Prioridad |
|---------|------|-----------|
| `app/Http/Controllers/Admin/DashboardController.php` | 1 | Alta |
| `app/Services/DashboardService.php` | 6 | Alta |

---

## 📝 Archivos de Documentación Afectados

Para actualizar tu documentación después de corregir estos bugs, debes modificar:

1. **`docs/dev/dashboard_controller.md`** - Líneas 704-737 (eliminar bugs corregidos)
2. **`docs/dev/dashboard_service.md`** - Referencias a tendencias, cache y cálculos (bugs #2-7)

---

## 🎯 Prioridad de Corrección

### 🔴 ALTA - Corregir ASAP
1. **Validación del parámetro `range`** (#1) - Previene inyección y errores
2. **División por cero en tendencias** (#2) - Previene error 500
3. **Manejo de errores en consultas** (#3) - Previene caídas del dashboard
4. **Inconsistencia de fechas en comparación anual** (#4) - Corrige gráficos
5. **Relaciones no cargadas en últimos trámites** (#5) - Previene errores N+1
6. **Cache keys no únicas por usuario** (#7) - Privacidad y seguridad

### 🟠 MEDIA - Corregir pronto
7. **Formato de fecha incorrecto en gráficos** (#6) - Mejora UX

---

## 💡 Roadmap de Implementación

### Fase 1: Seguridad y Estabilidad (Día 1-2)
- [ ] Validar parámetro `range`
- [ ] Manejar división por cero en tendencias
- [ ] Implementar try-catch en consultas
- [ ] Mejorar carga de relaciones en últimos trámites

### Fase 2: Corrección de Datos (Día 3-4)
- [ ] Corregir comparación anual con array_replace
- [ ] Ajustar formato de fechas según rango
- [ ] Probar con datos de ejemplo

### Fase 3: Optimización y Seguridad (Día 5)
- [ ] Implementar cache keys únicos por usuario
- [ ] Agregar monitoreo de errores
- [ ] Documentación de cambios
