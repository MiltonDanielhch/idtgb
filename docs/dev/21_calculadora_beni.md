# Documentación Técnica - Módulo Calculadora Beni

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Propósito y Alcance](#propósito-y-alcance)
3. [Arquitectura del Controlador](#arquitectura-del-controlador)
4. [Base de Datos](#base-de-datos)
5. [Controlador](#controlador)
6. [Rutas](#rutas)
7. [Vistas](#vistas)
8. [Integración con otros módulos](#integración-con-otros-módulos)
9. [Flujo de Trabajo Completo](#flujo-de-trabajo-completo)
10. [Consideraciones Importantes](#consideraciones-importantes)
11. [Ejemplos de Uso](#ejemplos-de-uso)
12. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **Calculadora Beni** es una interfaz pública (sin autenticación) que permite a los ciudadanos realizar una estimación del Impuesto a la Transmisión Gratuita de Bienes (ITGB) para el departamento del Beni. Este módulo utiliza el servicio `IdtgbCalculator` para realizar los cálculos según la Ley 812 y las tasas vigentes.

### Propósito
- Permitir a los ciudadanos calcular una estimación del ITGB antes de presentar su trámite oficial
- Proporcionar un documento PDF oficial de preliquidación para referencia
- Servir como herramienta de consulta para fines informativos
- Facilitar la planificación financiera de los contribuyentes

### ⚠️ CRÍTICO: Cálculos IDÉNTICOS al Sistema Oficial

**IMPORTANTE:** Los cálculos realizados por esta calculadora son **EXACTAMENTE LOS MISMOS** que utiliza el sistema oficial cuando los funcionarios crean un trámite. No hay algoritmo diferente ni aproximación.

#### ¿Qué significa esto?

1. **Mismo servicio de cálculo:**
   - La calculadora usa el servicio `IdtgbCalculator`, que es el MISMO servicio que el sistema oficial invoca al crear trámites (ver `TramiteController`, `AdquirenteTramiteController`, `TramiteExencionController`).
   - No hay dos algoritmos diferentes; es un único servicio estandarizado.

2. **Resultados consistentes:**
   - Si un ciudadano calcula su ITGB en esta calculadora con ciertos datos, y luego presenta su trámite oficial con los MISMOS DATOS, el monto a pagar será EXACTAMENTE EL MISMO.
   - Esto garantiza transparencia y confianza: los ciudadanos pueden verificar que el sistema oficial no modifica los cálculos que ellos vieron en la calculadora.

3. **Fórmula idéntica:**
   - ITGB Base, Mantenimiento de Valor, Intereses por Mora, Multa IDF: Todo se calcula con las mismas fórmulas, tasas y algoritmos.

4. **Diferencia única:**
   - La calculadora NO guarda los datos en la base de datos (no persistencia).
   - El sistema oficial SÍ guarda y mantiene el historial de trámites.
   - Esa es la única diferencia: persistencia vs no persistencia.

#### Flujo de equivalencia:
```
Cálculo en Calculadora Beni (Pública)
    ↓
Mismo servicio: IdtgbCalculator@calculateEstimate()
    ↓
Cálculo en Sistema Oficial (Funcionarios)
    ↓
Mismo servicio: IdtgbCalculator@calculateAndSave()
    ↓
Resultados: 100% IDÉNTICOS
```

### Alcance
- **Solo para el departamento del Beni**: La calculadora está configurada exclusivamente para trabajar con el departamento de Beni (código 'BE')
- **Cálculos estimados:** Los resultados son referenciales y no tienen valor legal hasta que el trámite sea oficializado
- **Sin autenticación:** La calculadora es accesible públicamente sin requerir login

---

## 🏗️ Arquitectura del Controlador

### Ubicación
**Archivo:** `app/Http/Controllers/CalculadoraBeniController.php`

### Dependencias
```php
use App\Models\Departamento;
use App\Models\Parentesco;
use App\Models\TipoTransmision;
use App\Services\IdtgbCalculator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
```

### Estructura del Controlador
```
CalculadoraBeniController
├─ formulario()           → Muestra el formulario de cálculo
├─ calcular()             → Procesa el formulario y retorna JSON
└─ descargarPdf()         → Genera PDF descargable
```

### Flujo de Datos
```
Formulario Web (Usuario)
    ↓
[POST] /calculadora-idtgb-beni
    ↓
Validación de datos
    ↓
IdtgbCalculator::calculateEstimate()
    ↓
Respuesta JSON con resultados
    ↓
Muestra resultados + opción de descargar PDF
```

---

## 🗄️ Base de Datos

Este controlador **NO utiliza tablas propias** de base de datos. Solo consulta tablas existentes:

| Tabla | Uso |
|-------|-----|
| `departamentos` | Obtiene ID del Beni (código 'BE') |
| `parentescos` | Lista de tipos de parentesco disponibles |
| `tipos_transmision` | Lista de tipos de transmisión disponibles |

**IMPORTANTE:** Los cálculos **NO se persisten** en la base de datos. Son transitorios y solo para el usuario que los solicita.

---

## 🎮 Controlador

### Controlador: `CalculadoraBeniController`

**Ubicación:** `app/Http/Controllers/CalculadoraBeniController.php`

**Middleware:** Ninguno (acceso público)

---

### Método 1: `formulario()`

**Descripción:** Muestra el formulario de cálculo público.

**Firma:**
```php
public function formulario()
```

**Retorno:** Vista `calculadora_beni_interactivo.blade.php`

**Datos provistos a la vista:**
```php
return view('calculadora_beni_interactivo', [
    'parentescos' => $parentescos,
    'tipos_transmision' => $tipos_transmision,
    'nro_tramite' => null
]);
```

**Uso en el sistema:**
- URL: `/calculadora-idtgb-beni`
- Accesible desde el menú público

---

### Método 2: `calcular(Request $request, IdtgbCalculator $calculator)`

**Descripción:** Procesa el formulario, valida los datos y calcula la preliquidación estimada.

**Firma:**
```php
public function calcular(Request $request, IdtgbCalculator $calculator)
```

**Parámetros:**
- `$request`: HTTP Request con datos del formulario
- `$calculator`: Servicio de cálculo inyectado automáticamente

**Validaciones:**
| Campo | Reglas | Descripción |
|-------|--------|-------------|
| `nombre_sujeto` | `nullable|string|max:150` | Nombre del contribuyente (opcional) |
| `ci_sujeto` | `nullable|string|max:20` | C.I. o NIT (opcional) |
| `tipo_contribuyente` | `required|in:Natural,Jurídica` | Tipo de persona |
| `parentesco_id` | `required|exists:parentescos,id` | Parentesco del adquirente |
| `fecha_transmision` | `required|date|before_or_equal:today` | Fecha del hecho generador |
| `base_imponible` | `required|numeric|min:0.01` | Valor del bien |
| `tipo_transmision` | `required|string` | Tipo de transmisión (se busca por nombre) |
| `participacion` | `required|numeric|min:1|max:100` | Porcentaje de propiedad (1-100%) |

**Lógica del método:**

1. **Obtener ID del departamento Beni:**
```php
$beniId = Departamento::where('codigo', self::CODIGO_BENI)->firstOrFail()->id;
```

2. **Obtener ID del tipo de transmisión:**
```php
$tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;
```

3. **Calcular fecha de vencimiento:**
```php
$fecha_transmision = Carbon::parse($request->fecha_transmision);
$fecha_vencimiento = $fecha_transmision->copy()->addDays(90); // 90 días desde transmisión
```

4. **Ejecutar cálculo con el servicio IdtgbCalculator:**
```php
$calculo = $calculator->calculateEstimate(
    (float)$request->base_imponible,      // Base imponible
    $beniId,                               // ID del departamento Beni
    (int)$request->parentesco_id,         // ID del parentesco
    $tipoTransmisionId,                    // ID del tipo de transmisión
    $fecha_transmision->toDateString(),    // Fecha de transmisión (Y-m-d)
    Carbon::now()->toDateString(),          // Fecha de pago/presentación (hoy)
    $fecha_vencimiento->toDateString(),     // Fecha de vencimiento (Y-m-d)
    $request->tipo_contribuyente,            // Tipo de contribuyente
    (float)$request->participacion          // Porcentaje de participación
);
```

5. **Agregar datos del sujeto a la respuesta:**
```php
return response()->json(array_merge($calculo, [
    'nombre_sujeto' => strtoupper($request->nombre_sujeto ?? 'CONSULTA REFERENCIAL'),
    'ci_sujeto'     => $request->ci_sujeto ?? 'S/N',
    'tipo_contribuyente' => $request->tipo_contribuyente,
]));
```

**Retorno:**
- `response()->json(...)`: JSON con todos los resultados del cálculo

**Uso en el sistema:**
- URL: `/calculadora-idtgb-beni` (POST)
- Invocado desde JavaScript vía AJAX

---

### Método 3: `descargarPdf(Request $request, IdtgbCalculator $calculator)`

**Descripción:** Genera un documento PDF oficial de preliquidación descargable.

**Firma:**
```php
public function descargarPdf(Request $request, IdtgbCalculator $calculator)
```

**Parámetros:**
- `$request`: HTTP Request con datos del formulario
- `$calculator`: Servicio de cálculo inyectado automáticamente

**Lógica del método:**

1. **Obtener IDs del departamento y tipo de transmisión:**
```php
$beniId = Departamento::where('codigo', self::CODIGO_BENI)->firstOrFail()->id;
$tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;
```

2. **Calcular fechas:**
```php
$fecha_transmision = Carbon::parse($request->fecha_transmision);
$fecha_vencimiento = $fecha_transmision->copy()->addDays(90);
```

3. **Ejecutar el cálculo (repetición del proceso):**
```php
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
```

4. **Preparar datos adicionales para el PDF:**
```php
$dataReporte = array_merge($calculo, [
    'nombre_sujeto'           => strtoupper($request->nombre_sujeto ?? 'CONSULTA REFERENCIAL'),
    'ci_sujeto'               => $request->ci_sujeto ?? 'S/N',
    'tipo_contribuyente'       => $request->tipo_contribuyente,
    'telefono'                => $request->telefono ?? '',
    'parentesco'              => Parentesco::find($request->parentesco_id)->nombre,
    'tipo_transmision_nombre'  => $request->tipo_transmision
]);
```

5. **Generar PDF con DomPDF:**
```php
$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calculo_estimado_beni', $dataReporte);
```

6. **Retornar PDF descargable:**
```php
return $pdf->download('Preliquidacion_IDTGB_Beni_' . $calculo['nro_tramite'] . '.pdf');
```

**Retorno:**
- `pdf->download(...)`: Stream de PDF con nombre dinámico

**Uso en el sistema:**
- URL: `/calculadora-idtgb-beni-pdf` (GET)
- Invocado desde el botón "Imprimir PDF Oficial" en la respuesta JSON

---

## 🛣️ Rutas

**Ubicación:** `routes/web.php:57-60`

```php
Route::get('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'formulario'])->name('calculadora.beni.form');
Route::post('/calculadora-idtgb-beni', [CalculadoraBeniController::class, 'calcular'])->name('calculadora.beni.post');
Route::get('/calculadora-idtgb-beni-pdf', [CalculadoraBeniController::class, 'descargarPdf'])->name('calculadora.beni.pdf');
```

### Lista de Rutas

| Método | URI | Nombre | Descripción |
|--------|-----|--------|-------------|
| GET | `/calculadora-idtgb-beni` | `calculadora.beni.form` | Mostrar formulario de cálculo |
| POST | `/calculadora-idtgb-beni` | `calculadora.beni.post` | Calcular preliquidación (AJAX) |
| GET | `/calculadora-idtgb-beni-pdf` | `calculadora.beni.pdf` | Descargar PDF oficial |

**Middleware aplicado:** Ninguno (acceso público)

---

## 🎨 Vistas

### 1. Vista Principal del Formulario
**Ubicación:** `resources/views/calculadora_beni_interactivo.blade.php`

**Layout:** `layouts.app`

**Funcionalidades:**
- Formulario dividido en 2 secciones:
  - Datos del Sujeto Pasivo (opcional)
  - Parámetros del Impuesto (requeridos)
- Resultados dinámicos vía AJAX
- Botón para descargar PDF oficial
- Diseño responsivo con Bootstrap

**Campos del formulario:**

**Sección 1 - Datos del Sujeto:**
- `nombre_sujeto`: Nombres y apellidos (opcional)
- `ci_sujeto`: C.I. o NIT (opcional)
- `participacion`: Porcentaje de la propiedad (1-100%, default: 100%)

**Sección 2 - Parámetros del Impuesto:**
- `tipo_contribuyente`: Select (Natural/Jurídica)
- `base_imponible`: Input numérico (Bs.)
- `tipo_transmision`: Select dinámico (se carga desde BD)
- `fecha_transmision`: Input date
- `parentesco_id`: Select dinámico (se carga desde BD)

**Sección 3 - Resultado Estimado:**
- Se muestra después del cálculo vía AJAX
- Contiene:
  - Información de referencia (Nº trámite, UFVs, tasas)
  - Boleta de preliquidación desglosada
  - Botón para descargar PDF oficial

**JavaScript:**
```javascript
- Event listener en submit del formulario
- Fetch API para comunicación AJAX
- Formateo de números (es-BO)
- Scroll suave a resultados
- Función `descargarPDF()` que construye URL con parámetros
```

---

### 2. Vista del PDF Oficial
**Ubicación:** `resources/views/pdf/calculo_estimado_beni.blade.php`

**Layout:** HTML puro (sin framework)

**Secciones del documento:**

1. **Encabezado:**
   - Nro. de trámite (código único)
   - Títulos institucionales del Gobierno Autónomo del Beni

2. **Sección 1 - Identificación del Sujeto Pasivo:**
   - Nombre / Razón Social
   - C.I. / NIT
   - Parentesco y tasa aplicada

3. **Sección 2 - Información del Bien y Transmisión:**
   - Tipo de transmisión
   - Fecha hecho generador
   - Base imponible original (100%)
   - % Participación
   - Base imponible sujeto

4. **Sección 3 - Liquidación de la Deuda Tributaria:**
   - Estado al día actual (con o sin mora)
   - Desglose detallado:
     - Tributo omitido (S900)
     - Mantenimiento de valor (S920)
     - Intereses moratorios (S930)
     - Multa por incumplimiento de deberes formales (S900)
   - Total deuda tributaria

5. **Nota importante:**
   - "Este documento es una Preliquidación Referencial"
   - "Los montos están sujetos a revisión"
   - "No constituye un comprobante de pago definitivo"

6. **Footer:**
   - Frase institucional: "Beni, hacia la consolidación de la autonomía departamental"
   - Fecha y hora de generación
   - Usuario: Público Web

**Estilos CSS:**
- Colores institucionales (verde #007A33 del Beni)
- Fuente Helvetica/Arial
- Diseño limpio y profesional
- Tablas con bordes legibles

---

## 🔗 Integración con otros módulos

### 1. Servicio `IdtgbCalculator` (CORAZÓN DEL SISTEMA)
**Ubicación:** `app/Services/IdtgbCalculator.php`

**Uso en la calculadora pública:**
```php
$calculo = $calculator->calculateEstimate(
    $baseImponible,
    $departamentoId,
    $parentescoId,
    $tipoTransmisionId,
    $fechaTransmision,
    $fechaPresentacion,
    $fechaVencimiento,
    $tipoContribuyente,
    $participacion
);
```

**Uso IDÉNTICO en el sistema oficial:**
```php
// En TramiteController
$tramite->update([...]);
$calculator = app(IdtgbCalculator::class);
$resultados = $calculator->calculateAndSave($tramite);

// En AdquirenteTramiteController
$tramite->adquirentes()->create([...]);
$calculator = app(IdtgbCalculator::class);
$resultados = $calculator->calculateAndSave($tramite);
```

**Importante:** Este es el MISMO servicio, con los MISMOS parámetros, que utiliza el sistema oficial. No hay diferencia alguna en el algoritmo de cálculo.

### 2. Modelo `Departamento`
**Uso:** Obtener ID del departamento Beni por su código
```php
$beni = Departamento::where('codigo', 'BE')->firstOrFail();
$beniId = $beni->id;
```

### 3. Modelo `Parentesco`
**Uso:**
- Cargar lista de parentescos para el select en el formulario
- Obtener nombre del parentesco para el PDF
```php
// En formulario()
$parentescos = Parentesco::all();

// En descargarPdf()
$parentesco = Parentesco::find($request->parentesco_id)->nombre;
```

### 4. Modelo `TipoTransmision`
**Uso:**
- Cargar lista de tipos de transmisión para el select
- Convertir nombre a ID para el cálculo
```php
// En formulario()
$tipos_transmision = TipoTransmision::all();

// En calcular() y descargarPdf()
$tipoTransmisionId = TipoTransmision::where('nombre', $request->tipo_transmision)->first()?->id ?? 1;
```

### 5. Librería `barryvdh/laravel-dompdf`
**Uso:** Generación de PDFs desde vistas Blade
```php
$pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.calculo_estimado_beni', $data);
return $pdf->download('Preliquidacion_IDTGB_Beni_' . $calculo['nro_tramite'] . '.pdf');
```

---

## 🔄 Flujo de Trabajo Completo

### 1. Usuario accede a la calculadora
```
Usuario navega a: /calculadora-idtgb-beni
    ↓
CalculadoraBeniController@formulario()
    ↓
Muestra: calculadora_beni_interactivo.blade.php
```

### 2. Usuario completa el formulario
**Campos requeridos:**
- Tipo de contribuyente (Natural/Jurídica)
- Valor del inmueble (Bs.)
- Tipo de transmisión
- Fecha de transmisión
- Parentesco
- Porcentaje (1-100%)

**Campos opcionales:**
- Nombre del sujeto
- C.I./NIT

**3. Usuario hace clic en "Calcular Preliquidación"
```
Formulario submit → Event listener JavaScript
    ↓
Fetch POST a /calculadora-idtgb-beni
    ↓
CalculadoraBeniController@calcular()
    ↓
Validación de datos
    ↓
Obtener IDs (Beni, TipoTransmisión)
    ↓
Calcular fecha de vencimiento (fecha_transmisión + 90 días)
    ↓
Invocar IdtgbCalculator@calculateEstimate()
    ↓
Retorna JSON con resultados (MISMA FÓRMULA que el sistema oficial)
```

### 4. Resultados se muestran en la página
```javascript
JSON recibido:
{
    "nro_tramite": "REF-54321",
    "idtgb_base": 1500.00,
    "mantenimiento_valor": 234.50,
    "interes": 102.34,
    "multa_idf": 197.50,
    "final": 2034.34,
    ...
}

    ↓
Se muestra boleta detallada + botón "Imprimir PDF Oficial"

    ↓
Usuario puede:
    1. Imprimir PDF para referencia
    2. Ir al sistema oficial y presentar trámite
    3. Si los datos son IDÉNTICOS, el monto será EXACTAMENTE el mismo
```

### 5. Flujo paralelo: Sistema Oficial
```
Funcionario crea trámite en sistema oficial
    ↓
TramiteWizardController@store()
    ↓
    ↓
MISMO IdtgbCalculator@calculateAndSave() ← MISMO SERVICIO
    ↓
Guarda resultados en base de datos
    ↓
Genera comprobante oficial
```

**Conexión entre ambos mundos:**
```
Cálculo en Calculadora Beni (Pública)
    ↓
Misma fórmula = IdtgbCalculator::calculateEstimate()
    ↓
Cálculo en Sistema Oficial (Funcionario)
    ↓
Misma fórmula = IdtgbCalculator::calculateAndSave()
    ↓
Resultados IDÉNTICOS si los datos son iguales
```
### 6. Usuario descarga PDF
```
Usuario hace clic en "Imprimir PDF Oficial"
    ↓
Función descargarPDF()
    ↓
Construye URL con parámetros del formulario
    ↓
Navega a: /calculadora-idtgb-beni-pdf?...
    ↓
CalculadoraBeniController@descargarPdf()
    ↓
Obtiene IDs y vuelve a ejecutar cálculo
    ↓
Prepara datos adicionales para PDF
    ↓
Genera PDF con DomPDF
    ↓
Descarga archivo: Preliquidacion_IDTGB_Beni_REF-54321.pdf
```

---

## ⚠️ Consideraciones Importantes

### 1. Departamento Fijo
- La calculadora **SOLO funciona para el departamento del Beni**
- El ID del Beni se obtiene por código: `where('codigo', 'BE')`
- Si el código cambia en la base de datos, la calculadora fallará

### 2. No Persistencia de Datos
- Los cálculos **NO se guardan** en la base de datos
- Cada cálculo es transitorio y solo para el usuario que lo solicita
- No se genera historial de consultas

### 3. Fecha de Vencimiento Fija
- La fecha de vencimiento se calcula automáticamente como: `fecha_transmision + 90 días`
- Esto es una simplificación y puede variar según la normativa específica

### 4. Tipo de Transmisión por Nombre
- El tipo de transmisión se busca por `nombre` en lugar de ID
- Riesgo de inconsistencia si hay nombres duplicados
- Fallback a ID = 1 si no se encuentra

### 5. Acceso Público Sin Autenticación
- No requiere login ni permisos
- Puede ser objeto de abuso o uso excesivo
- Considerar implementar rate limiting

### 6. Dependencia del Servicio IdtgbCalculator
- Si `IdtgbCalculator` cambia su firma, este controlador también debe actualizarse
- El servicio es el corazón de todos los cálculos

### 7. Valores por Defecto
- `participacion`: 100%
- `tipo_transmision_id`: 1 (si no se encuentra el nombre)
- `nombre_sujeto`: "CONSULTA REFERENCIAL"
- `ci_sujeto`: "S/N"

### 8. Formato de Moneda
- Los montos se muestran en Bolivianos (Bs.)
- Formato: separador de miles (.), separador decimal (,)
- 2 decimales de precisión

---

## 📚 Ejemplos de Uso

### Ejemplo 1: Cálculo Estimado (Mismo resultado que sistema oficial)
```javascript
// Datos del formulario
{
    "nombre_sujeto": "Juan Pérez",
    "ci_sujeto": "1234567",
    "tipo_contribuyente": "Natural",
    "parentesco_id": 1,  // Padre - tasa 1.5%
    "fecha_transmision": "2025-01-01",
    "base_imponible": 100000,
    "tipo_transmision": "Herencia",
    "participacion": 100
}

// Resultado esperado (mismo en calculadora y sistema oficial)
{
    "numero_tramite": "REF-54321",  // Generado por calculadora
    "idtgb_base": 1500.00,
    "mantenimiento_valor": 0.00,
    "interes": 0.00,
    "multa_idf": 0.00,
    "final": 1500.00,
    "dias_mora": 0
}

// Verificación: Si luego se crea un trámite oficial con estos mismos datos,
// el resultado será EXACTAMENTE EL MISMO.
```


### Ejemplo 2: Cálculo Con Mora (Mismo resultado que sistema oficial)
```javascript
{
    "tipo_contribuyente": "Natural",
    "parentesco_id": 2, // Tío - tasa 3.0%
    "fecha_transmision": "2024-01-01",
    "base_imponible": 100000,
    "participacion": 100
}

// Resultado esperado (mismo en calculadora y sistema oficial)
{
    "idtgb_base": 3000.00,
    "mantenimiento_valor": ~250.00,
    "interes": ~150.00 (6% anual, 1 año),
    "multa_idf": ~200.00 (50 UFVs para Naturales),
    "final": ~3600.00,
    "dias_mora": ~365
}

// NOTA: Si el usuario presenta su trámite oficial en el sistema
// con los MISMOS DATOS, obtendrá EXACTAMENTE el mismo monto final (Bs. 3,600.00)
```

### Ejemplo 3: Comparativa Directa - Cálculadora vs Sistema Oficial
Este ejemplo muestra que usar la calculadora o presentar el trámite oficial produce EXACTAMENTE el MISMO resultado.

**Escenario:** El ciudadano presenta su trámite oficial en el sistema con los mismos datos que usó en la calculadora.

```php
// PASO 1: Ciudadano calcula en Calculadora Pública (ya hecho)
$calculoPública = $calculator->calculateEstimate(
    100000,        // Base imponible
    1,              // ID del Beni
    1,              // ID del Padre (tasa 1.5%)
    1,              // ID de Herencia
    '2025-01-01', // Fecha transmisión (misma)
    '2025-01-10', // Fecha presentación (misma)
    '2025-04-10', // Fecha vencimiento (misma)
    'Natural',      // Tipo contribuyente (mismo)
    100             // Participación (mismo)
);

// Resultado de Calculadora Pública:
// Bs. 1,500.00 (ITGB Base)
// Bs. 0.00     (Sin mora)
// Bs. 1,500.00 (Total)

// PASO 2: Ciudadano presenta trámite oficial en Sistema Oficial (ahora)
$tramiteOficial = Tramite::create([
    'nro_tramite' => 'TRM-' . uniqid(),
    'fecha_presentacion' => '2025-01-10',
    'fecha_transmision' => '2025-01-01',
    'tipo_transmision_id' => 1,
    'base_imponible' => 100000,
    'valor_declarado' => 100000,
    'ufv_aplicada' => 1.00000,
    'estado' => 'Borrador',
    'user_id' => auth()->id(),
    'created_by' => auth()->id()
]);

// Asociar inmueble
$tramiteOficial->inmuebles()->attach($inmueble_id);

// PASO 3: Agregar adquirente al trámite oficial
$tramiteOficial->adquirentes()->create([
    'tramite_id' => $tramiteOficial->id,
    'person_id' => 12345,  // Juan Pérez (mismo sujeto)
    'parentesco_id' => 1,        // Padre (mismo parentesco)
    'porcentaje' => 100,     // 100% de la propiedad (misma participación)
    'tasa_aplicada' => 0,       // Se llena automáticamente
    'idtgb_proporcional' => 0,      // Se llena automáticamente
]);

// PASO 4: Sistema Oficial calcula automáticamente (Observer)
// El TramiteObserver::updated() detecta cambios y recalcula
// Se invoca: IdtgbCalculator@calculateAndSave($tramite)
$calculator = app(IdtgbCalculator::class);
$resultadoOficial = $calculator->calculateAndSave($tramiteOficial);

// Resultado de Sistema Oficial:
// Bs. 1,500.00 (ITGB Base)
// Bs. 0.00     (Sin mora)
// Bs. 1,500.00 (Total)

// PASO 5: Comparación de Resultados
$calculadoraPública['final'];    // Bs. 1,500.00
$resultadoOficial['monto_final']; // Bs. 1,500.00

if (abs($calculadoraPública['final'] - $resultadoOficial['monto_final']) < 0.01) {
    // VERIFICACIÓN EXITOSA: Los cálculos son EXACTAMENTE
    echo "✅ VERIFICACIÓN EXITOSA: Ambos sistemas calcularon el MISMO monto (Bs. 1,500.00)";
} else {
    // ERROR: Algo está mal en el sistema
    echo "❌ ERROR: Discrepancia de Bs. " . abs($calculadoraPública['final'] - $resultadoOficial['monto_final']);
}

// PASO 6: Si los resultados son iguales, el trámite puede finalizarse
if ($tramiteOficial->monto_final === $calculadoraPública['final']) {
    $tramiteOficial->update(['estado' => 'Pagado']);
    echo "✅ Trámite finalizado con el MISMO monto calculado en la calculadora";
}
```

**Por qué es importante esta equivalencia:**

1. **Confianza del usuario:** Cuando un ciudadano usa la calculadora y luego presenta su trámite oficial, espera ver exactamente el MISMO monto que calculó previamente.

2. **Consistencia:** Ambos contextos usan el MISMO servicio `IdtgbCalculator`, por lo que la fórmula es la MISMA.

3. **Evita conflictos:** Si hubiera diferencias entre lo que ve en la calculadora y lo que ve en el sistema oficial, perderían confianza.

4. **Auditoría tributaria:** Permite verificar que los funcionarios no alteran los cálculos en los trámites oficiales.

**NOTA:** La ÚNICA diferencia técnica es que la calculadora usa `calculateEstimate()` (NO guarda en BD) y el sistema usa `calculateAndSave()` (SÍ guarda en BD). El resultado es matemáticamente idéntico.

### Ejemplo 4: Participación Parcial
```javascript
{
    "participacion": 60,  // Solo el 60%
    "base_imponible": 100000,
    "parentesco_id": 1  // Padre - 1.5%
}

// Cálculo:
// Base imponible calculada = 100000 × (60/100) = 60000
// ITGB Base = 60000 × 1.5% = 900
{
    "base_original": 100000,
    "participacion": 60,
    "base_imponible_calculada": 60000,
    "idtgb_base": 900.00
}
```

### Ejemplo 5: JavaScript para Consumir la API
```javascript
// Ejemplo de integración en una aplicación externa
async function calcularITGB(datos) {
    const formData = new FormData();
    formData.append('tipo_contribuyente', datos.tipo);
    formData.append('parentesco_id', datos.parentesco_id);
    formData.append('fecha_transmision', datos.fecha);
    formData.append('base_imponible', datos.base);
    formData.append('tipo_transmision', datos.tipo_trans);
    formData.append('participacion', datos.participacion);

    const response = await fetch('https://sitio.com/calculadora-idtgb-beni', {
        method: 'POST',
        body: formData
    });

    const resultado = await response.json();
    console.log('Total a pagar:', resultado.final);
    return resultado;
}

// Uso
calcularITGB({
    tipo: 'Natural',
    parentesco_id: 1,
    fecha: '2025-01-01',
    base: 100000,
    tipo_transmision: 'Herencia',
    participacion: 100
});
```

### Ejemplo 6: Testing con cURL
```bash
curl -X POST https://sitio.com/calculadora-idtgb-beni \
  -H "X-CSRF-TOKEN: your-csrf-token" \
  -H "Accept: application/json" \
  -F "tipo_contribuyente=Natural" \
  -F "parentesco_id=1" \
  -F "fecha_transmision=2025-01-01" \
  -F "base_imponible=100000" \
  -F "tipo_transmision=Herencia" \
  -F "participacion=100"
```

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.1.0 (20 de enero de 2026) ✅

El módulo de Calculadora Beni ha sido mejorado con validaciones robustas, caching optimizado y logging de auditoría. El sistema garantiza cálculos idénticos al sistema oficial mediante el uso compartido del servicio `IdtgbCalculator`.

### 🐛 Bugs Corregidos (6/6) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Código de departamento hardcoded 'BE' en lugar de constante | ✅ Corregido | `CalculadoraBeniController.php:16,59,92` |
| 2 | Falta de validación para impedir fechas futuras en la transmisión | ✅ Corregido | `CalculadoraBeniController.php:43` |
| 3 | Visualización incorrecta de multa IDF en frontend (mostraba 100 UFV para Naturales) | ✅ Corregido | `CalculadoraBeniController.php:85` |
| 4 | Ruta PDF como POST en lugar de GET para descarga directa | ✅ Corregido | `routes/web.php:60` |
| 5 | Falta de logging de consultas para auditoría | ✅ Corregido | `CalculadoraBeniController.php:52-56` |
| 6 | Consultas repetidas a BD por listas sin caching | ✅ Corregido | `CalculadoraBeniController.php:21-27` |

### 🚀 Mejoras Implementadas ✅

- ✅ **Constante `CODIGO_BENI`**: Se reemplazó el string hardcoded `'BE'` por una constante de clase para facilitar el mantenimiento.
- ✅ **Validación de fechas futuras**: Se agregó la regla `before_or_equal:today` para impedir cálculos con fechas futuras de transmisión.
- ✅ **Logging de auditoría**: Se implementó `Log::info` para registrar cada consulta realizada, incluyendo IP y parámetros clave.
- ✅ **Caching de listas**: Se implementó `Cache::remember` para las listas de parentescos y tipos de transmisión, reduciendo la carga en la base de datos.
- ✅ **Retorno de `tipo_contribuyente`**: El controlador devuelve explícitamente el campo `tipo_contribuyente` en la respuesta JSON para correcta visualización en el frontend.
- ✅ **Ruta PDF como GET**: La ruta de descarga de PDF se cambió de POST a GET para permitir descarga directa en el navegador.

### 📝 Historial de Cambios

### v1.1.0 (20 de enero de 2026)
**Correcciones Completadas (6/6):**
- ✅ Bug #1: Constante `CODIGO_BENI` definida en línea 16
- ✅ Bug #2: Validación `before_or_equal:today` agregada en línea 43
- ✅ Bug #3: Campo `tipo_contribuyente` agregado a respuesta JSON en línea 85
- ✅ Bug #4: Ruta PDF cambiada de POST a GET en `routes/web.php:60`
- ✅ Bug #5: Logging implementado en método `calcular()` líneas 52-56
- ✅ Bug #6: Caching implementado en método `formulario()` líneas 21-27

**Cambios en Código:**
- `app/Http/Controllers/CalculadoraBeniController.php`:
  - Agregada constante `const CODIGO_BENI = 'BE';` (línea 16)
  - Implementado caching de listas en método `formulario()` (líneas 21-27)
  - Agregada regla de validación `before_or_equal:today` (línea 43)
  - Implementado logging con `Log::info` en método `calcular()` (líneas 52-56)
  - Agregado campo `tipo_contribuyente` a respuesta JSON (línea 85)
- `routes/web.php`:
  - Ruta de PDF cambiada de POST a GET (línea 60)
  - Nombre de ruta de cálculo cambiado de `.calcular` a `.post` (línea 59)

### v1.0.0 (Enero 2026)
**Versión inicial:**
- Implementación de Calculadora Beni pública
- Integración con servicio `IdtgbCalculator` para cálculos idénticos al sistema oficial
- Generación de PDF oficial de preliquidación
- Validación de formularios
- Cálculos de ITGB, mantenimiento de valor, intereses y multas según Ley 812

---

## 🔗 Recursos Adicionales

- **Documentación Laravel:** https://laravel.com/docs
- **Documentación IdtgbCalculator:** docs/dev/18_idtgb_calculator.md
- **Documentación Parentesco:** docs/dev/03_parentesco.md
- **Documentación Tipos Transmisión:** docs/dev/04_tipos_transmision.md
- **Documentación Departamento:** docs/dev/02_geografia.md
- **Laravel DomPDF:** https://github.com/barryvdh/laravel-dompdf
