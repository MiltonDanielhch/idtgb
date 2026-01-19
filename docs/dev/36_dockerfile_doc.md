# Dockerfile

## Ubicación
**Archivo:** `Dockerfile` (raíz del proyecto)

## Descripción
Dockerfile para construir una imagen Docker del proyecto Laravel usando Nginx Unit como servidor web. La imagen está basada en `unit:1.33.0-php8.2` e incluye todas las extensiones de PHP necesarias para el proyecto.

## Estado Actual
**ESTADO:** FUNCIONAL - Imagen construida y en uso

---

## Código Actual

```dockerfile
FROM unit:1.33.0-php8.2

RUN apt update && apt install -y \
    curl unzip git libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pcntl opcache pdo pdo_mysql intl zip gd exif ftp bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=512M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/example

RUN mkdir -p /var/www/example/storage /var/www/example/bootstrap/cache

RUN chown -R unit:unit /var/www/example/storage bootstrap/cache && chmod -R 775 /var/www/example/storage

COPY . .

RUN chown -R unit:unit storage bootstrap/cache && chmod -R 775 storage bootstrap/cache

RUN composer install --prefer-dist --optimize-autoloader --no-interaction

COPY unit.json /docker-entrypoint.d/unit.json

COPY .env.example .env

RUN php artisan key:generate

RUN php artisan storage:link

EXPOSE 8000

CMD ["unitd", "--no-daemon"]
```

---

## Configuración de Nginx Unit

### Archivo unit.json
**Ubicación:** `unit.json` (raíz del proyecto)

```json
{
    "listeners": {
        "*:8000": {
            "pass": "routes"
        }
    },

    "routes": [
        {
            "match": {
                "uri": "!/index.php"
            },
            "action": {
                "share": "/var/www/example/public$uri",
                "fallback": {
                    "pass": "applications/laravel"
                }
            }
        }
    ],

    "applications": {
        "laravel": {
            "type": "php",
            "root": "/var/www/example/public/",
            "script": "index.php"
        }
    }
}
```

**Descripción de la configuración:**
- **Listener:** Escucha en puerto 8000 en todas las interfaces
- **Routes:** Sirve archivos estáticos directamente, pasa el resto a PHP
- **Application:** Ejecuta `index.php` con PHP-FPM
- **Directorio raíz:** `/var/www/example/public/`

---

## Uso del Dockerfile

### Construcción de la imagen
```bash
docker build -t example .
```

### Ejecución básica
```bash
docker run -e DB_DATABASE=example -e DB_HOST=host.docker.internal -p 8000:8000 -t example
```

### Ejecución con variables de Solución Digital
```bash
docker run \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=example \
  -e DB_USERNAME=root \
  -e DB_CONNECTION_SOLUCION_DIGITAL=mysql \
  -e DB_HOST_SOLUCION_DIGITAL=host.docker.internal \
  -e DB_PORT_SOLUCION_DIGITAL=3306 \
  -e DB_DATABASE_SOLUCION_DIGITAL=soluciondigital \
  -e DB_USERNAME_SOLUCION_DIGITAL=root \
  -p 8000:8000 \
  -t example
```

---

## Dependencias del Proyecto

### Extensiones de PHP instaladas
| Extensión | Versión | Propósito |
|-----------|----------|-----------|
| pcntl | Latest | Control de procesos |
| opcache | Latest | Caché de OPcode |
| pdo | Latest | Abstracción de base de datos |
| pdo_mysql | Latest | Driver de MySQL |
| intl | Latest | Internacionalización |
| zip | Latest | Manejo de archivos ZIP |
| gd | Latest | Manipulación de imágenes |
| exif | Latest | Metadatos de imágenes |
| ftp | Latest | Cliente FTP |
| bcmath | Latest | Matemáticas de precisión arbitraria |
| redis | Latest | Cliente de Redis |

### Librerías del sistema instaladas
| Librería | Propósito |
|-----------|-----------|
| curl | Cliente HTTP |
| unzip | Descompresión de archivos |
| git | Sistema de control de versiones |
| libicu-dev | Internacionalización |
| libzip-dev | Manejo de archivos ZIP |
| libpng-dev | Soporte PNG |
| libjpeg-dev | Soporte JPEG |
| libfreetype6-dev | Fuentes TrueType |
| libssl-dev | Soporte SSL/TLS |

---

## Configuración de PHP

### Archivo custom.ini
**Ubicación:** `/usr/local/etc/php/conf.d/custom.ini` (dentro del contenedor)

```ini
opcache.enable=1
opcache.jit=tracing
opcache.jit_buffer_size=256M
memory_limit=512M
upload_max_filesize=64M
post_max_size=64M
```

**Descripción de directivas:**

| Directiva | Valor | Descripción |
|-----------|-------|-------------|
| opcache.enable | 1 | Habilita OPcache |
| opcache.jit | tracing | Habilita compilación JIT en modo tracing |
| opcache.jit_buffer_size | 256M | Tamaño del buffer JIT (muy grande) |
| memory_limit | 512M | Límite de memoria de PHP |
| upload_max_filesize | 64M | Tamaño máximo de subida de archivos |
| post_max_size | 64M | Tamaño máximo de POST |

