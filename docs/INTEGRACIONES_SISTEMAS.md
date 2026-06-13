# Integraciones del Sistema IDTGB

## Descripción del Sistema

El **IDTGB** (Impuesto Departamental a la Transmisión Gratuita de Bienes) es un sistema del **Gobierno Autónomo Departamental del Beni** que gestiona el impuesto aplicado a:

- **Herencias**
- **Donaciones**
- **Legados**

Este impuesto departamental está establecido por ley y recae sobre la transmisión gratuita de bienes inmuebles.

## Funcionalidades del Sistema

El sistema IDTGB automatiza:

1. **Cálculo del impuesto** según tasas departamentales (1%, 10%, 20% según valor)
2. **Cálculo de intereses** por mora usando UFV y tramos escalonados (Ley 812)
3. **Gestión de trámites** para ciudadanos y funcionarios
4. **Generación de Formulario A-01** oficial
5. **Validación de documentos** mediante hash SHA256
6. **Registro de pagos** con generación de QR

## Por qué es necesario este sistema

El IDTGB es un impuesto obligatorio por ley departamental que requiere:

- **Transparencia:** Registro digital de todas las transmisiones gratuitas
- **Eficiencia:** Automatización de cálculos complejos con UFV
- **Control:** Auditoría y trazabilidad de trámites
- **Accesibilidad:** Calculadora pública para ciudadanos
- **Legalidad:** Cumplimiento de la Ley 812 y normativas departamentales

## Sistemas con los que puede conectarse

### 1. SIN (Servicio de Impuestos Nacionales)

**Propósito de integración:**
- Validar NIT de contribuyentes
- Verificar estado fiscal
- Compartir datos de transmisiones
- Evitar doble tributación

**Métodos de integración:**
```php
// Ejemplo de integración con SIN
Route::get('/api/sin/validar-nit/{nit}', [SINController::class, 'validarNIT']);
Route::post('/api/sin/consultar-estado', [SINController::class, 'consultarEstado']);
```

**Datos a intercambiar:**
- NIT del contribuyente
- Estado fiscal (activo, inactivo, suspendido)
- Historial de declaraciones
- Información de domicilio fiscal

**Beneficios:**
- Validación automática de contribuyentes
- Reducción de fraudes
- Mejor control tributario

---

### 2. Sistema de Derechos Reales

**Propósito de integración:**
- Validar propiedad de inmuebles
- Verificar derechos registrales
- Consultar historial de transmisiones
- Validar gravámenes y embargos

**Métodos de integración:**
```php
// Ejemplo de integración con Derechos Reales
Route::get('/api/derechos-reales/validar-inmueble/{matricula}', [DerechosRealesController::class, 'validarInmueble']);
Route::get('/api/derechos-reales/historial/{matricula}', [DerechosRealesController::class, 'historial']);
```

**Datos a intercambiar:**
- Número de matrícula
- Propietario(s) registrado(s)
- Superficie y ubicación
- Gravámenes y embargos
- Historial de transmisiones

**Beneficios:**
- Validación automática de propiedad
- Prevención de fraudes
- Mejor trazabilidad de inmuebles

---

### 3. Sistema de Notarías

**Propósito de integración:**
- Validar escrituras públicas
- Verificar autenticidad de documentos
- Digitalizar procesos notariales
- Sincronizar registros de transmisiones

**Métodos de integración:**
```php
// Ejemplo de integración con Notarías
Route::post('/api/notarias/validar-escritura', [NotariaController::class, 'validarEscritura']);
Route::post('/api/notarias/registrar-transmision', [NotariaController::class, 'registrarTransmision']);
```

**Datos a intercambiar:**
- Número de escritura
- Notaría autorizante
- Fecha de otorgamiento
- Partes intervinientes
- Datos del inmueble

**Beneficios:**
- Validación automática de documentos
- Reducción de tiempos de procesamiento
- Mayor seguridad jurídica

---

### 4. Sistema Bancario

**Propósito de integración:**
- Conciliación automática de pagos
- Validación de operaciones bancarias
- Integración con pasarelas de pago
- Notificación de pagos en tiempo real

**Métodos de integración:**
```php
// Ejemplo de integración con Bancos
Route::post('/api/bancos/conciliar-pago', [BancoController::class, 'conciliarPago']);
Route::post('/api/bancos/validar-operacion', [BancoController::class, 'validarOperacion']);
Route::post('/api/webhooks/pago-bancario', [WebhookController::class, 'pagoBancario']);
```

**Datos a intercambiar:**
- Número de operación
- Banco origen
- Monto y fecha
- Estado de la transacción
- Comprobante de pago

**Beneficios:**
- Conciliación automática
- Reducción de errores humanos
- Mejor control de recaudación

---

### 5. Sistema de Catastro

**Propósito de integración:**
- Validar datos catastrales
- Obtener valor fiscal de inmuebles
- Verificar zonificación y uso de suelo
- Consultar superficie y ubicación precisa

**Métodos de integración:**
```php
// Ejemplo de integración con Catastro
Route::get('/api/catastro/consultar-inmueble/{codigo}', [CatastroController::class, 'consultarInmueble']);
Route::get('/api/catastro/valor-fiscal/{codigo}', [CatastroController::class, 'valorFiscal']);
```

