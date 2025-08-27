#!/bin/bash

# WL School API Gateway Deployment Script
# This script helps deploy the API Gateway with all necessary configurations

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="wl-school"
COMPOSE_FILE="docker-compose.gateway.yml"
ENV_FILE=".env.gateway"
ENV_EXAMPLE=".env.gateway.example"

# Functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

check_dependencies() {
    log_info "Checking dependencies..."
    
    # Check Docker
    if ! command -v docker &> /dev/null; then
        log_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    # Check Docker Compose
    if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
        log_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    # Check if Docker is running
    if ! docker info &> /dev/null; then
        log_error "Docker is not running. Please start Docker first."
        exit 1
    fi
    
    log_success "All dependencies are available."
}

setup_environment() {
    log_info "Setting up environment..."
    
    if [ ! -f "$ENV_FILE" ]; then
        if [ -f "$ENV_EXAMPLE" ]; then
            log_warning "Environment file not found. Copying from example..."
            cp "$ENV_EXAMPLE" "$ENV_FILE"
            log_warning "Please edit $ENV_FILE with your actual configuration values."
            log_warning "Pay special attention to JWT_SECRET, database passwords, and domain settings."
        else
            log_error "Environment example file not found. Cannot proceed."
            exit 1
        fi
    else
        log_success "Environment file found."
    fi
}

create_ssl_directory() {
    log_info "Creating SSL directory..."
    
    mkdir -p nginx/ssl
    
    if [ ! -f "nginx/ssl/dhparam.pem" ]; then
        log_info "Generating DH parameters (this may take a while)..."
        openssl dhparam -out nginx/ssl/dhparam.pem 2048
        log_success "DH parameters generated."
    fi
    
    if [ ! -f "nginx/ssl/certificate.crt" ] || [ ! -f "nginx/ssl/private.key" ]; then
        log_warning "SSL certificates not found. Generating self-signed certificates for development..."
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout nginx/ssl/private.key \
            -out nginx/ssl/certificate.crt \
            -subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"
        log_success "Self-signed certificates generated."
        log_warning "For production, replace with valid SSL certificates."
    fi
}

validate_configuration() {
    log_info "Validating Nginx configuration..."
    
    # Test Nginx configuration using a temporary container
    if docker run --rm -v "$(pwd)/nginx/nginx.conf:/etc/nginx/nginx.conf:ro" \
                      -v "$(pwd)/nginx/conf.d:/etc/nginx/conf.d:ro" \
                      -v "$(pwd)/nginx/lua:/etc/nginx/lua:ro" \
                      openresty/openresty:alpine nginx -t; then
        log_success "Nginx configuration is valid."
    else
        log_error "Nginx configuration is invalid. Please check your configuration files."
        exit 1
    fi
}

build_services() {
    log_info "Building services..."
    
    # Load environment variables
    if [ -f "$ENV_FILE" ]; then
        export $(cat "$ENV_FILE" | grep -v '^#' | xargs)
    fi
    
    # Build services
    docker-compose -f "$COMPOSE_FILE" build --no-cache
    
    log_success "Services built successfully."
}

start_services() {
    log_info "Starting services..."
    
    # Load environment variables
    if [ -f "$ENV_FILE" ]; then
        export $(cat "$ENV_FILE" | grep -v '^#' | xargs)
    fi
    
    # Start services
    docker-compose -f "$COMPOSE_FILE" up -d
    
    log_success "Services started successfully."
}

check_health() {
    log_info "Checking service health..."
    
    # Wait for services to be ready
    sleep 30
    
    # Check API Gateway health
    if curl -f -s http://localhost/health > /dev/null; then
        log_success "API Gateway is healthy."
    else
        log_warning "API Gateway health check failed. Check logs with: docker-compose -f $COMPOSE_FILE logs api-gateway"
    fi
    
    # Check database connection
    if docker-compose -f "$COMPOSE_FILE" exec -T mysql mysqladmin ping -h localhost > /dev/null 2>&1; then
        log_success "Database is healthy."
    else
        log_warning "Database health check failed. Check logs with: docker-compose -f $COMPOSE_FILE logs mysql"
    fi
}

show_status() {
    log_info "Service Status:"
    docker-compose -f "$COMPOSE_FILE" ps
    
    echo ""
    log_info "Available endpoints:"
    echo "  - API Gateway: http://localhost (HTTP) / https://localhost (HTTPS)"
    echo "  - Frontend: http://localhost:3000"
    echo "  - Grafana: http://localhost:3001 (admin/admin)"
    echo "  - Prometheus: http://localhost:9090"
    echo ""
    log_info "Logs can be viewed with: docker-compose -f $COMPOSE_FILE logs [service-name]"
}

cleanup() {
    log_info "Cleaning up..."
    docker-compose -f "$COMPOSE_FILE" down
    docker system prune -f
    log_success "Cleanup completed."
}

show_help() {
    echo "WL School API Gateway Deployment Script"
    echo ""
    echo "Usage: $0 [COMMAND]"
    echo ""
    echo "Commands:"
    echo "  deploy    - Full deployment (default)"
    echo "  start     - Start services"
    echo "  stop      - Stop services"
    echo "  restart   - Restart services"
    echo "  status    - Show service status"
    echo "  logs      - Show logs for all services"
    echo "  cleanup   - Stop services and clean up"
    echo "  help      - Show this help message"
    echo ""
    echo "Examples:"
    echo "  $0 deploy          # Full deployment"
    echo "  $0 logs api-gateway # Show API Gateway logs"
    echo "  $0 restart         # Restart all services"
}

# Main execution
case "${1:-deploy}" in
    "deploy")
        log_info "Starting WL School API Gateway deployment..."
        check_dependencies
        setup_environment
        create_ssl_directory
        validate_configuration
        build_services
        start_services
        check_health
        show_status
        log_success "Deployment completed successfully!"
        ;;
    "start")
        start_services
        show_status
        ;;
    "stop")
        log_info "Stopping services..."
        docker-compose -f "$COMPOSE_FILE" down
        log_success "Services stopped."
        ;;
    "restart")
        log_info "Restarting services..."
        docker-compose -f "$COMPOSE_FILE" restart
        show_status
        ;;
    "status")
        show_status
        ;;
    "logs")
        if [ -n "$2" ]; then
            docker-compose -f "$COMPOSE_FILE" logs -f "$2"
        else
            docker-compose -f "$COMPOSE_FILE" logs -f
        fi
        ;;
    "cleanup")
        cleanup
        ;;
    "help")
        show_help
        ;;
    *)
        log_error "Unknown command: $1"
        show_help
        exit 1
        ;;
esac