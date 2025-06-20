#!/bin/bash

# build.sh - Docker build and run script for Prakersh Portfolio Website
# This script provides convenient commands for Docker operations

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
CONTAINER_NAME="portfolio"
IMAGE_NAME="portfolio:latest"
PORT="8080"

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if Docker is running
check_docker() {
    if ! docker info >/dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker and try again."
        exit 1
    fi
}

# Function to display help
show_help() {
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  build           Build the Docker image"
    echo "  run             Run the application using docker-compose"
    echo "  stop            Stop the running containers"
    echo "  restart         Restart the application"
    echo "  logs            Show application logs"
    echo "  clean           Remove containers and images"
    echo "  dev             Run in development mode with live reload"
    echo "  test            Run tests in Docker container"
    echo "  init            Initialize database (first time setup)"
    echo "  backup          Create database backup"
    echo "  status          Show container status"
    echo "  shell           Access container shell"
    echo "  help            Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 build && $0 run    # Build and start the application"
    echo "  $0 dev                # Start in development mode"
    echo "  $0 logs               # View application logs"
    echo ""
}

# Function to build Docker image
build_image() {
    print_status "Building Docker image..."
    
    # Ensure data directory exists
    mkdir -p data
    
    # Build the image
    if docker build -t "$IMAGE_NAME" .; then
        print_success "Docker image built successfully!"
    else
        print_error "Failed to build Docker image"
        exit 1
    fi
}

# Function to run with docker-compose
run_compose() {
    print_status "Starting application with docker-compose..."
    
    # Ensure data directory exists with proper permissions
    mkdir -p data
    chmod 755 data
    
    # Start with docker-compose
    if docker compose up -d; then
        print_success "Application started successfully!"
        print_status "Access the application at: http://localhost:$PORT"
        print_status "Admin panel at: http://localhost:$PORT/admin.php"
        print_status "Use 'admin' / 'admin123' for initial login (change immediately!)"
    else
        print_error "Failed to start application"
        exit 1
    fi
}

# Function to run in development mode
run_dev() {
    print_status "Starting in development mode..."
    
    # Create development docker-compose override
    cat > docker-compose.dev.yml << EOF
version: '3.8'
services:
  portfolio:
    volumes:
      - .:/var/www/html
      - ./data:/var/www/html/data
    environment:
      - APACHE_DOCUMENT_ROOT=/var/www/html
      - PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d:/usr/local/etc/php/dev.d
EOF

    # Start with development configuration
    if docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d; then
        print_success "Development mode started!"
        print_status "Live reload enabled - changes will be reflected immediately"
        print_status "Access at: http://localhost:$PORT"
    else
        print_error "Failed to start development mode"
        exit 1
    fi
}

# Function to stop containers
stop_containers() {
    print_status "Stopping containers..."
    
    if docker compose down; then
        print_success "Containers stopped successfully!"
    else
        print_warning "Some containers may still be running"
    fi
}

# Function to restart application
restart_app() {
    print_status "Restarting application..."
    stop_containers
    run_compose
}

# Function to show logs
show_logs() {
    print_status "Showing application logs..."
    docker compose logs -f portfolio
}

# Function to clean up
clean_up() {
    print_warning "This will remove all containers and images related to this project."
    read -p "Are you sure? (y/N): " -n 1 -r
    echo
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        print_status "Cleaning up..."
        
        # Stop and remove containers
        docker compose down --rmi all --volumes --remove-orphans 2>/dev/null || true
        
        # Remove any leftover containers
        docker rm -f "$CONTAINER_NAME" 2>/dev/null || true
        
        # Remove image
        docker rmi "$IMAGE_NAME" 2>/dev/null || true
        
        # Remove development override file
        rm -f docker-compose.dev.yml
        
        print_success "Cleanup completed!"
    else
        print_status "Cleanup cancelled."
    fi
}

# Function to run tests
run_tests() {
    print_status "Running tests in Docker container..."
    
    # Build if image doesn't exist
    if ! docker image inspect "$IMAGE_NAME" >/dev/null 2>&1; then
        build_image
    fi
    
    # Run tests
    docker run --rm -v "$(pwd):/app" -w /app "$IMAGE_NAME" bash -c "
        # Basic PHP syntax check
        find . -name '*.php' -not -path './vendor/*' -exec php -l {} \;
        
        # Test database initialization
        mkdir -p data
        php init_db.php
        
        # Test that database was created
        if [ -f 'data/resume.db' ]; then
            echo 'SUCCESS: Database created successfully'
        else
            echo 'ERROR: Database not created'
            exit 1
        fi
        
        echo 'All tests passed!'
    "
}

# Function to initialize database
init_database() {
    print_status "Initializing database..."
    
    if docker compose exec portfolio php init_db.php; then
        print_success "Database initialized successfully!"
        print_status "You can now access the admin panel with admin/admin123"
    else
        print_error "Failed to initialize database"
    fi
}

# Function to backup database
backup_database() {
    print_status "Creating database backup..."
    
    BACKUP_FILE="data/backup_$(date +%Y%m%d_%H%M%S).db"
    
    if [ -f "data/resume.db" ]; then
        cp "data/resume.db" "$BACKUP_FILE"
        print_success "Database backed up to: $BACKUP_FILE"
    else
        print_error "Database file not found"
    fi
}

# Function to show container status
show_status() {
    print_status "Container status:"
    docker compose ps
    
    print_status "Application health:"
    if curl -s -f http://localhost:$PORT >/dev/null 2>&1; then
        print_success "Application is responding"
    else
        print_warning "Application may not be responding"
    fi
}

# Function to access container shell
access_shell() {
    print_status "Accessing container shell..."
    docker compose exec portfolio bash
}

# Main script logic
main() {
    # Check if Docker is available
    check_docker
    
    # Handle commands
    case "${1:-help}" in
        "build")
            build_image
            ;;
        "run")
            run_compose
            ;;
        "stop")
            stop_containers
            ;;
        "restart")
            restart_app
            ;;
        "logs")
            show_logs
            ;;
        "clean")
            clean_up
            ;;
        "dev")
            run_dev
            ;;
        "test")
            run_tests
            ;;
        "init")
            init_database
            ;;
        "backup")
            backup_database
            ;;
        "status")
            show_status
            ;;
        "shell")
            access_shell
            ;;
        "help"|"--help"|"-h")
            show_help
            ;;
        *)
            print_error "Unknown command: $1"
            echo ""
            show_help
            exit 1
            ;;
    esac
}

# Run main function with all arguments
main "$@"