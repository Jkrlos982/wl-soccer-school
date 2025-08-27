#!/bin/bash

# WL School - Development Environment with Hot Reload
# This script sets up the development environment with hot reload capabilities

set -e

echo "🚀 Starting WL School Development Environment with Hot Reload..."

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "❌ Docker is not running. Please start Docker and try again."
    exit 1
fi

# Stop any existing containers
echo "🛑 Stopping existing containers..."
docker-compose -f docker-compose.dev.yml down

# Remove any orphaned containers
docker-compose -f docker-compose.dev.yml down --remove-orphans

# Build and start development containers
echo "🔨 Building and starting development containers..."
docker-compose -f docker-compose.dev.yml up --build -d

# Wait for services to be ready
echo "⏳ Waiting for services to be ready..."
sleep 10

# Check service status
echo "📊 Checking service status..."
docker-compose -f docker-compose.dev.yml ps

echo ""
echo "✅ Development environment is ready!"
echo ""
echo "📱 Frontend (React PWA): http://localhost:3000"
echo "🔐 Auth Service API: http://localhost:8001"
echo "🌐 Nginx Gateway: http://localhost:8000"
echo "🗄️  phpMyAdmin: http://localhost:8080"
echo "🔴 Redis Commander: http://localhost:8081"
echo ""
echo "📝 Hot Reload Features:"
echo "   • Frontend: Changes in ./frontend-pwa/ will auto-reload"
echo "   • Backend: Changes in ./microservices/auth-service/ will auto-reload"
echo ""
echo "🔧 Development Commands:"
echo "   • View logs: docker-compose -f docker-compose.dev.yml logs -f [service-name]"
echo "   • Stop services: docker-compose -f docker-compose.dev.yml down"
echo "   • Restart service: docker-compose -f docker-compose.dev.yml restart [service-name]"
echo ""
echo "🎯 Happy coding! Your changes will be reflected immediately."