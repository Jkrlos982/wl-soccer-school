# WL School API Gateway

Este documento describe la configuración y uso del API Gateway para el sistema WL School.

## Descripción

El API Gateway actúa como punto de entrada único para todos los microservicios del sistema WL School. Proporciona:

- **Enrutamiento inteligente** a microservicios
- **Autenticación JWT** centralizada
- **Rate limiting** por IP y usuario
- **CORS** y headers de seguridad
- **Logging estructurado** en formato JSON
- **Load balancing** entre instancias
- **Health checks** automáticos
- **SSL/TLS** termination

## Arquitectura

```
┌─────────────────┐    ┌──────────────────┐    ┌─────────────────────┐
│   Frontend PWA  │────│   API Gateway    │────│   Microservicios   │
│   (Port 3000)   │    │  (Nginx + Lua)   │    │  (Ports 8001-8009) │
└─────────────────┘    │   (Port 80/443)  │    └─────────────────────┘
                       └──────────────────┘
                              │
                       ┌──────────────────┐
                       │   Base de Datos  │
                       │   MySQL + Redis  │
                       └──────────────────┘
```

## Componentes

### 1. Nginx con OpenResty
- **Imagen**: `openresty/openresty:alpine`
- **Funcionalidad**: Proxy reverso con soporte Lua
- **Configuración**: `/nginx/nginx.conf`, `/nginx/conf.d/`

### 2. Middleware JWT (Lua)
- **Archivo**: `/nginx/lua/jwt_auth.lua`
- **Funcionalidad**: Validación de tokens JWT
- **Cache**: Redis para tokens válidos

### 3. Microservicios
- **Authentication Service** (8001)
- **Financial Service** (8002)
- **Sports Service** (8003)
- **Notification Service** (8004)
- **Medical Service** (8005)
- **Payroll Service** (8006)
- **Report Service** (8007)
- **Calendar Service** (8008)
- **Customization Service** (8009)

## Instalación y Configuración

### Prerrequisitos

- Docker 20.10+
- Docker Compose 2.0+
- OpenSSL (para certificados SSL)

### Configuración Inicial

1. **Clonar el repositorio**:
   ```bash
   git clone <repository-url>
   cd wl-school
   ```

2. **Configurar variables de entorno**:
   ```bash
   cp .env.gateway.example .env.gateway
   nano .env.gateway  # Editar con valores reales
   ```

3. **Configurar SSL (Producción)**:
   ```bash
   # Copiar certificados SSL válidos
   cp your-certificate.crt nginx/ssl/certificate.crt
   cp your-private-key.key nginx/ssl/private.key
   
   # O generar certificados auto-firmados (desarrollo)
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
     -keyout nginx/ssl/private.key \
     -out nginx/ssl/certificate.crt
   ```

### Despliegue Automático

```bash
# Despliegue completo
./deploy-gateway.sh deploy

# Comandos adicionales
./deploy-gateway.sh start    # Iniciar servicios
./deploy-gateway.sh stop     # Detener servicios
./deploy-gateway.sh restart  # Reiniciar servicios
./deploy-gateway.sh status   # Ver estado
./deploy-gateway.sh logs     # Ver logs
./deploy-gateway.sh cleanup  # Limpiar todo
```

### Despliegue Manual

```bash
# Construir servicios
docker-compose -f docker-compose.gateway.yml build

# Iniciar servicios
docker-compose -f docker-compose.gateway.yml up -d

# Ver estado
docker-compose -f docker-compose.gateway.yml ps

# Ver logs
docker-compose -f docker-compose.gateway.yml logs -f api-gateway
```

## Configuración Avanzada

### Rate Limiting

El sistema implementa múltiples niveles de rate limiting:

```nginx
# Global: 1000 req/min por IP
limit_req_zone $binary_remote_addr zone=global:10m rate=1000r/m;

# Usuario autenticado: 60 req/min
limit_req_zone $jwt_user_id zone=user:10m rate=60r/m;

# Login: 5 intentos/min por IP
limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
```

### CORS Configuration

```nginx
# Headers CORS automáticos
add_header Access-Control-Allow-Origin $cors_origin always;
add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS" always;
add_header Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With" always;
add_header Access-Control-Max-Age 86400 always;
```

### Security Headers

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

### JWT Validation

El middleware Lua valida automáticamente los tokens JWT:

```lua
-- Endpoints públicos (sin autenticación)
local public_endpoints = {
    "/api/v1/auth/login",
    "/api/v1/auth/register",
    "/api/v1/auth/forgot-password",
    "/health",
    "/metrics"
}

-- Validación automática para otros endpoints
if not is_public_endpoint(ngx.var.uri) then
    validate_jwt_token()
end
```

## Monitoreo y Logging

### Logs Estructurados

Todos los logs se generan en formato JSON:

```json
{
  "timestamp": "2024-01-15T10:30:00Z",
  "level": "info",
  "method": "GET",
  "uri": "/api/v1/users",
  "status": 200,
  "response_time": 0.045,
  "user_id": "12345",
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0..."
}
```

