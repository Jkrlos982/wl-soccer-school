#!/bin/bash

# Financial Service Development Script
# This script helps manage the development environment with hot reload

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

# Function to show help
show_help() {
    echo "Financial Service Development Script"
    echo ""
    echo "Usage: ./dev.sh [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  start     Start development environment with hot reload"
    echo "  stop      Stop development environment"
    echo "  restart   Restart development environment"
    echo "  logs      Show logs from all services"
    echo "  shell     Access PHP container shell"
    echo "  migrate   Run database migrations"
    echo "  seed      Run database seeders"
    echo "  test      Run tests"
    echo "  build     Rebuild development containers"
    echo "  clean     Clean up containers and volumes"
    echo "  status    Show container status"
    echo "  help      Show this help message"
}

# Function to start development environment
start_dev() {
    print_status "Starting Financial Service development environment..."
    docker-compose -f docker-compose.dev.yml up -d
    print_success "Development environment started!"
    print_status "Services available at:"
    echo "  - API: http://localhost:8003"
    echo "  - Database: localhost:3307"
    print_status "Use './dev.sh logs' to see container logs"
}

# Function to stop development environment
stop_dev() {
    print_status "Stopping Financial Service development environment..."
    docker-compose -f docker-compose.dev.yml down
    print_success "Development environment stopped!"
}

# Function to restart development environment
restart_dev() {
    print_status "Restarting Financial Service development environment..."
    docker-compose -f docker-compose.dev.yml restart
    print_success "Development environment restarted!"
}

# Function to show logs
show_logs() {
    print_status "Showing logs from all services..."
    docker-compose -f docker-compose.dev.yml logs -f
}

# Function to access shell
access_shell() {
    print_status "Accessing PHP container shell..."
    docker-compose -f docker-compose.dev.yml exec financial-service bash
}

# Function to run migrations
run_migrations() {
    print_status "Running database migrations..."
    docker-compose -f docker-compose.dev.yml exec financial-service php artisan migrate
    print_success "Migrations completed!"
}

# Function to run seeders
run_seeders() {
    print_status "Running database seeders..."
    docker-compose -f docker-compose.dev.yml exec financial-service php artisan db:seed
    print_success "Seeders completed!"
}

# Function to run tests
run_tests() {
    print_status "Running tests..."
    docker-compose -f docker-compose.dev.yml exec financial-service php artisan test
}

# Function to build containers
build_containers() {
    print_status "Building development containers..."
    docker-compose -f docker-compose.dev.yml build --no-cache
    print_success "Containers built successfully!"
}

# Function to clean up
clean_up() {
    print_warning "This will remove all containers and volumes. Are you sure? (y/N)"
    read -r response
    if [[ "$response" =~ ^([yY][eE][sS]|[yY])$ ]]; then
        print_status "Cleaning up containers and volumes..."
        docker-compose -f docker-compose.dev.yml down -v --remove-orphans
        docker system prune -f
        print_success "Cleanup completed!"
    else
        print_status "Cleanup cancelled."
    fi
}

# Function to show status
show_status() {
    print_status "Container status:"
    docker-compose -f docker-compose.dev.yml ps
}

# Main script logic
case "$1" in
    start)
        start_dev
        ;;
    stop)
        stop_dev
        ;;
    restart)
        restart_dev
        ;;
    logs)
        show_logs
        ;;
    shell)
        access_shell
        ;;
    migrate)
        run_migrations
        ;;
    seed)
        run_seeders
        ;;
    test)
        run_tests
        ;;
    build)
        build_containers
        ;;
    clean)
        clean_up
        ;;
    status)
        show_status
        ;;
    help|--help|-h)
        show_help
        ;;
    "")
        print_error "No command specified. Use './dev.sh help' for available commands."
        exit 1
        ;;
    *)
        print_error "Unknown command: $1"
        print_status "Use './dev.sh help' for available commands."
        exit 1
        ;;
esac