---

## Estructura del Contenedor

### Directorios
| Ruta | Propósito |
|-------|-----------|
| `/var/www/example/` | Raíz del proyecto Laravel |
| `/var/www/example/public/` | Directorio público |
| `/var/www/example/storage/` | Almacenamiento de Laravel |
| `/var/www/example/bootstrap/cache/` | Caché de Laravel |
| `/usr/local/bin/composer` | Ejecutable de Composer |
| `/usr/local/etc/php/conf.d/custom.ini` | Configuración de PHP |
| `/docker-entrypoint.d/unit.json` | Configuración de Nginx Unit |

---

## Versión de PHP y Framework

| Componente | Versión |
|------------|----------|
| Imagen base | `unit:1.33.0-php8.2` |
| PHP | 8.2 |
| Nginx Unit | 1.33.0 |
| Laravel | 10.0.0 (según composer.json:12) |
| PHP requerido | ^8.2 (según composer.json:8) |

---

## Archivos Relacionados

| Archivo | Ubicación | Propósito |
|---------|-----------|-----------|
| Dockerfile | `Dockerfile` | Definición de imagen Docker |
| unit.json | `unit.json` | Configuración de Nginx Unit |
| composer.json | `composer.json` | Dependencias del proyecto |
| .env.example | `.env.example` | Plantilla de variables de entorno |
| .gitignore | `.gitignore` | Archivos ignorados por Git |
| README | `README.md` | Documentación de uso |

---

## 🐛 BUGS IDENTIFICADOS

### 1. Error tipográfico en README: host.docker.internal
**Ubicación:** `README.md:37`

**Descripción:** El README usa `host.docker.internal` que es correcto, pero hay inconsistencia en documentación

**Problema:**
- `host.docker.internal` es el formato correcto para Docker Desktop en Mac/Windows
- Algunos archivos de documentación usan variaciones incorrectas
- Puede causar confusión

**Código actual:**
```bash
docker run -e DB_DATABASE=example -e DB_HOST=host.docker.internal -p 8000:8000 -t example
```

**Formatos encontrados en la documentación:**
- `host.docker.internal` - CORRECTO (README.md)
- `host.docker.internal` - CORRECTO (SolucionDigitalController.md)

**Impacto:** Menor - el formato principal es correcto

**Solución:** Verificar que toda la documentación use el formato correcto `host.docker.internal`

---

### 2. No existe .dockerignore
**Ubicación:** Raíz del proyecto

**Descripción:** No hay archivo `.dockerignore` para excluir archivos innecesarios del contexto de Docker

**Problema:**
- Se copian todos los archivos al contexto de build
- Incluye `node_modules/`, `vendor/`, `.git/`, etc.
- Aumenta el tiempo de build
- Aumenta el tamaño de la imagen

**Archivos que deberían ignorarse:**
- `node_modules/`
- `vendor/`
- `.git/`
- `.gitignore`
- `.env`
- `.env.*`
- `storage/logs/*`
- `storage/framework/cache/*`
- `storage/framework/sessions/*`
- `storage/framework/views/*`
- `bootstrap/cache/*`
- `tests/`
- `.phpunit.result.cache`
- `phpunit.xml`
- `README.md`
- `docs/`
- `Dockerfile`
- `unit.json`

**Impacto:**
- Builds más lentos
- Imágenes más grandes
- Copia de archivos innecesarios

**Solución:** Crear archivo `.dockerignore`

**Código sugerido:**
```dockerignore
# Git
.git
.gitignore
.gitattributes

# Environment
.env
.env.*
!.env.example

# Dependencies
node_modules
vendor

# Cache
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
storage/app/sin_exports/*
bootstrap/cache/*

# Testing
tests
phpunit.xml
.phpunit.result.cache

# Documentation
README.md
docs/

# Docker
Dockerfile
unit.json
.dockerignore

# IDE
.idea
.vscode
.fleet

# Misc
*.log
*.tmp
```

---

### 3. Composer install se ejecuta en cada build
**Ubicación:** `Dockerfile:29`

**Descripción:** `composer install` se ejecuta cada vez que se reconstruye la imagen

**Problema:**
- Si no cambia `composer.json`, se reinstalan todas las dependencias
- Tiempo de build innecesariamente largo
- Consume ancho de banda

**Código actual:**
```dockerfile
RUN composer install --prefer-dist --optimize-autoloader --no-interaction
```

**Impacto:**
- Builds más lentos
- Inconsistencia de versiones si `composer.lock` no está en la imagen

**Solución:** Copiar `composer.json` y `composer.lock`, luego hacer install

**Código sugerido:**
```dockerfile
# Copiar solo composer files primero
COPY composer.json composer.lock* ./

# Instalar dependencias
RUN composer install --prefer-dist --optimize-autoloader --no-interaction

# Copiar el resto del código
COPY . .
```

---

### 4. storage:link se ejecuta en build
**Ubicación:** `Dockerfile:35`

**Descripción:** `php artisan storage:link` se ejecuta durante la construcción de la imagen

**Problema:**
- El enlace simbólico se crea dentro de la imagen
- Si se monta un volumen para `storage/`, el enlace puede romperse
- En producción, `storage:link` se ejecuta en la configuración inicial
- No debería ejecutarse en el build

