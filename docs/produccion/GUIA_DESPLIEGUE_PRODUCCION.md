# Guía de Despliegue a Producción - Sistema IDTGB Beni

## 📋 Resumen Ejecutivo

Esta guía te ayudará a desplegar el sistema IDTGB Beni a producción de forma segura y correcta.

**⚠️ IMPORTANTE:** No ejecutes datos de prueba en producción. Lee esta guía completamente antes de comenzar.

---

## 🚀 Comando de Instalación

### ¿Ejecutar `php artisan example:install`?

**RESPUESTA:** NO para producción ❌

**Razones:**
1. Este comando está diseñado para desarrollo local
2. Pregunta si deseas "Eliminar y recrear la base de datos" (peligroso en producción)
3. Ejecuta `migrate:fresh` que borra TODO (¡pérdida de datos!)
4. Ejecuta `db:seed` con datos de desarrollo

**✅ Alternativa correcta para producción:**
Ejecuta los comandos manualmente y controladamente (ver sección "Pasos para Producción").

---

## 🎯 Configuración de Seeders para Producción

### 1. ¿Qué debe ir en `IdtgbMaestrosSeeder` para Producción?

**Versión CORRECTA para Producción:**

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class IdtgbMaestrosSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Sistema base Voyager (OBLIGATORIO)
            VoyagerDatabaseSeeder::class,

            // 2. Geografía Bolivia (OBLIGATORIO)
            DepartamentoSeeder::class,
            ProvinciaSeeder::class,
            MunicipioSeeder::class,

            // 3. Maestros del sistema (OBLIGATORIO)
            ParentescoSeeder::class,      // Parentescos con tasas
            TipoTransmisionSeeder::class, // Tipos de transmisión
            TipoInmuebleSeeder::class,    // Tipos de inmueble
            TasaSeeder::class,            // Tasas impositivas vigentes
            ExencionSeeder::class,        // Exenciones legales

            // 4. Menús del sistema (OBLIGATORIO)
            IdtgbMenuAppendSeeder::class,

            // 5. UFV - Valores históricos (RECOMENDADO)
            // UfvSeeder::class,          // Datos históricos de UFV
            // O usar UfvApiSeeder para datos actualizados desde API
        ]);
    }
}
```

### 2. ¿Qué NO debe ir en Producción? ❌

**Elimina o comenta estos seeders:**

```php
// ❌ NO INCLUIR EN PRODUCCIÓN:

// Datos de prueba/desarrollo
PeopleBeniSeeder::class,           // Personas ficticias
InmuebleSeeder::class,             // Inmuebles de prueba
AvaluoSeeder::class,               // Avalúos de prueba
CalculadoraDemoSeeder::class,      // Datos demo calculadora

// Trámites de prueba (borra todo si re-ejecutas)
TramiteSeeder::class,              // Trámites ficticios
AdquirenteTramiteSeeder::class,    // Adquirentes de prueba
DisponenteTramiteSeeder::class,    // Disponentes de prueba
TramiteExencionSeeder::class,      // Exenciones de prueba
DocumentoSeeder::class,            // Documentos de prueba
PagoSeeder::class,                 // Pagos de prueba

// Seeders de Voyager Dummy (solo desarrollo)
VoyagerDummyDatabaseSeeder::class, // Posts, páginas, categorías dummy
PostsTableSeeder::class,
PagesTableSeeder::class,
CategoriesTableSeeder::class,
TranslationsTableSeeder::class,    // Si no usas multi-idioma

