#!/bin/bash

# WL-School Reset Script
# This script resets the development environment

set -e

echo "🔄 Resetting WL-School Development Environment..."

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

# Confirm reset
confirm_reset() {
    print_warning "This will stop all containers, remove volumes, and delete all data."
    read -p "Are you sure you want to reset everything? (y/N): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        print_status "Reset cancelled."
        exit 0
    fi
}

# Stop and remove containers
stop_containers() {
    print_status "Stopping and removing containers..."
    
    if [ -f "docker-compose.yml" ]; then
        docker-compose down -v --remove-orphans
        print_success "Containers stopped and removed"
    else
        print_warning "docker-compose.yml not found, skipping container cleanup"
    fi
}

# Remove Docker images
remove_images() {
    print_status "Removing Docker images..."
    
    # Remove WL-School related images
    docker images | grep wl-school | awk '{print $3}' | xargs -r docker rmi -f
    
    # Remove dangling images
    docker image prune -f
    
    print_success "Docker images cleaned up"
}

# Remove volumes
remove_volumes() {
    print_status "Removing Docker volumes..."
    
    # Remove WL-School volumes
    docker volume ls | grep wl-school | awk '{print $2}' | xargs -r docker volume rm
    
    # Remove dangling volumes
    docker volume prune -f
    
    print_success "Docker volumes removed"
}

# Clean up directories
clean_directories() {
    print_status "Cleaning up directories..."
    
    directories_to_clean=(
        "storage/logs"
        "api-gateway"
        "auth-service"
        "financial-service"
        "sports-service"
        "notification-service"
        "medical-service"
        "payroll-service"
        "report-service"
        "calendar-service"
        "customization-service"
        "frontend-pwa"
    )
    
    for dir in "${directories_to_clean[@]}"; do
        if [ -d "$dir" ]; then
            rm -rf "$dir"
            print_status "Removed directory: $dir"
        fi
    done
    
    print_success "Directories cleaned up"
}

# Remove environment file
remove_env() {
    if [ -f ".env" ]; then
        read -p "Remove .env file? (y/N): " -n 1 -r
        echo
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            rm .env
            print_success ".env file removed"
        else
            print_status ".env file kept"
        fi
    fi
}

# Clean Docker system
clean_docker_system() {
    print_status "Cleaning Docker system..."
    
    # Remove unused networks
    docker network prune -f
    
    # Remove build cache
    docker builder prune -f
    
    print_success "Docker system cleaned"
}

# Display final information
show_final_info() {
    print_success "🎉 WL-School environment reset completed!"
    echo ""
    echo "📋 What was cleaned:"
    echo "   • All Docker containers stopped and removed"
    echo "   • All Docker volumes removed"
    echo "   • All WL-School Docker images removed"
    echo "   • All service directories removed"
    echo "   • Log files cleared"
    echo "   • Docker system cleaned"
    echo ""
    echo "🚀 To set up the environment again:"
    echo "   ./scripts/setup.sh"
    echo ""
}

# Main execution
main() {
    confirm_reset
    stop_containers
    remove_volumes
    remove_images
    clean_directories
    remove_env
    clean_docker_system
    show_final_info
}

# Run main function
main "$@"