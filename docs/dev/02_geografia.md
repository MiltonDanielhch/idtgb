# Documentación Técnica - Módulo de División Política (Geografía)

## 📋 Tabla de Contenidos

1. [Introducción](#introducción)
2. [Base de Datos](#base-de-datos)
3. [Modelos](#modelos)
4. [Carga de Datos (Seeders)](#carga-de-datos-seeders)
5. [Integración con otros módulos](#integración-con-otros-módulos)
6. [Guía para Desarrolladores](#guía-para-desarrolladores)
7. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## 🎯 Introducción

El módulo de **División Política** gestiona la estructura jerárquica geográfica de Bolivia, compuesta por **Departamentos**, **Provincias** y **Municipios**.

A diferencia de otros módulos del sistema, este **no posee una interfaz de administración (CRUD)** para los usuarios finales, ya que se considera información estática ("Datos Maestros") que rara vez cambia. Su función es proveer catálogos normalizados para la ubicación de inmuebles, personas y la determinación de jurisdicciones tributarias.

### Propósito
- Estandarizar la ubicación geográfica en todo el sistema.
- Permitir la segmentación de tasas impositivas por Departamento.
- Vincular inmuebles y personas a un Municipio específico.

---

## 🗄️ Base de Datos

El esquema sigue una estructura jerárquica clásica de uno a muchos.

### 1. Tabla: `departamentos`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `nombre` | VARCHAR(50) | NOT NULL | Ej: "Beni", "Santa Cruz". |
| `codigo` | VARCHAR(5) | UNIQUE, NOT NULL | Código ISO o interno (ej: "BE", "SC"). |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

### 2. Tabla: `provincias`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `departamento_id` | BIGINT | FK, NOT NULL | Relación con el departamento padre. |
| `nombre` | VARCHAR(100) | NOT NULL | Ej: "Cercado", "Vaca Díez". |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

### 3. Tabla: `municipios`

| Campo | Tipo | Atributos | Descripción |
|-------|------|------------|-------------|
| `id` | BIGINT | PK, AUTO_INCREMENT | Identificador único. |
| `provincia_id` | BIGINT | FK, NOT NULL | Relación con la provincia padre. |
| `nombre` | VARCHAR(100) | NOT NULL | Ej: "Trinidad", "Riberalta". |
| `codigo` | VARCHAR(10) | NULLABLE | Código municipal (si aplica). |
| `created_at` | TIMESTAMP | | |
| `updated_at` | TIMESTAMP | | |

---

## 🧩 Modelos

### `App\Models\Departamento`

**Relaciones:**
```php
public function provincias() {
    return $this->hasMany(Provincia::class);
}

// Relación directa con municipios a través de provincias
public function municipios() {
    return $this->hasManyThrough(Municipio::class, Provincia::class);
}

// Un departamento tiene muchas tasas impositivas definidas
public function tasas() {
    return $this->hasMany(Tasa::class);
}
```

### `App\Models\Provincia`

**Relaciones:**
```php
public function departamento() {
    return $this->belongsTo(Departamento::class);
}

public function municipios() {
    return $this->hasMany(Municipio::class);
}
```

### `App\Models\Municipio`

**Relaciones:**
```php
public function provincia() {
    return $this->belongsTo(Provincia::class);
}

// Un municipio tiene muchos inmuebles registrados
public function inmuebles() {
    return $this->hasMany(Inmueble::class);
}

// Un municipio tiene muchas personas residentes
public function personas() {
    return $this->hasMany(Person::class);
}
```

---

## 🌱 Carga de Datos (Seeders)

Dado que no existe CRUD, los datos se cargan mediante Seeders durante el despliegue inicial.

**Seeder Principal:** `DatabaseSeeder` llama a los seeders específicos (ej. `DivisionPoliticaSeeder` o `IdtgbMaestrosSeeder`).

**Lógica de Carga:**
1.  Se insertan los 9 departamentos con sus códigos fijos.
2.  Se insertan las provincias vinculadas a los departamentos.
3.  Se insertan los municipios vinculados a las provincias.

> **Nota:** Es crucial que los IDs o Códigos de los departamentos (especialmente Beni) se mantengan constantes, ya que hay lógica de negocio (como la Calculadora del Beni) que depende de ellos.

---

## 🔗 Integración con otros módulos

### 1. Módulo de Inmuebles
-   **Campo:** `municipio_id` en la tabla `inmuebles`.
-   **Uso:** Determina la ubicación física del bien. Es vital para reportes estadísticos por región.

### 2. Módulo de Personas
-   **Campo:** `municipio_id` en la tabla `people`.
-   **Uso:** Registra el domicilio de la persona.

### 3. Módulo de Tasas
-   **Campo:** `departamento_id` en la tabla `tasas`.
-   **Uso Crítico:** El sistema permite definir tasas de impuesto diferentes para cada departamento. Al calcular el impuesto de un trámite, el sistema busca la tasa vigente para el departamento donde se ubica el inmueble.
    ```php
    // Ejemplo conceptual de búsqueda de tasa
    $depId = $inmueble->municipio->provincia->departamento_id;
    $tasa = Tasa::where('departamento_id', $depId)->...->first();
    ```

### 4. Calculadora Beni (`CalculadoraBeniController`)
-   Utiliza el código `BE` para identificar al departamento del Beni y aplicar reglas de negocio específicas o cargar las tasas por defecto para la simulación.

---

## 📝 Guía para Desarrolladores

### Cómo obtener listas para Selects
Al no haber controladores API específicos, se suelen cargar directamente en los métodos `create` de otros controladores:

```php
// En InmuebleController
$municipios = Municipio::with('provincia.departamento')->orderBy('nombre')->get();
```

### Agregar un nuevo Municipio
Como no hay interfaz gráfica, se debe hacer vía base de datos o creando un nuevo Seeder/Migration si es un cambio estructural permanente:

```php
// Ejemplo en Tinker o Seeder
$provincia = Provincia::where('nombre', 'Cercado')->first();
Municipio::create([
    'provincia_id' => $provincia->id,
    'nombre' => 'Nuevo Municipio',
    'codigo' => '1234'
]);
```

---

## 🚨 Análisis de Calidad y Mejoras

### 🐛 Posibles Bugs / Riesgos

1.  **Selects Pesados (Rendimiento):**
    -   **Problema:** En formularios como el de Inmuebles, se hace `Municipio::all()` o similar. Si bien Bolivia tiene ~340 municipios, cargar esto en cada petición puede ser ineficiente, especialmente si se traen relaciones anidadas sin necesidad.
    -   **Solución:** Implementar un endpoint AJAX `/ajax/geografia/municipios` que permita filtrar por provincia o departamento, implementando "Selects en Cascada" (Seleccionar Depto -> Cargar Provincias -> Cargar Municipios).

2.  **Dependencia de Códigos "Hardcoded":**
    -   **Problema:** El código contiene referencias explícitas como `where('codigo', 'BE')`. Si alguien cambia el código del departamento en la base de datos manualmente, la calculadora del Beni dejará de funcionar.
    -   **Solución:** Definir constantes en el modelo `Departamento` (ej. `const CODIGO_BENI = 'BE';`) o bloquear la edición de estos campos a nivel de base de datos.

3.  **Integridad Referencial en Seeders:**
    -   **Riesgo:** Si se vuelven a correr los seeders sin limpiar la tabla, se pueden duplicar registros si no se usa `updateOrCreate` o si no hay índices únicos en los nombres.
    -   **Ubicación:** `database/seeders/DepartamentoSeeder.php`, `database/seeders/ProvinciaSeeder.php`, `database/seeders/MunicipioSeeder.php`

### 4.  **Falta de Restricciones Unique Compuestas:**
    -   **Problema:** Las tablas `provincias` y `municipios` no tienen restricciones unique compuestas para evitar nombres duplicados dentro del mismo departamento o provincia.
    -   **Ubicación:** `database/migrations/2025_09_22_122711_create_provincias_table.php:16`, `database/migrations/2025_09_22_122715_create_municipios_table.php:16`
    -   **Riesgo:** Se pueden crear dos provincias con el mismo nombre en el mismo departamento (ej: "Cercado" en Beni y otra "Cercado" también en Beni).
    -   **Solución:** Agregar restricciones unique compuestas:
        ```php
        // En create_provincias_table.php
        $table->unique(['nombre', 'departamento_id']);
        
        // En create_municipios_table.php
        $table->unique(['nombre', 'provincia_id']);
        ```

### 5.  **Inconsistencia en Modelo Departamento:**
    -   **Problema:** La documentación menciona relaciones `municipios()` y `tasas()` en el modelo Departamento, pero estas NO existen en el código actual.
    -   **Ubicación:** `app/Models/Departamento.php:16-20` (solo tiene `provincias()`)
    -   **Solución:** Agregar las relaciones faltantes al modelo:
        ```php
        public function municipios()
        {
            return $this->hasManyThrough(Municipio::class, Provincia::class);
        }

        public function tasas()
        {
            return $this->hasMany(Tasa::class);
        }
        ```

### 6.  **Campo `codigo` en Municipios No Implementado:**
    -   **Problema:** La documentación menciona el campo `codigo` en la tabla municipios (línea 59), pero NO existe en la migración actual.
    -   **Ubicación:** `database/migrations/2025_09_22_122715_create_municipios_table.php:14-18` (solo tiene `nombre` y `provincia_id`)
    -   **Solución:** Agregar el campo en una nueva migración o actualizar la existente:
        ```php
        $table->string('codigo', 10)->nullable();
        ```

### 7.  **Ausencia de Constantes en Modelo Departamento:**
    -   **Problema:** No hay constantes definidas para los códigos de departamento (ej: `CODIGO_BENI = 'BE'`).
    -   **Ubicación:** `app/Models/Departamento.php` (líneas 8-20)
    -   **Riesgo:** Los códigos están hardcodeados en múltiples lugares del sistema (CalculadoraBeniController, TasaSeeder, etc.), lo que hace difícil mantenerlos.
    -   **Solución:** Agregar constantes al modelo:
        ```php
        const CODIGO_BENI = 'BE';
        const CODIGO_SANTA_CRUZ = 'SC';
        // ... otros códigos
        ```

### 8.  **Falta de Validación de Integridad Referencial en Modelos:**
    -   **Problema:** No hay métodos para evitar la eliminación de departamentos, provincias o municipios que tienen registros relacionados.
    -   **Ubicación:** `app/Models/Departamento.php`, `app/Models/Provincia.php`, `app/Models/Municipio.php`
    -   **Riesgo:** Si se elimina un departamento, provincia o municipio que tiene personas, inmuebles o tasas asociadas, se producirán errores de integridad.
    -   **Solución:** Implementar eventos de modelo para validar antes de eliminar:
        ```php
        protected static function boot()
        {
            parent::boot();
            static::deleting(function ($departamento) {
                if ($departamento->provincias()->exists()) {
                    throw new \Exception('No se puede eliminar: tiene provincias asociadas.');
                }
            });
        }
        ```

---

## 💡 Posibles Mejoras

### 1.  **Implementar Selects en Cascada (AJAX):**
    -   **Descripción:** En lugar de cargar todos los municipios (~340) en un solo select, implementar selects anidados que se carguen dinámicamente.
    -   **Beneficios:**
        -   Mejora el rendimiento de carga de formularios
        -   Mejora la experiencia de usuario (buscador más contextualizado)
        -   Reduce el tráfico de datos en cada petición
    -   **Implementación sugerida:**
        -   Crear endpoint AJAX: `/ajax/geografia/provincias/{departamento_id}`
        -   Crear endpoint AJAX: `/ajax/geografia/municipios/{provincia_id}`
        -   Agregar rutas en `routes/web.php`
        -   Modificar vistas `resources/views/admin/people/edit-add.blade.php:168-176` y `resources/views/admin/inmuebles/edit-add.blade.php:59-68` para usar selects en cascada con JavaScript

### 2.  **Centralizar Carga de Municipios:**
    -   **Descripción:** Crear un método estático en el modelo Municipio para cargar municipios con sus relaciones.
    -   **Ubicación actual duplicada:**
        -   `app/Http/Controllers/PersonController.php:83`
        -   `app/Http/Controllers/PersonController.php:108`
        -   `app/Http/Controllers/InmuebleController.php:57`
        -   `app/Http/Controllers/InmuebleController.php:76`
    -   **Solución:** Agregar método en `app/Models/Municipio.php`:
        ```php
        public static function getForSelect()
        {
            return self::with('provincia.departamento')->orderBy('nombre')->get();
        }
        ```

### 3.  **Implementar Caching para Listas Geográficas:**
    -   **Descripción:** Usar cache de Redis o file para almacenar listas de departamentos, provincias y municipios.
    -   **Beneficios:**
        -   Reduce consultas a la base de datos
        -   Mejora el tiempo de respuesta de formularios
    -   **Ejemplo:** (Ya implementado en `CalculadoraBeniController.php:21-27` para parentescos y tipos de transmisión)
    -   **Solución:** Aplicar mismo patrón a municipios:
        ```php
        $municipios = Cache::remember('municipios.all', 3600, function () {
            return Municipio::with('provincia.departamento')->orderBy('nombre')->get();
        });
        ```

### 4.  **Agregar Búsqueda AJAX en Selects de Municipios:**
    -   **Descripción:** Implementar búsqueda en tiempo real en los selects de municipios usando Select2 con AJAX.
    -   **Beneficios:**
        -   Mejora UX cuando hay muchas opciones (~340 municipios)
        -   Filtra resultados mientras el usuario escribe
    -   **Ubicación:** `resources/views/admin/people/edit-add.blade.php:168`, `resources/views/admin/inmuebles/edit-add.blade.php:59`
    -   **Implementación:** Usar la librería Select2 con configuración AJAX.

### 5.  **Agregar Accesores y Scopes Útiles:**
    -   **Descripción:** Agregar métodos helper para consultas frecuentes.
    -   **Ubicación:** `app/Models/Departamento.php`, `app/Models/Provincia.php`, `app/Models/Municipio.php`
    -   **Sugerencias:**
        ```php
        // En Municipio
        public function scopeByDepartamento($query, $departamentoId)
        {
            return $query->whereHas('provincia', fn($q) => $q->where('departamento_id', $departamentoId));
        }

        public function scopeByProvincia($query, $provinciaId)
        {
            return $query->where('provincia_id', $provinciaId);
        }

        // Accesor para nombre completo con jerarquía
        public function getNombreCompletoAttribute()
        {
            return "{$this->nombre} ({$this->provincia->nombre}, {$this->provincia->departamento->nombre})";
        }
        ```

### 6.  **Crear Controlador API para Geografía:**
    -   **Descripción:** Crear un controlador dedicado para servir datos geográficos vía API/AJAX.
    -   **Beneficios:**
        -   Centraliza la lógica de carga de datos geográficos
        -   Facilita la implementación de selects en cascada
        -   Permite reutilizar el código en diferentes partes del sistema
    -   **Ubicación sugerida:** `app/Http/Controllers/GeografiaController.php`
    -   **Métodos sugeridos:**
        ```php
        public function getDepartamentos() { return Departamento::all(); }
        public function getProvincias($departamentoId) { ... }
        public function getMunicipios($provinciaId) { ... }
        public function searchMunicipios(Request $request) { ... }
        ```

### 7.  **Implementar Validación de Datos en Seeders:**
    -   **Descripción:** Mejorar los seeders para validar que los datos existan antes de relacionarlos.
    -   **Ubicación:** `database/seeders/PeopleBeniSeeder.php:15-17`, `database/seeders/PeopleBeniSeeder.php:62-63`, `database/seeders/PeopleBeniSeeder.php:133-134`
    -   **Problema:** Si un municipio no existe, el seeder usa un fallback (municipioTrinidad) pero podría pasar desapercibido.
    -   **Solución:** Agregar logging de advertencias cuando se usa el fallback:
        ```php
        if (!$municipioModel) {
            Log::warning("Municipio no encontrado: {$nombreMunicipio}, usando fallback");
        }
        ```

---

## 📉 Optimizaciones

### 1.  **Optimizar Consultas de Municipios en Formularios:**
    -   **Problema:** Se cargan todos los municipios con relaciones anidadas en cada petición de create/edit.
    -   **Ubicación:**
        -   `app/Http/Controllers/PersonController.php:83` - `Municipio::with('provincia.departamento')->get()`
        -   `app/Http/Controllers/PersonController.php:108` - `Municipio::with('provincia.departamento')->get()`
        -   `app/Http/Controllers/InmuebleController.php:57` - `Municipio::with('provincia.departamento')->orderBy('nombre')->get()`
        -   `app/Http/Controllers/InmuebleController.php:76` - `Municipio::with('provincia.departamento')->orderBy('nombre')->get()`
    -   **Impacto:** ~340 municipios x 3 relaciones = ~1,020 objetos cargados en memoria por petición
    -   **Soluciones:**
        -   Implementar caching (ver Mejora #3)
        -   Implementar selects en cascada con AJAX (ver Mejora #1)
        -   Usar `lazy()` o `lazyById()` para carga diferida si es necesario

### 2.  **Agregar Índices en Campos de Búsqueda Frecuentes:**
    -   **Descripción:** Agregar índices en campos que se usan frecuentemente en WHERE.
    -   **Ubicación:**
        -   `database/migrations/2025_09_22_122711_create_provincias_table.php:16` - campo `nombre`
        -   `database/migrations/2025_09_22_122715_create_municipios_table.php:16` - campo `nombre`
    -   **Solución:** Agregar índices en migraciones:
        ```php
        $table->index('nombre'); // En ambas tablas
        ```

### 3.  **Optimizar Accesor `ubicacion_completa` en Modelo Person:**
    -   **Ubicación:** `app/Models/Person.php:149-166`
    -   **Problema:** El accesor verifica manualmente si las relaciones existen, pero no tiene protección contra excepciones.
    -   **Solución actual:** Ya existe `ubicacion_segura` (líneas 171-178) que maneja excepciones.
    -   **Optimización:** Usar siempre `ubicacion_segura` en lugar de `ubicacion_completa` en vistas que podrían no tener las relaciones cargadas.

### 4.  **Usar `selectOnly` en Consultas de Municipios:**
    -   **Descripción:** Cuando se cargan municipios para selects, no se necesitan todos los campos.
    -   **Ubicación:** `app/Http/Controllers/PersonController.php:83`, `app/Http/Controllers/InmuebleController.php:57`
    -   **Optimización:**
        ```php
        Municipio::select('id', 'nombre', 'provincia_id')
            ->with(['provincia:id,nombre,departamento_id', 'provincia.departamento:id,nombre,codigo'])
            ->orderBy('nombre')
            ->get();
        ```

---

## 🚧 Faltan Cosas / Funcionalidades Faltantes

### 1.  **CRUD para Administración de Geografía:**
    -   **Descripción:** No hay interfaz para gestionar departamentos, provincias y municipios desde el panel de administración.
    -   **Evidencia:** En `database/seeders/IdtgbMenuAppendSeeder.php:26-28` las rutas están comentadas:
        ```php
        // ['title' => 'Departamentos',         'route' => 'admin.departamentos.index',       ...],
        // ['title' => 'Provincias',            'route' => 'admin.provincias.index',          ...],
        // ['title' => 'Municipios',            'route' => 'admin.municipios.index',          ...],
        ```
    -   **Qué falta:**
        -   Controladores para Departamento, Provincia y Municipio
        -   Vistas de CRUD (index, create, edit)
        -   Rutas en `routes/web.php`
        -   FormRequests de validación

### 2.  **Sistema de Importación/Exportación de Datos Geográficos:**
    -   **Descripción:** No hay forma de importar datos geográficos desde archivos externos (CSV, Excel, JSON).
    -   **Beneficios:**
        -   Facilita actualizaciones periódicas de la división política
        -   Permite mantener el sistema actualizado con cambios oficiales del INE o autoridades competentes
    -   **Implementación sugerida:**
        -   Comando de Artisan: `php artisan geografia:import archivo.csv`
        -   Controlador para importación vía UI admin
        -   Formato estándar para importación

### 3.  **Endpoints API para Geografía:**
    -   **Descripción:** No hay endpoints dedicados para consultar datos geográficos vía API.
    -   **Beneficios:**
        -   Permite integración con sistemas externos
        -   Facilita implementación de selects en cascada
        -   Útil para aplicaciones móviles o frontend separado
    -   **Implementación sugerida:**
        -   Crear `GeografiaController` con métodos:
            -   `GET /api/departamentos`
            -   `GET /api/departamentos/{id}/provincias`
            -   `GET /api/provincias/{id}/municipios`
            -   `GET /api/municipios/search?q={termino}`

### 4.  **Validación de Coherencia en Datos:**
    -   **Descripción:** No hay comandos o jobs que verifiquen la integridad de los datos geográficos.
    -   **Beneficios:**
        -   Detectar provincias sin departamento
        -   Detectar municipios sin provincia
        -   Detectar personas o inmuebles con municipio_id inválido
    -   **Implementación sugerida:**
        -   Comando de Artisan: `php artisan geografia:check-integrity`
        -   Job programado para verificación periódica

### 5.  **Normalización de Nombres:**
    -   **Descripción:** No hay proceso para normalizar los nombres geográficos (acentos, mayúsculas, espacios).
    -   **Beneficios:**
        -   Consistencia en datos
        -   Mejora búsquedas
        -   Evita duplicados por variaciones de mayúsculas/acentos
    -   **Implementación sugerida:**
        -   Mutator en modelos para guardar nombres en formato normalizado
        -   Comando para normalizar datos existentes

### 6.  **Historial de Cambios en Geografía:**
    -   **Descripción:** No hay registro de cambios en datos geográficos (quién modificó, cuándo, qué cambió).
    -   **Beneficios:**
        -   Auditoría de cambios
        -   Posibilidad de revertir cambios
        -   Trazabilidad de modificaciones
    -   **Implementación sugerida:**
        -   Usar paquete como `spatie/laravel-activitylog`
        -   Agregar trait `LogsActivity` a modelos Departamento, Provincia, Municipio

### 7.  **Métodos de Conveniencia en Modelos:**
    -   **Descripción:** Los modelos no tienen métodos helper para operaciones comunes.
    -   **Ubicación:** `app/Models/Departamento.php`, `app/Models/Provincia.php`, `app/Models/Municipio.php`
    -   **Faltan:**
        -   `Departamento::findByCodigo('BE')`
        -   `Municipio::findByNombre('Trinidad')`
        -   `Provincia::findByDepartamentoAndNombre($depId, 'Cercado')`
        -   Métodos scope para filtros comunes

### 8.  **Relaciones Inversas en Modelo Municipio:**
    -   **Descripción:** El modelo Municipio no tiene las relaciones inversas mencionadas en la documentación.
    -   **Ubicación:** `app/Models/Municipio.php:16-19` (solo tiene `provincia()`)
    -   **Faltan según documentación (líneas 107-115):**
        ```php
        public function inmuebles()
        {
            return $this->hasMany(Inmueble::class);
        }

        public function personas()
        {
            return $this->hasMany(Person::class);
        }
        ```

---

## 🔍 Observaciones Adicionales

### 1.  **Consistencia de Nomenclatura:**
    -   Las migraciones usan `string('nombre', 80)` para provincias y municipios (líneas 16 de cada migración), pero la documentación menciona `VARCHAR(100)` para municipios.
    -   **Ubicación:** Documentación línea 58 vs `database/migrations/2025_09_22_122715_create_municipios_table.php:16`
    -   **Recomendación:** Alinear la documentación con el código o viceversa.

### 2.  **Uso Inconsistente de Hardcoded 'BE':**
    -   El código 'BE' está hardcodeado en múltiples lugares:
        -   `app/Http/Controllers/CalculadoraBeniController.php:59,92`
        -   `app/Http/Controllers/Admin/TramiteWizardController.php:166`
        -   `database/seeders/TasaSeeder.php:23`
    -   Aunque `CalculadoraBeniController.php` tiene la constante `CODIGO_BENI = 'BE'` (línea 16), el modelo Departamento no tiene esta constante definida.
    -   **Recomendación:** Mover constantes de códigos al modelo Departamento y usarlas en todos los controladores.

### 3.  **Comentarios en Código Indicando Mejoras Futuras:**
    -   En `CalculadoraBeniController.php` hay comentarios que indican mejoras ya implementadas:
        -   Línea 20: "// Implementación de Caching para listas (Mejora #3)"
        -   Línea 43: "// Mejora #4: Validación fecha futura"
        -   Línea 51: "// Mejora #2: Logging de consultas"
        -   Línea 58: "// Mejora #3: Uso de constante para evitar hardcoding"
    -   **Recomendación:** Actualizar estos comentarios para reflejar el estado actual del código o eliminarlos si ya están completados.

### 4.  **Selección de Municipios en Vistas:**
    -   En `resources/views/admin/people/edit-add.blade.php:173` se muestra el municipio con formato: `{{ $m->nombre }} - {{ $m->provincia->nombre }} - {{ $m->provincia->departamento->nombre }}`
    -   En `resources/views/admin/inmuebles/edit-add.blade.php:64` se muestra diferente: `{{ $m->nombre }} ({{ $m->provincia->departamento->codigo }})`
    -   **Recomendación:** Estandarizar el formato de display usando un accesor en el modelo Municipio.

---

## 📊 Resumen de Problemas por Severidad

| Severidad | Problema | Ubicación |
|-----------|----------|-----------|
| **CRÍTICO** | Falta de restricciones unique compuestas | `database/migrations/2025_09_22_122711_create_provincias_table.php`, `2025_09_22_122715_create_municipios_table.php` |
| **CRÍTICO** | Relaciones faltantes en modelos (pueden causar errores) | `app/Models/Departamento.php`, `app/Models/Municipio.php` |
| **ALTO** | Hardcoded 'BE' en múltiples lugares | `CalculadoraBeniController.php`, `TasaSeeder.php`, `TramiteWizardController.php` |
| **ALTO** | Consultas ineficientes de municipios | `PersonController.php:83,108`, `InmuebleController.php:57,76` |
| **MEDIO** | Campo `codigo` en municipios no implementado | `database/migrations/2025_09_22_122715_create_municipios_table.php` |
| **MEDIO** | No hay CRUD para administración de geografía | `database/seeders/IdtgbMenuAppendSeeder.php:26-28` |
| **BAJO** | Falta de constantes en modelo Departamento | `app/Models/Departamento.php` |
| **BAJO** | Formatos inconsistentes en vistas | `resources/views/admin/people/edit-add.blade.php:173`, `resources/views/admin/inmuebles/edit-add.blade.php:64` |

---

## 🛠️ Prioridad de Implementación Sugerida

### 1.  **CRÍTICO (Implementar inmediatamente):**
    -   Agregar restricciones unique compuestas en migraciones (nueva migración)
    -   Completar relaciones faltantes en modelos
    -   Agregar constantes de códigos en modelo Departamento

### 2.  **ALTO (Implementar pronto):**
    -   Implementar selects en cascada con AJAX
    -   Optimizar consultas de municipios (caching o carga diferida)
    -   Centralizar carga de municipios en un método del modelo

### 3.  **MEDIO (Implementar en siguiente sprint):**
    -   Implementar CRUD para geografía
    -   Agregar endpoints API para geografía
    -   Implementar sistema de importación/exportación

### 4.  **BAJO (Implementar cuando sea posible):**
    -   Agregar historial de cambios
    -   Implementar comandos de verificación de integridad
    -   Normalizar nombres geográficos