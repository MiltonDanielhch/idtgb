# Documentación de Modelos Eloquent

Esta sección detalla la implementación de los modelos de Laravel, sus relaciones, lógica de negocio y atributos importantes.

---

## Módulo 1: Gestión de Identidades y Usuarios

### `App\Models\User`

Representa a un usuario autenticado en el sistema.

*   **Extiende de**: `TCG\Voyager\Models\User`
*   **Traits**:
    *   `Illuminate\Database\Eloquent\SoftDeletes`: Habilita el borrado lógico.
    *   `Laravel\Sanctum\HasApiTokens`: Para la autenticación vía API.
    *   `App\Traits\RegistersUserEvents`: Registra automáticamente la actividad del usuario (creación/actualización) en otros modelos.
*   **Propiedades**:
    *   `$fillable`: `['person_id', 'name', 'role_id', 'email', 'status', ...]`
*   **Relaciones**:
    *   `person()`: `belongsTo(Person::class)`. El perfil de persona asociado a este usuario.

### `App\Models\Person`

Registro central para personas naturales o jurídicas. Es una de las tablas más importantes del sistema.

*   **Traits**:
    *   `Illuminate\Database\Eloquent\SoftDeletes`: Habilita el borrado lógico.
    *   `App\Traits\RegistersUserEvents`: Auditoría de creación y modificación.
*   **Constantes**:
    *   `STATUS_ACTIVE = 1`
    *   `STATUS_INACTIVE = 0`
    *   `STATUS_PENDING = 2`
*   **Propiedades**:
    *   `$casts`: `['birth_date' => 'date', 'status' => 'integer']`
    *   `$appends`: `['ubicacion_completa', 'ubicacion_segura']`
*   **Accesores y Mutadores**:
    *   `getFullNameAttribute()`: Retorna el nombre completo para personas naturales.
    *   `getDisplayNameAttribute()`: Retorna el nombre a mostrar (razón social para jurídicas, nombre completo para naturales).
    *   `getDisplayDocumentAttribute()`: Retorna el documento principal (NIT para jurídicas, CI para naturales).
    *   `getDisplayAgeAttribute()`: Calcula y retorna la edad.
    *   `getUbicacionCompletaAttribute()`: Retorna la dirección jerárquica completa (Municipio, Provincia, Departamento).
*   **Scopes**:
    *   `scopeActive($query)`: Filtra las personas cuyo estado es `STATUS_ACTIVE`.
*   **Relaciones**:
    *   `municipio()`: `belongsTo(Municipio::class)`.
    *   `adquirentesTramite()`: `hasMany(AdquirenteTramite::class)`.
    *   `disponentesTramite()`: `hasMany(DisponenteTramite::class)`.
    *   `registerUser()`: `belongsTo(User::class)`.
    *   `deleteUser()`: `belongsTo(User::class)`.

---

## Módulo 2: Catálogos y Datos de Referencia

### `App\Models\Departamento`, `Provincia`, `Municipio`

Modelos que gestionan la jerarquía geográfica.

*   **Relaciones**:
    *   `Departamento` -> `hasMany(Provincia::class)`
    *   `Provincia` -> `belongsTo(Departamento::class)`, `hasMany(Municipio::class)`
    *   `Municipio` -> `belongsTo(Provincia::class)`

### `App\Models\Tasa`

Define las tasas de impuesto aplicables según parentesco, ubicación y tipo de transmisión.

*   **Propiedades**:
    *   `$casts`: `['tasa' => 'decimal:2', 'vigente_desde' => 'date', 'vigente_hasta' => 'date']`
*   **Relaciones**:
    *   `departamento()`, `parentesco()`, `tipoTransmision()`: Relaciones `belongsTo` que definen los criterios de la tasa.
*   **Métodos Estáticos**:
    *   `vigente(int $deptoId, int $parentescoId, ?string $fecha)`: Busca y devuelve la tasa aplicable para una combinación de criterios en una fecha específica.

### `App\Models\Exencion`

Reglas y condiciones para la exención de impuestos.

*   **Propiedades**:
    *   `$casts`: `['valor' => 'decimal:2', 'monto_maximo' => 'decimal:2', 'vigente_desde' => 'date', 'vigente_hasta' => 'date']`
*   **Scopes**:
    *   `scopeVigente($query, ?string $fecha)`: Filtra las exenciones vigentes en una fecha determinada.

---

## Módulo 3: Núcleo de Trámites

### `App\Models\Tramite`

El modelo más importante del sistema. Orquesta toda la información de una transferencia de bien.

