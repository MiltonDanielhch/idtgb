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

**Restricciones Únicas Compuestas:**
- `provincias`: Unique compuesto en `(nombre, departamento_id)` - Evita nombres duplicados en el mismo departamento.
- `municipios`: Unique compuesto en `(nombre, provincia_id)` - Evita nombres duplicados en la misma provincia.

---

## 🧩 Modelos

### `App\Models\Departamento`

**Constantes de Códigos:**
```php
const CODIGO_BENI = 'BE';
const CODIGO_SANTA_CRUZ = 'SC';
const CODIGO_LA_PAZ = 'LP';
const CODIGO_COCHABAMBA = 'CB';
const CODIGO_ORURO = 'OR';
const CODIGO_POTOSI = 'PT';
const CODIGO_TARIJA = 'TJ';
const CODIGO_CHUQUISACA = 'CH';
const CODIGO_PANDO = 'PA';
```

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

**Validación de Integridad Referencial:**
```php
protected static function boot()
{
    parent::boot();
    static::deleting(function ($departamento) {
        if ($departamento->provincias()->exists()) {
            throw new \Exception('No se puede eliminar el departamento: tiene provincias asociadas.');
        }
        if ($departamento->tasas()->exists()) {
            throw new \Exception('No se puede eliminar el departamento: tiene tasas asociadas.');
        }
    });
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

**Validación de Integridad Referencial:**
```php
protected static function boot()
{
    parent::boot();
    static::deleting(function ($provincia) {
        if ($provincia->municipios()->exists()) {
            throw new \Exception('No se puede eliminar la provincia: tiene municipios asociados.');
        }
    });
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

**Métodos de Carga Optimizada:**
```php
// Método para obtener municipios con relaciones cargadas para selects
public static function getForSelect()
{
    return self::with('provincia.departamento')->orderBy('nombre')->get();
}

// Método para obtener municipios con cache
public static function getCachedForSelect($cacheTime = 3600)
{
    return Cache::remember('municipios.all', $cacheTime, function () {
        return self::getForSelect();
    });
}
```

**Validación de Integridad Referencial:**
```php
protected static function boot()
{
    parent::boot();
    static::deleting(function ($municipio) {
        if ($municipio->inmuebles()->exists()) {
            throw new \Exception('No se puede eliminar el municipio: tiene inmuebles asociados.');
        }
        if ($municipio->personas()->exists()) {
            throw new \Exception('No se puede eliminar el municipio: tiene personas asociadas.');
        }
    });
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

**Validación en Seeders:**
- En `PeopleBeniSeeder.php` se agrega logging de advertencias cuando un municipio no existe y se usa el fallback.
- Se usa `updateOrCreate` para evitar duplicados al correr seeders múltiples veces.

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
-   Utiliza el código `Departamento::CODIGO_BENI` para identificar al departamento del Beni y aplicar reglas de negocio específicas o cargar las tasas por defecto para la simulación.

---

## 📝 Guía para Desarrolladores

### Cómo obtener listas para Selects
Al no haber controladores API específicos, se suelen cargar directamente en los métodos `create` de otros controladores:

```php
// Método recomendado: usando el método estático del modelo
$municipios = Municipio::getForSelect();

// O con cache para mejor rendimiento
$municipios = Municipio::getCachedForSelect();
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

### Puntos Clave a Recordar
-   **Constantes de Códigos:** Usar `Departamento::CODIGO_BENI` y otras constantes en lugar de hardcodear códigos.
-   **Validación de Integridad:** Los modelos tienen métodos `boot()` que protegen contra eliminación de registros con dependencias.
-   **Métodos Optimizados:** Usar `getForSelect()` o `getCachedForSelect()` para cargar municipios en formularios.
-   **Restricciones Únicas:** Las tablas tienen restricciones unique compuestas para evitar duplicados.

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v3.0.0 (20 de enero de 2026) ✅

Todos los bugs identificados en el análisis original han sido corregidos exitosamente. El módulo de División Política ahora cuenta con:

- ✅ Constantes de códigos de departamentos en el modelo
- ✅ Restricciones unique compuestas en provincias y municipios
- ✅ Validación de integridad referencial en modelos
- ✅ Métodos optimizados para carga de municipios (`getForSelect()`, `getCachedForSelect()`)
- ✅ Campo `codigo` agregado en municipios
- ✅ Logging de advertencias en seeders
- ✅ Reemplazo de hardcoded 'BE' con constantes

### 🐛 Bugs Corregidos (8/8) ✅

| # | Bug | Estado | Ubicación |
|---|-----|--------|-----------|
| 1 | Optimización de selects pesados | ✅ Corregido | `Municipio.php:getForSelect()`, `getCachedForSelect()` |
| 2 | Eliminar hardcoded 'BE' | ✅ Corregido | `Departamento.php`, múltiples archivos |
| 3 | Validación de integridad en seeders | ✅ Corregido | `PeopleBeniSeeder.php` (logging) |
| 4 | Restricciones unique compuestas | ✅ Corregido | Migración `2026_01_19_194859_add_unique_composite_...` |
| 5 | Relaciones faltantes en modelos | ✅ Corregido | `Departamento.php`, `Municipio.php` |
| 6 | Campo `codigo` en municipios | ✅ Corregido | Migración `2026_01_20_123456_add_codigo_to_municipios_...` |
| 7 | Constantes de códigos | ✅ Corregido | `Departamento.php` |
| 8 | Validación de integridad referencial | ✅ Corregido | `boot()` en Departamentos, Provincias, Municipios |

### 🚀 Mejoras Implementadas ✅

- ✅ **Constantes de Códigos:** Definidas en modelo `Departamento` para evitar hardcoding en todo el sistema.
- ✅ **Restricciones Únicas Compuestas:** Evitan duplicados en nombres de provincias y municipios dentro de su jerarquía.
- ✅ **Validación de Integridad Referencial:** Métodos `boot()` en modelos que protegen contra eliminación de registros con dependencias.
- ✅ **Métodos Optimizados:** `getForSelect()` y `getCachedForSelect()` para carga eficiente de municipios en formularios.
- ✅ **Campo `codigo` en Municipios:** Agregado para compatibilidad con sistemas externos.
- ✅ **Logging en Seeders:** Advertencias registradas cuando se usan fallbacks.
- ✅ **Reemplazo de Hardcoded 'BE':** Reemplazado con `Departamento::CODIGO_BENI` en todo el código.

### 📋 Mejoras Futuras Sugeridas

| Prioridad | Mejora | Descripción |
|-----------|--------|-------------|
| **MEDIO** | CRUD para Geografía | Crear interfaz para gestionar departamentos, provincias y municipios desde el panel admin |
| **MEDIO** | Endpoints API | Crear `GeografiaController` con endpoints para selects en cascada |
| **MEDIO** | Importación/Exportación | Sistema para importar datos geográficos desde CSV/Excel |
| **BAJO** | Historial de Cambios | Implementar auditoría de cambios en datos geográficos |
| **BAJO** | Verificación Integridad | Comando para verificar coherencia de datos geográficos |
| **BAJO** | Normalización de Nombres | Proceso para normalizar nombres (acentos, mayúsculas) |
| **BAJO** | Métodos Helper | Agregar `findByCodigo()`, `findByNombre()`, etc. |

### 📝 Historial de Cambios

### v3.0.0 (20 de enero de 2026)
**Correcciones Completadas (8/8):**
- ✅ Bug #1: Optimización de selects pesados - Implementados `getForSelect()` y `getCachedForSelect()` en modelo Municipio
- ✅ Bug #2: Eliminar hardcoded 'BE' - Reemplazados con `Departamento::CODIGO_BENI` en todos los archivos
- ✅ Bug #3: Validación de integridad en seeders - Agregado logging de advertencias en `PeopleBeniSeeder.php`
- ✅ Bug #4: Restricciones unique compuestas - Implementadas en provincias y municipios
- ✅ Bug #5: Relaciones faltantes en modelos - Agregadas `municipios()` y `tasas()` en Departamento, `inmuebles()` y `personas()` en Municipio
- ✅ Bug #6: Campo `codigo` en municipios - Agregado en migración `2026_01_20_123456_add_codigo_to_municipios_table.php`
- ✅ Bug #7: Constantes de códigos - Agregadas en modelo Departamento
- ✅ Bug #8: Validación de integridad referencial - Implementados métodos `boot()` en Departamento, Provincia y Municipio

**Archivos Modificados:**
- `database/migrations/2026_01_19_194859_add_unique_composite_to_provincias_and_municipios_tables.php` - Nueva migración unique compuestas
- `database/migrations/2026_01_20_123456_add_codigo_to_municipios_table.php` - Nueva migración campo codigo
- `app/Models/Departamento.php` - Agregadas constantes, relaciones faltantes y validación en `boot()`
- `app/Models/Provincia.php` - Agregada validación en `boot()`
- `app/Models/Municipio.php` - Agregado `codigo` a fillable, métodos `getForSelect()`/`getCachedForSelect()`, relaciones faltantes y validación en `boot()`
- `database/seeders/ProvinciaSeeder.php` - Reemplazado hardcoded 'BE' con constante
- `tests/Unit/Unit/IdtgbCalculatorTest.php` - Reemplazado hardcoded 'BE' con constante
- `tests/Feature/PublicCalculatorTest.php` - Reemplazado hardcoded 'BE' con constante
- `database/seeders/PeopleBeniSeeder.php` - Agregado logging de advertencias