### Métricas con Prometheus

Acceso a métricas en: `http://localhost:9090`

### Dashboards con Grafana

Acceso a dashboards en: `http://localhost:3001`
- Usuario: `admin`
- Contraseña: Configurada en `.env.gateway`

## Endpoints Disponibles

### API Gateway
- **HTTP**: `http://localhost`
- **HTTPS**: `https://localhost`
- **Health Check**: `GET /health`
- **Metrics**: `GET /metrics`

### Microservicios (a través del Gateway)
- **Authentication**: `/api/v1/auth/*`
- **Financial**: `/api/v1/financial/*`
- **Sports**: `/api/v1/sports/*`
- **Notifications**: `/api/v1/notifications/*`
- **Medical**: `/api/v1/medical/*`
- **Payroll**: `/api/v1/payroll/*`
- **Reports**: `/api/v1/reports/*`
- **Calendar**: `/api/v1/calendar/*`
- **Customization**: `/api/v1/customization/*`

### Frontend
- **PWA**: `http://localhost:3000`

## Troubleshooting

### Problemas Comunes

1. **Error 502 Bad Gateway**
   ```bash
   # Verificar que los microservicios estén ejecutándose
   docker-compose -f docker-compose.gateway.yml ps
   
   # Ver logs del API Gateway
   docker-compose -f docker-compose.gateway.yml logs api-gateway
   ```

2. **Error de autenticación JWT**
   ```bash
   # Verificar configuración JWT_SECRET
   grep JWT_SECRET .env.gateway
   
   # Ver logs de autenticación
   docker-compose -f docker-compose.gateway.yml logs auth-service
   ```

3. **Rate limiting excesivo**
   ```bash
   # Ajustar límites en nginx/nginx.conf
   # Reiniciar API Gateway
   docker-compose -f docker-compose.gateway.yml restart api-gateway
   ```

4. **Problemas de CORS**
   ```bash
   # Verificar CORS_ALLOWED_ORIGINS en .env.gateway
   # Verificar configuración en nginx/lua/jwt_auth.lua
   ```

### Comandos de Diagnóstico

```bash
# Ver estado de todos los servicios
docker-compose -f docker-compose.gateway.yml ps

# Ver logs en tiempo real
docker-compose -f docker-compose.gateway.yml logs -f

# Probar conectividad a microservicios
docker-compose -f docker-compose.gateway.yml exec api-gateway curl -f http://auth-service:8001/health

# Verificar configuración Nginx
docker-compose -f docker-compose.gateway.yml exec api-gateway nginx -t

# Ver métricas de sistema
docker stats
```

### Logs Importantes

```bash
# API Gateway
docker-compose -f docker-compose.gateway.yml logs api-gateway

# Base de datos
docker-compose -f docker-compose.gateway.yml logs mysql

# Cache Redis
docker-compose -f docker-compose.gateway.yml logs redis

# Servicio específico
docker-compose -f docker-compose.gateway.yml logs auth-service
```

## Seguridad

### Mejores Prácticas

1. **Cambiar secretos por defecto**:
   - `JWT_SECRET`
   - `DB_PASSWORD`
   - `GRAFANA_PASSWORD`

2. **Usar certificados SSL válidos** en producción

3. **Configurar firewall** para limitar acceso

4. **Actualizar regularmente** las imágenes Docker

5. **Monitorear logs** para detectar actividad sospechosa

### Configuración de Producción

```bash
# Variables críticas para producción
APP_ENV=production
APP_DEBUG=false
JWT_SECRET=<strong-random-secret>
DB_PASSWORD=<strong-password>
CORS_ALLOWED_ORIGINS=https://your-domain.com
```

## Escalabilidad

### Horizontal Scaling

```yaml
# docker-compose.gateway.yml
auth-service:
  deploy:
    replicas: 3
  scale: 3
```

### Load Balancing

```nginx
# nginx/nginx.conf
upstream auth_service {
    least_conn;
    server auth-service-1:8001 max_fails=3 fail_timeout=30s;
    server auth-service-2:8001 max_fails=3 fail_timeout=30s;
    server auth-service-3:8001 max_fails=3 fail_timeout=30s;
}
```

## Mantenimiento

### Backups

```bash
# Backup de base de datos
docker-compose -f docker-compose.gateway.yml exec mysql mysqldump -u root -p wl_school > backup.sql

# Backup de configuración
tar -czf config-backup.tar.gz nginx/ .env.gateway
```

### Actualizaciones

```bash
# Actualizar imágenes
docker-compose -f docker-compose.gateway.yml pull

# Reconstruir servicios
docker-compose -f docker-compose.gateway.yml build --no-cache

# Reiniciar con nuevas imágenes
docker-compose -f docker-compose.gateway.yml up -d
```

## Soporte

Para soporte técnico:

1. Revisar logs del sistema
2. Consultar esta documentación
3. Verificar configuración de variables de entorno
4. Contactar al equipo de desarrollo

---

**Nota**: Este API Gateway está diseñado para el sistema WL School y debe ser configurado según los requisitos específicos de cada entorno de despliegue.