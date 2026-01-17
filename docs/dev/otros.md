# Componentes del Sistema ITGB - Estado de Documentación

Este archivo contiene el registro de todos los componentes del sistema ITGB y su estado de documentación técnica en `docs/dev/`.

## ✅ Estado General: COMPLETADO

**Fecha de finalización:** 17 de Enero 2026

Todos los componentes del sistema han sido documentados correctamente. Ya no hay módulos pendientes de documentación.

---

## 📊 Historial de Componentes Documentados

| Tipo | Componente | Estado | Documentación |
|------|-----------|--------|---------------|
| **Controlador** | `DashboardController` | ✅ Completado | `dashboard_controller.md` |
| **Servicio** | `DashboardService` | ✅ Completado | `dashboard_service.md` |
| **Servicio** | `IdtgbCalculator` | ✅ Completado | `idtgb_calculator.md` |
| **Controlador** | `CalculadoraBeniController` | ✅ Completado | `calculadora_beni.md` |
| **Controlador** | `ReporteController` | ✅ Completado | `ReporteController.md` |
| **Controlador** | `AjaxController` | ✅ Completado | `AjaxController.md` |
| **Controlador** | `StorageController` | ✅ Completado | `StorageController.md` |
| **Observer** | `PagoObserver` | ✅ Completado | `PagoObserver.md` |
| **Servicio** | `DashboardCacheInvalidator` | ✅ Completado | `DashboardCacheInvalidator.md` |
| **Middleware** | `System` | ✅ Completado | `System.md` |
| **Middleware** | `Loggin` | ✅ Completado | `Loggin.md` |
| **Controlador** | `ValidacionController` | ✅ Completado | `ValidacionController.md` |
| **Controlador** | `UserController` | ✅ Completado | `UserController.md` |
| **Controlador** | `RoleController` | ✅ Completado | `RoleController.md` |
| **Trait** | `RegistersUserEvents` | ✅ Completado | `RegistersUserEvents.md` |
| **Controlador** | `ErrorController` | ✅ Completado | `ErrorController.md` |
| **Command** | `Install` | ✅ Completado | `Install.md` |
| **Controlador** | `SolucionDigitalController` | ✅ Completado | `SolucionDigitalController.md` |
| **Job** | `ExportarAlSINJob` | ✅ Completado | `ExportarAlSINJob.md` |

---

## 🎯 Módulos Core Documentados (CRÍTICOS)

### 1. ✅ IdtgbCalculator - SERVICIO CENTRAL
- **Archivo:** `docs/dev/idtgb_calculator.md`
- **Estado:** Completado
- **Contenido:** Lógica completa de cálculo de impuestos según Ley 812

### 2. ✅ DashboardService
- **Archivo:** `docs/dev/dashboard_service.md`
- **Estado:** Completado
- **Contenido:** Todos los KPIs, gráficos y cálculos estadísticos

### 3. ✅ DashboardController
- **Archivo:** `docs/dev/dashboard_controller.md`
- **Estado:** Completado
- **Contenido:** Controlador principal del dashboard

---

## 📚 Otros Módulos Documentados

### Módulos de Trámites
- ✅ `tramites.md` - Modelo Tramite
- ✅ `tramite_inmuebles.md` - Relación inmuebles
- ✅ `adquirentes_tramite.md` - Relación adquirentes
- ✅ `tramite_exenciones.md` - Relación exenciones

### Módulos de Datos
- ✅ `people.md` - Personas
- ✅ `parentesco.md` - Parentescos
- ✅ `tipos_transmision.md` - Tipos de transmisión
- ✅ `tipos_inmueble.md` - Tipos de inmueble
- ✅ `inmuebles.md` - Inmuebles
- ✅ `exenciones.md` - Exenciones
- ✅ `avaluos.md` - Avalúos
- ✅ `tasas.md` - Tasas de impuesto
- ✅ `ufvs.md` - Unidad de Fomento a la Vivienda
- ✅ `geografia.md` - Geografía (departamentos, provincias, municipios)
- ✅ `pagos.md` - Pagos
- ✅ `documentos.md` - Documentos

