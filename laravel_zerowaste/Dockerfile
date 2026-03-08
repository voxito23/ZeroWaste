FROM php:8.4-apache

WORKDIR /var/www/html

# 1. Instalar dependencias del sistema necesarias para Composer y Laravel
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# 2. Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# 3. Cambiar el DocumentRoot de Apache a la carpeta public de Laravel
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

# 4. Instalar Composer copiándolo desde su imagen oficial (la forma más limpia)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Copiar todos los archivos de tu proyecto al contenedor
COPY . /var/www/html

# 6. Instalar las dependencias de Laravel (Esto es lo que crea la carpeta vendor)
RUN composer install --no-interaction --optimize-autoloader

# 7. Darle permisos a Apache para que pueda escribir en las carpetas de caché y logs de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache