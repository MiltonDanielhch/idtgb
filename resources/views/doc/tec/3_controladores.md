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

*   **Propósito**: Gestiona el ciclo de vida completo de un `Tramite`. Es el controlador más importante del núcleo de negocio.
*   **Ruta Base**: `/admin/tramites`
*   **Form Requests**: `StoreTramiteRequest`, `UpdateTramiteRequest`
*   **Policy**: `TramitePolicy`

| Método | Descripción de la Lógica |
|---|---|
| `store` / `update` | 1. Valida la entrada con el FormRequest.<br>2. Obtiene la UFV del día y calcula la fecha de vencimiento.<br>3. Dentro de una transacción, crea o actualiza el `Tramite`.<br>4. **Invoca a `IdtgbCalculator`** para ejecutar el cálculo completo del impuesto.<br>5. Redirige con un mensaje de éxito. |
| `destroy` | Autoriza la acción. Verifica que el trámite no tenga pagos aplicados. Si es seguro, realiza un borrado lógico. |
| `a01(Tramite $tramite)` | **Genera el PDF del Formulario A-01**. Carga todas las relaciones, genera el hash de validación y un código QR, y renderiza la vista en un PDF usando `barryvdh/dompdf`. |

### `AdquirenteTramiteController` (Recurso Anidado)

*   **Propósito**: Gestiona los adquirentes (compradores/herederos) de un `Tramite` específico.
*   **Ruta Base**: `/admin/tramites/{tramite}/adquirentes`

| Método | Descripción de la Lógica |
|---|---|
| `store` | 1. Añade una persona como adquirente a un trámite.<br>2. Busca la tasa de impuesto aplicable para la relación.<br>3. **Invoca a `IdtgbCalculator`** para recalcular el monto total del trámite. |
| `destroy` | Elimina al adquirente del trámite, borra archivos asociados y **vuelve a invocar a `IdtgbCalculator`**. |

### `PagoController`

*   **Propósito**: Gestiona los pagos asociados a un `Tramite`.
*   **Ruta Base**: `/admin/pagos` (o anidado bajo trámites)
*   **Policy**: `PagoPolicy`

| Método | Descripción de la Lógica |
|---|---|
| `store` | 1. Registra un nuevo pago para un trámite.<br>2. **Lógica de Estado**: Si el monto pagado cubre el total del impuesto (`monto >= monto_final`), actualiza el estado del `Tramite` a 'Pagado'. |
| `destroy` | **No elimina el pago**. Lo marca como 'Reversado' y devuelve el `Tramite` a su estado anterior (ej. 'Borrador'), previniendo la eliminación de registros financieros. |

### `DocumentoController`

*   **Propósito**: Gestiona la subida y el versionado de archivos para un `Tramite`.
*   **Ruta Base**: `/admin/tramites/{tramite}/documentos`

| Método | Descripción de la Lógica |
|---|---|
| `store` | 1. Sube el archivo al disco.<br>2. Calcula su hash SHA-256 para integridad.<br>3. **Lógica de Versionado**: Busca documentos del mismo tipo y los marca como no vigentes (`vigente = false`).<br>4. Crea el nuevo registro de `Documento` con la versión `max(version) + 1` y lo marca como vigente. |
| `destroy` | No elimina el registro ni el archivo. Simplemente marca el `Documento` como no vigente. |

---

## Otros Controladores Relevantes

### `PersonController`

*   **Propósito**: CRUD estándar para el modelo `Person`.
*   **Puntos Clave**: Implementa autorización con `PersonPolicy` para todas sus acciones. El método `list` realiza búsquedas complejas concatenando nombres. Gestiona la subida de imágenes de perfil.

### `UfvController`

*   **Propósito**: CRUD para los valores de la Unidad de Fomento de Vivienda.
*   **Puntos Clave**: Incluye un método `import()` para la carga masiva de valores de UFV desde un archivo CSV, procesando los datos dentro de una transacción.

### `ReporteController`

*   **Propósito**: Genera y exporta reportes del sistema.
*   **Puntos Clave**: Permite a los usuarios seleccionar un tipo de reporte y un rango de fechas. Puede mostrar los resultados en HTML o exportarlos directamente a PDF usando `barryvdh/dompdf`.

### `CalculadoraBeniController`

*   **Propósito**: Expone un endpoint de API para realizar cálculos de estimación de impuestos.
*   **Puntos Clave**: Tiene un método `calcular()` que recibe datos en JSON, invoca al servicio `IdtgbCalculator` y devuelve el resultado de la estimación, también en JSON. Ideal para ser consumido por un frontend interactivo.