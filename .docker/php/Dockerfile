FROM php:8.2-apache

# Install required dependencies for wkhtmltopdf
RUN apt-get update && apt-get install -y \
    wkhtmltopdf \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (if needed for routing)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html