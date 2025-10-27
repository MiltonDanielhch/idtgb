# Documentación Técnica de la Base de Datos

## Visión General

Este esquema de base de datos está diseñado para un sistema de gestión de trámites de transferencia de inmuebles y cálculo de impuestos (IDTGB - Impuesto a la Transmisión Gratuita de Bienes). La arquitectura sigue los principios de un diseño relacional robusto, con un fuerte enfoque en la auditoría, la integridad de los datos y la trazabilidad de las operaciones.

El sistema se divide en los siguientes módulos funcionales:
1.  **Gestión de Identidades y Usuarios**: Manejo de usuarios del sistema y personas (naturales/jurídicas).
2.  **Catálogos y Datos de Referencia**: Tablas de apoyo para datos geográficos, tipos, tasas y exenciones.
3.  **Núcleo de Trámites**: Gestión del ciclo de vida de los trámites de transferencia.
4.  **Activos y Finanzas**: Registro de inmuebles, avalúos, pagos y documentos.
5.  **Sistema**: Tablas de utilidad para la operación de la aplicación.
6.  **Sistema y Administración (Framework)**: Tablas estándar de Laravel y el panel de administración.

> **Nota sobre Auditoría**: La mayoría de las tablas incluyen campos de auditoría como `registerUser_id`, `deleteUser_id`, etc. Este comportamiento se gestiona de forma centralizada a través del trait `App\Traits\RegistersUserEvents`, que se engancha a los eventos de los modelos para registrar automáticamente qué usuario realizó cada acción.

---

## Módulo 1: Gestión de Identidades y Usuarios

### `users`

Almacena los usuarios que pueden iniciar sesión en la aplicación.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key, Auto Increment | Identificador único del usuario. |
| `name` | `string` | | Nombre de usuario para el sistema. |
| `email` | `string` | Unique | Correo electrónico, utilizado para el login. |
| `email_verified_at` | `timestamp` | Nullable | Fecha de verificación del correo. |
| `password` | `string` | | Contraseña hasheada. |
| `remember_token` | `string` | Nullable | Token para "recordar sesión". |
| `status` | `smallint` | Default: `1` | Estado del usuario (1: Activo, 0: Inactivo). |
| `person_id` | `foreignId` | Nullable, FK -> `people.id` | Relación con la persona (Natural/Jurídica) asociada. |
| `registerUser_id` | `foreignId` | Nullable, FK -> `users.id` | Usuario que registró a este usuario. |
| `registerRole` | `string` | Nullable | Rol del usuario que lo registró. |
| `deleted_at` | `timestamp` | Nullable (Soft Delete) | Fecha de borrado lógico. |
| `deleteUser_id` | `foreignId` | Nullable, FK -> `users.id` | Usuario que realizó el borrado lógico. |
| `deleteRole` | `string` | Nullable | Rol del usuario que lo borró. |
| `deleteObservation` | `text` | Nullable | Motivo del borrado. |
| `created_at` / `updated_at` | `timestamp` | | Timestamps de Eloquent. |

**Relaciones Eloquent:**
*   `belongsTo(Person::class, 'person_id')`: Un usuario pertenece a una persona.
*   `belongsTo(User::class, 'registerUser_id')`: Usuario que lo registró.
*   `belongsTo(User::class, 'deleteUser_id')`: Usuario que lo borró.

### `people`

