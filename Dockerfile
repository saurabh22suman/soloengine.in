FROM php:8.2-apache

# Install wkhtmltopdf and dependencies
RUN apt-get update && apt-get install -y \
    wkhtmltopdf \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    && rm -rf /var/lib/apt/lists/*

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

# Ensure correct ownership for web root — ***THIS FIXES YOUR PERMISSIONS ISSUE***
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html