# WL-School 🎓

**Sistema Integral de Gestión Escolar con Arquitectura de Microservicios**

WL-School es una plataforma completa para la gestión de instituciones educativas, desarrollada con una arquitectura moderna de microservicios que garantiza escalabilidad, mantenibilidad y alta disponibilidad.

## 🏗️ Arquitectura

### Microservicios

| Servicio | Puerto | Descripción | Tecnología |
|----------|--------|-------------|------------|
| **API Gateway** | 8000 | Punto de entrada unificado | Laravel 10 |
| **Auth Service** | 8001 | Autenticación y autorización | Laravel 10 |
| **Financial Service** | 8002 | Gestión financiera y pagos | Laravel 10 |
| **Sports Service** | 8003 | Gestión deportiva | Laravel 10 |
| **Notification Service** | 8004 | Sistema de notificaciones | Laravel 10 |
| **Medical Service** | 8005 | Gestión médica | Laravel 10 |
| **Payroll Service** | 8006 | Gestión de nómina | Laravel 10 |
| **Report Service** | 8007 | Generación de reportes | Laravel 10 |
| **Calendar Service** | 8008 | Gestión de calendario | Laravel 10 |
| **Customization Service** | 8009 | Personalización del sistema | Laravel 10 |
| **Frontend PWA** | 3000 | Aplicación web progresiva | React 18 |

### Infraestructura

| Componente | Puerto | Descripción |
|------------|--------|-------------|
| **Nginx** | 80/443 | Load balancer y reverse proxy |
| **Redis** | 6379 | Cache y sesiones |
| **MySQL** | 3306+ | Bases de datos (una por servicio) |
| **phpMyAdmin** | 8080 | Administración de bases de datos |
| **Redis Commander** | 8081 | Administración de Redis |

## 🚀 Inicio Rápido

### Prerrequisitos

- Docker 20.10+
- Docker Compose 2.0+
- Git
- Make (opcional, para comandos simplificados)

### Instalación Automática

```bash
# Clonar el repositorio principal
git clone https://github.com/Jkrlos982/wl-school.git
cd wl-school

# Configuración completa automática
make setup
# o
./scripts/setup.sh
```

### Instalación Manual

```bash
# 1. Copiar archivo de configuración
cp .env.example .env

# 2. Editar configuración (opcional)
nano .env

# 3. Crear directorios necesarios
mkdir -p storage/logs/{gateway,auth,financial,sports,notification,medical,payroll,report,calendar,customization,nginx}
mkdir -p database/init nginx/ssl

# 4. Generar certificados SSL para desarrollo
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout nginx/ssl/wl-school.key \
  -out nginx/ssl/wl-school.crt \
  -subj "/C=CO/ST=Bogota/L=Bogota/O=WL-School/OU=Development/CN=wl-school.local"

# 5. Iniciar servicios
docker-compose up -d
```

## 🛠️ Comandos de Desarrollo

### Usando Make (Recomendado)

```bash
# Gestión de servicios
make start          # Iniciar todos los servicios
make stop           # Detener todos los servicios
make restart        # Reiniciar todos los servicios
make status         # Ver estado de los servicios
make health         # Verificar salud de los servicios

# Desarrollo
make logs                    # Ver logs de todos los servicios
make logs-auth-service       # Ver logs de un servicio específico
make shell-api-gateway       # Abrir shell en un contenedor
make test                    # Ejecutar tests en todos los servicios
make test-financial-service  # Ejecutar tests en un servicio específico

# Base de datos
make migrate        # Ejecutar migraciones
make seed          # Poblar bases de datos
make backup        # Crear respaldo de bases de datos

# Utilidades
make build         # Construir imágenes
make rebuild       # Reconstruir imágenes (sin cache)
make clean         # Limpiar recursos de Docker
make update        # Actualizar repositorios
```

### Usando Scripts Directamente

```bash
# Gestión de servicios
./scripts/dev.sh start
./scripts/dev.sh stop
./scripts/dev.sh logs auth-service
./scripts/dev.sh shell api-gateway

# Reset completo del entorno
./scripts/reset.sh
```

### Usando Docker Compose

```bash
# Comandos básicos
docker-compose up -d              # Iniciar servicios
docker-compose down               # Detener servicios
docker-compose ps                 # Ver estado
docker-compose logs -f [service]  # Ver logs

# Ejecutar comandos en contenedores
docker-compose exec api-gateway php artisan migrate
docker-compose exec frontend-pwa npm run build
```

## 🌐 URLs de Acceso