### Módulos del Sistema
- ✅ `dockerfile_doc.md` - Dockerfile

---

## ✅ Conclusión

La documentación técnica del sistema ITGB está **100% COMPLETADA**. Todos los componentes críticos y secundarios han sido documentados con:

- Arquitectura detallada
- Métodos públicos y privados
- Flujo de datos
- Ejemplos de uso
- Casos de uso reales
- Consideraciones importantes
- Integración con otros módulos

**Última actualización:** 17 de Enero 2026
**Estado:** ✅ Finalizado - No hay más pendientes de documentación

## 🔴 MÓDULOS PRIORITARIOS PARA DOCUMENTAR

### 1. **IdtgbCalculator** ⭐⭐⭐⭐
**Archivo:** `app/Services/IdtgbCalculator.php`
**Por qué documentar:** Es el SERVICIO CENTRAL de todo el sistema. Sin esto, no se calculan impuestos. Toda la lógica financiera está aquí.

**Responsabilidades:**
- Cálculo del ITGB base según Ley 812
- Cálculo de mantenimiento de valor (UFVs)
- Cálculo de intereses por mora (escalado 4%, 6%, 10%)
- Cálculo de multa IDF (50 o 100 UFVs)
- Factor de participación (Estilo Cochabamba)

**Métodos principales:**
- `calculateAndSave(Tramite $tramite)` - Para trámites grabados
- `calculateEstimate(...)` - Para calculadora pública

**Fórmulas implementadas:**
```
ITGB Base = max(0, Σ(BaseImponible × Tasa%))
Mantenimiento = ITGBBase × (UFV_Pago / UFV_Vencimiento)
Interés = ITGBActualizado × ((1 + r/360)^días - 1)
Multa IDF = (50 o 100) × UFV_Pago
Final = ITGBBase + Mantenimiento + Interés + Multa
```

**Integraciones:**
- Usa `Tasa::vigente()` para obtener tasas
- Usa `Ufv::getValorEnFecha()` para mantenimiento de valor
- Invocado desde: `TramiteObserver`, `AdquirenteTramiteController`, `TramiteExencionController`, `CalculadoraBeniController`

---

### 2. **DashboardService** ⭐⭐⭐⭐
**Archivo:** `app/Services/DashboardService.php`
**Por qué documentar:** Provee todos los datos estadísticos, KPIs y gráficos del dashboard. Es crítico para la toma de decisiones.

**Responsabilidades:**
- Cálculo de KPIs dinámicos por rango (hoy, semana, mes, año)
- Cálculo de tendencias vs período anterior
- Generación de datos para gráficos (recaudación, tipos de trámite, estados)
- Tabla de últimos trámites
- Gestión inteligente de cache (5 minutos TTL)

**KPIs calculados:**
- Recaudado en el período
- Trámites creados
- Trámites finalizados
- Trámites pendientes (acumulado)
- Tendencias (porcentaje de cambio vs anterior)

**Gráficos generados:**
- Recaudación por día/mes
- Comparación anual (actual vs anterior)
- Trámites por tipo de transmisión
- Trámites por estado

**Uso de cache:**
- Prefijo: `dashboard:{range}:{fechaInicio}:{fechaFin}`
- Métodos usan `cache()->remember()`
- Invalidación vía `DashboardCacheInvalidator`

---

### 3. **DashboardController** ⭐⭐⭐⭐
**Archivo:** `app/Http/Controllers/Admin/DashboardController.php`
**Por qué documentar:** Controlador principal del dashboard, consume `DashboardService`.

**Responsabilidades:**
- Renderizar vista principal del dashboard (Voyager)
- Proveer endpoint AJAX `/admin/dashboard/data` con JSON para gráficos
- Delegar toda la lógica de negocio a `DashboardService`

**Rutas:**
```
GET /admin/                   → DashboardController@index
GET /admin/dashboard/data     → DashboardController@fetchData
```

