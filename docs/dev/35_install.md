# Install Command

## Ubicación
**Archivo:** `app/Console/Commands/Install.php`

## Descripción
Comando de Artisan para la instalación inicial del proyecto. Automatiza la configuración básica necesaria para poner en marcha el sistema.

## Firma del Comando
```bash
php artisan example:install
```

## Funcionalidades

### 1. Creación del archivo .env
- Verifica si existe el archivo `.env`
- Si no existe, lo crea copiando desde `.env.example`
- Muestra mensaje de confirmación

### 2. Generación de APP_KEY
- Ejecuta el comando `php artisan key:generate`
- Genera una clave de aplicación única

### 3. Configuración de Base de Datos
- **Pregunta interactiva:** ¿Eliminar y recrear la base de datos?
- Si responde `yes`:
  - Ejecuta `migrate:fresh` - Elimina todas las tablas y vuelve a ejecutar migraciones
  - Ejecuta `db:seed` - Ejecuta los seeders para poblar la base de datos

### 4. Enlace Simbólico de Storage
- Ejecuta `php artisan storage:link`
- Crea el enlace desde `public/storage` hacia `storage/app/public`

### 5. Voyager (Comentado)
Código preparado para publicar assets de Voyager (actualmente comentado):
```php
$this->call('vendor:publish', [
    '--provider' => 'TCG\\Voyager\\VoyagerServiceProvider',
    '--tag' => ['config', 'voyager_avatar']
]);
```

## Flujo de Ejecución

```
Inicio
  ↓
Verificar .env
  ↓
Generar APP_KEY
  ↓
[Pregunta] ¿Eliminar y recrear DB?
  ├─ YES → migrate:fresh → db:seed
  └─ NO  → Continuar
  ↓
Crear storage:link
  ↓
Fin
```

## Uso

### Ejecución básica
```bash
php artisan example:install
```

### Ejemplo de salida
```
Iniciando instalación...
.env creado desde .env.example
Application key set successfully.
¿Eliminar y recrear la base de datos? (yes/no) [no]:
  > yes
Database seeding completed successfully.
The [public/storage] directory has been linked.
✅ Instalación completada. ¡Gracias por usar LaravelTemplate!
```

## Requisitos Previos

Antes de ejecutar este comando, asegúrate de:

1. **Base de datos configurada:** Tener las credenciales de DB configuradas en `.env` (antes de ejecutar o manualmente después)
2. **Permisos de escritura:** Tener permisos para crear archivos en el directorio raíz
3. **Conexión a DB:** Tener acceso al servidor de base de datos
4. **PHP extensions:** Tener las extensiones PHP requeridas por Laravel

## Casos de Uso

### Instalación inicial de proyecto nuevo
```bash
# Clonar proyecto
git clone <repo-url>
cd impuestos

# Ejecutar instalación
php artisan example:install
```

### Reinstalación completa
```bash
# Responder YES a la pregunta de DB
php artisan example:install
```

### Solo configurar sin tocar DB
```bash
# Responder NO a la pregunta de DB
php artisan example:install
```

## Notas Importantes

- **Cuidado con migrate:fresh:** Esta operación elimina TODOS los datos existentes en la base de datos
- **Seeders:** Asegúrate de tener seeders configurados en `database/seeders/DatabaseSeeder.php`
- **Voyager:** Si usas Voyager, descomenta el bloque correspondiente en el código del comando
- **Entorno de producción:** No usar en producción si ya hay datos importantes en la DB

## Dependencias del Proyecto

Este comando utiliza:
- `Illuminate\Console\Command` - Base del comando
- `file_exists()` - Verificación de archivos
- `copy()` - Copia de archivos
- `$this->call()` - Ejecución de otros comandos Artisan
- `$this->confirm()` - Interacción con usuario
- `$this->info()` - Mensajes informativos

## Comandos Invocados Internamente

| Comando | Descripción | Condición |
|---------|-------------|-----------|
| `key:generate` | Genera APP_KEY | Siempre |
| `migrate:fresh` | Recrea DB | Si responde YES |
| `db:seed` | Puebla DB | Si responde YES |
| `storage:link` | Enlace storage | Siempre |

## Personalización

Para adaptar este comando a necesidades específicas:

```php
public function handle()
{
    // Agregar pasos personalizados aquí
    
    $this->call('custom:command');
    
    // Modificar mensajes
    $this->info('Mensaje personalizado');
}
```

## Testing

