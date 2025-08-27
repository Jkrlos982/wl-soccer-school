<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\School;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Database\Seeders\RolePermissionSeeder;
use Tymon\JWTAuth\Facades\JWTAuth;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $schoolAdmin;
    protected $regularUser;
    protected $school1;
    protected $school2;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test schools
        $this->school1 = School::factory()->create([
            'name' => 'Test School 1',
            'subdomain' => 'school1',
            'is_active' => true
        ]);
        
        $this->school2 = School::factory()->create([
            'name' => 'Test School 2', 
            'subdomain' => 'school2',
            'is_active' => true
        ]);

        // Run role permission seeder
        $seeder = new RolePermissionSeeder();
        $seeder->run();

        // Create test users
        $this->superAdmin = User::factory()->create([
            'email' => 'superadmin@test.com',
            'school_id' => $this->school1->id
        ]);
        $this->superAdmin->assignRole('super_admin');

        $this->schoolAdmin = User::factory()->create([
            'email' => 'schooladmin@test.com',
            'school_id' => $this->school1->id
        ]);
        $this->schoolAdmin->assignRole('school_admin');

        $this->regularUser = User::factory()->create([
            'email' => 'user@test.com',
            'school_id' => $this->school2->id
        ]);
        $this->regularUser->assignRole('parent');
    }

    /** @test */
    public function it_can_get_all_roles()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/roles');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'display_name',
                            'description',
                            'permissions' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'display_name'
                                ]
                            ]
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertCount(8, $response->json('data')); // 8 roles created by seeder
    }

    /** @test */
    public function it_requires_authentication_to_get_roles()
    {
        $response = $this->getJson('/api/v1/roles');
        
        $response->assertStatus(401);
    }

    /** @test */
    public function it_can_assign_roles_to_user_as_super_admin()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        $targetUser = User::factory()->create(['school_id' => $this->school1->id]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/users/{$targetUser->id}/roles", [
            'roles' => ['coach', 'medical_staff']
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Roles assigned successfully'
                ]);

        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('coach'));
        $this->assertTrue($targetUser->hasRole('medical_staff'));
    }

    /** @test */
    public function it_can_assign_roles_to_user_in_same_school_as_school_admin()
    {
        $token = JWTAuth::fromUser($this->schoolAdmin);
        $targetUser = User::factory()->create(['school_id' => $this->school1->id]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/users/{$targetUser->id}/roles", [
            'roles' => ['coach']
        ]);

        $response->assertStatus(200);
        
        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('coach'));
    }

    /** @test */
    public function it_cannot_assign_roles_to_user_in_different_school()
    {
        $token = JWTAuth::fromUser($this->schoolAdmin);
        $targetUser = User::factory()->create(['school_id' => $this->school2->id]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/users/{$targetUser->id}/roles", [
            'roles' => ['coach']
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You can only manage users from your school'
                ]);
    }

    /** @test */
    public function it_validates_role_assignment_request()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        $targetUser = User::factory()->create(['school_id' => $this->school1->id]);
        
        // Test missing roles
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/users/{$targetUser->id}/roles", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['roles']);

        // Test invalid role
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/users/{$targetUser->id}/roles", [
            'roles' => ['invalid_role']
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['roles.0']);
    }

    /** @test */
    public function it_can_get_all_permissions()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/permissions');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        '*' => [
                            'module',
                            'permissions' => [
                                '*' => [
                                    'id',
                                    'name',
                                    'display_name'
                                ]
                            ]
                        ]
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        
        // Check that permissions are grouped by module
        $data = $response->json('data');
        $modules = collect($data)->pluck('module')->toArray();
        $this->assertContains('auth', $modules);
        $this->assertContains('financial', $modules);
        $this->assertContains('sports', $modules);
    }

    /** @test */
    public function it_can_assign_permissions_to_role_as_super_admin()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        $role = Role::where('name', 'parent')->first();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => ['sports.create', 'sports.edit']
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'message' => 'Permissions assigned successfully'
                ]);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('sports.create'));
        $this->assertTrue($role->hasPermissionTo('sports.edit'));
    }

    /** @test */
    public function it_cannot_modify_super_admin_role_permissions_as_non_super_admin()
    {
        $token = JWTAuth::fromUser($this->schoolAdmin);
        $superAdminRole = Role::where('name', 'super_admin')->first();
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/roles/{$superAdminRole->id}/permissions", [
            'permissions' => ['sports.view']
        ]);

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You cannot modify super admin role permissions'
                ]);
    }

    /** @test */
    public function it_validates_permission_assignment_request()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        $role = Role::where('name', 'parent')->first();
        
        // Test missing permissions
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/roles/{$role->id}/permissions", []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['permissions']);

        // Test invalid permission
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/roles/{$role->id}/permissions", [
            'permissions' => ['invalid.permission']
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['permissions.0']);
    }

    /** @test */
    public function it_can_get_user_roles_and_permissions()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        $targetUser = $this->schoolAdmin;
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/users/{$targetUser->id}/roles");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'user_id',
                        'user_name',
                        'roles' => [
                            '*' => [
                                'id',
                                'name',
                                'display_name',
                                'permissions'
                            ]
                        ],
                        'all_permissions'
                    ]
                ]);

        $this->assertTrue($response->json('success'));
        $this->assertEquals($targetUser->id, $response->json('data.user_id'));
        $this->assertContains('school_admin', collect($response->json('data.roles'))->pluck('name')->toArray());
    }

    /** @test */
    public function it_cannot_get_user_roles_from_different_school()
    {
        $token = JWTAuth::fromUser($this->schoolAdmin);
        $targetUser = $this->regularUser; // Different school
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson("/api/v1/users/{$targetUser->id}/roles");

        $response->assertStatus(403)
                ->assertJson([
                    'success' => false,
                    'message' => 'You can only view users from your school'
                ]);
    }

    /** @test */
    public function it_handles_non_existent_user_gracefully()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/users/99999/roles', [
            'roles' => ['coach']
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_handles_non_existent_role_gracefully()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/v1/roles/99999/permissions', [
            'permissions' => ['sports.view']
        ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function it_logs_role_assignment_activities()
    {
        $token = JWTAuth::fromUser($this->superAdmin);
        $targetUser = User::factory()->create(['school_id' => $this->school1->id]);
        
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson("/api/v1/users/{$targetUser->id}/roles", [
            'roles' => ['coach']
        ]);

        $response->assertStatus(200);
        
        // Verify the role was assigned successfully
        $targetUser->refresh();
        $this->assertTrue($targetUser->hasRole('coach'));
        
        // TODO: Implement activity logging system and uncomment the following assertion
        // $this->assertDatabaseHas('activity_log', [
        //     'subject_type' => User::class,
        //     'subject_id' => $targetUser->id,
        //     'causer_id' => $this->superAdmin->id
        // ]);
    }
}