# Guía de Simulación Manual - Cálculo IDTGB (Ley 812 - Bolivia)

## Ejemplo Numérico: Mora de 5 Años (Pasa por Tramos 1 y 2)

### Datos del Ejemplo
- **Base Imponible:** Bs. 100,000
- **Tasa Aplicada:** 3% (Línea Directa)
- **Participación:** 100%
- **Tipo Contribuyente:** Natural
- **Fecha de Vencimiento:** 01/01/2021
- **Fecha de Pago:** 01/01/2026 (5 años de mora = 1,825 días)
- **UFV Vencimiento (01/01/2021):** 2.50000
- **UFV Pago (01/01/2026):** 3.50000

---

## PASO 1: Cálculo del Tributo Omitido (TO) en Bolivianos

```
IDTGB_Base = Base_Imponible × Tasa
IDTGB_Base = 100,000 × 0.03
IDTGB_Base = Bs. 3,000
```

---

## PASO 2: Conversión a UFV (Dominio Puro UFV)

Convertir el Tributo Omitido a UFV en la fecha de vencimiento:

```
TO_UFV = IDTGB_Base / UFV_Vencimiento
TO_UFV = 3,000 / 2.50000
TO_UFV = 1,200 UFV
```

---

## PASO 3: Cálculo de Intereses por Tramos Acumulados

### Tramo 1: Años 1-4 (Hasta 1,440 días) - Tasa 4%

```
n1 = min(1,825, 1,440) = 1,440 días
r1 = 0.04 (4%)

I1 = TO_UFV × ((1 + (r1/360))^n1 - 1)
I1 = 1,200 × ((1 + (0.04/360))^1440 - 1)

Cálculo paso a paso:
1. 0.04/360 = 0.000111111...
2. 1 + 0.000111111... = 1.000111111...
3. 1.000111111...^1440 = 1.169858...
4. 1.169858... - 1 = 0.169858...
5. 1,200 × 0.169858... = 203.83 UFV

I1 = 203.83 UFV
Saldo1 = TO_UFV + I1 = 1,200 + 203.83 = 1,403.83 UFV
```

### Tramo 2: Años 5-7 (Días 1,441-2,520) - Tasa 6%

```
n2 = min(1,825 - 1,440, 1,080) = 385 días
r2 = 0.06 (6%)

I2 = Saldo1 × ((1 + (r2/360))^n2 - 1)
I2 = 1,403.83 × ((1 + (0.06/360))^385 - 1)

Cálculo paso a paso:
1. 0.06/360 = 0.000166666...
2. 1 + 0.000166666... = 1.000166666...
3. 1.000166666...^385 = 1.065698...
4. 1.065698... - 1 = 0.065698...
5. 1,403.83 × 0.065698... = 92.25 UFV

I2 = 92.25 UFV
Saldo2 = Saldo1 + I2 = 1,403.83 + 92.25 = 1,496.08 UFV
```

### Tramo 3: Año 8 en adelante (Más de 2,520 días) - Tasa 10%

```
n3 = 0 (no aplica, mora de 5 años < 8 años)
I3 = 0 UFV
Saldo3 = Saldo2 = 1,496.08 UFV
```

### Total de Intereses en UFV

```
Intereses_Total_UFV = I1 + I2 + I3
Intereses_Total_UFV = 203.83 + 92.25 + 0
Intereses_Total_UFV = 296.08 UFV
```

---

## PASO 4: Deuda Tributaria en UFV

```
DT_UFV = TO_UFV + Intereses_Total_UFV
DT_UFV = 1,200 + 296.08
DT_UFV = 1,496.08 UFV
```

---

## PASO 5: Conversión a Bolivianos

```
Deuda_Tributaria_Bs = DT_UFV × UFV_Pago
Deuda_Tributaria_Bs = 1,496.08 × 3.50000
Deuda_Tributaria_Bs = Bs. 5,236.28
```