**Integración:**
- Consume: `DashboardService`
- Vista: `vendor/voyager.index` (override de Voyager)

---

### 4. **CalculadoraBeniController** ⭐⭐⭐⭐
**Archivo:** `app/Http/Controllers/CalculadoraBeniController.php`
**Por qué documentar:** Interfaz pública para que ciudadanos calculen su impuesto estimado.

**Responsabilidades:**
- Formulario de cálculo público (sin autenticación)
- Cálculo estimado usando `IdtgbCalculator`
- Generación de PDF descargable con estimación

**Métodos:**
- `formulario()` - Muestra formulario con parentescos y tipos de transmisión
- `calcular(Request $request)` - Retorna JSON con cálculo
- `descargarPdf(Request $request)` - Genera PDF descargable

**Validaciones:**
- Tipo contribuyente: Natural/Jurídica
- Base imponible: numérico, mínimo 0.01
- Participación: 1-100%
- Fechas: transmisión, presentación, vencimiento

**Rutas públicas:**
```
GET /calculadora-idtgb-beni     → Formulario
POST /calculadora-idtgb-beni   → Cálculo JSON
GET /calculadora-idtgb-beni-pdf → Descargar PDF
```

**Integración:**
- Usa: `IdtgbCalculator->calculateEstimate()`
- Departamento: Fija a "Beni" (código 'BE')
- Vista: `calculadora_beni_interactivo`
- PDF: `pdf/calculo_estimado_beni`

---

### 5. **DashboardCacheInvalidator** ⭐⭐⭐
**Archivo:** `app/Services/DashboardCacheInvalidator.php`
**Por qué documentar:** Servicio especializado para invalidación inteligente de cache del dashboard.

**Responsabilidades:**
- Identificar qué rangos de fecha se afectan por un cambio
- Invalidar solo las claves de cache necesarias (performance)
- Soportar múltiples rangos: hoy, semana, mes, año

**Métodos:**
- `clearForPago(Pago $pago)` - Invalidar por cambio en pago
- `clearForTramite(Tramite $tramite)` - Invalidar por cambio en trámite
- `clearForDate(Carbon $date)` - Detectar rangos afectados por fecha
- `clearAnnualComparisons()` - Invalidar comparaciones año actual vs anterior

**Lógica de rangos afectados:**
- Si pago hoy → invalidar rango diario
- Si pago esta semana → invalidar rango semanal
- Si pago este mes → invalidar rango mensual
- Si pago este año → invalidar rango anual

**Sufijos de cache que maneja:**
```
:recaudadoPeriodo
:tramitesPeriodo
:tramitesFinalizadosPeriodo
:recaudadoPeriodoAnterior
:tramitesPeriodoAnterior
:tramitesFinalizadosPeriodoAnterior
:tramitesPendientes
:tramitesPendientesAnterior
:tramitesPorEstado
:tramitesPorTipo
:ultimosTramites
:recaudacionGrouped
:recaudacionAnioActual
:recaudacionAnioAnterior
```

---

### 6. **PagoObserver** ⭐⭐⭐
**Archivo:** `app/Observers/PagoObserver.php`
**Por qué documentar:** Observer que mantiene sincronizado el dashboard cuando cambian pagos.

**Responsabilidades:**
- Escuchar eventos: `created`, `updated`, `deleted` de `Pago`
- Invalidar cache del dashboard automáticamente
- Evitar queries innecesarias en dashboard

**Eventos monitoreados:**
```php
Pago::created($pago)   → clearDashboardCache()
Pago::updated($pago)   → clearDashboardCache()
Pago::deleted($pago)  → clearDashboardCache()
```

**Integración:**
- Llama a: `DashboardCacheInvalidator->clearForPago($pago)`
- Se registra automáticamente en `AppServiceProvider` o modelo `Pago`

---

### 7. **ReporteController** ⭐⭐⭐
**Archivo:** `app/Http/Controllers/ReporteController.php`
**Por qué documentar:** Genera reportes estadísticos con opción de exportar a PDF.

