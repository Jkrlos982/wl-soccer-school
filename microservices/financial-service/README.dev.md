# Financial Service - Desarrollo con Hot Reload

Este documento explica cómo configurar y usar el entorno de desarrollo con hot reload para el servicio financiero.

## 🚀 Configuración de Desarrollo

### Archivos de Desarrollo

- `Dockerfile.dev` - Dockerfile optimizado para desarrollo
- `docker-compose.dev.yml` - Configuración de Docker Compose para desarrollo
- `docker/php/dev.ini` - Configuración PHP para desarrollo
- `dev.sh` - Script de utilidades para desarrollo

### Características del Entorno de Desarrollo

✅ **Hot Reload**: Los cambios en el código se reflejan inmediatamente
✅ **Debugging habilitado**: Errores y logs visibles
✅ **OPcache deshabilitado**: Para evitar cache de código
✅ **Dependencias de desarrollo**: Incluye herramientas de testing y debugging
✅ **Volúmenes optimizados**: Excluye vendor y node_modules para mejor rendimiento

## 📋 Comandos Disponibles

### Usando el Script de Desarrollo

```bash
# Iniciar entorno de desarrollo
./dev.sh start

# Ver logs en tiempo real
./dev.sh logs

# Acceder al shell del contenedor
./dev.sh shell

# Ejecutar migraciones
./dev.sh migrate

# Ejecutar seeders
./dev.sh seed

# Ejecutar tests
./dev.sh test

# Reiniciar servicios
./dev.sh restart

# Parar entorno
./dev.sh stop

# Ver estado de contenedores
./dev.sh status

# Reconstruir contenedores
./dev.sh build

# Limpiar todo (contenedores y volúmenes)
./dev.sh clean

# Ver ayuda
./dev.sh help
```

### Comandos Docker Compose Directos

```bash
# Iniciar en modo desarrollo
docker-compose -f docker-compose.dev.yml up -d

# Ver logs
docker-compose -f docker-compose.dev.yml logs -f

# Parar servicios
docker-compose -f docker-compose.dev.yml down

# Reconstruir
docker-compose -f docker-compose.dev.yml build --no-cache
```

## 🌐 Servicios Disponibles

| Servicio | URL/Puerto | Descripción |
|----------|------------|-------------|
| API | http://localhost:8003 | API del servicio financiero |
| Base de datos | localhost:3307 | MySQL (usuario: financial_user, password: financial_password) |
| PHP-FPM | Puerto 9000 | Servidor PHP |

## 🔧 Configuración de Desarrollo

### Variables de Entorno

El entorno de desarrollo usa estas configuraciones:

```env
APP_ENV=local
APP_DEBUG=true
DB_HOST=financial-db
DB_PORT=3306
DB_DATABASE=financial_service
DB_USERNAME=financial_user
DB_PASSWORD=financial_password
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync
```

### Configuración PHP

El archivo `docker/php/dev.ini` incluye:

- `display_errors=On` - Mostrar errores en pantalla
- `opcache.enable=0` - OPcache deshabilitado para hot reload
- `memory_limit=512M` - Memoria aumentada para desarrollo
- Configuración de Xdebug (comentada, descomenta si necesitas debugging)

## 🔄 Hot Reload

### Cómo Funciona

1. **Volumen de código**: `./:/var/www` mapea todo el código fuente
2. **Exclusión de dependencias**: Los directorios `vendor` y `node_modules` se excluyen para mejor rendimiento
3. **OPcache deshabilitado**: Evita que PHP cache el código compilado
4. **Configuración de desarrollo**: PHP configurado para mostrar errores inmediatamente

### Archivos que se Actualizan Automáticamente

✅ Archivos PHP (controladores, modelos, etc.)
✅ Archivos de configuración
✅ Rutas (routes/)
✅ Vistas
✅ Archivos de recursos

### Archivos que Requieren Reinicio

❌ `composer.json` (requiere `composer install`)
❌ Variables de entorno (`.env`)
❌ Configuración de servicios

## 🐛 Debugging

### Logs de PHP

```bash
# Ver logs en tiempo real
./dev.sh logs

# Ver solo logs del servicio PHP
docker-compose -f docker-compose.dev.yml logs -f financial-service
```

### Acceso al Contenedor

```bash
# Acceder al shell
./dev.sh shell

# Ejecutar comandos Artisan
php artisan route:list
php artisan config:cache
php artisan cache:clear
```

### Xdebug (Opcional)

Para habilitar Xdebug, descomenta las líneas en `docker/php/dev.ini`:

```ini
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=host.docker.internal
xdebug.client_port=9003
```

## 🚨 Solución de Problemas

### El código no se actualiza

1. Verifica que OPcache esté deshabilitado
2. Reinicia el contenedor: `./dev.sh restart`
3. Verifica los volúmenes: `docker-compose -f docker-compose.dev.yml config`

### Errores de permisos

```bash
# Arreglar permisos
docker-compose -f docker-compose.dev.yml exec financial-service chown -R www-data:www-data /var/www
```

### Base de datos no conecta

1. Verifica que el contenedor de DB esté corriendo: `./dev.sh status`
2. Verifica las credenciales en el archivo de configuración
3. Reinicia los servicios: `./dev.sh restart`

### Limpiar todo y empezar de nuevo

```bash
# Limpia contenedores, volúmenes e imágenes
./dev.sh clean

# Reconstruye todo
./dev.sh build

# Inicia de nuevo
./dev.sh start
```

## 📝 Notas Importantes

- **Rendimiento**: El hot reload puede ser más lento que producción debido a la falta de OPcache
- **Memoria**: El entorno de desarrollo usa más memoria (512M vs 256M)
- **Seguridad**: No uses esta configuración en producción
- **Dependencias**: Las dependencias de desarrollo están incluidas

## 🔄 Flujo de Trabajo Recomendado

1. **Iniciar desarrollo**:
   ```bash
   ./dev.sh start
   ./dev.sh migrate
   ./dev.sh seed
   ```

2. **Durante el desarrollo**:
   - Edita archivos normalmente
   - Los cambios se reflejan automáticamente
   - Usa `./dev.sh logs` para ver errores

3. **Testing**:
   ```bash
   ./dev.sh test
   ```

4. **Finalizar**:
   ```bash
   ./dev.sh stop
   ```

¡Ahora puedes desarrollar con hot reload! 🎉