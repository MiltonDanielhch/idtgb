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
