# Sprint 2: Auth Service y API Gateway

**Duración:** 2 semanas  
**Fase:** 1 - Fundación y Autenticación  
**Objetivo:** Implementar el sistema de autenticación completo y establecer el API Gateway

## Resumen del Sprint

Este sprint se enfoca en crear el corazón del sistema de seguridad: el microservicio de autenticación con JWT, sistema de roles y permisos, junto con el API Gateway que manejará el enrutamiento y la seguridad de todas las peticiones.

## Objetivos Específicos

- ✅ Desarrollar microservicio de autenticación completo
- ✅ Implementar JWT con refresh tokens
- ✅ Crear sistema de roles y permisos granular
- ✅ Establecer API Gateway con enrutamiento
- ✅ Desarrollar frontend de login/registro

## Tareas Detalladas

### 1. Microservicio de Autenticación (Auth Service)

**Responsable:** Backend Developer Senior  
**Estimación:** 4 días  
**Prioridad:** Alta

#### Subtareas:

1. **Configurar proyecto Laravel para Auth Service:**
   ```bash
   # Crear proyecto base
   composer create-project laravel/laravel auth-service
   # Instalar dependencias específicas
   composer require tymon/jwt-auth
   composer require spatie/laravel-permission
   composer require laravel/sanctum
   ```

2. **Crear modelos y migraciones:**
   - **User Model:**
     ```php
     // Campos: id, email, password, first_name, last_name, 
     // phone, is_active, email_verified_at, school_id
     ```
   - **School Model:**
     ```php
     // Campos: id, name, subdomain, logo, theme_config, 
     // is_active, subscription_type
     ```
   - **Role y Permission Models** (usando Spatie)

3. **Implementar controladores de autenticación:**
   - `AuthController` - Login, logout, refresh
   - `RegisterController` - Registro de usuarios
   - `PasswordController` - Reset y cambio de contraseña
   - `ProfileController` - Gestión de perfil

4. **Configurar JWT:**
   ```php
   // Configurar tokens de acceso (15 min)
   // Configurar refresh tokens (7 días)
   // Implementar blacklist de tokens
   // Configurar claims personalizados
   ```

5. **Crear middleware de autenticación:**
   - `JwtAuthMiddleware` - Validación de tokens
   - `RoleMiddleware` - Verificación de roles
   - `PermissionMiddleware` - Verificación de permisos
   - `SchoolMiddleware` - Aislamiento por escuela

#### Criterios de Aceptación:
- [ ] Usuario puede registrarse con validaciones
- [ ] Login funcional con JWT
- [ ] Refresh token implementado
- [ ] Sistema de roles y permisos funcional
- [ ] Middleware de seguridad funcionando
- [ ] Tests unitarios > 85% cobertura

---

### 2. Sistema de Roles y Permisos

**Responsable:** Backend Developer  
**Estimación:** 2 días  
**Prioridad:** Alta

#### Subtareas:

1. **Definir roles del sistema:**
   ```php
   // Super Admin - Acceso total al sistema
   // School Admin - Administrador de escuela
   // Coach - Entrenador con acceso a deportes
   // Medical Staff - Personal médico
   // Financial Manager - Gestor financiero
   // Secretary - Secretaria/recepcionista
   // Parent - Padre de familia (solo lectura)
   ```

2. **Definir permisos granulares:**
   ```php
   // Módulo Financiero
   'financial.view', 'financial.create', 'financial.edit', 'financial.delete'
   // Módulo Deportivo
   'sports.view', 'sports.create', 'sports.edit', 'sports.delete'
   // Módulo Médico
   'medical.view', 'medical.create', 'medical.edit', 'medical.delete'
   // Y así para cada módulo...
   ```

3. **Crear seeders para roles y permisos:**
   ```php
   // RolePermissionSeeder
   // Asignar permisos por defecto a cada rol
   // Crear usuario super admin inicial
   ```

4. **Implementar API endpoints:**
   - `GET /api/v1/roles` - Listar roles
   - `POST /api/v1/users/{id}/roles` - Asignar rol
   - `GET /api/v1/permissions` - Listar permisos
   - `POST /api/v1/roles/{id}/permissions` - Asignar permisos