### Aplicaciones
- **Frontend PWA**: http://localhost:3000
- **API Gateway**: http://localhost:8000
- **Documentación API**: http://localhost:8000/api/documentation

### Herramientas de Administración
- **phpMyAdmin**: http://localhost:8080
  - Usuario: `root`
  - Contraseña: `rootpassword`
- **Redis Commander**: http://localhost:8081

### APIs de Microservicios
- **Auth Service**: http://localhost:8001
- **Financial Service**: http://localhost:8002
- **Sports Service**: http://localhost:8003
- **Notification Service**: http://localhost:8004
- **Medical Service**: http://localhost:8005
- **Payroll Service**: http://localhost:8006
- **Report Service**: http://localhost:8007
- **Calendar Service**: http://localhost:8008
- **Customization Service**: http://localhost:8009

## 📁 Estructura del Proyecto

```
wl-school/
├── api-gateway/              # Servicio API Gateway
├── auth-service/             # Servicio de Autenticación
├── financial-service/        # Servicio Financiero
├── sports-service/           # Servicio Deportivo
├── notification-service/     # Servicio de Notificaciones
├── medical-service/          # Servicio Médico
├── payroll-service/          # Servicio de Nómina
├── report-service/           # Servicio de Reportes
├── calendar-service/         # Servicio de Calendario
├── customization-service/    # Servicio de Personalización
├── frontend-pwa/             # Frontend PWA
├── nginx/                    # Configuración de Nginx
│   ├── nginx.conf
│   ├── wl-school.conf
│   └── ssl/
├── scripts/                  # Scripts de automatización
│   ├── setup.sh
│   ├── dev.sh
│   └── reset.sh
├── storage/                  # Almacenamiento y logs
│   └── logs/
├── database/                 # Inicialización de BD
│   └── init/
├── docker-compose.yml        # Configuración de Docker
├── .env.example             # Variables de entorno
├── Makefile                 # Comandos simplificados
└── README.md               # Este archivo
```

## 🔧 Configuración

### Variables de Entorno

El archivo `.env` contiene todas las configuraciones necesarias:

```bash
# Aplicación
APP_NAME="WL-School"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

# Base de datos
DB_CONNECTION=mysql
DB_HOST=mysql-gateway
DB_PORT=3306
DB_DATABASE=wl_school_gateway
DB_USERNAME=root
DB_PASSWORD=rootpassword

# Redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# JWT
JWT_SECRET=your-jwt-secret-key
JWT_TTL=60

# Más configuraciones...
```

### Personalización por Servicio

Cada microservicio puede tener su propia configuración específica en su directorio correspondiente.

## 🧪 Testing

```bash
# Ejecutar todos los tests
make test

# Ejecutar tests de un servicio específico
make test-auth-service

# Ejecutar tests con cobertura
docker-compose exec auth-service php artisan test --coverage
```

## 📊 Monitoreo y Logs

### Ver Logs

```bash
# Logs de todos los servicios
make logs

# Logs de un servicio específico
make logs-api-gateway

# Logs en tiempo real
docker-compose logs -f api-gateway
```

### Verificar Salud

```bash
# Verificar salud de todos los servicios
make health

# Verificar estado de contenedores
make status
```

## 🔒 Seguridad

### Autenticación
- JWT para autenticación de APIs
- OAuth2 para integraciones externas
- Roles y permisos granulares

### Comunicación
- HTTPS en producción
- Comunicación interna segura entre microservicios
- Rate limiting en API Gateway

### Base de Datos
- Conexiones encriptadas
- Respaldos automáticos
- Separación de datos por servicio

## 🚀 Despliegue

### Desarrollo
```bash
make dev
```

### Producción
```bash
# Construir para producción
make prod-build

# Desplegar
docker-compose -f docker-compose.prod.yml up -d
```

## 🤝 Contribución

1. Fork el proyecto
2. Crear una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abrir un Pull Request

### Estándares de Código

- **PHP**: PSR-12
- **JavaScript**: ESLint + Prettier
- **Commits**: Conventional Commits
- **Tests**: Cobertura mínima del 80%

## 📝 Documentación

