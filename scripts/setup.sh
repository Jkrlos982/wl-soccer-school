#!/bin/bash

# WL-School Setup Script
# This script sets up the development environment for WL-School

set -e

echo "🚀 Setting up WL-School Development Environment..."

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

# Check if Docker is installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_error "Docker is not installed. Please install Docker first."
        exit 1
    fi
    
    if ! command -v docker-compose &> /dev/null; then
        print_error "Docker Compose is not installed. Please install Docker Compose first."
        exit 1
    fi
    
    print_success "Docker and Docker Compose are installed"
}

# Check if Git is installed
check_git() {
    if ! command -v git &> /dev/null; then
        print_error "Git is not installed. Please install Git first."
        exit 1
    fi
    
    print_success "Git is installed"
}

# Create directory structure
create_directories() {
    print_status "Creating directory structure..."
    
    directories=(
        "storage/logs/gateway"
        "storage/logs/auth"
        "storage/logs/financial"
        "storage/logs/sports"
        "storage/logs/notification"
        "storage/logs/medical"
        "storage/logs/payroll"
        "storage/logs/report"
        "storage/logs/calendar"
        "storage/logs/customization"
        "storage/logs/nginx"
        "database/init"
        "nginx/ssl"
    )
    
    for dir in "${directories[@]}"; do
        mkdir -p "$dir"
        print_status "Created directory: $dir"
    done
    
    print_success "Directory structure created"
}

# Copy environment file
setup_environment() {
    print_status "Setting up environment file..."
    
    if [ ! -f ".env" ]; then
        cp .env.example .env
        print_success "Environment file created from .env.example"
        print_warning "Please update the .env file with your specific configuration"
    else
        print_warning ".env file already exists, skipping..."
    fi
}

# Generate SSL certificates for development
generate_ssl_certificates() {
    print_status "Generating SSL certificates for development..."
    
    if [ ! -f "nginx/ssl/wl-school.crt" ]; then
        openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
            -keyout nginx/ssl/wl-school.key \
            -out nginx/ssl/wl-school.crt \
            -subj "/C=CO/ST=Bogota/L=Bogota/O=WL-School/OU=Development/CN=wl-school.local"
        
        print_success "SSL certificates generated"
    else
        print_warning "SSL certificates already exist, skipping..."
    fi
}

# Clone repositories
clone_repositories() {
    print_status "Cloning repositories..."
    
    repositories=(
        "wl-school-api-gateway:api-gateway"
        "wl-school-auth-service:auth-service"
        "wl-school-financial-service:financial-service"
        "wl-school-sports-service:sports-service"
        "wl-school-notification-service:notification-service"
        "wl-school-medical-service:medical-service"
        "wl-school-payroll-service:payroll-service"
        "wl-school-report-service:report-service"
        "wl-school-customization-service:customization-service"
        "wl-school-calendar-service:calendar-service"
        "wl-school-frontend-pwa:frontend-pwa"
    )
    
    for repo in "${repositories[@]}"; do
        IFS=':' read -r repo_name local_dir <<< "$repo"
        
        if [ ! -d "$local_dir" ]; then
            git clone "https://github.com/Jkrlos982/$repo_name.git" "$local_dir"
            print_success "Cloned $repo_name to $local_dir"
        else
            print_warning "Directory $local_dir already exists, skipping clone..."
        fi
    done
}

# Setup database initialization
setup_database_init() {
    print_status "Setting up database initialization..."
    
    cat > database/init/01-create-databases.sql << 'EOF'
-- Create databases for all microservices
CREATE DATABASE IF NOT EXISTS wl_school_gateway;
CREATE DATABASE IF NOT EXISTS wl_school_auth;
CREATE DATABASE IF NOT EXISTS wl_school_financial;
CREATE DATABASE IF NOT EXISTS wl_school_sports;
CREATE DATABASE IF NOT EXISTS wl_school_notification;
CREATE DATABASE IF NOT EXISTS wl_school_medical;
CREATE DATABASE IF NOT EXISTS wl_school_payroll;
CREATE DATABASE IF NOT EXISTS wl_school_report;
CREATE DATABASE IF NOT EXISTS wl_school_calendar;
CREATE DATABASE IF NOT EXISTS wl_school_customization;

-- Grant privileges
GRANT ALL PRIVILEGES ON wl_school_gateway.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_auth.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_financial.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_sports.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_notification.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_medical.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_payroll.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_report.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_calendar.* TO 'root'@'%';
GRANT ALL PRIVILEGES ON wl_school_customization.* TO 'root'@'%';

FLUSH PRIVILEGES;
EOF
    
    print_success "Database initialization script created"
}

# Build and start services
start_services() {
    print_status "Building and starting services..."
    
    # Build images
    docker-compose build
    
    # Start services
    docker-compose up -d
    
    print_success "Services started successfully"
    
    # Wait for services to be ready
    print_status "Waiting for services to be ready..."
    sleep 30
    
    # Show running services
    docker-compose ps
}

# Display final information
show_final_info() {
    print_success "🎉 WL-School setup completed successfully!"
    echo ""
    echo "📋 Available services:"
    echo "   • Frontend PWA: http://localhost:3000"
    echo "   • API Gateway: http://localhost:8000"
    echo "   • Auth Service: http://localhost:8001"
    echo "   • Financial Service: http://localhost:8002"
    echo "   • Sports Service: http://localhost:8003"
    echo "   • Notification Service: http://localhost:8004"
    echo "   • Medical Service: http://localhost:8005"
    echo "   • Payroll Service: http://localhost:8006"
    echo "   • Report Service: http://localhost:8007"
    echo "   • Calendar Service: http://localhost:8008"
    echo "   • Customization Service: http://localhost:8009"
    echo ""
    echo "🛠️  Management tools:"
    echo "   • phpMyAdmin: http://localhost:8080"
    echo "   • Redis Commander: http://localhost:8081"
    echo ""
    echo "📝 Next steps:"
    echo "   1. Update the .env file with your specific configuration"
    echo "   2. Run migrations in each microservice"
    echo "   3. Seed the databases with initial data"
    echo "   4. Start developing!"
    echo ""
    echo "🔧 Useful commands:"
    echo "   • Stop services: docker-compose down"
    echo "   • View logs: docker-compose logs -f [service-name]"
    echo "   • Rebuild services: docker-compose build --no-cache"
    echo "   • Reset everything: ./scripts/reset.sh"
}

# Main execution
main() {
    echo "Starting WL-School setup..."
    
    check_docker
    check_git
    create_directories
    setup_environment
    generate_ssl_certificates
    clone_repositories
    setup_database_init
    start_services
    show_final_info
}

# Run main function
main "$@"