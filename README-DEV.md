# WL School - Entorno de Desarrollo con Hot Reload

Este documento explica cómo configurar y usar el entorno de desarrollo con hot reload para el proyecto WL School.

## 🚀 Inicio Rápido

### Prerrequisitos
- Docker y Docker Compose instalados
- Puerto 3000, 8000, 8001, 8080, 8081, 3307, 6379 disponibles

### Iniciar el Entorno de Desarrollo

```bash
# Opción 1: Usar el script automatizado (recomendado)
./scripts/dev-hot-reload.sh

# Opción 2: Comando manual
docker-compose -f docker-compose.dev.yml up --build
```

## 🔥 Características del Hot Reload

### Frontend (React PWA)
- **Ubicación**: `./frontend-pwa/`
- **Puerto**: http://localhost:3000
- **Hot Reload**: ✅ Automático
- **Características**:
  - Cambios en componentes React se reflejan inmediatamente
  - Recarga automática del navegador
  - Preservación del estado de la aplicación cuando es posible
  - Soporte para Fast Refresh de React

### Backend (Laravel Auth Service)
- **Ubicación**: `./microservices/auth-service/`
- **Puerto**: http://localhost:8001
- **Hot Reload**: ✅ Automático
- **Características**:
  - Cambios en controladores, modelos y rutas se aplican inmediatamente
  - No requiere reiniciar el contenedor
  - Logs en tiempo real

## 🌐 Servicios Disponibles

| Servicio | URL | Descripción |
|----------|-----|-------------|
| Frontend PWA | http://localhost:3000 | Aplicación React con hot reload |
| Auth Service API | http://localhost:8001 | API de autenticación Laravel |
| Nginx Gateway | http://localhost:8000 | Gateway principal |
| phpMyAdmin | http://localhost:8080 | Administrador de base de datos |
| Redis Commander | http://localhost:8081 | Administrador de Redis |

## 🛠️ Comandos de Desarrollo

### Gestión de Contenedores
```bash
# Ver estado de los servicios
docker-compose -f docker-compose.dev.yml ps

# Ver logs en tiempo real
docker-compose -f docker-compose.dev.yml logs -f

# Ver logs de un servicio específico
docker-compose -f docker-compose.dev.yml logs -f frontend-pwa
docker-compose -f docker-compose.dev.yml logs -f auth-service

# Reiniciar un servicio específico
docker-compose -f docker-compose.dev.yml restart frontend-pwa
docker-compose -f docker-compose.dev.yml restart auth-service

# Detener todos los servicios
docker-compose -f docker-compose.dev.yml down

# Detener y eliminar volúmenes
docker-compose -f docker-compose.dev.yml down -v
```

### Acceso a Contenedores
```bash
# Acceder al contenedor del frontend
docker-compose -f docker-compose.dev.yml exec frontend-pwa sh

# Acceder al contenedor del backend
docker-compose -f docker-compose.dev.yml exec auth-service bash

# Ejecutar comandos de Laravel
docker-compose -f docker-compose.dev.yml exec auth-service php artisan migrate
docker-compose -f docker-compose.dev.yml exec auth-service php artisan tinker

# Ejecutar comandos de npm en el frontend
docker-compose -f docker-compose.dev.yml exec frontend-pwa npm install
docker-compose -f docker-compose.dev.yml exec frontend-pwa npm test
```

## 📁 Estructura de Volúmenes

### Frontend
```
./frontend-pwa/ → /app (en el contenedor)
```
- Todos los cambios en `src/`, `public/`, `package.json`, etc. se reflejan inmediatamente
- `node_modules` está excluido para evitar conflictos

### Backend
```
./microservices/auth-service/ → /var/www/html (en el contenedor)
```
- Cambios en `app/`, `routes/`, `config/`, etc. se aplican automáticamente
- `vendor/` está excluido para evitar conflictos
- Los logs se guardan en `./storage/logs/auth/`

## 🔧 Configuración de Desarrollo

### Variables de Entorno

#### Frontend
- `NODE_ENV=development`
- `CHOKIDAR_USEPOLLING=true` - Habilita polling para hot reload
- `WATCHPACK_POLLING=true` - Mejora la detección de cambios
- `FAST_REFRESH=true` - Habilita Fast Refresh de React

#### Backend
- `APP_ENV=local`
- `APP_DEBUG=true`
- Configuración de base de datos y Redis

### Optimizaciones
- **Dockerfiles específicos**: `Dockerfile.dev` optimizados para desarrollo
- **Volúmenes excluidos**: `node_modules` y `vendor` para evitar conflictos
- **Polling habilitado**: Para sistemas de archivos que no soportan inotify

## 🐛 Solución de Problemas

### El Hot Reload No Funciona
1. Verificar que los volúmenes estén montados correctamente:
   ```bash
   docker-compose -f docker-compose.dev.yml exec frontend-pwa ls -la /app
   ```

2. Verificar variables de entorno:
   ```bash
   docker-compose -f docker-compose.dev.yml exec frontend-pwa env | grep CHOKIDAR
   ```

3. Reiniciar el servicio:
   ```bash
   docker-compose -f docker-compose.dev.yml restart frontend-pwa
   ```

### Puertos en Uso
Si algún puerto está ocupado, puedes modificar los puertos en `docker-compose.dev.yml`:
```yaml
ports:
  - "3001:3000"  # Cambiar puerto local
```

### Problemas de Permisos
```bash
# Ajustar permisos en macOS/Linux
sudo chown -R $USER:$USER ./frontend-pwa/node_modules
sudo chown -R $USER:$USER ./microservices/auth-service/vendor
```

## 📊 Monitoreo

### Logs en Tiempo Real
```bash
# Todos los servicios
docker-compose -f docker-compose.dev.yml logs -f

# Solo errores
docker-compose -f docker-compose.dev.yml logs -f | grep ERROR

# Servicio específico con timestamp
docker-compose -f docker-compose.dev.yml logs -f -t frontend-pwa
```

### Métricas de Rendimiento
```bash
# Uso de recursos
docker stats

# Información de contenedores
docker-compose -f docker-compose.dev.yml top
```

## 🎯 Flujo de Trabajo Recomendado

1. **Iniciar el entorno**: `./scripts/dev-hot-reload.sh`
2. **Abrir el navegador**: http://localhost:3000
3. **Hacer cambios en el código**
4. **Ver cambios automáticamente** sin recargar manualmente
5. **Usar las herramientas de desarrollo**:
   - React DevTools en el navegador
   - Laravel Telescope (si está configurado)
   - phpMyAdmin para la base de datos
   - Redis Commander para el cache

## 🔄 Diferencias con Producción

| Aspecto | Desarrollo | Producción |
|---------|------------|------------|
| Hot Reload | ✅ Habilitado | ❌ Deshabilitado |
| Optimización | ❌ Mínima | ✅ Completa |
| Debug | ✅ Habilitado | ❌ Deshabilitado |
| Volúmenes | ✅ Código fuente | ❌ Solo assets |
| Build | ❌ En tiempo real | ✅ Pre-compilado |

---

¡Ahora puedes desarrollar con hot reload y ver tus cambios inmediatamente! 🚀