# WL-School Development Makefile
# This Makefile provides shortcuts for common development tasks

.PHONY: help setup start stop restart status logs build rebuild clean reset update backup health test migrate seed shell

# Default target
help:
	@echo "🛠️  WL-School Development Commands"
	@echo ""
	@echo "Setup Commands:"
	@echo "  make setup     - Initial setup of the development environment"
	@echo "  make reset     - Reset the entire development environment"
	@echo ""
	@echo "Service Management:"
	@echo "  make start     - Start all services"
	@echo "  make stop      - Stop all services"
	@echo "  make restart   - Restart all services"
	@echo "  make status    - Show status of all services"
	@echo "  make health    - Check health of all services"
	@echo ""
	@echo "Development:"
	@echo "  make logs      - Show logs for all services"
	@echo "  make logs-SERVICE - Show logs for specific service"
	@echo "  make build     - Build all services"
	@echo "  make rebuild   - Rebuild all services (no cache)"
	@echo "  make migrate   - Run migrations in all services"
	@echo "  make seed      - Seed databases with initial data"
	@echo "  make test      - Run tests in all services"
	@echo "  make test-SERVICE - Run tests for specific service"
	@echo ""
	@echo "Utilities:"
	@echo "  make shell-SERVICE - Open shell in service container"
	@echo "  make clean     - Clean Docker resources"
	@echo "  make update    - Update all repositories"
	@echo "  make backup    - Backup databases"
	@echo ""
	@echo "Available Services:"
	@echo "  api-gateway, auth-service, financial-service, sports-service,"
	@echo "  notification-service, medical-service, payroll-service,"
	@echo "  report-service, calendar-service, customization-service, frontend-pwa"
	@echo ""
	@echo "Examples:"
	@echo "  make logs-auth-service"
	@echo "  make shell-api-gateway"
	@echo "  make test-financial-service"

# Setup and Reset
setup:
	@echo "🚀 Setting up WL-School development environment..."
	@./scripts/setup.sh

reset:
	@echo "🔄 Resetting WL-School development environment..."
	@./scripts/reset.sh

# Service Management
start:
	@./scripts/dev.sh start

stop:
	@./scripts/dev.sh stop

restart:
	@./scripts/dev.sh restart

status:
	@./scripts/dev.sh status

health:
	@./scripts/dev.sh health

# Development
logs:
	@./scripts/dev.sh logs

logs-%:
	@./scripts/dev.sh logs $*

build:
	@./scripts/dev.sh build

rebuild:
	@./scripts/dev.sh rebuild

migrate:
	@./scripts/dev.sh migrate

seed:
	@./scripts/dev.sh seed

test:
	@./scripts/dev.sh test

test-%:
	@./scripts/dev.sh test $*

# Utilities
shell-%:
	@./scripts/dev.sh shell $*

clean:
	@./scripts/dev.sh clean

update:
	@./scripts/dev.sh update

backup:
	@./scripts/dev.sh backup

# Docker Compose shortcuts
up:
	@docker-compose up -d

down:
	@docker-compose down

ps:
	@docker-compose ps

# Quick development shortcuts
dev: start
	@echo "🎉 Development environment is ready!"
	@echo "Frontend: http://localhost:3000"
	@echo "API Gateway: http://localhost:8000"
	@echo "phpMyAdmin: http://localhost:8080"
	@echo "Redis Commander: http://localhost:8081"

full-setup: setup dev
	@echo "✅ Full setup completed!"

# Database shortcuts
db-reset: stop
	@docker-compose down -v
	@docker-compose up -d mysql-gateway mysql-auth mysql-financial mysql-sports mysql-notification mysql-medical mysql-payroll mysql-report mysql-calendar mysql-customization
	@sleep 10
	@make migrate
	@make seed

# Frontend specific
frontend-dev:
	@docker-compose up -d frontend-pwa
	@echo "Frontend available at http://localhost:3000"

# API specific
api-dev:
	@docker-compose up -d api-gateway auth-service
	@echo "API Gateway available at http://localhost:8000"
	@echo "Auth Service available at http://localhost:8001"

# Install dependencies (when repositories are cloned)
install-deps:
	@echo "Installing dependencies for all services..."
	@for dir in api-gateway auth-service financial-service sports-service notification-service medical-service payroll-service report-service calendar-service customization-service; do \
		if [ -d "$$dir" ]; then \
			echo "Installing dependencies for $$dir..."; \
			docker-compose exec $$dir composer install --no-dev --optimize-autoloader; \
		fi; \
	done
	@if [ -d "frontend-pwa" ]; then \
		echo "Installing dependencies for frontend-pwa..."; \
		docker-compose exec frontend-pwa npm install; \
	fi

# Generate application keys
generate-keys:
	@echo "Generating application keys..."
	@for dir in api-gateway auth-service financial-service sports-service notification-service medical-service payroll-service report-service calendar-service customization-service; do \
		if [ -d "$$dir" ]; then \
			echo "Generating key for $$dir..."; \
			docker-compose exec $$dir php artisan key:generate; \
		fi; \
	done

# Clear caches
clear-cache:
	@echo "Clearing caches..."
	@for dir in api-gateway auth-service financial-service sports-service notification-service medical-service payroll-service report-service calendar-service customization-service; do \
		if [ -d "$$dir" ]; then \
			echo "Clearing cache for $$dir..."; \
			docker-compose exec $$dir php artisan cache:clear; \
			docker-compose exec $$dir php artisan config:clear; \
			docker-compose exec $$dir php artisan route:clear; \
			docker-compose exec $$dir php artisan view:clear; \
		fi; \
	done

# Production build
prod-build:
	@echo "Building for production..."
	@docker-compose -f docker-compose.prod.yml build

# Show environment info
info:
	@echo "📋 WL-School Environment Information"
	@echo "Docker version: $$(docker --version)"
	@echo "Docker Compose version: $$(docker-compose --version)"
	@echo "Current directory: $$(pwd)"
	@echo "Git branch: $$(git branch --show-current 2>/dev/null || echo 'Not a git repository')"
	@echo "Services status:"
	@docker-compose ps 2>/dev/null || echo "Services not running"