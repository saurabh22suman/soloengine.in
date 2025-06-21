# Database Persistence in Docker

This document explains how database persistence is maintained when deploying this application with Docker.

## How Database Persistence Works

The SQLite database used by this application is stored in a Docker volume to ensure data persistence between deployments and container restarts.

### Key Components:

1. **Docker Volume**:
   - A named volume `portfolio_database` is used to store the database files
   - This volume persists even when the container is destroyed and recreated
   - The volume is mounted at `/var/www/html/data` in the container

2. **Database Manager Script**:
   - `db_manager.sh` handles database backup and restoration
   - The script ensures proper permissions for database files
   - It creates automatic backups before significant operations

3. **Docker Compose Configuration**:
   - The `docker-compose.yml` file defines the volume and mounting configuration
   - The startup command ensures all directories exist with proper permissions

## Backup and Restore

### Automatic Backups

The application automatically backs up the database when:
- The container starts and finds an existing database
- Before any potential destructive operations

These backups are stored in `/var/www/html/backup` within the container.

### Manual Backup

You can manually trigger a backup by running:

```bash
docker exec -it [container_name] /var/www/html/db_manager.sh backup
```

### Manual Restore

If you need to restore from a backup:

```bash
docker exec -it [container_name] /var/www/html/db_manager.sh restore
```

## Troubleshooting

### If Changes Are Lost After Deployment

1. Check if the volume exists:
   ```bash
   docker volume ls | grep portfolio_database
   ```

2. Inspect the volume:
   ```bash
   docker volume inspect portfolio_database_data
   ```

3. Verify the container is using the volume:
   ```bash
   docker inspect [container_name] | grep portfolio_database
   ```

4. Check logs for any errors:
   ```bash
   docker logs [container_name]
   ```

### Complete Data Reset

If you need to completely reset the database:

1. Access the container:
   ```bash
   docker exec -it [container_name] bash
   ```

2. Navigate to the initialization script:
   ```bash
   cd /var/www/html
   php reset_db.php
   ```

## Best Practices

1. **Regular Backups**: Periodically export the database to a safe location
2. **Version Control**: Keep the initial database schema and seed data in version control
3. **Permissions**: Ensure the web server has write permissions to the data directory
4. **Security**: Limit access to the admin panel and database backup files
