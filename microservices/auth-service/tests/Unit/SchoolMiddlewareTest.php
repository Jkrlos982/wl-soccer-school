<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\SchoolMiddleware;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Permission\Models\Role;
use Mockery;

class SchoolMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $user;
    protected $school;
    protected $otherSchool;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new SchoolMiddleware();
        
        // Create test schools
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonths(6)
        ]);
        
        $this->otherSchool = School::factory()->create([
            'name' => 'Other School',
            'subdomain' => 'otherschool',
            'is_active' => true,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonths(3)
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
        
        $this->user->assignRole('student');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_allows_user_from_correct_school()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => $this->school->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, $this->school->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_user_from_different_school()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => $this->otherSchool->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, $this->otherSchool->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Access denied', $responseData['message']);
        $this->assertStringContainsString('different school', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_allows_super_admin_to_access_any_school()
    {
        // Create super admin user
        $superAdmin = User::factory()->create([
            'email' => 'superadmin@example.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $superAdmin->assignRole('super-admin');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () use ($superAdmin) {
            return $superAdmin;
        });
        $request->merge(['school_id' => $this->otherSchool->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, $this->otherSchool->id);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_unauthenticated_user()
    {
        $request = Request::create('/api/test', 'GET');
        // No user resolver set (unauthenticated)
        $request->merge(['school_id' => $this->school->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, $this->school->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User not authenticated', $responseData['message']);
    }

    /** @test */
    public function it_handles_subdomain_based_school_access()
    {
        $request = Request::create('http://testschool.localhost/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handleSubdomain($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_subdomain_mismatch()
    {
        $request = Request::create('http://otherschool.localhost/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handleSubdomain($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Access denied', $responseData['message']);
        $this->assertStringContainsString('subdomain mismatch', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_handles_multi_school_access_for_authorized_roles()
    {
        // Create admin user who can access multiple schools
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $adminUser->assignRole('admin');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });
        $request->merge(['school_ids' => [$this->school->id, $this->otherSchool->id]]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handleMultiSchool($request, $next, 'admin', 'super-admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_multi_school_access_for_unauthorized_roles()
    {
        // Student trying to access multiple schools
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user; // This user has 'student' role
        });
        $request->merge(['school_ids' => [$this->school->id, $this->otherSchool->id]]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handleMultiSchool($request, $next, 'admin', 'super-admin');
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Access denied', $responseData['message']);
        $this->assertStringContainsString('multi-school access', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_validates_school_subscription_status()
    {
        // Create school with expired subscription
        $expiredSchool = School::factory()->create([
            'name' => 'Expired School',
            'subdomain' => 'expiredschool',
            'is_active' => true,
            'subscription_status' => 'expired',
            'subscription_expires_at' => now()->subDays(10)
        ]);

        $expiredUser = User::factory()->create([
            'email' => 'expired@example.com',
            'school_id' => $expiredSchool->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $expiredUser->assignRole('student');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () use ($expiredUser) {
            return $expiredUser;
        });
        $request->merge(['school_id' => $expiredSchool->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->validateSubscription($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Access denied', $responseData['message']);
        $this->assertStringContainsString('subscription', $responseData['errors']['subscription'][0]);
    }

    /** @test */
    public function it_allows_access_with_valid_subscription()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => $this->school->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->validateSubscription($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_access_to_inactive_school()
    {
        // Create inactive school
        $inactiveSchool = School::factory()->create([
            'name' => 'Inactive School',
            'subdomain' => 'inactiveschool',
            'is_active' => false,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonths(3)
        ]);

        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'school_id' => $inactiveSchool->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $inactiveUser->assignRole('student');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () use ($inactiveUser) {
            return $inactiveUser;
        });
        $request->merge(['school_id' => $inactiveSchool->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, $inactiveSchool->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Access denied', $responseData['message']);
        $this->assertStringContainsString('inactive school', $responseData['errors']['authorization'][0]);
    }

    /** @test */
    public function it_handles_missing_school_id_parameter()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        // No school_id in request

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Bad request', $responseData['message']);
        $this->assertStringContainsString('school_id', $responseData['errors']['validation'][0]);
    }

    /** @test */
    public function it_handles_invalid_school_id()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => 99999]); // Non-existent school ID

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, 99999);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School not found', $responseData['message']);
    }

    /** @test */
    public function it_extracts_school_id_from_route_parameters()
    {
        $request = Request::create('/api/schools/123/users', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        
        // Mock route parameters
        $route = Mockery::mock('Illuminate\Routing\Route');
        $route->shouldReceive('parameter')->with('school')->andReturn($this->school->id);
        $route->shouldReceive('parameter')->with('school_id')->andReturn(null);
        $request->setRouteResolver(function () use ($route) {
            return $route;
        });

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_logs_unauthorized_school_access_attempts()
    {
        // This test would verify that unauthorized access attempts are logged
        // For now, we'll just verify the response
        
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => $this->otherSchool->id]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, $this->otherSchool->id);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        
        // In a real implementation, you would also verify that the attempt was logged
        // $this->assertDatabaseHas('security_logs', [...]);
    }

    /** @test */
    public function it_handles_multiple_school_ids_in_request()
    {
        // Create admin user who can access multiple schools
        $adminUser = User::factory()->create([
            'email' => 'admin@example.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        $adminUser->assignRole('admin');

        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });
        $request->merge(['school_ids' => [$this->school->id, $this->otherSchool->id]]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        // Should validate that user has access to all requested schools
        $response = $this->middleware->handleMultiSchool($request, $next, 'admin');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_handles_edge_case_with_null_school_id()
    {
        $request = Request::create('/api/test', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });
        $request->merge(['school_id' => null]);

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next, null);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Bad request', $responseData['message']);
    }
}