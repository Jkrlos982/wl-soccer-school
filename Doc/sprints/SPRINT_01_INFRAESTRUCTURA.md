# Sprint 1: Infraestructura Base

**Duración:** 2 semanas  
**Fase:** 1 - Fundación y Autenticación  
**Objetivo:** Establecer la infraestructura base del proyecto y configurar el entorno de desarrollo

## Resumen del Sprint

Este sprint se enfoca en establecer los cimientos técnicos del proyecto, configurando todos los repositorios, herramientas de desarrollo, bases de datos y pipelines de CI/CD necesarios para el desarrollo eficiente de la aplicación.

## Objetivos Específicos

- ✅ Configurar arquitectura de repositorios para microservicios
- ✅ Establecer entorno de desarrollo con Docker
- ✅ Configurar bases de datos para cada microservicio
- ✅ Implementar pipeline básico de CI/CD
- ✅ Crear documentación de setup y onboarding

## Tareas Detalladas

### 1. Configuración de Repositorios Git

**Responsable:** Tech Lead + DevOps Engineer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear repositorio principal (monorepo de documentación)**
   - Inicializar repositorio `wl-school-docs`
   - Configurar estructura de carpetas para documentación
   - Establecer README principal del proyecto
   - Configurar .gitignore apropiado

2. **Crear repositorios individuales para microservicios:**
   - `wl-school-api-gateway`
   - `wl-school-auth-service`
   - `wl-school-financial-service`
   - `wl-school-sports-service`
   - `wl-school-notification-service`
   - `wl-school-medical-service`
   - `wl-school-payroll-service`
   - `wl-school-report-service`
   - `wl-school-customization-service`
   - `wl-school-calendar-service`

3. **Crear repositorio para frontend:**
   - `wl-school-frontend-pwa`

4. **Configurar templates de repositorio:**
   - Template para microservicios Laravel
   - Template para documentación
   - Configurar branch protection rules
   - Establecer políticas de merge

#### Criterios de Aceptación:
- [ ] Todos los repositorios creados y configurados
- [ ] Branch protection habilitado en main/master
- [ ] Templates aplicados correctamente
- [ ] Documentación de estructura de repositorios

---

### 2. Setup Docker para Desarrollo

**Responsable:** DevOps Engineer + Backend Developers  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Crear Dockerfile base para Laravel:**
   ```dockerfile
   # Configurar imagen base PHP 8.2
   # Instalar extensiones necesarias
   # Configurar Composer
   # Establecer estructura de directorios
   ```

2. **Crear docker-compose.yml para desarrollo:**
   - Servicios de base de datos (MySQL para cada microservicio)
   - Redis para cache y sesiones
   - Nginx como reverse proxy
   - Configurar volúmenes para desarrollo
   - Variables de entorno para cada servicio

3. **Configurar networking entre contenedores:**
   - Red interna para microservicios
   - Exposición de puertos necesarios
   - Configurar service discovery

4. **Scripts de automatización:**
   - Script de inicialización `./scripts/init-dev.sh`
   - Script de limpieza `./scripts/clean-dev.sh`
   - Script de logs `./scripts/logs.sh`

#### Criterios de Aceptación:
- [ ] Docker-compose funcional con todos los servicios
- [ ] Networking entre contenedores configurado
- [ ] Scripts de automatización funcionando
- [ ] Documentación de comandos Docker

---

### 3. Configuración de Base de Datos

**Responsable:** Backend Developers + DevOps Engineer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Diseñar esquema de bases de datos por microservicio:**
   - `wl_school_auth` - Usuarios, roles, permisos
   - `wl_school_financial` - Transacciones, conceptos, cuentas
   - `wl_school_sports` - Jugadores, categorías, entrenamientos
   - `wl_school_notification` - Templates, cola de mensajes
   - `wl_school_medical` - Historiales, lesiones, tratamientos
   - `wl_school_payroll` - Empleados, nóminas, desprendibles
   - `wl_school_report` - Configuraciones de reportes
   - `wl_school_customization` - Temas, configuraciones
   - `wl_school_calendar` - Eventos, recordatorios

2. **Crear migraciones iniciales:**
   - Estructura base para cada microservicio
   - Seeders con datos de prueba
   - Índices y relaciones necesarias

