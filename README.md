# WL School - Aplicación de Marca Blanca para Escuelas de Fútbol

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)
![Laravel](https://img.shields.io/badge/Laravel-10+-red.svg)
![React](https://img.shields.io/badge/React-18+-blue.svg)
![Docker](https://img.shields.io/badge/Docker-Ready-blue.svg)

## 🏆 Descripción

**WL School** es una aplicación web progresiva (PWA) de marca blanca diseñada específicamente para la gestión integral de escuelas de fútbol. Construida con una arquitectura de microservicios moderna, permite a cada escuela personalizar completamente la aplicación con sus colores, logo y branding.

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
</div>