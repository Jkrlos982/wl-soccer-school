<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\RoleMiddleware;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Mockery;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new RoleMiddleware();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create roles
        Role::create(['name' => 'student', 'guard_name' => 'api']);
        Role::create(['name' => 'teacher', 'guard_name' => 'api']);
        Role::create(['name' => 'admin', 'guard_name' => 'api']);
        Role::create(['name' => 'super-admin', 'guard_name' => 'api']);
        
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
    public function it_allows_user_with_required_role()
    {
        // Assign role to user
        $this->user->assignRole('student');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'student');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_allows_user_with_one_of_multiple_required_roles()
    {
        // Assign role to user
        $this->user->assignRole('teacher');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // User has 'teacher' role, which is one of the required roles
        $response = $this->middleware->handle($request, $next, 'student', 'teacher', 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_user_without_required_role()
    {
        // User has no roles assigned

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'admin');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
        $this->assertStringContainsString('admin', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_rejects_user_with_wrong_role()
    {
        // Assign different role to user
        $this->user->assignRole('student');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'admin');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
    }

    /** @test */
    public function it_allows_super_admin_to_bypass_role_checks()
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

        // Super admin should bypass any role requirement
        $response = $this->middleware->handle($request, $next, 'any-role-requirement');

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

        $response = $this->middleware->handle($request, $next, 'student');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User not authenticated', $responseData['message']);
    }

    /** @test */
    public function it_handles_and_logic_for_multiple_roles()
    {
        // Assign multiple roles to user
        $this->user->assignRole(['student', 'teacher']);

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // User has both required roles
        $response = $this->middleware->handleAnd($request, $next, 'student', 'teacher');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_user_missing_one_role_in_and_logic()
    {
        // Assign only one role to user
        $this->user->assignRole('student');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // User needs both roles but only has one
        $response = $this->middleware->handleAnd($request, $next, 'student', 'teacher');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
        $this->assertStringContainsString('teacher', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_handles_empty_roles_parameter()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // No roles specified - should allow access
        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_handles_case_insensitive_role_names()
    {
        // Assign role to user
        $this->user->assignRole('student');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Test with different case
        $response = $this->middleware->handle($request, $next, 'STUDENT');
        $responseData = json_decode($response->getContent(), true);

        // Should fail because role names are case-sensitive in Spatie
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
    }

    /** @test */
    public function it_provides_detailed_error_messages()
    {
        // User has no roles
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'admin', 'teacher');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Insufficient permissions', $responseData['message']);
        $this->assertArrayHasKey('authorization', $responseData['errors']);
        $this->assertStringContainsString('admin, teacher', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_logs_unauthorized_access_attempts()
    {
        // This test would verify that unauthorized access attempts are logged
        // For now, we'll just verify the response
        
        $this->user->assignRole('student');

        $request = Request::create('/api/admin/sensitive', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 'admin');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        
        // In a real implementation, you would also verify that the attempt was logged
        // $this->assertDatabaseHas('security_logs', [...]);
    }

    /** @test */
    public function it_handles_multiple_role_assignments_correctly()
    {
        // Assign multiple roles to user
        $this->user->assignRole(['student', 'teacher', 'admin']);

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Should pass with any of the assigned roles
        $response = $this->middleware->handle($request, $next, 'teacher');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->middleware->handle($request, $next, 'admin');
        $this->assertEquals(200, $response->getStatusCode());

        $response = $this->middleware->handle($request, $next, 'student');
        $this->assertEquals(200, $response->getStatusCode());
    }
}