#### Criterios de Aceptación:
- [ ] Roles definidos y creados
- [ ] Permisos granulares implementados
- [ ] Seeders funcionando correctamente
- [ ] API endpoints para gestión de roles
- [ ] Validación de permisos en endpoints

---

### 3. API Gateway

**Responsable:** DevOps Engineer + Backend Developer  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Configurar Nginx como API Gateway:**
   ```nginx
   # Configurar upstream servers
   upstream auth_service {
       server auth-service:8000;
   }
   upstream financial_service {
       server financial-service:8000;
   }
   # Configurar routing por path
   ```

2. **Implementar rate limiting:**
   ```nginx
   # Límites por IP
   limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
   # Límites por usuario autenticado
   limit_req_zone $jwt_user_id zone=user:10m rate=100r/s;
   ```

3. **Configurar CORS y headers de seguridad:**
   ```nginx
   # CORS headers
   add_header Access-Control-Allow-Origin $cors_origin;
   # Security headers
   add_header X-Frame-Options DENY;
   add_header X-Content-Type-Options nosniff;
   ```

4. **Implementar logging y monitoreo:**
   ```nginx
   # Access logs con formato JSON
   log_format json_combined escape=json '{'...'}';
   # Error logs detallados
   ```

5. **Crear middleware de validación JWT:**
   ```lua
   -- Validar JWT en Nginx usando lua
   -- Extraer claims del token
   -- Pasar información al upstream
   ```

#### Criterios de Aceptación:
- [ ] Routing funcionando a todos los servicios
- [ ] Rate limiting implementado
- [ ] CORS configurado correctamente
- [ ] Logging estructurado funcionando
- [ ] Validación JWT en gateway

---

### 4. Frontend Login/Register

**Responsable:** Frontend Developer  
**Estimación:** 3 días  
**Prioridad:** Alta

#### Subtareas:

1. **Configurar proyecto React PWA:**
   ```bash
   npx create-react-app wl-school-frontend --template typescript
   npm install @mui/material @emotion/react @emotion/styled
   npm install @reduxjs/toolkit react-redux
   npm install axios react-router-dom
   ```

2. **Crear componentes de autenticación:**
   - `LoginForm` - Formulario de login
   - `RegisterForm` - Formulario de registro
   - `ForgotPasswordForm` - Recuperación de contraseña
   - `AuthLayout` - Layout para páginas de auth

3. **Implementar gestión de estado:**
   ```typescript
   // Redux slice para autenticación
   interface AuthState {
     user: User | null;
     token: string | null;
     isLoading: boolean;
     error: string | null;
   }
   ```

4. **Crear servicios de API:**
   ```typescript
   // authService.ts
   class AuthService {
     async login(credentials: LoginCredentials): Promise<AuthResponse>
     async register(userData: RegisterData): Promise<AuthResponse>
     async refreshToken(): Promise<TokenResponse>
     async logout(): Promise<void>
   }
   ```

5. **Implementar rutas protegidas:**
   ```typescript
   // ProtectedRoute component
   // Verificar autenticación
   // Redirigir a login si no autenticado
   // Verificar permisos si es necesario
   ```

6. **Configurar PWA:**
   ```json
   // manifest.json
   {
     "name": "WL School",
     "short_name": "WLSchool",
     "theme_color": "#000000",
     "background_color": "#ffffff",
     "display": "standalone",
     "start_url": "/"
   }
   ```

#### Criterios de Aceptación:
- [ ] Login funcional con validaciones
- [ ] Registro de usuarios funcionando
- [ ] Recuperación de contraseña implementada
- [ ] Estado de autenticación persistente
- [ ] Rutas protegidas funcionando
- [ ] PWA configurada correctamente

---

### 5. Testing y Documentación

**Responsable:** QA Engineer + Todo el equipo  
**Estimación:** 2 días  
**Prioridad:** Media

#### Subtareas:

1. **Tests unitarios para Auth Service:**
   ```php
   // AuthControllerTest
   // UserModelTest
   // JwtServiceTest
   // RolePermissionTest
   ```

2. **Tests de integración:**
   ```php
   // Login flow completo
   // Refresh token flow
   // Role assignment flow
   // Permission validation
   ```

