# Documentación de Cambios en `auth-service` para Registro de Escuelas

Este documento detalla los cambios implementados en el microservicio `auth-service` para permitir el registro de nuevas escuelas y la creación automática de un usuario `school_admin` asociado a cada escuela.

## 1. Modelos Existentes

Se ha confirmado que los modelos `School` y `User` en `microservices/auth-service/app/Models/` ya contienen los campos necesarios para esta funcionalidad:

*   **`School.php`**: Incluye `name`, `subdomain`, `logo`, `theme_config`, `is_active`, `subscription_type`, y `subscription_expires_at`. El campo `subdomain` es crucial para la funcionalidad de subdominios dinámicos, y `logo` y `theme_config` para el branding.
*   **`User.php`**: Contiene `school_id`, lo que permite la asociación directa de usuarios a una escuela específica.

## 2. Nuevo Controlador: `SchoolRegistrationController`

Se ha creado un nuevo controlador en `microservices/auth-service/app/Http/Controllers/Api/V1/Auth/SchoolRegistrationController.php` con el método `registerSchool`.

### 2.1. Funcionalidad del `registerSchool`

Este método maneja la lógica de negocio para el registro de una nueva escuela y su administrador:

1.  **Validación de Datos**: Valida los datos de entrada para la escuela (nombre, subdominio, logo, configuración de tema) y para el usuario administrador (nombre, apellido, email, contraseña).
    *   Se asegura que el nombre de la escuela y el subdominio sean únicos.
    *   El subdominio solo permite caracteres alfanuméricos en minúscula y guiones.
2.  **Transacción de Base de Datos**: Envuelve la creación de la escuela y el usuario en una transacción para asegurar la atomicidad de la operación.
3.  **Creación de la Escuela**: Crea una nueva entrada en la tabla `schools` con los datos proporcionados. Si se proporciona un logo, lo almacena en el sistema de archivos y guarda la ruta.
4.  **Creación del Usuario Administrador**: Crea un nuevo usuario en la tabla `users` asociado a la `school_id` recién creada. El email del administrador se verifica automáticamente para simplificar el proceso inicial.
5.  **Asignación de Rol**: Asigna el rol `school_admin` al usuario recién creado. Se verifica la existencia de este rol y se lanza una excepción si no se encuentra (lo que indica que los roles deben ser sembrados previamente).
6.  **Respuesta**: Retorna una respuesta JSON indicando el éxito del registro, incluyendo los datos de la escuela y el usuario administrador.

### 2.2. Código del Controlador (`SchoolRegistrationController.php`)

```php
<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\School;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SchoolRegistrationController extends Controller
{
    public function registerSchool(Request $request)
    {
        $request->validate([
            'school_name' => ['required', 'string', 'max:255', 'unique:schools,name'],
            'subdomain' => ['required', 'string', 'max:255', 'unique:schools,subdomain', 'regex:/^[a-z0-9-]+$/'],
            'school_logo' => ['nullable', 'image', 'max:2048'], // Max 2MB
            'theme_config' => ['nullable', 'array'],
            'admin_first_name' => ['required', 'string', 'max:255'],
            'admin_last_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::beginTransaction();

        try {
            // 1. Create the School
            $school = School::create([
                'name' => $request->school_name,
                'subdomain' => $request->subdomain,
                'theme_config' => $request->theme_config ?? [],
                'is_active' => true,
                'subscription_type' => 'free', // Default to free subscription
            ]);

            // Handle school logo upload
            if ($request->hasFile('school_logo')) {
                $logoPath = $request->file('school_logo')->store('logos', 'public');
                $school->logo = $logoPath;
                $school->save();
            }

            // 2. Create the School Admin User
            $adminUser = User::create([
                'school_id' => $school->id,
                'first_name' => $request->admin_first_name,
                'last_name' => $request->admin_last_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'is_active' => true,
                'email_verified_at' => now(), // Auto-verify for simplicity, can be changed
            ]);

            // 3. Assign 'school_admin' role to the user
            $schoolAdminRole = Role::where('name', 'school_admin')->first();
            if (!$schoolAdminRole) {
                throw new \Exception('Role "school_admin" not found. Please seed roles.');
            }
            $adminUser->assignRole($schoolAdminRole);

            DB::commit();

            return response()->json([
                'message' => 'School and School Admin registered successfully.',
                'school' => $school,
                'admin_user' => $adminUser,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'School registration failed.', 'error' => $e->getMessage()], 500);
        }
    }
}
```

## 3. Modificación de Rutas API (`api.php`)

Se ha añadido una nueva ruta pública en `microservices/auth-service/routes/api.php` para exponer el endpoint de registro de escuelas.

### 3.1. Ruta Añadida

```php
// ... existing routes ...

// School Registration Route (public)
Route::post('/register-school', [SchoolRegistrationController::class, 'registerSchool']);

// ... existing routes ...
```

Esta ruta permite que el frontend envíe solicitudes POST a `/api/v1/auth/register-school` para iniciar el proceso de registro de una nueva escuela y su administrador.