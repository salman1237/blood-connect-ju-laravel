FROM php:8.2-apache

# System dependencies + PHP extensions Laravel needs. supervisor runs
# Apache/Reverb/the queue worker as sibling processes in this one container
# (see supervisord.conf) -- this project's Dokploy hosting is one
# application = one container = one deploy path, so real-time broadcasting
# and a persistent queue worker are added here rather than as separate
# Dokploy applications.
RUN apt-get update && apt-get install -y \
        git curl libpng-dev libonig-dev libxml2-dev libzip-dev zip unzip default-mysql-client supervisor \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite \
    && rm -rf /var/lib/apt/lists/*

# php.ini's own defaults (upload_max_filesize=2M) are well below the app's
# own advertised/validated photo-upload limit (max:4096 KB, i.e. 4MB, in
# both ProfileController@updatePhoto — web and API) — PHP was silently
# rejecting any photo between 2-4MB before Laravel's validation ever saw
# it, surfacing as "The photo failed to upload." with no clear cause.
# post_max_size needs headroom above upload_max_filesize for multipart
# overhead (other form fields, boundaries).
RUN { \
        echo 'upload_max_filesize = 8M'; \
        echo 'post_max_size = 10M'; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Node (for the Vite asset build)
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && npm ci \
    && npm run build \
    && npm cache clean --force

# Point Apache's docroot at Laravel's public/ directory
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

COPY supervisord.conf /etc/supervisor/conf.d/blood-connect.conf

EXPOSE 80
# Reverb's default port -- exposed via its own Dokploy domain (ws.bloodconnectju.org)
# pointed at this same container/application, separate from the port-80 domains.
EXPOSE 8080

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["supervisord", "-n", "-c", "/etc/supervisor/conf.d/blood-connect.conf"]
