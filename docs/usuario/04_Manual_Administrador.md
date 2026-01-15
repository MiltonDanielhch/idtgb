# 3. Manual del Usuario Administrador (Backoffice)

El **panel de administración** permite a los funcionarios autorizados gestionar todos los componentes del sistema IDTGB.  
Todas las acciones quedan registradas con auditoría (`quién creó`, `quién modificó`, `cuándo`), y los cálculos se actualizan automáticamente al modificar datos.

---

## 3.1. Gestión de Catálogos

Los **catálogos** son listas maestras que definen las reglas del sistema. Solo los administradores pueden crear, editar o desactivar registros.

### 🌍 Departamentos, Provincias y Municipios
- **Propósito**: Definir la ubicación geográfica de los inmuebles.
- **Flujo**:  
  - Primero se crea un **Departamento** (solo “Beni” está activo por defecto).  
  - Luego se asignan **Provincias** (ej: Cercado, Vaca Díez, etc.).  
  - Finalmente, se vinculan **Municipios** a cada provincia.
- **Importante**: Solo los municipios del **departamento Beni** pueden usarse en trámites IDTGB.

### 👨‍👩‍👧 Parentescos y Tasas
- **Parentescos**: Define las relaciones familiares válidas (ej: *Cónyuge*, *Hijo*, *Padre*, *Hermano*, *Sin parentesco*).
- **Tasas**:  
  - Cada combinación **(Departamento + Parentesco + Tipo de Transmisión)** tiene una **tasa de impuesto** (%).  
  - Ejemplo:  
    - *Hijo* → 1%  
    - *Sin parentesco* → 5%  
  - Las tasas tienen **fecha de vigencia**: el sistema usa siempre la tasa activa en la fecha del trámite.
- **Regla clave**:  
  > La tasa **cambia según el grado de parentesco**. Cuanto más cercano sea el vínculo, menor es el impuesto.

### 📋 Tipos de Transmisión
Define el motivo de la transferencia gratuita:
- Donación
- Herencia
- Legado
- Otros

> ⚠️ Este dato afecta tanto la **tasa aplicable** como los **documentos requeridos**.

### 🏢 Tipos de Inmueble
Clasificación del bien:
- Vivienda
- Terreno
- Comercial
- Industrial
- Otros

### 💱 Valores UFV (Unidad de Fomento a la Vivienda)
- Se actualizan **diariamente** (importados desde fuentes oficiales).
- **Propósito**: Ajustar montos por inflación.  
  Ej: Si un avalúo fue emitido en enero y el trámite se hace en junio, el sistema **reexpresa el valor en UFV de la fecha del trámite**.
- El sistema **requiere UFV vigente** para cualquier cálculo. Si falta, muestra error.

> 🔁 **Recomendación**: Actualice los valores UFV al menos una vez por semana.

---

## 3.2. Registro de Personas

Permite registrar a **personas naturales o jurídicas** que participarán en trámites (como disponentes o adquirentes).

### 📝 Cómo registrar
1. Vaya a **Personas > Agregar**.
2. Seleccione **Tipo de persona**:  
   - **Natural**: CI, nombre, apellido, foto (opcional)  
   - **Jurídica**: NIT, razón social, representante legal
3. Ingrese **número de identificación** (CI o NIT).
4. Complete datos de contacto y dirección.
5. Suba una **foto o escaneo del documento** (opcional, pero recomendado para auditoría).
6. Haga clic en **Guardar**.

### ✅ Validación de CI/NIT
- El sistema **no valida en tiempo real con el SIBOIFE o RUAT**, pero:
  - Rechaza formatos inválidos (ej: letras en CI, dígitos en NIT).
  - Evita duplicados: no se pueden registrar dos personas con el mismo CI/NIT y tipo.

> 📎 **Nota**: La foto del documento se almacena con **hash SHA-256** para garantizar que no se altere.

---

## 3.3. Registro de Inmuebles

Cada inmueble debe registrarse antes de incluirse en un trámite.

### 📋 Datos requeridos
| Campo | Descripción |
|------|-------------|
| **Número de catastro** | Único e identificador oficial |
| **Tipo de inmueble** | Vivienda, terreno, etc. |
| **Valor catastral** | En bolivianos (Bs) |
| **Superficie** | En metros cuadrados (m²) |
| **Ubicación** | Departamento → Provincia → Municipio |
| **Vivienda única y familiar** | ✅ Marque si aplica (clave para exenciones) |
| **Estado** | Activo / Inactivo |

### ❓ ¿Qué es “vivienda única y familiar”?
Es una **exención legal** que reduce o elimina el IDTGB cuando:
- El inmueble es **vivienda habitual** del adquirente.
- Es la **única propiedad** del beneficiario en el país.
- Se acredita con documentación (certificado de vivienda única del municipio).

> ⚠️ Si marca esta opción, el sistema **recalcula el impuesto** aplicando la exención al crear el trámite.

---

## 3.4. Gestión de Avalúos

Los avalúos respaldan el valor del inmueble cuando **no se usa el valor catastral**.