// Seeders de actualización (ejecutar solo cuando sea necesario)
UpdatePermissionsSeeder::class,    // Solo si actualizas permisos
UpdateBreadSeeder::class,          // Solo si actualizas BREAD
```

### 3. Diferencia entre Entornos

| Tipo de Dato | Desarrollo | Producción |
|--------------|------------|------------|
| **Usuarios admin** | admin@example.com / password | Usuarios reales con contraseñas seguras |
| **Geografía** | ✅ Sí | ✅ Sí |
| **Maestros (tasas, parentescos)** | ✅ Sí | ✅ Sí |
| **Personas** | ✅ Ficticias | ❌ No (solo reales) |
| **Inmuebles** | ✅ Ficticios | ❌ No (solo reales) |
| **Trámites** | ✅ Ficticios | ❌ No (empiezan en 0) |
| **UFV** | ✅ Históricos | ✅ Desde API real |

---

## 📋 Pasos para Producción (Checklist)

### Pre-requisitos en Servidor

- [ ] PHP 8.2+ instalado
- [ ] Composer instalado
- [ ] MySQL/MariaDB configurado
- [ ] Web server (Nginx/Apache) configurado
- [ ] SSL/TLS certificado instalado
- [ ] Dominio configurado y apuntando al servidor

### Paso 1: Preparar el Servidor

```bash
# 1.1 Clonar o subir el proyecto
# Opción A: Clonar desde git
git clone https://tu-repositorio.git /var/www/idtgb-beni
cd /var/www/idtgb-beni

# Opción B: Subir por SFTP/FTP los archivos

# 1.2 Instalar dependencias (sin dev)
composer install --no-dev --optimize-autoloader

# 1.3 Permisos de carpetas
chmod -R 755 storage bootstrap/cache
chmod -R 775 storage/logs storage/app storage/framework
chown -R www-data:www-data /var/www/idtgb-beni
```

### Paso 2: Configurar Variables de Entorno

```bash
# 2.1 Copiar archivo de entorno
cp .env.example .env

# 2.2 Generar key de aplicación
php artisan key:generate

# 2.3 Editar .env con valores de producción
nano .env
```

**Configuración crítica en `.env`:**

```env
# ✅ OBLIGATORIO - Modo producción
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.gob.bo

# ✅ BASE DE DATOS - Configuración real
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=idtgb_produccion
DB_USERNAME=idtgb_user
DB_PASSWORD=TuPasswordSeguro123!

# ✅ SEGURIDAD
APP_KEY=base64:xxxxxxxxxxxx  # Generado automáticamente
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=strict

# ✅ LOGS (desactivar debug)
LOG_LEVEL=error
LOG_CHANNEL=stack

# ✅ CACHE Y OPTIMIZACIÓN
CACHE_DRIVER=redis  # o file/database si no tienes Redis
QUEUE_CONNECTION=database
SESSION_DRIVER=database

# ✅ CORREO (configurar con tu servidor SMTP)
MAIL_MAILER=smtp
MAIL_HOST=smtp.tuserver.gob.bo
MAIL_PORT=587
MAIL_USERNAME=noreply@tudominio.gob.bo
MAIL_PASSWORD=password_seguro
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@tudominio.gob.bo
MAIL_FROM_NAME="GAD Beni - IDTGB"
```

### Paso 3: Base de Datos (MIGRACIÓN INICIAL)

```bash
# ⚠️ IMPORTANTE: Esto crea las tablas, NO borra nada
php artisan migrate --force

# --force es necesario en producción para evitar confirmación
```

**⚠️ ADVERTENCIA:**
- Si la base de datos ya tiene datos, NO uses `migrate:fresh`
- Usa solo `migrate` para aplicar migraciones nuevas
- Si necesitas resetear, haz backup primero

### Paso 4: Seeders de Producción

```bash
# Ejecutar seeders MAESTROS (datos esenciales)
php artisan db:seed --class=IdtgbMaestrosSeeder --force

# Si prefieres usar ProduccionSeeder (ya está preparado):
# php artisan db:seed --class=ProduccionSeeder --force
```

**Orden correcto de ejecución manual (si es necesario):**

```bash
# 1. Voyager Base
php artisan db:seed --class=VoyagerDatabaseSeeder --force

# 2. Geografía
php artisan db:seed --class=DepartamentoSeeder --force
php artisan db:seed --class=ProvinciaSeeder --force
php artisan db:seed --class=MunicipioSeeder --force

