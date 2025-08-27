<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Illuminate\Database\Eloquent\Collection;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $school;
    protected $role;
    protected $permission;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create a test user
        $this->user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@testschool.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Create test role and permission
        $this->role = Role::create([
            'name' => 'teacher',
            'guard_name' => 'api'
        ]);
        
        $this->permission = Permission::create([
            'name' => 'view-students',
            'guard_name' => 'api'
        ]);
    }

    /** @test */
    public function it_can_create_role()
    {
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);
        
        $this->assertInstanceOf(Role::class, $role);
        $this->assertEquals('admin', $role->name);
        $this->assertEquals('api', $role->guard_name);
        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'guard_name' => 'api'
        ]);
    }

    /** @test */
    public function it_can_create_permission()
    {
        $permission = Permission::create([
            'name' => 'edit-grades',
            'guard_name' => 'api'
        ]);
        
        $this->assertInstanceOf(Permission::class, $permission);
        $this->assertEquals('edit-grades', $permission->name);
        $this->assertEquals('api', $permission->guard_name);
        $this->assertDatabaseHas('permissions', [
            'name' => 'edit-grades',
            'guard_name' => 'api'
        ]);
    }

    /** @test */
    public function it_can_assign_role_to_user()
    {
        $this->user->assignRole($this->role);
        
        $this->assertTrue($this->user->hasRole('teacher'));
        $this->assertDatabaseHas('model_has_roles', [
            'role_id' => $this->role->id,
            'model_type' => User::class,
            'model_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_assign_multiple_roles_to_user()
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'api']);
        
        $this->user->assignRole([$this->role, $adminRole, $studentRole]);
        
        $this->assertTrue($this->user->hasRole('teacher'));
        $this->assertTrue($this->user->hasRole('admin'));
        $this->assertTrue($this->user->hasRole('student'));
        $this->assertTrue($this->user->hasAnyRole(['teacher', 'admin']));
        $this->assertTrue($this->user->hasAllRoles(['teacher', 'admin', 'student']));
    }

    /** @test */
    public function it_can_remove_role_from_user()
    {
        $this->user->assignRole($this->role);
        $this->assertTrue($this->user->hasRole('teacher'));
        
        $this->user->removeRole($this->role);
        
        $this->assertFalse($this->user->hasRole('teacher'));
        $this->assertDatabaseMissing('model_has_roles', [
            'role_id' => $this->role->id,
            'model_type' => User::class,
            'model_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_sync_roles_for_user()
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'api']);
        
        // Assign initial roles
        $this->user->assignRole([$this->role, $adminRole]);
        $this->assertTrue($this->user->hasRole('teacher'));
        $this->assertTrue($this->user->hasRole('admin'));
        
        // Sync with new roles (should replace existing)
        $this->user->syncRoles([$studentRole]);
        
        $this->assertFalse($this->user->hasRole('teacher'));
        $this->assertFalse($this->user->hasRole('admin'));
        $this->assertTrue($this->user->hasRole('student'));
    }

    /** @test */
    public function it_can_assign_permission_to_role()
    {
        $this->role->givePermissionTo($this->permission);
        
        $this->assertTrue($this->role->hasPermissionTo('view-students'));
        $this->assertDatabaseHas('role_has_permissions', [
            'permission_id' => $this->permission->id,
            'role_id' => $this->role->id
        ]);
    }

    /** @test */
    public function it_can_assign_multiple_permissions_to_role()
    {
        $editPermission = Permission::create(['name' => 'edit-students', 'guard_name' => 'api']);
        $deletePermission = Permission::create(['name' => 'delete-students', 'guard_name' => 'api']);
        
        $this->role->givePermissionTo([$this->permission, $editPermission, $deletePermission]);
        
        $this->assertTrue($this->role->hasPermissionTo('view-students'));
        $this->assertTrue($this->role->hasPermissionTo('edit-students'));
        $this->assertTrue($this->role->hasPermissionTo('delete-students'));
        $this->assertTrue($this->role->hasAnyPermission(['view-students', 'edit-students']));
        $this->assertTrue($this->role->hasAllPermissions(['view-students', 'edit-students', 'delete-students']));
    }

    /** @test */
    public function it_can_revoke_permission_from_role()
    {
        $this->role->givePermissionTo($this->permission);
        $this->assertTrue($this->role->hasPermissionTo('view-students'));
        
        $this->role->revokePermissionTo($this->permission);
        
        $this->assertFalse($this->role->hasPermissionTo('view-students'));
        $this->assertDatabaseMissing('role_has_permissions', [
            'permission_id' => $this->permission->id,
            'role_id' => $this->role->id
        ]);
    }

    /** @test */
    public function it_can_sync_permissions_for_role()
    {
        $editPermission = Permission::create(['name' => 'edit-students', 'guard_name' => 'api']);
        $deletePermission = Permission::create(['name' => 'delete-students', 'guard_name' => 'api']);
        
        // Assign initial permissions
        $this->role->givePermissionTo([$this->permission, $editPermission]);
        $this->assertTrue($this->role->hasPermissionTo('view-students'));
        $this->assertTrue($this->role->hasPermissionTo('edit-students'));
        
        // Sync with new permissions (should replace existing)
        $this->role->syncPermissions([$deletePermission]);
        
        $this->assertFalse($this->role->hasPermissionTo('view-students'));
        $this->assertFalse($this->role->hasPermissionTo('edit-students'));
        $this->assertTrue($this->role->hasPermissionTo('delete-students'));
    }

    /** @test */
    public function it_can_assign_permission_directly_to_user()
    {
        $this->user->givePermissionTo($this->permission);
        
        $this->assertTrue($this->user->hasPermissionTo('view-students'));
        $this->assertDatabaseHas('model_has_permissions', [
            'permission_id' => $this->permission->id,
            'model_type' => User::class,
            'model_id' => $this->user->id
        ]);
    }

    /** @test */
    public function it_can_check_user_permission_via_role()
    {
        $this->role->givePermissionTo($this->permission);
        $this->user->assignRole($this->role);
        
        $this->assertTrue($this->user->hasPermissionTo('view-students'));
        $this->assertTrue($this->user->can('view-students'));
    }

    /** @test */
    public function it_can_get_all_user_permissions()
    {
        $editPermission = Permission::create(['name' => 'edit-students', 'guard_name' => 'api']);
        
        // Assign permission via role
        $this->role->givePermissionTo($this->permission);
        $this->user->assignRole($this->role);
        
        // Assign permission directly
        $this->user->givePermissionTo($editPermission);
        
        $permissions = $this->user->getAllPermissions();
        
        $this->assertInstanceOf(Collection::class, $permissions);
        $this->assertCount(2, $permissions);
        $this->assertTrue($permissions->contains('name', 'view-students'));
        $this->assertTrue($permissions->contains('name', 'edit-students'));
    }

    /** @test */
    public function it_can_get_permissions_via_roles()
    {
        $this->role->givePermissionTo($this->permission);
        $this->user->assignRole($this->role);
        
        $permissions = $this->user->getPermissionsViaRoles();
        
        $this->assertInstanceOf(Collection::class, $permissions);
        $this->assertCount(1, $permissions);
        $this->assertTrue($permissions->contains('name', 'view-students'));
    }

    /** @test */
    public function it_can_get_direct_permissions()
    {
        $editPermission = Permission::create(['name' => 'edit-students', 'guard_name' => 'api']);
        
        // Assign permission via role
        $this->role->givePermissionTo($this->permission);
        $this->user->assignRole($this->role);
        
        // Assign permission directly
        $this->user->givePermissionTo($editPermission);
        
        $directPermissions = $this->user->getDirectPermissions();
        
        $this->assertInstanceOf(Collection::class, $directPermissions);
        $this->assertCount(1, $directPermissions);
        $this->assertTrue($directPermissions->contains('name', 'edit-students'));
        $this->assertFalse($directPermissions->contains('name', 'view-students'));
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_role()
    {
        $this->expectException(RoleDoesNotExist::class);
        
        $this->user->assignRole('nonexistent-role');
    }

    /** @test */
    public function it_throws_exception_for_nonexistent_permission()
    {
        $this->expectException(PermissionDoesNotExist::class);
        
        $this->user->givePermissionTo('nonexistent-permission');
    }

    /** @test */
    public function it_can_check_role_existence()
    {
        $this->assertTrue(Role::where('name', 'teacher')->exists());
        $this->assertFalse(Role::where('name', 'nonexistent')->exists());
    }

    /** @test */
    public function it_can_check_permission_existence()
    {
        $this->assertTrue(Permission::where('name', 'view-students')->exists());
        $this->assertFalse(Permission::where('name', 'nonexistent')->exists());
    }

    /** @test */
    public function it_can_find_role_by_name()
    {
        $foundRole = Role::findByName('teacher', 'api');
        
        $this->assertInstanceOf(Role::class, $foundRole);
        $this->assertEquals($this->role->id, $foundRole->id);
        $this->assertEquals('teacher', $foundRole->name);
    }

    /** @test */
    public function it_can_find_permission_by_name()
    {
        $foundPermission = Permission::findByName('view-students', 'api');
        
        $this->assertInstanceOf(Permission::class, $foundPermission);
        $this->assertEquals($this->permission->id, $foundPermission->id);
        $this->assertEquals('view-students', $foundPermission->name);
    }

    /** @test */
    public function it_can_get_role_names_for_user()
    {
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        
        $this->user->assignRole([$this->role, $adminRole]);
        
        $roleNames = $this->user->getRoleNames();
        
        $this->assertInstanceOf(Collection::class, $roleNames);
        $this->assertCount(2, $roleNames);
        $this->assertTrue($roleNames->contains('teacher'));
        $this->assertTrue($roleNames->contains('admin'));
    }

    /** @test */
    public function it_can_get_permission_names_for_user()
    {
        $editPermission = Permission::create(['name' => 'edit-students', 'guard_name' => 'api']);
        
        $this->role->givePermissionTo($this->permission);
        $this->user->assignRole($this->role);
        $this->user->givePermissionTo($editPermission);
        
        $permissionNames = $this->user->getPermissionNames();
        
        $this->assertInstanceOf(Collection::class, $permissionNames);
        $this->assertCount(2, $permissionNames);
        $this->assertTrue($permissionNames->contains('view-students'));
        $this->assertTrue($permissionNames->contains('edit-students'));
    }

    /** @test */
    public function it_respects_guard_names()
    {
        $webRole = Role::create(['name' => 'web-admin', 'guard_name' => 'web']);
        
        // Should not be able to assign web guard role to api guard user
        $this->expectException(\Spatie\Permission\Exceptions\GuardDoesNotMatch::class);
        
        $this->user->assignRole($webRole);
    }

    /** @test */
    public function it_can_create_role_with_permissions()
    {
        $editPermission = Permission::create(['name' => 'edit-grades', 'guard_name' => 'api']);
        $deletePermission = Permission::create(['name' => 'delete-grades', 'guard_name' => 'api']);
        
        $role = Role::create(['name' => 'grade-manager', 'guard_name' => 'api']);
        $role->givePermissionTo([$this->permission, $editPermission, $deletePermission]);
        
        $this->assertTrue($role->hasPermissionTo('view-students'));
        $this->assertTrue($role->hasPermissionTo('edit-grades'));
        $this->assertTrue($role->hasPermissionTo('delete-grades'));
        $this->assertCount(3, $role->permissions);
    }

    /** @test */
    public function it_can_check_super_admin_role()
    {
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'api']);
        
        $this->user->assignRole($superAdminRole);
        
        $this->assertTrue($this->user->hasRole('super-admin'));
        
        // Super admin should have all permissions (if implemented)
        // This depends on your Gate definitions or middleware
        $this->assertTrue($this->user->hasRole('super-admin'));
    }
}