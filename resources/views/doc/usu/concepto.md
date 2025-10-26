
## 📖 Glosario de Términos Clave

Antes de ver los módulos, aquí tienes 3 conceptos fundamentales:

*   **Disponente**: La persona que **entrega** el bien (ej. el donante, o el fallecido en una herencia).
*   **Adquirente**: La persona que **recibe** el bien (ej. el que recibe la donación, el heredero).
*   **Base Imponible**: Es el **monto sobre el cual se calcula el impuesto**. Por ley, el sistema siempre usará **el valor más alto** que encuentre entre:
    1.  El valor declarado por las partes.
    2.  El valor del avalúo técnico vigente.
    3.  El valor catastral del inmueble.

---

## 📚 Módulos del Sistema IDTGB – Beni

---

### 1. **Departamentos, Provincias y Municipios**  
**Concepto**: Catálogos geográficos que definen la ubicación administrativa de los inmuebles.  
**Propósito**: Asegurar que solo se gestionen inmuebles del **departamento del Beni**, y permitir estadísticas por zona.  
**Ejemplo**:  
> Un inmueble en *Trinidad* debe estar registrado como:  
> **Departamento**: Beni → **Provincia**: Cercado → **Municipio**: Trinidad.  
> Si alguien intenta registrar un inmueble de *Santa Cruz*, el sistema lo rechaza.  
**Relación con el IDTGB**: Solo los inmuebles del Beni están sujetos a este impuesto.

---

### 2. **Parentescos**  
**Concepto**: Lista de relaciones familiares válidas entre quien transmite y quien recibe el bien.  
**Propósito**: Determinar la **tasa del impuesto** (más cercanía = menor tasa).  
**Ejemplo**:  
> - *Cónyuge* → tasa 0% (exento en muchos casos)  
> - *Hijo* → tasa 1%  
> - *Sin parentesco* → tasa 5%  
**Relación con el IDTGB**: Es **clave** para el cálculo. El sistema usa esta tabla para aplicar la tasa correcta.

---

### 3. **Tasas**  
**Concepto**: Valores porcentuales del impuesto, definidos por combinación de:  
- Departamento  
- Parentesco  
- Tipo de transmisión  
- Vigencia (fecha de inicio y fin)  
**Propósito**: Aplicar la normativa tributaria vigente en cada momento.  
**Ejemplo**:  
> Del 01/01/2023 al 31/12/2024:  
> - *Donación a hijo* en Beni → **1%**  
> A partir del 01/01/2025:  
> - *Donación a hijo* en Beni → **1.5%**  
> El sistema usa la tasa vigente **en la fecha del trámite**.  
**Relación con el IDTGB**: Es la **base del cálculo**.

---

### 4. **Tipos de Transmisión**  
**Concepto**: Motivo legal de la transferencia gratuita del bien.  
**Propósito**: Clasificar el trámite y aplicar reglas específicas (documentos, tasas, exenciones).  
**Ejemplo**:  
> - *Donación*: requiere escritura pública  
> - *Herencia*: requiere certificado de defunción y partición  
**Relación con el IDTGB**: Algunas transmisiones tienen **tratamientos fiscales distintos**.

---

### 5. **Tipos de Inmueble**  
**Concepto**: Clasificación del bien (vivienda, terreno, comercial, etc.).  
**Propósito**: Ayudar en la valoración y aplicación de exenciones.  
**Ejemplo**:  
> La exención de *vivienda única* **solo aplica a inmuebles tipo “Vivienda”**.  
> Un terreno no califica, aunque sea la única propiedad.  
**Relación con el IDTGB**: Afecta la **aplicabilidad de exenciones**.

---

### 6. **Valores UFV**  
**Concepto**: Registro diario del valor de la **Unidad de Fomento a la Vivienda** (moneda de ajuste inflacionario).  
**Propósito**: Reexpresar montos históricos a valor actual.  
**Ejemplo**:  
> Un avalúo de Bs 200.000 emitido en enero (UFV = 2.50)  
> Se reexpresa en junio (UFV = 2.70):  
> `200.000 × (2.70 / 2.50) = Bs 216.000`  
**Relación con el IDTGB**: La ley exige que el impuesto se calcule sobre el valor del inmueble **al día de hoy**. Por eso, el sistema usa la UFV para "traer al presente" el valor de avalúos o valores catastrales antiguos, asegurando un cálculo justo y legal.

---

### 7. **Personas**  
**Concepto**: Registro de personas naturales o jurídicas (con CI o NIT).  
**Propósito**: Identificar a disponentes y adquirentes en los trámites.  
**Ejemplo**:  
> - **Persona natural**: María López, CI 1234567 LP  
> - **Persona jurídica**: Fundación Beni, NIT 1234567020  
**Relación con el IDTGB**: Se usan para:  
> - Determinar parentesco  
> - Aplicar exenciones (ej: ¿es su vivienda única?)  
> - Generar el formulario A-01

---

### 8. **Inmuebles**  
**Concepto**: Ficha técnica de un bien inmueble en el Beni.  
**Propósito**: Almacenar datos clave para el cálculo del impuesto.  
**Ejemplo**:  
> Catastro: `10-20-30-40`  
> Ubicación: Trinidad, Beni  
> Valor catastral: Bs 250.000  
> Superficie: 120 m²  
> ¿Vivienda única?: ✅ Sí  
**Relación con el IDTGB**: El **valor del inmueble** es la base del cálculo.

---