**Datos a intercambiar:**
- Código catastral
- Valor fiscal
- Superficie y dimensiones
- Ubicación y coordenadas
- Zonificación y uso de suelo

**Beneficios:**
- Cálculo automático de base imponible
- Validación de datos de inmuebles
- Mejor precisión en avalúos

---

## Métodos de Integración

### 1. API REST

**Ventajas:**
- Estándar de la industria
- Fácil de implementar
- Escalable
- Independiente de tecnología

**Ejemplo:**
```php
// Controlador para integración con SIN
class SINController extends Controller
{
    public function validarNIT($nit)
    {
        $response = Http::get('https://api.sin.gob.bo/validar-nit', [
            'nit' => $nit,
            'token' => config('integraciones.sin.token')
        ]);

        return response()->json($response->json());
    }
}
```

---

### 2. Webhooks

**Ventajas:**
- Notificaciones en tiempo real
- Event-driven
- Reducción de polling

**Ejemplo:**
```php
// Webhook para recibir notificaciones de bancos
class WebhookController extends Controller
{
    public function pagoBancario(Request $request)
    {
        $data = $request->validate([
            'nro_operacion' => 'required|string',
            'monto' => 'required|numeric',
            'fecha' => 'required|date',
            'banco' => 'required|string'
        ]);

        // Procesar notificación de pago
        Pago::where('nro_operacion', $data['nro_operacion'])
            ->update(['estado' => 'Conciliado']);

        return response()->json(['status' => 'ok']);
    }
}
```

---

### 3. Intercambio de Archivos

**Formatos soportados:**
- XML (estándar tributario)
- JSON (API moderna)
- CSV (migraciones masivas)
- PDF (documentos oficiales)

**Ejemplo:**
```php
// Exportación de trámites a XML
public function exportarXML()
{
    $tramites = Tramite::with(['adquirentes', 'disponentes', 'inmuebles'])
        ->where('estado', 'Pagado')
        ->get();

    $xml = new \SimpleXMLElement('<tramites></tramites>');
    
    foreach ($tramites as $tramite) {
        $tramiteNode = $xml->addChild('tramite');
        $tramiteNode->addChild('nro_tramite', $tramite->nro_tramite);
        $tramiteNode->addChild('monto', $tramite->total_idtgb);
        // ... más campos
    }

    return response($xml->asXML(), 200)
        ->header('Content-Type', 'application/xml');
}
```

---

### 4. Base de Datos Compartida

**Opciones:**
- Vistas materializadas
- Replicación de datos
- Tablas compartidas con triggers
- ETL (Extract, Transform, Load)

**Ejemplo:**
```sql
-- Vista materializada para SIN
CREATE MATERIALIZED VIEW mv_sin_contribuyentes AS
SELECT 
    p.nit,
    p.full_name,
    p.ci,
    p.direccion,
    t.estado
FROM people p
JOIN tramites t ON t.contribuyente_id = p.id
WHERE t.estado = 'Pagado';

-- Actualizar vista
REFRESH MATERIALIZED VIEW mv_sin_contribuyentes;
```

---

## Seguridad en Integraciones

### 1. Autenticación

- **API Keys:** Tokens únicos por sistema
- **OAuth 2.0:** Para integraciones web
- **JWT:** Para autenticación stateless
- **Mutual TLS:** Para comunicaciones seguras

### 2. Autorización

- **RBAC:** Role-Based Access Control
- **Scopes:** Permisos específicos por API
- **Rate Limiting:** Limitar solicitudes por sistema

### 3. Encriptación

- **TLS 1.3:** Para comunicaciones en tránsito
- **AES-256:** Para datos sensibles en reposo
- **Hashing:** Para contraseñas y datos sensibles

### 4. Logging y Auditoría

- Registro de todas las llamadas a APIs externas
- Trazabilidad de datos intercambiados
- Alertas de anomalías o fallos

---

## Plan de Implementación

### Fase 1: Prioridad Alta
1. **Integración con SIN** - Validación de NIT
2. **Integración Bancaria** - Conciliación de pagos

### Fase 2: Prioridad Media
3. **Integración con Derechos Reales** - Validación de inmuebles
4. **Integración con Catastro** - Datos catastrales

### Fase 3: Prioridad Baja
5. **Integración con Notarías** - Validación de escrituras
6. **Sistema de Reporting** - Reportes cruzados

---

## Consideraciones Técnicas

### Performance
- Uso de colas para procesos asíncronos
- Caching de respuestas de APIs externas
- Timeout configurado para cada integración
- Circuit breakers para evitar fallos en cascada

### Resiliencia
- Reintentos automáticos con exponential backoff
- Fallback a procesos manuales si falla la integración
- Modo offline para operaciones críticas
- Sincronización diferida cuando hay conectividad

### Mantenibilidad
- Documentación de todas las APIs externas
- Versionado de contratos de integración
- Tests de integración automatizados
- Monitoreo continuo de endpoints

---

## Contacto

Para más información sobre integraciones, contactar al:
- **Departamento de Sistemas** - Gobernación del Beni
- **Dirección de Ingresos** - Gobernación del Beni

---

**Última actualización:** 13 de junio de 2026
**Versión:** 1.0.0