Registro central de todas las personas (naturales y jurídicas) que participan en los trámites.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `person_type` | `enum` | Default: 'Natural' | Tipo de persona: 'Natural' o 'Jurídica'. |
| `tipo_doc` | `string(10)` | Default: 'CI' | Tipo de documento (CI, NIT, Pasaporte, etc.). |
| `ci` / `ci_complemento` | `string` | Nullable | Número de Cédula de Identidad y su complemento. |
| `nit` | `string` | Nullable | Número de Identificación Tributaria. |
| `first_name` / `middle_name` | `string` | Nullable | Nombres de persona natural. |
| `paternal_surname` / `maternal_surname` | `string` | Nullable | Apellidos de persona natural. |
| `legal_name` | `string` | Nullable | Razón social de persona jurídica. |
| `birth_date` | `date` | Nullable | Fecha de nacimiento. |
| `email` / `phone` | `string` | Nullable | Contacto. |
| `address` | `text` | Nullable | Dirección de residencia. |
| `municipio_id` | `foreignId` | **No implementado en migración.** Nullable, FK -> `municipios.id` | Municipio de residencia de la persona. |
| `gender` | `enum` | Nullable | Género: 'Masculino', 'Femenino'. |
| `image` | `string` | Nullable | Path a la imagen de perfil. |
| `status` | `tinyint` | Default: `1` | Estado lógico (1: Activo, 0: Inactivo, 2: Pendiente). |
| `estado_persona` | `enum` | Default: 'Activo' | Estado civil/legal: 'Activo', 'Inactivo', 'Fallecido'. |
| `registerUser_id` / `deleteUser_id` | `foreignId` | Nullable, FK -> `users.id` | Auditoría. |
| `registerRole` / `deleteRole` | `string` | Nullable | Auditoría. |
| `deleteObservation` | `text` | Nullable | Auditoría. |
| `deleted_at` | `timestamp` | Nullable (Soft Delete) | Borrado lógico. |
| `created_at` / `updated_at` | `timestamp` | | Timestamps. |

**Índices y Claves Únicas:**
*   `unique(['tipo_doc', 'ci', 'ci_complemento'])`: **(Recomendado)** Evita duplicados de identidad.
*   **En migración actual**: `unique(['ci', 'ci_complemento'])` y `unique(['nit'])`. Se recomienda alinear con la documentación.

**Relaciones Eloquent:**
*   `hasMany(User::class, 'person_id')`
*   `hasMany(AdquirenteTramite::class)`
*   `hasMany(DisponenteTramite::class)`

---

## Módulo 2: Catálogos y Datos de Referencia

*(Estas tablas son ideales para ser pobladas con Seeders)*

### Catálogos Geográficos

Estructura jerárquica para la ubicación de inmuebles y personas.
*   **`departamentos`**: Nivel más alto de la división territorial.
    *   `id`, `nombre`, `codigo` (documentado como `codigo_ine`).
*   **`provincias`**: Nivel intermedio, pertenece a un `departamento`.
    *   `id`, `departamento_id`, `nombre`
*   **`municipios`**: Nivel más bajo, pertenece a una `provincia`.
    *   `id`, `provincia_id`, `nombre`

### `parentescos`

Catálogo de relaciones familiares (Ej: 'Hijo/a', 'Cónyuge', 'Padre/Madre'), clave para determinar la tasa del impuesto.

### `tipos_transmision`

Catálogo de tipos de transferencia (Ej: 'Sucesión', 'Donación', 'Anticipo de Legítima').

### `tipos_inmueble`

Catálogo para clasificar propiedades (Ej: 'Casa', 'Departamento', 'Terreno').

### `tasas`

**Tabla crítica**. Define las tasas de impuesto aplicables según criterios variables.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `departamento_id` | `foreignId` | FK -> `departamentos.id` | Departamento geográfico. |
| `parentesco_id` | `foreignId` | FK -> `parentescos.id` | Parentesco entre las partes. |
| `tipo_transmision_id` | `foreignId` | Nullable, FK -> `tipos_transmision.id` | Tipo de transmisión. |
| `tasa` | `decimal(5,2)` | | Valor de la tasa (ej: 1.00 para 1%). |
| `vigente_desde` | `date` | | Inicio de vigencia de la tasa. |
| `vigente_hasta` | `date` | Nullable | Fin de vigencia. |

**Índices y Claves Únicas:**
*   `unique(['departamento_id', 'parentesco_id', 'tipo_transmision_id', 'vigente_desde'])`: Garantiza que no haya tasas duplicadas para la misma combinación en la misma fecha.

### `exenciones`

Define las reglas de exención de impuestos.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `nombre` | `string(100)` | | Nombre de la exención. |
| `descripcion` | `text` | | Descripción detallada. |
| `tipo` | `enum` | | 'porcentaje' o 'monto_fijo'. |
| `valor` | `decimal(10,2)` | | Valor de la exención (ej: 100.00 o 15.50). |
| `monto_maximo` | `decimal(14,2)` | Nullable | Monto máximo a exonerar (si aplica). |
| `vigente_desde` / `vigente_hasta` | `date` | Nullable | Periodo de vigencia. |

---

## Módulo 3: Núcleo de Trámites

### `tramites`

