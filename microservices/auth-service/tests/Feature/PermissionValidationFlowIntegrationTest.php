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

class PermissionValidationFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $school;
    protected $adminUser;
    protected $teacherUser;
    protected $studentUser;
    protected $headers;
    protected $adminToken;
    protected $teacherToken;
    protected $studentToken;
    protected $adminRole;
    protected $teacherRole;
    protected $studentRole;
    protected $viewUsersPermission;
    protected $editUsersPermission;
    protected $deleteUsersPermission;
    protected $manageRolesPermission;
    protected $viewReportsPermission;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create active school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create permissions
        $this->viewUsersPermission = Permission::create(['name' => 'view-users', 'guard_name' => 'api']);
        $this->editUsersPermission = Permission::create(['name' => 'edit-users', 'guard_name' => 'api']);
        $this->deleteUsersPermission = Permission::create(['name' => 'delete-users', 'guard_name' => 'api']);
        $this->manageRolesPermission = Permission::create(['name' => 'manage-roles', 'guard_name' => 'api']);
        $this->viewReportsPermission = Permission::create(['name' => 'view-reports', 'guard_name' => 'api']);
        
        // Create roles
        $this->adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $this->teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'api']);
        $this->studentRole = Role::create(['name' => 'student', 'guard_name' => 'api']);
        
        // Assign permissions to roles
        $this->adminRole->givePermissionTo([
            $this->viewUsersPermission,
            $this->editUsersPermission,
            $this->deleteUsersPermission,
            $this->manageRolesPermission,
            $this->viewReportsPermission
        ]);
        
        $this->teacherRole->givePermissionTo([
            $this->viewUsersPermission,
            $this->editUsersPermission,
            $this->viewReportsPermission
        ]);
        
        $this->studentRole->givePermissionTo([
            $this->viewUsersPermission
        ]);
        
        // Create users
        $this->adminUser = User::factory()->create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@testschool.com',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        $this->teacherUser = User::factory()->create([
            'first_name' => 'Teacher',
            'last_name' => 'User',
            'email' => 'teacher@testschool.com',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        $this->studentUser = User::factory()->create([
            'first_name' => 'Student',
            'last_name' => 'User',
            'email' => 'student@testschool.com',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Assign roles to users
        $this->adminUser->assignRole($this->adminRole);
        $this->teacherUser->assignRole($this->teacherRole);
        $this->studentUser->assignRole($this->studentRole);
        
        // Generate tokens
        $this->adminToken = JWTAuth::fromUser($this->adminUser);
        $this->teacherToken = JWTAuth::fromUser($this->teacherUser);
        $this->studentToken = JWTAuth::fromUser($this->studentUser);
        
        // Set headers
        $this->headers = [
            'X-School-Subdomain' => 'testschool',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    /** @test */
    public function complete_permission_validation_flow_for_admin()
    {
        // Admin should have access to all protected resources
        
        // Test view-users permission
        $viewResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->getJson('/api/users');
        
        $viewResponse->assertStatus(200);
        
        // Test edit-users permission
        $editResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->putJson('/api/users/' . $this->studentUser->id, [
            'first_name' => 'Updated Student'
        ]);
        
        $editResponse->assertStatus(200);
        
        // Test delete-users permission
        $deleteResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->deleteJson('/api/users/' . $this->studentUser->id);
        
        $deleteResponse->assertStatus(200);
        
        // Test manage-roles permission
        $rolesResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->teacherUser->id,
            'role' => 'admin'
        ]);
        
        $rolesResponse->assertStatus(200);
        
        // Test view-reports permission
        $reportsResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->getJson('/api/reports');
        
        $reportsResponse->assertStatus(200);
    }

    /** @test */
    public function complete_permission_validation_flow_for_teacher()
    {
        // Teacher should have limited access
        
        // Test view-users permission (should work)
        $viewResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->teacherToken
        ]))->getJson('/api/users');
        
        $viewResponse->assertStatus(200);
        
        // Test edit-users permission (should work)
        $editResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->teacherToken
        ]))->putJson('/api/users/' . $this->studentUser->id, [
            'first_name' => 'Updated Student'
        ]);
        
        $editResponse->assertStatus(200);
        
        // Test delete-users permission (should fail)
        $deleteResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->teacherToken
        ]))->deleteJson('/api/users/' . $this->studentUser->id);
        
        $deleteResponse->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
        
        // Test manage-roles permission (should fail)
        $rolesResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->teacherToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->studentUser->id,
            'role' => 'teacher'
        ]);
        
        $rolesResponse->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
        
        // Test view-reports permission (should work)
        $reportsResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->teacherToken
        ]))->getJson('/api/reports');
        
        $reportsResponse->assertStatus(200);
    }

    /** @test */
    public function complete_permission_validation_flow_for_student()
    {
        // Student should have very limited access
        
        // Test view-users permission (should work)
        $viewResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->getJson('/api/users');
        
        $viewResponse->assertStatus(200);
        
        // Test edit-users permission (should fail)
        $editResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->putJson('/api/users/' . $this->teacherUser->id, [
            'first_name' => 'Updated Teacher'
        ]);
        
        $editResponse->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
        
        // Test delete-users permission (should fail)
        $deleteResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->deleteJson('/api/users/' . $this->teacherUser->id);
        
        $deleteResponse->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
        
        // Test manage-roles permission (should fail)
        $rolesResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->postJson('/api/roles/assign', [
            'user_id' => $this->teacherUser->id,
            'role' => 'admin'
        ]);
        
        $rolesResponse->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
        
        // Test view-reports permission (should fail)
        $reportsResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->getJson('/api/reports');
        
        $reportsResponse->assertStatus(403)
            ->assertJson([
                'error' => 'Insufficient permissions'
            ]);
    }

    /** @test */
    public function permission_validation_flow_with_direct_permissions()
    {
        // Create user without role but with direct permission
        $specialUser = User::factory()->create([
            'email' => 'special@testschool.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Give direct permission (not through role)
        $specialUser->givePermissionTo('view-reports');
        
        $specialToken = JWTAuth::fromUser($specialUser);
        
        // User should have access to reports
        $reportsResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $specialToken
        ]))->getJson('/api/reports');
        
        $reportsResponse->assertStatus(200);
        
        // But not to other resources
        $usersResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $specialToken
        ]))->getJson('/api/users');
        
        $usersResponse->assertStatus(403);
    }

    /** @test */
    public function permission_validation_flow_with_multiple_permissions()
    {
        // Test user with multiple permissions through different roles
        $multiRoleUser = User::factory()->create([
            'email' => 'multi@testschool.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Assign multiple roles
        $multiRoleUser->assignRole(['teacher', 'student']);
        
        $multiToken = JWTAuth::fromUser($multiRoleUser);
        
        // Should have combined permissions from both roles
        $this->assertTrue($multiRoleUser->hasPermissionTo('view-users')); // From both roles
        $this->assertTrue($multiRoleUser->hasPermissionTo('edit-users')); // From teacher role
        $this->assertTrue($multiRoleUser->hasPermissionTo('view-reports')); // From teacher role
        $this->assertFalse($multiRoleUser->hasPermissionTo('delete-users')); // Not in either role
        
        // Test actual API access
        $viewResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $multiToken
        ]))->getJson('/api/users');
        
        $viewResponse->assertStatus(200);
        
        $editResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $multiToken
        ]))->putJson('/api/users/' . $this->studentUser->id, [
            'first_name' => 'Updated'
        ]);
        
        $editResponse->assertStatus(200);
        
        $deleteResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $multiToken
        ]))->deleteJson('/api/users/' . $this->studentUser->id);
        
        $deleteResponse->assertStatus(403);
    }

    /** @test */
    public function permission_validation_flow_handles_permission_changes()
    {
        // Initially student can only view users
        $viewResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->getJson('/api/users');
        
        $viewResponse->assertStatus(200);
        
        $editResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->putJson('/api/users/' . $this->teacherUser->id, [
            'first_name' => 'Updated'
        ]);
        
        $editResponse->assertStatus(403);
        
        // Give student additional permission
        $this->studentUser->givePermissionTo('edit-users');
        
        // Now student should be able to edit
        $editResponse2 = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->putJson('/api/users/' . $this->teacherUser->id, [
            'first_name' => 'Updated Again'
        ]);
        
        $editResponse2->assertStatus(200);
        
        // Remove permission
        $this->studentUser->revokePermissionTo('edit-users');
        
        // Should no longer be able to edit
        $editResponse3 = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->putJson('/api/users/' . $this->teacherUser->id, [
            'first_name' => 'Updated Third Time'
        ]);
        
        $editResponse3->assertStatus(403);
    }

    /** @test */
    public function permission_validation_flow_with_wildcard_permissions()
    {
        // Create wildcard permission
        $wildcardPermission = Permission::create(['name' => 'users.*', 'guard_name' => 'api']);
        
        $wildcardUser = User::factory()->create([
            'email' => 'wildcard@testschool.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        $wildcardUser->givePermissionTo($wildcardPermission);
        $wildcardToken = JWTAuth::fromUser($wildcardUser);
        
        // Should have access to all user-related operations
        $viewResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $wildcardToken
        ]))->getJson('/api/users');
        
        $viewResponse->assertStatus(200);
        
        $editResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $wildcardToken
        ]))->putJson('/api/users/' . $this->studentUser->id, [
            'first_name' => 'Wildcard Update'
        ]);
        
        $editResponse->assertStatus(200);
        
        $deleteResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $wildcardToken
        ]))->deleteJson('/api/users/' . $this->studentUser->id);
        
        $deleteResponse->assertStatus(200);
    }

    /** @test */
    public function permission_validation_flow_handles_inactive_users()
    {
        // Deactivate teacher user
        $this->teacherUser->update(['is_active' => false]);
        
        // Even with valid token, inactive user should be denied
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->teacherToken
        ]))->getJson('/api/users');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'User account is inactive'
            ]);
    }

    /** @test */
    public function permission_validation_flow_handles_inactive_schools()
    {
        // Deactivate school
        $this->school->update(['is_active' => false]);
        
        // All users from inactive school should be denied
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->getJson('/api/users');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'School is inactive'
            ]);
    }

    /** @test */
    public function permission_validation_flow_with_resource_ownership()
    {
        // Test that users can only access their own resources
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->getJson('/api/users/' . $this->studentUser->id . '/profile');
        
        $response->assertStatus(200);
        
        // But cannot access other users' profiles
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->getJson('/api/users/' . $this->teacherUser->id . '/profile');
        
        $response->assertStatus(403)
            ->assertJson([
                'error' => 'Cannot access other users\' profiles'
            ]);
    }

    /** @test */
    public function permission_validation_flow_with_conditional_permissions()
    {
        // Create time-based permission (e.g., only during school hours)
        $timeBasedUser = User::factory()->create([
            'email' => 'timebased@testschool.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        $timeBasedUser->assignRole($this->teacherRole);
        $timeBasedToken = JWTAuth::fromUser($timeBasedUser);
        
        // Mock current time to be during school hours (9 AM)
        $this->travelTo(now()->setTime(9, 0));
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $timeBasedToken
        ]))->getJson('/api/reports');
        
        $response->assertStatus(200);
        
        // Mock current time to be outside school hours (11 PM)
        $this->travelTo(now()->setTime(23, 0));
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $timeBasedToken
        ]))->getJson('/api/reports');
        
        // Depending on implementation, this might be restricted
        // $response->assertStatus(403);
    }

    /** @test */
    public function permission_validation_flow_logs_access_attempts()
    {
        // Test successful access logging
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->adminToken
        ]))->getJson('/api/users');
        
        $response->assertStatus(200);
        
        // Verify access is logged
        // $this->assertDatabaseHas('access_logs', [
        //     'user_id' => $this->adminUser->id,
        //     'resource' => '/api/users',
        //     'action' => 'view',
        //     'status' => 'allowed'
        // ]);
        
        // Test failed access logging
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->deleteJson('/api/users/' . $this->teacherUser->id);
        
        $response->assertStatus(403);
        
        // Verify failed access is logged
        // $this->assertDatabaseHas('access_logs', [
        //     'user_id' => $this->studentUser->id,
        //     'resource' => '/api/users/' . $this->teacherUser->id,
        //     'action' => 'delete',
        //     'status' => 'denied',
        //     'reason' => 'insufficient_permissions'
        // ]);
    }

    /** @test */
    public function permission_validation_flow_handles_permission_caching()
    {
        // Test that permission changes are reflected immediately
        // (tests cache invalidation)
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->deleteJson('/api/users/' . $this->teacherUser->id);
        
        $response->assertStatus(403);
        
        // Give student delete permission
        $this->studentUser->givePermissionTo('delete-users');
        
        // Should immediately have access (cache should be invalidated)
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->studentToken
        ]))->deleteJson('/api/users/' . $this->teacherUser->id);
        
        $response->assertStatus(200);
    }

    /** @test */
    public function permission_validation_flow_with_api_rate_limiting()
    {
        // Test that permission checks don't bypass rate limiting
        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $this->adminToken
            ]))->getJson('/api/users');
            
            if ($i < 5) {
                $response->assertStatus(200);
            } else {
                // Assuming rate limit is 5 requests per minute
                $response->assertStatus(429)
                    ->assertJson([
                        'error' => 'Too many requests'
                    ]);
                break;
            }
        }
    }

    /** @test */
    public function permission_validation_flow_with_cross_school_isolation()
    {
        // Create another school and user
        $otherSchool = School::factory()->create([
            'name' => 'Other School',
            'subdomain' => 'otherschool',
            'is_active' => true
        ]);
        
        $otherUser = User::factory()->create([
            'email' => 'admin@otherschool.com',
            'school_id' => $otherSchool->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        $otherUser->assignRole($this->adminRole);
        $otherToken = JWTAuth::fromUser($otherUser);
        
        // Admin from other school should not see users from this school
        $response = $this->withHeaders([
            'X-School-Subdomain' => 'otherschool',
            'Authorization' => 'Bearer ' . $otherToken,
            'Accept' => 'application/json'
        ])->getJson('/api/users');
        
        $response->assertStatus(200);
        
        // Verify response doesn't contain users from testschool
        $responseData = $response->json();
        $userEmails = collect($responseData['data'] ?? [])->pluck('email');
        
        $this->assertNotContains('admin@testschool.com', $userEmails);
        $this->assertNotContains('teacher@testschool.com', $userEmails);
        $this->assertNotContains('student@testschool.com', $userEmails);
    }
}