<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use App\Models\School;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\RolePermissionSeeder;

class RolePermissionSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Manually run migrations for SQLite in-memory database
        $this->artisan('migrate', ['--database' => 'sqlite']);
        
        // Create a test school
        School::create([
            'name' => 'System School',
            'subdomain' => 'system',
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_creates_all_required_permissions()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        // Check some key permissions that should exist
        $expectedPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete',
            'schools.view', 'schools.edit',
            'financial.view', 'financial.create', 'financial.edit', 'financial.delete',
            'sports.view', 'sports.create', 'sports.edit', 'sports.delete',
            'medical.view', 'medical.create', 'medical.edit', 'medical.delete',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.delete',
            'notifications.view', 'notifications.create', 'notifications.edit', 'notifications.delete',
            'payroll.view', 'payroll.create', 'payroll.edit', 'payroll.delete',
            'reports.view', 'reports.create', 'reports.edit', 'reports.delete',
            'communication.view', 'communication.create', 'communication.edit', 'communication.delete',
            'system.view_logs', 'system.manage_backups', 'system.manage_settings'
        ];

        foreach ($expectedPermissions as $permissionName) {
            $this->assertTrue(
                Permission::where('name', $permissionName)->exists(),
                "Permission {$permissionName} should exist"
            );
        }

        // Check that we have a reasonable number of permissions
        $this->assertGreaterThan(50, Permission::count());
    }

    /** @test */
    public function it_creates_all_required_roles()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        $expectedRoles = [
            'super_admin',
            'school_admin', 
            'coach',
            'medical_staff',
            'financial_manager',
            'secretary',
            'parent',
            'student'
        ];

        foreach ($expectedRoles as $roleName) {
            $this->assertTrue(
                Role::where('name', $roleName)->exists(),
                "Role {$roleName} should exist"
            );
        }

        $this->assertEquals(count($expectedRoles), Role::count());
    }

    /** @test */
    public function it_assigns_all_permissions_to_super_admin()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        $superAdminRole = Role::where('name', 'super_admin')->first();
        $totalPermissions = Permission::count();

        $this->assertEquals(
            $totalPermissions,
            $superAdminRole->permissions()->count(),
            'Super admin should have all permissions'
        );
    }

    /** @test */
    public function it_assigns_correct_permissions_to_school_admin()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        $schoolAdminRole = Role::where('name', 'school_admin')->first();
        $permissions = $schoolAdminRole->permissions()->pluck('name')->toArray();

        // School admin should have most permissions except system administration
        $expectedPermissions = [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_roles',
            'schools.view', 'schools.edit', 'schools.manage_settings',
            'financial.view', 'financial.create', 'financial.edit', 'financial.delete',
            'sports.view', 'sports.create', 'sports.edit', 'sports.delete',
            'medical.view', 'medical.create', 'medical.edit', 'medical.delete',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.delete',
            'notifications.view', 'notifications.create', 'notifications.edit', 'notifications.delete',
            'payroll.view', 'payroll.create', 'payroll.edit', 'payroll.delete',
            'reports.view', 'reports.create', 'reports.edit', 'reports.delete',
            'communication.view', 'communication.create', 'communication.edit', 'communication.delete'
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertContains($permission, $permissions, "School admin should have {$permission} permission");
        }

        // Should NOT have system permissions
        $systemPermissions = ['system.view_logs', 'system.manage_backups', 'system.manage_settings', 'system.view_analytics'];
        foreach ($systemPermissions as $permission) {
            $this->assertNotContains($permission, $permissions, "School admin should NOT have {$permission} permission");
        }
    }

    /** @test */
    public function it_assigns_limited_permissions_to_parent_role()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        $parentRole = Role::where('name', 'parent')->first();
        $permissions = $parentRole->permissions()->pluck('name')->toArray();

        // Parent should only have view permissions for specific modules
        $expectedPermissions = [
            'sports.view', 'sports.view_statistics',
            'medical.view_records',
            'calendar.view',
            'financial.view',
            'notifications.view',
            'communication.view',
            'dashboard.view'
        ];

        $this->assertEquals(count($expectedPermissions), count($permissions));

        foreach ($expectedPermissions as $permission) {
            $this->assertContains($permission, $permissions, "Parent should have {$permission} permission");
        }
    }

    /** @test */
    public function it_creates_super_admin_user()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        $superAdminUser = User::where('email', 'admin@wl-school.com')->first();
        
        $this->assertNotNull($superAdminUser, 'Super admin user should be created');
        $this->assertEquals('Super', $superAdminUser->first_name);
        $this->assertEquals('Admin', $superAdminUser->last_name);
        $this->assertTrue($superAdminUser->hasRole('super_admin'));
    }

    /** @test */
    public function it_assigns_correct_permissions_to_coach_role()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        $coachRole = Role::where('name', 'coach')->first();
        $permissions = $coachRole->permissions()->pluck('name')->toArray();

        $expectedPermissions = [
            'sports.view', 'sports.create', 'sports.edit', 'sports.manage_teams', 'sports.manage_schedules', 'sports.view_statistics',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.manage_events', 'calendar.manage_schedules',
            'users.view',
            'dashboard.view',
            'communication.view', 'communication.send_messages'
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertContains($permission, $permissions, "Coach should have {$permission} permission");
        }
    }

    /** @test */
    public function it_assigns_correct_permissions_to_medical_staff_role()
    {
        // Run the seeder
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        $medicalRole = Role::where('name', 'medical_staff')->first();
        $permissions = $medicalRole->permissions()->pluck('name')->toArray();

        $expectedPermissions = [
            'medical.view', 'medical.create', 'medical.edit', 'medical.view_records', 'medical.manage_appointments', 'medical.manage_treatments', 'medical.export_reports',
            'calendar.view', 'calendar.create', 'calendar.edit', 'calendar.manage_events',
            'users.view',
            'dashboard.view',
            'communication.view', 'communication.send_messages'
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertContains($permission, $permissions, "Medical staff should have {$permission} permission");
        }
    }

    /** @test */
    public function it_can_run_seeder_multiple_times_without_errors()
    {
        // Run the seeder twice
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);
        Artisan::call('db:seed', ['--class' => 'Database\\Seeders\\RolePermissionSeeder']);

        // Should not create duplicates
        $this->assertEquals(8, Role::count());
        $this->assertGreaterThan(50, Permission::count());
        $this->assertEquals(1, User::where('email', 'admin@wl-school.com')->count());
    }
}