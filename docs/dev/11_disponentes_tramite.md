# Documentación Técnica: Módulo DisponenteTrámite

Este documento detalla la implementación técnica del módulo `DisponenteTrámite`, que conecta a las personas (disponentes) con un trámite específico, es decir, quienes transfieren el bien o derecho.

## 1. Propósito del Módulo

El módulo `DisponenteTrámite` gestiona la relación uno a muchos entre un `Trámite` y las `Person` que actúan como disponentes (vendedores, donantes, etc.). Al igual que el módulo de adquirentes, trata a cada disponente como una entidad única ligada a un trámite.

Este módulo es más sencillo que el de adquirentes ya que su propósito principal es informativo: registrar quiénes son las personas que transfieren la propiedad. A diferencia de los adquirentes, los disponentes no afectan directamente el cálculo de impuestos en este sistema, por lo que su lógica no invoca al `IdtgbCalculator`.

## 2. Estructura de la Base de Datos

La relación se materializa en la tabla `disponentes_tramite`.

### Migración: `2025_09_22_122816_create_disponentes_tramite_table.php`

```php
Schema::create('disponentes_tramite', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tramite_id')->constrained()->cascadeOnDelete();
    $table->foreignId('person_id')->constrained('people');
    $table->enum('tipo', ['Causante', 'Donante', 'Testador']);
    $table->date('fecha_fallecimiento')->nullable();
    $table->boolean('es_discapacitado')->default(false);
    $table->timestamps();
});
```

-   **`tramite_id`**: FK a la tabla `tramites`.
-   **`person_id`**: FK a la tabla `people`, identificando al disponente.
-   **`tipo`**: El rol del disponente en la transferencia (ej. `Causante` en una herencia, `Donante`, etc.).
-   **`fecha_fallecimiento`**: Campo opcional para casos como sucesiones hereditarias.
-   **`es_discapacitado`**: Flag booleano con fines informativos.

## 3. Modelos (Eloquent)

### a. `App\Models\DisponenteTramite`

Este modelo representa una entrada en la tabla `disponentes_tramite`.

-   **Tabla**: `disponentes_tramite`
-   **Fillable**: `['tramite_id', 'person_id', 'tipo', 'fecha_fallecimiento', 'es_discapacitado']`
-   **Relaciones**:
    -   `tramite()`: `belongsTo(Tramite::class)`
    -   `person()`: `belongsTo(Person::class, 'person_id')`

```php
// app/Models/DisponenteTramite.php
class DisponenteTramite extends Model
{
    // ...
    public function tramite()
    {
        return $this->belongsTo(Tramite::class);
    }

    public function person()
    {
        return $this->belongsTo(Person::class, 'person_id');
    }
}
```

### b. `App\Models\Tramite`

El modelo `Tramite` define la relación inversa `hasMany` y una `hasManyThrough` para conveniencia.

```php
// app/Models/Tramite.php

// Relación directa con el modelo intermedio
public function disponentes()
{
    return $this->hasMany(DisponenteTramite::class);
}

// Relación para obtener las Personas directamente
public function personasDisponentes()
{
    return $this->hasManyThrough(
        Person::class,
        DisponenteTramite::class,
        'tramite_id', // FK en disponentes_tramite
        'id',         // FK en people
        'id',         // Local key en tramites
        'person_id'   // FK en disponentes_tramite que apunta a people
    );
}
```

## 4. Rutas (Web)

Las rutas siguen la misma estructura anidada que otros módulos relacionados a trámites.

**Archivo**: `routes/web.php`

```php
// routes/web.php

Route::prefix('admin')->group(function () {
    // ...
    Route::prefix('tramites/{tramite}/disponentes')->name('admin.tramites.disponentes.')->group(function () {
        Route::get('/', [DisponenteTramiteController::class, 'index'])->name('index');
        Route::get('/ajax/list', [DisponenteTramiteController::class, 'list'])->name('ajax.list');
        Route::get('/create', [DisponenteTramiteController::class, 'create'])->name('create');
        Route::post('/', [DisponenteTramiteController::class, 'store'])->name('store');
        Route::get('/{item}', [DisponenteTramiteController::class, 'show'])->name('show');
        Route::delete('/{item}', [DisponenteTramiteController::class, 'destroy'])->name('destroy');
    });
    // ...
});
```

-   **`{tramite}`**: ID del `Tramite` padre.
-   **`{item}`**: ID de la instancia de `DisponenteTramite`.

