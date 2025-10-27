# Documentación de Controladores

## Arquitectura y Patrones Comunes

Los controladores de la aplicación actúan como la capa de orquestación entre las solicitudes HTTP y la lógica de negocio. Se observan los siguientes patrones de diseño clave:

1.  **Inyección de Dependencias**: Se utiliza para desacoplar los controladores de sus dependencias, como los modelos, servicios y Form Requests.
2.  **Resource Controllers**: La mayoría de los controladores siguen el patrón de recursos de Laravel, exponiendo métodos estándar como `index`, `show`, `store`, `update` y `destroy`.
3.  **Form Requests para Validación**: La lógica de validación de datos de entrada se delega a clases `FormRequest` dedicadas (ej. `StoreTramiteRequest`). Esto mantiene los controladores limpios y la validación centralizada y reutilizable.
4.  **Policies para Autorización**: La seguridad y los permisos de cada acción se gestionan mediante `Policies` (ej. `TramitePolicy`). Los controladores invocan `$this->authorize()` para asegurar que el usuario tiene los permisos necesarios.
5.  **Capa de Servicios para Lógica de Negocio**: La lógica de negocio compleja, especialmente el cálculo de impuestos, está encapsulada en servicios como `IdtgbCalculator`. Los controladores invocan estos servicios pero no contienen la lógica de cálculo en sí mismos.
6.  **Transacciones de Base de Datos**: Las operaciones que involucran múltiples escrituras en la base de datos (ej. `store`, `update`) se envuelven en transacciones (`DB::transaction()`) para garantizar la atomicidad y la integridad de los datos.

---

## Controladores Principales

### `TramiteController`

*   **Propósito**: Gestiona la visualización, edición, eliminación y generación de reportes de un `Tramite` ya existente. La creación es delegada al `TramiteWizardController`.
*   **Ruta Base**: `/admin/tramites`
*   **Form Requests**: `UpdateTramiteRequest`.
*   **Policy**: `TramitePolicy`

| Método | Descripción de la Lógica |
|---|---|
| `update` | 1. Valida la entrada con `UpdateTramiteRequest`.<br>2. Actualiza los datos básicos del trámite.<br>3. **Invoca a `IdtgbCalculator`** para recalcular el impuesto con los nuevos datos, usando `withoutEvents` para evitar bucles con los Observers. |
| `destroy` | Autoriza la acción. Verifica que el trámite no tenga pagos aplicados. Si es seguro, realiza un borrado lógico. |
| `a01(Tramite $tramite)` | **Genera el PDF del Formulario A-01**. Carga todas las relaciones, genera el hash de validación y un código QR, y renderiza la vista en un PDF usando `barryvdh/dompdf`. |

### `AdquirenteTramiteController` (Recurso Anidado)

*   **Propósito**: Gestiona los adquirentes (compradores/herederos) de un `Tramite` específico.
*   **Ruta Base**: `/admin/tramites/{tramite}/adquirentes`

| Método | Descripción de la Lógica |
|---|---|
| `store` | 1. Añade una persona como adquirente a un trámite.<br>2. Busca la tasa de impuesto aplicable para la relación.<br>3. **Invoca a `IdtgbCalculator`** para recalcular el monto total del trámite. |
| `destroy` | Elimina al adquirente del trámite, borra archivos asociados y **vuelve a invocar a `IdtgbCalculator`**. |

### `PagoController` (Recurso Anidado)

*   **Propósito**: Gestiona los pagos asociados a un `Tramite`.
*   **Ruta Base**: `/admin/tramites/{tramite}/pagos`
*   **Policy**: `PagoPolicy`

| Método | Descripción de la Lógica |
|---|---|
| `store` | 1. Registra un nuevo pago.<br>2. Genera un archivo de imagen QR con los datos del pago.<br>3. **Lógica de Estado**: Si el monto pagado cubre el total del impuesto (`monto >= monto_final`), actualiza el estado del `Tramite` a 'Pagado' y el del pago a 'Aplicado'. |
| `destroy` | **No elimina el pago**. Lo marca como 'Reversado' y devuelve el `Tramite` a su estado anterior (ej. 'Borrador'), previniendo la eliminación de registros financieros. |

### `DocumentoController`

