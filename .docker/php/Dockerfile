FROM php:8.1-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

WORKDIR /var/www/html


# Copy your PHP application into the container
COPY . /var/www/html

# Optional: Set correct permissions if needed
# RUN chown -R www-data:www-data /var/www/html