Tabla central que representa cada trámite de transferencia.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `nro_tramite` | `string(15)` | Unique | Número de trámite generado por el sistema. |
| `fecha_presentacion` | `date` | | Fecha de inicio del trámite. |
| `tipo_transmision_id` | `foreignId` | FK -> `tipos_transmision.id` | Tipo de transmisión. |
| `valor_declarado` | `decimal(14,2)` | | Valor declarado por las partes. |
| `base_imponible` | `decimal(14,2)` | | Valor sobre el que se calcula el impuesto. |
| `total_idtgb` | `decimal(12,2)` | Default: 0 | Total del impuesto calculado. |
| `recargo_mora` | `decimal(12,2)` | Default: 0 | Recargos por mora. |
| `monto_final` | `decimal(14,2)` | Default: 0 | Monto total a pagar. |
| `ufv_aplicada` | `decimal(8,5)` | | Valor de UFV usado para el cálculo. |
| `estado` | `enum` | Default: 'Borrador' | Flujo del trámite. Ver nota abajo. |
| `fecha_transmision` | `date` | | Fecha real de la transferencia. |
| `fecha_vencimiento` | `date` | | Fecha límite para pagar sin recargo. |
| `observaciones` | `text` | Nullable | Notas internas. |
| `user_id` | `foreignId` | FK -> `users.id` | Usuario que gestiona el trámite. |
| `hash_validacion` | `char(64)` | Unique, Nullable | Hash SHA256 para garantizar la integridad de los datos. |
| `created_at` / `updated_at` | `timestamp` | | Timestamps. |

**Relaciones Eloquent:**
*   `belongsTo(User::class, 'user_id')`
*   `hasMany(TramiteInmueble::class)`
*   `hasMany(AdquirenteTramite::class)`
*   `hasMany(DisponenteTramite::class)`
*   `hasMany(Pago::class)`

**Notas para el Desarrollador:**
*   **Lógica de Negocio**: El cálculo de `base_imponible`, `total_idtgb` y `recargo_mora` es gestionado por la clase `App\Services\IdtgbCalculator`. El `hash_validacion` se genera en el observador `App\Observers\TramiteObserver`.
*   **Valores de `estado`**:
    *   `Borrador`: El trámite ha sido creado pero no finalizado.
    *   `Presentado`: (No en migración) El trámite ha sido presentado y está pendiente de revisión.
    *   `Observado`: El trámite tiene errores que deben ser subsanados por el usuario.
    *   `Validado`: (No en migración) El trámite ha sido revisado y está listo para el pago.
    *   `Pagado`: El impuesto ha sido pagado.
    *   `Anulado`: (No en doc) El trámite ha sido anulado.
    *   `Finalizado`: El trámite ha concluido exitosamente.

### Tablas Pivot del Trámite

*   **`tramite_inmuebles`**: Relaciona `tramites` con `inmuebles` (N-M).
*   **`adquirentes_tramite`**: Relaciona `tramites` con `people` (adquirentes). **Contiene datos de la relación**: `parentesco_id` (con el disponente), `tasa_aplicada`, `porcentaje`, `idtgb_proporcional`, `es_beneficiario_exencion`.
*   **`disponentes_tramite`**: Relaciona `tramites` con `people` (disponentes). **Contiene datos de la relación**: `tipo` ('Causante', 'Donante'), `fecha_fallecimiento`.
*   **`tramite_exenciones`**: Relaciona `tramites` con `exenciones` (N-M). **Contiene datos**: `monto_aplicado`.

**Notas para el Desarrollador:**
*   Para las relaciones N-M con datos extra (`adquirentes_tramite`, etc.), usar `belongsToMany` con `withPivot()` en los modelos Eloquent.

---

## Módulo 4: Activos y Finanzas

### `inmuebles`

Registro catastral de las propiedades.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `catastro` | `string(15)` | Unique | Código catastral único. |
| `tipo_inmueble_id` | `foreignId` | FK -> `tipos_inmueble.id` | Tipo de inmueble. |
| `municipio_id` | `foreignId` | Nullable, FK -> `municipios.id` | Ubicación geográfica. |
| `direccion` | `string(200)` | Nullable | Dirección detallada del inmueble. |
| `superficie_m2` | `decimal(12,2)` | Nullable | Superficie en metros cuadrados. |
| `valor_catastral` | `decimal(14,2)` | | Valor fiscal del inmueble. |
| `matricula_rr` | `string(20)` | Nullable | Matrícula en Derechos Reales. |
| `es_vivienda_unica_familiar` | `boolean` | Default: false | Indica si aplica esta exención. |
| `estado_inmueble` | `enum` | Default: 'Activo' | Estado del inmueble en el sistema. |

