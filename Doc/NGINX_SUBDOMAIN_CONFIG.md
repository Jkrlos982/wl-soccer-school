# Configuración de Nginx para Subdominios Dinámicos y Branding

Este documento describe cómo configurar Nginx para manejar subdominios dinámicos para cada escuela registrada y cómo esto se relaciona con la adaptación del branding.

## 1. Concepto de Subdominios Dinámicos

Cada escuela registrada en el sistema tendrá un subdominio único (ej. `wl-school.promesas.com`). Nginx actuará como un proxy inverso, dirigiendo las solicitudes entrantes basadas en el subdominio a la aplicación frontend principal, que luego se encargará de cargar la configuración de branding específica de la escuela.

## 2. Configuración de Nginx

La configuración de Nginx debe incluir un bloque `server` que capture cualquier subdominio bajo el dominio principal (ej. `promesas.com`) y lo redirija a la aplicación frontend. Esto se logra utilizando una variable `server_name` con un wildcard.

### 2.1. Ejemplo de Configuración de Nginx

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name *.promesas.com promesas.com;

    # Redirigir HTTP a HTTPS (opcional pero recomendado)
    # return 301 https://$host$request_uri;

    root /var/www/html/frontend_app/build; # Ruta a tu aplicación frontend (ej. React, Vue, Angular)
    index index.html index.htm;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Proxy a la API Gateway (si la API está en un subdominio diferente o ruta específica)
    location /api/ {
        proxy_pass http://api_gateway_service:8000/api/;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # Configuración para SSL (si se usa HTTPS)
    # listen 443 ssl;
    # listen [::]:443 ssl;
    # ssl_certificate /etc/nginx/ssl/promesas.com.crt;
    # ssl_certificate_key /etc/nginx/ssl/promesas.com.key;
}
```

**Explicación de la Configuración:**

*   `server_name *.promesas.com promesas.com;`: Este directiva le dice a Nginx que este bloque `server` debe manejar cualquier solicitud para `promesas.com` y cualquier subdominio bajo `*.promesas.com`.
*   `root /var/www/html/frontend_app/build;`: Define la raíz de los archivos estáticos de tu aplicación frontend. Asegúrate de que esta ruta apunte al directorio de construcción de tu aplicación (ej. `build` para React, `dist` para Vue/Angular).
*   `location / { try_files $uri $uri/ /index.html; }`: Esta configuración es crucial para las aplicaciones de una sola página (SPA). Asegura que todas las rutas que no son archivos estáticos se redirijan a `index.html`, permitiendo que el enrutador del frontend maneje la navegación.
*   `location /api/ { ... }`: Si tu API Gateway está expuesta en un subdominio o ruta diferente, puedes configurar un proxy inverso aquí para dirigir las solicitudes a tu backend.

## 3. Manejo del Branding en el Frontend

Una vez que Nginx redirige la solicitud al frontend, la aplicación cliente (ej. React, Vue) debe ser capaz de identificar el subdominio de la URL actual y, basándose en él, cargar la configuración de branding (logo, colores, etc.) específica de la escuela.

### 3.1. Pasos en el Frontend

1.  **Obtener el Subdominio**: Al cargar la aplicación, el frontend puede extraer el subdominio de `window.location.hostname`.
2.  **Solicitar Configuración de Branding**: Utilizando el subdominio, el frontend realizará una llamada a la API (ej. a un endpoint como `/api/school-config?subdomain=wl-school`) para obtener los datos de `theme_config` y `logo` de la escuela.
3.  **Aplicar Branding**: Una vez recibida la configuración, el frontend aplicará dinámicamente los estilos y el logo a la interfaz de usuario, especialmente al formulario de login.

### 3.2. Ejemplo de Lógica Frontend (Pseudocódigo)

```javascript
// En el archivo principal de la aplicación o en un componente de inicialización

function getSubdomain() {
    const parts = window.location.hostname.split('.');
    if (parts.length > 2 && parts[parts.length - 2] === 'promesas' && parts[parts.length - 1] === 'com') {
        return parts[0]; // Retorna 'wl-school' si la URL es 'wl-school.promesas.com'
    }
    return null; // No es un subdominio de escuela
}

async function loadSchoolBranding() {
    const subdomain = getSubdomain();
    if (subdomain) {
        try {
            const response = await fetch(`/api/school-config?subdomain=${subdomain}`);
            const data = await response.json();
            if (data.theme_config) {
                // Aplicar estilos dinámicamente (ej. usando CSS-in-JS o variables CSS)
                document.documentElement.style.setProperty('--primary-color', data.theme_config.primaryColor);
                // ... aplicar otros estilos
            }
            if (data.logo) {
                // Actualizar la imagen del logo
                document.getElementById('school-logo').src = `/storage/${data.logo}`;
            }
        } catch (error) {
            console.error('Error loading school branding:', error);
        }
    }
}

// Llamar a esta función al inicio de la aplicación
loadSchoolBranding();
```

## 4. Consideraciones Adicionales

*   **DNS Wildcard**: Asegúrate de que tu proveedor de DNS tenga una entrada de tipo `A` o `CNAME` para `*.promesas.com` apuntando a la IP de tu servidor Nginx.
*   **HTTPS/SSL**: Para entornos de producción, es fundamental configurar SSL para el wildcard (`*.promesas.com`). Esto requiere un certificado SSL wildcard (ej. de Let's Encrypt).
*   **Caché**: Configura adecuadamente el caché de Nginx para los archivos estáticos del frontend para mejorar el rendimiento.
*   **Seguridad**: Asegúrate de que la API `school-config` solo exponga la información de branding necesaria y no datos sensibles de la escuela.