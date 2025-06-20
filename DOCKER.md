# Docker Deployment Guide

This document provides comprehensive instructions for deploying the PHP Portfolio Website using Docker and Docker Compose.

## Prerequisites

- Docker Engine 20.10+ 
- Docker Compose 2.0+ (or `docker compose` command)
- Git (for cloning the repository)

## Quick Start

1. **Clone the repository**:
   ```bash
   git clone https://github.com/prakersh/prakersh.in.git
   cd prakersh.in
   ```

2. **Build and run with one command**:
   ```bash
   ./build.sh build && ./build.sh run
   ```

3. **Access the application**:
   - Website: http://localhost:8080
   - Admin Panel: http://localhost:8080/admin.php
   - Default credentials: `admin` / `admin123` (change immediately!)

## Docker Architecture

### Dockerfile

The application uses a lightweight PHP 8.1-cli base image with:
- **PHP Extensions**: SQLite3, PDO_SQLite for database operations
- **System Tools**: curl for health checks, wkhtmltopdf for PDF generation
- **Built-in Server**: PHP's built-in web server (avoids Apache configuration complexity)
- **Security**: Disabled PHP version exposure, proper file permissions

### Docker Compose

The `docker-compose.yml` provides:
- **Service Orchestration**: Single-service setup with networking
- **Data Persistence**: Volume mount for SQLite database
- **Port Mapping**: Host port 8080 → Container port 80
- **Development Support**: Optional volume mounts for live code editing

## Build Script (build.sh)

The `build.sh` script provides convenient commands for all Docker operations:

### Core Commands

```bash
# Build the Docker image (takes 3-4 minutes)
./build.sh build

# Start the application
./build.sh run

# Stop the application
./build.sh stop

# View logs
./build.sh logs

# Check status
./build.sh status
```

### Development Commands

```bash
# Start in development mode (live reload)
./build.sh dev

# Access container shell
./build.sh shell

# Run tests
./build.sh test
```

### Management Commands

```bash
# Restart the application
./build.sh restart

# Create database backup
./build.sh backup

# Initialize database (first time)
./build.sh init

# Clean up containers and images
./build.sh clean
```

### Help

```bash
# Show all available commands
./build.sh help
```

## Deployment Scenarios

### Development Deployment

For development with live code changes:

```bash
# Start in development mode
./build.sh dev

# This mounts the current directory, allowing real-time code editing
# Changes to PHP files are immediately reflected in the browser
```

### Production Deployment

For production deployment:

```bash
# Build optimized image
./build.sh build

# Start in production mode
./build.sh run

# The application files are baked into the container
# Only the data directory is mounted for database persistence
```

### Custom Port

To use a different port, modify `docker-compose.yml`:

```yaml
ports:
  - "3000:80"  # Use port 3000 instead of 8080
```

## Data Persistence

### Database Storage

The SQLite database is stored in the `data/` directory, which is mounted as a volume:
- **Host Path**: `./data/resume.db`
- **Container Path**: `/var/www/html/data/resume.db`
- **Persistence**: Data survives container restarts and rebuilds

### Backup Strategy

```bash
# Create manual backup
./build.sh backup

# Automated backup (add to cron)
0 2 * * * cd /path/to/prakersh.in && ./build.sh backup
```

Backups are stored in `data/backup_YYYYMMDD_HHMMSS.db` format.

## Security Considerations

### Container Security

- **Non-root User**: Application runs as www-data user
- **Minimal Attack Surface**: CLI-based image without unnecessary services
- **Security Headers**: PHP configured with security best practices
- **File Permissions**: Proper 644/755 permission structure

### Network Security

- **Local Access**: Default configuration binds to localhost only
- **Firewall**: Ensure proper firewall rules for production deployment
- **HTTPS**: Consider adding reverse proxy (nginx) for SSL termination

### Application Security