**Responsabilidades:**
- Reporte de Recaudación (trámites finalizados por período)
- Reporte por Tipos de Trámite (agrupación estadística)
- Exportación a PDF usando `DomPDF`

**Métodos:**
- `index(Request $request)` - Muestra interfaz de reportes
- `generarReporteRecaudacion(Request $request)` - Reporte de recaudación
- `generarReporteTiposTramite(Request $request)` - Reporte por tipos

**Reportes disponibles:**
1. **Recaudación:**
   - Lista trámites finalizados en período
   - Suma total recaudada
   - Exporta a PDF

2. **Tipos de Trámite:**
   - Agrupa trámites por tipo de transmisión
   - Cantidad y monto total por tipo
   - Exporta a PDF

**Parámetros de consulta:**
```
fecha_inicio: required|date
fecha_fin:   required|date|after_or_equal:fecha_inicio
tipo_reporte: required|in:recaudacion,tipos_tramite
exportar:     nullable|in:pdf
```

**Rutas:**
```
GET /admin/reportes                 → Formulario de reportes
GET /admin/reportes?tipo=...     → Genera reporte
GET /admin/reportes?exportar=pdf  → Descarga PDF
```

---

### 8. **AjaxController** ⭐⭐⭐
**Archivo:** `app/Http/Controllers/AjaxController.php`
**Por qué documentar:** Provee endpoints AJAX para búsquedas dinámicas usadas en Select2.

**Responsabilidades:**
- Búsqueda rápida de personas (por CI, nombres, apellidos, teléfono)
- Creación rápida de personas desde AJAX (para wizard de trámites)

**Métodos:**
- `personList()` - Buscar personas con filtro flexible
- `personStore(Request $request)` - Crear persona desde AJAX

**Lógica de búsqueda:**
Busca en campos: `ci`, `phone`, `first_name`, `middle_name`, `paternal_surname`, `maternal_surname`
También busca en combinaciones: nombre completo, nombre + apellido

**Rutas:**
```
GET /ajax/personList       → Buscar personas (JSON)
POST /ajax/person/store     → Crear persona (JSON)
```

**Uso en el sistema:**
- Wizard de trámites (paso 2 y 3) para seleccionar disponentes/adquirentes
- Cualquier select de personas con búsqueda dinámica

---

## 🟠 MÓDULOS DE IMPORTANCIA MEDIA

### 9. **ValidacionController**
**Archivo:** `app/Http/Controllers/ValidacionController.php`
**Por qué documentar:** Permite validación pública de trámites por ciudadanos.

**Responsabilidades:**
- Buscar trámite por `hash_validacion`
- Mostrar vista pública con datos del trámite

**Ruta pública:**
```
GET /validar/{hash} → Vista pública de validación
```

**Vista:** `validacion.show`
Muestra: número de trámite, fechas, montos, estado, participantes

**Uso:** Ciudadanos pueden validar la autenticidad de su trámite ingresando el hash del documento PDF.

---

### 10. **StorageController**
**Archivo:** `app/Http/Controllers/StorageController.php`
**Por qué documentar:** Maneja el almacenamiento de imágenes con múltiples tamaños y versiones.

**Responsabilidades:**
- Subida de imágenes con optimización (Intervention Image)
- Generación de múltiples versiones: original, banner (900px), medium (600px), small (256px), cropped (300x300)
- Orientación automática de imágenes
- Compresión con calidad ajustable

**Método principal:**
- `store_image($file, $folder, $size = 1200)` - Procesa y guarda imagen

**Proceso de almacenamiento:**
1. Crea directorio por mes/año (ej: `avatars/F2025/`)
2. Genera nombre aleatorio único
3. Carga imagen una sola vez
4. Genera 5 versiones optimizadas
5. Guarda todas en storage

**Configuraciones de versiones:**
```
''        → 1200px, calidad 80
'-banner'  → 900px, calidad 80
'-medium'  → 600px, calidad 80
'-small'   → 256px, calidad 80
'-cropped'→ 300x300, calidad 80, crop center
```

