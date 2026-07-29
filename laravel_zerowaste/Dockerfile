FROM php:8.4-apache

# 1. Instalar dependencias del sistema y Node.js (para Tailwind/Vite)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql zip gd

# Configurar límites de subida de PHP para permitir imágenes grandes
RUN echo "upload_max_filesize = 64M\npost_max_size = 64M\nmemory_limit = 256M" > /usr/local/etc/php/conf.d/uploads.ini

# 2. Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# 3. Cambiar el document root a la carpeta public de Laravel
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# 4. Traer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Copiar los archivos del proyecto
COPY . .

# 6. Instalar dependencias (Instalación limpia ignorando reqs del sistema anfitrión)
RUN composer install --no-interaction --optimize-autoloader --ignore-platform-reqs --no-scripts

# 7. Crear directorios de imágenes compartidos con Flask y permisos correctos
RUN mkdir -p /var/www/flask_zerowaste/static/img/perfiles \
    /var/www/flask_zerowaste/static/img/eventos \
    && chmod -R 777 /var/www/flask_zerowaste/static/img

# 8. Permisos de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true