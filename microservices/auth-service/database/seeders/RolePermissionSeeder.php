<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $this->createPermissions();

        // Create roles
        $this->createRoles();

        // Assign permissions to roles
        $this->assignPermissionsToRoles();

        // Create super admin user
        $this->createSuperAdminUser();
    }

    /**
     * Create all system permissions
     */
    private function createPermissions()
    {
        $permissions = [
            // Authentication & User Management
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.manage_roles',
            'users.manage_permissions',

            // School Management
            'schools.view',
            'schools.create',
            'schools.edit',
            'schools.delete',
            'schools.manage_settings',
            'schools.manage_subscription',

            // Financial Module
            'financial.view',
            'financial.create',
            'financial.edit',
            'financial.delete',
            'financial.view_reports',
            'financial.manage_payments',
            'financial.manage_fees',
            'financial.export_data',

            // Sports Module
            'sports.view',
            'sports.create',
            'sports.edit',
            'sports.delete',
            'sports.manage_teams',
            'sports.manage_competitions',
            'sports.manage_schedules',
            'sports.view_statistics',

            // Medical Module
            'medical.view',
            'medical.create',
            'medical.edit',
            'medical.delete',
            'medical.view_records',
            'medical.manage_appointments',
            'medical.manage_treatments',
            'medical.export_reports',

            // Calendar Module
            'calendar.view',
            'calendar.create',
            'calendar.edit',
            'calendar.delete',
            'calendar.manage_events',
            'calendar.manage_schedules',

            // Notification Module
            'notifications.view',
            'notifications.create',
            'notifications.edit',
            'notifications.delete',
            'notifications.send',
            'notifications.manage_templates',

            // Payroll Module
            'payroll.view',
            'payroll.create',
            'payroll.edit',
            'payroll.delete',
            'payroll.process',
            'payroll.view_reports',
            'payroll.export_data',

            // Reports & Dashboard
            'reports.view',
            'reports.create',
            'reports.edit',
            'reports.delete',
            'reports.export',
            'dashboard.view',
            'dashboard.customize',

            // Communication Module
            'communication.view',
            'communication.create',
            'communication.edit',
            'communication.delete',
            'communication.send_messages',
            'communication.manage_channels',

            // System Administration
            'system.view_logs',
            'system.manage_backups',
            'system.manage_settings',
            'system.view_analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'api'
            ]);
        }
    }

    /**
     * Create system roles
     */
    private function createRoles()
    {
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'Super Administrador',
                'description' => 'Acceso total al sistema, puede gestionar múltiples escuelas'
            ],
            [
                'name' => 'school_admin',
                'display_name' => 'Administrador de Escuela',
                'description' => 'Administrador con acceso completo a su escuela'
            ],
            [
                'name' => 'coach',
                'display_name' => 'Entrenador',
                'description' => 'Entrenador con acceso a módulos deportivos y calendario'
            ],
            [
                'name' => 'medical_staff',
                'display_name' => 'Personal Médico',
                'description' => 'Personal médico con acceso a registros médicos y citas'
            ],
            [
                'name' => 'financial_manager',
                'display_name' => 'Gestor Financiero',
                'description' => 'Gestor con acceso a módulos financieros y reportes'
            ],
            [
                'name' => 'secretary',
                'display_name' => 'Secretaria/Recepcionista',
                'description' => 'Personal administrativo con acceso limitado'
            ],
            [
                'name' => 'parent',
                'display_name' => 'Padre de Familia',
                'description' => 'Padre con acceso de solo lectura a información de sus hijos'
            ],
            [
                'name' => 'student',
                'display_name' => 'Estudiante',
                'description' => 'Estudiante con acceso limitado a su información'
            ]
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate([
                'name' => $roleData['name'],
                'guard_name' => 'api'
            ]);
        }
    }

    /**
     * Assign permissions to roles
     */
    private function assignPermissionsToRoles()
    {
        // Super Admin - All permissions
        $superAdmin = Role::where('name', 'super_admin')->first();
        $superAdmin->givePermissionTo(Permission::where('guard_name', 'api')->get());

        // School Admin - All permissions except system administration
        $schoolAdmin = Role::where('name', 'school_admin')->first();
        $schoolAdminPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_roles',
            'schools.view', 'schools.edit', 'schools.manage_settings',
            'financial.view', 'financial.create', 'financial.edit', 'financial.delete', 'financial.view_reports', 'financial.manage_payments', 'financial.manage_fees', 'financial.export_data',
            'sports.view', 'sports.create', 'sports.edit', 'sports.delete', 'sports.manage_teams', 'sports.manage_competitions', 'sports.manage_schedules', 'sports.view_statistics',
            'medical.view', 'medical.create', 'medical.edit', 'medical.delete', 'medical.view_records', 'medical.manage_appointments', 'medical.manage_treatments', 'medical.export_reports',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.delete', 'calendar.manage_events', 'calendar.manage_schedules',
            'notifications.view', 'notifications.create', 'notifications.edit', 'notifications.delete', 'notifications.send', 'notifications.manage_templates',
            'payroll.view', 'payroll.create', 'payroll.edit', 'payroll.delete', 'payroll.process', 'payroll.view_reports', 'payroll.export_data',
            'reports.view', 'reports.create', 'reports.edit', 'reports.delete', 'reports.export',
            'dashboard.view', 'dashboard.customize',
            'communication.view', 'communication.create', 'communication.edit', 'communication.delete', 'communication.send_messages', 'communication.manage_channels'
        ];
        $schoolAdmin->givePermissionTo($schoolAdminPermissions);

        // Coach - Sports and calendar access
        $coach = Role::where('name', 'coach')->first();
        $coachPermissions = [
            'sports.view', 'sports.create', 'sports.edit', 'sports.manage_teams', 'sports.manage_schedules', 'sports.view_statistics',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.manage_events', 'calendar.manage_schedules',
            'users.view',
            'dashboard.view',
            'communication.view', 'communication.send_messages'
        ];
        $coach->givePermissionTo($coachPermissions);

        // Medical Staff - Medical module access
        $medicalStaff = Role::where('name', 'medical_staff')->first();
        $medicalPermissions = [
            'medical.view', 'medical.create', 'medical.edit', 'medical.view_records', 'medical.manage_appointments', 'medical.manage_treatments', 'medical.export_reports',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.manage_events',
            'users.view',
            'dashboard.view',
            'communication.view', 'communication.send_messages'
        ];
        $medicalStaff->givePermissionTo($medicalPermissions);

        // Financial Manager - Financial module access
        $financialManager = Role::where('name', 'financial_manager')->first();
        $financialPermissions = [
            'financial.view', 'financial.create', 'financial.edit', 'financial.view_reports', 'financial.manage_payments', 'financial.manage_fees', 'financial.export_data',
            'payroll.view', 'payroll.create', 'payroll.edit', 'payroll.process', 'payroll.view_reports', 'payroll.export_data',
            'reports.view', 'reports.create', 'reports.export',
            'users.view',
            'dashboard.view',
            'communication.view', 'communication.send_messages'
        ];
        $financialManager->givePermissionTo($financialPermissions);

        // Secretary - Administrative access
        $secretary = Role::where('name', 'secretary')->first();
        $secretaryPermissions = [
            'users.view', 'users.create', 'users.edit',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.manage_events',
            'notifications.view', 'notifications.send',
            'communication.view', 'communication.send_messages',
            'dashboard.view'
        ];
        $secretary->givePermissionTo($secretaryPermissions);

        // Parent - Read-only access to relevant information
        $parent = Role::where('name', 'parent')->first();
        $parentPermissions = [
            'sports.view', 'sports.view_statistics',
            'medical.view_records',
            'calendar.view',
            'financial.view',
            'notifications.view',
            'communication.view',
            'dashboard.view'
        ];
        $parent->givePermissionTo($parentPermissions);

        // Student - Limited access to own information
        $student = Role::where('name', 'student')->first();
        $studentPermissions = [
            'sports.view',
            'calendar.view',
            'notifications.view',
            'communication.view',
            'dashboard.view'
        ];
        $student->givePermissionTo($studentPermissions);
    }

    /**
     * Create initial super admin user
     */
    private function createSuperAdminUser()
    {
        // Find existing school or create a default school for super admin
        $defaultSchool = School::where('subdomain', 'system')->first() 
            ?? School::first() 
            ?? School::create([
                'name' => 'Sistema Central',
                'subdomain' => 'system',
                'is_active' => true,
                'subscription_type' => 'enterprise',
                'subscription_expires_at' => now()->addYears(10)
            ]);

        // Create super admin user
        $superAdmin = User::firstOrCreate([
            'email' => 'admin@wl-school.com'
        ], [
            'school_id' => $defaultSchool->id,
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'password' => Hash::make('SuperAdmin123!'),
            'email_verified_at' => now(),
            'is_active' => true
        ]);

        // Assign super admin role
        $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'api')->first();
        $superAdmin->assignRole($superAdminRole);
    }
}