Para probar el comando:
```bash
# Simular instalación
php artisan example:install --help

# Probar en entorno local
php artisan example:install
```

## Archivos Relacionados

- `.env.example` - Plantilla de configuración
- `database/migrations/` - Definiciones de tablas
- `database/seeders/DatabaseSeeder.php` - Datos iniciales
- `public/` - Directorio público donde se crea el link
- `storage/app/public/` - Directorio de almacenamiento público

---

## 🐛 BUGS IDENTIFICADOS

### 1. Inconsistencia con Voyager
**Ubicación:** `app/Console/Commands/Install.php:30-34`
**Descripción:** El código de Voyager está comentado pero el proyecto SÍ usa Voyager
**Problema:** Los assets de Voyager no se publican correctamente durante la instalación
**Impacto:** 
- Voyager puede no funcionar correctamente
- Faltan configuraciones de Voyager
- Las imágenes de avatares pueden no estar disponibles
**Evidencia:**
- `composer.json:17` tiene `"tcg/voyager": "^1.7"`
- `database/seeders/DatabaseSeeder.php:17` llama a `VoyagerDatabaseSeeder`
- Múltiples seeder de Voyager en `database/seeders/`
**Solución:** Descomentar el bloque de vendor:publish para Voyager

### 2. Dependencia circular de configuración
**Ubicación:** `app/Console/Commands/Install.php:21`
**Descripción:** `key:generate` se ejecuta ANTES de que el .env tenga las credenciales de DB configuradas
**Problema:** Si el usuario responde YES a recrear DB y las credenciales en .env.example no son válidas, el comando falla
**Impacto:** El proceso de instalación puede fallar parcialmente
**Flujo problemático:**
1. Se crea .env desde .env.example (DB: laravel, user: root, pass: empty)
2. Se genera APP_KEY
3. Usuario responde YES para migrate:fresh
4. ¡ERROR! si la DB "laravel" no existe o las credenciales son incorrectas
**Solución:** Agregar verificación de conexión a DB antes de migrar

### 3. Falta de validación de entorno
**Ubicación:** `app/Console/Commands/Install.php:23`
**Descripción:** No se verifica si estamos en entorno de producción
**Problema:** El comando podría destruir datos en producción accidentalmente
**Impacto:** 
- Pérdida total de datos de producción
- Riesgo de ejecutar migrate:fresh en producción
**Solución:** Bloquear migrate:fresh si APP_ENV=production

### 4. Falta de verificación de storage:link
**Ubicación:** `app/Console/Commands/Install.php:28`
**Descripción:** No se verifica si el enlace simbólico ya existe
**Problema:** Puede generar advertencias si el link ya existe
**Impacto:** Menor, solo advertencias en consola
**Solución:** Verificar si el link existe antes de crearlo

### 5. Falta de rollback en caso de error
**Ubicación:** `app/Console/Commands/Install.php:12-37` (método handle completo)
**Descripción:** Si algún paso falla, no hay limpieza de cambios parciales
**Problema:** El sistema puede quedar en estado inconsistente
**Escenario:** 
1. .env creado ✓
2. APP_KEY generada ✓
3. migrate:fresh iniciado ✗ FALLA
4. storage:link NO se ejecuta
5. ¡Estado inconsistente!
**Solución:** Implementar try-catch con cleanup

### 6. Nombre del comando no descriptivo
**Ubicación:** `app/Console/Commands/Install.php:9`
**Descripción:** El comando se llama `example:install` pero es para el proyecto de impuestos
**Problema:** No es intuitivo para el desarrollador
**Impacto:** Confusión al buscar el comando correcto
**Solución:** Cambiar a `impuestos:install` o `sistema:install`

### 7. Mensaje final genérico
**Ubicación:** `app/Console/Commands/Install.php:36`
**Descripción:** El mensaje dice "LaravelTemplate" pero el proyecto es de impuestos
**Problema:** Información desactualizada/confusa
**Impacto:** Menor, solo confusión visual
**Solución:** Actualizar mensaje con nombre real del proyecto

### 8. Falta de permisos en Linux/Unix
**Ubicación:** Todo el método handle
**Descripción:** No se establecen permisos de escritura en storage y cache
**Problema:** En Linux, el comando puede fallar por falta de permisos
**Impacto:** Error de permisos al escribir archivos
**Evidencia:** README.md:17-18 menciona chmod y chown manual
**Solución:** Agregar verificación y/o set de permisos (solo en Unix)

