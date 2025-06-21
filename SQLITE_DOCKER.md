# SQLite Database Configuration for Docker

This document provides guidance for configuring SQLite in a Docker environment for this portfolio website.

## Key Files

- **Dockerfile**: Properly configured to install SQLite and the PDO SQLite extension
- **php.ini**: Contains SQLite extension configurations
- **docker-compose.yml**: Sets up volume persistence for SQLite database files
- **sqlite-test.php**: A utility script to verify SQLite functionality

## Testing SQLite Functionality

After deploying the application, you can test if SQLite is working correctly:

1. Navigate to `/sqlite-test.php` in your browser
2. The page will show:
   - If the required PHP extensions are loaded
   - If a database connection can be established
   - If basic database operations work

## Database Persistence

The database files are stored in a Docker volume named `portfolio-data` which is mounted at `/var/www/html/data` in the container. This ensures that your database changes persist between deployments.

## Troubleshooting

### Common Issues

1. **Permission Problems**
   - Verify that the web server has write permissions to the data directory
   - Check the permissions shown in the sqlite-test.php output

2. **Missing Extensions**
   - If extensions aren't loading, check the php.ini configuration
   - Look for errors in the PHP logs

3. **Volume Not Mounting**
   - Verify the Docker volume is properly created:
     ```
     docker volume ls | grep portfolio-data
     ```
   - Check that it's correctly mounted in the container:
     ```
     docker inspect [container_name] | grep Mounts -A 10
     ```

## Docker Commands

Inspect the database files in the volume:
```bash
docker exec -it [container_name] ls -la /var/www/html/data
```

Create a backup of the database:
```bash
docker exec -it [container_name] sqlite3 /var/www/html/data/resume.db .dump > backup.sql
```