3. **Configurar conexiones de base de datos:**
   - Variables de entorno para cada servicio
   - Pool de conexiones
   - Configuración de timeouts

#### Criterios de Aceptación:
- [ ] Esquemas de BD diseñados y documentados
- [ ] Migraciones creadas y probadas
- [ ] Conexiones configuradas correctamente
- [ ] Seeders funcionando con datos de prueba

---

### 4. CI/CD Pipeline Básico

**Responsable:** DevOps Engineer  
**Estimación:** 3 días  
**Prioridad:** Media

#### Subtareas:

1. **Configurar GitHub Actions para cada repositorio:**
   ```yaml
   # .github/workflows/ci.yml
   # - Checkout código
   # - Setup PHP/Node
   # - Instalar dependencias
   # - Ejecutar tests
   # - Análisis de código
   # - Build de imágenes Docker
   ```

2. **Pipeline para microservicios Laravel:**
   - Lint con PHP CS Fixer
   - Tests unitarios con PHPUnit
   - Análisis estático con PHPStan
   - Coverage reports
   - Build de imagen Docker

3. **Pipeline para frontend React:**
   - Lint con ESLint
   - Tests con Jest
   - Build de producción
   - Análisis de bundle size

4. **Configurar ambientes:**
   - Development (auto-deploy en push a develop)
   - Staging (auto-deploy en push a main)
   - Production (deploy manual)

#### Criterios de Aceptación:
- [ ] Pipelines configurados en todos los repos
- [ ] Tests ejecutándose automáticamente
- [ ] Builds de Docker funcionando
- [ ] Notificaciones de estado configuradas

---

### 5. Documentación de Setup

**Responsable:** Tech Lead + Todo el equipo  
**Estimación:** 2 días  
**Prioridad:** Media

#### Subtareas:

1. **Crear guía de instalación local:**
   - Prerrequisitos del sistema
   - Instalación de Docker
   - Clonado de repositorios
   - Configuración de variables de entorno
   - Comandos de inicialización

2. **Documentar arquitectura técnica:**
   - Diagrama de infraestructura
   - Flujo de datos entre servicios
   - Puertos y endpoints
   - Variables de entorno por servicio

3. **Crear guías de desarrollo:**
   - Estándares de código
   - Flujo de Git (GitFlow)
   - Proceso de code review
   - Debugging local

4. **Documentar troubleshooting:**
   - Problemas comunes y soluciones
   - Logs y monitoreo
   - Comandos útiles

#### Criterios de Aceptación:
- [ ] Documentación completa y actualizada
- [ ] Guías probadas por desarrolladores
- [ ] README actualizado en cada repositorio
- [ ] Troubleshooting documentado

---

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Todos los repositorios creados y configurados
- [ ] Docker-compose funcional con todos los servicios
- [ ] Bases de datos configuradas con migraciones
- [ ] CI/CD pipelines funcionando
- [ ] Tests básicos pasando
- [ ] Documentación completa

### Criterios de Calidad:
- [ ] Code review completado
- [ ] Documentación revisada
- [ ] Setup probado por al menos 2 desarrolladores
- [ ] Performance básica validada

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Problemas de networking Docker | Media | Alto | Documentar configuración, tener plan B |
| Complejidad de microservicios | Alta | Medio | Empezar simple, iterar |
| Configuración de CI/CD | Media | Medio | Templates probados, documentación |

## Métricas de Éxito

- **Setup time**: < 30 minutos para nuevo desarrollador
- **Build time**: < 5 minutos por servicio
- **Test coverage**: > 80% en componentes críticos
- **Documentation coverage**: 100% de funcionalidades documentadas

## Entregables

1. **Repositorios configurados** - Todos los repos con estructura base
2. **Entorno Docker** - docker-compose.yml funcional
3. **Esquemas de BD** - Migraciones y seeders
4. **Pipelines CI/CD** - GitHub Actions configurado
5. **Documentación** - Guías de setup y desarrollo

## Retrospectiva

### Preguntas para la retrospectiva:
1. ¿Qué funcionó bien en la configuración inicial?
2. ¿Qué obstáculos encontramos?
3. ¿Qué podemos mejorar para el próximo sprint?
4. ¿La documentación es suficiente?
5. ¿El setup es lo suficientemente simple?

---

**Próximo Sprint:** Sprint 2 - Auth Service y API Gateway