## 5. Controlador: `DisponenteTramiteController`

**Archivo**: `app/Http/Controllers/DisponenteTramiteController.php`

Gestiona el CRUD para los disponentes de un trámite. Su lógica es más simple que la del controlador de adquirentes.

-   **`index()` y `list()`**: Muestran la lista de disponentes de un trámite.
-   **`create()`**: Muestra el formulario para agregar un nuevo disponente, filtrando las personas para no incluir duplicados.
-   **`store(StoreDisponenteTramiteRequest $request, Tramite $tramite)`**:
    1.  Valida la solicitud usando `StoreDisponenteTramiteRequest`.
    2.  Crea un nuevo registro en `disponentes_tramite` con los datos proporcionados.
    3.  Toda la operación se ejecuta dentro de una transacción de BD para seguridad.
    4.  **Importante**: No se invoca al `IdtgbCalculator`, ya que agregar o quitar un disponente no altera el cálculo del impuesto.
-   **`destroy(Tramite $tramite, DisponenteTramite $item)`**:
    1.  Elimina el registro de `disponentes_tramite`.
    2.  Se ejecuta dentro de una transacción.

## 6. Validación: `StoreDisponenteTramiteRequest`

**Archivo**: `app/Http/Requests/StoreDisponenteTramiteRequest.php`

Valida los datos para agregar un disponente.

-   **`rules()`**:
    -   `person_id`: Requerido y debe existir en la tabla `people`.
    -   `tipo`: Requerido y debe ser uno de los valores definidos (`Causante`, `Donante`, `Testador`).
    -   `fecha_fallecimiento`: Opcional, debe ser una fecha válida no futura.
-   **Validación Personalizada (`withValidator`)**:
    -   Asegura que la misma persona no pueda ser agregada dos veces como disponente en el mismo trámite.

## 7. Autorización: `DisponenteTramitePolicy`

**Archivo**: `app/Policies/DisponenteTramitePolicy.php`

Define los permisos basados en Voyager para las acciones del CRUD.

-   `viewAny`: Requiere `browse_disponentes_tramite`.
-   `view`: Requiere `read_disponentes_tramite`.
-   `create`: Requiere `add_disponentes_tramite`.
-   `delete`: Requiere `delete_disponentes_tramite`.

## 8. Flujo de Operación (Resumen)

1.  Un usuario navega a la sección de disponentes de un trámite.
2.  El `DisponenteTramiteController@index` muestra los disponentes ya registrados.
3.  El usuario hace clic en "Agregar Disponente".
4.  Se presenta un formulario con una lista de personas (excluyendo las ya agregadas).
5.  Al enviar el formulario, el método `store` del controlador valida los datos y crea el nuevo registro `DisponenteTramite`.
6.  El usuario es redirigido a la lista de disponentes. No hay recálculos de impuestos involucrados.
7.  La eliminación (`destroy`) simplemente borra el registro.

## 9. Posibles Bugs, Mejoras y Optimizaciones

El análisis del código del módulo `DisponenteTramite` ha revelado varias oportunidades críticas de mejora en rendimiento, seguridad y mantenibilidad.

### a. Ausencia de Funcionalidad de Actualización (`Update`)

-   **Problema**: Al igual que en el módulo de adquirentes, no existe una forma de editar un disponente una vez ha sido agregado. Si se comete un error, la única opción es eliminarlo y volver a crearlo.
-   **Mejora Sugerida**:
    -   Implementar el flujo completo de `edit` y `update`:
        1.  **Rutas**: Añadir las rutas `GET .../{item}/edit` y `PUT/PATCH .../{item}` en `routes/web.php`.
        2.  **Controlador**: Implementar los métodos `edit()` y `update()` en `DisponenteTramiteController`.
        3.  **Request**: Crear un `UpdateDisponenteTramiteRequest` para la validación.
        4.  **Política**: Añadir el permiso `update` en `DisponenteTramitePolicy`.

### b. [CORREGIDO] Rendimiento en Formulario de Creación

