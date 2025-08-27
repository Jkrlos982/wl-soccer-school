# WL School Auth Service - API Documentation

## Overview

This document provides comprehensive information about the WL School Authentication Service API. The Auth Service handles user authentication, authorization, role-based access control, and permission management for the WL School Management System.

## API Documentation Access

### Local Development

- **Swagger UI Interface**: http://localhost:8001/api/documentation
- **OpenAPI JSON**: http://localhost:8001/docs/api-docs.json
- **OpenAPI YAML**: Available in `openapi.yaml` file

### Via API Gateway

- **Swagger UI Interface**: http://localhost:8000/api/documentation
- **OpenAPI JSON**: http://localhost:8000/docs/api-docs.json

## Quick Start

### 1. Authentication Flow

```bash
# Register a new user
curl -X POST http://localhost:8001/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "school_subdomain": "testschool",
    "first_name": "John",
    "last_name": "Doe",
    "email": "john.doe@testschool.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "student"
  }'

# Login
curl -X POST http://localhost:8001/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john.doe@testschool.com",
    "password": "password123",
    "school_subdomain": "testschool"
  }'
```

### 2. Using JWT Token

After successful login/registration, use the returned JWT token in the Authorization header:

```bash
# Get current user profile
curl -X GET http://localhost:8001/api/v1/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"

# Get all roles
curl -X GET http://localhost:8001/api/v1/roles \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

## API Endpoints Overview

### Authentication Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/v1/auth/login` | User login | No |
| POST | `/v1/auth/register` | User registration | No |
| GET | `/v1/auth/me` | Get current user profile | Yes |
| POST | `/v1/auth/logout` | User logout | Yes |
| POST | `/v1/auth/refresh` | Refresh JWT token | Yes |

### Role Management Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/v1/roles` | Get all roles | Yes |
| GET | `/v1/permissions` | Get all permissions | Yes |

### User Role Management Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/v1/users/{userId}/roles` | Get user roles | Yes |
| POST | `/v1/users/{userId}/roles` | Assign roles to user | Yes |

### Role Permission Management Endpoints

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| POST | `/v1/roles/{roleId}/permissions` | Assign permissions to role | Yes |

## Authentication & Authorization

### JWT Token Authentication

The API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:

```
Authorization: Bearer <your-jwt-token>
```

### Token Lifecycle

- **Expiration**: Tokens expire after 1 hour (3600 seconds)
- **Refresh**: Use the `/v1/auth/refresh` endpoint to get a new token
- **Logout**: Use the `/v1/auth/logout` endpoint to invalidate the current token

### Rate Limiting

- **Login attempts**: Limited to 5 attempts per IP address within 5 minutes
- **Other endpoints**: Standard Laravel rate limiting applies

## Error Handling

All API endpoints return consistent error responses:

### Success Response Format
```json
{
  "success": true,
  "message": "Operation successful",
  "data": {
    // Response data
  }
}
```

### Error Response Format
```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field_name": ["Specific error message"]
  }
}
```

### Common HTTP Status Codes

- **200**: Success
- **201**: Created (for registration)
- **401**: Unauthorized (invalid/missing token)
- **403**: Forbidden (insufficient permissions)
- **404**: Not Found
- **422**: Validation Error
- **429**: Too Many Requests (rate limited)
- **500**: Internal Server Error

## Data Models

### User Model
```json
{
  "id": 123,
  "email": "user@testschool.com",
  "first_name": "John",
  "last_name": "Doe",
  "full_name": "John Doe",
  "phone": "+1234567890",
  "avatar_url": "https://example.com/avatar.jpg",
  "is_active": true,
  "email_verified": true,
  "last_login_at": "2024-01-15T10:30:00.000Z",
  "school": {
    "id": 1,
    "name": "Test School",
    "subdomain": "testschool"
  },
  "roles": [
    {
      "id": 1,
      "name": "student",
      "display_name": "Student"
    }
  ]
}
```

### Role Model
```json
{
  "id": 1,
  "name": "admin",
  "display_name": "Administrator",
  "description": "System administrator with full access",
  "permissions_count": 15,
  "permissions": ["view-users", "edit-users", "delete-users"]
}
```

## Multi-Tenancy

The Auth Service supports multi-tenancy through school subdomains:

- Each school has a unique subdomain (e.g., "testschool")
- Users belong to specific schools
- Authentication requires specifying the school subdomain
- Users can only access data from their own school

## Security Features

### Password Requirements
- Minimum 8 characters for registration
- Minimum 6 characters for login (legacy support)
- Password confirmation required for registration

### Account Security
- Email verification support
- Account deactivation capability
- Last login tracking
- Rate limiting on login attempts

### Permission System
- Role-based access control (RBAC)
- Granular permissions
- Permission inheritance through roles
- School-level isolation

## Development Setup

### Prerequisites
- PHP 8.1+
- Laravel 10+
- MySQL/PostgreSQL database
- Composer

### Installation

1. Install dependencies:
```bash
composer install
```

2. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

3. Configure database and run migrations:
```bash
php artisan migrate
php artisan db:seed
```

4. Generate API documentation:
```bash
php artisan l5-swagger:generate
```

5. Start the development server:
```bash
php artisan serve --port=8001
```

### Environment Variables

```env
# JWT Configuration
JWT_SECRET=your-jwt-secret
JWT_TTL=60

# Swagger Documentation
L5_SWAGGER_GENERATE_ALWAYS=true
L5_SWAGGER_GENERATE_YAML_COPY=true

# API Configuration
API_VERSION=v1
API_PREFIX=api
```

## Testing

The API includes comprehensive test coverage:

### Running Tests
```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

### Test Categories
- **Unit Tests**: Individual component testing
- **Feature Tests**: End-to-end API testing
- **Integration Tests**: Multi-service interaction testing

## Troubleshooting

### Common Issues

1. **Token Expired Error**
   - Use the refresh endpoint to get a new token
   - Check token expiration time (1 hour default)

2. **Rate Limiting**
   - Wait for the rate limit window to reset
   - Check IP address restrictions

3. **School Not Found**
   - Verify school subdomain exists and is active
   - Check school subscription status

4. **Permission Denied**
   - Verify user has required role/permissions
   - Check if user belongs to the correct school

### Debug Mode

Enable debug mode in development for detailed error messages:

```env
APP_DEBUG=true
```

## Support

For technical support or questions:

- **Documentation**: Check this README and OpenAPI specification
- **Issues**: Create GitHub issues for bugs or feature requests
- **Contact**: dev@wlschool.com

## Changelog

### Version 1.0.0
- Initial API release
- JWT authentication
- Role-based access control
- Multi-tenant support
- Comprehensive documentation