**Nota:** El mantenimiento de valor ya está implícito en esta conversión. 
- Tributo actualizado por inflación: 1,200 UFV × 3.50000 = Bs. 4,200
- Intereses actualizados: 296.08 UFV × 3.50000 = Bs. 1,036.28
- Total: Bs. 4,200 + Bs. 1,036.28 = Bs. 5,236.28

---

## PASO 6: Cálculo de Intereses en Bs (para mostrar)

```
Intereses_Bs = Deuda_Tributaria_Bs - (TO_UFV × UFV_Pago)
Intereses_Bs = 5,236.28 - (1,200 × 3.50000)
Intereses_Bs = 5,236.28 - 4,200
Intereses_Bs = Bs. 1,036.28
```

---

## PASO 7: Multa IDF (Incumplimiento a Deberes Formales)

```
Para Persona Natural: 50 × UFV_Pago
Multa_IDF = 50 × 3.50000
Multa_IDF = Bs. 175.00
```

---

## PASO 8: Liquidación Final

```
Monto_Total = Deuda_Tributaria_Bs + Multa_IDF
Monto_Total = 5,236.28 + 175.00
Monto_Total = Bs. 5,411.28
```

---

## Resumen de Liquidación para el Contribuyente

| Concepto | Monto (Bs) |
|----------|-----------|
| **IDTGB Base** | 3,000.00 |
| **Intereses** | 1,036.28 |
| **Multa IDF** | 175.00 |
| **TOTAL A PAGAR** | **5,411.28** |

### Desglose de Intereses por Tramo
- **Tramo 1 (4%):** Bs. 713.41 (203.83 UFV × 3.50000)
- **Tramo 2 (6%):** Bs. 322.88 (92.25 UFV × 3.50000)
- **Tramo 3 (10%):** Bs. 0.00
- **Total Intereses:** Bs. 1,036.28

### Valores UFV Aplicados
- **UFV Vencimiento (01/01/2021):** 2.50000
- **UFV Pago (01/01/2026):** 3.50000
- **Días de Mora:** 1,825 días (5 años)

---

## Verificación con Calculadora Científica

Para replicar este cálculo manualmente:

### Cálculo de I1 (Tramo 1):
```
1,200 × (1.000111111^1440 - 1) = 203.83 UFV
```

### Cálculo de I2 (Tramo 2):
```
1,403.83 × (1.000166666^385 - 1) = 92.25 UFV
```

### Conversión final:
```
(1,200 + 203.83 + 92.25) × 3.50000 = 5,236.28 Bs
5,236.28 + (50 × 3.50000) = 5,411.28 Bs
```

---

## Comparación con Sistema Anterior (Buggy)

### Sistema Anterior (Cálculo Incorrecto):
```
Mantenimiento = Tributo_Actualizado - IDTGB_Base
= (3,000 × 3.5/2.5) - 3,000
= 4,200 - 3,000
= Bs. 1,200 (Duplicación del ajuste por inflación)

Interés = Tributo_Actualizado × ((1 + 0.06/360)^1825 - 1)
= 4,200 × 0.3176...
= Bs. 1,334 (Tasa única incorrecta)

Total = 3,000 + 1,200 + 1,334 + 175 = Bs. 5,709
```

### Sistema Corregido (Ley 812):
```
Total = Bs. 5,411.28
Diferencia = Bs. 297.72 (5.2% menos)
```

---

## Notas Importantes

1. **Mantenimiento de Valor:** Ya no se calcula como recargo independiente. El ajuste por inflación va implícito en la conversión UFV final.

2. **Intereses Escalonados:** Se aplican tasas progresivas (4%, 6%, 10%) sobre saldos acumulados, no una sola tasa sobre todo el período.

3. **Precisión:** El cálculo en dominio UFV puro elimina errores de redondeo y asegura precisión según Ley 812.

4. **Aplicación:** Este cálculo es idéntico para funcionarios y calculadora pública, garantizando consistencia.