**Uso:** Sistema de avatares, fotos de personas, documentos con imágenes.

---

### 11. **UserController** y **RoleController**
**Archivos:** `app/Http/Controllers/UserController.php`, `app/Http/Controllers/RoleController.php`
**Por qué documentar:** Extensión de Voyager para gestión de usuarios y roles.

**UserController:**
- `list()` - Listado AJAX de usuarios
- `store()` - Crear usuario
- `update()` - Actualizar usuario
- `destroy()` - Eliminar usuario (soft delete)

**RoleController:**
- `list()` - Listado AJAX de roles
- Oculta rol admin a no-admins

**Rutas:**
```
GET /admin/users/ajax/list     → Usuarios AJAX
POST /admin/users/store        → Crear usuario
PUT /admin/users/{id}          → Actualizar usuario
DELETE /admin/users/{id}/deleted → Eliminar usuario

GET /admin/roles/ajax/list     → Roles AJAX
```

**Uso:** Panel de administración de Voyager extendido.

---

### 12. **System Middleware**
**Archivo:** `app/Http/Middleware/System.php`
**Por qué documentar:** Middleware que controla acceso al sistema.

**Responsabilidades:**
- Modo mantenimiento: bloquea acceso excepto admins
- Modo desarrollo: bloquea excepto admins/administradores
- Lógica de licencia (comentada, para Solución Digital)
- Rutas siempre abiertas: login, logout, assets

**Lógica:**
```php
1. Rutas abiertas siempre (login, assets)
2. Si maintenance=1 → 503 excepto admins
3. Si development=1 → 503 excepto admins/administradores
4. Si licencia pagada → bloquear escritura (comentado)
5. Log de acceso
```

**Rutas afectadas:** Todo `/admin/*`

---

### 13. **Loggin Middleware**
**Archivo:** `app/Http/Middleware/Loggin.php`
**Por qué documentar:** Middleware que registra todas las peticiones HTTP.

**Responsabilidades:**
- Registrar cada petición en log `requests`
- Guardar: user_id, role, nombre, email, IP, URL, método, input
- Excluir rutas `/admin/compass` (evita loop infinito)
- No registrar datos sensibles: password, _token

**Lógica:**
```php
if (auth()->user() && !auth()->user()->hasRole('admin')) {
    Log::channel('requests')->info('Petición HTTP', $data);
}
```

**Uso:** Auditoría completa de acciones en el sistema.

---

### 14. **ExportarAlSINJob**
**Archivo:** `app/Jobs/ExportarAlSINJob.php`
**Por qué documentar:** Job que exporta trámites al SIN en segundo plano.

**Responsabilidades:**
- Generar archivo CSV con datos del trámite
- Guardar CSV en `storage/app/sin_exports/`
- Subir archivo por FTP (comentado, simulado)
- Ejecución en cola (queue)

**Datos exportados:**
```
NRO_TRAMITE, FECHA_PRESENTACION, MONTO_FINAL, ESTADO, 
ADQUIRENTE_CI, ADQUIRENTE_NOMBRE, INMUEBLE_CATASTRO
```

**Integración:**
- Despachado desde `TramiteObserver` cuando trámite finaliza
- Usa: `Storage` para guardar, FTP para subir (simulado)
- Loguea progreso en `Log::info()`

**Configuración FTP (env):**
```
SIN_FTP_HOST
SIN_FTP_USERNAME
SIN_FTP_PASSWORD
SIN_FTP_REMOTE_PATH
```

---

### 15. **SolucionDigitalController**
**Archivo:** `app/Http/Controllers/SolucionDigitalController.php`
**Por qué documentar:** Controlador para integración con servicio externo Solución Digital.

**Responsabilidades:**
- Verificar licencia/pago del sistema (comentado)
- Conectar con API externa de Solución Digital
- Obtener estado del sistema web

**Métodos:**
- `settings_code()` - Obtiene configuración desde base de datos externa