-   **Estado**: ✅ **CORREGIDO EN EL CÓDIGO ACTUAL**
-   **Nota Histórica**: La documentación original mencionaba que el método `create` cargaba todas las personas de la base de datos para luego filtrarlas en PHP, lo cual era ineficiente.
-   **Estado Actual**: El código en `DisponenteTramiteController.php:53-58` YA está optimizado usando `whereDoesntHave`:
    ```php
    $personas = Person::where('status', 1)
        ->where('estado_persona', 'Activo')
        ->whereDoesntHave('disponentesTramite', fn($q) => $q->where('tramite_id', $tramite->id))
        ->orderBy('first_name')
        ->orderBy('paternal_surname')
        ->get();
    ```
-   **Resultado**: El filtrado se realiza directamente en la base de datos, lo cual es eficiente y correcto.

### c. Riesgo de Integridad de Datos: Falta de Índice Único

-   **Problema**: La tabla `disponentes_tramite` no tiene un índice único compuesto para `['tramite_id', 'person_id']`. La validación que previene duplicados se hace solo a nivel de aplicación en `StoreDisponenteTramiteRequest`.
-   **Riesgo**: Si la validación de la aplicación falla o es eludida, es posible insertar la misma persona dos veces como disponente en el mismo trámite, corrompiendo los datos.
-   **Solución Crítica**:
    -   **Añadir Índice en la Migración**: Modificar la migración `2025_09_22_122816_create_disponentes_tramite_table.php` o crear una nueva para añadir el índice.
    ```php
    // database/migrations/..._create_disponentes_tramite_table.php
    $table->unique(['tramite_id', 'person_id']);
    ```
    -   Esto garantiza la unicidad a nivel de base de datos, que es la forma más robusta de asegurar la integridad de los datos.

### d. Modernización: Uso de `Enum` para el Campo `tipo`

-   **Problema**: El campo `tipo` utiliza un `enum` de base de datos y se valida con un array quemado en el código en `StoreDisponenteTramiteRequest`.
    ```php
    // app/Http/Requests/StoreDisponenteTramiteRequest.php
    'tipo' => ['required', Rule::in(['Causante', 'Donante', 'Testador'])],
    ```
-   **Mejora Sugerida**:
    -   **Crear un Enum de PHP**: Aprovechar los Enums de PHP 8.1+ para una mayor seguridad de tipos y autocompletado.
    ```php
    // app/Enums/TipoDisponente.php
    namespace App\Enums;

    enum TipoDisponente: string
    {
        case CAUSANTE = 'Causante';
        case DONANTE = 'Donante';
        case TESTADOR = 'Testador';
    }
    ```
    -   **Actualizar el Modelo**: Indicarle a Eloquent que haga un "casting" de este campo al nuevo Enum.
    ```php
    // app/Models/DisponenteTramite.php
    use App\Enums\TipoDisponente;
    protected $casts = [
        'tipo' => TipoDisponente::class,
    ];
    ```
    -   **Actualizar la Validación**: Usar el Enum para la regla de validación, eliminando el array quemado.
    ```php
    // app/Http/Requests/StoreDisponenteTramiteRequest.php
    use Illuminate\Validation\Rules\Enum;
    'tipo' => ['required', new Enum(TipoDisponente::class)],
    ```

### e. Simplificación de Validación de Unicidad

-   **Problema**: La validación de unicidad en `StoreDisponenteTramiteRequest` se realiza con una lógica personalizada en `withValidator`.
-   **Mejora Sugerida**:
    -   Una vez añadido el índice único en la base de datos (punto c), esta validación puede ser reemplazada por la regla estándar de Laravel, que es más limpia y eficiente.
    ```php
    // app/Http/Requests/StoreDisponenteTramiteRequest.php -> rules()
    use Illuminate\Validation\Rule;

    'person_id' => [
        'required',
        'exists:people,id',
        Rule::unique('disponentes_tramite')->where('tramite_id', $this->route('tramite')->id)
    ],
    ```
    -   Esto elimina la necesidad del método `withValidator` para esta comprobación.

### f. Automatización de Campos de Auditoría (`created_by`, `updated_by`)

-   **Problema**: En `DisponenteTramiteController@store`, los campos de autoría se asignan manualmente.
    ```php
    // El modelo DisponenteTramite no parece tenerlos, pero si los tuviera,
    // es un patrón común que se debe evitar.
    ```