### 📊 Tipos de avalúo
| Tipo | Descripción |
|------|-------------|
| **Fiscal** | Valor asignado por la Alcaldía |
| **Comercial** | Estimación de mercado |
| **Pericial** | Realizado por perito autorizado (más usado en herencias) |

### 📤 Cómo registrar un avalúo
1. Vaya a **Avalúos > Agregar**.
2. Seleccione el **inmueble** al que corresponde.
3. Elija el **tipo de avalúo**.
4. Ingrese el **valor** (en Bs) y la **fecha de emisión**.
5. Suba el **informe del perito** (PDF o imagen).
6. El sistema asigna automáticamente el estado:  
   - **Vigente**: si la fecha está dentro del plazo legal (ej: 1 año)  
   - **Caducado**: si supera el plazo

> 🔁 **Importante**: Solo los avalúos **vigentes** pueden usarse en trámites IDTGB.

---

## 3.5. Creación de Trámites IDTGB (Formulario A-01)

Un **trámite IDTGB** representa un proceso completo de transmisión gratuita de un inmueble en el Beni.  
Este módulo es el **corazón del sistema**: aquí se integran personas, inmuebles, exenciones, pagos y el cálculo final del impuesto.

> 🔁 **Importante**: Cada vez que modifique **cualquier dato** (personas, inmuebles, exenciones, fechas), el sistema **recalcula automáticamente** el monto del IDTGB.

---

### Paso 1: Crear un nuevo trámite

1. En el menú lateral, vaya a **Trámites > Agregar**.
2. Seleccione:
   - **Tipo de transmisión** (Donación, Herencia, etc.)
   - **Fecha del trámite** (debe tener UFV registrada para esa fecha)
3. Haga clic en **Guardar**.  
   → Se genera un **número único de trámite** (ej: `IDTGB-2024-0001`).

> 📌 El trámite comienza en estado **“Borrador”**. Solo al marcarlo como **“Finalizado”** se emite el PDF y se envía al SIN.

---

### Paso 2: Agregar disponentes (quien transmite el bien)

Los **disponentes** son las personas que **ceden o donan** el inmueble (también llamados causantes o donantes).

1. En la ficha del trámite, vaya a la pestaña **“Disponentes”**.
2. Haga clic en **“+ Agregar disponente”**.
3. Seleccione una **persona registrada** (o créela primero en el módulo *Personas*).
4. Confirme los datos y guarde.

> ⚠️ Un trámite debe tener **al menos un disponente**.

---

### Paso 3: Agregar adquirentes (quien recibe el bien)

Los **adquirentes** son quienes **reciben** el inmueble. Aquí se definen:

- Su participación porcentual (%)
- Si aplican **exenciones** (ej: vivienda única)

1. Vaya a la pestaña **“Adquirentes”**.
2. Haga clic en **“+ Agregar adquirente”**.
3. Seleccione una **persona registrada**.
4. Ingrese el **porcentaje de participación** (debe sumar 100% entre todos los adquirentes).
5. Marque las **exenciones aplicables** (ej: *Vivienda única y familiar*).
6. Guarde.

> 🔄 **Al guardar**, el sistema **recalcula inmediatamente** el impuesto considerando:
> - Parentesco entre disponente y adquirente
> - Exenciones seleccionadas
> - Valor del inmueble

---

### Paso 4: Vincular inmuebles

1. Vaya a la pestaña **“Inmuebles”**.
2. Haga clic en **“+ Agregar inmueble”**.
3. Seleccione un **inmueble previamente registrado**.
4. Confirme y guarde.

> 📌 Solo se pueden vincular inmuebles del **departamento Beni**.  
> 🔄 Al agregar o quitar un inmueble, el sistema **actualiza el valor base** del impuesto.

---

### Paso 5: Subir documentos

Los documentos respaldan la legalidad del trámite (escrituras, certificados, etc.).

1. Vaya a la pestaña **“Documentos”**.
2. Haga clic en **“+ Subir documento”**.
3. Seleccione el archivo (PDF, JPG, PNG – máximo 10 MB).
4. Asigne un **tipo de documento** (ej: *Escritura pública*, *Certificado de defunción*, *Certificado de vivienda única*).
5. Guarde.

> 🔒 Cada archivo se almacena con un **hash SHA-256** para garantizar su integridad.  
> 📎 Puede subir múltiples versiones (el sistema guarda historial).

---

### Paso 6: Aplicar exenciones

Las **exenciones** reducen o eliminan el impuesto si se cumplen ciertas condiciones legales.

1. Vaya a la pestaña **“Exenciones”**.
2. Haga clic en **“+ Aplicar exención”**.
3. Seleccione una exención del catálogo (ej: *Vivienda única y familiar*).
4. El sistema **verifica automáticamente** si el inmueble y el adquirente cumplen los requisitos.
5. Si es válido, **recalcula el IDTGB** con la exención aplicada.

> ⚠️ Si la exención no aplica (ej: el adquirente tiene otra propiedad), el sistema lo notificará y no la aplicará.

---

### Paso 7: Ver el cálculo automático del impuesto

En la parte superior de la ficha del trámite, siempre se muestra un **resumen del cálculo**:

