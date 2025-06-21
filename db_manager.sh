#!/bin/bash
# Script to manage database backup and restoration

DATA_DIR="/var/www/html/data"
BACKUP_DIR="/var/www/html/backup"
DB_FILE="$DATA_DIR/resume.db"
BACKUP_FILE="$BACKUP_DIR/resume_backup.db"

# Create directories if they don't exist
mkdir -p $DATA_DIR
mkdir -p $BACKUP_DIR

# Ensure proper permissions
chmod -R 777 $DATA_DIR
chmod -R 777 $BACKUP_DIR

# Function to back up the database
backup_database() {
    if [ -f "$DB_FILE" ]; then
        echo "Backing up database..."
        sqlite3 $DB_FILE .dump > $BACKUP_DIR/resume_dump.sql
        cp $DB_FILE $BACKUP_FILE
        echo "Backup created at $(date) - $(ls -lh $BACKUP_FILE)" >> $BACKUP_DIR/backup_log.txt
        echo "Database backed up successfully."
    else
        echo "No database file found to back up."
    fi
}

# Function to restore database from backup
restore_database() {
    if [ -f "$BACKUP_FILE" ]; then
        echo "Restoring database from backup..."
        cp $BACKUP_FILE $DB_FILE
        chmod 777 $DB_FILE
        echo "Database restored at $(date)" >> $BACKUP_DIR/restore_log.txt
        echo "Database restored successfully."
    else
        echo "No backup file found to restore from."
    fi
}

# Check if the database exists in volume, if not, try to restore it
check_and_restore() {
    if [ ! -f "$DB_FILE" ]; then
        echo "Database file not found in volume."
        restore_database
    else
        echo "Database file exists in volume."
        # Create a backup just in case
        backup_database
    fi
}

# Main execution based on argument
case "$1" in
    backup)
        backup_database
        ;;
    restore)
        restore_database
        ;;
    check)
        check_and_restore
        ;;
    *)
        echo "Usage: $0 {backup|restore|check}"
        exit 1
        ;;
esac

exit 0