### `avaluos`

Registra valores de avalúo de los inmuebles a lo largo del tiempo.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `inmueble_id` | `foreignId` | FK -> `inmuebles.id`, Cascade Delete | Inmueble avalúado. |
| `tipo_avaluo` | `enum` | | 'Fiscal', 'Comercial', 'Pericial'. |
| `fecha_avaluo` | `date` | | Fecha en que se realizó el avalúo. |
| `valor` | `decimal(14,2)` | | Valor del avalúo. |
| `perito_id` | `foreignId` | Nullable, FK -> `people.id` | Perito que realizó el avalúo. |
| `documento_path` | `string(250)` | Nullable | Path al PDF del avalúo. |

### `pagos`

Registro de los pagos realizados para cada trámite.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `tramite_id` | `foreignId` | FK -> `tramites.id`, Cascade Delete | Trámite asociado. |
| `fecha_pago` | `datetime` | | Fecha y hora del pago. |
| `monto` | `decimal(14,2)` | | Monto pagado. |
| `qr_path` | `string` | Nullable | Path a la imagen del QR para pago. |
| `nro_operacion` | `string(25)` | Nullable | Número de operación bancaria. |
| `conciliado_el` | `timestamp` | Nullable | Fecha de conciliación bancaria. |
| `banco` | `string(30)` | Nullable | Banco donde se realizó el pago. |
| `estado` | `enum` | Default: 'Pendiente' | 'Pendiente', 'Aplicado', 'Reversado'. |

### `documentos`

Almacena los archivos digitales asociados a los trámites.

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `tramite_id` | `foreignId` | FK -> `tramites.id`, Cascade Delete | Trámite asociado. |
| `tipo_doc` | `enum` | | 'Escritura', 'Testamento', 'CI', etc. |
| `file_path` | `string(250)` | | Ruta al archivo en el almacenamiento. |
| `hash_sha256` | `char(64)` | Nullable | Hash de integridad del archivo. |
| `person_id` | `foreignId` | Nullable, FK -> `people.id` | Persona a la que pertenece el documento (si aplica). |
| `version` | `unsignedTinyInteger` | Default: 1 | Control de versiones del documento. |

---

## Módulo 5: Sistema

### `ufvs`

Tabla de referencia con el valor diario de la UFV (Unidad de Fomento de Vivienda).

| Columna | Tipo | Restricciones | Descripción |
|---|---|---|---|
| `id` | `bigint` | Primary Key | Identificador único. |
| `fecha` | `date` | Unique | Fecha del valor. |
| `valor` | `decimal(8,5)` | | Valor de la UFV para esa fecha. |

---

## Módulo 6: Sistema y Administración (Framework)

Estas tablas son parte de Laravel y/o el panel de administración (Voyager). No contienen lógica de negocio del IDTGB, pero son esenciales para el funcionamiento de la aplicación.

*   **`roles` / `permissions` / `permission_role`**: Gestión de roles y permisos de usuario.
*   **`password_resets`**: Tabla estándar de Laravel para el reseteo de contraseñas.
*   **`jobs` / `failed_jobs`**: Tablas del sistema de colas (Queues) de Laravel.
*   **`personal_access_tokens`**: Tabla de Laravel Sanctum para la autenticación de API.
*   **`pages` / `posts` / `categories`**: Tablas del CMS de Voyager, si se utiliza para contenido estático o blogs.

---

## Recomendaciones y Siguientes Pasos

1.  **Diagrama Entidad-Relación (ERD)**: Se recomienda generar un diagrama visual de la base de datos. Esto proporciona una visión general rápida y clara de la arquitectura. Herramientas como `draw.io` o plugins de su editor de base de datos pueden ser de gran ayuda.

2.  **Documentar Modelos de Eloquent**: El siguiente paso natural es documentar los modelos (`app/Models`), detallando `scopes`, `accessors`, `mutators` y la implementación de las relaciones aquí descritas.

3.  **Documentar Flujos de Usuario**: Describir los casos de uso principales (ej: "Crear un nuevo trámite de sucesión") ayudará a conectar la estructura de datos con la funcionalidad visible para el usuario.
