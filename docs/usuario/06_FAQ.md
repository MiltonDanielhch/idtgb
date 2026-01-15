# 5. Preguntas Frecuentes (FAQ)

## 🧑‍💻 Para ciudadanos

### ¿Necesito registrarme para calcular el IDTGB?
**No.** Cualquier persona puede acceder al sistema, ingresar los datos del trámite y obtener el cálculo + formulario A-01 **sin crear una cuenta**.

### ¿El formulario A-01 que descargo es válido ante notarías?
**Sí.** El PDF generado es el **formulario oficial del Gobierno Autónomo Departamental del Beni**, con código QR de validación y cumplimiento de normativa vigente.

### ¿Cómo sé si mi formulario es auténtico?
Escaneé el **código QR** en la esquina inferior derecha del PDF con la cámara de su celular. Si los datos coinciden y aparece “**VÁLIDO**”, el documento es auténtico.

### ¿Puedo hacer el cálculo varias veces?
**Sí.** Puede repetir el proceso cuantas veces quiera, con distintos datos, sin costo ni registro.

### ¿Qué pasa si me equivoco en los datos?
Si aún no ha presentado el formulario, simplemente **repita el cálculo con los datos correctos** y descargue un nuevo PDF. Solo el último con QR válido será aceptado.

---

## 👨‍💼 Para administradores

### ¿Puedo editar un trámite ya finalizado?
**No.** Una vez marcado como **“Finalizado”**, el trámite queda **bloqueado para edición** (solo lectura). Si hay un error grave, debe:
1. Anular el trámite (cambiar estado a “Anulado”)
2. Crear uno nuevo con los datos corregidos

> ⚠️ La anulación queda registrada en la auditoría.

### ¿El sistema valida el CI/NIT con el SIBOIFE o RUAT?
**No en tiempo real.** El sistema verifica formato y evita duplicados, pero **no consulta bases nacionales**. Se recomienda validar documentos físicamente al registrar personas.

### ¿Qué pasa si no hay UFV para la fecha del trámite?
El sistema **no permitirá guardar** el trámite y mostrará un error:  
> “No existe valor UFV para la fecha seleccionada. Registre la UFV primero.”

### ¿Se pueden aplicar varias exenciones a un mismo adquirente?
**Sí**, pero solo si son compatibles. Por ejemplo:  
- ✅ *Vivienda única* + *Discapacidad* (si aplica)  
- ❌ Dos exenciones excluyentes (el sistema lo impedirá)

### ¿El PDF del formulario A-01 se regenera si edito el trámite antes de finalizar?
**Sí.** Mientras el trámite esté en estado **“Borrador”**, cualquier cambio (personas, inmuebles, exenciones) **actualiza el cálculo y el PDF futuro**. El PDF solo se fija al **finalizar**.

### ¿Qué pasa si falla la exportación al SIN?
- El trámite **sí se marca como Finalizado**.
- El ciudadano **sí recibe su PDF válido**.
- El sistema **guarda el error en logs** y permite **reintentar la exportación manualmente** desde el panel de administración.

---

## 🔐 Seguridad y cumplimiento

### ¿Los documentos subidos se pueden alterar?
**No.** Cada archivo se almacena con un **hash SHA-256**. Si alguien intenta modificarlo, el hash no coincidirá y se detectará la alteración.

### ¿Quién puede ver los trámites de un ciudadano?
Solo los **usuarios con rol administrador**. No hay acceso público a trámites finalizados, salvo mediante el **código QR de verificación** (que solo muestra datos básicos).

### ¿El sistema cumple con la normativa del IDTGB del Beni?
**Sí.** Está desarrollado conforme a:
- Ley Departamental del IDTGB
- Reglamento de aplicación
- Resoluciones sobre tasas, exenciones y formularios oficiales

---

¿Tienes una duda que no aparece aquí?  
Comunícate con el **Equipo de Soporte del Sistema IDTGB – Beni**.

---