### 9. No se verifica si composer install se ejecutó
**Ubicación:** Todo el método handle
**Descripción:** No hay verificación de que las dependencias estén instaladas
**Problema:** Si no se ejecutó composer install, el comando fallará silenciosamente
**Impacto:** Errores difíciles de diagnosticar
**Solución:** Verificar existencia de vendor/autoload.php

### 10. Storage link en Windows
**Ubicación:** `app/Console/Commands/Install.php:28`
**Descripción:** En Windows 7/Server 2008 no funcionan los symlinks
**Problema:** El comando falla en sistemas Windows antiguos
**Impacto:** Error en Windows 7/Server 2008
**Evidencia:** Git Bash muestra el link como `lrwxrwxrwx` (ya creado)
**Solución:** Verificar si soporta symlinks o usar alternativa

---

## 💡 POSIBLES MEJORAS

### 1. Agregar verificación de conexión a DB
**Ubicación:** `app/Console/Commands/Install.php:23` (antes de migrate:fresh)
**Descripción:** Verificar que se puede conectar a la DB antes de migrar
**Código sugerido:**
```php
try {
    DB::connection()->getPdo();
    $this->info('Conexión a base de datos exitosa');
} catch (\Exception $e) {
    $this->error('No se puede conectar a la base de datos');
    $this->error('Verifica las credenciales en .env');
    return 1;
}
```
**Beneficio:** Mensajes de error más claros y específicos

### 2. Protección para producción
**Ubicación:** `app/Console/Commands/Install.php:12` (inicio del método)
**Descripción:** Bloquear operaciones destructivas en producción
**Código sugerido:**
```php
if (app()->environment('production')) {
    $this->error('Este comando no debe ejecutarse en producción');
    return 1;
}
```
**Beneficio:** Evita destrucción accidental de datos

### 3. Verificar existencia del enlace simbólico
**Ubicación:** `app/Console/Commands/Install.php:28`
**Descripción:** Crear el link solo si no existe
**Código sugerido:**
```php
$linkPath = public_path('storage');
if (!file_exists($linkPath)) {
    $this->call('storage:link');
} else {
    $this->info('El enlace de storage ya existe');
}
```
**Beneficio:** Evita advertencias redundantes

### 4. Agregar opción --force
**Ubicación:** `app/Console/Commands/Install.php:9`
**Descripción:** Permitir ejecución no-interactiva
**Código sugerido:**
```php
protected $signature = 'example:install {--force : Ejecutar sin confirmaciones}';
```
**Beneficio:** Permite automatización en CI/CD

### 5. Validar dependencias de PHP
**Ubicación:** `app/Console/Commands/Install.php:12`
**Descripción:** Verificar que las extensiones requeridas estén instaladas
**Extensiones requeridas (README.md:26):** mbstring, intl, dom, gd, xml, zip, curl, mysql
**Código sugerido:**
```php
$requiredExtensions = ['mbstring', 'intl', 'dom', 'gd', 'xml', 'zip', 'curl', 'pdo_mysql'];
$missing = array_filter($requiredExtensions, fn($ext) => !extension_loaded($ext));

if (!empty($missing)) {
    $this->error('Faltan extensiones de PHP: ' . implode(', ', $missing));
    return 1;
}
```
**Beneficio:** Diagnóstico temprano de problemas de dependencias

### 6. Mostrar progreso de seeders
**Ubicación:** `app/Console/Commands/Install.php:25`
**Descripción:** Mostrar qué seeder se está ejecutando
**Código sugerido:**
```php
$this->call('db:seed', [
    '--class' => 'DatabaseSeeder',
    '--force' => true
]);
```
**Beneficio:** Mejor visibilidad del progreso

### 7. Agregar opción --seed-only
**Ubicación:** `app/Console/Commands\Install.php:9`
**Descripción:** Permitir solo seeders sin migrar
**Código sugerido:**
```php
protected $signature = 'example:install {--seed-only : Solo ejecutar seeders}';
```
**Beneficio:** Flexibilidad para diferentes escenarios

### 8. Crear logs de instalación
**Ubicación:** `app/Console\Commands\Install.php:12`
**Descripción:** Guardar registro de instalación para debugging
**Código sugerido:**
```php
$logFile = storage_path('logs/install.log');
$startTime = now();

// ... código de instalación ...

$duration = now()->diffInSeconds($startTime);
file_put_contents($logFile, "Instalación completada en {$duration} segundos\n", FILE_APPEND);
```
**Beneficio:** Historial de instalaciones para debugging