**Lógica (comentada):**
```php
DB::connection('solucionDigital')
   ->table('web_systems')
   ->where('code', setting('system.code-system'))
   ->first();
```

**Estado:** Funcionalidad está comentada/pausada.

---

### 16. **ErrorController**
**Archivo:** `app/Http/Controllers/ErrorController.php`
**Por qué documentar:** Manejo de errores HTTP personalizados.

**Responsabilidades:**
- Renderizar vistas de error dinámicas
- Soportar códigos de error 4xx y 5xx

**Método:**
- `error($id)` - Retorna vista `errors.{id}`

**Vistas:**
- `errors.503` - Mantenimiento
- Otros errores 4xx/5xx

**Ruta:**
```
GET /errors/{id} → Vista de error dinámica
```

---

## 🟢 MÓDULOS DE IMPORTANCIA BAJA

### 17. **Trait: RegistersUserEvents**
**Archivo:** `app/Traits/RegistersUserEvents.php`
**Por qué documentar:** Trait para auditoría automática en modelos.

**Responsabilidades:**
- Registrar usuario que crea registros
- Registrar usuario que elimina registros
- Guardar rol del usuario en ese momento

**Eventos:**
```php
creating() → Guardar registerUser_id, registerRole
deleting() → Guardar deleteUser_id, deleteRole, deleteObservacion
```

**Uso:** Usado en modelo `Person` y otros modelos que requieren auditoría.

---

### 18. **Command: Install**
**Archivo:** `app/Console/Commands/Install.php`
**Por qué documentar:** Comando de Artisan para instalación inicial del proyecto.

**Responsabilidades:**
- Crear `.env` desde `.env.example` si no existe
- Generar `APP_KEY`
- Migrar y semear base de datos
- Crear enlace simbólico de storage

**Uso:**
```bash
php artisan example:install
```

**Preguntas interactivas:**
- ¿Eliminar y recrear DB? → migrate:fresh, db:seed

---

## 🔗 INTEGRACIONES ENTRE COMPONENTES

```
DashboardController
    └─→ DashboardService
          ├─→ DashboardCacheInvalidator
          │     ├─→ PagoObserver
          │     └─→ TramiteObserver (asumido)
          └─→ Modelos: Tramite, Pago

CalculadoraBeniController
    └─→ IdtgbCalculator ← [SERVICIO CENTRAL]
          ├─→ Tasa
          └─→ Ufv

TramiteObserver
    ├─→ IdtgbCalculator (cuando se actualiza)
    └─→ ExportarAlSINJob (cuando finaliza)

PagoObserver
    └─→ DashboardCacheInvalidator

TramiteWizardController
    └─→ AjaxController (búsqueda de personas)
```

---

## 📋 ORDEN SUGERIDO DE DOCUMENTACIÓN

**Fase 1 - Críticos (inmediato):**
1. IdtgbCalculator
2. DashboardService
3. DashboardController

**Fase 2 - Importantes (corto plazo):**
4. CalculadoraBeniController
5. ReporteController
6. DashboardCacheInvalidator
7. PagoObserver

**Fase 3 - Utilitarios (medio plazo):**
8. AjaxController
9. StorageController
10. ExportarAlSINJob
11. System Middleware
12. Loggin Middleware

**Fase 4 - Opcionales (largo plazo):**
13. UserController, RoleController
14. ValidacionController
15. SolucionDigitalController
16. ErrorController
17. RegistersUserEvents trait
18. Install command

---

## 🎯 CONCLUSIONES

**Total componentes sin documentar:** 18
**Prioridad alta:** 4
**Prioridad media:** 8
**Prioridad baja:** 6

**Recomendación:** 
Empezar documentando `IdtgbCalculator` y `DashboardService` ya que son el corazón del sistema. Sin estos documentos, cualquier desarrollador nuevo tendrá dificultad para entender la lógica de cálculo de impuestos y el dashboard.

**Próximo paso:** ¿Qué módulo quieres que documente primero?
