# Guía de Migraciones Automáticas con Docker

## 📋 Resumen

Los contenedores Docker de los microservicios ahora ejecutan automáticamente las migraciones de base de datos cada vez que se construyen o inician. Esto garantiza que la base de datos esté siempre actualizada con el esquema más reciente.

## 🔧 Cambios Implementados

### 1. **Auth Service**
- ✅ Entrypoint script mejorado con validaciones
- ✅ Ejecución automática de migraciones
- ✅ Seeding automático en desarrollo
- ✅ Manejo de errores robusto

### 2. **Financial Service**
- ✅ Nuevo entrypoint script creado
- ✅ Dockerfile y Dockerfile.dev actualizados
- ✅ Ejecución automática de migraciones
- ✅ Seeding automático en desarrollo
- ✅ Validaciones de estado de migraciones

## 🚀 Cómo Funciona

### Proceso de Inicio del Contenedor:

1. **Espera por la Base de Datos** 🔄
   - El contenedor espera hasta que la conexión a la base de datos esté disponible
   - Reintentos automáticos cada 2 segundos

2. **Optimización de la Aplicación** ⚙️
   - Limpia cachés de configuración, rutas y vistas
   - Optimiza para producción si `APP_ENV=production`

3. **Ejecución de Migraciones** 🗄️
   - Verifica si hay migraciones pendientes
   - Ejecuta `php artisan migrate --force`
   - Maneja errores y detiene el contenedor si fallan

4. **Seeding (Solo Desarrollo)** 🌱
   - Ejecuta seeders si `APP_ENV=local` o `APP_ENV=development`
   - Continúa aunque los seeders fallen

5. **Configuración Final** 🔗
   - Crea enlaces de almacenamiento
   - Limpia cachés finales
   - Inicia el servicio principal

## 📝 Comandos de Docker

### Construcción y Inicio
```bash
# Construir y iniciar todos los servicios
docker-compose up --build

# Solo el servicio financiero
docker-compose up --build financial-service

# Solo el servicio de autenticación
docker-compose up --build auth-service
```

### Reconstruir Contenedores
```bash
# Forzar reconstrucción completa
docker-compose build --no-cache

# Reconstruir servicio específico
docker-compose build --no-cache financial-service
```

### Ver Logs de Migraciones
```bash
# Ver logs del servicio financiero
docker-compose logs financial-service

# Ver logs en tiempo real
docker-compose logs -f financial-service

# Ver solo logs de migraciones
docker-compose logs financial-service | grep "🗄️\|✅\|❌"
```

## 🎯 Beneficios

### ✅ **Automatización Completa**
- No más comandos manuales de migración
- Base de datos siempre actualizada
- Proceso consistente en todos los entornos

### ✅ **Validación Robusta**
- Verificación de estado de migraciones
- Manejo de errores con códigos de salida
- Logs detallados con colores

### ✅ **Flexibilidad por Entorno**
- Seeding automático solo en desarrollo
- Optimizaciones específicas para producción
- Configuración mediante variables de entorno

### ✅ **Recuperación de Errores**
- El contenedor se detiene si las migraciones fallan
- Logs claros para debugging
- Reintentos automáticos para conexiones de BD

## 🔍 Solución de Problemas

### Error: "Database migrations failed"
```bash
# Ver logs detallados
docker-compose logs financial-service

# Ejecutar migraciones manualmente
docker-compose exec financial-service php artisan migrate:status
docker-compose exec financial-service php artisan migrate --force
```

### Error: "Database is unavailable"
```bash
# Verificar estado de la base de datos
docker-compose ps mysql-financial

# Reiniciar servicios de base de datos
docker-compose restart mysql-financial mysql-auth
```

### Limpiar y Reiniciar
```bash
# Detener todos los servicios
docker-compose down

# Limpiar volúmenes (⚠️ ELIMINA DATOS)
docker-compose down -v

# Reconstruir desde cero
docker-compose up --build --force-recreate
```

## 📊 Variables de Entorno Importantes

| Variable | Valores | Descripción |
|----------|---------|-------------|
| `APP_ENV` | `local`, `development`, `production`, `testing` | Controla el comportamiento de migraciones y seeding |
| `APP_DEBUG` | `true`, `false` | Habilita/deshabilita modo debug |
| `DB_HOST` | `mysql-auth`, `mysql-financial` | Host de la base de datos |
| `DB_DATABASE` | `wl_school_auth`, `wl_school_financial` | Nombre de la base de datos |

## 🎉 Resultado

Ahora cada vez que construyas o inicies los contenedores:

1. 🔄 **Se conectarán automáticamente** a la base de datos
2. 🗄️ **Ejecutarán todas las migraciones** pendientes
3. 🌱 **Poblarán la base de datos** (en desarrollo)
4. ✅ **Estarán listos para usar** con la BD actualizada

¡No más pasos manuales para mantener la base de datos sincronizada! 🚀