### 9. Verificar composer dependencies
**Ubicación:** `app/Console\Commands\Install.php:12`
**Descripción:** Verificar que vendor/autoload.php existe
**Código sugerido:**
```php
if (!file_exists(base_path('vendor/autoload.php'))) {
    $this->error('Dependencias no instaladas. Ejecuta: composer install');
    return 1;
}
```
**Beneficio:** Mensaje de error claro y específico

### 10. Agregar rollback en caso de error
**Ubicación:** `app/Console\Commands\Install.php:12`
**Descripción:** Implementar cleanup si falla algún paso
**Código sugerido:**
```php
try {
    // pasos de instalación
} catch (\Exception $e) {
    $this->error('Error durante instalación: ' . $e->getMessage());
    // cleanup: eliminar .env si se creó, etc.
    return 1;
}
```
**Beneficio:** No deja sistema en estado inconsistente

---

## ❌ FALTAS COSAS

### 1. Falta publicación de Voyager assets
**Ubicación:** `app/Console\Commands\Install.php:30-34`
**Descripción:** Los assets de Voyager no se publican (están comentados)
**Lo que falta:**
```php
$this->call('vendor:publish', [
    '--provider' => 'TCG\\Voyager\\VoyagerServiceProvider',
    '--tag' => ['config', 'voyager_avatar']
]);
```
**Por qué es importante:**
- Voyager necesita sus configuraciones
- Los avatares por defecto no estarán disponibles
- El panel de administración puede fallar
**Ubicación del servicio:** `vendor/tcg/voyager/src/VoyagerServiceProvider.php`

### 2. Falta ejecución de cache:clear
**Ubicación:** `app/Console\Commands\Install.php:28` (después de storage:link)
**Descripción:** No se limpia el cache de Laravel después de la instalación
**Lo que falta:**
```php
$this->call('cache:clear');
$this->call('config:clear');
$this->call('route:clear');
$this->call('view:clear');
```
**Por qué es importante:**
- Asegura que se cargen nuevas configuraciones
- Evita problemas con cache obsoleto
- Previene errores inesperados

### 3. Falta verificación de storage permissions
**Ubicación:** `app/Console\Commands\Install.php:28` (antes de storage:link)
**Descripción:** No se verifican permisos de escritura en storage
**Lo que falta:**
```php
$storagePath = storage_path('app/public');
if (!is_writable($storagePath)) {
    $this->error('No hay permisos de escritura en storage');
    $this->info('Ejecuta: chmod -R 775 storage');
    return 1;
}
```
**Por qué es importante:** Previene errores de permisos en Unix/Linux

### 4. Falta creación de directorios necesarios
**Ubicación:** `app/Console\Commands\Install.php:28` (antes de storage:link)
**Descripción:** No se verifican/crean directorios de storage
**Lo que falta:**
```php
$directories = [
    storage_path('app/public'),
    storage_path('framework/cache'),
    storage_path('framework/sessions'),
    storage_path('framework/views'),
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}
```
**Por qué es importante:** Previene errores por directorios faltantes

### 5. Falta migración específica para el proyecto
**Ubicación:** `app/Console\Commands\Install.php:24`
**Descripción:** No hay validación de que las migraciones del proyecto se ejecutan
**Evidencia:** Existen 33 migraciones en database/migrations/
**Lo que falta:** Verificar número de migraciones ejecutadas
**Por qué es importante:** Asegura que toda la estructura de DB está creada

### 6. Falta verificación de seeders del proyecto
**Ubicación:** `app/Console\Commands\Install.php:25`
**Descripción:** DatabaseSeeder solo llama a VoyagerDatabaseSeeder y UsersTableSeeder
**Evidencia:** Existen 37 seeders pero la mayoría están comentados en DatabaseSeeder.php
**Lo que falta:**
- PeopleSeeder
- TasaSeeder
- MunicipioSeeder
- Y otros 28 seeders más
**Por qué es importante:** El sistema no tendrá datos básicos de impuestos

### 7. Falta configuración de URL base
**Ubicación:** `app/Console\Commands\Install.php:16-19` (al crear .env)
**Descripción:** El .env.example tiene APP_URL=http://localhost
**Lo que falta:** Preguntar o configurar la URL correcta del proyecto
**Por qué es importante:** Links generados pueden ser incorrectos