**Código actual:**
```dockerfile
RUN php artisan storage:link
```

**Impacto:**
- El enlace puede no funcionar correctamente con volúmenes
- Redundante si se ejecuta en configuración inicial
- Puede causar problemas en entornos de producción

**Solución:** Eliminar del Dockerfile y ejecutar solo en configuración inicial o usar un entrypoint

---

### 5. APP_KEY se genera en build
**Ubicación:** `Dockerfile:34`

**Descripción:** `php artisan key:generate` se ejecuta durante la construcción de la imagen

**Problema:**
- Cada build genera una nueva APP_KEY
- No es consistente entre builds
- Si se usa la misma imagen en múltiples entornos, tendrán la misma clave
- En producción, la APP_KEY debería configurarse por variable de entorno

**Código actual:**
```dockerfile
RUN php artisan key:generate
```

**Impacto:**
- Inconsistencia de APP_KEY entre entornos
- Posible seguridad si se comparte la misma clave
- Redundante si se configura por variable de entorno

**Solución:** Usar variable de entorno `APP_KEY` en lugar de generar

---

### 6. Permisos duplicados de storage y cache
**Ubicación:** `Dockerfile:23, 27`

**Descripción:** Los permisos de `storage/` y `bootstrap/cache/` se configuran dos veces

**Código actual:**
```dockerfile
# Línea 23
RUN chown -R unit:unit /var/www/example/storage bootstrap/cache && chmod -R 775 /var/www/example/storage

# Línea 27
RUN chown -R unit:unit storage bootstrap/cache && chmod -R 775 storage bootstrap/cache
```

**Problema:**
- Redundancia
- La primera configuración de permisos se sobrescribe al copiar el código
- Ineficiente

**Impacto:** Menor - solo redundancia

**Solución:** Configurar permisos solo una vez después de copiar todo el código

**Código sugerido:**
```dockerfile
# Crear directorios
RUN mkdir -p /var/www/example/storage /var/www/example/bootstrap/cache

# Copiar código
COPY . .

# Configurar permisos una sola vez
RUN chown -R unit:unit storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache
```

---

### 7. memory_limit demasiado alto para opcache.jit_buffer_size
**Ubicación:** `Dockerfile:11-13`

**Descripción:** `opcache.jit_buffer_size=256M` es excesivo

**Código actual:**
```dockerfile
RUN echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=512M" > /usr/local/etc/php/conf.d/custom.ini
```

**Problema:**
- 256M para JIT buffer es muy grande
- La mitad de `memory_limit` (512M)
- Puede causar falta de memoria para el código
- Valores típicos: 32M - 128M

**Impacto:**
- Menos memoria disponible para código PHP
- Posibles OOM (Out of Memory) errors
- Performance degradada

**Solución:** Reducir a 64M o 128M

---

### 8. No se valida la conexión a base de datos
**Ubicación:** Ejecución del contenedor

**Descripción:** No hay verificación de que la base de datos es accesible antes de iniciar la aplicación

**Problema:**
- Si la base de datos no está disponible, el contenedor iniciará pero fallará en el primer request
- No hay healthcheck
- Docker no puede detectar si el servicio está funcionando correctamente

**Impacto:**
- Servicios que dependen del contenedor pueden fallar
- Dificultad para orquestar con docker-compose
- No hay auto-restart inteligente

**Solución:** Agregar healthcheck

**Código sugerido:**
```dockerfile
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php-fpm-healthcheck || exit 1

# O verificar que Laravel responde:
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/ || exit 1
```

---

### 9. No hay multi-stage build para composer
**Ubicación:** `Dockerfile:17`

**Descripción:** Se usa `COPY --from=composer:latest` pero no hay multi-stage build para el proyecto

**Código actual:**
```dockerfile
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
```

**Problema:**
- Composer se copia pero el proceso de instalación no está optimizado
- No se separa el proceso de build y runtime
- La imagen final incluye herramientas de build que no son necesarias

**Impacto:**
- Imágenes más grandes de lo necesario
- Incluye herramientas innecesarias

**Solución:** Implementar multi-stage build completo

**Código sugerido:**
```dockerfile
# Stage 1: Build
FROM composer:latest AS build
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --prefer-dist --optimize-autoloader --no-interaction --no-dev

# Stage 2: Runtime
FROM unit:1.33.0-php8.2
# ... configuración de PHP ...

# Copiar vendor desde stage build
COPY --from=build /app/vendor /var/www/example/vendor

# Copiar código
COPY . .

# Resto del Dockerfile...
```

---

### 10. No se configuran variables de entorno por defecto
**Ubicación:** `Dockerfile`

**Descripción:** No hay valores por defecto para variables de entorno críticas

**Problema:**
- Si no se pasan variables de entorno, la aplicación fallará
- `APP_NAME`, `APP_ENV`, `APP_DEBUG` no tienen valores por defecto
- No se puede iniciar el contenedor sin pasar todas las variables

**Impacto:**
- El contenedor es difícil de usar sin docker-compose
- Se requiere pasar muchas variables manualmente
- Menos amigable para desarrolladores

**Solución:** Agregar ENV en Dockerfile

