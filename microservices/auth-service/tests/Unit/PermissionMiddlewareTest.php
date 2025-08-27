<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\PermissionMiddleware;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Mockery;

class PermissionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new PermissionMiddleware();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create permissions
        Permission::create(['name' => 'view-users', 'guard_name' => 'api']);
        Permission::create(['name' => 'create-users', 'guard_name' => 'api']);
        Permission::create(['name' => 'edit-users', 'guard_name' => 'api']);
        Permission::create(['name' => 'delete-users', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage-school', 'guard_name' => 'api']);
        Permission::create(['name' => 'view-reports', 'guard_name' => 'api']);
        
        // Create roles
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'api']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        $teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'api']);
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'api']);
        
        // Assign permissions to roles
        $adminRole->givePermissionTo(['view-users', 'create-users', 'edit-users', 'manage-school']);
        $teacherRole->givePermissionTo(['view-users', 'view-reports']);
        $studentRole->givePermissionTo(['view-reports']);
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_user_with_required_permission()
    {
        // Give user direct permission
        $this->user->givePermissionTo('view-users');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'view-users');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_allows_user_with_permission_via_role()
    {
        // Assign role to user (role has the permission)
        $this->user->assignRole('admin');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'view-users');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_allows_user_with_one_of_multiple_required_permissions()
    {
        // Give user one of the required permissions
        $this->user->givePermissionTo('edit-users');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // User has 'edit-users' permission, which is one of the required permissions
        $response = $this->middleware->handle($request, $next, 'view-users', 'edit-users', 'delete-users');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_user_without_required_permission()
    {
        // User has no permissions assigned

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'delete-users');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
        $this->assertStringContainsString('delete-users', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_rejects_user_with_wrong_permission()
    {
        // Give user different permission
        $this->user->givePermissionTo('view-reports');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'delete-users');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
    }

    /** @test */
    public function it_allows_super_admin_to_bypass_permission_checks()
    {
        // Assign super-admin role to user
        $this->user->assignRole('super-admin');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Super admin should bypass any permission requirement
        $response = $this->middleware->handle($request, $next, 'any-permission-requirement');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_unauthenticated_user()
    {
        $request = Request::create('/api/test', 'GET');
        // No user resolver set (unauthenticated)

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'view-users');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User not authenticated', $responseData['message']);
    }

    /** @test */
    public function it_handles_and_logic_for_multiple_permissions()
    {
        // Give user multiple permissions
        $this->user->givePermissionTo(['view-users', 'edit-users']);

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // User has both required permissions
        $response = $this->middleware->handleAnd($request, $next, 'view-users', 'edit-users');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_user_missing_one_permission_in_and_logic()
    {
        // Give user only one permission
        $this->user->givePermissionTo('view-users');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // User needs both permissions but only has one
        $response = $this->middleware->handleAnd($request, $next, 'view-users', 'edit-users');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
        $this->assertStringContainsString('edit-users', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_handles_resource_specific_permissions()
    {
        // Give user permission
        $this->user->givePermissionTo('edit-users');

        $request = Request::create('/api/users/123', 'PUT');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->route()->setParameter('user', '123');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handleResource($request, $next, 'edit', 'user');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_handles_school_scoped_permissions()
    {
        // Give user permission
        $this->user->givePermissionTo('manage-school');

        $request = Request::create('/api/school/settings', 'PUT');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => $this->school->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handleSchoolScoped($request, $next, 'manage-school');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_school_scoped_permission_for_different_school()
    {
        // Create another school
        $otherSchool = School::factory()->create([
            'name' => 'Other School',
            'subdomain' => 'otherschool',
            'is_active' => true
        ]);

        // Give user permission
        $this->user->givePermissionTo('manage-school');

        $request = Request::create('/api/school/settings', 'PUT');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => $otherSchool->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handleSchoolScoped($request, $next, 'manage-school');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
        $this->assertStringContainsString('different school', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_handles_empty_permissions_parameter()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // No permissions specified - should allow access
        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_provides_detailed_error_messages()
    {
        // User has no permissions
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'delete-users', 'manage-school');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
        $this->assertArrayHasKey('authorization', $responseData['errors']);
        $this->assertStringContainsString('delete-users, manage-school', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_handles_case_sensitive_permission_names()
    {
        // Give user permission
        $this->user->givePermissionTo('view-users');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Test with different case
        $response = $this->middleware->handle($request, $next, 'VIEW-USERS');
        $responseData = json_decode($response->getContent(), true);

        // Should fail because permission names are case-sensitive in Spatie
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
    }

    /** @test */
    public function it_handles_complex_permission_combinations()
    {
        // Give user some permissions via role and some directly
        $this->user->assignRole('teacher'); // Has 'view-users' and 'view-reports'
        $this->user->givePermissionTo('create-users'); // Direct permission

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Should pass with permission from role
        $response = $this->middleware->handle($request, $next, 'view-users');
        $this->assertEquals(200, $response->getStatusCode());

        // Should pass with direct permission
        $response = $this->middleware->handle($request, $next, 'create-users');
        $this->assertEquals(200, $response->getStatusCode());

        // Should fail with permission not assigned
        $response = $this->middleware->handle($request, $next, 'delete-users');
        $this->assertEquals(403, $response->getStatusCode());
    }

    /** @test */
    public function it_logs_unauthorized_permission_access_attempts()
    {
        // This test would verify that unauthorized access attempts are logged
        // For now, we'll just verify the response
        
        $this->user->givePermissionTo('view-reports');

        $request = Request::create('/api/admin/sensitive', 'DELETE');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'delete-users');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        
        // In a real implementation, you would also verify that the attempt was logged
        // $this->assertDatabaseHas('security_logs', [...]);
    }
}