-   **Mejora Sugerida**:
    -   **Usar el Trait `RegistersUserEvents`**: El proyecto ya incluye un trait (`App\Traits\RegistersUserEvents`) que automatiza el llenado de `created_by` y `updated_by` mediante observadores.
    -   **Implementar en el Modelo**: Simplemente hay que agregar el trait al modelo `DisponenteTramite`.
    ```php
    // app/Models/DisponenteTramite.php
    use App\Traits\RegistersUserEvents;
    use Illuminate\Database\Eloquent\Model;

    class DisponenteTramite extends Model
    {
        use RegistersUserEvents;
        // ...
    }
    ```
    - Esto centraliza la lógica y reduce el código repetido en los controladores. Es importante asegurarse que los campos `created_by` y `updated_by` existan en la tabla, y si no, agregarlos.

### g. BUG CRÍTICO: Campos No Existentes en la Base de Datos

-   **Ubicación**: `app/Http/Controllers/DisponenteTramiteController.php:81-82`
-   **Problema**: El método `store()` asigna manualmente los campos `created_by` y `updated_by`:
    ```php
    // Líneas 81-82
    'created_by' => auth()->id(),
    'updated_by' => auth()->id(),
    ```
    Sin embargo, **estos campos NO existen en la migración** de la tabla `disponentes_tramite` (archivo: `database/migrations/2025_09_22_122816_create_disponentes_tramite_table.php`).
-   **Impacto**: Esto causará un error de SQL (`Column not found`) al intentar insertar el registro.
-   **Solución**:
    -   **Opción A**: Crear una nueva migración para agregar los campos:
        ```php
        // database/migrations/XXXX_XX_XX_XXXXXX_add_audit_fields_to_disponentes_tramite_table.php
        public function up()
        {
            Schema::table('disponentes_tramite', function (Blueprint $table) {
                $table->foreignId('created_by')->nullable()->constrained('users')->after('timestamps');
                $table->foreignId('updated_by')->nullable()->constrained('users')->after('created_by');
            });
        }
        ```
    -   **Opción B**: Eliminar estas líneas del controlador si no se necesita auditoría.

### h. Inconsistencia de Nombres de Campos

-   **Ubicación**: `app/Http/Controllers/DisponenteTramiteController.php:34`
-   **Problema**: El método `list()` usa nombres de campos que no corresponden a la tabla `people`:
    ```php
    // Línea 34
    ->whereHas('person', fn($sq) => $sq->where('ci', 'like', "%{$search}%")
        ->orWhere('first_name', 'like', "%{$search}%")
        ->orWhere('paternal_surname', 'like', "%{$search}%"))
    ```
-   **Estado**: El código está **CORRECTO** porque la tabla `people` sí usa `first_name` y `paternal_surname`.
-   **Nota**: Sin embargo, la documentación (sección 9b) menciona incorrectamente `apellido_paterno` y `apellido_materno`. La tabla real usa:
    -   `first_name` (no `nombre`)
    -   `paternal_surname` (no `apellido_paterno`)
    -   `maternal_surname` (no `apellido_materno`)

### i. Vista Preparada para Edición Pero No Implementada

-   **Ubicación**: `resources/views/admin/tramites/disponentes/edit-add.blade.php:9-11`
-   **Problema**: La vista `edit-add.blade.php` ya está preparada para modo edición:
    ```php
    // Líneas 9-11
    <form action="{{ ($item->exists ?? false)
            ? route('admin.tramites.disponentes.update', [$tramite, $item])
            : route('admin.tramites.disponentes.store', $tramite) }}"
    ```
    Y el select de personas se deshabilita en modo edición (línea 30):
    ```php
    <select ... {{ ($item->exists ?? false) ? 'disabled' : '' }}>
    ```
-   **Falta**:
    -   No existe la ruta `PUT/PATCH admin/tramites/{tramite}/disponentes/{item}`
    -   No existe el método `update()` en `DisponenteTramiteController`
    -   No existe el método `edit()` en `DisponenteTramiteController`
    -   No existe el permiso `update_disponentes_tramite` en `DisponenteTramitePolicy`
-   **Impacto**: La vista tiene código para edición pero no puede ejecutarse. Si se intenta acceder a la ruta de edición, dará error 404.

### j. Falta de Validación de Tipo de Persona