3. **Tests frontend:**
   ```typescript
   // LoginForm.test.tsx
   // RegisterForm.test.tsx
   // AuthService.test.ts
   // ProtectedRoute.test.tsx
   ```

4. **Documentación de API:**
   ```yaml
   # OpenAPI/Swagger spec
   # Endpoints de autenticación
   # Modelos de datos
   # Ejemplos de requests/responses
   ```

5. **Guías de usuario:**
   - Cómo registrarse en el sistema
   - Cómo recuperar contraseña
   - Gestión de roles y permisos

#### Criterios de Aceptación:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración pasando
- [ ] Tests frontend funcionando
- [ ] Documentación API completa
- [ ] Guías de usuario creadas

---

## API Endpoints Implementados

### Auth Service
```
POST /api/v1/auth/login
POST /api/v1/auth/register
POST /api/v1/auth/logout
POST /api/v1/auth/refresh
POST /api/v1/auth/forgot-password
POST /api/v1/auth/reset-password
GET  /api/v1/auth/me
PUT  /api/v1/auth/profile

GET  /api/v1/roles
POST /api/v1/roles
PUT  /api/v1/roles/{id}
DEL  /api/v1/roles/{id}

GET  /api/v1/permissions
POST /api/v1/users/{id}/roles
DEL  /api/v1/users/{id}/roles/{roleId}
```

## Definición de Terminado (DoD)

### Criterios Técnicos:
- [ ] Auth Service completamente funcional
- [ ] JWT implementado con refresh tokens
- [ ] Sistema de roles y permisos operativo
- [ ] API Gateway enrutando correctamente
- [ ] Frontend de auth funcionando
- [ ] PWA configurada

### Criterios de Calidad:
- [ ] Tests unitarios > 85% cobertura
- [ ] Tests de integración pasando
- [ ] Code review completado
- [ ] Documentación API actualizada
- [ ] Performance validada (< 200ms login)

### Criterios de Seguridad:
- [ ] Validación de inputs implementada
- [ ] Rate limiting funcionando
- [ ] CORS configurado correctamente
- [ ] Headers de seguridad implementados
- [ ] Tokens seguros (no en localStorage)

## Riesgos Identificados

| Riesgo | Probabilidad | Impacto | Mitigación |
|--------|-------------|---------|------------|
| Complejidad JWT | Media | Alto | Usar librerías probadas, tests exhaustivos |
| Performance API Gateway | Media | Medio | Load testing, optimización Nginx |
| Seguridad auth | Alta | Crítico | Security review, penetration testing |
| Integración frontend-backend | Media | Medio | Tests de integración, mocks |

## Métricas de Éxito

- **Login time**: < 200ms
- **Token refresh**: < 100ms
- **API Gateway latency**: < 50ms overhead
- **Security score**: 100% en audit de seguridad
- **Test coverage**: > 85%

## Entregables

1. **Auth Service** - Microservicio completo con JWT
2. **API Gateway** - Nginx configurado con routing
3. **Frontend Auth** - Login/register funcional
4. **Sistema de Roles** - Roles y permisos implementados
5. **Documentación** - API docs y guías de usuario
6. **Tests** - Suite completa de tests

## Configuración de Entorno

### Variables de Entorno Auth Service
```env
JWT_SECRET=your-256-bit-secret
JWT_TTL=15
JWT_REFRESH_TTL=10080
DB_HOST=mysql-auth
DB_DATABASE=wl_school_auth
REDIS_HOST=redis
```

### Variables de Entorno Frontend
```env
REACT_APP_API_URL=http://localhost:8080/api/v1
REACT_APP_AUTH_SERVICE_URL=http://localhost:8080/api/v1/auth
```

## Retrospectiva

### Preguntas para la retrospectiva:
1. ¿El sistema de autenticación es suficientemente seguro?
2. ¿La experiencia de usuario en login es fluida?
3. ¿El API Gateway introduce latencia significativa?
4. ¿Los roles y permisos cubren todos los casos de uso?
5. ¿Qué podemos mejorar en la arquitectura de seguridad?

---

**Sprint Anterior:** Sprint 1 - Infraestructura Base  
**Próximo Sprint:** Sprint 3 - Financial Service (Parte 1)