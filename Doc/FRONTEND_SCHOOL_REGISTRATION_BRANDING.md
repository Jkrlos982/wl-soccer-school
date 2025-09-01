# Documentación de Cambios en el Frontend para Registro de Escuelas y Branding

Este documento describe los cambios necesarios en la aplicación frontend para soportar el registro de nuevas escuelas y la adaptación dinámica del branding (logo y colores) según el subdominio de la escuela.

## 1. Formulario de Registro de Escuelas

Se debe implementar un nuevo formulario en el frontend que permita a los usuarios registrar una nueva escuela y crear un usuario `school_admin` asociado. Este formulario enviará los datos al endpoint `/api/v1/auth/register-school` que se ha implementado en el `auth-service`.

### 1.1. Campos del Formulario

El formulario debe incluir los siguientes campos:

*   **Información de la Escuela:**
    *   `Nombre de la Escuela` (school_name): Texto, requerido.
    *   `Subdominio` (subdomain): Texto, requerido. Se debe validar en el frontend para asegurar que solo contenga caracteres alfanuméricos en minúscula y guiones. Se puede mostrar una vista previa del URL completo (ej. `[subdominio].promesas.com`).
    *   `Logo de la Escuela` (school_logo): Campo de tipo `file` para subir una imagen. Opcional.
    *   `Configuración de Tema` (theme_config): Opcional. Podría ser un selector de colores o un campo JSON para configuraciones más avanzadas (ej. `{ "primaryColor": "#1a73e8", "secondaryColor": "#f3f3f3" }`).
*   **Información del Administrador de la Escuela:**
    *   `Nombre` (admin_first_name): Texto, requerido.
    *   `Apellido` (admin_last_name): Texto, requerido.
    *   `Email` (admin_email): Email, requerido. Debe ser único.
    *   `Contraseña` (admin_password): Contraseña, requerido. Mínimo 8 caracteres.
    *   `Confirmar Contraseña` (admin_password_confirmation): Contraseña, requerido. Debe coincidir con `admin_password`.

### 1.2. Manejo del Envío del Formulario

Al enviar el formulario, se debe realizar una solicitud `POST` al endpoint `/api/v1/auth/register-school` del `auth-service`. Es importante manejar la carga de archivos (para el logo) utilizando `FormData`.

```javascript
// Ejemplo de cómo enviar los datos del formulario (usando React/Vue/Angular con fetch o Axios)

const handleSubmit = async (event) => {
    event.preventDefault();

    const formData = new FormData();
    formData.append('school_name', schoolName);
    formData.append('subdomain', subdomain);
    if (schoolLogo) {
        formData.append('school_logo', schoolLogo);
    }
    if (themeConfig) {
        formData.append('theme_config', JSON.stringify(themeConfig)); // Enviar como string JSON
    }
    formData.append('admin_first_name', adminFirstName);
    formData.append('admin_last_name', adminLastName);
    formData.append('admin_email', adminEmail);
    formData.append('admin_password', adminPassword);
    formData.append('admin_password_confirmation', adminPasswordConfirmation);

    try {
        const response = await fetch('/api/v1/auth/register-school', {
            method: 'POST',
            body: formData,
            // No es necesario establecer 'Content-Type' para FormData, el navegador lo hace automáticamente
        });

        const data = await response.json();

        if (response.ok) {
            alert('Escuela y administrador registrados exitosamente!');
            // Redirigir al usuario o limpiar el formulario
        } else {
            alert(`Error al registrar: ${data.message || 'Error desconocido'}`);
            console.error(data.error);
        }
    } catch (error) {
        console.error('Error de red:', error);
        alert('Ocurrió un error de conexión.');
    }
};
```

## 2. Adaptación del Branding en el Frontend

La aplicación frontend debe ser capaz de detectar el subdominio actual y, basándose en él, cargar y aplicar la configuración de branding específica de la escuela. Esto afectará principalmente al formulario de login y a la interfaz general de la aplicación.

### 2.1. Lógica de Detección y Carga

1.  **Detección del Subdominio**: Al cargar la aplicación, se debe extraer el subdominio de `window.location.hostname`. Por ejemplo, si la URL es `wl-school.promesas.com`, el subdominio sería `wl-school`.
2.  **Solicitud de Configuración**: Se realizará una llamada a la API (ej. `/api/school-config?subdomain=wl-school`) para obtener los datos de `theme_config` y `logo` de la escuela. Este endpoint debería ser público y solo devolver información de branding.
3.  **Almacenamiento de Configuración**: La configuración de branding obtenida debe almacenarse en un contexto global (ej. Context API en React, Vuex en Vue, NGRX en Angular) para que sea accesible por todos los componentes.

### 2.2. Aplicación del Branding

*   **Logo**: El componente del logo en el formulario de login y en el encabezado de la aplicación debe actualizar su `src` dinámicamente con la URL del logo de la escuela.
    ```html
    <img id="school-logo" src="/default-logo.png" alt="School Logo" />
    ```
    ```javascript
    // Después de cargar la configuración de branding
    document.getElementById('school-logo').src = `/storage/${brandingConfig.logo}`;
    ```
*   **Colores y Estilos**: Los colores principales y otros estilos definidos en `theme_config` deben aplicarse dinámicamente. Esto se puede lograr de varias maneras:
    *   **Variables CSS**: La forma más sencilla es definir variables CSS (custom properties) en el `:root` o en un elemento contenedor, y luego actualizar sus valores con JavaScript.
        ```css
        :root {
            --primary-color: #1a73e8;
            --secondary-color: #f3f3f3;
        }
        /* En tus componentes */
        .button { background-color: var(--primary-color); }
        ```
        ```javascript
        // Después de cargar la configuración de branding
        document.documentElement.style.setProperty('--primary-color', brandingConfig.theme_config.primaryColor);
        document.documentElement.style.setProperty('--secondary-color', brandingConfig.theme_config.secondaryColor);
        ```
    *   **CSS-in-JS**: Si se utilizan librerías como Styled Components o Emotion, se pueden pasar las propiedades de tema directamente a los componentes estilizados.
    *   **Clases CSS Dinámicas**: Se pueden añadir clases CSS al `body` o a un contenedor principal basadas en el subdominio o un ID de tema, y luego definir estilos específicos para esas clases.

### 2.3. Formulario de Login Personalizado

El formulario de login debe ser el principal punto donde se refleje el branding. Esto incluye:

*   Mostrar el logo de la escuela.
*   Aplicar los colores de la escuela a los botones, enlaces y otros elementos de la UI.
*   Posiblemente, mostrar el nombre de la escuela en el título o subtítulo del formulario.

## 3. Consideraciones Adicionales

*   **Rutas Protegidas**: Asegurarse de que las rutas de registro de escuelas y la configuración de branding sean accesibles públicamente, mientras que otras rutas sensibles permanezcan protegidas por autenticación.
*   **Manejo de Errores**: Implementar un manejo robusto de errores para el formulario de registro y la carga de branding.
*   **Carga de Estado**: Mostrar indicadores de carga mientras se envían los datos del formulario o se carga la configuración de branding.
*   **Validación Frontend**: Aunque el backend valida los datos, es buena práctica implementar validaciones básicas en el frontend para una mejor experiencia de usuario.