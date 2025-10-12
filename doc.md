<!-- 
meta-description: Guía completa del Sistema IDTGB. Aprende a calcular, declarar y pagar el Impuesto a la Transmisión Gratuita de Bienes. Tutorial para usuarios y API.
-->

# Guía de Usuario de Sistema de Gestión IDTGB

**Desde cero hasta tu primera acción en menos de 10 minutos.**

Bienvenido al Sistema de Gestión del Impuesto Departamental a la Transmisión Gratuita de Bienes (IDTGB). Esta guía te ayudará a usar la plataforma, ya seas un ciudadano, un funcionario o un desarrollador que busca integrarse con nuestros servicios.

---

### Contenido
- [Requisitos Previos](#requisitos-previos)
- [Tutorial Nivel 1: Usuario Final](#nivel-1--primeros-pasos-para-el-usuario-final)
- [Tutorial Nivel 2: Integrador API](#nivel-2--guía-rápida-para-integradores-api)
- [Tutorial Nivel 3: Administrador](#nivel-3--gestión-para-administradores)
- [Preguntas Frecuentes (FAQ)](#preguntas-frecuentes-faq)
- [Solución de Problemas](#solución-de-problemas-troubleshooting)
- [Soporte y Próximos Pasos](#soporte-y-próximos-pasos)

---

## Requisitos Previos

Antes de empezar, asegúrate de tener todo lo siguiente:

- [x] Una cuenta de usuario activa. Si no la tienes, solicítala al administrador del sistema.
- [x] Un navegador web moderno, como Chrome, Firefox o Edge.
- [x] Los documentos relacionados con tu trámite en formato digital (PDF, JPG, PNG).
- [x] (Solo para integradores) Permisos de administrador para generar un token de API.

---

## Tutorial Paso a Paso

### Nivel 1 – Primeros Pasos para el Usuario Final

Este tutorial te guía para registrar tu primer trámite de impuesto.

**Paso 1: Inicia sesión**  
Accede a la plataforma web con tu usuario y contraseña. Verás el panel principal.

![Paso 1 – Panel principal](https://via.placeholder.com/800x400.png/F2F2F2/333333?text=Panel+Principal+del+Sistema)

**Paso 2: Crea un nuevo trámite**  
Haz clic en la sección “Trámites” en el menú lateral. Luego, pulsa el botón “Añadir Nuevo”.

**Paso 3: Completa los formularios**  
Rellena cuidadosamente la información solicitada:
- Datos del disponente (quien transfiere el bien).
- Datos del adquirente (quien recibe el bien).
- Información del inmueble (catastro, dirección, etc.).

![Paso 3 – Formulario de datos del trámite](https://via.placeholder.com/800x400.png/F2F2F2/333333?text=Formulario+de+Nuevo+Trámite)

**Paso 4: Sube los documentos**  
Adjunta los archivos digitalizados que respaldan la transmisión del bien. Asegúrate de que sean legibles.

**Paso 5: Revisa y finaliza**  
El sistema calculará automáticamente la base imponible y el impuesto a pagar. Revisa que todos los datos sean correctos y haz clic en “Guardar” para finalizar el registro. Tu trámite pasará a estado “Iniciado”.

### Nivel 2 – Guía Rápida para Integradores (API)

Obtén un token y realiza tu primera consulta a la API REST.

**Paso 1: Ve a tu perfil**  
Inicia sesión con una cuenta de administrador y haz clic en tu nombre de usuario en la esquina superior derecha. Selecciona "Perfil".

**Paso 2: Accede a los Tokens de API**  
Dentro de tu perfil, busca la sección o pestaña de "API Tokens".
*(Nota: Esta sección solo es visible para roles con permisos de administrador).*

**Paso 3: Genera un nuevo token**  
Haz clic en “Generar Nuevo Token”. Dale un nombre descriptivo para recordar su propósito (ej. “Integración SIN”).

![Paso 3 – Generación de Token API](https://via.placeholder.com/800x400.png/F2F2F2/333333?text=Generar+Nuevo+Token+API)

**Paso 4: Copia tu token**  
El sistema mostrará el token por única vez. Cópialo y guárdalo en un lugar seguro. No podrás volver a verlo.

**Paso 5: Abre tu terminal**  
Prepara tu entorno para hacer una llamada cURL.

**Paso 6: Realiza una llamada de prueba**  
Usa el siguiente comando para consultar el estado de un trámite. Reemplaza `TU_TOKEN` y `ID_DEL_TRAMITE`.

```bash
curl -X GET "https://api.proygobe.bo/v1/tramites/ID_DEL_TRAMITE" \
-H "Authorization: Bearer TU_TOKEN" \
-H "Content-Type: application/json"
```

**Paso 7: Interpreta la respuesta**  
Si la llamada es exitosa, recibirás un objeto JSON con los detalles del trámite.

```json
{
  "id": 123,
  "nro_tramite": "2025-123",
  "estado": "Finalizado",
  "fecha_presentacion": "2025-10-11",
  "total_idtgb": 4500.50
  // ... otros campos
}
```

### Nivel 3 – Gestión para Administradores

Aprende a gestionar usuarios, roles y logs del sistema.

1.  **Invitar un usuario:** Ve a `Herramientas > Usuarios` y haz clic en “Añadir Nuevo”.
2.  **Asignar rol:** Completa el formulario con los datos del nuevo usuario y selecciona un rol (ej. “Funcionario”, “Revisor”).
3.  **Guardar:** Haz clic en “Guardar”. El usuario recibirá una notificación para establecer su contraseña.
4.  **Configurar roles:** En `Herramientas > Roles`, puedes crear nuevos roles o editar los permisos de los existentes para controlar el acceso a cada módulo.
5.  **Revisar logs:** Para auditorías o depuración, los logs de la aplicación se encuentran en el servidor, en la ruta `storage/logs/`. El archivo `laravel.log` contiene un registro detallado de todas las operaciones y errores.

---

## Preguntas Frecuentes (FAQ)

### Para Usuarios Finales

❓ **¿Cómo recupero mi contraseña?**  
En la pantalla de inicio de sesión, haz clic en el enlace “¿Olvidaste tu contraseña?”. Ingresa tu correo electrónico y recibirás instrucciones para restablecerla.

💰 **¿Qué métodos de pago se aceptan para el impuesto?**  
Actualmente, el pago se realiza mediante depósito bancario en las cuentas autorizadas por la Gobernación. Debes adjuntar el comprobante de pago en la sección "Pagos" de tu trámite.

📱 **¿Puedo usar el sistema desde mi celular?**  
Sí. La interfaz web es responsiva y se adapta a navegadores de dispositivos móviles, aunque recomendamos usar una computadora para subir documentos con mayor comodidad.

📄 **¿Qué hago si un documento que subí es rechazado?**  
Recibirás una notificación con el motivo del rechazo. Deberás acceder a tu trámite, eliminar el archivo incorrecto y subir una nueva versión corregida.

⏳ **¿Cuánto tiempo tarda en aprobarse un trámite?**  
El tiempo promedio es de 3 a 5 días hábiles, dependiendo de la complejidad del caso y la correcta presentación de los documentos.

### Para Integradores

🔧 **¿Cuál es el *rate limit* de la API?**  
El límite de peticiones es de 60 solicitudes por minuto por token de API. Si lo excedes, recibirás un código de estado `429 Too Many Requests`.

❌ **Recibo un error `401 Unauthorized`. ¿Qué significa?**  
Este error indica un problema con tu token de API. Verifica que:
- Estés incluyendo el token en la cabecera `Authorization` con el prefijo `Bearer`.
- El token no haya expirado o sido revocado.
- El token sea válido para el entorno (desarrollo/producción) que estás atacando.

🤔 **¿El sistema soporta webhooks?**  
Sí, ofrecemos webhooks para notificar en tiempo real los cambios de estado de un trámite (ej. de `Iniciado` a `Observado` o `Finalizado`). Puedes configurarlos en la sección API de tu perfil de administrador.

🔐 **¿Qué esquema de autenticación usa la API?**  
Nuestra API REST utiliza `Bearer Token Authentication`. Debes pasar tu token de API en la cabecera `Authorization` de cada solicitud.

📋 **¿Dónde encuentro la lista completa de códigos de error de la API?**  
La documentación completa de la API, incluyendo todos los códigos de estado y error, está disponible en nuestro portal para desarrolladores: [dev.proygobe.bo/docs](https://dev.proygobe.bo/docs).

---

## Solución de Problemas (Troubleshooting)

| Síntoma | Causa Probable | Solución |
| :--- | :--- | :--- |
| La página tarda mucho en cargar. | 1. Conexión a internet lenta.<br>2. Caché del navegador desactualizada. | - Verifica tu velocidad de internet.<br>- Limpia la caché y cookies de tu navegador.<br>- Intenta acceder desde otro dispositivo. |
| No puedo iniciar sesión. | 1. Credenciales incorrectas.<br>2. Bloqueo por intentos fallidos. | - Usa la función “¿Olvidaste tu contraseña?”.<br>- Asegúrate de que la tecla `Bloq Mayús` no esté activada.<br>- Contacta al [soporte técnico](#soporte-y-próximos-pasos). |
| Error al subir un archivo. | 1. El formato no es soportado (solo PDF, JPG, PNG).<br>2. El archivo excede el límite de 10 MB. | - Convierte el archivo al formato correcto.<br>- Comprime el archivo para reducir su tamaño.<br>- Revisa que el nombre del archivo no contenga caracteres especiales. |
| (API) Recibo un error `500 Internal Server Error`. | 1. Error inesperado en el servidor.<br>2. Envío de datos malformados en la petición. | - Revisa que el cuerpo de tu petición JSON sea válido.<br>- Espera unos minutos y reintenta la llamada.<br>- Si el error persiste, contacta a soporte con el `request-id` de la cabecera de respuesta. |

---

## Soporte y Próximos Pasos

¿Necesitas más ayuda?

- **Crea un Ticket de Soporte:** [soporte.proygobe.bo](https://soporte.proygobe.bo)
- **Chatea con nosotros:** Disponible en el portal de soporte.
- **Únete a la Comunidad (Discord):** [discord.gg/proygobe](https://discord.gg/proygobe)
- **Consulta nuestro Roadmap Público:** [proygobe.bo/roadmap](https://proygobe.bo/roadmap)

---
*Última actualización: 2025-10-11 | v1.0*


¡Excelente idea! Preparar una explicación conceptual para el cliente es clave para que entienda el valor de lo que hemos construido.

Aquí tienes un desglose completo de los módulos del sistema, explicado de forma sencilla, con ejemplos y aclarando los términos más técnicos.

Explicación Conceptual del Sistema de Gestión IDTGB
Este documento desglosa los componentes (módulos) que forman el sistema, explicando qué hace cada uno, por qué es importante y cómo funciona con ejemplos del día a día.

1. Módulo de Gestión de Trámites
¿Qué es y para qué sirve? Es el corazón del sistema. Se encarga de registrar, procesar y seguir cada caso de impuesto desde que inicia hasta que se completa. Digitaliza todo el papeleo y el flujo de trabajo que antes se hacía manualmente.

Conceptos Clave:

Trámite: Es el expediente digital de un caso de impuesto. Contiene toda la información, documentos y el historial de un proceso.
Disponente: La persona o entidad que transfiere el bien (ej. el padre que deja una herencia).
Adquirente: La persona o entidad que recibe el bien (ej. el hijo que hereda).
Inmueble: El bien raíz (casa, terreno) que se está transfiriendo.
Estado del Trámite: La fase en la que se encuentra el expediente (Iniciado, Observado, Aprobado, Finalizado, Pagado). Permite saber de un vistazo qué está pasando.
Ejemplo Práctico: Doña Carmen quiere donar un terreno a su sobrina, Lucía.

Un funcionario inicia un Trámite en el sistema.
Registra a Doña Carmen como Disponente y a Lucía como Adquirente.
Añade los datos del Inmueble (el terreno).
El Estado del Trámite inicial es "Iniciado". El sistema ya está trabajando.
2. Módulo de Cálculo de Impuestos (El "Motor Inteligente")
¿Qué es y para qué sirve? Es el cerebro matemático del sistema. Realiza automáticamente todos los cálculos complejos para determinar el monto exacto del impuesto, eliminando errores humanos y asegurando que se aplique la normativa vigente.

Conceptos Clave:

Base Imponible: El valor sobre el cual se calcula el impuesto. Generalmente, es el valor más alto entre el avalúo catastral y el valor de la transferencia.
Tasa: El porcentaje que se aplica a la Base Imponible para calcular el impuesto. Varía según el parentesco.
UFV (Unidad de Fomento a la Vivienda): Un valor que cambia diariamente y que se usa para actualizar las deudas y mantener su valor en el tiempo. El sistema lo consulta automáticamente.
Recargo por Mora: Una penalización que el sistema calcula si el impuesto se paga fuera de plazo.
Exención: Una condición legal que libera del pago del impuesto (ej. donaciones a fundaciones benéficas). El sistema sabe cuándo aplicarla.
Ejemplo Práctico: Continuando con el caso de Lucía, el sistema:

Toma el valor del terreno (la Base Imponible).
Identifica que la relación es tía-sobrina y aplica la Tasa correspondiente (ej. 10%).
Consulta la UFV del día para hacer ajustes si son necesarios.
Si Lucía tarda más de 90 días en pagar, el sistema automáticamente calculará el Recargo por Mora.
3. Módulo de Administración y Seguridad (El "Panel de Control")
¿Qué es y para qué sirve? Es el área restringida para los administradores del sistema. Desde aquí se gestiona quién puede entrar, qué puede ver y qué puede hacer. Es la garantía de que la información está segura y organizada.

Conceptos Clave (y un concepto "raro"):

Usuarios: Las personas que tienen acceso al sistema (funcionarios, directores, etc.).
Roles: "Etiquetas" que agrupan permisos (ej. Rol "Cajero", Rol "Auditor"). Un usuario tiene uno o más roles.
Permisos: Acciones específicas que un rol puede realizar (ej. "crear trámite", "ver reportes").
Concepto "Raro": BREAD (Browse, Read, Edit, Add, Delete). Es el acrónimo en inglés de las 5 acciones básicas que se pueden hacer con cualquier dato: Navegar (ver listas), Leer (ver un detalle), Editar (modificar), Añadir (crear nuevo) y Borrar. Nuestro panel de control (Voyager) usa esta lógica para asignar permisos de forma muy granular.
Ejemplo Práctico: El Director de Recaudaciones necesita que un nuevo pasante solo pueda ver los trámites, pero sin modificarlos.

El administrador crea un Usuario para el pasante.
Le asigna el Rol de "Consultor".
A ese rol, le concede solo los Permisos de Browse (Navegar) y Read (Leer) para los trámites, pero le niega Edit, Add y Delete.
4. Módulo de Catálogos (La "Biblioteca" del Sistema)
¿Qué es y para qué sirve? Son las listas de datos predefinidos que el sistema usa para funcionar de manera ordenada y consistente. Evitan que los usuarios escriban la misma información de formas distintas, lo que es vital para la calidad de los datos y los reportes.

Conceptos Clave:

Son las tablas "maestras": Tipos de Inmueble, Tipos de Transmisión, Parentescos, Municipios, Tasas, etc.
Ejemplo Práctico: Al registrar un trámite, en lugar de que el funcionario escriba a mano "Herencia", "Anticipo de legítima" o "Donación", simplemente lo selecciona de una lista desplegable en el campo "Tipo de Transmisión". Esto garantiza que los datos siempre sean uniformes.

5. Módulo de Reportes y Dashboard (El "Tablero de Inteligencia")
¿Qué es y para qué sirve? Es la parte visual y analítica. Presenta toda la información acumulada en el sistema a través de gráficos y cifras fáciles de entender. Permite a los directivos tomar decisiones informadas basadas en datos reales.

Conceptos Clave:

Dashboard (Panel): La pantalla principal que muestra los indicadores más importantes en tiempo real (ej. total recaudado hoy, número de trámites iniciados este mes).
Métricas / KPIs: Los indicadores clave de rendimiento (ej. tiempo promedio de un trámite, recaudación por municipio).
Exportación: La capacidad de descargar los datos en formatos como PDF (para imprimir y presentar) o Excel (para un análisis más profundo).
Ejemplo Práctico: El Gobernador quiere un resumen del primer trimestre.

Accede al Dashboard y ve de un vistazo la recaudación total.
Luego, va al módulo de reportes y genera un PDF con el "Top 5 de municipios con mayor recaudación".
Finalmente, exporta a Excel todos los trámites de ese periodo para que su equipo de finanzas lo analice.
6. Módulo de Tareas en Segundo Plano (El "Asistente Silencioso")
¿Qué es y para qué sirve? Este módulo ejecuta tareas pesadas o lentas "detrás de cámaras", sin que el usuario tenga que esperar. Es fundamental para mantener el sistema rápido y eficiente, especialmente cuando se comunica con otros sistemas.

Conceptos "Raros" y su explicación:

Proceso Asíncrono (o "en segundo plano"): Imagina que pides una pizza por teléfono. No te quedas esperando en la línea hasta que la pizza esté horneada y entregada. Cuelgas y sigues con tu vida; la pizzería hace el trabajo y te avisa cuando está lista. Un proceso asíncrono es igual: el usuario le da una orden al sistema (ej. "envía esto al SIN") y el sistema la anota para hacerla por su cuenta, dejando al usuario libre para seguir trabajando.
Job (Tarea): La orden específica que se debe ejecutar (ej. "generar el CSV del trámite 123").
Queue (Cola): La "fila de espera" donde el sistema pone las tareas pendientes para irlas procesando una por una.
Ejemplo Práctico: Cuando un funcionario marca un trámite como "Finalizado", el sistema necesita enviar esa información al SIN (Servicio de Impuestos Nacionales).

En lugar de congelar la pantalla del usuario, el sistema crea un Job (ExportarAlSINJob).
Pone ese Job en la Queue.
El usuario ve un mensaje de "Éxito" al instante y puede seguir trabajando. Mientras tanto, un proceso invisible toma el Job de la cola y se encarga de la comunicación con el SIN.
7. Módulo API (El "Traductor Universal")
¿Qué es y para qué sirve? La API es una puerta de comunicación segura para que otros sistemas informáticos puedan hablar con el nuestro, solicitarle datos o enviarle instrucciones de forma estandarizada, sin necesidad de intervención humana.

Conceptos "Raros" y su explicación:

API (Interfaz de Programación de Aplicaciones): Pensemos en un mesero en un restaurante. Tú (un sistema cliente) no entras a la cocina (la base de datos) a buscar los ingredientes. Le das una orden clara al mesero (la API), como "tráeme los datos del trámite 45". El mesero va a la cocina, obtiene lo que pediste y te lo trae en un plato (un formato estándar como JSON). La API es ese intermediario que atiende peticiones de forma ordenada y segura.
Endpoint: La "dirección" específica a la que otro sistema debe llamar para pedir algo (ej. /api/tramites/45).
JSON: El "idioma" en que los sistemas hablan entre sí. Es un formato de texto ligero y fácil de entender para las máquinas.
Token de Autenticación: Una "llave digital" que un sistema debe presentar para demostrar que tiene permiso para hacer una petición a nuestra API.
Ejemplo Práctico: Un sistema externo del Catastro necesita verificar el estado de un inmueble en nuestro sistema.

El sistema de Catastro hace una llamada al Endpoint /api/inmuebles/ABC-123.
En la llamada, presenta su Token de Autenticación.
Nuestra API verifica el token, busca la información y se la devuelve en formato JSON.


¡Claro! Basado en nuestro historial y el estado actual del sistema, aquí tienes un conjunto de ideas estructuradas que puedes plantearme. Las he organizado por áreas de trabajo para que puedas elegir qué te interesa más.

A. Nuevas Funcionalidades y Mejoras de lo Existente
Dashboard Interactivo con Filtros

Idea: Ahora que el dashboard muestra métricas, el siguiente paso es hacerlo dinámico. Los usuarios (especialmente los administradores) querrán filtrar los datos para entender mejor la información.
Puedes pedirme que: "Implemente filtros en el dashboard para que se puedan visualizar las métricas por rango de fechas (ej: últimos 30 días, mes actual, año actual) y por tipo de trámite."
Módulo de Reportes (Profundización)

Idea: Ya sentamos las bases, ahora podemos crear los reportes específicos. El de "rendimiento de funcionarios" es un excelente candidato.
Puedes pedirme que: "Cree la lógica y la vista para el 'Reporte de Rendimiento de Funcionarios'. Debe mostrar una tabla con cada funcionario, el número de trámites gestionados en un período y el tiempo promedio de finalización. Añade un botón para exportar los resultados a Excel."
Sistema de Notificaciones dentro de la App

Idea: Cuando un trámite cambia de estado (ej: de "En Proceso" a "Finalizado") o cuando la exportación al SIN falla, el usuario debería ser notificado directamente en la interfaz.
Puedes pedirme que: "Añada un sistema de notificaciones en la barra de navegación. Cuando un trámite importante para el usuario cambie de estado, debe aparecer un ícono con un contador. Al hacer clic, se desplegará una lista con las últimas actualizaciones."
B. Calidad, Mantenimiento y Deuda Técnica
Pruebas Unitarias y de Integración (Testing)

Idea: Nuestro sistema tiene lógica crítica (cálculo de impuestos en IdtgbCalculator, observadores, jobs). Para asegurar que futuros cambios no rompan nada, es fundamental tener una red de seguridad de pruebas automáticas.
Puedes pedirme que: "Escriba pruebas unitarias (Unit Tests) para el servicio IdtgbCalculator para cubrir diferentes escenarios de cálculo. Luego, cree una prueba de integración (Feature Test) que simule la creación de un trámite y verifique que el ExportarAlSINJob se encola correctamente al finalizarlo."
Finalizar la Integración Real con el SIN

Idea: El ExportarAlSINJob actualmente simula el envío. Podemos completar la funcionalidad real. Necesitaríamos las credenciales y la estructura final del archivo.
Puedes pedirme que: "Modifique el ExportarAlSINJob para que, en lugar de guardar un archivo local, se conecte a un servidor FTP (usando las credenciales que te proporcionaré en el .env) y suba el archivo. Implementa también un manejo de errores que registre si la conexión o la subida fallan."
Auditoría de Seguridad y Optimización

Idea: Revisar que el sistema sea seguro y eficiente. Esto incluye verificar que los permisos y políticas (Policies) se apliquen correctamente en todas las rutas y optimizar consultas lentas a la base de datos.
Puedes pedirme que: "Realice una auditoría de las Policies de la aplicación para asegurar que un usuario no pueda ver o modificar trámites que no le pertenecen. Adicionalmente, use Laravel Debugbar o Telescope para identificar y optimizar consultas N+1 en el listado de trámites."
C. Documentación y Experiencia de Usuario (UX)
Generar Documentación de la API REST

Idea: Si en el futuro otros sistemas necesitaran interactuar con nuestra aplicación (por ejemplo, para consultar el estado de un trámite), necesitaríamos una API. Podemos empezar a documentarla.
Puedes pedirme que: "Cree un endpoint de API básico en routes/api.php que permita consultar el estado de un trámite por su hash_validacion. Luego, use una herramienta como Scribe para generar automáticamente una página de documentación para esta API."
Explicación de Conceptos para el Cliente

Idea: Ya me pediste esto, y es una excelente idea para mantener alineado al cliente. Podemos hacerlo de forma recurrente para cada nuevo módulo.
Puedes pedirme que: "Genere un documento en Markdown explicando en términos sencillos para un no-técnico qué es el 'Módulo de Reportes', qué beneficios trae, y cómo un 'Reporte de Rendimiento de Funcionarios' ayuda a la gestión. Incluye ejemplos visuales (placeholders de gráficos)."
Mejorar la Experiencia del Formulario de Trámites

Idea: El formulario para crear/editar un trámite es probablemente la parte más compleja de la UI. Podemos hacerlo más intuitivo.
Puedes pedirme que: "Analice el formulario de creación de trámites y proponga mejoras de UX. Por ejemplo, dividirlo en pasos (un 'wizard'), añadir textos de ayuda (tooltips) en campos complejos como 'Tipo de Transmisión', o cargar dinámicamente las provincias y municipios al seleccionar un departamento."
Espero que esto te dé un buen punto de partida. ¿Cuál de estas ideas te interesa más para que empecemos a trabajar?






De acuerdo. Actuando como ingeniero de software senior, he preparado el siguiente resumen técnico exhaustivo del sistema. Este documento servirá como un excelente punto de partida para garantizar la continuidad del desarrollo.

Resumen Técnico del Sistema de Gestión Tributaria (proygobe/impuestos)
Versión: 1.0 (en desarrollo) Fecha: 12 de octubre de 2025

Este documento detalla la arquitectura, componentes y estado actual del sistema de gestión de impuestos sobre la transferencia de bienes inmuebles.

1. Arquitectura y Stack Tecnológico
Arquitectura: El sistema sigue una arquitectura monolítica basada en el patrón Modelo-Vista-Controlador (MVC), estándar en el framework Laravel. La lógica de negocio principal está siendo progresivamente encapsulada en Clases de Servicio para desacoplarla de los controladores.
Stack Tecnológico:
Backend: PHP 8.x / Laravel 10.x.
Frontend: Plantillas Blade con assets compilados mediante Vite.js (JavaScript ES6, CSS).
Base de Datos: Sistema de gestión de bases de datos relacional (probablemente MySQL/MariaDB), gestionado a través del ORM Eloquent de Laravel.
Panel de Administración: TCG Voyager se utiliza para la administración rápida de entidades maestras (CRUDs), usuarios y roles.
Gestión de Tareas en Segundo Plano: Laravel Queues con el driver database, lo que implica que los trabajos pendientes se almacenan en la tabla jobs.
2. Módulos Implementados
Gestión de Trámites (Módulo Core):

Permite el registro completo de un trámite de transferencia de inmueble.
Asocia múltiples disponentes, adquirentes e inmuebles a un único trámite.
Maneja diferentes estados del trámite (Iniciado, En Proceso, Observado, Finalizado, Anulado).
Cálculo de Impuestos:

La lógica de negocio para el cálculo del impuesto está centralizada en el servicio app/Services/IdtgbCalculator.php. Esto es una decisión de diseño clave para mantener los controladores y modelos limpios.
Administración (Voyager):

CRUDs para entidades "maestras" o catálogos: Departamentos, Provincias, Municipios, Tipos de Inmueble, Tasas, UFV, etc.
Gestión de usuarios, roles y permisos a través de la interfaz de Voyager.
Exportación Asíncrona al SIN:

Un proceso en segundo plano que se activa al finalizar un trámite.
Utiliza un TramiteObserver para detectar el cambio de estado a "Finalizado".
Encola un ExportarAlSINJob que (actualmente) genera un CSV de ejemplo y lo guarda localmente. Está preparado para enviar el archivo por FTP.
Dashboard de Métricas (UI):

Se ha modificado la vista principal de Voyager (resources/views/vendor/voyager/index.blade.php) para incluir una sección de dashboard con métricas clave. Actualmente, los datos son estáticos (placeholders).
3. APIs y Puntos de Integración
API Interna/Web: La interacción principal se realiza a través de la interfaz web servida por Laravel.
Integraciones Salientes:
SIN (Sistema de Impuestos Nacionales): Se realiza una exportación de datos vía Job asíncrono. El método de transporte final será FTP, aunque actualmente es una simulación.
API Externa (Entrante): No existen actualmente endpoints REST públicos para consumo de terceros.
4. Estructura de la Base de Datos
El esquema es relacional, definido mediante migraciones de Laravel.

Tablas Principales: tramites, people, users, inmuebles, pagos, ufvs, jobs.
Relaciones Clave:
tramites tiene una relación uno a muchos con pagos.
Un tramite se relaciona con people a través de tablas pivote como adquirentes_tramite y disponentes_tramite.
Un tramite se relaciona con inmuebles a través de la tabla pivote tramite_inmuebles.
Campos Notables:
tramites.status: Campo clave que dispara la lógica de negocio.
tramites.hash_validacion: Añadido para permitir una futura verificación pública del trámite.
jobs: Tabla utilizada por el sistema de colas de Laravel para gestionar tareas asíncronas.
5. Flujos Críticos
Creación y Cálculo de un Trámite:

El usuario completa el formulario de nuevo trámite.
El sistema valida los datos.
Se invoca al servicio IdtgbCalculator para determinar el monto del impuesto basado en el avalúo, las tasas, UFVs y exenciones aplicables.
Se crea el registro en la tabla tramites y sus relaciones correspondientes.
Finalización y Exportación de un Trámite:

// Flujo de ejecución
1. Usuario (UI) -> Actualiza Tramite.status a 'Finalizado'.
2. Laravel -> Dispara el evento 'updated' en el modelo Tramite.
3. TramiteObserver::updated(Tramite $tramite) -> Se ejecuta.
4. IF ($tramite->wasChanged('status') && $tramite->status === 'Finalizado') THEN
5.     ExportarAlSINJob::dispatch($tramite); // Encola el Job
6. END IF
7. Laravel Queue Worker -> Procesa el ExportarAlSINJob desde la tabla 'jobs'.
8.     - Genera el archivo CSV/JSON con los datos del trámite.
9.     - (Futuro) Conecta al servidor FTP del SIN.
10.    - (Futuro) Sube el archivo.
11.    - (Futuro) Registra el éxito o fallo de la operación.
6. Decisiones de Diseño Clave
Uso de Voyager: Se optó por Voyager para acelerar el desarrollo de la administración de catálogos, permitiendo al equipo centrarse en la lógica de negocio del trámite.
Lógica de Negocio Aislada: La creación del IdtgbCalculator Service es una decisión consciente para adherirse al principio de Responsabilidad Única y facilitar las pruebas y el mantenimiento del cálculo de impuestos.
Procesos Asíncronos para Integraciones: La exportación al SIN se diseñó como un Job asíncrono para no degradar la experiencia de usuario (la UI no se bloquea esperando la respuesta del sistema externo) y para aumentar la resiliencia del sistema (los jobs fallidos pueden reintentarse).
Seeders para Configuración de Voyager: Se utiliza un seeder (IdtgbMenuAppendSeeder) para añadir programáticamente los nuevos elementos de menú (como "Reportes"), asegurando que la configuración del entorno de desarrollo sea consistente y reproducible.

7. Deuda Técnica Identificada
Ausencia Total de Cobertura de Pruebas (Testing): Es la deuda más crítica. No existen pruebas unitarias ni de integración, especialmente para la lógica vital en IdtgbCalculator y el flujo de TramiteObserver -> Job.
Implementación Parcial de Módulos:
El Dashboard contiene datos estáticos y no está conectado a la base de datos.
El Módulo de Reportes existe solo como un ítem de menú, sin implementación.
La exportación al SIN es una simulación que no realiza la conexión FTP real.
Falta de Documentación de API: No hay una especificación formal (como OpenAPI/Swagger) para las futuras APIs que se puedan desarrollar.
Potencial de Consultas Ineficientes: Sin herramientas como Laravel Telescope o Debugbar activas, es probable que existan consultas N+1 no identificadas en los listados de datos.
8. Próximos Features a Implementar
Módulo de Reportes Avanzados: Generación de reportes en PDF y Excel sobre recaudación, tipos de trámite y rendimiento de funcionarios.
Dashboard Dinámico: Conectar la vista del dashboard a consultas reales y añadir filtros interactivos.
API Pública de Consulta: Exponer un endpoint público para verificar el estado de un trámite usando su hash_validacion.
Sistema de Notificaciones: Alertar a los usuarios sobre cambios de estado en sus trámites.
9. Tareas Pendientes Inmediatas
Conectar el Dashboard a Datos Reales: Crear el método en el controlador que consulte la base de datos y pase las métricas a la vista.
Implementar el primer reporte: Desarrollar la lógica y vista para el "Reporte de Rendimiento de Funcionarios".
Escribir Pruebas para IdtgbCalculator: Iniciar la mitigación de la deuda técnica creando pruebas unitarias para el componente más crítico de la lógica de negocio.







Milton Daniel Hipamo Cholima
Rol: Actúa como un asistente experto en desarrollo con Laravel.

Objetivo: Quiero que examines la estructura de mi proyecto para entender su propósito y estado actual.

Tareas:

Identifica cambios: Usa git status para encontrar archivos nuevos o modificados que no estén en el repositorio. Analiza la estructura: Describe la función de los directorios principales de Laravel (app, database, routes, etc.). Destaca lo específico: Dentro de tu análisis, pon especial atención a los archivos que definen la lógica de negocio de mi aplicación (Modelos, Servicios, Jobs, etc.) y explica brevemente qué hacen. Resume el propósito: Basándote en los nombres de los archivos y la lógica que encuentres, elabora un resumen de alto nivel sobre la finalidad de este software. Formato: Presenta la información de manera clara y concisa.


Gemini
stat_minus_1
Thinking
Claro, procedo a analizar tu proyecto Laravel como un asistente experto.

Primero, identificaré los cambios pendientes en tu repositorio.

check
Shell
You chose to run 'git ...' once.
Perfecto. Basado en el análisis y la estructura de tu proyecto, aquí tienes un resumen técnico.

1. Cambios Identificados (git status)
Archivo sin seguimiento: doc.md. Has añadido un nuevo archivo de documentación que aún no ha sido incluido en el control de versiones (git add).
2. Análisis de la Estructura y Lógica de Negocio
A continuación, se describe la función de los directorios clave de Laravel y se destacan los archivos específicos de tu aplicación.

app/ - Corazón de la Aplicación

Función General: Contiene toda la lógica de negocio principal.
Archivos Específicos de tu Proyecto:
Models/: Definen la representación de tus datos. Los nombres son muy descriptivos: Tramite, Inmueble, Pago, Person, Ufv. Estos modelos indican que el sistema gestiona "Trámites" relacionados con "Inmuebles", "Personas" y sus "Pagos", probablemente usando la "UFV" (Unidad de Fomento de Valor) como índice económico.
Services/IdtgbCalculator.php: Este es un archivo crucial. Es una clase de servicio dedicada exclusivamente a realizar cálculos, probablemente el "Impuesto a la Donación y Transmisión Gratuita de Bienes" (IDTGB). Aislar esta lógica aquí es una excelente práctica.
Jobs/ExportarAlSINJob.php: Define un "Job" o tarea asíncrona. Su propósito es exportar datos de un trámite finalizado a un sistema externo, presumiblemente el "SIN" (Servicio de Impuestos Nacionales). Esto se hace en segundo plano para no bloquear al usuario.
Observers/TramiteObserver.php: "Observa" el modelo Tramite. Su función es disparar acciones automáticamente cuando ocurren eventos en un trámite, como un cambio de estado. Seguramente es el encargado de llamar a ExportarAlSINJob cuando un trámite se marca como "Finalizado".
database/ - Definición y Datos de la BD

Función General: Contiene las migraciones, seeders y factories.
Archivos Específicos de tu Proyecto:
migrations/: Definen la estructura de tu base de datos. Archivos como 2025_09_22_122758_create_tramites_table.php y 2025_10_11_003404_add_hash_validacion_to_tramites_table.php crean las tablas para los modelos y añaden campos específicos, como un hash de validación para los trámites.
seeders/: Contienen clases para poblar la base de datos con datos iniciales o de prueba. IdtgbMaestrosSeeder.php y CalculadoraDemoSeeder.php sugieren que se usan para cargar datos maestros (catálogos) y para configurar escenarios de demostración para la calculadora de impuestos.
routes/ - Puntos de Entrada a la Aplicación

Función General: Define todas las URLs y endpoints.
Archivos Específicos de tu Proyecto:
web.php: Contiene las rutas para la interfaz web principal, donde los usuarios interactúan con los formularios de trámites.
api.php: Define las rutas para una posible API REST, aunque parece no ser el foco principal por ahora.
resources/ - Frontend

Función General: Contiene las vistas (plantillas HTML Blade), archivos CSS y JavaScript.
Archivos Específicos de tu Proyecto: Tu historial menciona la modificación de resources/views/vendor/voyager/index.blade.php, lo que confirma que usas Voyager como panel de administración y has personalizado su dashboard para mostrar métricas del negocio.
3. Resumen del Propósito del Software
Basado en el análisis, este software es un Sistema de Gestión de Trámites para el Impuesto a la Transferencia de Bienes Inmuebles.

Su finalidad es:

Registrar y gestionar de forma detallada los trámites de transferencia de propiedades.
Calcular automáticamente el impuesto correspondiente a cada trámite, utilizando valores económicos como la UFV.
Integrarse con un sistema gubernamental externo (SIN), exportando los datos de los trámites finalizados de manera asíncrona.
Proveer un panel de administración (Voyager) para gestionar los datos maestros del sistema (tipos de inmueble, tasas, etc.) y visualizar métricas clave en un dashboard personalizado.