-   **Ubicación**: `app/Http/Requests/StoreDisponenteTramiteRequest.php`
-   **Problema**: No se valida que la persona seleccionada sea de tipo `Natural` y no `Jurídica`.
-   **Riesgo**: Teóricamente se podría agregar una persona jurídica como disponente, lo cual no tiene sentido en la mayoría de contextos (los disponentes suelen ser personas físicas: causantes, donantes, testadores).
-   **Mejora Sugerida**: Añadir validación en `withValidator`:
    ```php
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $tramite = $this->route('tramite');
            $person = Person::find($this->person_id);

            // Validación de unicidad
            if ($tramite->disponentes()->where('person_id', $this->person_id)->exists()) {
                $v->errors()->add('person_id', 'Esta persona ya está agregada como disponente.');
            }

            // Validación de tipo de persona
            if ($person && $person->person_type === 'Jurídica') {
                $v->errors()->add('person_id', 'El disponente debe ser una persona natural, no jurídica.');
            }
        });
    }
    ```

### k. Falta de Validación de Estado del Trámite

-   **Ubicación**: `app/Http/Controllers/DisponenteTramiteController.php:69-94`
-   **Problema**: No se valida si el trámite está en un estado que permita agregar/eliminar disponentes.
-   **Riesgo**: Podría agregarse o eliminarse un disponente en un trámite que ya está `Pagado` o `Finalizado`.
-   **Mejora Sugerida**: Añadir validación en el método `store()` y `destroy()`:
    ```php
    // En store()
    if (in_array($tramite->estado, ['Pagado', 'Finalizado', 'Anulado'])) {
        return back()->withInput()
            ->with(['message' => 'No se puede agregar disponentes en un trámite ' . $tramite->estado, 'alert-type' => 'error']);
    }

    // En destroy()
    if (in_array($tramite->estado, ['Pagado', 'Finalizado', 'Anulado'])) {
        return back()
            ->with(['message' => 'No se puede quitar disponentes en un trámite ' . $tramite->estado, 'alert-type' => 'error']);
    }
    ```

### l. Riesgo de Error N+1 en Vista de Lista

-   **Ubicación**: `resources/views/admin/tramites/disponentes/list.blade.php:16`
-   **Estado**: **CORRECTO** - El controlador ya usa eager loading:
    ```php
    // DisponenteTramiteController.php:32
    $data = DisponenteTramite::with(['person'])
    ```
-   **Nota**: Sin embargo, si en el futuro se agrega más información de la persona (como municipio, ubicación), habrá que actualizar la consulta eager loading para evitar N+1 queries.

### m. Falta de Soft Deletes

-   **Ubicación**: `app/Models/DisponenteTramite.php`
-   **Problema**: El modelo no usa `SoftDeletes`.
-   **Impacto**: Si se elimina un disponente por error, no hay forma de recuperarlo.
-   **Mejora Sugerida**:
    -   Agregar el trait `SoftDeletes` al modelo:
        ```php
        use Illuminate\Database\Eloquent\SoftDeletes;

        class DisponenteTramite extends Model
        {
            use HasFactory, SoftDeletes;
            // ...
        }
        ```
    -   Crear una migración para agregar la columna `deleted_at`:
        ```php
        Schema::table('disponentes_tramite', function (Blueprint $table) {
            $table->softDeletes();
        });
        ```

### n. Inconsistencia en Validación de Persona Inactiva

-   **Ubicación**: `app/Http/Controllers/DisponenteTramiteController.php:53-55`
-   **Problema**: El método `create()` filtra personas por `status` y `estado_persona`:
    ```php
    Person::where('status', 1)
        ->where('estado_persona', 'Activo')
    ```
-   **Inconsistencia**: Solo en el formulario se filtran, pero en `StoreDisponenteTramiteRequest` se puede pasar cualquier `person_id` que exista en la tabla `people`.
-   **Riesgo**: Alguien podría manipular el formulario y enviar un `person_id` de una persona inactiva o fallecida.
-   **Mejora Sugerida**: Añadir validación en el request:
    ```php
    public function rules(): array
    {
        return [
            'person_id' => [
                'required',
                'exists:people,id',
                // Validar que la persona esté activa
                function ($attribute, $value, $fail) {
                    $person = Person::find($value);
                    if (!$person || $person->status !== 1 || $person->estado_persona !== 'Activo') {
                        $fail('La persona seleccionada no está activa.');
                    }
                },
            ],
            // ...
        ];
    }
    ```

### o. Falta de Ordenamiento por Fecha de Creación

-   **Ubicación**: `app/Http/Controllers/DisponenteTramiteController.php:35`
-   **Problema**: El método `list()` ordena por `id`:
    ```php
    ->orderBy('id')
    ```
