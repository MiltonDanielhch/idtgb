FROM unit:1.33.0-php8.2

# Instalar dependencias del sistema en una sola capa
RUN apt update && apt install -y \
    curl unzip git libicu-dev libzip-dev libpng-dev libjpeg-dev libfreetype6-dev libssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) pcntl opcache pdo pdo_mysql intl zip gd exif ftp bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Configuración optimizada de PHP para producción
RUN echo "opcache.enable=1" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit=tracing" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.jit_buffer_size=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.memory_consumption=256M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.interned_strings_buffer=32M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.revalidate_freq=60" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/example

# Copiar composer files primero para mejor caché
COPY composer.json composer.lock ./

# Instalar dependencias de producción con optimizaciones
RUN composer install \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-dev \
    --no-scripts \
    --quiet

# Copiar el resto del proyecto
COPY . .

# Crear directorios necesarios y configurar permisos
RUN mkdir -p /var/www/example/storage /var/www/example/bootstrap/cache && \
    chown -R unit:unit /var/www/example/storage bootstrap/cache && \
    chmod -R 775 /var/www/example/storage bootstrap/cache

# Optimizar Laravel para producción (Excluyendo cache de rutas para evitar conflicto de Voyager)
RUN php artisan config:cache && \
    php artisan view:cache && \
    php artisan event:cache

COPY unit.json /docker-entrypoint.d/unit.json

EXPOSE 8000

CMD ["unitd", "--no-daemon"]