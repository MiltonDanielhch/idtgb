# Documentación Técnica - ValidacionController

## 📋 Tabla de Contenidos

1. [Descripción General](#descripción-general)
2. [Ubicación](#ubicación)
3. [Rutas](#rutas)
4. [Dependencias](#dependencias)
5. [Métodos](#métodos)
6. [Hash de Validación](#hash-de-validación)
7. [Uso del Hash](#uso-del-hash)
8. [Vista de Validación](#vista-de-validación)
9. [Seguridad](#seguridad)
10. [Ejemplo de Uso](#ejemplo-de-uso)
11. [Relaciones del Modelo Tramite Utilizadas](#relaciones-del-modelo-tramite-utilizadas)
12. [Estados de Trámite](#estados-de-trámite)
13. [Políticas de Autorización](#políticas-de-autorización)
14. [Testing](#testing)
15. [Referencias Relacionadas](#referencias-relacionadas)
16. [Análisis de Calidad y Mejoras](#análisis-de-calidad-y-mejoras)

---

## Descripción General

El `ValidacionController` es un controlador público que permite la verificación de trámites mediante un hash único de validación. Este controlador no requiere autenticación y está diseñado para que ciudadanos externos puedan verificar la autenticidad de los documentos de trámites generados por el sistema.

## Ubicación

- **Archivo:** `app/Http/Controllers/ValidacionController.php`
- **Namespace:** `App\Http\Controllers`
- **Vista asociada:** `resources/views/validacion/show.blade.php`

## Rutas

| Método HTTP | Ruta | Nombre de ruta | Descripción |
|-------------|------|----------------|-------------|
| GET | `/validar/{hash}` | `tramite.validar` | Muestra la información de validación de un trámite |

**Definición en `routes/web.php:62`:**
```php
Route::get('/validar/{hash}', [ValidacionController::class, 'show'])->name('tramite.validar');
```

## Dependencias

- **Modelo:** `App\Models\Tramite`

## Métodos

### `show(string $hash)`

Muestra la información de un trámite a partir de su hash de validación.

**Ubicación:** `app/Http/Controllers/ValidacionController.php:15-35`

#### Parámetros
- `$hash` (string): El hash de validación SHA256 de 64 caracteres hexadecimales

#### Retorno
- `Illuminate\View\View`: Vista con la información del trámite
- `Illuminate\Http\Response`: 404 si el hash es inválido o el trámite no está disponible

#### Lógica
1. **Validación del hash** (Bug #2): Verifica que tenga exactamente 64 caracteres y sea hexadecimal
2. **Consulta optimizada** (Bug #5): Usa el scope `whereHashValidacion()` con eager loading
3. **Validación de estado** (Bug #4): Rechaza trámites en estados `Borrador` o `Anulado`
4. Si no existe o no es válido, lanza excepción 404
5. Retorna la vista `validacion.show` con el trámite cargado

```php
public function show(string $hash)
{
    // Bug #2: Validar longitud exacta de hash SHA256 (64 caracteres hexadecimales)
    if (strlen($hash) !== 64 || !ctype_xdigit($hash)) {
        abort(404, 'Hash de validación inválido');
    }

    // Bug #5: Usar scope whereHashValidacion para reutilización y eager loading
    $tramite = Tramite::whereHashValidacion($hash)->firstOrFail();

    // Bug #4: Validar que el trámite no esté en estados no válidos para verificación
    if (in_array($tramite->estado, ['Borrador', 'Anulado'])) {
        abort(404, 'El trámite no está disponible para validación');
    }

    return view('validacion.show', compact('tramite'));
}
```

## Hash de Validación

### Generación

El hash de validación se genera automáticamente mediante el método `generateHashValidacion()` en el modelo `Tramite` (`app/Models/Tramite.php:158-168`):

```php
public function generateHashValidacion(): string
{
    if ($this->hash_validacion) {
        return $this->hash_validacion;
    }

    $this->hash_validacion = hash('sha256',
        $this->id . '|' .
        $this->nro_tramite . '|' .
        now()->timestamp . '|' .
        Str::random(10)
    );
    $this->save();

    return $this->hash_validacion;
}
```

**Componentes del hash:**
- ID del trámite
- Número de trámite
- Timestamp actual
- String aleatorio de 10 caracteres
- Algoritmo: SHA256 (64 caracteres hexadecimales)

### Estructura en Base de Datos

**Migración:** `database/migrations/2025_09_22_122758_create_tramites_table.php:27`

```php
$table->char('hash_validacion', 64)->unique()->nullable();
```

**Atributo fillable en modelo:** `app/Models/Tramite.php:32`

```php
protected $fillable = [
    // ...
    'hash_validacion',
];
```

**Casteo automático:** `app/Models/Tramite.php:45`

```php
protected $casts = [
    // ...
    'hash_validacion' => 'string',
];
```

## Uso del Hash

### Generación en PDF

El hash se genera automáticamente al crear el PDF del Formulario A01 (`app/Http/Controllers/TramiteController.php:121`):

```php
public function a01(Tramite $tramite)
{
    // Genera el hash de validación si no existe
    $hash = $tramite->hash_validacion ?: $tramite->generateHashValidacion();

    // Genera la URL de validación y el código QR
    $validation_url = route('tramite.validar', ['hash' => $hash]);
    $qr = base64_encode(QrCode::format('svg')->size(200)->generate($validation_url));

    // ...
}
```

### Invalidación Automática

Si se modifican campos críticos de un trámite que ya tiene hash, este se invalida automáticamente (`app/Observers/TramiteObserver.php:16-31`):

```php
public function saving(Tramite $tramite): void
{
    if ($tramite->exists && !empty($tramite->hash_validacion)) {
        $camposCriticos = [
            'base_imponible',
            'fecha_transmision',
            'tipo_transmision_id',
        ];

        if ($tramite->isDirty($camposCriticos)) {
            $tramite->hash_validacion = null;
        }
    }
}
```

**Nota:** El campo `tipo_contribuyente` fue eliminado del array `$camposCriticos` en v1.1.0 porque no existe en la tabla `tramites` (Bug #1 corregido).

## Vista de Validación

**Archivo:** `resources/views/validacion/show.blade.php`

### Características
- Diseño responsivo con Bootstrap 5.3.2
- Logo de la Gobernación del Beni
- Alerta verde indicando validación exitosa
- Información del trámite:
  - Número de trámite
  - Fecha de presentación
  - Tipo de transmisión
  - Adquirente principal
  - Monto del impuesto
  - Estado actual (con badge de color)
- Footer con fecha de generación

### Variables Disponibles en la Vista
- `$tramite`: Instancia del modelo `Tramite` con relaciones cargadas vía eager loading

### Manejo de Relaciones Nulas (Bug #3 Corregido)

La vista utiliza el helper `optional()` de Laravel para manejar casos donde las relaciones pueden ser nulas:

```php
{{-- Antes (propenso a error 500) --}}
{{ $tramite->tipoTransmision->nombre }}

{{-- Después (seguro) --}}
{{ optional($tramite->tipoTransmision)->nombre ?? 'No especificado' }}
```

Esto evita errores 500 si el tipo de transmisión no está definido para el trámite.

## Seguridad

### Características de Seguridad
1. **Hash único:** Cada trámite tiene un hash único de 64 caracteres
2. **Índice único:** La base de datos garantiza que no haya hashes duplicados
3. **Invalidación automática:** Cambios en datos críticos invalidan el hash
4. **Sin autenticación requerida:** La validación es pública por diseño
5. **Validación de modelo:** `firstOrFail()` retorna 404 si el hash no existe

### Consideraciones de Seguridad
- El hash contiene información sensible (ID y nro_tramite) pero está encriptada con SHA256
- El timestamp y string aleatorio previenen colisiones de hash
- No se expone información adicional del trámite más allá de lo necesario para verificación

## Ejemplo de Uso

### URL de Validación
```
https://dominio.com/validar/a1b2c3d4e5f6... (64 caracteres hexadecimales)
```

### Generar URL en Código
```php
$hash = $tramite->generateHashValidacion();
$url = route('tramite.validar', ['hash' => $hash]);
```

### Generar Código QR
```php
use SimpleSoftwareIO\QrCode\Facades\QrCode;

$hash = $tramite->generateHashValidacion();
$url = route('tramite.validar', ['hash' => $hash]);
$qr = QrCode::format('svg')->size(200)->generate($url);
```

## Relaciones del Modelo Tramite Utilizadas

En la vista de validación se accede a las siguientes relaciones:

- `tipoTransmision` → Tipo de transmisión del trámite
- `adquirentes` → Lista de adquirentes del trámite
- `adquirentes.person` → Persona del adquirente
- `adquirentes.person.display_name` o `full_name` → Nombre de la persona

### Scope `whereHashValidacion()`

El modelo `Tramite` incluye un scope reutilizable para consultar por hash de validación (Bug #5 corregido):

```php
// app/Models/Tramite.php
public function scopeWhereHashValidacion($query, string $hash)
{
    return $query->where('hash_validacion', $hash)
                 ->with(['tipoTransmision', 'adquirentes.person']);
}
```

**Ventajas:**
- Reutilización de código
- Eager loading automático de relaciones necesarias
- Reducción de consultas N+1
- Facilita mantenimiento y testing

## Estados de Trámite

Los posibles estados mostrados en la validación son:

- **Borrador** (gris)
- **Pagado** (verde)
- **Observado** (amarillo)
- **Anulado** (rojo)
- **Finalizado** (azul)

## Políticas de Autorización

Este controlador **NO** utiliza policies de autorización ya que es una ruta pública destinada a verificación externa de documentos. No requiere autenticación ni permisos específicos.

## Testing

### Casos de Prueba Recomendados

1. **Validación exitosa:** Verificar trámite con hash válido
2. **Hash inválido:** Intentar validar con hash inexistente (debe retornar 404)
3. **Hash vacío:** Intentar validar con string vacío
4. **Vista renderizada:** Verificar que la vista muestra correctamente los datos del trámite
5. **Colores de estado:** Verificar que cada estado tiene el color correcto en la UI

## Referencias Relacionadas

- **Modelo Tramite:** `app/Models/Tramite.php`
- **Controlador Tramite:** `app/Http/Controllers/TramiteController.php` (método `a01`)
- **Observer Tramite:** `app/Observers/TramiteObserver.php` (invalidación de hash)
- **Migración:** `database/migrations/2025_09_22_122758_create_tramites_table.php`
- **Vista PDF:** `resources/views/admin/tramites/pdf/a01.blade.php`
- **Rutas:** `routes/web.php:62`
- **Documentación adicional:** `docs/dev/documentos.md:828`

---

## 🚨 Análisis de Calidad y Mejoras

### Estado Actual - v1.1.0 (19 de febrero de 2026) ✅

El módulo `ValidacionController` está **funcional y listo para producción**. Todos los bugs críticos identificados han sido corregidos:

- ✅ Validación de longitud exacta de hash SHA256 (64 caracteres hexadecimales)
- ✅ Validación de formato hexadecimal del hash
- ✅ Uso de scope reutilizable `whereHashValidacion()` con eager loading
- ✅ Validación de estado del trámite (rechaza Borrador/Anulado)
- ✅ Manejo seguro de relaciones nulas en la vista
- ✅ Eliminado campo inexistente del observer de invalidación
- ✅ Consulta optimizada con eager loading de relaciones necesarias

### 🐛 Bugs Corregidos (5/5) ✅

| # | Bug | Severidad | Estado | Ubicación | Solución Aplicada |
|---|-----|-----------|--------|-----------|-------------------|
| 1 | Campo `tipo_contribuyente` inexistente en Observer | Alta | ✅ Corregido | `TramiteObserver.php:21-26` | Eliminado `'tipo_contribuyente'` del array `$camposCriticos`. El campo no existe en la tabla `tramites`. |
| 2 | Sin validación de longitud de hash (64 caracteres) | Media | ✅ Corregido | `ValidacionController.php:15-26` | Agregada validación `strlen($hash) !== 64 \|\| !ctype_xdigit($hash)` antes de consultar la BD. |
| 3 | Posible error 500 si no existe relación tipoTransmision | Media | ✅ Corregido | `validacion/show.blade.php:54` | Usado `optional($tramite->tipoTransmision)->nombre ?? 'No especificado'` para manejar relaciones nulas. |
| 4 | No se validan trámites en estado específico | Baja | ✅ Corregido | `ValidacionController.php:28-30` | Agregada validación para rechazar trámites en estados `'Borrador'` o `'Anulado'`. |
| 5 | No se usa scope `whereHashValidacion()` para reutilización | Baja | ✅ Corregido | `Tramite.php:176-187` y `ValidacionController.php:26` | Creado scope `scopeWhereHashValidacion()` con eager loading de relaciones necesarias. |

### 🚀 Mejoras Sugeridas (10)

| # | Mejora | Prioridad | Estado | Ubicación |
|---|--------|-----------|--------|-----------|
| 1 | Rate limiting para prevenir fuerza bruta | Alta | ⏳ Pendiente | `routes/web.php:62` |
| 2 | Endpoint API JSON para integración | Media | ⏳ Pendiente | `routes/api.php` (nuevo) |
| 3 | Logging de intentos de validación | Media | ⏳ Pendiente | `ValidacionController.php` |
| 4 | Metadatos SEO y Open Graph | Baja | ⏳ Pendiente | `validacion/show.blade.php:1-6` |
| 5 | Indicador visual de fecha de generación del hash | Baja | ⏳ Pendiente | `Tramite.php` + vista |
| 6 | Tests automatizados para ValidacionController | Alta | ⏳ Pendiente | `tests/Feature/` (nuevo) |
| 7 | Notificación al invalidar hash | Media | ⏳ Pendiente | `TramiteObserver.php` |
| 8 | Historial de cambios de hash | Baja | ⏳ Pendiente | Migración nueva |
| 9 | Funcionalidad de regeneración manual de hash | Media | ⏳ Pendiente | `TramiteController.php` |
| 10 | Verificación de firma digital | Baja | ⏳ Pendiente | `Tramite.php` |

### ⚡ Optimizaciones Recomendadas (5)

| # | Optimización | Prioridad | Estado | Ubicación |
|---|--------------|-----------|--------|-----------|
| 1 | Eager loading de relaciones | Media | ⏳ Pendiente | `ValidacionController.php:17` |
| 2 | Verificar índice de hash en producción | Alta | ⏳ Pendiente | Migración nueva |
| 3 | Caché de trámites validados frecuentemente | Baja | ⏳ Pendiente | `ValidacionController.php` |
| 4 | Route model binding para hash | Baja | ⏳ Pendiente | `RouteServiceProvider.php` |
| 5 | Prevenir timing attacks en validación | Baja | ⏳ Pendiente | `ValidacionController.php:17` |

### 📝 Detalle de Correcciones Aplicadas

#### Bug #1: Campo `tipo_contribuyente` inexistente en Observer ✅

**Severidad:** ALTA  
**Ubicación:** `app/Observers/TramiteObserver.php:21-26`  
**Estado:** ✅ CORREGIDO

**Problema:** El observer intentaba invalidar el hash si cambiaba el campo `tipo_contribuyente`, pero este campo NO existía en la tabla `tramites`.

**Solución aplicada:** Eliminado `'tipo_contribuyente'` del array `$camposCriticos`.

```php
// ✅ CORREGIDO - Eliminado campo inexistente
$camposCriticos = [
    'base_imponible',
    'fecha_transmision',
    'tipo_transmision_id',
    // 'tipo_contribuyente'  // Eliminado: campo no existe
];
```

---

#### Bug #2: Sin validación de longitud de hash en el controlador ✅

**Severidad:** MEDIA  
**Ubicación:** `app/Http/Controllers/ValidacionController.php:18-21`  
**Estado:** ✅ CORREGIDO

**Problema:** El controlador no validaba que el hash tuviera exactamente 64 caracteres hexadecimales antes de consultar la BD.

**Solución aplicada:** Agregada validación de longitud y formato hexadecimal.

```php
public function show(string $hash)
{
    // ✅ CORREGIDO - Validación de hash SHA256
    if (strlen($hash) !== 64 || !ctype_xdigit($hash)) {
        abort(404, 'Hash de validación inválido');
    }

    $tramite = Tramite::whereHashValidacion($hash)->firstOrFail();
    // ...
}
```

---

#### Bug #3: Posible error 500 si no existe la relación tipoTransmision ✅

**Severidad:** MEDIA  
**Ubicación:** `resources/views/validacion/show.blade.php:54`  
**Estado:** ✅ CORREGIDO

**Problema:** La vista accedía directamente a `$tramite->tipoTransmision->nombre` sin verificar que la relación existiera.

**Solución aplicada:** Usado helper `optional()` con valor por defecto.

```php
<dt class="col-sm-4">Tipo de Transmisión</dt>
<dd class="col-sm-8">{{ optional($tramite->tipoTransmision)->nombre ?? 'No especificado' }}</dd>
```

---

#### Bug #4: No se validan trámites en estado específico ✅

**Severidad:** BAJA  
**Ubicación:** `ValidacionController.php:28-30`  
**Estado:** ✅ CORREGIDO

**Problema:** El controlador permitía validar trámites en estados `Borrador` o `Anulado`, que no deberían ser válidos para verificación pública.

**Solución aplicada:** Agregada validación de estado antes de mostrar el trámite.

```php
// ✅ CORREGIDO - Validación de estado
if (in_array($tramite->estado, ['Borrador', 'Anulado'])) {
    abort(404, 'El trámite no está disponible para validación');
}
```

---

#### Bug #5: No se usa scope `whereHashValidacion()` para reutilización ✅

**Severidad:** BAJA  
**Ubicación:** `Tramite.php:176-187` y `ValidacionController.php:26`  
**Estado:** ✅ CORREGIDO

**Problema:** La consulta se realizaba directamente sin usar un scope reutilizable y sin eager loading de relaciones.

**Solución aplicada:** Creado scope `whereHashValidacion()` en el modelo con eager loading incluido.

```php
// En Tramite.php
public function scopeWhereHashValidacion($query, string $hash)
{
    return $query->where('hash_validacion', $hash)
                 ->with(['tipoTransmision', 'adquirentes.person']);
}

// En ValidacionController.php
$tramite = Tramite::whereHashValidacion($hash)->firstOrFail();
```

---

### 📊 Resumen de Prioridades

#### ✅ RESUELTOS (v1.1.0 - 19 Febrero 2026)
Todos los bugs identificados han sido corregidos:
1. ✅ **Bug #1:** Campo `tipo_contribuyente` inexistente en Observer
2. ✅ **Bug #2:** Validación de longitud de hash (64 caracteres)
3. ✅ **Bug #3:** Validar relaciones en vista (optional)
4. ✅ **Bug #4:** Validar trámites en estado específico
5. ✅ **Bug #5:** Usar scope `whereHashValidacion()`

#### 🔴 URGENTE (Mejoras pendientes)
1. **Optimización #2:** Verificar índice de hash en producción
2. **Mejora #6:** Crear tests automatizados

#### 🟠 ALTA
1. **Mejora #1:** Agregar rate limiting
2. **Mejora #3:** Agregar logging de intentos
3. **Mejora #7:** Notificación al invalidar hash

#### 🟡 MEDIA
1. **Mejora #2:** Crear endpoint API JSON
2. **Mejora #9:** Regeneración manual de hash

#### 🟢 BAJA
1. **Mejora #4:** Metadatos SEO/Open Graph
2. **Mejora #5:** Indicador visual de generación de hash
3. **Mejora #8:** Historial de cambios de hash
4. **Mejora #10:** Verificación de firma digital
5. **Optimización #1,3-5:** Eager loading adicional, caché, route model binding, timing attacks

### 📝 Historial de Cambios

### v1.1.0 (19 de febrero de 2026) ✅
**Corrección de Bugs Críticos:**
- Todos los 5 bugs identificados han sido corregidos
- El módulo está listo para producción

**Bugs Corregidos:**
- **Bug #1:** Eliminado campo inexistente `'tipo_contribuyente'` de `TramiteObserver.php`
- **Bug #2:** Agregada validación de longitud (64 caracteres) y formato hexadecimal en `ValidacionController.php`
- **Bug #3:** Usado `optional()` en vista para manejar relaciones nulas de `tipoTransmision`
- **Bug #4:** Agregada validación para rechazar trámites en estados `Borrador` o `Anulado`
- **Bug #5:** Creado scope `whereHashValidacion()` en modelo `Tramite` con eager loading

**Archivos Modificados:**
- `app/Observers/TramiteObserver.php` - Eliminado campo inexistente
- `app/Http/Controllers/ValidacionController.php` - Validaciones de hash y estado
- `app/Models/Tramite.php` - Nuevo scope `whereHashValidacion()`
- `resources/views/validacion/show.blade.php` - Uso de `optional()` para relaciones

### v1.0.0 (11 de octubre de 2025)
**Versión inicial:**
- Implementación de `ValidacionController` con método `show()`
- Generación de hash SHA256 en modelo `Tramite`
- Invalidación automática de hash en `TramiteObserver`
- Vista de validación con Bootstrap 5.3.2
- Integración con generación de PDF Formulario A01

**Bugs conocidos identificados (ahora corregidos en v1.1.0):**
- Campo `tipo_contribuyente` inexistente en observer
- Sin validación de longitud de hash en controlador
- Posible error 500 si no existe relación tipoTransmision
- No se validan estados de trámite
- No se usa scope reutilizable para consulta de hash

---

## 📚 Documentación Relacionada

- **Modelo Tramite:** `docs/dev/07_tramites.md`
- **Controlador Tramite:** `docs/dev/07_tramites.md` (sección de controladores)
- **Documentos:** `docs/dev/15_documentos.md`
- **IdtgbCalculator:** `docs/dev/18_idtgb_calculator.md`
- **Pagos:** `docs/dev/16_pagos.md`