### 9. **Avalúos**  
**Concepto**: Valoración profesional del inmueble (alternativa al valor catastral).  
**Propósito**: Usar un valor más actual o preciso cuando el catastro está desactualizado.  
**Ejemplo**:  
> Perito certifica que una casa vale Bs 350.000 (mientras el catastro dice Bs 200.000).  
> El sistema usa el avalúo **si está vigente** (menos de 1 año).  
**Relación con el IDTGB**: Puede **aumentar o disminuir** la **Base Imponible** del impuesto.

---

### 10. **Trámites (Formulario A-01)**  
**Concepto**: Proceso completo de transmisión gratuita de un inmueble.  
**Propósito**: Centralizar todos los datos necesarios para calcular, pagar y emitir el formulario oficial.  
**Ejemplo**:  
> **Trámite IDTGB-2024-0001**  
> - Disponente: Juan Pérez (CI 7654321)  
> - Adquirente: Ana Pérez (CI 1234567), hija → parentesco = *Hijo*  
> - Inmueble: Catastro 10-20-30-40, valor = Bs 250.000  
> - Exención: ✅ Vivienda única  
> - Cálculo: 1% de 250.000 = Bs 2.500 → exención total → **Bs 0**  
> - Estado: Finalizado → PDF generado + enviado al SIN  
**Relación con el IDTGB**: Es el **corazón del sistema**. Aquí se integra todo.

---

### 11. **Exenciones**  
**Concepto**: Beneficios legales que reducen o eliminan el impuesto.  
**Propósito**: Aplicar normas fiscales que favorecen ciertos casos.  
**Ejemplo**:  
> - *Vivienda única y familiar*: exime el 100% si es la única propiedad del adquirente  
> - *Discapacidad*: descuento adicional del 50%  
**Relación con el IDTGB**: **Recalcula el monto final** al aplicarse.

---

### 12. **Documentos**  
**Concepto**: Archivos adjuntos al trámite (escrituras, certificados, etc.).  
**Propósito**: Sustentar la legalidad del trámite.  
**Ejemplo**:  
> - Escritura de donación  
> - Certificado de defunción (en herencias)  
> - Certificado municipal de vivienda única  
**Relación con el IDTGB**: No afectan el cálculo, pero son **requeridos para validar el trámite**.

---

### 13. **Pagos**  
**Concepto**: Registro de los pagos realizados por el impuesto.  
**Propósito**: Acreditar el cumplimiento de la obligación tributaria.  
**Ejemplo**:  
> Monto a pagar: Bs 2.245  
> Pago registrado: Bs 2.245 el 15/06/2024  
> Código de barras: `IDTGB20240001`  
> Comprobante: PDF descargable  
**Relación con el IDTGB**: El trámite **no se puede finalizar sin pago completo** (excepto si hay exención total).

---

### 14. **Formulario A-01 (PDF con QR)**  
**Concepto**: Documento oficial que acredita el cálculo y pago del IDTGB.  
**Propósito**: Ser presentado ante notarías, registros públicos o el SIN.  
**Ejemplo**:  
> PDF con logo institucional, datos del trámite, desglose del cálculo y **código QR**.  
> Al escanear el QR: muestra “VÁLIDO – Monto: Bs 0 – Fecha: 15/06/2024”  
**Relación con el IDTGB**: Es la **salida oficial del sistema**.

---

### 15. **Exportación al SIN**  
**Concepto**: Envío automático de datos al Sistema de Impuestos Nacionales.  
**Propósito**: Cumplir con obligaciones de reporte tributario.  
**Ejemplo**:  
> Al finalizar el trámite, se genera:  
> `IDTGB_BENI_20240615_143022.csv`  
> y se envía por FTP al servidor del SIN.  
**Relación con el IDTGB**: Garantiza **trazabilidad nacional**.

---

### 16. **Dashboard**  
**Concepto**: Panel de control con estadísticas y alertas.  
**Propósito**: Monitorear la operación del sistema.  
**Ejemplo**:  
> - “Este mes: 42 trámites, Bs 85.200 recaudados”  
> - “Alerta: 3 avalúos caducarán en 7 días”  
**Relación con el IDTGB**: No afecta cálculos, pero **mejora la gestión institucional**.

---

## 🔁 Flujo Visual de un Trámite

A continuación se muestra un diagrama de flujo que resume cómo interactúan todos estos módulos en un caso práctico.

```mermaid
graph TD
    subgraph "1. Preparación"
        A[▶️ Inicia Trámite] --> B{Registrar Datos Previos};
        B --> C[👤 Persona Adquirente];
        B --> D[🏠 Inmueble];
    end

    subgraph "2. Creación y Cálculo"
        C & D --> E[📝 Se crea el Trámite];
        E --> F{Se vinculan<br>Disponente, Adquirente, Inmueble};
        F --> G[⚖️ Sistema determina<br>la Base Imponible<br>(el mayor valor)];
        F --> H[📜 Sistema busca Tasa<br>según Parentesco];
        G & H --> I[💵 Se calcula<br>IDTGB Bruto];
    end

    subgraph "3. Exenciones y Pago"
        I --> J{¿Aplica Exención?}; 
        J -- Sí --> K[📉 Se resta el<br>monto exento];
        J -- No --> L[💰 IDTGB Neto];
        K --> L;
        L --> M{¿Impuesto > 0?};
        M -- Sí --> N[💳 Se registra el Pago];
        M -- No (Exención Total) --> O[✅ Trámite listo para finalizar];
        N --> O;
    end

    subgraph "4. Finalización y Salida"
        O --> P[🏁 Se finaliza el Trámite];
        P --> Q[📄 Se genera PDF A-01<br>con Código QR];
        P --> R[📤 Se envía reporte al SIN];
        Q & R --> S[⏹️ Fin];
    end
```
