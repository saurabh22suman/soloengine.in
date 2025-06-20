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

WORKDIR /var/www/html