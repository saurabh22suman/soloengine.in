FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_sqlite opcache

# Configure PHP for production
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/max_execution_time = 30/max_execution_time = 120/' $PHP_INI_DIR/php.ini \
    && sed -i 's/memory_limit = 128M/memory_limit = 256M/' $PHP_INI_DIR/php.ini \
    && sed -i 's/post_max_size = 8M/post_max_size = 32M/' $PHP_INI_DIR/php.ini \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 32M/' $PHP_INI_DIR/php.ini

# Configure FPM for better performance
RUN { \
        echo '[global]'; \
        echo 'process.max = 128'; \
        echo 'request_terminate_timeout = 120s'; \
        echo '[www]'; \
        echo 'pm = dynamic'; \
        echo 'pm.max_children = 50'; \
        echo 'pm.start_servers = 5'; \
        echo 'pm.min_spare_servers = 5'; \
        echo 'pm.max_spare_servers = 35'; \
        echo 'pm.max_requests = 500'; \
    } > /usr/local/etc/php-fpm.d/zzz-dokploy-custom.conf

WORKDIR /var/www/html

# We don't need to copy files here - we're using volume mounts
# COPY . /var/www/html

# Set correct permissions for web server
RUN chown -R www-data:www-data /var/www/html