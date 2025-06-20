FROM php:8.2-apache

# Install wkhtmltopdf and dependencies
RUN apt-get update && apt-get install -y \
    wkhtmltopdf \
    libxrender1 \
    libfontconfig1 \
    libxext6 \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite (optional but recommended for PHP apps)
RUN a2enmod rewrite

# Working directory (where your index.php is)
WORKDIR /var/www/html