All original security features are preserved in Docker:
- **Authentication**: Admin login required for management functions
- **CSRF Protection**: Form submissions protected with CSRF tokens
- **Session Management**: Secure session configuration
- **Input Validation**: All user inputs validated and sanitized

## Troubleshooting

### Common Issues

1. **Port Already in Use**:
   ```bash
   # Check what's using port 8080
   netstat -tulpn | grep 8080
   
   # Use different port in docker-compose.yml
   ```

2. **Permission Denied**:
   ```bash
   # Ensure build.sh is executable
   chmod +x build.sh
   
   # Check Docker daemon is running
   docker info
   ```

3. **Database Not Persisting**:
   ```bash
   # Verify data directory exists and has correct permissions
   ls -la data/
   
   # Check volume mount in container
   ./build.sh shell
   ls -la /var/www/html/data/
   ```

4. **Container Won't Start**:
   ```bash
   # Check logs for errors
   ./build.sh logs
   
   # Rebuild without cache
   docker compose down
   docker compose build --no-cache
   ```

### Health Checks

The container includes health checks:
```bash
# Check container health
docker ps

# Manual health check
curl -f http://localhost:8080/ || echo "Health check failed"
```

### Debugging

```bash
# Access container for debugging
./build.sh shell

# View container processes
docker compose exec portfolio ps aux

# Check PHP configuration
docker compose exec portfolio php -i

# Test database connectivity
docker compose exec portfolio sqlite3 data/resume.db ".tables"
```

## Production Considerations

### Resource Limits

Add resource limits to `docker-compose.yml`:

```yaml
services:
  portfolio:
    # ... existing configuration
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
        reservations:
          memory: 256M
          cpus: '0.25'
```

### Monitoring

Set up monitoring for production:

```bash
# Monitor container stats
docker stats portfolio

# Set up log rotation
# Add to logrotate configuration
```

### Reverse Proxy Setup

For production with SSL, add nginx reverse proxy:

```yaml
# Uncomment nginx service in docker-compose.yml
nginx:
  image: nginx:alpine
  container_name: portfolio-nginx
  ports:
    - "80:80"
    - "443:443"
  volumes:
    - ./nginx.conf:/etc/nginx/nginx.conf:ro
    - ./ssl:/etc/nginx/ssl:ro
  depends_on:
    - portfolio
```

### Environment Variables

Customize deployment with environment variables:

```bash
# Set custom database path
export DB_PATH=/custom/path/resume.db

# Set custom PHP memory limit
export PHP_MEMORY_LIMIT=256M
```

## Migration from Traditional Deployment

### From Shared Hosting

1. **Export Data**: Use existing admin panel to export/backup data
2. **Setup Docker**: Follow quick start guide above
3. **Import Data**: Copy database file to `data/` directory
4. **Verify**: Test all functionality in Docker environment

### From VPS/Dedicated Server

1. **Backup Database**: Copy `data/resume.db` file
2. **Setup Docker**: Install Docker on target system
3. **Deploy**: Use production deployment steps
4. **Restore Data**: Place database file in mounted volume
5. **Configure**: Update any server-specific settings

## Integration with CI/CD

### GitHub Actions

Add Docker testing to existing workflow:

```yaml
- name: Test Docker Build
  run: |
    docker build -t test-portfolio .
    docker run --rm -d --name test-container -p 8081:80 test-portfolio
    sleep 5
    curl -f http://localhost:8081/ || exit 1
    docker stop test-container
```

### Automated Deployment

Set up automated deployment:

```bash
# Deploy script example
#!/bin/bash
cd /path/to/prakersh.in
git pull origin main
./build.sh stop
./build.sh build
./build.sh run
```

## Support and Contributing

- **Issues**: Report Docker-related issues on GitHub
- **Documentation**: Contribute improvements to this guide
- **Testing**: Help test on different platforms and configurations

For more information, see the main [README.md](README.md) and [CICD.md](CICD.md) documentation.