# Flujo de Datos y APIs para el Listado de Estudiantes por Escuela

Este documento describe el flujo de datos y las interacciones de la API para un escenario donde un usuario (perteneciente a una escuela específica) desea ver un listado de todos los estudiantes de su escuela.

## Escenario

Un usuario `X` que pertenece a la `Escuela Y` quiere ver un listado de todos los estudiantes de la `Escuela Y`.

## Microservicios Involucrados

1.  **API Gateway:** Punto de entrada para todas las solicitudes externas, encargado de enrutar las solicitudes a los microservicios apropiados y manejar la autenticación/autorización inicial.
2.  **Auth Service:** Gestiona la autenticación de usuarios, la autorización y la información de la relación usuario-escuela.
3.  **Student Service (hipotético):** Gestiona la información de los estudiantes, incluyendo su asociación con una escuela.

## Flujo de Datos

El flujo de datos para obtener el listado de estudiantes de una escuela específica sería el siguiente:

1.  **Solicitud del Cliente:** El usuario `X` (a través de la aplicación frontend) realiza una solicitud para ver el listado de estudiantes de su escuela.
    *   **Ejemplo de Solicitud (Frontend a API Gateway):**
        ```
        GET /api/v1/students?school_id=Y
        Headers: {
            "Authorization": "Bearer <token_de_autenticacion>"
        }
        ```

2.  **API Gateway:**
    *   El API Gateway intercepta la solicitud.
    *   **Autenticación y Autorización (con Auth Service):** El Gateway valida el token de autenticación (`<token_de_autenticacion>`) con el `Auth Service` para verificar la identidad del usuario `X` y si tiene permisos para acceder a los datos de la `Escuela Y`. También recupera el `school_id` asociado al usuario `X` si no se proporciona explícitamente en la solicitud o para validarlo.
    *   Si la autenticación y autorización son exitosas, el Gateway enruta la solicitud al `Student Service`.

3.  **Auth Service (interacción con API Gateway):**
    *   **API:** `GET /api/v1/auth/validate-token`
        *   **Parámetros:** `token` (en el header `Authorization`)
        *   **Respuesta Exitosa:** `HTTP 200 OK`, `body: { "user_id": "X", "school_id": "Y", "roles": ["admin", "teacher"] }`
    *   **API:** `GET /api/v1/auth/user-school-info` (si el `school_id` no viene en la solicitud original y se necesita obtener del usuario autenticado)
        *   **Parámetros:** `user_id`
        *   **Respuesta Exitosa:** `HTTP 200 OK`, `body: { "school_id": "Y" }`

4.  **Student Service:**
    *   El `Student Service` recibe la solicitud del API Gateway (que ya ha sido validada y posiblemente enriquecida con el `school_id`).
    *   Consulta su base de datos para obtener todos los estudiantes asociados con el `school_id` proporcionado.
    *   Prepara la respuesta con la lista de estudiantes.

5.  **Respuesta del Student Service al API Gateway:**
    *   **API:** `GET /api/v1/students`
        *   **Parámetros:** `school_id` (query parameter)
        *   **Respuesta Exitosa:** `HTTP 200 OK`, `body: [ { "id": "s1", "name": "Estudiante A", "school_id": "Y" }, { "id": "s2", "name": "Estudiante B", "school_id": "Y" }, ... ]`

6.  **API Gateway:** El Gateway recibe la respuesta del `Student Service` y la reenvía al cliente (aplicación frontend).

7.  **Cliente (Aplicación Frontend):** La aplicación frontend recibe la lista de estudiantes y la muestra al usuario `X`.

## APIs Necesarias (Ejemplos)

### API Gateway (Endpoints expuestos al cliente)

*   `GET /api/v1/students`: Obtiene una lista de estudiantes. Puede aceptar `school_id` como parámetro de consulta. Requiere token de autenticación.

### Auth Service (Endpoints internos, llamados por API Gateway u otros microservicios)

*   `GET /api/v1/auth/validate-token`: Valida un token de autenticación y devuelve la información del usuario (ID, roles, `school_id`).
    *   **Request:** `GET /api/v1/auth/validate-token`, `Headers: { "Authorization": "Bearer <token>" }`
    *   **Response (200 OK):** `{
        "user_id": "uuid-del-usuario",
        "school_id": "uuid-de-la-escuela",
        "roles": ["student", "teacher", "admin"]
    }`
*   `GET /api/v1/auth/user-school-info/{user_id}`: Obtiene la información de la escuela asociada a un usuario específico.
    *   **Request:** `GET /api/v1/auth/user-school-info/uuid-del-usuario`
    *   **Response (200 OK):** `{
        "school_id": "uuid-de-la-escuela"
    }`

### Student Service (Endpoints internos, llamados por API Gateway)

*   `GET /api/v1/students`: Obtiene una lista de estudiantes, filtrada opcionalmente por `school_id`.
    *   **Request:** `GET /api/v1/students?school_id=uuid-de-la-escuela`
    *   **Response (200 OK):** `[
        {
            "id": "uuid-del-estudiante-1",
            "name": "Nombre Estudiante 1",
            "school_id": "uuid-de-la-escuela",
            "grade": "10",
            "date_of_birth": "2007-01-15"
        },
        {
            "id": "uuid-del-estudiante-2",
            "name": "Nombre Estudiante 2",
            "school_id": "uuid-de-la-escuela",
            "grade": "11",
            "date_of_birth": "2006-03-20"
        }
    ]`

## Consideraciones Adicionales

*   **Paginación y Filtrado:** Las APIs de listado de estudiantes deberían soportar paginación (`page`, `limit`) y filtrado adicional (por ejemplo, por nombre, grado).
*   **Manejo de Errores:** Cada servicio debe manejar errores (ej. usuario no autorizado, escuela no encontrada, errores internos del servidor) y devolver códigos de estado HTTP apropiados.
*   **Seguridad:** Asegurar que todas las comunicaciones entre microservicios sean seguras (ej. HTTPS, mTLS).
*   **Observabilidad:** Implementar logging, métricas y tracing distribuido para monitorear el flujo de solicitudes a través de los microservicios.
*   **Descubrimiento de Servicios:** Los microservicios deberían poder descubrirse entre sí (ej. usando un registro de servicios como Eureka o Consul).

Este diseño mantiene la independencia de los microservicios, permitiendo que cada uno gestione su propio dominio de datos y lógica de negocio, y se comuniquen a través de APIs bien definidas.