### 8. Falta creación de usuario administrador
**Ubicación:** `app/Console\Commands\Install.php:25`
**Descripción:** No se asegura la creación de un usuario admin
**Evidencia:** UsersTableSeeder existe pero puede no tener un admin
**Lo que falta:** Verificar o crear usuario admin por defecto
**Por qué es importante:** Sin admin no se puede acceder al panel de Voyager

### 9. Falta documentación de post-instalación
**Ubicación:** `app/Console\Commands\Install.php:36`
**Descripción:** El mensaje final no indica siguientes pasos
**Lo que falta:**
- URL para acceder al admin
- Credenciales por defecto
- Comandos adicionales necesarios
**Por qué es importante:** Guía al usuario en siguientes pasos

### 10. Falta verificación de .env.example
**Ubicación:** `app/Console\Commands\Install.php:16`
**Descripción:** No se verifica que .env.example existe
**Lo que falta:**
```php
if (!file_exists('.env.example')) {
    $this->error('.env.example no encontrado');
    return 1;
}
```
**Por qué es importante:** Previene error si el archivo no existe

---

## ⚡ OPTIMIZACIONES

### 1. Paralelizar seeders
**Ubicación:** `app/Console\Commands\Install.php:25`
**Descripción:** Ejecutar seeders en paralelo cuando sea posible
**Optimización:** Usar procesos en background o queue system
**Beneficio:** Reduce tiempo de instalación en proyectos grandes
**Implementación sugerida:**
```php
// Usar queues para seeders independientes
$this->call('db:seed', ['--class' => 'VoyagerDatabaseSeeder']);
// ... otros seeders
```

### 2. Cachear configuraciones de Voyager
**Ubicación:** Después de `app/Console\Commands\Install.php:34` (si se descomenta)
**Descripción:** Generar cache de configuraciones de Voyager para mejor rendimiento
**Optimización:**
```php
$this->call('config:cache');
$this->call('route:cache');
```
**Beneficio:** Mejora performance inicial del sistema

### 3. Optimizar vendor:publish de Voyager
**Ubicación:** `app/Console\Commands\Install.php:31-34`
**Descripción:** Solo publicar archivos necesarios en vez de todos
**Optimización:** Usar tags específicos en lugar de publicar todo
**Beneficio:** Reducir tiempo de instalación y tamaño de archivos

### 4. Verificar storage link de forma eficiente
**Ubicación:** `app/Console\Commands\Install.php:28`
**Descripción:** Usar symlink() directamente en vez de storage:link command
**Optimización:**
```php
$target = storage_path('app/public');
$link = public_path('storage');
if (!is_link($link)) {
    symlink($target, $link);
}
```
**Beneficio:** Más rápido y menos overhead de Artisan

### 5. Lazy-load de seeders
**Ubicación:** `app/Console\Commands\Install.php:25`
**Descripción:** Solo ejecutar seeders necesarios según entorno
**Optimización:**
```php
if (app()->environment('local')) {
    $this->call('CalculadoraDemoSeeder');
}
```
**Beneficio:** Evita cargar datos innecesarios en producción

### 6. Validación de APP_KEY
**Ubicación:** `app/Console\Commands\Install.php:21`
**Descripción:** Verificar si APP_KEY ya existe antes de regenerar
**Optimización:**
```php
if (empty(config('app.key')) || strlen(config('app.key')) !== 32) {
    $this->call('key:generate');
} else {
    $this->info('APP_KEY ya existe');
}
```
**Beneficio:** Evita regeneración innecesaria

### 7. Progreso visual con barra
**Ubicación:** Todo el método handle
**Descripción:** Agregar barra de progreso visual
**Optimización:**
```php
$this->output->createProgressBar($totalSteps);
// ... pasos ...
$this->output->progressAdvance();
```
**Beneficio:** Mejor UX para el desarrollador

### 8. Memoización de verificaciones
**Ubicación:** `app/Console\Commands\Install.php:12`
**Descripción:** Cachear resultados de verificaciones repetidas
**Beneficio:** Menos overhead en verificaciones múltiples

### 9. Usar flags de Artisan
**Ubicación:** `app/Console\Commands\Install.php:24-25`
**Descripción:** Usar flags --force en comandos Artisan para evitar interacciones
**Optimización:**
```php
$this->call('migrate:fresh', ['--force' => true]);
$this->call('db:seed', ['--force' => true]);
```
**Beneficio:** Evita prompts interactivos dentro de comandos

