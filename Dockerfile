FROM php:8.2-apache

# Install wkhtmltopdf and dependencies
RUN apt-get update && apt-get install -y \
    wkhtmltopdf \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    sqlite3 \
    libsqlite3-dev \
    && rm -rf /var/lib/apt/lists/*

# Install SQLite PDO extension
RUN docker-php-ext-install pdo_sqlite

# Enable mod_rewrite for Apache
RUN a2enmod rewrite

# Set DirectoryIndex so Apache knows to use index.php
RUN echo 'DirectoryIndex index.php index.html' >> /etc/apache2/apache2.conf

# Set Apache Directory permissions correctly
RUN echo '<Directory /var/www/html/>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

# Set working directory
WORKDIR /var/www/html

# Copy custom PHP configuration
COPY php.ini /usr/local/etc/php/conf.d/custom.ini

# Ensure correct ownership for web root
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html