- [Documentación de API](http://localhost:8000/api/documentation)
- [Guía de Desarrollo](./docs/development.md)
- [Arquitectura](./docs/architecture.md)
- [Despliegue](./docs/deployment.md)

## 🐛 Solución de Problemas

### Problemas Comunes

**Error de conexión a la base de datos**
```bash
# Verificar que los contenedores estén ejecutándose
make status

# Reiniciar servicios de base de datos
docker-compose restart mysql-gateway mysql-auth
```

**Puerto ya en uso**
```bash
# Verificar qué proceso está usando el puerto
lsof -i :8000

# Detener todos los servicios
make stop
```

**Problemas de permisos**
```bash
# Arreglar permisos de storage
sudo chown -R $USER:$USER storage/
chmod -R 755 storage/
```

### Reset Completo

```bash
# Reset completo del entorno
make reset

# Configuración desde cero
make setup
```

## 📞 Soporte

- **Issues**: [GitHub Issues](https://github.com/Jkrlos982/wl-school/issues)
- **Documentación**: [Wiki del Proyecto](https://github.com/Jkrlos982/wl-school/wiki)
- **Email**: support@wl-school.com

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## 🙏 Agradecimientos

- Laravel Framework
- React.js
- Docker
- Nginx
- MySQL
- Redis

---

**Desarrollado con ❤️ para la comunidad educativa**

*WL-School - Transformando la educación a través de la tecnología*

### ✨ Características Principales

- 🏗️ **Arquitectura de Microservicios** - Escalable y mantenible
- 📱 **PWA (Progressive Web App)** - Funciona como app nativa
- 🎨 **Marca Blanca Completa** - Personalización total del branding
- 💰 **Gestión Financiera** - Control de ingresos, gastos y nómina
- ⚽ **Módulo Deportivo** - Jugadores, entrenamientos, partidos y estadísticas
- 📲 **Notificaciones Inteligentes** - WhatsApp, Email y SMS
- 🏥 **Seguimiento Médico** - Control de lesiones y fisioterapia
- 📊 **Reportes Avanzados** - Analytics financieros y deportivos
- 📅 **Calendario Integrado** - Gestión de eventos y programación

## 🏗️ Arquitectura

### Microservicios Backend (Laravel)

```
wl-school-platform/
├── 🔐 auth-service/          # Autenticación y autorización
├── 💰 financial-service/     # Gestión financiera
├── 👥 payroll-service/       # Nómina de empleados
├── ⚽ sports-service/         # Módulo deportivo
├── 📲 notification-service/  # Sistema de notificaciones
├── 🏥 medical-service/       # Seguimiento médico
├── 📊 report-service/        # Generación de reportes
├── 🎨 customization-service/ # Personalización marca blanca
├── 📅 calendar-service/      # Gestión de calendario
└── 🌐 api-gateway/          # Gateway principal
```

### Frontend (React PWA)

```
wl-school-frontend/
├── 📱 Progressive Web App
├── ⚡ React 18+ con Hooks
├── 🎨 Material-UI / Tailwind CSS
├── 🔄 State Management (Redux/Zustand)
└── 📡 Service Workers
```

## 🚀 Inicio Rápido

### Prerrequisitos

- Docker & Docker Compose
- Git
- Node.js 18+ (para desarrollo frontend)
- PHP 8.2+ (para desarrollo backend)

### Instalación

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/tu-usuario/wl-school.git
   cd wl-school
   ```

2. **Configurar variables de entorno**
   ```bash
   cp .env.example .env
   # Editar .env con tus configuraciones
   ```

3. **Levantar servicios con Docker**
   ```bash
   docker-compose up -d
   ```

4. **Ejecutar migraciones**
   ```bash
   docker-compose exec api-gateway php artisan migrate
   ```

5. **Acceder a la aplicación**
   - Frontend: http://localhost:3000
   - API Gateway: http://localhost:8000
   - Documentación API: http://localhost:8000/docs

## 📚 Documentación

### 📖 Documentos Principales

- **[Plan General](./Docs/README.md)** - Visión completa del proyecto
- **[Arquitectura de Microservicios](./Docs/ARQUITECTURA_MICROSERVICIOS.md)** - Detalles técnicos de la arquitectura
- **[Plan de Desarrollo](./Docs/PLAN_DESARROLLO.md)** - Roadmap y fases de implementación
- **[Especificaciones Técnicas](./Docs/ESPECIFICACIONES_TECNICAS.md)** - APIs y modelos de datos

### 🔗 Enlaces Útiles

- [Documentación de APIs](http://localhost:8000/docs)
- [Guía de Contribución](./CONTRIBUTING.md)
- [Changelog](./CHANGELOG.md)
- [Licencia](./LICENSE)

## 🎯 Módulos Funcionales

### 💰 Módulo Financiero
- Gestión de ingresos y gastos
- Conceptos personalizables
- Cuentas por cobrar/pagar
- Generación de comprobantes
- Conciliación bancaria

### ⚽ Módulo Deportivo
- **Jugadores**: Información completa y seguimiento
- **Categorías**: Organización por edades
- **Entrenamientos**: Planificación y asistencia
- **Partidos**: Programación y estadísticas
- **Torneos**: Participación y resultados
- **Scouting**: Evaluación de prospectos

### 👥 Módulo de Nómina
- Gestión de empleados
- Cálculo automático de nómina
- Deducciones y bonificaciones
- Generación de desprendibles
- Control de vacaciones

### 📲 Sistema de Notificaciones
- **WhatsApp**: Comprobantes y convocatorias
- **Email**: Comunicados oficiales
- **SMS**: Recordatorios urgentes
- Templates personalizables
- Programación de envíos

### 🏥 Seguimiento Médico
- Registro de lesiones
- Control de fisioterapia
- Historial médico completo
- Seguimiento de recuperación
- Reportes médicos

### 📊 Sistema de Reportes
- **Financieros**: Estados, flujos, rentabilidad
- **Deportivos**: Rendimiento, estadísticas
- **Operativos**: Asistencias, programación
- Exportación múltiple (PDF, Excel, CSV)
- Dashboards interactivos

### 🎨 Marca Blanca
- Personalización de colores
- Gestión de logos
- Configuración de temas
- Branding completo
- Vista previa en tiempo real

## 🛠️ Tecnologías

### Backend
- **Laravel 10+** - Framework PHP
- **MySQL 8.0** - Base de datos principal
- **Redis** - Cache y colas
- **JWT** - Autenticación
- **Docker** - Contenedorización

### Frontend
- **React 18+** - Biblioteca de UI
- **TypeScript** - Tipado estático
- **PWA** - Aplicación web progresiva
- **Material-UI** - Componentes de UI
- **Axios** - Cliente HTTP

### DevOps
- **Docker Compose** - Orquestación local
- **Kubernetes** - Orquestación producción
- **GitHub Actions** - CI/CD
- **Nginx** - Proxy reverso
- **Prometheus** - Monitoreo

### Integraciones
- **WhatsApp Business API** - Mensajería
- **SendGrid/Mailgun** - Email
- **Google Calendar** - Sincronización
- **Stripe/PayU** - Pagos online

## 🚀 Despliegue

### Desarrollo Local
```bash
docker-compose up -d
```

### Staging
```bash
kubectl apply -f k8s/staging/
```

### Producción
```bash
kubectl apply -f k8s/production/
```

## 🧪 Testing

### Backend (Laravel)
```bash
# Tests unitarios
docker-compose exec auth-service php artisan test

# Tests de integración
docker-compose exec api-gateway php artisan test --testsuite=Integration
```

### Frontend (React)
```bash
# Tests unitarios
npm test

# Tests E2E
npm run test:e2e
```

## 📈 Monitoreo

- **Prometheus**: Métricas de aplicación
- **Grafana**: Dashboards y visualización
- **ELK Stack**: Logs centralizados
- **Jaeger**: Tracing distribuido
- **Sentry**: Error tracking

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

Ver [CONTRIBUTING.md](./CONTRIBUTING.md) para más detalles.

## 📋 Roadmap

### ✅ Fase 1 - Fundación (Completada)
- [x] Configuración de infraestructura
- [x] Sistema de autenticación
- [x] API Gateway

### 🚧 Fase 2 - Módulos Core (En Progreso)
- [ ] Módulo financiero
- [ ] Módulo deportivo básico
- [ ] Frontend base

### 📅 Fase 3 - Notificaciones (Planificada)
- [ ] Sistema de notificaciones
- [ ] Integración WhatsApp
- [ ] Sistema de calendario

### 🔮 Futuras Fases
- [ ] Módulo médico
- [ ] Sistema de reportes
- [ ] Marca blanca
- [ ] Optimización y producción

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE](./LICENSE) para más detalles.

## 👥 Equipo

- **Tech Lead**: [Tu Nombre](mailto:tu@email.com)
- **Backend Developer**: [Nombre](mailto:email@example.com)
- **Frontend Developer**: [Nombre](mailto:email@example.com)
- **DevOps Engineer**: [Nombre](mailto:email@example.com)

## 📞 Soporte

- 📧 Email: soporte@wlschool.com
- 💬 Discord: [WL School Community](https://discord.gg/wlschool)
- 📚 Documentación: [docs.wlschool.com](https://docs.wlschool.com)
- 🐛 Issues: [GitHub Issues](https://github.com/tu-usuario/wl-school/issues)

---

<div align="center">
  <p>Hecho con ❤️ para las escuelas de fútbol</p>
  <p>© 2024 WL School. Todos los derechos reservados.</p>
</div># wl-soccer-school
