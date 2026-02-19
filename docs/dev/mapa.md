# Documentación Completa del Sistema de Mapas - Proyecto Incendios

## Índice
1. [Descripción General](#descripción-general)
2. [Tecnologías Utilizadas](#tecnologías-utilizadas)
3. [Estructura de Base de Datos](#estructura-de-base-de-datos)
4. [Componentes de Backend](#componentes-de-backend)
5. [Componentes de Frontend](#componentes-de-frontend)
6. [Implementaciones de Mapas](#implementaciones-de-mapas)
7. [Código de Ejemplo](#código-de-ejemplo)
8. [Mejoras Sugeridas](#mejoras-sugeridas)

---

## Descripción General

El sistema de mapas del proyecto de Incendios permite:

- Visualizar incendios activos y controlados en un mapa interactivo
- Seleccionar ubicaciones geográficas mediante clic en el mapa
- Obtener coordenadas (latitud/longitud) y direcciones automáticamente
- Mostrar detalles de incendios en popups interactivos
- Utilizar la geolocalización del dispositivo del usuario

El sistema utiliza **Leaflet.js** como librería de mapas, **OpenStreetMap** como proveedor de tiles, y **PostGIS** (POINT de MySQL) para el almacenamiento de coordenadas geoespaciales.

---

## Tecnologías Utilizadas

### Frontend
- **Leaflet.js v1.9.4**: Librería principal de mapas interactivos
- **OpenStreetMap**: Proveedor de tiles de mapa gratuitos
- **Chart.js**: Para gráficos estadísticos (complemento al mapa)
- **jQuery**: Para manipulación del DOM y eventos

### Backend
- **Laravel**: Framework PHP
- **MySQL con PostGIS**: Almacenamiento de datos geoespaciales (tipo POINT)

### Servicios Externos
- **Nominatim API (OpenStreetMap)**: Para geocodificación inversa (obtener dirección desde coordenadas)

### Dependencias
```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

---

## Estructura de Base de Datos

### Tabla `ubicaciones`

**Archivo**: `database/migrations/2025_08_30_212328_create_ubicaciones_table.php`

```php
Schema::create('ubicaciones', function (Blueprint $table) {
    $table->id();
    $table->string('direccion')->nullable();
    $table->text('referencia')->nullable();
    $table->point('coordenadas', 4326)->nullable();
    $table->timestamps();
    $table->softDeletes();
});
```

**Campos principales**:
- `id`: Identificador único
- `direccion`: Dirección manual o obtenida por geocodificación inversa
- `referencia`: Referencia adicional de ubicación
- `coordenadas`: Campo tipo POINT (geoespacial) usando SRID 4326 (WGS84)
- `created_at`, `updated_at`: Timestamps automáticos
- `deleted_at`: Soft deletes

**SRID 4326**: Sistema de referencia espacial WGS84 usado por GPS (latitud/longitud en grados decimales)

---

## Componentes de Backend

### Modelo `Ubicacion`

**Archivo**: `app/Models/Ubicacion.php`

#### Atributos Fillable
```php
protected $fillable = [
    'latitud',
    'longitud',
    'direccion',
    'referencia',
    'coordenadas',
];
```

#### Relaciones
```php
public function provincia()  { return $this->hasOne(Provincia::class); }
public function municipio()  { return $this->hasOne(Municipio::class); }
public function comunidad()  { return $this->hasOne(Comunidad::class); }
public function incendio()   { return $this->hasOne(Incendio::class); }
```

#### Accesor/Mutador: `getCoordenadasAttribute()`
**Propósito**: Parsea el campo POINT binario de MySQL a array PHP

```php
public function getCoordenadasAttribute($value)
{
    if (!$value) return null;

    $row = \DB::select("SELECT ST_AsText(coordenadas) AS point FROM ubicaciones WHERE id = ?", [$this->id])[0] ?? null;
    if (!$row) return null;

    preg_match('/POINT\(([^ ]+) ([^ ]+)\)/', $row->point, $m);
    if (count($m) !== 3) return null;

    return [
        'latitud'  => (float)$m[2], // POINT(long lat) - NOTA: el orden está invertido
        'longitud' => (float)$m[1],
    ];
}
```

**NOTA IMPORTANTE**: MySQL almacena POINT como `POINT(longitud latitud)` pero el código asume `POINT(latitud, longitud)`. Hay un error en la línea 80 del mutador (ver sección de Mejoras).

#### Accesor/Mutador: `setCoordenadasAttribute()`
**Propósito**: Convierte array PHP a expresión SQL POINT

```php
public function setCoordenadasAttribute($value)
{
    if ($value instanceof \Illuminate\Database\Query\Expression) {
        $this->attributes['coordenadas'] = $value;
    } elseif (is_array($value) && isset($value['latitud'], $value['longitud'])) {
        $this->attributes['coordenadas'] = \DB::raw("POINT({$value['latitud']}, {$value['longitud']})");
    }
}
```

**PROBLEMA**: El orden es incorrecto. Debería ser `POINT({$value['longitud']}, {$value['latitud']})`.

#### Accesor: `getLatLngAttribute()`
**Propósito**: Retorna array [lat, lng] o null, descartando coordenadas (0,0)

```php
public function getLatLngAttribute(): ?array
{
    $parsed = $this->coordenadas;
    if (!$parsed || !isset($parsed['latitud'], $parsed['longitud'])) {
        return null;
    }
    if ($parsed['latitud'] == 0 && $parsed['longitud'] == 0) {
        return null;
    }
    return [(float)$parsed['latitud'], (float)$parsed['longitud']];
}
```

---

### Controlador `PublicController`

**Archivo**: `app/Http/Controllers/PublicController.php`

#### Método `index()`
**Propósito**: Obtiene datos para el dashboard público incluyendo ubicaciones de incendios

```php
$incendiosParaMapa = Incendio::join('ubicaciones', 'incendios.ubicacion_id', '=', 'ubicaciones.id')
    ->whereIn('estado', ['activo', 'controlado'])
    ->whereNotNull('ubicaciones.coordenadas')
    ->select(
        'incendios.id', 
        'incendios.codigo_incendio', 
        'incendios.estado', 
        'incendios.nivel_gravedad', 
        'incendios.fecha_inicio',
        DB::raw('ST_Y(ubicaciones.coordenadas) as lat'),   // Extrae latitud
        DB::raw('ST_X(ubicaciones.coordenadas) as lon')    // Extrae longitud
    )
    ->get();
```

**Funciones SQL utilizadas**:
- `ST_X(point)`: Extrae la coordenada X (longitud) de un POINT
- `ST_Y(point)`: Extrae la coordenada Y (latitud) de un POINT

---

## Componentes de Frontend

### 1. Mapa Público de Incendios

**Archivos**:
- `resources/views/home.blade.php`: Vista principal
- `resources/views/partials/map.blade.php`: Partial del mapa

#### Estructura del Partial `partials/map.blade.php`
```html
<div class="card shadow-sm">
    <div class="card-header">
        <h3 class="card-title mb-0">📍 Mapa de Incendios Activos y Controlados</h3>
    </div>
    <div class="card-body">
        <div id="mapaIncendios"></div>
    </div>
</div>
```

#### Estilos CSS en `home.blade.php`
```css
#mapaIncendios {
    height: 500px;
    border-radius: .25rem;
    border: 1px solid #ddd;
}
```

#### JavaScript para Inicializar el Mapa
```javascript
document.addEventListener('DOMContentLoaded', function () {
    // Datos de incendios desde PHP
    const incendios = @json($incendiosParaMapa);
    
    // Coordenadas centradas en el departamento del Beni, Bolivia
    const map = L.map('mapaIncendios').setView([-13.45, -65.40], 7);

    // Capa de tiles de OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Icono para incendios activos
    const fireIconActivo = L.icon({
        iconUrl: 'https://img.icons8.com/plasticine/100/000000/fire-element.png',
        iconSize: [38, 38],
    });

    // Icono para incendios controlados
    const fireIconControlado = L.icon({
        iconUrl: 'https://img.icons8.com/color/96/000000/fire-extinguisher.png',
        iconSize: [35, 35],
    });

    // Agregar marcadores para cada incendio
    incendios.forEach(incendio => {
        const icon = incendio.estado === 'activo' ? fireIconActivo : fireIconControlado;
        const marker = L.marker([incendio.lat, incendio.lon], { icon: icon }).addTo(map);

        const popupContent = `
            <b>Código:</b> ${incendio.codigo_incendio}<br>
            <b>Estado:</b> <span style="text-transform: capitalize; font-weight: bold; color: ${incendio.estado === 'activo' ? 'red' : 'orange'}">${incendio.estado}</span><br>
            <b>Gravedad:</b> ${incendio.nivel_gravedad}<br>
            <b>Fecha Inicio:</b> ${new Date(incendio.fecha_inicio).toLocaleString()}
        `;
        marker.bindPopup(popupContent);
    });
});
```

---

### 2. Selector de Ubicación (Mapa Picker)

**Archivo**: `resources/views/vendor/voyager/formularios/edit-add.blade.php`

#### Estructura HTML
```html
<div class="row">
    <div class="col-md-6">
        <label>Dirección aprox.</label>
        <input type="text" name="direccion_manual" id="direccion_manual" class="form-control"
               value="{{ old('direccion_manual', optional(optional(optional($formulario)->incendio)->ubicacion)->direccion ?? '') }}"
               placeholder="Ej: Km 12 ruta 40">
        <small class="text-muted">Hacé clic en el mapa para obtener latitud y longitud</small>
    </div>

    <div class="col-md-3">
        <label>Latitud</label>
        <input type="number" step="0.000001" name="lat" id="lat" class="form-control"
               value="{{ old('lat', $lat) }}" placeholder="-24.123456">
    </div>
    <div class="col-md-3">
        <label>Longitud</label>
        <input type="number" step="0.000001" name="lon" id="lon" class="form-control"
               value="{{ old('lon', $lon) }}" placeholder="-65.654321">
    </div>
</div>

<div class="row">
    <div class="col-md-12">
        <label>Seleccioná la ubicación en el mapa</label>
        <div id="mapaPicker"></div>
        <br>
        <button type="button" id="btnMiUbicacion" class="btn btn-sm btn-info">
            <i class="voyager-location"></i> Usar mi ubicación actual
        </button>
    </div>
</div>
```

#### Estilos CSS
```css
#mapaPicker {
    height: 350px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
```

#### JavaScript Completo
```javascript
let mapPicker, marker;
const defaultCenter = [-24.123456, -65.654321];

function initMapPicker() {
    mapPicker = L.map('mapaPicker').setView(defaultCenter, 10);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { 
        attribution: '© OpenStreetMap' 
    }).addTo(mapPicker);
    
    // Cargar coordenadas existentes si están disponibles
    const lat = parseFloat($('#lat').val()) || defaultCenter[0];
    const lon = parseFloat($('#lon').val()) || defaultCenter[1];
    
    if (!isNaN(lat) && !isNaN(lon)) {
        mapPicker.setView([lat, lon], 13);
        marker = L.marker([lat, lon]).addTo(mapPicker);
    }
    
    // Evento clic en el mapa
    mapPicker.on('click', function (ev) {
        const {lat, lng} = ev.latlng;
        $('#lat').val(lat.toFixed(6));
        $('#lon').val(lng.toFixed(6));
        if (marker) mapPicker.removeLayer(marker);
        marker = L.marker([lat, lng]).addTo(mapPicker);
        reverseGeocode(lat, lng);
    });
}

// Botón para usar geolocalización del dispositivo
$('#btnMiUbicacion').on('click', () => {
    if (!navigator.geolocation) return toastr.error('Tu navegador no soporta geolocalización');
    
    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude, lon = pos.coords.longitude;
            $('#lat').val(lat.toFixed(6));
            $('#lon').val(lon.toFixed(6));
            if (marker) mapPicker.removeLayer(marker);
            marker = L.marker([lat, lon]).addTo(mapPicker);
            mapPicker.setView([lat, lon], 15);
            reverseGeocode(lat, lon);
        },
        err => toastr.error('No se pudo obtener tu ubicación: ' + err.message),
        {enableHighAccuracy: true, timeout: 10000}
    );
});

// Geocodificación inversa (obtener dirección desde coordenadas)
function reverseGeocode(lat, lon) {
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`)
        .then(res => res.json())
        .then(data => {
            if (data && data.display_name) {
                const direccion = data.display_name.split(',').slice(0, 3).join(',').trim();
                $('#direccion_manual').val(direccion);
            } else {
                $('#direccion_manual').val('');
            }
        })
        .catch(err => {
            console.warn('Error reverse geocoding:', err);
            $('#direccion_manual').val('');
        });
}

initMapPicker();
```

---

### 3. Visualización de Ubicación en Detalle

**Archivo**: `resources/views/vendor/voyager/formularios/partials/ubicacion-mapa.blade.php`

#### Estructura HTML y PHP
```html
<h4>🌍 Ubicación del incendio</h4>
@php
    $ubi = $formulario->incendio->ubicacion;
    $parsed = $ubi?->coordenadas;
    $lat = $parsed['latitud']  ?? null;
    $lon = $parsed['longitud'] ?? null;

    // Descartar (0,0)
    if ($lat == 0 && $lon == 0)  $lat = $lon = null;
@endphp

@if($ubi && $lat && $lon)
    <table class="table table-bordered">
        <tr>
            <th width="200">Dirección / Referencia</th>
            <td>{{ $ubi->direccion ?? 'Sin referencia' }}</td>
        </tr>
        <tr>
            <th>Latitud</th>
            <td>{{ number_format($lat, 6) }}</td>
        </tr>
        <tr>
            <th>Longitud</th>
            <td>{{ number_format($lon, 6) }}</td>
        </tr>
    </table>

    <div id="mapa" style="height: 350px; border: 1px solid #ccc;"></div>
    
    @push('javascript')
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <script>
            var map = L.map('mapa').setView([{{ $lat }}, {{ $lon }}], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap'
            }).addTo(map);
            L.marker([{{ $lat }}, {{ $lon }}]).addTo(map)
                .bindPopup("Incendio {{ $formulario->incendio->codigo_incendio }}");
        </script>
    @endpush
@else
    <p class="alert alert-warning">No se registró ubicación para este incendio.</p>
@endif
```

---

## Implementaciones de Mapas

### Resumen de Implementaciones

| # | Implementación | Archivo | Propósito | ID del Mapa |
|---|----------------|---------|-----------|-------------|
| 1 | Mapa Público de Incendios | `resources/views/home.blade.php` | Mostrar todos los incendios activos y controlados | `mapaIncendios` |
| 2 | Selector de Ubicación | `resources/views/vendor/voyager/formularios/edit-add.blade.php` | Permitir seleccionar ubicación al crear/editar formulario | `mapaPicker` |
| 3 | Visualización de Ubicación | `resources/views/vendor/voyager/formularios/partials/ubicacion-mapa.blade.php` | Mostrar ubicación específica de un incendio | `mapa` |

---

## Código de Ejemplo

### Ejemplo 1: Crear un mapa simple con Leaflet
```javascript
// Inicializar mapa centrado en coordenadas específicas
const map = L.map('miMapa').setView([-13.45, -65.40], 7);

// Agregar capa de tiles de OpenStreetMap
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
}).addTo(map);

// Agregar un marcador
const marker = L.marker([-13.45, -65.40]).addTo(map);

// Agregar popup al marcador
marker.bindPopup('¡Hola! Este es un marcador en el mapa.');
```

### Ejemplo 2: Marcador personalizado
```javascript
// Crear icono personalizado
const customIcon = L.icon({
    iconUrl: '/path/to/icon.png',
    iconSize: [38, 38],
    iconAnchor: [19, 38],
    popupAnchor: [0, -38]
});

// Usar el icono personalizado
const marker = L.marker([-13.45, -65.40], { icon: customIcon }).addTo(map);
```

### Ejemplo 3: Múltiples marcadores desde datos JSON
```javascript
const ubicaciones = [
    { id: 1, lat: -13.45, lon: -65.40, nombre: 'Ubicación 1' },
    { id: 2, lat: -13.50, lon: -65.45, nombre: 'Ubicación 2' },
    { id: 3, lat: -13.40, lon: -65.35, nombre: 'Ubicación 3' },
];

ubicaciones.forEach(ubicacion => {
    const marker = L.marker([ubicacion.lat, ubicacion.lon]).addTo(map);
    marker.bindPopup(`<b>${ubicacion.nombre}</b>`);
});
```

### Ejemplo 4: Evento de clic en el mapa
```javascript
map.on('click', function(e) {
    const { lat, lng } = e.latlng;
    console.log(`Clic en: ${lat}, ${lng}`);
    
    // Mostrar popup en la ubicación del clic
    L.popup()
        .setLatLng(e.latlng)
        .setContent(`Ubicación: ${lat.toFixed(6)}, ${lng.toFixed(6)}`)
        .openOn(map);
});
```

### Ejemplo 5: Geolocalización del usuario
```javascript
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lon = position.coords.longitude;
            
            L.marker([lat, lon]).addTo(map)
                .bindPopup('Tu ubicación actual')
                .openPopup();
            
            map.setView([lat, lon], 15);
        },
        function(error) {
            console.error('Error al obtener ubicación:', error);
        }
    );
}
```

### Ejemplo 6: Consulta SQL para extraer coordenadas POINT
```php
// Extraer latitud y longitud desde campo POINT
$ubicaciones = DB::table('ubicaciones')
    ->select(
        'id',
        'direccion',
        DB::raw('ST_Y(coordenadas) as latitud'),
        DB::raw('ST_X(coordenadas) as longitud')
    )
    ->whereNotNull('coordenadas')
    ->get();

// O usando Eloquent con join
$incendios = Incendio::join('ubicaciones', 'incendios.ubicacion_id', '=', 'ubicaciones.id')
    ->select(
        'incendios.*',
        DB::raw('ST_Y(ubicaciones.coordenadas) as lat'),
        DB::raw('ST_X(ubicaciones.coordenadas) as lon')
    )
    ->whereNotNull('ubicaciones.coordenadas')
    ->get();
```

### Ejemplo 7: Guardar coordenadas en formato POINT
```php
use Illuminate\Support\Facades\DB;

// Método 1: Usando DB::raw
$ubicacion = new Ubicacion();
$ubicacion->coordenadas = DB::raw("POINT(-65.40, -13.45)"); // NOTA: lon, lat
$ubicacion->direccion = 'Dirección de ejemplo';
$ubicacion->save();

// Método 2: Usando array (con el mutador del modelo)
$ubicacion = new Ubicacion();
$ubicacion->coordenadas = [
    'latitud' => -13.45,
    'longitud' => -65.40
];
$ubicacion->direccion = 'Dirección de ejemplo';
$ubicacion->save();
```

---

## Mejoras Sugeridas

### 🔴 CRÍTICAS (Deben corregirse)

#### 1. **Corregir el orden de coordenadas en POINT**
**Problema**: MySQL almacena POINT como `POINT(longitud latitud)` pero el código actual lo guarda como `POINT(latitud, longitud)`.

**Ubicación**: `app/Models/Ubicacion.php:80`

**Código actual**:
```php
$this->attributes['coordenadas'] = \DB::raw("POINT({$value['latitud']}, {$value['longitud']})");
```

**Código corregido**:
```php
$this->attributes['coordenadas'] = \DB::raw("POINT({$value['longitud']}, {$value['latitud']})");
```

**Impacto**: Sin esta corrección, las coordenadas se guardan incorrectamente y el marcador aparecerá en la ubicación equivocada.

#### 2. **Corregir el parser de coordenadas**
**Problema**: El accesor `getCoordenadasAttribute()` asume un orden incorrecto al leer el POINT.

**Ubicación**: `app/Models/Ubicacion.php:67-68`

**Código actual**:
```php
return [
    'latitud'  => (float)$m[2], // POINT(long lat)
    'longitud' => (float)$m[1],
];
```

**Código corregido**:
```php
return [
    'longitud' => (float)$m[1], // El primer valor es X (longitud)
    'latitud'  => (float)$m[2], // El segundo valor es Y (latitud)
];
```

---

### 🟡 IMPORTANTES (Recomendadas)

#### 3. **Validación de coordenadas**
Agregar validación en el backend para asegurar que las coordenadas estén en rangos válidos:

```php
// En el FormRequest o Controller
$request->validate([
    'lat' => 'required|numeric|between:-90,90',
    'lon' => 'required|numeric|between:-180,180',
]);
```

#### 4. **Manejo de errores en geocodificación inversa**
Implementar un sistema de reintentos y manejo de errores más robusto:

```javascript
async function reverseGeocode(lat, lon, retries = 3) {
    try {
        const response = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`
        );
        
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        
        const data = await response.json();
        
        if (data && data.display_name) {
            const direccion = data.display_name.split(',').slice(0, 3).join(',').trim();
            $('#direccion_manual').val(direccion);
        }
    } catch (err) {
        if (retries > 0) {
            setTimeout(() => reverseGeocode(lat, lon, retries - 1), 1000);
        } else {
            console.error('Error en geocodificación inversa:', err);
            $('#direccion_manual').val('');
        }
    }
}
```

#### 5. **Agregar indicador de carga durante geocodificación**
Mejorar la UX mostrando un indicador de carga mientras se obtiene la dirección:

```html
<div class="input-group">
    <input type="text" name="direccion_manual" id="direccion_manual" class="form-control">
    <span class="input-group-addon" id="geocoding-loader" style="display:none;">
        <i class="fa fa-spinner fa-spin"></i>
    </span>
</div>
```

```javascript
function reverseGeocode(lat, lon) {
    $('#geocoding-loader').show();
    
    fetch(`https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lon}&zoom=18&addressdetails=1`)
        .then(res => res.json())
        .then(data => {
            if (data && data.display_name) {
                const direccion = data.display_name.split(',').slice(0, 3).join(',').trim();
                $('#direccion_manual').val(direccion);
            }
        })
        .finally(() => {
            $('#geocoding-loader').hide();
        });
}
```

#### 6. **Implementar clustering de marcadores**
Cuando hay muchos marcadores, el mapa se puede saturar. Usar Leaflet.markercluster:

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"/>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
```

```javascript
const markers = L.markerClusterGroup();

incendios.forEach(incendio => {
    const marker = L.marker([incendio.lat, incendio.lon], { icon: icon });
    marker.bindPopup(popupContent);
    markers.addLayer(marker);
});

map.addLayer(markers);
```

#### 7. **Agregar filtros por estado y gravedad**
Permitir al usuario filtrar qué incendios mostrar en el mapa:

```html
<div class="mb-3">
    <label>Filtrar por estado:</label>
    <select id="filtroEstado" class="form-control">
        <option value="todos">Todos</option>
        <option value="activo">Activos</option>
        <option value="controlado">Controlados</option>
    </select>
</div>
```

```javascript
let allMarkers = [];

// Guardar referencias a todos los marcadores
incendios.forEach(incendio => {
    const marker = L.marker([incendio.lat, incendio.lon], { icon: icon });
    marker.estado = incendio.estado;
    marker.bindPopup(popupContent);
    marker.addTo(map);
    allMarkers.push(marker);
});

// Filtrar marcadores
$('#filtroEstado').on('change', function() {
    const filtro = $(this).val();
    
    allMarkers.forEach(marker => {
        if (filtro === 'todos' || marker.estado === filtro) {
            if (!map.hasLayer(marker)) map.addLayer(marker);
        } else {
            if (map.hasLayer(marker)) map.removeLayer(marker);
        }
    });
});
```

---

### 🟢 MEJORAS (Opcionales pero recomendadas)

#### 8. **Agregar círculos de radio**
Mostrar el área afectada con un círculo semitransparente:

```javascript
incendios.forEach(incendio => {
    const marker = L.marker([incendio.lat, incendio.lon]).addTo(map);
    
    if (incendio.area_afectada_ha) {
        const radio = Math.sqrt(incendio.area_afectada_ha * 10000 / Math.PI); // Convertir ha a metros
        L.circle([incendio.lat, incendio.lon], {
            color: 'red',
            fillColor: '#f03',
            fillOpacity: 0.3,
            radius: radio
        }).addTo(map);
    }
});
```

#### 9. **Implementar búsqueda de ubicación**
Agregar un control de búsqueda usando Leaflet Control Geocoder:

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css"/>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
```

```javascript
L.Control.geocoder().addTo(map);
```

#### 10. **Agregar capa de calor (Heatmap)**
Visualizar la densidad de incendios con un mapa de calor:

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.css"/>
<script src="https://unpkg.com/leaflet.heat@0.2.0/dist/leaflet-heat.js"></script>
```

```javascript
const heatData = incendios.map(i => [i.lat, i.lon, 1]);
L.heatLayer(heatData, {
    radius: 25,
    blur: 15,
    maxZoom: 12
}).addTo(map);
```

#### 11. **Implementar control de capas**
Permitir cambiar entre diferentes proveedores de mapas:

```javascript
const baseMaps = {
    "OpenStreetMap": L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }),
    "Satélite": L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
    }),
    "Terreno": L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
        attribution: 'Map data: &copy; OpenStreetMap'
    })
};

baseMaps["OpenStreetMap"].addTo(map);

L.control.layers(baseMaps).addTo(map);
```

#### 12. **Exportar mapa a imagen**
Permitir a los usuarios descargar el mapa como imagen:

```html
<script src="https://unpkg.com/leaflet.easyprint@2.1.9/dist/bundle.min.js"></script>
```

```javascript
L.easyPrint({
    title: 'Imprimir mapa',
    position: 'bottomright',
    sizeModes: ['Current', 'A4Landscape', 'A4Portrait'],
    exportOnly: true,
    filename: 'mapa_incendios.png'
}).addTo(map);
```

#### 13. **Agregar animaciones de marcadores**
Implementar marcadores animados para incendios activos:

```javascript
const pulsingIcon = L.divIcon({
    className: 'custom-div-icon',
    html: "<div style='background-color: red; width: 20px; height: 20px; border-radius: 50%; animation: pulse 2s infinite;'></div>",
    iconSize: [20, 20],
    iconAnchor: [10, 10]
});

const marker = L.marker([lat, lon], { icon: pulsingIcon }).addTo(map);
```

```css
@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.5); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}
```

#### 14. **Implementar límites geográficos**
Restringir el mapa a una región específica:

```javascript
const bounds = [
    [-15.0, -67.0], // Sudoeste
    [-12.0, -63.0]  // Noreste
];

map.setMaxBounds(bounds);
map.fitBounds(bounds);
```

#### 15. **Agregar medición de distancias**
Implementar herramientas de medición en el mapa:

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet-measure@3.1.0/dist/leaflet-measure.css"/>
<script src="https://unpkg.com/leaflet-measure@3.1.0/dist/leaflet-measure.js"></script>
```

```javascript
L.control.measure({
    position: 'topleft',
    primaryLengthUnit: 'meters',
    secondaryLengthUnit: 'kilometers',
    primaryAreaUnit: 'sqmeters',
    secondaryAreaUnit: 'hectares'
}).addTo(map);
```

#### 16. **Optimizar rendimiento con datos masivos**
Implementar carga diferida de marcadores:

```javascript
let currentPage = 1;
const perPage = 50;

function loadMarkers(page) {
    fetch(`/api/incendios?page=${page}&per_page=${perPage}`)
        .then(res => res.json())
        .then(data => {
            data.forEach(incendio => {
                const marker = L.marker([incendio.lat, incendio.lon]).addTo(map);
                marker.bindPopup(popupContent);
            });
        });
}

// Cargar más marcadores cuando el usuario hace zoom
map.on('moveend', function() {
    const zoom = map.getZoom();
    if (zoom > 10) {
        currentPage++;
        loadMarkers(currentPage);
    }
});
```

#### 17. **Agregar soporte para múltiples idiomas**
Implementar internacionalización en los popups:

```javascript
const idiomas = {
    es: {
        codigo: 'Código',
        estado: 'Estado',
        gravedad: 'Gravedad',
        fecha: 'Fecha Inicio'
    },
    en: {
        codigo: 'Code',
        estado: 'Status',
        gravedad: 'Severity',
        fecha: 'Start Date'
    }
};

const lang = $('html').attr('lang') || 'es';
const labels = idiomas[lang];

const popupContent = `
    <b>${labels.codigo}:</b> ${incendio.codigo_incendio}<br>
    <b>${labels.estado}:</b> ${incendio.estado}<br>
    <b>${labels.gravedad}:</b> ${incendio.nivel_gravedad}<br>
    <b>${labels.fecha}:</b> ${new Date(incendio.fecha_inicio).toLocaleString()}
`;
```

#### 18. **Implementar sistema de notificaciones**
Mostrar notificaciones cuando se actualiza el estado de un incendio:

```javascript
function checkForUpdates() {
    fetch('/api/incendios/updates')
        .then(res => res.json())
        .then(updates => {
            updates.forEach(update => {
                if (update.tipo === 'nuevo_incendio') {
                    toastr.success(`Nuevo incendio detectado: ${update.codigo}`);
                    addMarkerToMap(update);
                } else if (update.tipo === 'cambio_estado') {
                    toastr.info(`Incendio ${update.codigo} ahora está ${update.estado}`);
                    updateMarkerStatus(update.id, update.estado);
                }
            });
        });
}

// Verificar actualizaciones cada 30 segundos
setInterval(checkForUpdates, 30000);
```

#### 19. **Agregar modo oscuro del mapa**
Implementar tema oscuro para el mapa:

```javascript
const darkLayer = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
    attribution: '&copy; OpenStreetMap &copy; CARTO'
});

