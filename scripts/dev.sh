#!/bin/bash

# WL-School Development Script
# This script provides common development tasks

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Show usage
show_usage() {
    echo "🛠️  WL-School Development Script"
    echo ""
    echo "Usage: $0 [command] [options]"
    echo ""
    echo "Commands:"
    echo "  start           Start all services"
    echo "  stop            Stop all services"
    echo "  restart         Restart all services"
    echo "  status          Show status of all services"
    echo "  logs [service]  Show logs (optionally for specific service)"
    echo "  build           Build all services"
    echo "  rebuild         Rebuild all services (no cache)"
    echo "  shell [service] Open shell in service container"
    echo "  migrate         Run migrations in all services"
    echo "  seed            Seed databases with initial data"
    echo "  test [service]  Run tests (optionally for specific service)"
    echo "  clean           Clean up Docker resources"
    echo "  update          Update all repositories"
    echo "  backup          Backup databases"
    echo "  restore [file]  Restore databases from backup"
    echo "  health          Check health of all services"
    echo ""
    echo "Examples:"
    echo "  $0 start"
    echo "  $0 logs auth-service"
    echo "  $0 shell api-gateway"
    echo "  $0 test financial-service"
}

# Start services
start_services() {
    print_status "Starting WL-School services..."
    docker-compose up -d
    print_success "Services started"
    show_service_status
}

# Stop services
stop_services() {
    print_status "Stopping WL-School services..."
    docker-compose down
    print_success "Services stopped"
}

# Restart services
restart_services() {
    print_status "Restarting WL-School services..."
    docker-compose restart
    print_success "Services restarted"
    show_service_status
}

# Show service status
show_service_status() {
    print_status "Service status:"
    docker-compose ps
}

# Show logs
show_logs() {
    local service=$1
    if [ -n "$service" ]; then
        print_status "Showing logs for $service..."
        docker-compose logs -f "$service"
    else
        print_status "Showing logs for all services..."
        docker-compose logs -f
    fi
}

# Build services
build_services() {
    print_status "Building WL-School services..."
    docker-compose build
    print_success "Services built"
}

# Rebuild services
rebuild_services() {
    print_status "Rebuilding WL-School services (no cache)..."
    docker-compose build --no-cache
    print_success "Services rebuilt"
}

# Open shell in service
open_shell() {
    local service=$1
    if [ -z "$service" ]; then
        print_error "Please specify a service name"
        echo "Available services: api-gateway, auth-service, financial-service, sports-service, notification-service, medical-service, payroll-service, report-service, calendar-service, customization-service, frontend-pwa"
        exit 1
    fi
    
    print_status "Opening shell in $service..."
    docker-compose exec "$service" /bin/bash
}

# Run migrations
run_migrations() {
    print_status "Running migrations in all Laravel services..."
    
    services=("api-gateway" "auth-service" "financial-service" "sports-service" "notification-service" "medical-service" "payroll-service" "report-service" "calendar-service" "customization-service")
    
    for service in "${services[@]}"; do
        print_status "Running migrations in $service..."
        docker-compose exec "$service" php artisan migrate --force || print_warning "Migration failed for $service"
    done
    
    print_success "Migrations completed"
}

# Seed databases
seed_databases() {
    print_status "Seeding databases..."
    
    services=("api-gateway" "auth-service" "financial-service" "sports-service" "notification-service" "medical-service" "payroll-service" "report-service" "calendar-service" "customization-service")
    
    for service in "${services[@]}"; do
        print_status "Seeding database for $service..."
        docker-compose exec "$service" php artisan db:seed --force || print_warning "Seeding failed for $service"
    done
    
    print_success "Database seeding completed"
}

# Run tests
run_tests() {
    local service=$1
    
    if [ -n "$service" ]; then
        print_status "Running tests for $service..."
        docker-compose exec "$service" php artisan test
    else
        print_status "Running tests for all services..."
        services=("api-gateway" "auth-service" "financial-service" "sports-service" "notification-service" "medical-service" "payroll-service" "report-service" "calendar-service" "customization-service")
        
        for svc in "${services[@]}"; do
            print_status "Running tests for $svc..."
            docker-compose exec "$svc" php artisan test || print_warning "Tests failed for $svc"
        done
    fi
}

# Clean Docker resources
clean_docker() {
    print_status "Cleaning Docker resources..."
    
    # Remove stopped containers
    docker container prune -f
    
    # Remove unused images
    docker image prune -f
    
    # Remove unused volumes
    docker volume prune -f
    
    # Remove unused networks
    docker network prune -f
    
    print_success "Docker resources cleaned"
}

# Update repositories
update_repositories() {
    print_status "Updating all repositories..."
    
    directories=("api-gateway" "auth-service" "financial-service" "sports-service" "notification-service" "medical-service" "payroll-service" "report-service" "calendar-service" "customization-service" "frontend-pwa")
    
    for dir in "${directories[@]}"; do
        if [ -d "$dir" ]; then
            print_status "Updating $dir..."
            cd "$dir"
            git pull origin main || git pull origin master
            cd ..
        fi
    done
    
    print_success "Repositories updated"
}

# Backup databases
backup_databases() {
    print_status "Creating database backup..."
    
    timestamp=$(date +"%Y%m%d_%H%M%S")
    backup_dir="backups/$timestamp"
    mkdir -p "$backup_dir"
    
    databases=("wl_school_gateway" "wl_school_auth" "wl_school_financial" "wl_school_sports" "wl_school_notification" "wl_school_medical" "wl_school_payroll" "wl_school_report" "wl_school_calendar" "wl_school_customization")
    
    for db in "${databases[@]}"; do
        print_status "Backing up $db..."
        docker-compose exec mysql-gateway mysqldump -u root -prootpassword "$db" > "$backup_dir/$db.sql"
    done
    
    print_success "Backup created in $backup_dir"
}

# Restore databases
restore_databases() {
    local backup_file=$1
    
    if [ -z "$backup_file" ]; then
        print_error "Please specify backup file or directory"
        exit 1
    fi
    
    print_status "Restoring databases from $backup_file..."
    # Implementation depends on backup format
    print_warning "Restore functionality needs to be implemented based on backup format"
}

# Check health of services
check_health() {
    print_status "Checking health of all services..."
    
    services=(
        "api-gateway:8000"
        "auth-service:8001"
        "financial-service:8002"
        "sports-service:8003"
        "notification-service:8004"
        "medical-service:8005"
        "payroll-service:8006"
        "report-service:8007"
        "calendar-service:8008"
        "customization-service:8009"
        "frontend-pwa:3000"
    )
    
    for service in "${services[@]}"; do
        IFS=':' read -r name port <<< "$service"
        if curl -f -s "http://localhost:$port/health" > /dev/null 2>&1; then
            print_success "$name is healthy"
        else
            print_error "$name is not responding"
        fi
    done
}

# Main execution
main() {
    case $1 in
        start)
            start_services
            ;;
        stop)
            stop_services
            ;;
        restart)
            restart_services
            ;;
        status)
            show_service_status
            ;;
        logs)
            show_logs $2
            ;;
        build)
            build_services
            ;;
        rebuild)
            rebuild_services
            ;;
        shell)
            open_shell $2
            ;;
        migrate)
            run_migrations
            ;;
        seed)
            seed_databases
            ;;
        test)
            run_tests $2
            ;;
        clean)
            clean_docker
            ;;
        update)
            update_repositories
            ;;
        backup)
            backup_databases
            ;;
        restore)
            restore_databases $2
            ;;
        health)
            check_health
            ;;
        *)
            show_usage
            ;;
    esac
}

# Run main function
main "$@"