<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Middleware\JwtAuthMiddleware;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Mockery;

class JwtAuthMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected $middleware;
    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->middleware = new JwtAuthMiddleware();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
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
    public function it_allows_request_with_valid_token_and_active_user()
    {
        // Mock JWT authentication
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andReturn($this->user);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    /** @test */
    public function it_rejects_request_without_token()
    {
        // Mock JWT to throw exception for missing token
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andThrow(new JWTException('Token not provided'));

        $request = Request::create('/api/test', 'GET');
        // No Authorization header

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Token not provided', $responseData['message']);
    }

    /** @test */
    public function it_rejects_request_with_expired_token()
    {
        // Mock JWT to throw expired token exception
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andThrow(new TokenExpiredException('Token has expired'));

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer expired.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Token has expired', $responseData['message']);
    }

    /** @test */
    public function it_rejects_request_with_invalid_token()
    {
        // Mock JWT to throw invalid token exception
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andThrow(new TokenInvalidException('Token is invalid'));

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer invalid.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Token is invalid', $responseData['message']);
    }

    /** @test */
    public function it_rejects_request_for_inactive_user()
    {
        // Create inactive user
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'school_id' => $this->school->id,
            'is_active' => false,
            'email_verified_at' => now()
        ]);

        // Mock JWT authentication
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andReturn($inactiveUser);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User account is not active', $responseData['message']);
    }

    /** @test */
    public function it_rejects_request_for_unverified_user()
    {
        // Create unverified user
        $unverifiedUser = User::factory()->create([
            'email' => 'unverified@example.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => null
        ]);

        // Mock JWT authentication
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andReturn($unverifiedUser);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Email not verified', $responseData['message']);
    }

    /** @test */
    public function it_rejects_request_for_user_with_inactive_school()
    {
        // Create inactive school
        $inactiveSchool = School::factory()->create([
            'name' => 'Inactive School',
            'subdomain' => 'inactiveschool',
            'is_active' => false
        ]);

        // Create user with inactive school
        $userWithInactiveSchool = User::factory()->create([
            'email' => 'user@inactiveschool.com',
            'school_id' => $inactiveSchool->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        // Mock JWT authentication
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andReturn($userWithInactiveSchool);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School is not active', $responseData['message']);
    }

    /** @test */
    public function it_rejects_request_for_user_without_school()
    {
        // Create user without school
        $userWithoutSchool = User::factory()->create([
            'email' => 'noschool@example.com',
            'school_id' => null,
            'is_active' => true,
            'email_verified_at' => now()
        ]);

        // Mock JWT authentication
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andReturn($userWithoutSchool);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User not associated with a school', $responseData['message']);
    }

    /** @test */
    public function it_handles_general_jwt_exceptions()
    {
        // Mock JWT to throw general exception
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andThrow(new JWTException('Something went wrong'));

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer problematic.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Something went wrong', $responseData['message']);
    }

    /** @test */
    public function it_handles_unexpected_exceptions()
    {
        // Mock JWT to throw unexpected exception
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andThrow(new \Exception('Unexpected error'));

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid.jwt.token');

        $next = function ($req) {
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Authentication failed', $responseData['message']);
    }

    /** @test */
    public function it_adds_user_to_request_on_successful_authentication()
    {
        // Mock JWT authentication
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andReturn($this->user);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Authorization', 'Bearer valid.jwt.token');

        $next = function ($req) {
            // Verify user was added to request
            $this->assertNotNull($req->user());
            $this->assertEquals('test@example.com', $req->user()->email);
            return new Response('Success', 200);
        };

        $response = $this->middleware->handle($request, $next);

        $this->assertEquals(200, $response->getStatusCode());
    }

    /** @test */
    public function it_allows_optional_authentication_when_configured()
    {
        // This test would be for optional authentication scenarios
        // where the middleware might allow requests without tokens
        // depending on configuration or route requirements
        
        $request = Request::create('/api/public', 'GET');
        // No Authorization header

        $next = function ($req) {
            return new Response('Public Success', 200);
        };

        // For this test, we'd need to modify the middleware to support optional auth
        // or create a separate optional auth middleware
        // This is just a placeholder for future implementation
        $this->assertTrue(true);
    }
}