# 3. Maestros
php artisan db:seed --class=ParentescoSeeder --force
php artisan db:seed --class=TipoTransmisionSeeder --force
php artisan db:seed --class=TipoInmuebleSeeder --force
php artisan db:seed --class=TasaSeeder --force
php artisan db:seed --class=ExencionSeeder --force

# 4. Menús
php artisan db:seed --class=IdtgbMenuAppendSeeder --force

# 5. UFV (opcional, puedes cargar desde API después)
# php artisan db:seed --class=UfvSeeder --force
```

### Paso 5: Optimización para Producción

```bash
# 5.1 Cache de configuración
php artisan config:cache

# 5.2 Cache de rutas
php artisan route:cache

# 5.3 Cache de vistas
php artisan view:cache

# 5.4 Optimizar autoloader
composer dump-autoload --optimize

# 5.5 Enlace simbólico a storage
php artisan storage:link
```

### Paso 6: Configurar Usuario Administrador

```bash
# Crear usuario admin (opción 1: tinker)
php artisan tinker

# Dentro de tinker:
$user = App\Models\User::create([
    'name' => 'Administrador IDTGB',
    'email' => 'admin@tuinstitucion.gob.bo',
    'password' => bcrypt('TuPasswordMuySeguro123!'),
]);
$user->assignRole('admin');
exit

# Opción 2: Modificar UsersTableSeeder antes de ejecutar
# y asegurar que el email sea institucional
```

### Paso 7: Configurar UFV (Valores Reales)

```bash
# Opción A: Cargar desde API del BCB (recomendado)
php artisan db:seed --class=UfvApiSeeder --force

# Opción B: Importar desde archivo CSV/Excel
# Crear comando personalizado o usar tinker

# Opción C: Actualización automática diaria (cron job)
# Agregar a crontab:
# 0 6 * * * cd /var/www/idtgb-beni && php artisan ufv:update >> /dev/null 2>&1
```

### Paso 8: Configurar Web Server

**Nginx (recomendado):**

```nginx
server {
    listen 80;
    server_name tudominio.gob.bo;
    return 301 https://$server_name$request_uri;  # Redirigir a HTTPS
}