-   **Mejora Sugerida**: Considerar ordenar por `created_at` para mostrar primero los disponentes más recientes:
    ```php
    ->orderBy('created_at', 'desc')
    ```

### p. Falta de Indización para Mejorar Rendimiento

-   **Ubicación**: `database/migrations/2025_09_22_122816_create_disponentes_tramite_table.php`
-   **Problema**: La tabla no tiene índices adicionales para mejorar el rendimiento de las búsquedas más comunes.
-   **Mejora Sugerida**: Considerar agregar índices para:
    ```php
    // Para búsquedas por tipo de disponente
    $table->index('tipo');

    // Para búsquedas de fecha de fallecimiento
    $table->index('fecha_fallecimiento');

    // Para filtrar por discapacidad
    $table->index('es_discapacitado');
    ```

### q. Falta de Validación de Fecha de Fallecimiento vs Tipo

-   **Ubicación**: `app/Http/Requests/StoreDisponenteTramiteRequest.php:20`
-   **Problema**: La fecha de fallecimiento es opcional para cualquier tipo de disponente.
-   **Mejora Sugerida**: Considerar que la fecha de fallecimiento debería ser **requerida** si el tipo es `Causante` (en sucesiones hereditarias):
    ```php
    public function rules(): array
    {
        $rules = [
            'person_id' => 'required|exists:people,id',
            'tipo' => 'required|in:Causante,Donante,Testador',
            'fecha_fallecimiento' => 'nullable|date|before_or_equal:today',
            'es_discapacitado' => 'boolean',
        ];

        // Si el tipo es Causante, la fecha de fallecimiento es requerida
        if ($this->tipo === 'Causante') {
            $rules['fecha_fallecimiento'] = 'required|date|before_or_equal:today';
        }

        return $rules;
    }
    ```

### r. Falta de Información de Auditoría en Vista

-   **Ubicación**: `resources/views/admin/tramites/disponentes/read.blade.php:76-78`
-   **Estado**: La vista de detalle muestra `Registrado el` con `created_at`, lo cual está bien.
-   **Mejora Sugerida**: Considerar agregar también:
    -   **Actualizado por**: Mostrar quién actualizó el registro (`updated_by`) si existe
    -   **Actualizado el**: Mostrar cuándo se actualizó por última vez (`updated_at`)
    Esto proporciona más contexto sobre la historia del registro.

### s. Riesgo de Excepción si Persona No Existe

-   **Ubicación**: `resources/views/admin/tramites/disponentes/list.blade.php:16-17`
-   **Problema**: La vista asume que `$item->person` siempre existe:
    ```php
    <td><strong>{{ $item->person->fullName }}</strong></td>
    ```
-   **Riesgo**: Si la persona asociada se elimina (soft delete o hard delete), la vista dará error.
-   **Mejora Sugerida**: Usar el operador nullsafe de PHP 8.0+ u `optional()`:
    ```php
    <td><strong>{{ $item->person?->fullName ?? 'Persona no disponible' }}</strong></td>
    ```

### t. Falta de Internacionalización (i18n)

-   **Ubicación**: Todas las vistas en `resources/views/admin/tramites/disponentes/*.blade.php`
-   **Problema**: Todo el texto está en español (hardcoded).
-   **Mejora Sugerida**: Considerar usar el sistema de traducción de Laravel para soporte multiidioma:
    ```php
    // En lugar de:
    <label>Persona <span class="required">*</span></label>

    // Usar:
    <label>{{ __('disponente.person') }} <span class="required">*</span></label>
    ```

### u. Resumen de Prioridades

| Item | Prioridad | Tipo | Estado Actual |
|------|----------|------|---------------|
| **g** - Campos no existentes | ALTA | Bug | Corregir ASAP |
| **c** - Índice único | ALTA | Seguridad | Pendiente |
| **i** - Editar no implementado | MEDIA | Funcionalidad | Pendiente |
| **j** - Validar tipo persona | MEDIA | Validación | Pendiente |
| **k** - Validar estado trámite | MEDIA | Lógica de negocio | Pendiente |
| **m** - Soft deletes | BAJA | Funcionalidad | Pendiente |
| **n** - Validar persona inactiva | MEDIA | Seguridad | Pendiente |
| **q** - Fecha fallecimiento req | BAJA | Lógica de negocio | Pendiente |
| **s** - Nullsafe en vistas | MEDIA | Robustez | Pendiente |
