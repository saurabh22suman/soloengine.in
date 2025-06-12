# Use official PHP image (with CLI instead of Apache)
FROM php:8.1-cli

# Set working directory
WORKDIR /var/www/html

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
    sqlite3 \
    libsqlite3-dev \
    wkhtmltopdf \
    curl \
    && docker-php-ext-install pdo pdo_sqlite \
    && rm -rf /var/lib/apt/lists/*

# Copy application files
COPY . .

# Create data directory with proper permissions
RUN mkdir -p /var/www/html/data \
    && chmod 755 /var/www/html/data

# Set proper permissions for the application
RUN find /var/www/html -type f -exec chmod 644 {} \; \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && chmod 755 /var/www/html/*.php

# Configure PHP for production (optional security settings)
RUN echo "expose_php = Off" >> /usr/local/etc/php/conf.d/security.ini

# Expose port 80
EXPOSE 80

# Health check to ensure the application is working
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1

# Create an entrypoint script to fix permissions on startup and start PHP server
RUN echo '#!/bin/bash' > /entrypoint.sh && \
    echo 'set -e' >> /entrypoint.sh && \
    echo '# Fix permissions for mounted directories' >> /entrypoint.sh && \
    echo 'chmod 755 /var/www/html/data || true' >> /entrypoint.sh && \
    echo '# Start PHP built-in server' >> /entrypoint.sh && \
    echo 'exec php -S 0.0.0.0:80 -t /var/www/html' >> /entrypoint.sh && \
    chmod +x /entrypoint.sh

# Use custom entrypoint
CMD ["/entrypoint.sh"]