server {
    listen 443 ssl http2;
    server_name tudominio.gob.bo;
    root /var/www/idtgb-beni/public;
    index index.php;

    # SSL
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;

    # Seguridad
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";
    add_header Referrer-Policy "strict-origin-when-cross-origin";

    # Logs
    access_log /var/log/nginx/idtgb-access.log;
    error_log /var/log/nginx/idtgb-error.log;

    # Laravel
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Apache:**

```apache
<VirtualHost *:80>
    ServerName tudominio.gob.bo
    Redirect permanent / https://tudominio.gob.bo/
</VirtualHost>

<VirtualHost *:443>
    ServerName tudominio.gob.bo
    DocumentRoot /var/www/idtgb-beni/public

    SSLEngine on
    SSLCertificateFile /path/to/cert.pem
    SSLCertificateKeyFile /path/to/key.pem

    <Directory /var/www/idtgb-beni/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/idtgb-error.log
    CustomLog ${APACHE_LOG_DIR}/idtgb-access.log combined
</VirtualHost>
```

### Paso 9: Verificación Final

```bash
# 9.1 Verificar estado de Laravel
php artisan about

# 9.2 Verificar conexión a base de datos
php artisan tinker --execute="DB::connection()->getPdo(); echo 'Conexión OK'"

# 9.3 Verificar que no hay errores en logs
tail -f storage/logs/laravel.log

# 9.4 Probar la aplicación
# Abrir navegador: https://tudominio.gob.bo
```

---

## 🔒 Seguridad Importante

### Configuraciones de Seguridad Obligatorias

```bash
# 1. Proteger archivos sensibles
chmod 640 .env
chmod 644 public/index.php
chmod 755 public

# 2. Denegar acceso a carpetas sensibles en servidor web
# (ya cubierto en configuración Nginx/Apache arriba)

# 3. Desactivar listado de directorios
# Agregar en .htaccess para Apache:
Options -Indexes

# 4. Configurar CSP (Content Security Policy) headers
# En middleware o configuración de servidor
```

### Usuarios y Contraseñas

**Usuario Admin Inicial:**
- Usar correo institucional real
- Contraseña mínimo 12 caracteres
- Cambiar contraseña en primer login
- Habilitar 2FA si está disponible

**Nunca usar en producción:**
- ❌ `admin@example.com`
- ❌ `password`
- ❌ `12345678`
- ❌ Usuarios de prueba

---

## 🔄 Actualizaciones Post-Despliegue

### Para aplicar actualizaciones:

```bash
# 1. Modo mantenimiento
php artisan down

# 2. Actualizar código
git pull origin main

# 3. Actualizar dependencias
composer install --no-dev --optimize-autoloader

# 4. Aplicar migraciones
php artisan migrate --force

# 5. Limpiar y recrear caches
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan config:cache
php artisan view:cache
php artisan route:cache

# 6. Salir de mantenimiento
php artisan up
```

---

## 📊 Monitoreo y Mantenimiento

### Tareas Cron (crontab -e)

```bash
# Actualización diaria de UFV (6:00 AM)
0 6 * * * cd /var/www/idtgb-beni && php artisan ufv:update >> /dev/null 2>&1

# Backup diario de base de datos (2:00 AM)
0 2 * * * mysqldump -u idtgb_user -p'TuPassword' idtgb_produccion > /backups/idtgb-$(date +\%Y\%m\%d).sql

# Limpieza de logs antiguos (mensual)
0 3 1 * * find /var/www/idtgb-beni/storage/logs -name "*.log" -mtime +30 -delete
```

### Comandos Útiles de Mantenimiento

```bash
# Ver espacio en disco
df -h

# Ver tamaño de logs
du -sh storage/logs

# Limpiar logs antiguos (manual)
php artisan log:clear  # Si tienes un comando para esto
# o
rm storage/logs/laravel-*.log

# Ver errores recientes
tail -n 100 storage/logs/laravel.log
```

---

## 🆘 Solución de Problemas Comunes

### Error 500 - Internal Server Error

```bash
# Ver logs
sudo tail -f /var/log/nginx/error.log
tail -f storage/logs/laravel.log

# Verificar permisos
sudo chown -R www-data:www-data /var/www/idtgb-beni
sudo chmod -R 755 /var/www/idtgb-beni/storage

# Limpiar caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Error de Permisos en Storage

```bash
# Resetear permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage
sudo chmod -R 755 bootstrap/cache
```

### Error de Base de Datos

```bash
# Verificar conexión
php artisan tinker --execute="DB::connection()->getPdo()"

# Ver migraciones pendientes
php artisan migrate:status

# Si es necesario, ejecutar migraciones específicas
php artisan migrate --path=database/migrations/2026_01_17_xxxx_nombre.php --force
```

### Error de CSRF Token

```bash
# Limpiar cookies del navegador
# O regenerar key (⚠️ invalida sesiones activas)
php artisan key:generate --force
```

---

## 📞 Contacto y Soporte

Si encuentras problemas durante el despliegue:

1. Revisar logs de Laravel: `storage/logs/laravel.log`
2. Revisar logs del servidor web: `/var/log/nginx/` o `/var/log/apache2/`
3. Verificar configuración: `php artisan about`
4. Consultar documentación oficial: https://laravel.com/docs/10.x/deployment

---

## ✅ Checklist Final

Antes de dar por terminado el despliegue:

- [ ] Todos los pasos completados
- [ ] Sitio accesible por HTTPS
- [ ] Login funciona con usuario admin
- [ ] Calculadora pública funciona
- [ ] Wizard de trámites funciona
- [ ] No hay errores en logs
- [ ] Backups configurados
- [ ] Certificado SSL vigente
- [ ] Contraseñas cambiadas de los defaults
- [ ] Modo debug desactivado (APP_DEBUG=false)
- [ ] Caches activados (config, routes, views)

---

**¡Éxito en tu despliegue! 🚀**

Documento creado: Febrero 2026
Sistema: IDTGB Beni v2.0