*   **Traits**: `App\Traits\RegistersUserEvents`.
*   **Propiedades**:
    *   `$casts`: `['fecha_presentacion' => 'date', 'fecha_transmision' => 'date', 'fecha_vencimiento' => 'date', 'valor_declarado' => 'decimal:2', 'base_imponible' => 'decimal:2', ...]`
*   **Relaciones**:
    *   `user()`: `belongsTo(User::class)`. El funcionario que gestiona el trámite.
    *   `inmuebles()`: `belongsToMany(Inmueble::class, 'tramite_inmuebles')`.
    *   `exenciones()`: `belongsToMany(Exencion::class, 'tramite_exenciones')->withPivot('monto_aplicado')`.
    *   `adquirentes()`: `hasMany(AdquirenteTramite::class)`. Relación directa con el modelo pivot.
    *   `disponentes()`: `hasMany(DisponenteTramite::class)`. Relación directa con el modelo pivot.
    *   `pagos()`: `hasMany(Pago::class)`.
    *   `documentos()`: `hasMany(Documento::class)`.
    *   `personasAdquirentes()`: `hasManyThrough(Person::class, AdquirenteTramite::class)`. Relación indirecta para obtener las personas adquirentes directamente.
    *   `personasDisponentes()`: `hasManyThrough(Person::class, DisponenteTramite::class)`. Relación indirecta para obtener las personas disponentes.
*   **Scopes**:
    *   `scopeConRelacionesCompletas($query)`: Carga previamente todas las relaciones anidadas comunes para optimizar consultas.
*   **Accesores**:
    *   `getAdquirentesCompletosAttribute()`: Retorna la colección de adquirentes con sus relaciones cargadas.
    *   `getEstadoColorAttribute()`: Devuelve un color (ej. 'success', 'warning') según el estado del trámite, útil para la UI.
*   **Lógica de Negocio**:
    *   `calcularMora()`: Calcula el recargo por mora basado en la `base_imponible` y la fecha de vencimiento.
    *   `generateHashValidacion()`: Genera un hash SHA256 para garantizar la integridad de los datos del trámite una vez finalizado.

### Modelos Pivot (`AdquirenteTramite`, `DisponenteTramite`, `TramiteInmueble`, `TramiteExencion`)

Modelos para las tablas intermedias que permiten definir relaciones y propiedades sobre ellas.

*   **Relaciones**: Cada modelo pivot tiene una relación `belongsTo` con `Tramite` y con el otro modelo implicado (ej: `Person` o `Inmueble`).
*   **Ejemplo**: `AdquirenteTramite` tiene `belongsTo(Tramite::class)` y `belongsTo(Person::class)`.

---

## Módulo 4: Activos y Finanzas

### `App\Models\Inmueble`

Representa una propiedad o bien inmueble.

*   **Propiedades**:
    *   `$casts`: `['es_vivienda_unica_familiar' => 'boolean', 'valor_catastral' => 'decimal:2']`
*   **Relaciones**:
    *   `tipoInmueble()`, `municipio()`: `belongsTo`.
    *   `avaluos()`: `hasMany(Avaluo::class)`.
    *   `tramiteInmuebles()`: `hasMany(TramiteInmueble::class)`.
*   **Lógica de Negocio**:
    *   `avaluoVigente()`: Devuelve el último avalúo vigente registrado para la propiedad.

### `App\Models\Pago`

Registra una transacción financiera asociada a un trámite.

*   **Traits**: `App\Traits\RegistersUserEvents`.
*   **Propiedades**:
    *   `$casts`: `['fecha_pago' => 'datetime', 'conciliado_el' => 'datetime', 'monto' => 'decimal:2']`
*   **Relaciones**:
    *   `tramite()`: `belongsTo(Tramite::class)`.
    *   `creador()`, `editor()`: `belongsTo(User::class)`.
*   **Métodos de Ayuda**:
    *   `estaAplicado()`: Retorna `true` si el estado del pago es 'Aplicado'.

### `App\Models\Documento`

Gestiona los archivos digitales asociados a un trámite.

*   **Relaciones**:
    *   `tramite()`: `belongsTo(Tramite::class)`.
    *   `persona()`: `belongsTo(Person::class)`.
*   **Propiedades**:
    *   `$casts`: `['vigente' => 'boolean']`

---

## Anexo: Traits Personalizados

### `App\Traits\RegistersUserEvents`

Este trait es una pieza clave para la auditoría del sistema. Su función es interceptar los eventos `creating` y `updating` de los modelos que lo utilizan.

*   **En `creating`**: Asigna automáticamente el `id` del usuario autenticado a los campos `created_by` o `registerUser_id` del modelo.
*   **En `updating`**: Asigna automáticamente el `id` del usuario autenticado al campo `updated_by` del modelo.

Esto centraliza la lógica de auditoría y mantiene los controladores limpios de esta responsabilidad.