**Código sugerido:**
```dockerfile
ENV APP_NAME="Laravel" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    APP_URL="http://localhost" \
    LOG_CHANNEL="stack" \
    LOG_LEVEL="debug" \
    BROADCAST_DRIVER="log" \
    CACHE_DRIVER="file" \
    FILESYSTEM_DISK="local" \
    QUEUE_CONNECTION="sync" \
    SESSION_DRIVER="file" \
    SESSION_LIFETIME="120"
```

---

## 💡 POSIBLES MEJORAS

### 1. Crear archivo .dockerignore
**Ubicación:** Raíz del proyecto (crear archivo)

**Descripción:** Excluir archivos innecesarios del contexto de Docker

**Código sugerido:** (Ver en bug #2 arriba)

**Beneficio:**
- Builds más rápidos
- Imágenes más pequeñas
- Menos ancho de banda

---

### 2. Optimizar composer install con multi-stage build
**Ubicación:** `Dockerfile`

**Descripción:** Usar multi-stage build para optimizar la instalación de dependencias

**Código sugerido:**
```dockerfile
# Stage 1: Composer Build
FROM composer:2.7 AS composer-build
WORKDIR /app

# Copiar solo archivos necesarios
COPY composer.json composer.lock* ./

# Instalar dependencias
RUN composer install \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-dev \
    --no-autoloader

# Stage 2: Runtime
FROM unit:1.33.0-php8.2

# Instalar extensiones de PHP
RUN apt update && apt install -y \
    curl unzip git libicu-dev libzip-dev libpng-dev \
    libjpeg-dev libfreetype6-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pcntl opcache pdo pdo_mysql intl zip gd exif ftp bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

# Configurar PHP
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=512M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/custom.ini

# Copiar Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Configurar directorios
WORKDIR /var/www/example
RUN mkdir -p /var/www/example/storage /var/www/example/bootstrap/cache

# Copiar vendor desde stage build
COPY --from=composer-build /app/vendor /var/www/example/vendor

# Copiar código del proyecto
COPY . .

# Configurar permisos
RUN chown -R unit:unit storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache

# Copiar configuración de Unit
COPY unit.json /docker-entrypoint.d/unit.json

# Variables de entorno por defecto
ENV APP_NAME="Laravel" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    APP_URL="http://localhost"

EXPOSE 8000

# Agregar healthcheck
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/ || exit 1

CMD ["unitd", "--no-daemon"]
```

**Beneficio:**
- Imágenes más pequeñas
- Builds más rápidos
- Mejor caché de capas de Docker
- Imágenes reproducibles

---

### 3. Reducir opcache.jit_buffer_size
**Ubicación:** `Dockerfile:12`

**Descripción:** Ajustar el tamaño del buffer JIT a un valor razonable

**Código sugerido:**
```dockerfile
RUN echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=64M" >> /usr/local/etc/php/conf.d/custom.ini
```

**Beneficio:**
- Más memoria disponible para código
- Mejor balance entre JIT y código PHP
- Evita OOM errors

---

### 4. Agregar healthcheck
**Ubicación:** `Dockerfile` (antes de CMD)

**Descripción:** Agregar healthcheck para que Docker pueda monitorear el estado del contenedor

**Código sugerido:**
```dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/ || exit 1
```

**Beneficio:**
- Docker sabe cuando el servicio está sano
- Auto-restart inteligente
- Mejor integración con orquestadores (Kubernetes, docker-compose)
- Monitoreo más preciso

---

### 5. Agregar variables de entorno por defecto
**Ubicación:** `Dockerfile` (después de WORKDIR)

**Descripción:** Configurar variables de entorno básicas

**Código sugerido:**
```dockerfile
ENV APP_NAME="Laravel" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    APP_URL="http://localhost" \
    LOG_CHANNEL="stack" \
    LOG_LEVEL="error" \
    CACHE_DRIVER="file" \
    SESSION_DRIVER="file" \
    QUEUE_CONNECTION="sync"
```

**Beneficio:**
- Contenedor usable sin docker-compose
- Valores por defecto seguros
- Más amigable para desarrolladores

---

### 6. Usar entrypoint script
**Ubicación:** Crear `docker-entrypoint.sh`

**Descripción:** Script de entrypoint para inicialización

**Código sugerido:**
```bash
#!/bin/bash
set -e

# Si no existe APP_KEY, generarla
if [ -z "$APP_KEY" ]; then
    echo "WARNING: APP_KEY not set, generating..."
    php artisan key:generate --ansi
fi

# Si no existe storage:link, crearlo
if [ ! -L "public/storage" ]; then
    echo "Creating storage link..."
    php artisan storage:link
fi

# Ejecutar el comando principal
exec "$@"
```

**En Dockerfile:**
```dockerfile
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
```

**Beneficio:**
- Lógica de inicialización flexible
- Validación de entorno
- Más mantenible

---

### 7. Crear docker-compose.yml
**Ubicación:** Raíz del proyecto (crear archivo)

**Descripción:** Archivo docker-compose para orquestar la aplicación

**Código sugerido:**
```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    container_name: laravel-app
    restart: unless-stopped
    ports:
      - "8000:8000"
    environment:
      APP_NAME: "Laravel App"
      APP_ENV: "local"
      APP_DEBUG: "true"
      APP_URL: "http://localhost:8000"
      DB_CONNECTION: "mysql"
      DB_HOST: "mysql"
      DB_PORT: "3306"
      DB_DATABASE: "laravel"
      DB_USERNAME: "root"
      DB_PASSWORD: "root"
    volumes:
      - ./storage:/var/www/example/storage
      - ./bootstrap/cache:/var/www/example/bootstrap/cache
    depends_on:
      mysql:
        condition: service_healthy
    networks:
      - laravel

  mysql:
    image: mysql:8.0
    container_name: laravel-mysql
    restart: unless-stopped
    environment:
      MYSQL_ROOT_PASSWORD: "root"
      MYSQL_DATABASE: "laravel"
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5
    networks:
      - laravel

  redis:
    image: redis:7-alpine
    container_name: laravel-redis
    restart: unless-stopped
    ports:
      - "6379:6379"
    networks:
      - laravel

volumes:
  mysql_data:

networks:
  laravel:
    driver: bridge
```

**Beneficio:**
- Orquestación fácil de todos los servicios
- Configuración centralizada
- Redes y volúmenes automáticos
- Desarrollo simplificado

---

### 8. Separar Dockerfile para desarrollo y producción
**Ubicación:** Crear `Dockerfile.prod` y `Dockerfile.dev`

**Descripción:** Dockerfiles específicos para cada entorno

**Dockerfile.dev:**
```dockerfile
FROM unit:1.33.0-php8.2

# Incluir dependencias de desarrollo
RUN apt update && apt install -y \
    curl unzip git libicu-dev libzip-dev \
    && docker-php-ext-install -j$(nproc) pcntl opcache pdo pdo_mysql intl zip gd exif ftp bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

# Configuración de desarrollo
RUN echo "opcache.enable=0" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors=1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/custom.ini

# Resto del Dockerfile...
```

**Dockerfile.prod:**
```dockerfile
# Multi-stage build para producción (ver mejora #2)

# Configuración de producción
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "display_errors=0" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini

# Resto del Dockerfile...
```

**Beneficio:**
- Configuración específica por entorno
- Desarrollo sin OPcache para cambios rápidos
- Producción con OPcache máximo para performance
- Imágenes más pequeñas en producción

---

### 9. Agregar labels al Dockerfile
**Ubicación:** `Dockerfile` (inicio)

**Descripción:** Agregar labels para metadatos de la imagen

**Código sugerido:**
```dockerfile
FROM unit:1.33.0-php8.2

LABEL maintainer="admin@example.com"
LABEL version="1.0.0"
LABEL description="Laravel 10.0 application with Nginx Unit"
LABEL org.opencontainers.image.source="https://github.com/example/laravel-app"
LABEL org.opencontainers.image.licenses="MIT"

# Resto del Dockerfile...
```

**Beneficio:**
- Metadatos de la imagen
- Información de versiones
- Documentación integrada
- Mejor gestión de imágenes

---

### 10. Agregar optimización de capas
**Ubicación:** `Dockerfile`

**Descripción:** Ordenar los comandos para maximizar el caché de capas de Docker

**Código sugerido:**
```dockerfile
# Copiar composer.json y composer.lock primero
COPY composer.json composer.lock* ./

# Instalar dependencias (se cachea si composer.json no cambia)
RUN composer install --prefer-dist --optimize-autoloader --no-interaction

# Copiar unit.json (se cachea si no cambia)
COPY unit.json /docker-entrypoint.d/unit.json

# Copiar el resto del código (se cachea si no cambia)
COPY . .

# Configurar permisos (se ejecuta siempre)
RUN chown -R unit:unit storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache
```

**Beneficio:**
- Mejor caché de capas
- Builds más rápidos
- Reconstrucciones inteligentes
- Ahorro de tiempo

---

## ❌ FALTAS COSAS

### 1. Falta archivo .dockerignore
**Ubicación:** Raíz del proyecto

**Descripción:** No existe archivo para excluir archivos del contexto de Docker

**Lo que falta:**
```dockerignore
# Ver mejora #1 arriba para el contenido completo
```

**Por qué es importante:**
- Builds más lentos sin .dockerignore
- Imágenes más grandes de lo necesario
- Copia de archivos innecesarios

---

### 2. Falta docker-compose.yml
**Ubicación:** Raíz del proyecto

**Descripción:** No hay archivo para orquestar servicios con Docker Compose

**Lo que falta:** (Ver mejora #7 arriba para el contenido completo)

**Por qué es importante:**
- Difícil orquestar múltiples servicios
- No se gestiona MySQL, Redis, etc.
- Desarrollo más complicado

---

### 3. Falta healthcheck
**Ubicación:** `Dockerfile`

**Descripción:** No hay healthcheck para monitorear el estado del contenedor

**Lo que falta:**
```dockerfile
HEALTHCHECK --interval=30s --timeout=10s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/ || exit 1
```

**Por qué es importante:**
- Docker no puede monitorear el estado
- No hay auto-restart inteligente
- Difícil integrar con orquestadores

---

### 4. Falta variables de entorno por defecto
**Ubicación:** `Dockerfile`

**Descripción:** No hay valores por defecto para variables críticas

**Lo que falta:**
```dockerfile
ENV APP_NAME="Laravel" \
    APP_ENV="production" \
    APP_DEBUG="false" \
    APP_URL="http://localhost"
```

**Por qué es importante:**
- Contenedor no usable sin pasar variables
- Difícil para desarrolladores
- No hay valores seguros por defecto

---

### 5. Falta documentación de configuración
**Ubicación:** `README.md` o docs específicos

**Descripción:** Documentación mínima sobre Docker

**Lo que falta:**
- Guía de construcción paso a paso
- Explicación de variables de entorno
- Troubleshooting común
- Uso con docker-compose
- Deploy en producción

**Por qué es importante:**
- Difícil para nuevos desarrolladores
- Falta documentación de uso
- Problemas no documentados

---

### 6. Falta validación de conexión a DB en entrypoint
**Ubicación:** No existe entrypoint

**Descripción:** No hay validación de que la DB esté disponible

**Lo que falta:**
```bash
#!/bin/bash
# Esperar a que la base de datos esté disponible
until php artisan db:show; do
  echo "Waiting for database connection..."
  sleep 2
done

# Ejecutar migraciones si está en modo setup
if [ "$APP_ENV" = "local" ]; then
    php artisan migrate --force
fi

exec "$@"
```

**Por qué es importante:**
- El contenedor puede iniciar antes que la DB
- La aplicación fallará en el primer request
- Difícil orquestar con docker-compose

---

### 7. Falta optimización de OPcache para desarrollo
**Ubicación:** `Dockerfile`

**Descripción:** La configuración de OPcache es para producción, no para desarrollo

**Lo que falta:**
- OPcache deshabilitado en desarrollo
- `display_errors=1` en desarrollo
- `xdebug` para debugging

**Por qué es importante:**
- Los cambios de código no se reflejan inmediatamente en desarrollo
- Difícil depurar sin errores visibles
- Experiencia de desarrollo pobre

---

### 8. Falta separación de entornos
**Ubicación:** `Dockerfile` único

**Descripción:** No hay Dockerfiles específicos para desarrollo y producción

**Lo que falta:**
- `Dockerfile.dev` para desarrollo
- `Dockerfile.prod` para producción
- Configuraciones diferentes por entorno

**Por qué es importante:**
- Desarrolladores sufren con OPcache
- Producción no está optimizada al máximo
- No hay flexibilidad de configuración

---

### 9. Falta script de entrypoint
**Ubicación:** No existe

**Descripción:** No hay script de entrypoint para inicialización

**Lo que falta:** (Ver mejora #6 arriba)

**Por qué es importante:**
- Lógica de inicialización en Dockerfile
- No es flexible
- Difícil mantener

---

### 10. Falta documentación de deployment
**Ubicación:** Documentación del proyecto

**Descripción:** No hay guía de deployment con Docker

**Lo que falta:**
- Cómo desplegar en producción
- Uso con Kubernetes o Docker Swarm
- Configuración de CI/CD
- Backups con volúmenes
- Rollbacks con versiones de imágenes

**Por qué es importante:**
- Despliegues difíciles
- No hay guía de producción
- Riesgo de errores en deployment

---

## ⚡ OPTIMIZACIONES

### 1. Usar multi-stage build completo
**Ubicación:** `Dockerfile` completo

**Descripción:** Implementar multi-stage build para reducir tamaño de imagen

**Optimización:** (Ver mejora #2 arriba)

**Beneficio:**
- Imágenes más pequeñas (30-40% menos)
- Builds más rápidos con caché de capas
- No incluye herramientas de build en imagen final
- Mejor seguridad (menos superficie de ataque)

---

### 2. Optimizar caché de capas
**Ubicación:** `Dockerfile`

**Descripción:** Ordenar comandos para maximizar caché de Docker

**Optimización:** (Ver mejora #10 arriba)

**Beneficio:**
- Builds más rápidos
- Reconstrucciones inteligentes
- Menor uso de recursos
- Ahorro de tiempo significativo

---

### 3. Usar .dockerignore agresivo
**Ubicación:** `.dockerignore`

**Descripción:** Excluir todos los archivos innecesarios

**Optimización:**
```dockerignore
# Excluir todo por defecto
*

# Incluir solo lo necesario
!composer.json
!composer.lock
!unit.json
!.env.example
!app/
!bootstrap/
!config/
!public/
!resources/
!routes/
!storage/
!artisan
```

**Beneficio:**
- Contexto de build más pequeño
- Builds más rápidos
- Menor uso de red

---

### 4. Instalar extensiones de PHP en paralelo
**Ubicación:** `Dockerfile:6`

**Descripción:** Ya se usa `-j$(nproc)`, pero se puede optimizar más

**Optimización actual:**
```dockerfile
docker-php-ext-install -j$(nproc) pcntl opcache pdo pdo_mysql intl zip gd exif ftp bcmath
```

**Beneficio:**
- Instalación en paralelo
- Tiempo de build reducido
- Mejor uso de CPU

---

### 5. Usar versiones específicas de imágenes
**Ubicación:** `Dockerfile:1`

**Descripción:** Usar versiones específicas en lugar de `latest`

**Optimización:**
```dockerfile
FROM unit:1.33.0-php8.2
FROM composer:2.7.8
```

**Beneficio:**
- Reproducibilidad de builds
- Evita cambios inesperados
- Mejor control de versiones
- Compliance más fácil

---

### 6. Eliminar caches de apt después de instalar
**Ubicación:** `Dockerfile:3`

**Descripción:** Limpiar caches de apt para reducir tamaño

**Optimización:**
```dockerfile
RUN apt update && apt install -y \
    curl unzip git libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libssl-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
```

**Beneficio:**
- Imágenes más pequeñas
- Menos superfice de ataque
- Builds más rápidos al subir al registry

---

### 7. Usar build arguments para configuración
**Ubicación:** `Dockerfile`

**Descripción:** Permitir configuración en build time

**Optimización:**
```dockerfile
ARG PHP_VERSION=8.2
ARG UNIT_VERSION=1.33.0

FROM unit:${UNIT_VERSION}-php${PHP_VERSION}

ARG APP_ENV=production
ENV APP_ENV=${APP_ENV}
```

**Uso:**
```bash
docker build --build-arg APP_ENV=development -t example:dev .
```

**Beneficio:**
- Configuración flexible
- Imágenes por entorno sin Dockerfiles separados
- Más mantenible

---

### 8. Optimizar configuración de OPcache para producción
**Ubicación:** `Dockerfile:10-13`

**Descripción:** Configuración más agresiva de OPcache

**Optimización:**
```dockerfile
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.enable_cli=0" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.memory_consumption=128M" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.interned_strings_buffer=16M" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.max_accelerated_files=10000" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit_buffer_size=64M" >> /usr/local/etc/php/conf.d/opcache.ini \
    && echo "opcache.jit_max_root_traces=10" >> /usr/local/etc/php/conf.d/opcache.ini
```

**Beneficio:**
- Mejor performance en producción
- Caché más efectivo
- Menos uso de CPU
- Mejor response time

---

### 9. Usar alpine cuando sea posible
**Ubicación:** `Dockerfile:1`

**Descripción:** Evaluar si se puede usar Alpine Linux

**Notas:**
- La imagen `unit:1.33.0-php8.2` no está basada en Alpine
- Alpine puede tener problemas de compatibilidad con algunas extensiones
- Necesaría evaluar cuidadosamente

**Beneficio (si es posible):**
- Imágenes más pequeñas (50-70% menos)
- Mejor seguridad
- Menor superficie de ataque
- Arranque más rápido

---

### 10. Implementar layer caching para vendor
**Ubicación:** `Dockerfile`

**Descripción:** Cachear la instalación de vendor en capas separadas

**Optimización:**
```dockerfile
# Copiar composer.json solo
COPY composer.json ./

# Instalar dependencias base (se cachea si composer.json no cambia)
RUN composer install --prefer-dist --optimize-autoloader --no-interaction

# Copiar composer.lock y reinstalar si cambió
COPY composer.lock* ./
RUN composer install --prefer-dist --optimize-autoloader --no-interaction
```

**Beneficio:**
- Builds más rápidos
- Mejor caché de capas
- Menor tiempo de desarrollo

---

## 📊 RESUMEN DE PROBLEMAS POR CATEGORÍA

### Bugs Críticos (Deben corregirse YA)
1. **No existe .dockerignore** - Builds lentos y grandes
2. **Composer install en cada build** - Tiempo perdido
3. **storage:link y key:generate en build** - Problemas en producción

### Bugs Importantes (Deben corregirse pronto)
4. **Permisos duplicados** - Redundancia
5. **opcache.jit_buffer_size demasiado alto** - OOM errors
6. **No hay healthcheck** - Monitoreo imposible

### Bugs Menores (Pueden esperar)
7. **No hay variables por defecto** - Contenedor difícil de usar
8. **No hay multi-stage** - Imágenes grandes
9. **No hay entrypoint** - Inicialización rígida
10. **No hay separación de entornos** - Desarrollo difícil

### Mejoras Críticas (Funcionalidad básica)
1. **Crear .dockerignore** - Essential para builds eficientes
2. **Multi-stage build** - Reduce tamaño significativamente
3. **docker-compose.yml** - Esencial para desarrollo

### Mejoras Importantes (Calidad y robustez)
1. **Healthcheck** - Monitoreo básico
2. **Variables por defecto** - Usabilidad
3. **Entry point script** - Flexibilidad
4. **Documentación de Docker** - Guía de uso

### Mejoras Útiles (Funcionalidad avanzada)
1. **Separación de Dockerfiles** - Desarrollo vs producción
2. **Labels en imagen** - Metadatos
3. **Build arguments** - Configuración flexible
4. **Validación de DB** - Robustez

### Faltas Críticas (Bloquean funcionalidad)
1. .dockerignore
2. docker-compose.yml
3. Healthcheck
4. Variables por defecto

### Faltas Importantes (Causan problemas)
5. Entry point script
6. Documentación de Docker
7. Validación de DB en startup
8. Separación de entornos

### Faltas Menores (Afectan experiencia)
9. Documentación de deployment
10. OPcache para desarrollo

### Optimizaciones de Alto Impacto
1. Multi-stage build - Reduce tamaño 30-40%
2. Optimización de caché de capas - Builds 2-3x más rápidos
3. .dockerignore agresivo - Contexto más pequeño

### Optimizaciones de Impacto Medio
4. Clean de apt cache - Imágenes más pequeñas
5. Instalación paralela de extensiones - Tiempo reducido
6. Versiones específicas de imágenes - Reproducibilidad

### Optimizaciones de Bajo Impacto
7. Build arguments - Flexibilidad
8. OPcache optimizado - Mejor performance
9. Alpine Linux (si es posible) - Menor tamaño
10. Layer caching para vendor - Mejor caché

---

## 🔗 UBICACIONES DE ARCHIVOS CLAVE

| Archivo | Ubicación | Estado | Descripción |
|---------|-----------|---------|-------------|
| Dockerfile | `Dockerfile` | ACTIVO | Definición de imagen |
| unit.json | `unit.json` | ACTIVO | Configuración de Nginx Unit |
| composer.json | `composer.json` | ACTIVO | Dependencias del proyecto |
| .env.example | `.env.example` | ACTIVO | Plantilla de variables de entorno |
| README | `README.md` | ACTIVO | Documentación de uso |
| .dockerignore | Raíz | NO EXISTE | Falta - crítico |
| docker-compose.yml | Raíz | NO EXISTE | Falta - crítico |
| docker-entrypoint.sh | Raíz | NO EXISTE | Falta - importante |
| Dockerfile.prod | Raíz | NO EXISTE | Falta - útil |
| Dockerfile.dev | Raíz | NO EXISTE | Falta - útil |

---

## 🎯 PRIORIDAD DE IMPLEMENTACIÓN

### Inmediato (Bloquea uso eficiente)
1. Crear .dockerignore
2. Implementar multi-stage build
3. Crear docker-compose.yml
4. Agregar healthcheck

### Corto plazo (Mejora experiencia)
5. Agregar variables por defecto
6. Crear entry point script
7. Separar Dockerfiles (dev/prod)
8. Agregar labels a la imagen

### Medio plazo (Optimizaciones)
9. Optimizar caché de capas
10. Limpiar cache de apt
11. Reducir opcache.jit_buffer_size
12. Configurar OPcache por entorno

### Largo Plazo (Documentación y avanzado)
13. Documentación completa de Docker
14. Guía de deployment
15. Build arguments
16. Optimización completa de OPcache

---

## 🔍 NOTAS IMPORTANTES

### Imagen Base
- `unit:1.33.0-php8.2` es la imagen oficial de Nginx Unit
- Nginx Unit es un servidor web moderno y de aplicaciones
- Soporta PHP nativamente sin necesidad de PHP-FPM separado
- Más ligero que Apache + PHP-FPM

### Extensiones de PHP
- El Dockerfile instala todas las extensiones necesarias
- Incluye extensión `redis` via PECL
- Incluye `gd` con soporte para JPEG y PNG

### Configuración de PHP
- OPcache está habilitado con JIT en modo tracing
- Memory limit de 512MB es razonable para Laravel
- Upload max de 64MB permite subida de documentos

### Directorios
- `WORKDIR /var/www/example` - Directorio de trabajo
- `storage/` y `bootstrap/cache/` tienen permisos especiales
- El usuario `unit` tiene permisos de escritura

### Variables de Entorno
- No hay variables por defecto configuradas
- Se requieren variables de DB para funcionamiento
- Ver README.md para ejemplos de uso

### Host de Docker
- `host.docker.internal` es el formato correcto para Docker Desktop
- Permite acceder al host desde dentro del contenedor
- Funciona en Mac, Windows, y Linux (Docker Desktop)

---

## 📋 COMANDOS DE USO

### Construir imagen
```bash
docker build -t example .
```

### Ejecutar contenedor (básico)
```bash
docker run -e DB_DATABASE=example -e DB_HOST=host.docker.internal -p 8000:8000 -t example
```

### Ejecutar contenedor (con Solución Digital)
```bash
docker run \
  -e DB_CONNECTION=mysql \
  -e DB_HOST=host.docker.internal \
  -e DB_PORT=3306 \
  -e DB_DATABASE=example \
  -e DB_USERNAME=root \
  -e DB_CONNECTION_SOLUCION_DIGITAL=mysql \
  -e DB_HOST_SOLUCION_DIGITAL=host.docker.internal \
  -e DB_PORT_SOLUCION_DIGITAL=3306 \
  -e DB_DATABASE_SOLUCION_DIGITAL=soluciondigital \
  -e DB_USERNAME_SOLUCION_DIGITAL=root \
  -p 8000:8000 \
  -t example
```

### Usar docker-compose (cuando se implemente)
```bash
docker-compose up -d
docker-compose logs -f
docker-compose down
```

### Ver logs del contenedor
```bash
docker logs <container_id>
docker logs -f <container_id>  # Seguir logs
```

### Entrar al contenedor
```bash
docker exec -it <container_id> /bin/bash
```

### Ver tamaño de la imagen
```bash
docker images example
```

### Limpiar imágenes no usadas
```bash
docker system prune -a
```