| Concepto | Valor |
|--------|-------|
| Valor base del inmueble | Bs 250.000 |
| Tasa aplicable (por parentesco) | 1% |
| Monto base | Bs 2.500 |
| Descuento del 15% (si aplica) | -Bs 375 |
| Recargo por mora (si aplica) | +Bs 120 |
| **Monto final a pagar** | **Bs 2.245** |

> ✅ Este cálculo se **actualiza en tiempo real** al modificar cualquier dato del trámite.

---

### Paso 8: Registrar pagos

Una vez conocido el monto, se puede registrar el pago:

1. Vaya a la pestaña **“Pagos”**.
2. Haga clic en **“+ Registrar pago”**.
3. Ingrese:
   - Fecha de pago
   - Monto abonado
   - Número de comprobante (opcional)
4. El sistema genera un **código de barras único** para el pago.
5. Puede **descargar el comprobante de pago en PDF** (con logo institucional y QR).

> 💡 El trámite puede tener **múltiples pagos parciales**, pero debe cubrir el 100% del monto antes de finalizar.

---

### Paso 9: Marcar como Finalizado

Cuando el trámite esté completo y el pago verificado:

1. Vaya a la ficha principal del trámite.
2. Cambie el **estado** a **“Finalizado”**.
3. Haga clic en **Guardar**.

✅ Al hacer esto, el sistema:

- **Genera automáticamente el Formulario A-01 en PDF** (con código QR de validación)
- **Envía un archivo CSV al Sistema de Impuestos Nacionales (SIN)** vía FTP
- **Bloquea el trámite** para edición (solo lectura)

> 📤 La exportación al SIN incluye: número de trámite, personas, inmueble, monto, fecha y estado.

---

### 🔁 Recordatorio clave

> **Todo en el trámite es dinámico**:  
> Si edita un dato (ej: cambia el parentesco, agrega una exención, corrige el valor del inmueble),  
> **el sistema recalcula el impuesto, actualiza el monto a pagar y ajusta el PDF final**.  
> ¡Nunca debe calcular manualmente!

---

## 3.6. Generación del Formulario A-01 con QR

El **Formulario A-01** es el documento oficial que acredita el cálculo y pago del IDTGB. Se genera **automáticamente** al marcar un trámite como **“Finalizado”**.

### 📄 Contenido del PDF

El formulario incluye:

- **Encabezado institucional**: Logo del Gobierno Autónomo Departamental del Beni
- **Número único de trámite**: Ej. `IDTGB-2024-0001`
- **Fecha de emisión**
- **Datos del disponente(s)**: Nombre, CI/NIT, rol (donante/causante)
- **Datos del adquirente(s)**: Nombre, CI/NIT, % de participación, exenciones aplicadas
- **Datos del inmueble**: Catastro, ubicación, valor base
- **Desglose del cálculo**:
  - Tasa aplicable (según parentesco)
  - Monto base
  - Descuento del 15% (si aplica)
  - Recargos por mora (si aplica)
  - **Monto final a pagar**
- **Estado del trámite**: Finalizado
- **Código QR de validación** (esquina inferior derecha)

### 🔐 Seguridad y autenticidad

- Cada PDF tiene un **código QR único** que codifica:
  - Número de trámite
  - Fecha de emisión
  - Monto final
  - Hash criptográfico del contenido
- Al escanear el QR, se accede a una **página de verificación pública** que confirma:
  - ✅ El trámite existe
  - ✅ Los datos coinciden
  - ✅ El documento no ha sido alterado
- El PDF **no es editable**: se genera en modo de solo lectura.

> 📌 Este formulario es **válido ante notarías, registros de propiedad y el SIN**.

---

## 3.7. Exportación al SIN

Cuando un trámite se marca como **“Finalizado”**, el sistema envía automáticamente un reporte al **Sistema de Impuestos Nacionales (SIN)**.

### 📤 ¿Qué se envía?

- Un archivo **CSV** con los siguientes campos:
  ```csv
  numero_tramite,fecha_tramite,ci_disponente,nombre_disponente,ci_adquirente,nombre_adquirente,catastro_inmueble,valor_base,tasa,monto_idtgb,estado
  IDTGB-2024-0001,2024-06-15,1234567,María López,7654321,Carlos Méndez,10-20-30-40,250000,0.01,2245,FINALIZADO
  ```

### 🔌 Conexión FTP

- El sistema se conecta a un **servidor FTP seguro** del SIN (configurado en `.env`).
- Usa credenciales preestablecidas por el ente recaudador.
- El archivo se nombra con el formato: `IDTGB_BENI_YYYYMMDD_HHMMSS.csv`

### 📝 Logs y auditoría

- Cada intento de exportación queda registrado en los **logs del sistema**:
  - Fecha y hora
  - Nombre del archivo
  - Estado: **Éxito** o **Error**
  - Mensaje de error (si falla)
- En caso de fallo (ej: SIN fuera de línea), el sistema:
  - Mantiene el trámite como **Finalizado**
  - Permite **reintentar la exportación manualmente** desde el panel

> ⚠️ La exportación **no afecta la emisión del PDF**: el ciudadano recibe su formulario incluso si el SIN está temporalmente inaccesible.

---