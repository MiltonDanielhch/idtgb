# Especificación Técnica: Servicio `IdtgbCalculator`

## 1. Propósito y Diseño

La clase `App\Services\IdtgbCalculator` encapsula toda la lógica de negocio para el cálculo del Impuesto a la Transmisión Gratuita de Bienes (IDTGB). Su diseño separa la lógica de cálculo pura de las operaciones de base de datos, lo que facilita las pruebas, la reutilización y el mantenimiento.

*   **Responsabilidad Principal**: Calcular los montos de un `Tramite` (impuesto, exenciones, mora, total).
*   **Principios de Diseño**:
    *   **Lógica Pura Aislada**: El método `performCalculation` no tiene efectos secundarios (no lee ni escribe en la base de datos), lo que garantiza que para los mismos datos de entrada, el resultado sea siempre el mismo.
    *   **Separación de Comandos y Consultas**: El servicio ofrece métodos distintos para calcular y guardar (`calculateAndSave`) y para solo estimar (`calculateEstimate`).

---

## 2. API Pública del Servicio

Estos son los métodos diseñados para ser invocados desde los controladores.

### `calculateAndSave(Tramite $tramite): array`

Calcula el impuesto para un trámite y persiste los resultados en la base de datos.

*   **Uso**: En controladores internos cuando se crea o modifica un trámite.
*   **Flujo**:
    1.  Extrae los datos necesarios del modelo `$tramite` y sus relaciones.
    2.  Delega el cálculo al método privado `performCalculation()`.
    3.  Actualiza y guarda los campos del modelo `$tramite` (`total_idtgb`, `recargo_mora`, `monto_final`).
    4.  Actualiza los campos de cada `AdquirenteTramite` (`tasa_aplicada`, `idtgb_proporcional`).
*   **Retorno**: Un `array` con el desglose completo del cálculo.

### `calculateEstimate(...)`

Realiza un cálculo de estimación sin efectos secundarios.

*   **Uso**: Para la calculadora pública o vistas previas.
*   **Parámetros**: Recibe todos los datos necesarios como argumentos primitivos (`$baseImponible`, `$departamentoId`, etc.).
*   **Flujo**:
    1.  Prepara una estructura de datos simple (asumiendo un solo adquirente y sin exenciones).
    2.  Delega el cálculo al método privado `performCalculation()`.
*   **Retorno**: Un `array` con el desglose de la estimación.

---

## 3. Algoritmo Central de Cálculo

La lógica reside en el método privado `performCalculation`, que sigue estos pasos:

### **Paso 1: Calcular el Impuesto Bruto por Adquirente**

Se itera sobre cada adquirente para determinar el impuesto que le corresponde.

1.  **Obtener Tasa**: Para cada adquirente, se busca la tasa aplicable (`TasaAplicada`) usando el método auxiliar `tasaVigente()`.
2.  **Calcular Impuesto Proporcional**: Se aplica la siguiente fórmula:
    ```
    IdtgbParcial = round(BaseImponible * (PorcentajeParticipacion / 100) * (TasaAplicada / 100), 2)
    ```
3.  **Sumar Total**: Se suman los `IdtgbParcial` de todos los adquirentes.
    ```
    IdtgbBrutoTotal = Σ(IdtgbParcial)
    ```

### **Paso 2: Calcular el Total de Exenciones**

Se itera sobre cada exención aplicada al trámite.

1.  **Determinar Monto de Exención**: Se usa una estructura `match` sobre el tipo de exención:
    *   Si es `'porcentaje'`: `MontoExencion = min(BaseImponible * (ValorExencion / 100), MontoMaximoExencion)`
    *   Si es `'monto_fijo'`: `MontoExencion = min(ValorExencion, MontoMaximoExencion)`
2.  **Sumar Total**: Se suman los `MontoExencion` de todas las exenciones.
    ```
    TotalExenciones = Σ(MontoExencion)
    ```

### **Paso 3: Calcular el Impuesto Neto (IDTGB Neto)**

Se resta el total de exenciones del impuesto bruto. El resultado no puede ser negativo.

```
IdtgbNeto = round(max(0, IdtgbBrutoTotal - TotalExenciones), 2)
```

### **Paso 4: Calcular el Recargo por Mora**

Se calcula si la fecha actual ha superado la fecha de vencimiento.

1.  **Calcular Días de Mora**: `DiasMora = Carbon::now()->diffInDays(FechaVencimiento, false)`
2.  **Aplicar Fórmula de Recargo**: La fórmula aplica un 1% diario sobre el `IdtgbNeto`, con un tope de 60 días (60%).
    ```
    RecargoMora = round(IdtgbNeto * 0.01 * min(DiasMora, 60), 2)
    ```

### **Paso 5: Calcular el Monto Final a Pagar**

Se suma el impuesto neto y el recargo por mora.

```
MontoFinal = round(IdtgbNeto + RecargoMora, 2)
```

### **Paso 6: Estructurar el Resultado**

El método retorna un `array` asociativo que contiene todos los valores calculados, tanto los totales como los desgloses por adquirente y exención.

---

## 4. Lógica de Soporte

### `tasaVigente(...)`

Este método privado es clave para obtener la tasa correcta de la base de datos. Su consulta SQL se puede describir como:

```sql
SELECT * FROM tasas
WHERE
  departamento_id = ? AND
  parentesco_id = ? AND
  (tipo_transmision_id = ? OR tipo_transmision_id IS NULL) AND
  vigente_desde <= ? AND
  (vigente_hasta IS NULL OR vigente_hasta >= ?)
ORDER BY tipo_transmision_id DESC -- Para dar prioridad a la tasa específica sobre la general
LIMIT 1;
```

La condición `(tipo_transmision_id = ? OR tipo_transmision_id IS NULL)` es la más importante, ya que implementa una **lógica de fallback**: el sistema busca una tasa específica para el tipo de transmisión, y si no la encuentra, busca una tasa general (con `tipo_transmision_id` nulo) que aplique a todos los tipos.