### 10. Minimizar salida de consola
**Ubicación:** Todo el método handle
**Descripción:** Reducir verbosidad usando -q o --quiet cuando corresponda
**Optimización:**
```php
$this->call('key:generate', ['--quiet' => true]);
```
**Beneficio:** Salida más limpia y más rápida

---

## 📊 RESUMEN DE PROBLEMAS POR CATEGORÍA

### Bugs Críticos (Deben corregirse YA)
1. **Inconsistencia con Voyager** - Assets no se publican
2. **Dependencia circular de configuración** - DB credentials antes de migrar
3. **Falta de validación de entorno** - Puede destruir producción

### Bugs Importantes (Deben corregirse pronto)
4. **Falta de rollback** - Sistema inconsistente en errores
5. **Falta verificación de composer** - Errores difíciles de diagnosticar
6. **Storage link en Windows** - Falla en sistemas antiguos

### Bugs Menores (Pueden esperar)
7. **Nombre del comando no descriptivo**
8. **Mensaje final genérico**
9. **Falta de verificación de storage:link**

### Mejoras Críticas (Deben implementarse pronto)
1. **Agregar verificación de conexión a DB**
2. **Protección para producción**
3. **Publicación de Voyager assets**

### Mejoras Importantes (Deberían implementarse)
4. **Validar dependencias de PHP**
5. **Agregar opción --force**
6. **Verificar composer dependencies**
7. **Crear logs de instalación**

### Mejoras Útiles (Buenas de tener)
8. **Verificar existencia del enlace simbólico**
9. **Mostrar progreso de seeders**
10. **Agregar opción --seed-only**

### Faltas Críticas (Bloquean funcionalidad)
1. **Falta publicación de Voyager assets**
2. **Falta verificación de seeders del proyecto** (solo 2 de 37 ejecutan)
3. **Falta creación de usuario administrador**

### Faltas Importantes (Causan problemas)
4. **Falta ejecución de cache:clear**
5. **Falta verificación de storage permissions**
6. **Falta creación de directorios necesarios**

### Faltas Menores (Afectan experiencia)
7. **Falta configuración de URL base**
8. **Falta documentación de post-instalación**
9. **Falta verificación de .env.example**

### Optimizaciones de Alto Impacto
1. **Paralelizar seeders** - Ahorra tiempo significativo
2. **Cachear configuraciones de Voyager** - Mejora performance
3. **Validación de APP_KEY** - Evita regeneración innecesaria

### Optimizaciones de Impacto Medio
4. **Verificar storage link de forma eficiente**
5. **Lazy-load de seeders**
6. **Usar flags de Artisan**

### Optimizaciones de Bajo Impacto
7. **Optimizar vendor:publish de Voyager**
8. **Progreso visual con barra**
9. **Memoización de verificaciones**
10. **Minimizar salida de consola**

---

## 🔗 UBICACIONES DE ARCHIVOS CLAVE

| Archivo | Ubicación | Propósito |
|---------|-----------|-----------|
| Comando Install | `app/Console/Commands/Install.php` | Comando principal |
| Seeder principal | `database/seeders/DatabaseSeeder.php` | Solo llama a 2 de 37 seeders |
| Seeder Voyager | `database/seeders/VoyagerDatabaseSeeder.php` | Configuración de Voyager |
| Voyager config | `config/voyager.php` | Configuración del admin panel |
| .env.example | `.env.example` | Plantilla de configuración |
| Composer | `composer.json:17` | Dependencia tcg/voyager |
| README | `README.md` | Documentación de instalación |
| Storage link | `public/storage` | Enlace simbólico ya creado |
| Tests | `tests/Feature/`, `tests/Unit/` | No hay tests para Install command |

---

## 🎯 PRIORIDAD DE IMPLEMENTACIÓN

### Inmediato (Bloquea instalación funcional)
1. Descomentar y publicar assets de Voyager
2. Agregar protección para APP_ENV=production
3. Verificar conexión a DB antes de migrate:fresh
4. Asegurar seeders necesarios en DatabaseSeeder

### Corto Plazo (Mejora experiencia)
5. Implementar try-catch con rollback
6. Validar extensiones PHP
7. Agregar verificación de composer install
8. Verificar permisos de storage

### Medio Plazo (Optimizaciones)
9. Agregar opción --force para automatización
10. Implementar cache:clear post-instalación
11. Crear logs de instalación
12. Mejorar mensajes de error

### Largo Plazo (Nice-to-have)
13. Paralelizar seeders
14. UI de progreso visual
15. Cambiar nombre del comando
16. Agregar tests automatizados
