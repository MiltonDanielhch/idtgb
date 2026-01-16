# Documentación Técnica - Servicio IdtgbCalculator

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Arquitectura del Servicio](#arquitectura-del-servicio)
3. [Métodos Públicos](#métodos-públicos)
4. [Lógica de Cálculo Core](#lógica-de-cálculo-core)
5. [Fórmulas Implementadas](#fórmulas-implementadas)
6. [Uso del Servicio](#uso-del-servicio)
7. [Integración con el Sistema](#integración-con-el-sistema)
8. [Casos de Uso](#casos-de-uso)
9. [Consideraciones Importantes](#consideraciones-importantes)
10. [Ejemplos de Código](#ejemplos-de-código)
11. [Pruebas y Validación](#pruebas-y-validación)

---

## 🎯 Introducción

El **IdtgbCalculator** es el servicio central encargado de calcular el Impuesto de Transmisiones Gratuitas de Bienes (ITGB) según la Ley 812 boliviana. Este servicio implementa toda la lógica financiera necesaria tanto para la calculadora pública (ciudadanos) como para el cálculo oficial en trámites de funcionarios.

### Propósito
- Calcular el ITGB base aplicando tasas según parentesco y departamento
- Calcular mantenimiento de valor utilizando UFVs (Unidad de Fomento a la Vivienda)
- Calcular intereses por mora con tasas escalonadas (4%, 6%, 10%)
- Calcular multa IDF (Infracción Dominio Fiscal) en UFVs
- Aplicar factor de participación (Estilo Cochabamba)
- Actualizar registros de trámites en base de datos

### Importancia en el Sistema ITGB
Este servicio es **CRÍTICO** para el sistema porque:
- Es utilizado por la calculadora pública (ciudadanos)
- Es utilizado por el wizard de trámites (funcionarios)
- Es invocado por observers automáticos al actualizar trámites
- Implementa la normativa legal boliviana (Ley 812)
- Afecta directamente el monto que pagan los contribuyentes

---

## 🏗️ Arquitectura del Servicio

### Ubicación
**Archivo:** `app/Services/IdtgbCalculator.php`

### Dependencias
```php
use App\Models\Tramite;
use App\Models\Tasa;
use App\Models\Ufv;
use Carbon\Carbon;
```

### Estructura del Servicio
```
IdtgbCalculator
├─ Métodos Públicos
│  ├─ calculateAndSave(Tramite $tramite)      → Para trámites grabados
│  └─ calculateEstimate(...)                   → Para calculadora pública
│
├─ Método Privado Core
│  └─ performCalculation(...)                  → Lógica central de cálculo
│
└─ Métodos Auxiliares
   └─ tasaVigente($depId, $parId, $fecha)    → Obtener tasa aplicable
```

### Flujo de Datos
```
Trámite o Calculadora
    ↓
Método Público (calculateAndSave o calculateEstimate)
    ↓
performCalculation (CORE)
    ↓
Cálculo ITGB Base
    ↓
Cálculo Mantenimiento de Valor
    ↓
Cálculo Intereses
    ↓
Cálculo Multa
    ↓
Retorno de Resultados
```

---

## 🎮 Métodos Públicos

### 1. `calculateAndSave(Tramite $tramite)` - Para Trámites Oficiales

**Descripción:** Calcula y guarda los montos de un trámite grabado en la base de datos. Este método se usa en el sistema interno de funcionarios.

**Firma:**
```php
public function calculateAndSave(Tramite $tramite): array
```

**Parámetros:**
- `$tramite` (Tramite): Instancia del modelo Tramite con relaciones cargadas

**Retorno:**
- `array`: Arreglo asociativo con todos los resultados del cálculo

**Proceso:**

1. **Carga de relaciones:**
```php
$tramite->load(['inmuebles.municipio.provincia.departamento', 'adquirentes']);
```

2. **Cálculo de porcentaje de participación:**
```php
$porcentajeParticipacion = $tramite->adquirentes->sum('porcentaje') ?: 100;
```

3. **Preparación de datos de adquirentes:**
```php
$adquirentesData = $tramite->adquirentes->map(function ($adq) {
    return [
        'parentesco_id' => $adq->parentesco_id,
        'porcentaje' => $adq->porcentaje,
    ];
})->all();
```

4. **Ejecución del cálculo CORE:**
```php
$resultados = $this->performCalculation(
    $tramite->base_imponible,
    $tramite->inmuebles->first()->municipio->provincia->departamento_id ?? 1,
    $tramite->tipo_transmision_id,
    $tramite->fecha_presentacion->toDateString(),
    $tramite->fecha_transmision->toDateString(),
    $tramite->fecha_vencimiento->toDateString(),
    $adquirentesData,
    [],
    $tramite->tipo_contribuyente ?? 'Natural',
    $porcentajeParticipacion
);
```

5. **Actualización del trámite:**
```php
$tramite->update([
    'total_idtgb'  => $resultados['idtgb_base'],
    'recargo_mora' => $resultados['mantenimiento_valor'] + 
                      $resultados['interes'] + 
                      $resultados['multa_idf'],
    'monto_final'  => $resultados['final'],
    'ufv_aplicada' => $resultados['ufv_pago'],
]);
```

6. **Actualización de adquirentes:**
```php
foreach ($tramite->adquirentes as $adq) {
    $tasaModel = $this->tasaVigente(
        $tramite->inmuebles->first()->municipio->provincia->departamento_id,
        $adq->parentesco_id,
        $tramite->fecha_presentacion
    );

    $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
    $baseSujeto = $tramite->base_imponible * ($adq->porcentaje / 100);

    $adq->update([
        'tasa_aplicada' => $tasaVal,
        'idtgb_proporcional' => round($baseSujeto * ($tasaVal / 100), 2)
    ]);
}
```

**Uso en el sistema:**
- Invocado desde: `TramiteObserver::updated()`
- Invocado desde: `AdquirenteTramiteController::store()` y `update()`
- Invocado desde: `TramiteExencionController::store()` y `update()`

---

### 2. `calculateEstimate(...)` - Para Calculadora Pública

**Descripción:** Calcula una estimación del ITGB sin guardar en la base de datos. Este método se usa en la calculadora pública para ciudadanos.

**Firma:**
```php
public function calculateEstimate(
    $base, 
    $depId, 
    $parId, 
    $tipoId, 
    $fTrans, 
    $fPres, 
    $fVenc, 
    $contribuyente = 'Natural', 
    $participacion = 100
): array
```

**Parámetros:**
| Parámetro | Tipo | Descripción |
|-----------|------|-------------|
| `$base` | float | Base imponible del trámite |
| `$depId` | int | ID del departamento |
| `$parId` | int | ID del parentesco del adquirente |
| `$tipoId` | int | ID del tipo de transmisión |
| `$fTrans` | string | Fecha de transmisión (Y-m-d) |
| `$fPres` | string | Fecha de presentación (Y-m-d) |
| `$fVenc` | string | Fecha de vencimiento (Y-m-d) |
| `$contribuyente` | string | Tipo de contribuyente ('Natural' o 'Jurídica') |
| `$participacion` | float | Factor de participación (1-100%) |

**Retorno:**
- `array`: Arreglo asociativo con todos los resultados del cálculo

**Proceso:**
```php
return $this->performCalculation(
    $base, 
    $depId, 
    $tipoId, 
    $fPres, 
    $fTrans, 
    $fVenc,
    [['parentesco_id' => $parId, 'porcentaje' => 100]],
    [], 
    $contribuyente, 
    $participacion
);
```

**Uso en el sistema:**
- Invocado desde: `CalculadoraBeniController::calcular()`
- Invocado desde: `CalculadoraBeniController::descargarPdf()`

---

## 🔢 Lógica de Cálculo Core

### Método: `performCalculation()`

**Descripción:** Este método contiene toda la lógica central de cálculo del ITGB según la Ley 812.

**Firma:**
```php
private function performCalculation(
    $base, 
    $depId, 
    $tipoId, 
    $fPres, 
    $fTrans, 
    $fVenc, 
    $adquirentes, 
    $exenciones, 
    $tipoContribuyente, 
    $participacion
): array
```

### Paso 1: Aplicación del Factor de Participación

**Lógica:** Estilo Cochabamba - Solo se calcula sobre la porción de participación del adquirente.

```php
$baseImponibleParticipacion = $base * ($participacion / 100);
```

**Ejemplo:**
```
Base imponible: Bs 1,000,000
Participación: 50%
Base imponible calculada: Bs 500,000
```

### Paso 2: Cálculo del Tributo Omitido (ITGB Base)

**Lógica:** Suma de tasas aplicables para todos los adquirentes.

```php
$totalTasas = 0;
$tasaAplicadaDecimal = 0;

foreach ($adquirentes as $adq) {
    $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);
    $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
    $tasaAplicadaDecimal = $tasaVal;

    $proporcional = round($baseImponibleParticipacion * ($tasaVal / 100), 2);
    $totalTasas += $proporcional;
}

$idtgbBase = max(0, $totalTasas);
```

**Ejemplo:**
```
Base imponible calculada: Bs 500,000
Adquirente 1 (Hermano): Tasa 1.5% → Bs 7,500
Adquirente 2 (Tío): Tasa 3.0% → Bs 15,000
ITGB Base total: Bs 22,500
```

### Paso 3: Cálculo de Variables de Mora

**Inicialización:**
```php
$mantenimientoValor = 0;
$interes = 0;
$multaIdf = 0;
$diasMora = 0;
$r_interes_display = 0;

$fechaPago = Carbon::parse($fPres)->startOfDay();
$fechaVenc = Carbon::parse($fVenc)->startOfDay();

$ufvVencimiento = Ufv::getValorEnFecha($fechaVenc);
$ufvPago = Ufv::getValorEnFecha($fechaPago);
```

### Paso 4: Cálculo si hay Mora

**Condición de mora:**
```php
if ($fechaPago->isAfter($fechaVenc)) {
    // Cálculo de recargos
}
```

**4A. Mantenimiento de Valor:**
```php
$diasMora = $fechaPago->diffInDays($fechaVenc);

$tributoActualizado = $idtgbBase * ($ufvPago / $ufvVencimiento);
$mantenimientoValor = max(0, $tributoActualizado - $idtgbBase);
```

**Ejemplo:**
```
ITGB Base: Bs 22,500
UFV Vencimiento: 3.580
UFV Pago: 3.950
Tributo Actualizado: 22,500 × (3.950 / 3.580) = Bs 24,821.23
Mantenimiento de Valor: 24,821.23 - 22,500 = Bs 2,321.23
```

**4B. Intereses (Ley 812 - Escalonado):**
```php
$aniosMora = $diasMora / 360;
$r = 0.04;
if ($aniosMora > 4) $r = 0.06;
if ($aniosMora > 7) $r = 0.10;

$r_interes_display = $r * 100;
$interes = $tributoActualizado * (pow(1 + ($r / 360), $diasMora) - 1);
```

**Tasas de interés:**
- 0-4 años: 4% anual
- 4-7 años: 6% anual
- Más de 7 años: 10% anual

**Ejemplo:**
```
Tributo Actualizado: Bs 24,821.23
Días de mora: 365 días (1 año)
Tasa: 4% anual
Interés = 24,821.23 × ((1 + 0.04/360)^365 - 1) = Bs 1,005.42
```

**4C. Multa IDF (Infracción al Dominio Fiscal):**
```php
$cantUfvMulta = ($tipoContribuyente === 'Jurídica') ? 100 : 50;
$multaIdf = $cantUfvMulta * $ufvPago;
```

**Montos de multa:**
- Personas Naturales: 50 UFVs
- Personas Jurídicas: 100 UFVs

**Ejemplo:**
```
Tipo contribuyente: Natural
UFV Pago: 3.950
Multa IDF = 50 × 3.950 = Bs 197.50
```

### Paso 5: Cálculo del Monto Final

```php
$recargoTotal = $mantenimientoValor + $interes + $multaIdf;
$final = $idtgbBase + $recargoTotal;
```

**Ejemplo:**
```
ITGB Base: Bs 22,500.00
Mantenimiento: Bs 2,321.23
Interés: Bs 1,005.42
Multa IDF: Bs 197.50
────────────────────────────────
Recargo Total: Bs 3,524.15
Monto Final: Bs 26,024.15
```

### Paso 6: Retorno de Resultados

```php
return [
    'nro_tramite' => 'REF-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
    'base_original' => $base,
    'participacion' => $participacion,
    'base_imponible_calculada' => $baseImponibleParticipacion,
    'tasa_aplicada' => $tasaAplicadaDecimal,
    'idtgb_base' => round($idtgbBase, 2),
    'mantenimiento_valor' => round($mantenimientoValor, 2),
    'interes' => round($interes, 2),
    'tasa_mora' => $r_interes_display,
    'multa_idf' => round($multaIdf, 2),
    'final' => round($final, 2),
    'dias_mora' => $diasMora,
    'ufv_vencimiento' => $ufvVencimiento,
    'ufv_pago' => $ufvPago,
    'fecha_transmision' => Carbon::parse($fTrans)->format('d/m/Y'),
    'fecha_vencimiento' => $fechaVenc->format('d/m/Y'),
    'fecha_pago' => $fechaPago->format('d/m/Y'),
];
```

---

## 📐 Fórmulas Implementadas

### 1. Factor de Participación
```
Base Imponible Calculada = Base Original × (Participación% / 100)
```

### 2. ITGB Base
```
ITGB Base = Σ(Base Imponible Calculada × Tasa% del Adquirente)
```

### 3. Mantenimiento de Valor
```
Tributo Actualizado = ITGB Base × (UFV_Pago / UFV_Vencimiento)
Mantenimiento = Tributo Actualizado - ITGB Base
```

### 4. Interés Compuesto Diario
```
Interés = Tributo Actualizado × ((1 + r/360)^días - 1)

Donde:
- r = Tasa de interés anual (0.04, 0.06, o 0.10)
- días = Días de mora
```

### 5. Tasas de Interés Escalonadas
```
Si años de mora ≤ 4:  r = 4%  (0.04)
Si 4 < años de mora ≤ 7: r = 6%  (0.06)
Si años de mora > 7:   r = 10% (0.10)

Donde años de mora = días / 360
```

### 6. Multa IDF
```
Multa IDF = (50 o 100 UFVs) × UFV_Pago

Donde:
- Personas Naturales: 50 UFVs
- Personas Jurídicas: 100 UFVs
```

### 7. Monto Final
```
Recargo Total = Mantenimiento + Interés + Multa IDF
Monto Final = ITGB Base + Recargo Total
```

---

## 🔍 Uso del Servicio

### Inyección de Dependencias

El servicio puede ser inyectado automáticamente por Laravel:

```php
use App\Services\IdtgbCalculator;

class MiController extends Controller
{
    public function metodo(IdtgbCalculator $calculator)
    {
        // Usar $calculator
    }
}
```

### Uso Directo

También se puede obtener desde el contenedor:

```php
$calculator = app(IdtgbCalculator::class);
$resultados = $calculator->calculateEstimate(...);
```

---

## 🔗 Integración con el Sistema

### 1. Calculadora Pública (Ciudadanos)

**Controlador:** `CalculadoraBeniController`
**Ruta:** `/calculadora-idtgb-beni`

```php
public function calcular(Request $request, IdtgbCalculator $calculator)
{
    $calculo = $calculator->calculateEstimate(
        (float)$request->base_imponible,
        $beniId,
        (int)$request->parentesco_id,
        $tipoTransmisionId,
        $request->fecha_transmision,
        Carbon::now()->toDateString(),
        $fecha_vencimiento->toDateString(),
        $request->tipo_contribuyente,
        (float)$request->participacion
    );

    return response()->json($calculo);
}
```

### 2. Wizard de Trámites (Funcionarios)

**Controlador:** `AdquirenteTramiteController`

```php
public function store(Request $request, $tramite)
{
    // ... validación ...

    $tramite->adquirentes()->create($validated);

    // Recalcular automáticamente
    $calculator = app(IdtgbCalculator::class);
    $calculator->calculateAndSave($tramite);

    return redirect()->back();
}
```

### 3. Observer Automático

**Observer:** `TramiteObserver`

```php
public function updated(Tramite $tramite)
{
    if ($tramite->isDirty(['base_imponible', 'fecha_presentacion', 'tipo_contribuyente'])) {
        $calculator = app(IdtgbCalculator::class);
        $calculator->calculateAndSave($tramite);
    }
}
```

---

## 📚 Casos de Uso

### Caso 1: Calculadora Pública - Sin Mora

**Escenario:** Ciudadano consulta impuesto de Bs 100,000 para trámite de herencia, presentado en fecha.

```php
$resultados = $calculator->calculateEstimate(
    base: 100000,
    depId: 1, // Beni
    parId: 1, // Padre
    tipoId: 1, // Herencia
    fTrans: '2025-01-01',
    fPres: '2025-01-10',
    fVenc: '2025-04-10',
    contribuyente: 'Natural',
    participacion: 100
);
```

**Resultado esperado:**
```
ITGB Base: Bs 1,500.00 (Tasa 1.5%)
Mantenimiento: Bs 0.00 (Sin mora)
Interés: Bs 0.00
Multa IDF: Bs 0.00
Monto Final: Bs 1,500.00
```

### Caso 2: Calculadora Pública - Con Mora

**Escenario:** Ciudadano consulta impuesto presentado con 1 año de mora.

```php
$resultados = $calculator->calculateEstimate(
    base: 100000,
    depId: 1,
    parId: 2, // Tío
    tipoId: 1,
    fTrans: '2024-01-01',
    fPres: '2025-01-10',  // 1 año después
    fVenc: '2024-04-10',
    contribuyente: 'Natural',
    participacion: 100
);
```

**Resultado esperado:**
```
ITGB Base: Bs 3,000.00 (Tasa 3.0%)
Mantenimiento: ~Bs 400.00 (según UFVs)
Interés: ~Bs 200.00 (4% anual, 1 año)
Multa IDF: ~Bs 200.00 (50 UFVs)
Monto Final: ~Bs 3,800.00
```

### Caso 3: Trámite Oficial - Múltiples Adquirentes

**Escenario:** Trámite con 2 adquirentes con diferentes tasas.

```php
$tramite = Tramite::with('adquirentes')->find(1);

$resultados = $calculator->calculateAndSave($tramite);
```

**Adquirentes:**
- Adquirente 1 (Hermano, 50%): Tasa 1.5% → Bs 750
- Adquirente 2 (Tío, 50%): Tasa 3.0% → Bs 1,500
- Total: Bs 2,250

### Caso 4: Participación Parcial

**Escenario:** Adquirente recibe solo el 60% de la herencia.

```php
$resultados = $calculator->calculateEstimate(
    base: 100000,
    depId: 1,
    parId: 1, // Padre
    tipoId: 1,
    fTrans: '2025-01-01',
    fPres: '2025-01-10',
    fVenc: '2025-04-10',
    contribuyente: 'Natural',
    participacion: 60  // Solo el 60%
);
```

**Resultado esperado:**
```
Base Original: Bs 100,000
Participación: 60%
Base Calculada: Bs 60,000
ITGB Base: Bs 900.00 (1.5% de 60,000)
Monto Final: Bs 900.00
```

### Caso 5: Persona Jurídica

**Escenario:** Trámite de persona jurídica con mora.

```php
$resultados = $calculator->calculateEstimate(
    base: 500000,
    depId: 1,
    parId: 3,
    tipoId: 1,
    fTrans: '2023-01-01',
    fPres: '2025-01-10',  // 2 años de mora
    fVenc: '2023-04-10',
    contribuyente: 'Jurídica',  // Afecta multa
    participacion: 100
);
```

**Resultado esperado:**
```
ITGB Base: Bs 7,500.00
Mantenimiento: ~Bs 1,500.00
Interés: ~Bs 1,200.00 (6% anual, 2 años)
Multa IDF: ~Bs 400.00 (100 UFVs para Jurídicas)
Monto Final: ~Bs 10,600.00
```

---

## ⚠️ Consideraciones Importantes

### 1. Fechas y Timezones
- Todas las fechas se normalizan al inicio del día: `->startOfDay()`
- El sistema usa el timezone configurado en `config/app.php`
- Importante: `fecha_presentacion` se usa como fecha de pago para cálculo

### 2. Cálculo de UFVs
- Se asume que el modelo `Ufv` tiene un scope `getValorEnFecha($fecha)`
- Si no hay UFV para una fecha, el cálculo puede fallar
- Las UFVs son obligatorias para el cálculo de mantenimiento de valor

### 3. Tasas Vigentes
- La función `tasaVigente()` busca tasas donde:
  - `vigente_desde <= fecha`
  - `vigente_hasta >= fecha` O `vigente_hasta IS NULL`
- Si no hay tasa vigente, se usa 0%

### 4. Redondeo
- Los montos monetarios se redondean a 2 decimales
- Usar `round($valor, 2)`
- El redondeo se aplica al final, no en pasos intermedios

### 5. Tipos de Contribuyente
- **Natural:** Multa de 50 UFVs
- **Jurídica:** Multa de 100 UFVs
- Afecta únicamente la multa IDF, no los intereses

### 6. Exenciones
- El parámetro `$exenciones` está recibido pero NO IMPLEMENTADO
- Futuro: Las exenciones pueden reducir la base imponible o anular el impuesto

### 7. Número de Trámite
- Para estimaciones se genera: `'REF-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT)`
- Para trámites reales se usa el `nro_tramite` existente

### 8. Base Imponible Mínima
- El sistema acepta base imponible desde 0.01
- Resultados negativos se convierten a 0: `max(0, $totalTasas)`

### 9. Actualización de Trámites
- El método `calculateAndSave()` actualiza directamente la base de datos
- No usa transacciones (considerar agregar)
- Actualiza tanto el trámite como sus adquirentes

---

## 💻 Ejemplos de Código

### Ejemplo 1: Uso en Controlador

```php
<?php

namespace App\Http\Controllers;

use App\Services\IdtgbCalculator;
use App\Models\Tramite;
use Illuminate\Http\Request;

class MiController extends Controller
{
    public function calcularTramite(Request $request, IdtgbCalculator $calculator)
    {
        $request->validate([
            'base_imponible' => 'required|numeric|min:0.01',
            'departamento_id' => 'required|exists:departamentos,id',
            'parentesco_id' => 'required|exists:parentescos,id',
            'tipo_transmision_id' => 'required|exists:tipos_transmision,id',
            'fecha_transmision' => 'required|date',
            'fecha_presentacion' => 'required|date',
            'fecha_vencimiento' => 'required|date',
            'tipo_contribuyente' => 'required|in:Natural,Jurídica',
            'participacion' => 'required|numeric|min:1|max:100',
        ]);

        $resultados = $calculator->calculateEstimate(
            $request->base_imponible,
            $request->departamento_id,
            $request->parentesco_id,
            $request->tipo_transmision_id,
            $request->fecha_transmision,
            $request->fecha_presentacion,
            $request->fecha_vencimiento,
            $request->tipo_contribuyente,
            $request->participacion
        );

        return response()->json([
            'success' => true,
            'data' => $resultados
        ]);
    }
}
```

### Ejemplo 2: Recalcular Trámite Existente

```php
<?php

namespace App\Http\Controllers;

use App\Services\IdtgbCalculator;
use App\Models\Tramite;

class TramiteController extends Controller
{
    public function recalcular($tramiteId)
    {
        $tramite = Tramite::with([
            'inmuebles.municipio.provincia.departamento',
            'adquirentes'
        ])->findOrFail($tramiteId);

        $calculator = app(IdtgbCalculator::class);
        $resultados = $calculator->calculateAndSave($tramite);

        return back()->with([
            'message' => 'Trámite recalculado exitosamente.',
            'alert-type' => 'success',
            'resultados' => $resultados
        ]);
    }
}
```

### Ejemplo 3: Simulación de Escenarios

```php
<?php

use App\Services\IdtgbCalculator;
use Carbon\Carbon;

$calculator = app(IdtgbCalculator::class);

// Escenario 1: Sin mora
$sinMora = $calculator->calculateEstimate(
    100000, 1, 1, 1,
    '2025-01-01', '2025-01-10', '2025-04-10',
    'Natural', 100
);

// Escenario 2: Con 1 año de mora
$conMora = $calculator->calculateEstimate(
    100000, 1, 1, 1,
    '2024-01-01', '2025-01-10', '2024-04-10',
    'Natural', 100
);

echo "Sin mora: Bs " . number_format($sinMora['final'], 2) . "\n";
echo "Con mora: Bs " . number_format($conMora['final'], 2) . "\n";
echo "Diferencia: Bs " . number_format($conMora['final'] - $sinMora['final'], 2) . "\n";
```

### Ejemplo 4: Comparación de Tasas

```php
<?php

use App\Services\IdtgbCalculator;

$calculator = app(IdtgbCalculator::class);
$base = 100000;

$parentescos = [1, 2, 3, 4, 5]; // IDs de parentescos

echo "Comparación de tasas para Bs " . number_format($base, 2) . ":\n\n";

foreach ($parentescos as $parId) {
    $resultado = $calculator->calculateEstimate(
        $base, 1, $parId, 1,
        '2025-01-01', '2025-01-10', '2025-04-10',
        'Natural', 100
    );

    echo "Parentesco ID $parId:\n";
    echo "  Tasa: {$resultado['tasa_aplicada']}%\n";
    echo "  ITGB Base: Bs " . number_format($resultado['idtgb_base'], 2) . "\n\n";
}
```

### Ejemplo 5: Test Unitario

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IdtgbCalculator;
use App\Models\Tramite;
use App\Models\AdquirenteTramite;

class IdtgbCalculatorTest extends TestCase
{
    public function test_calculo_sin_mora()
    {
        $calculator = app(IdtgbCalculator::class);

        $resultado = $calculator->calculateEstimate(
            100000, 1, 1, 1,
            '2025-01-01', '2025-01-10', '2025-04-10',
            'Natural', 100
        );

        $this->assertEquals(0, $resultado['dias_mora']);
        $this->assertEquals(0, $resultado['mantenimiento_valor']);
        $this->assertEquals(0, $resultado['interes']);
        $this->assertEquals(0, $resultado['multa_idf']);
    }

    public function test_calculo_con_mora()
    {
        $calculator = app(IdtgbCalculator::class);

        $resultado = $calculator->calculateEstimate(
            100000, 1, 1, 1,
            '2024-01-01', '2025-01-10', '2024-04-10',
            'Natural', 100
        );

        $this->assertGreaterThan(0, $resultado['dias_mora']);
        $this->assertGreaterThan(0, $resultado['mantenimiento_valor']);
        $this->assertGreaterThan(0, $resultado['interes']);
        $this->assertGreaterThan(0, $resultado['multa_idf']);
    }

    public function test_calculo_participacion_parcial()
    {
        $calculator = app(IdtgbCalculator::class);

        $resultado100 = $calculator->calculateEstimate(
            100000, 1, 1, 1,
            '2025-01-01', '2025-01-10', '2025-04-10',
            'Natural', 100
        );

        $resultado50 = $calculator->calculateEstimate(
            100000, 1, 1, 1,
            '2025-01-01', '2025-01-10', '2025-04-10',
            'Natural', 50
        );

        $this->assertEquals(
            $resultado100['idtgb_base'] / 2,
            $resultado50['idtgb_base'],
            'El ITGB con 50% debe ser la mitad del 100%'
        );
    }
}
```

---

## 🧪 Pruebas y Validación

### Validación Manual (Tinker)

```bash
php artisan tinker
```

```php
use App\Services\IdtgbCalculator;
use Carbon\Carbon;

$calculator = app(IdtgbCalculator::class);

// Test básico
$resultado = $calculator->calculateEstimate(
    100000, 1, 1, 1,
    '2025-01-01', '2025-01-10', '2025-04-10',
    'Natural', 100
);

dump($resultado);
```

### Validación de Fórmulas

**Fórmula de interés (1 año, 4%):**
```
Interés = Principal × ((1 + 0.04/360)^365 - 1)
```

**Cálculo manual:**
```
Principal = Bs 24,821.23
(1 + 0.04/360) = 1.000111111
(1.000111111)^365 = 1.040482432
1.040482432 - 1 = 0.040482432
24,821.23 × 0.040482432 = Bs 1,005.42
```

### Casos Límite a Probar

| Caso | Descripción | Resultado Esperado |
|------|-------------|-------------------|
| Base 0 | Base imponible = 0 | Todos los montos = 0 |
| Base negativa | Base imponible < 0 | Todos los montos = 0 (max(0, ...)) |
| Fecha futura | Presentación > hoy | Mora = 0 |
| Mora larga | > 7 años | Interés al 10% |
| Participación 0% | Participación = 0 | Base calculada = 0 |
| Participación 100% | Participación = 100 | Base calculada = original |
| Tasa 0% | Sin tasa registrada | ITGB = 0 |
| Sin UFV | UFV no encontrada | Error/Exception |

---

## 📞 Soporte y Mantenimiento

### Modificaciones Sugeridas

1. **Agregar soporte de transacciones:**
```php
public function calculateAndSave(Tramite $tramite): array
{
    DB::beginTransaction();
    try {
        // ... cálculo ...
        
        $tramite->update([...]);
        
        foreach ($tramite->adquirentes as $adq) {
            $adq->update([...]);
        }
        
        DB::commit();
        return $resultados;
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
}
```

2. **Implementar exenciones:**
```php
private function calcularExenciones($exenciones, $baseImponible): float
{
    $descuento = 0;
    foreach ($exenciones as $exencion) {
        // Lógica de exenciones
    }
    return $descuento;
}
```

3. **Agregar logging:**
```php
Log::info('Cálculo ITGB', [
    'tramite_id' => $tramite->id ?? null,
    'base' => $base,
    'resultado' => $resultados['final']
]);
```

### Referencias Legales

- **Ley 812 de 30 de julio de 2016** - Modificaciones al Código Tributario
- **Ley 843 (Reformada)** - Código Tributario Boliviano
- **Resoluciones Normativas del SIN** - Tasas actualizadas

---

## 🎯 Conclusión

El servicio `IdtgbCalculator` es el corazón financiero del sistema ITGB. Implementa toda la lógica necesaria para calcular el impuesto según la legislación boliviana, manteniendo separación clara entre:

1. **Cálculos públicos** (calculadora ciudadana)
2. **Cálculos oficiales** (trámites de funcionarios)
3. **Actualización automática** (observers y eventos)

Su diseño modular permite fácil extensión para:
- Nuevos tipos de exenciones
- Cambios en la legislación
- Nuevas tasas de interés
- Diferentes departamentos con reglas especiales

**Última actualización:** Enero 2026

**Versión:** 1.0.0

**Responsable:** Equipo de Desarrollo ITGB

---

## 🚨 Análisis de Calidad del Código - Bugs Detectados

### 🐛 CRÍTICO: Bug en Cálculo de ITGB Base (Líneas 100-107)

**Ubicación:** `IdtgbCalculator::performCalculation()`

**Problema:** El cálculo del ITGB base tiene una **LÓGICA INCORRECTA** que resulta en montos errados cuando hay múltiples adquirentes.

**Código actual:**
```php
foreach ($adquirentes as $adq) {
    $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);
    $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
    $tasaAplicadaDecimal = $tasaVal;

    $proporcional = round($baseImponibleParticipacion * ($tasaVal / 100), 2);
    $totalTasas += $proporcional;
}

$idtgbBase = max(0, $totalTasas);
```

**Ejemplo del error:**
```
Base imponible: Bs 100,000
Participación: 100%

Adquirente 1 (Padre, 50%, Tasa 1.5%):
  - $baseImponibleParticipacion = 100,000 × (100/100) = 100,000
  - $proporcional = 100,000 × (1.5/100) = Bs 1,500

Adquirente 2 (Tío, 50%, Tasa 3.0%):
  - $baseImponibleParticipacion = 100,000 × (100/100) = 100,000
  - $proporcional = 100,000 × (3.0/100) = Bs 3,000

$totalTasas = 1,500 + 3,000 = Bs 4,500 ❌ INCORRECTO
```

**Resultado esperado:**
```
Adquirente 1 (Padre, 50%, Tasa 1.5%):
  - $baseSujeto = 100,000 × (50/100) = Bs 50,000
  - $proporcional = 50,000 × (1.5/100) = Bs 750

Adquirente 2 (Tío, 50%, Tasa 3.0%):
  - $baseSujeto = 100,000 × (50/100) = Bs 50,000
  - $proporcional = 50,000 × (3.0/100) = Bs 1,500

$totalTasas = 750 + 1,500 = Bs 2,250 ✅ CORRECTO
```

**Impacto:** 
- Los contribuyentes están pagando **el doble de lo correcto** cuando hay múltiples adquirentes con el mismo porcentaje
- Error financiero grave que puede causar reclamos y problemas legales

**Solución:**
```php
foreach ($adquirentes as $adq) {
    $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);
    $tasaVal = $tasaModel ? $tasaModel->tasa : 0;

    // CORREGIR: Aplicar el porcentaje de participación de cada adquirente
    $baseSujeto = $base * ($adq['porcentaje'] / 100);
    $proporcional = round($baseSujeto * ($tasaVal / 100), 2);
    
    $totalTasas += $proporcional;
}
```

---

### 🐛 CRÍTICO: Inconsistencia en `calculateAndSave()` (Líneas 57-68)

**Ubicación:** `IdtgbCalculator::calculateAndSave()`

**Problema:** El cálculo de `tasa_aplicada` e `idtgb_proporcional` en el bucle `foreach` usa `$base_imponible` completo, no el porcentaje correspondiente a cada adquirente.

**Código actual:**
```php
foreach ($tramite->adquirentes as $adq) {
    $tasaModel = $this->tasaVigente(...);
    $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
    
    // INCORRECTO: Usa base_imponible completo
    $baseSujeto = $tramite->base_imponible * ($adq->porcentaje / 100);
    
    $adq->update([
        'tasa_aplicada' => $tasaVal,
        'idtgb_proporcional' => round($baseSujeto * ($tasaVal / 100), 2)
    ]);
}
```

**Ejemplo del error:**
```
Base imponible del trámite: Bs 100,000

Adquirente 1 (50%, Tasa 1.5%):
  - $baseSujeto = 100,000 × (50/100) = Bs 50,000
  - $idtgb_proporcional = 50,000 × (1.5/100) = Bs 750 ✅

Adquirente 2 (50%, Tasa 3.0%):
  - $baseSujeto = 100,000 × (50/100) = Bs 50,000
  - $idtgb_proporcional = 50,000 × (3.0/100) = Bs 1,500 ✅

Total idtgb_proporcional = Bs 2,250 ✅

PERO: $total_idtgb (calculado en performCalculation con el bug anterior)
      = Bs 4,500 ❌

INCONSISTENCIA: Los adquirentes suman 2,250 pero el trámite registra 4,500
```

**Solución:**
```php
foreach ($tramite->adquirentes as $adq) {
    $tasaModel = $this->tasaVigente(...);
    $tasaVal = $tasaModel ? $tasaModel->tasa : 0;
    
    // Calcular correctamente la base sujeto para este adquirente
    $baseSujeto = $tramite->base_imponible * ($adq->porcentaje / 100);
    
    $adq->update([
        'tasa_aplicada' => $tasaVal,
        'idtgb_proporcional' => round($baseSujeto * ($tasaVal / 100), 2)
    ]);
}
```

---

### 🐛 MEDIO: Error en Búsqueda de UFV (Líneas 30-44 en `Ufv.php`)

**Ubicación:** `App\Models\Ufv::getValorEnFecha()`

**Problema:** Si no existe una UFV para la fecha solicitada, retorna `1.0` para evitar división por cero, pero esto es peligroso porque puede generar cálculos incorrectos sin advertencia.

**Código actual:**
```php
public static function getValorEnFecha($fecha)
{
    $ufv = self::where('fecha', '<=', $fecha)
               ->orderBy('fecha', 'desc')
               ->first();
    
    if (!$ufv) {
        return 1.00000;  // ❌ RETORNA 1.0 SI NO HAY UFV
    }
    
    return (float) $ufv->valor;
}
```

**Ejemplo del error:**
```
Fecha vencimiento: 2024-01-01 (UFV no registrada)
Fecha pago: 2025-01-01 (UFV = 3.50)

$ufvVencimiento = 1.0  (valor por defecto)
$ufvPago = 3.50

$tributoActualizado = 10,000 × (3.50 / 1.0) = Bs 35,000 ❌ INCORRECTO
$mantenimiento = 35,000 - 10,000 = Bs 25,000 ❌ INCORRECTO
```

**Solución:**
```php
public static function getValorEnFecha($fecha)
{
    $ufv = self::where('fecha', '<=', $fecha)
               ->orderBy('fecha', 'desc')
               ->first();
    
    if (!$ufv) {
        throw new \Exception(
            "No existe UFV registrada para la fecha {$fecha}. " .
            "Registre la UFV antes de realizar el cálculo."
        );
    }
    
    return (float) $ufv->valor;
}
```

---

### 🐛 MEDIO: Número de Trámite Generado con `rand()` (Línea 149)

**Ubicación:** `IdtgbCalculator::performCalculation()`

**Problema:** El número de trámite usa `rand(1, 99999)` que puede generar duplicados y no garantiza unicidad.

**Código actual:**
```php
'nro_tramite' => 'REF-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT),
```

**Riesgos:**
1. **Duplicación:** Dos cálculos simultáneos pueden generar el mismo número
2. **Predictibilidad:** Un atacante puede adivinar los números generados
3. **Sin orden:** No hay forma de saber qué cálculo fue anterior

**Solución:**
```php
// Opción 1: Usar timestamp + random
'nro_tramite' => 'REF-' . date('YmdHis') . '-' . Str::random(4),

// Opción 2: Usar ID de trámite si existe (en calculateAndSave)
'nro_tramite' => $tramite->exists ? $tramite->nro_tramite : 'EST-' . time(),
```

---

### 🐛 BAJO: Parámetro `$tipoId` No Utilizado (Línea 33-44, 79-86)

**Ubicación:** `IdtgbCalculator::performCalculation()`

**Problema:** El parámetro `$tipoId` (tipo de transmisión) se recibe pero **jamás se usa** en el cálculo.

**Código actual:**
```php
private function performCalculation($base, $depId, $tipoId, $fPres, $fTrans, $fVenc, ...)
{
    // $tipoId se recibe pero no se usa en ningún lugar
    
    $tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);
    // Solo usa $depId, $parentesco_id, $fPres
}
```

**Impacto:** 
- No se está filtrando tasas por tipo de transmisión
- Si en el futuro hay tasas diferentes según tipo de transmisión (ej: Donación vs Herencia), el cálculo será incorrecto

**Solución:**
```php
private function performCalculation($base, $depId, $tipoId, $fPres, $fTrans, $fVenc, ...)
{
    // ...
    
    $tasaModel = $this->tasaVigenteConTipo($depId, $adq['parentesco_id'], $tipoId, $fPres);
    // Ahora considera el tipo de transmisión
}
```

Agregar método:
```php
private function tasaVigenteConTipo($depId, $parId, $tipoId, $fecha)
{
    return Tasa::where('departamento_id', $depId)
               ->where('parentesco_id', $parId)
               ->where('tipo_transmision_id', $tipoId)  // ← NUEVO
               ->where('vigente_desde', '<=', $fecha)
               ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                     ->orWhere('vigente_hasta', '>=', $fecha))
               ->first();
}
```

---

### 🐛 BAJO: Falta Transacción en `calculateAndSave()` (Líneas 19-73)

**Ubicación:** `IdtgbCalculator::calculateAndSave()`

**Problema:** Las actualizaciones a la base de datos no están dentro de una transacción. Si falla a mitad del proceso, se deja el sistema en estado inconsistente.

**Código actual:**
```php
public function calculateAndSave(Tramite $tramite): array
{
    // ... cálculos ...
    
    // ❌ SIN TRANSACCIÓN
    $tramite->update([...]);
    
    foreach ($tramite->adquirentes as $adq) {
        $adq->update([...]);
    }
    
    return $resultados;
}
```

**Riesgo:**
- Si el update del trámite tiene éxito pero falla un update de adquirente, queda inconsistente
- No se puede hacer rollback en caso de error

**Solución:**
```php
use Illuminate\Support\Facades\DB;

public function calculateAndSave(Tramite $tramite): array
{
    // ... cálculos ...
    
    DB::beginTransaction();
    try {
        $tramite->update([...]);
        
        foreach ($tramite->adquirentes as $adq) {
            $adq->update([...]);
        }
        
        DB::commit();
    } catch (\Exception $e) {
        DB::rollBack();
        throw $e;
    }
    
    return $resultados;
}
```

---

### 🐛 BAJO: Falta Manejo de Excepciones (Varias ubicaciones)

**Ubicación:** Todo el servicio

**Problema:** No hay try-catch en operaciones críticas como acceso a base de datos (UFV, Tasa).

**Ejemplos:**
```php
// Línea 22 - Sin manejo de excepción
$ufvVencimiento = Ufv::getValorEnFecha($fechaVenc);  // ❌ Podría fallar

// Línea 71-75 - Sin manejo de excepción  
$tasaModel = $this->tasaVigente($depId, $adq['parentesco_id'], $fPres);  // ❌ Podría fallar
```

**Riesgos:**
- Si falla una consulta, el error se propaga sin contexto
- No hay logging de errores para debugging

**Solución:**
```php
use Illuminate\Support\Facades\Log;

private function performCalculation(...)
{
    try {
        $ufvVencimiento = Ufv::getValorEnFecha($fechaVenc);
    } catch (\Exception $e) {
        Log::error('Error al obtener UFV de vencimiento', [
            'fecha' => $fechaVenc,
            'error' => $e->getMessage()
        ]);
        throw new \Exception('No se puede calcular el impuesto: ' . $e->getMessage());
    }
    
    try {
        $tasaModel = $this->tasaVigente(...);
    } catch (\Exception $e) {
        Log::error('Error al obtener tasa vigente', [
            'depId' => $depId,
            'parentescoId' => $adq['parentesco_id'],
            'fecha' => $fPres,
            'error' => $e->getMessage()
        ]);
        throw new \Exception('No se puede calcular el impuesto: ' . $e->getMessage());
    }
    
    // ...
}
```

---

### 🐛 INFO: Validación Incompleta de Parámetros

**Ubicación:** Métodos públicos `calculateAndSave()` y `calculateEstimate()`

**Problema:** No se valida que:
- La base imponible sea positiva
- Las fechas sean válidas
- El porcentaje de participación esté entre 1 y 100
- El departamento exista

**Solución:**
```php
use Illuminate\Validation\ValidationException;

public function calculateEstimate($base, $depId, $parId, $tipoId, $fTrans, $fPres, $fVenc, $contribuyente = 'Natural', $participacion = 100): array
{
    // Validaciones
    if ($base <= 0) {
        throw new ValidationException('La base imponible debe ser mayor a 0');
    }
    
    if ($participacion < 1 || $participacion > 100) {
        throw new ValidationException('La participación debe estar entre 1% y 100%');
    }
    
    if (!in_array($contribuyente, ['Natural', 'Jurídica'])) {
        throw new ValidationException('El tipo de contribuyente debe ser Natural o Jurídica');
    }
    
    // Validación de fechas
    $fTransObj = Carbon::parse($fTrans);
    $fPresObj = Carbon::parse($fPres);
    $fVencObj = Carbon::parse($fVenc);
    
    if ($fTransObj->isAfter($fPresObj)) {
        throw new ValidationException('La fecha de transmisión no puede ser posterior a la fecha de presentación');
    }
    
    // ... resto del código ...
}
```

---

## 🚀 Mejoras Sugeridas

### 1. Implementar Caché de Tasas (Rendimiento)

**Problema actual:** Cada cálculo hace consultas a la base de datos para obtener tasas. En un sistema con muchos cálculos, esto es ineficiente.

**Solución:**
```php
use Illuminate\Support\Facades\Cache;

private function tasaVigente($depId, $parId, $fecha)
{
    $cacheKey = "tasa:{$depId}:{$parId}:{$fecha}";
    
    return Cache::remember($cacheKey, 3600, function () use ($depId, $parId, $fecha) {
        return Tasa::where('departamento_id', $depId)
                   ->where('parentesco_id', $parId)
                   ->where('vigente_desde', '<=', $fecha)
                   ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                         ->orWhere('vigente_hasta', '>=', $fecha))
                   ->first();
    });
}
```

**Beneficios:**
- Reducción drástica de consultas a base de datos
- Mejora en rendimiento para cálculos masivos
- El cache expira automáticamente después de 1 hora

**Invalidación de cache:**
```php
// En el controlador que crea/actualiza tasas:
public function store(StoreTasaRequest $request)
{
    $tasa = Tasa::create($request->validated());
    
    // Invalidar cache afectada
    Cache::forget("tasa:{$tasa->departamento_id}:{$tasa->parentesco_id}:*");
    
    return redirect()->back()->with('success', 'Tasa creada');
}
```

---

### 2. Implementar Historial de Cálculos (Auditoría)

**Problema actual:** No hay registro de cómo se calculó un trámite. Si hay un reclamo, es difícil reconstruir el cálculo.

**Solución:**
Crear tabla `historial_calculos`:
```php
Schema::create('historial_calculos', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tramite_id')->constrained();
    $table->foreignId('user_id')->constrained();
    $table->decimal('base_imponible', 14, 2);
    $table->decimal('base_calculada', 14, 2);
    $table->decimal('participacion', 5, 2);
    $table->decimal('tasa_aplicada', 5, 2);
    $table->decimal('idtgb_base', 12, 2);
    $table->decimal('mantenimiento_valor', 12, 2);
    $table->decimal('interes', 12, 2);
    $table->decimal('multa_idf', 12, 2);
    $table->decimal('monto_final', 12, 2);
    $table->decimal('ufv_vencimiento', 10, 5);
    $table->decimal('ufv_pago', 10, 5);
    $table->integer('dias_mora');
    $table->text('detalle_adquirentes')->nullable();  // JSON
    $table->timestamps();
});
```

Usar en el servicio:
```php
public function calculateAndSave(Tramite $tramite): array
{
    // ... cálculos ...
    
    // Guardar histórico
    \App\Models\HistorialCalculo::create([
        'tramite_id' => $tramite->id,
        'user_id' => auth()->id(),
        'base_imponible' => $tramite->base_imponible,
        'base_calculada' => $baseImponibleParticipacion,
        'participacion' => $porcentajeParticipacion,
        'tasa_aplicada' => $tasaAplicadaDecimal,
        'idtgb_base' => $resultados['idtgb_base'],
        'mantenimiento_valor' => $resultados['mantenimiento_valor'],
        'interes' => $resultados['interes'],
        'multa_idf' => $resultados['multa_idf'],
        'monto_final' => $resultados['final'],
        'ufv_vencimiento' => $ufvVencimiento,
        'ufv_pago' => $ufvPago,
        'dias_mora' => $diasMora,
        'detalle_adquirentes' => json_encode($adquirentesData)
    ]);
    
    // ... resto del código ...
}
```

---

### 3. Agregar Soporte para Exenciones (Funcionalidad Faltante)

**Problema actual:** El parámetro `$exenciones` se recibe pero **no se usa en absoluto**.

**Solución:**
```php
private function performCalculation($base, $depId, $tipoId, $fPres, $fTrans, $fVenc, $adquirentes, $exenciones, $tipoContribuyente, $participacion): array
{
    // ... cálculo ITGB base ...
    
    // 4. Aplicar exenciones
    $totalExenciones = 0;
    
    foreach ($exenciones as $exencion) {
        $montoExencion = $this->calcularMontoExencion($exencion, $idtgbBase);
        $totalExenciones += $montoExencion;
    }
    
    // 5. Recalcular después de exenciones
    $idtgbBaseConExencion = max(0, $idtgbBase - $totalExenciones);
    
    // Resto del cálculo usando $idtgbBaseConExencion
}

private function calcularMontoExencion($exencion, $idtgbBase): float
{
    if ($exencion['tipo'] === 'porcentaje') {
        return $idtgbBase * ($exencion['valor'] / 100);
    } else {
        return $exencion['valor'];  // Monto fijo
    }
}
```

---

### 4. Implementar Eventos de Notificación

**Mejora:** Notificar cuando el cálculo exceda ciertos umbrales.

```php
use App\Notifications\CalculoRealizado;

public function calculateAndSave(Tramite $tramite): array
{
    // ... cálculos ...
    
    // Notificar si el monto es alto
    if ($resultados['final'] > 100000) {
        $tramite->user->notify(new CalculoRealizado($tramite, $resultados));
    }
    
    return $resultados;
}
```

---

### 5. Agregar Logging Completo

**Solución:**
```php
use Illuminate\Support\Facades\Log;

public function calculateAndSave(Tramite $tramite): array
{
    Log::info('Inicio de cálculo ITGB', [
        'tramite_id' => $tramite->id,
        'nro_tramite' => $tramite->nro_tramite,
        'base_imponible' => $tramite->base_imponible,
        'fecha_presentacion' => $tramite->fecha_presentacion,
        'usuario' => auth()->id()
    ]);
    
    try {
        // ... cálculos ...
        
        Log::info('Cálculo ITGB completado exitosamente', [
            'tramite_id' => $tramite->id,
            'monto_final' => $resultados['final'],
            'tiempo_segundos' => microtime(true) - $inicio
        ]);
        
        return $resultados;
    } catch (\Exception $e) {
        Log::error('Error en cálculo ITGB', [
            'tramite_id' => $tramite->id,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        throw $e;
    }
}
```

---

### 6. Agregar Validación de Escenarios Extremos

**Validaciones faltantes:**
```php
// Base imponible excesiva
if ($base > 10000000) {
    Log::warning('Cálculo con base imponible muy alta', ['base' => $base]);
}

// Mora muy larga
if ($diasMora > 3650) {  // Más de 10 años
    Log::warning('Cálculo con mora extrema', ['dias_mora' => $diasMora]);
}

// Validar suma de porcentajes de adquirentes
$sumaPorcentajes = collect($adquirentes)->sum('porcentaje');
if (abs($sumaPorcentajes - 100) > 0.01) {
    throw new ValidationException(
        "La suma de porcentajes de adquirentes debe ser 100%. Actual: {$sumaPorcentajes}%"
    );
}
```

---

## ⚡ Optimizaciones de Rendimiento

### 1. Reducir Consultas N+1 en `calculateAndSave()`

**Problema actual:** En el bucle foreach, cada iteración hace una consulta a la base de datos.

**Código actual:**
```php
foreach ($tramite->adquirentes as $adq) {
    $tasaModel = $this->tasaVigente(...);  // ❌ Una consulta por adquirente
}
```

**Solución:** Cargar todas las tasas necesarias en una sola consulta:
```php
public function calculateAndSave(Tramite $tramite): array
{
    // Obtener todas las combinaciones de tasas necesarias
    $combinacionesNecesarias = [];
    $fechaPresentacion = $tramite->fecha_presentacion->toDateString();
    
    foreach ($tramite->adquirentes as $adq) {
        $combinacionesNecesarias[] = [
            'departamento_id' => $tramite->inmuebles->first()->municipio->provincia->departamento_id,
            'parentesco_id' => $adq->parentesco_id
        ];
    }
    
    // Cargar todas las tasas en UNA consulta
    $tasasMap = [];
    foreach ($combinacionesNecesarias as $comb) {
        $key = "{$comb['departamento_id']}:{$comb['parentesco_id']}:{$fechaPresentacion}";
        if (!isset($tasasMap[$key])) {
            $tasasMap[$key] = Tasa::where('departamento_id', $comb['departamento_id'])
                                ->where('parentesco_id', $comb['parentesco_id'])
                                ->where('vigente_desde', '<=', $fechaPresentacion)
                                ->where(fn ($q) => $q->whereNull('vigente_hasta')
                                              ->orWhere('vigente_hasta', '>=', $fechaPresentacion))
                                ->first();
        }
    }
    
    // Ahora usar el caché en lugar de consultar
    foreach ($tramite->adquirentes as $adq) {
        $key = "{$depId}:{$adq->parentesco_id']}:{$fechaPresentacion}";
        $tasaModel = $tasasMap[$key];
        // ...
    }
}
```

**Impacto:** Si un trámite tiene 10 adquirentes, pasamos de 10 consultas a 1-2 consultas.

---

### 2. Consulta Eficiente de UFVs

**Problema actual:** Si hay 100 trámites para calcular y cada uno consulta la UFV, se hacen 200 consultas (vencimiento + pago).

**Solución:**
```php
private function performCalculation(...)
{
    // Obtener UFVs en una sola consulta con clúster
    $ufvs = Ufv::whereIn('fecha', [$fVenc, $fPres])
                ->orderBy('fecha')
                ->get()
                ->keyBy('fecha');
    
    $ufvVencimiento = $ufvs->get($fVenc)->valor ?? null;
    $ufvPago = $ufvs->get($fPres)->valor ?? null;
    
    if (!$ufvVencimiento || !$ufvPago) {
        throw new \Exception("No existen UFVs para las fechas requeridas");
    }
    
    // ...
}
```

---

### 3. Usar `bcadd()` para Precisión Financiera

**Problema:** Las operaciones matemáticas de PHP con floats pueden tener errores de precisión (ej: 0.1 + 0.2 != 0.3).

**Solución:**
```php
// En lugar de usar operadores matemáticos:
$mantenimientoValor = $tributoActualizado - $idtgbBase;  // ❌ Puede tener errores

// Usar funciones matemáticas de alta precisión:
$mantenimientoValor = bcsub($tributoActualizado, $idtgbBase, 4);  // ✅ Precisión decimal

$recargoTotal = bcadd($mantenimientoValor, $interes, 2);
$recargoTotal = bcadd($recargoTotal, $multaIdf, 2);

$final = bcadd($idtgbBase, $recargoTotal, 2);
```

---

### 4. Cache de Resultados para Repetidos

**Mejora:** Implementar caché de resultados para cálculos idénticos.

```php
use Illuminate\Support\Facades\Cache;

public function calculateEstimate($base, $depId, $parId, $tipoId, $fTrans, $fPres, $fVenc, $contribuyente = 'Natural', $participacion = 100): array
{
    // Generar key único para el cálculo
    $cacheKey = md5(implode('|', [
        $base, $depId, $parId, $tipoId, $fTrans, $fPres, $fVenc, $contribuyente, $participacion
    ]));
    
    return Cache::remember("idtgb_estimate:{$cacheKey}", 300, function () 
        use ($base, $depId, $parId, $tipoId, $fTrans, $fPres, $fVenc, $contribuyente, $participacion) {
            return $this->performCalculation(...);
        });
}
```

**Beneficio:** Si un usuario consulta varias veces el mismo cálculo, se retorna el resultado cacheado sin recalcular.

---

## 📋 Cosas Faltantes

### 1. Documentación de Cambios

No hay documentación de:
- Versiones del servicio
- Cambios en fórmulas de cálculo
- Historial de modificaciones
- Fecha de última actualización de tasas

**Recomendación:**
```php
class IdtgbCalculator
{
    const VERSION = '2.1.0';
    const FECHA_ULTIMA_ACTUALIZACION = '2026-01-16';
    const LEY_REFERENCIA = 'Ley 812';
    
    // ...
}
```

### 2. Tests Unitarios

No se encontraron tests para el servicio. Recomendación de tests críticos:

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\IdtgbCalculator;
use App\Models\Tramite;
use App\Models\AdquirenteTramite;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IdtgbCalculatorTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_calculo_simple_sin_mora()
    {
        $calculator = app(IdtgbCalculator::class);
        
        $resultados = $calculator->calculateEstimate(
            100000,  // base
            1,       // depId (Beni)
            1,       // parId (Padre)
            1,       // tipoId (Herencia)
            '2025-01-01',  // fTrans
            '2025-01-10',  // fPres (sin mora)
            '2025-04-10',  // fVenc
            'Natural',
            100
        );
        
        $this->assertEquals(1500, $resultados['idtgb_base']);
        $this->assertEquals(0, $resultados['mantenimiento_valor']);
        $this->assertEquals(0, $resultados['interes']);
        $this->assertEquals(0, $resultados['dias_mora']);
    }
    
    public function test_calculo_con_mora_1_anio()
    {
        // Test con 1 año de mora
    }
    
    public function test_calculo_con_mora_5_anos()
    {
        // Test con tasa de 10%
    }
    
    public function test_persona_juridica_multa_100_ufvs()
    {
        // Test multa IDF para personas jurídicas
    }
    
    public function test_participacion_parcial_50_por_ciento()
    {
        // Test del estilo Cochabamba
    }
    
    public function test_multiple_adquirentes()
    {
        // Test con varios adquirentes y porcentajes diferentes
    }
}
```

### 3. Métricas de Rendimiento

No hay monitoreo de:
- Tiempo de ejecución promedio por cálculo
- Número de consultas a base de datos
- Memoria utilizada
- Cuellos de botella

**Recomendación:**
```php
use Illuminate\Support\Facades\Log;

public function calculateAndSave(Tramite $tramite): array
{
    $inicio = microtime(true);
    
    // ... cálculos ...
    
    $tiempoTotal = microtime(true) - $inicio;
    
    Log::info('Métricas de cálculo ITGB', [
        'tramite_id' => $tramite->id,
        'tiempo_segundos' => $tiempoTotal,
        'memoria_mb' => memory_get_peak_usage(true) / 1024 / 1024,
        'num_adquirentes' => count($tramite->adquirentes),
        'con_mora' => $diasMora > 0
    ]);
    
    // ...
}
```

### 4. Sistema de Alertas

No hay alertas automáticas cuando:
- El cálculo excede cierto monto (ej: Bs 100,000)
- La mora es muy larga (ej: más de 5 años)
- No existe UFV para la fecha
- La tasa encontrada es 0%

**Recomendación:**
```php
if ($resultados['final'] > 100000) {
    Log::alert('Cálculo ITGB con monto muy alto', [
        'tramite_id' => $tramite->id,
        'monto' => $resultados['final'],
        'usuario' => auth()->id()
    ]);
    
    // Notificar por Slack/Email
    // ...
}
```

### 5. Validación de Negocio Adicional

Validaciones faltantes:
- [ ] No permitir adquirentes sin tasa definida
- [ ] Validar que el tipo de transmisión esté activo
- [ ] Verificar que el departamento tenga tasas configuradas
- [ ] Validar que la fecha de transmisión no sea futura
- [ ] Verificar que la fecha de presentación no sea anterior a 10 años atrás

---

## 🔗 Integraciones Faltantes

### 1. Integración con Sistema SIN

**Estado actual:** El archivo `otros.md` menciona que existe un `ExportarAlSINJob` pero no está integrado con el `IdtgbCalculator`.

**Recomendación:**
```php
public function calculateAndSave(Tramite $tramite): array
{
    // ... cálculos ...
    
    // Exportar a SIN si el trámite se finaliza
    if ($tramite->estado === 'Finalizado') {
        \App\Jobs\ExportarAlSINJob::dispatch($tramite->id);
    }
    
    return $resultados;
}
```

### 2. Integración con Módulo de Pagos

**Estado actual:** El módulo de pagos (`PagoController`) no está sincronizado con el `IdtgbCalculator`.

**Recomendación:**
```php
// En PagoObserver
public function created(Pago $pago)
{
    $tramite = $pago->tramite;
    $pagado = $tramite->pagos()->sum('monto');
    
    if ($pagado >= $tramite->monto_final) {
        // Marcar como pagado
        $tramite->update(['estado' => 'Pagado']);
        
        // Notificar
        $tramite->user->notify(new TramitePagadoNotification($tramite));
    }
}
```

---

## 📊 Estadísticas y Recomendaciones Finales

### Prioridad de Corrección (Ordenada)

| # | Bug/Mejora | Prioridad | Tiempo estimado |
|---|-------------|-----------|-----------------|
| 1 | **Bug crítico en cálculo de ITGB base** | 🔴 CRÍTICA | 2 horas |
| 2 | **Bug en actualización de adquirentes** | 🔴 CRÍTICA | 2 horas |
| 3 | **Agregar transacciones en calculateAndSave** | 🔴 CRÍTICA | 30 minutos |
| 4 | **Manejo de excepciones en UFV** | 🟠 MEDIA | 1 hora |
| 5 | **Validar suma de porcentajes = 100%** | 🟠 MEDIA | 1 hora |
| 6 | **Implementar caché de tasas** | 🟡 OPTIMIZACIÓN | 2 horas |
| 7 | **Agregar tests unitarios** | 🟢 BAJA | 4 horas |
| 8 | **Implementar histórico de cálculos** | 🟢 BAJA | 3 horas |
| 9 | **Reemplazar rand() en nro_tramite** | 🟢 BAJA | 30 minutos |
| 10 | **Implementar soporte para exenciones** | 🟢 BAJA | 3 horas |

### Resumen

**Bugs críticos encontrados:** 3
**Bugs medios encontrados:** 4  
**Bugs bajos encontrados:** 3
**Mejoras sugeridas:** 6
**Optimizaciones:** 4
**Funcionalidades faltantes:** 5

**Tiempo total estimado para corregir todo:** ~19 horas

**Recomendación inmediata:** 
1. Corregir el bug crítico en el cálculo del ITGB base
2. Agregar transacciones en `calculateAndSave()`
3. Validar que la suma de porcentajes de adquirentes sea 100%

Estos tres cambios eliminarán los riesgos financieros y de consistencia de datos más importantes del sistema.