*   **Propósito**: Gestiona la subida y el versionado de archivos para un `Tramite`.
*   **Ruta Base**: `/admin/tramites/{tramite}/documentos`

| Método | Descripción de la Lógica |
|---|---|
| `store` | 1. Sube el archivo al disco.<br>2. Calcula su hash SHA-256 para integridad.<br>3. **Lógica de Versionado**: Busca documentos del mismo tipo y los marca como no vigentes (`vigente = false`).<br>4. Crea el nuevo registro de `Documento` con la versión `max(version) + 1` y lo marca como vigente. |
| `destroy` | No elimina el registro ni el archivo. Simplemente marca el `Documento` como no vigente. |

### `TramiteWizardController`

*   **Propósito**: Orquesta la creación de un `Tramite` en un asistente de 7 pasos, gestionando el estado a través de la sesión del usuario.
*   **Ruta Base**: `/admin/tramites/wizard`

| Método | Descripción de la Lógica |
|---|---|
| `createStepX` | Muestra la vista para cada paso del asistente, cargando los datos necesarios (ej. tipos de transmisión, personas, inmuebles) y los datos ya guardados en la sesión. |
| `postStepX` / `add...` / `remove...` | Valida y guarda los datos del paso actual en la sesión (`tramite_wizard_data`) y redirige al siguiente paso o refresca la vista actual (al añadir/quitar elementos). |
| `store` | **Método final del asistente**.<br>1. Valida que todos los pasos obligatorios estén completos.<br>2. Dentro de una transacción, crea el `Tramite` y todas sus relaciones (disponentes, adquirentes, inmuebles, documentos, exenciones) a partir de los datos de la sesión.<br>3. Mueve los archivos de documentos temporales a su ubicación final.<br>4. **Invoca a `IdtgbCalculator`** para realizar el cálculo inicial del impuesto.<br>5. Limpia la sesión del asistente y redirige a la vista del trámite recién creado. |
| `cancelWizard` | Limpia los datos del asistente de la sesión y redirige al listado de trámites. |

---

## Otros Controladores Relevantes

### `PersonController`

*   **Propósito**: CRUD estándar para el modelo `Person`.
*   **Puntos Clave**: Implementa autorización con `PersonPolicy`. El método `list` realiza búsquedas complejas por CI, NIT o concatenando nombres. Gestiona la subida de imágenes de perfil.

### `UserController` y `RoleController`

*   **Propósito**: Extienden la funcionalidad de Voyager para la gestión de usuarios y roles.
*   **Puntos Clave**: Implementan listados con paginación y búsqueda vía AJAX. Incluyen lógica para que los usuarios no administradores no puedan ver o gestionar al rol de Super-Administrador.

### `UfvController`

*   **Propósito**: CRUD para los valores de la Unidad de Fomento de Vivienda.
*   **Puntos Clave**: Incluye un método `import()` para la carga masiva desde un archivo CSV. Este método es especialmente robusto, ya que **detecta automáticamente el delimitador** (`,` o `;`) y **múltiples formatos de fecha** (`d/m/Y` o `Y-m-d`), además de manejar duplicados y errores de formato.

### `ReporteController`

*   **Propósito**: Genera y exporta reportes del sistema.
*   **Puntos Clave**: Permite a los usuarios seleccionar un tipo de reporte (`recaudacion`, `tipos_tramite`) y un rango de fechas. Puede mostrar los resultados en HTML o exportarlos a PDF.

### `CalculadoraBeniController` y `ValidacionController` (Públicos)

*   **Propósito**: Exponen funcionalidades al público sin necesidad de autenticación.
*   **`CalculadoraBeniController`**: Proporciona una calculadora de impuestos simple.
*   **`ValidacionController`**: Permite a cualquier persona verificar la autenticidad de un trámite finalizado a través de su `hash_validacion` único, mostrando una vista pública con los datos del trámite.

### Controladores de Catálogos

*   **Controladores**: `ParentescoController`, `TipoTransmisionController`, `TipoInmuebleController`, `ExencionController`, `TasaController`.
*   **Propósito**: Gestionan los CRUDs de las tablas de catálogo del sistema.
*   **Puntos Clave**: La mayoría implementa una **verificación de dependencias** en el método `destroy()`. Por ejemplo, no se puede eliminar un `Parentesco` si está siendo utilizado en una `Tasa`, previniendo así la corrupción de datos.