const isDarkMode = document.body.classList.contains('dark-mode');
if (isDarkMode) {
    map.removeLayer(currentLayer);
    map.addLayer(darkLayer);
}
```

#### 20. **Implementar compartir ubicación**
Permitir compartir la ubicación de un incendio:

```javascript
function shareLocation(incendio) {
    const url = `${window.location.origin}/mapa?lat=${incendio.lat}&lon=${incendio.lon}&zoom=13`;
    
    if (navigator.share) {
        navigator.share({
            title: `Incendio ${incendio.codigo_incendio}`,
            text: `Mira la ubicación del incendio en el mapa`,
            url: url
        });
    } else {
        navigator.clipboard.writeText(url).then(() => {
            toastr.success('Enlace copiado al portapapeles');
        });
    }
}
```

---

## Buenas Prácticas

### 📌 Seguridad
1. **Validar siempre las coordenadas** en el lado del servidor
2. **Sanitizar las direcciones** obtenidas de APIs externas
3. **Limitar el uso de APIs externas** para evitar bloqueos por rate limiting
4. **No almacenar coordenadas (0,0)** como válidas

### 📌 Rendimiento
1. **Usar clustering** cuando hay más de 100 marcadores
2. **Implementar carga diferida** para conjuntos de datos grandes
3. **Cachear las respuestas** de geocodificación inversa
4. **Optimizar consultas SQL** usando índices espaciales

### 📌 Experiencia de Usuario
1. **Mostrar indicadores de carga** durante operaciones asíncronas
2. **Proporcionar retroalimentación** inmediata al usuario
3. **Implementar manejo de errores** amigable
4. **Usar iconos significativos** y consistentes

### 📌 Mantenibilidad
1. **Centralizar la configuración** de mapas en un archivo JS separado
2. **Crear componentes reutilizables** para mapas
3. **Documentar las dependencias** externas y sus versiones
4. **Implementar pruebas** para las funciones de geolocalización

---

## Referencias

### Documentación Oficial
- **Leaflet.js**: https://leafletjs.com/reference.html
- **OpenStreetMap**: https://www.openstreetmap.org/
- **Nominatim API**: https://nominatim.org/release-docs/latest/api/Reverse/
- **MySQL Spatial Data**: https://dev.mysql.com/doc/refman/8.0/en/spatial-types.html

### Plugins Recomendados de Leaflet
- **Leaflet.markercluster**: https://github.com/Leaflet/Leaflet.markercluster
- **Leaflet.Control.Geocoder**: https://github.com/perliedman/leaflet-control-geocoder
- **Leaflet.heat**: https://github.com/Leaflet/Leaflet.heat
- **Leaflet.draw**: https://github.com/Leaflet/Leaflet.draw
- **Leaflet easyPrint**: https://github.com/rowanwins/leaflet-easyPrint
- **Leaflet measure**: https://github.com/ljagis/leaflet-measure

---

## Conclusión

Este sistema de mapas está bien implementado pero requiere correcciones críticas en el manejo de coordenadas POINT de MySQL. Con las mejoras sugeridas, puede convertirse en una solución robusta y escalable para visualización y gestión de datos geoespaciales.

La implementación actual cubre los casos de uso básicos: visualización pública, selección de ubicación en formularios, y visualización de ubicaciones individuales. Las mejoras propuestas agregarían funcionalidades avanzadas como clustering, búsqueda, medición, y más.

---

**Documentación generada el**: 2026-01-30
**Proyecto**: Sistema de Gestión de Incendios
**Tecnologías**: Laravel, MySQL, Leaflet.js, OpenStreetMap
