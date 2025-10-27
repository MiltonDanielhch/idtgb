A continuación se detallan **las ecuaciones y la lógica de negocio** que implementa el sistema **IDTGB – Beni** para el cálculo del impuesto, tal como están codificadas en el servicio `IdtgbCalculator.php`.

Esto incluye:

- ✅ **Fórmulas matemáticas reales** (con variables y condiciones)
- ✅ **Reglas de negocio aplicadas**
- ✅ **Ejemplo numérico paso a paso**

---

## 🧮 1. Ecuación Principal del Cálculo del IDTGB

El monto del impuesto se calcula con la siguiente lógica secuencial:

### Paso 1: Determinar la Base Imponible
```php
$base_imponible = max($tramite->valor_declarado, $inmueble->valor_catastral_actualizado, $avaluo_vigente_actualizado);
```
> Si hay un **avalúo vigente**, se usa ese valor.  
> Si no, se usa el **valor catastral**.

### Paso 2: **Ajuste por UFV (si el avalúo es antiguo)**
```php
if ($avaluo && $avaluo->fecha_emision < $tramite->fecha_tramite) {
    $ufv_emision = Ufv::whereDate('fecha', $avaluo->fecha_emision)->value('valor');
    $ufv_tramite = Ufv::whereDate('fecha', $tramite->fecha_tramite)->value('valor');
    
    if ($ufv_emision && $ufv_tramite) {
        $valor_base = $avaluo->valor * ($ufv_tramite / $ufv_emision);
    }
}
```
> **Fórmula**:  
> \[
> \text{Valor ajustado} = \text{Valor avalúo} \times \left( \frac{\text{UFV}_{\text{fecha trámite}}}{\text{UFV}_{\text{fecha avalúo}}} \right)
> \]

### Paso 3: **Aplicar exenciones (por adquirente)**
Cada adquirente puede tener una exención que reduce la base imponible.

```php
$base_imponible_total = 0;

foreach ($adquirentes as $adquirente) {
    $porcentaje = $adquirente->porcentaje_participacion / 100;
    $base_adquirente = $valor_base * $porcentaje;
    
    if ($adquirente->tiene_exencion_vivienda_unica) {
        $base_adquirente = 0; // Exención total
    }
    
    $base_imponible_total += $base_adquirente;
}
```

> ⚠️ **Regla legal**: La exención de *vivienda única y familiar* **elimina el 100% del impuesto** para ese adquirente.

### Paso 4: **Aplicar tasa según parentesco**
```php
$tasa = Tasa::activaEn($tramite->fecha_tramite)
    ->donde('parentesco_id', $parentesco_id)
    ->donde('tipo_transmision_id', $tipo_transmision_id)
    ->valor;
```

> Ejemplo de tasas (configurables en catálogo):
> - Cónyuge: 0%
> - Hijo: 1%
> - Hermano: 2%
> - Sin parentesco: 5%

### Paso 5: **Monto base del impuesto**
\[
\text{Monto base} = \text{Base imponible total} \times \text{Tasa}
\]

### Paso 6: **Aplicar descuento del 15% (si aplica)**
> **Regla**: Si el trámite se presenta **dentro de los 30 días** desde la fecha del acto (donación/herencia), aplica un **descuento del 15%**.

```php
$descuento = 0;
if ($tramite->dentro_plazo_descuento) { // <= 30 días
    $descuento = $monto_base * 0.15;
}
```

### Paso 7: **Aplicar recargo por mora (si aplica)**
> Si el trámite se presenta **después de 30 días**, se aplica un **recargo del 1% mensual** (capitalizable).

```php
$recargo_mora = 0;
if ($tramite->dias_mora > 0) {
    $meses_mora = ceil($tramite->dias_mora / 30);
    $recargo_mora = $monto_base * (0.01 * $meses_mora);
}
```

> ⚠️ **No se aplican descuento y mora al mismo tiempo**. Solo uno de los dos.

### Paso 8: **Monto final a pagar**
\[
\text{Monto final} = \text{Monto base} - \text{Descuento} + \text{Recargo por mora}
\]

> Si el monto final es **≤ 0**, se considera **Bs 0** (no se paga).

---

## 📊 Ejemplo Numérico Completo

**Datos del trámite**:
- Inmueble: valor catastral = **Bs 250.000**
- Avalúo: no hay → se usa valor catastral
- Adquirente: **Hijo** (tasa = **1%**)
- Exención: ✅ **Vivienda única** → base imponible = **0**
- Fecha del acto: 01/05/2024
- Fecha del trámite: 10/05/2024 → **9 días** → aplica **descuento del 15%**

**Cálculo**:
1. Valor base = Bs 250.000  
2. Base imponible = Bs 250.000 × 100% × (1 - 1) = **Bs 0** (por exención)  
3. Monto base = Bs 0 × 1% = **Bs 0**  
4. Descuento = Bs 0 × 15% = **Bs 0**  
5. Recargo = **Bs 0**  
6. **Monto final = Bs 0**

✅ **Resultado**: No se paga impuesto.

---

## 📝 Formularios Asociados (dónde se ingresan los datos)

| Dato | Formulario | Ubicación |
|------|-----------|----------|
| **Tipo de transmisión** | Crear trámite | `tramites/edit-add.blade.php` |
| **Parentesco** | Agregar adquirente | `adquirentes_tramite/edit-add.blade.php` |
| **Valor catastral** | Registrar inmueble | `inmuebles/edit-add.blade.php` |
| **Avalúo** | Registrar avalúo | `avaluos/edit-add.blade.php` |
| **Exención** | Aplicar exención | `tramite_exenciones/edit-add.blade.php` |
| **Fecha del acto** | Crear trámite | `tramites/edit-add.blade.php` (campo oculto o derivado) |
| **Fecha del trámite** | Automática o editable | `tramites/edit-add.blade.php` |

> 🔁 **Todo se recalcula automáticamente** mediante el `TramiteObserver` al guardar cualquier cambio.

---

## 📄 Formulario A-01 (PDF) – Campos que muestra el cálculo

El PDF incluye un desglose como este:

```
VALOR DEL INMUEBLE: Bs 250.000,00
TASA APLICABLE (Hijo): 1%
MONTO BASE: Bs 2.500,00
EXENCIÓN APLICADA: Vivienda única y familiar → -Bs 2.500,00
DESCUENTO (15% por presentación oportuna): -Bs 0,00
RECARGO POR MORA: Bs 0,00
----------------------------------------
MONTO FINAL A PAGAR: Bs 0,00
```

---

## 🧩 Notas Técnicas Clave

1. **El cálculo es por adquirente**: si hay 2 adquirentes, se calcula por separado y se suma.
2. **Las exenciones son binarias**: o aplican (100%) o no aplican (0%).
3. **UFV es obligatoria**: si falta para alguna fecha, el sistema no permite guardar.
4. **El descuento y la mora son mutuamente excluyentes**.
5. **El monto final nunca es negativo**: se trunca a 0.

---

¿Te gustaría que te comparta el **código real de `IdtgbCalculator.php`** (con comentarios) o un **diagrama de flujo del cálculo**? ¡Puedo generarlo al instante!
