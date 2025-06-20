# Deploying with Dokploy UI

This document provides instructions for deploying this PHP portfolio application using Dokploy UI and Docker Compose.

## Overview

This project is configured to work seamlessly with Dokploy UI, which uses Traefik as a reverse proxy for handling HTTPS and routing. The configuration eliminates the need for a separate nginx container, as Dokploy handles the web server functionality.

## Recent Changes to Fix Gateway Timeout and YAML Syntax Issues

We've updated the configuration to address gateway timeout issues and fixed YAML syntax errors:

1. **Fixed YAML Indentation**
   - Corrected indentation in docker-compose.yml
   - Added validation script to catch YAML errors
   - Created YAML_SYNTAX_GUIDE.md for reference

2. **Optimized PHP-FPM Configuration**
   - Increased max execution time to 120 seconds
   - Adjusted PHP memory limits and buffer sizes
   - Added explicit timeout configuration for Dokploy

2. **Added Health Checks**
   - Implemented health.php endpoint for monitoring
   - Added Docker healthcheck configuration

3. **Improved Resource Management**
   - Optimized PHP-FPM process management
   - Added environment variables for easier configuration

## Prerequisites

- Dokploy UI installed (following [Dokploy documentation](https://docs.dokploy.com/docs/core/installation))
- Docker and Docker Compose installed
- Domain configured to point to your Dokploy server

## Deployment Steps

1. **Validate the docker-compose.yml file**
   
   Always validate the YAML syntax before deployment:
   ```bash
   ./validate-compose.sh
   ```
   
   Or use the Docker Compose built-in validation:
   ```bash
   docker-compose -f docker-compose.yml config
   ```

2. **Deploy using Dokploy UI**

   - Access your Dokploy UI dashboard
   - Add a new application
   - Connect to your Git repository or upload your project files
   - Select the repository and branch
   - Click "Deploy"

   Alternatively, deploy using the CLI:
   ```bash
   dokploy deploy
   ```

## Configuration Details

### Web Service

The `web` service is configured as a PHP-FPM application with the following features:
- FastCGI protocol on port 9000
- Automatic HTTPS with Let's Encrypt certificates
- HTTP to HTTPS redirection
- Security headers for enhanced protection
- Document root set to `/var/www/html`

### WKHTMLtoPDF Service

The `wkhtmltopdf` service is available for PDF generation needs.

## Environment Variables

Dokploy provides several environment variables that are used in the docker-compose.yml:
- `${DOKPLOY_PROJECT_NAME}` - Automatically set by Dokploy for consistent naming

## Troubleshooting

### Deployment Failures

If deployment fails, check:
1. Dokploy logs: `dokploy logs`
2. Docker logs: `docker logs [container-name]`
3. Domain DNS configuration
4. Firewall settings for ports 80 and 443

### Database Issues

This project uses SQLite. Ensure the database file has proper permissions:
```bash
dokploy exec web chmod 664 /var/www/html/database.sqlite
```

## Security Considerations

- HTTPS is enforced through Traefik
- Security headers are configured for browser protection:
  - XSS protection
  - Content type protections
  - Strict Transport Security (HSTS)
- All admin routes require authentication as specified in the PHP code

## Monitoring

Monitor your application after deployment:
```bash
dokploy logs -f
```

## Updating

To update your deployment:
```bash
git pull
dokploy deploy
```

## Backup

Backup your SQLite database regularly:
```bash
dokploy exec web "sqlite3 /var/www/html/database.sqlite .dump > /var/www/html/backup_$(date +%Y%m%d).sql"
dokploy cp web:/var/www/html/backup_*.sql ./backups/
```
