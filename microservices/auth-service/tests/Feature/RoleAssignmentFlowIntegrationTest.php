<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleAssignmentFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $school;
    protected $adminUser;
    protected $regularUser;
    protected $headers;
    protected $adminToken;
    protected $userToken;
    protected $adminRole;
    protected $teacherRole;
    protected $studentRole;
    protected $viewPermission;
    protected $editPermission;
    protected $deletePermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create active school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create roles
        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'api']);
        $this->studentRole = Role::create(['name' => 'student', 'guard_name' => 'api']);
        
        // Create permissions
        $this->viewPermission = Permission::create(['name' => 'view-users', 'guard_name' => 'api']);
        $this->editPermission = Permission::create(['name' => 'edit-users', 'guard_name' => 'api']);
        $this->deletePermission = Permission::create(['name' => 'delete-users', 'guard_name' => 'api']);
        
        // Assign permissions to roles
        $this->adminRole->givePermissionTo([$this->viewPermission, $this->editPermission, $this->deletePermission]);
        $this->teacherRole->givePermissionTo([$this->viewPermission, $this->editPermission]);
        $this->studentRole->givePermissionTo([$this->viewPermission]);
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@testschool.com',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Create regular user
        $this->regularUser = User::factory()->create([
            'first_name' => 'Regular',
            'last_name' => 'User',
            'email' => 'user@testschool.com',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Assign admin role to admin user
        $this->adminUser->assignRole($this->adminRole);
        
        // Generate tokens
        $this->adminToken = JWTAuth::fromUser($this->adminUser);
        $this->userToken = JWTAuth::fromUser($this->regularUser);
        
        // Set headers
        $this->headers = [
            'X-School-Subdomain' => 'testschool',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    /** @test */
    public function complete_role_assignment_flow_by_admin()
    {
        // Step 1: Admin assigns teacher role to regular user
        $assignResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'teacher'
        ]);
        
        $assignResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Role assigned successfully',
                'user' => [
                    'id' => $this->regularUser->id,
                    'roles' => [
                        ['name' => 'teacher']
                    ]
                ]
            ]);
        
        // Step 2: Verify user has the role in database
        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->hasRole('teacher'));
        
        // Step 3: Verify user can access teacher-level resources
        $accessResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->userToken
        ]))->getJson('/api/users');
        
        // Assuming this endpoint requires view-users permission
        $accessResponse->assertStatus(200);
        
        // Step 4: Verify user cannot access admin-level resources
        $adminAccessResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->userToken
        ]))->deleteJson('/api/users/' . $this->adminUser->id);
        
        // Assuming this endpoint requires delete-users permission
        $adminAccessResponse->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
        
        // Step 5: Admin removes role from user
        $removeResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/remove', [
            'user_id' => $this->regularUser->id,
            'role' => 'teacher'
        ]);
        
        $removeResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Role removed successfully'
            ]);
        
        // Step 6: Verify user no longer has the role
        $this->regularUser->refresh();
        $this->assertFalse($this->regularUser->hasRole('teacher'));
        
        // Step 7: Verify user can no longer access teacher resources
        $noAccessResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->userToken
        ]))->getJson('/api/users');
        
        $noAccessResponse->assertStatus(403);
    }

    /** @test */
    public function role_assignment_flow_with_multiple_roles()
    {
        // Assign multiple roles
        $assignResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign-multiple', [
            'user_id' => $this->regularUser->id,
            'roles' => ['teacher', 'student']
        ]);
        
        $assignResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Roles assigned successfully'
            ]);
        
        // Verify user has both roles
        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->hasRole('teacher'));
        $this->assertTrue($this->regularUser->hasRole('student'));
        $this->assertTrue($this->regularUser->hasAnyRole(['teacher', 'student']));
        $this->assertTrue($this->regularUser->hasAllRoles(['teacher', 'student']));
        
        // Verify user has combined permissions
        $this->assertTrue($this->regularUser->hasPermissionTo('view-users'));
        $this->assertTrue($this->regularUser->hasPermissionTo('edit-users'));
        $this->assertFalse($this->regularUser->hasPermissionTo('delete-users'));
    }

    /** @test */
    public function role_assignment_flow_fails_without_admin_permission()
    {
        // Regular user tries to assign role
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->userToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->adminUser->id,
            'role' => 'admin'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
        
        // Verify no role was assigned
        $this->assertFalse($this->regularUser->hasRole('admin'));
    }

    /** @test */
    public function role_assignment_flow_validates_input_data()
    {
        // Test missing user_id
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'role' => 'teacher'
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['user_id']);
        
        // Test missing role
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id
        ]);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['role']);
        
        // Test invalid user_id
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => 99999,
            'role' => 'teacher'
        ]);
        
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'User not found'
            ]);
        
        // Test invalid role
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'nonexistent-role'
        ]);
        
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'Role not found'
            ]);
    }

    /** @test */
    public function role_assignment_flow_prevents_duplicate_assignments()
    {
        // Assign role first time
        $firstResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'teacher'
        ]);
        
        $firstResponse->assertStatus(200);
        
        // Try to assign same role again
        $secondResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'teacher'
        ]);
        
        $secondResponse->assertStatus(409)
            ->assertJson([
                'error' => 'User already has this role'
            ]);
        
        // Verify user still has only one instance of the role
        $this->regularUser->refresh();
        $this->assertEquals(1, $this->regularUser->roles()->where('name', 'teacher')->count());
    }

    /** @test */
    public function role_assignment_flow_handles_cross_school_restrictions()
    {
        // Create another school and user
        $otherSchool = School::factory()->create([
            'name' => 'Other School',
            'subdomain' => 'otherschool',
            'is_active' => true
        ]);
        
        $otherUser = User::factory()->create([
            'email' => 'user@otherschool.com',
            'school_id' => $otherSchool->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Admin from testschool tries to assign role to user from otherschool
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $otherUser->id,
            'role' => 'teacher'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Cannot assign roles to users from different schools'
            ]);
    }

    /** @test */
    public function role_assignment_flow_syncs_roles_correctly()
    {
        // Assign initial roles
        $this->regularUser->assignRole(['teacher', 'student']);
        
        // Sync to new set of roles
        $syncResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/sync', [
            'user_id' => $this->regularUser->id,
            'roles' => ['admin']
        ]);
        
        $syncResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Roles synchronized successfully'
            ]);
        
        // Verify old roles are removed and new role is assigned
        $this->regularUser->refresh();
        $this->assertFalse($this->regularUser->hasRole('teacher'));
        $this->assertFalse($this->regularUser->hasRole('student'));
        $this->assertTrue($this->regularUser->hasRole('admin'));
    }

    /** @test */
    public function role_assignment_flow_updates_permissions_immediately()
    {
        // User initially has no permissions
        $this->assertFalse($this->regularUser->hasPermissionTo('view-users'));
        
        // Assign teacher role
        $assignResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'teacher'
        ]);
        
        $assignResponse->assertStatus(200);
        
        // Refresh user and check permissions immediately
        $this->regularUser->refresh();
        $this->assertTrue($this->regularUser->hasPermissionTo('view-users'));
        $this->assertTrue($this->regularUser->hasPermissionTo('edit-users'));
        $this->assertFalse($this->regularUser->hasPermissionTo('delete-users'));
        
        // Test immediate access to protected resource
        $accessResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->userToken
        ]))->getJson('/api/users');
        
        $accessResponse->assertStatus(200);
    }

    /** @test */
    public function role_assignment_flow_handles_inactive_users()
    {
        // Deactivate user
        $this->regularUser->update(['is_active' => false]);
        
        // Try to assign role to inactive user
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'teacher'
        ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Cannot assign roles to inactive users'
            ]);
    }

    /** @test */
    public function role_assignment_flow_logs_role_changes()
    {
        // This test would verify that role changes are logged
        // Implementation depends on your logging strategy
        
        $assignResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'teacher'
        ]);
        
        $assignResponse->assertStatus(200);
        
        // Verify role assignment is logged
        // $this->assertDatabaseHas('audit_logs', [
        //     'user_id' => $this->adminUser->id,
        //     'action' => 'role_assigned',
        //     'target_user_id' => $this->regularUser->id,
        //     'details' => json_encode(['role' => 'teacher'])
        // ]);
    }

    /** @test */
    public function role_assignment_flow_handles_bulk_operations()
    {
        // Create multiple users
        $users = User::factory()->count(3)->create([
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        $userIds = $users->pluck('id')->toArray();
        
        // Bulk assign roles
        $bulkResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/bulk-assign', [
            'user_ids' => $userIds,
            'role' => 'student'
        ]);
        
        $bulkResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Roles assigned to ' . count($userIds) . ' users successfully'
            ]);
        
        // Verify all users have the role
        foreach ($users as $user) {
            $user->refresh();
            $this->assertTrue($user->hasRole('student'));
        }
    }

    /** @test */
    public function role_assignment_flow_respects_role_hierarchy()
    {
        // Create role hierarchy: super-admin > admin > teacher > student
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'api']);
        
        // Super admin should be able to assign any role
        $this->adminUser->syncRoles([$superAdminRole]);
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'admin'
        ]);
        
        $response->assertStatus(200);
        
        // Regular admin should not be able to assign super-admin role
        $this->adminUser->syncRoles([$this->adminRole]);
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'super-admin'
        ]);
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions to assign this role'
            ]);
    }

    /** @test */
    public function role_assignment_flow_handles_role_dependencies()
    {
        // Create dependent roles (e.g., department-head requires teacher)
        $deptHeadRole = Role::create(['name' => 'department-head', 'guard_name' => 'api']);
        
        // Try to assign department-head without teacher role
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'department-head'
        ]);
        
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'User must have teacher role before being assigned department-head'
            ]);
        
        // Assign teacher role first
        $this->regularUser->assignRole('teacher');
        
        // Now department-head assignment should work
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->regularUser->id,
            'role' => 'department-head'
        ]);
        
        $response->assertStatus(200);
    }
}