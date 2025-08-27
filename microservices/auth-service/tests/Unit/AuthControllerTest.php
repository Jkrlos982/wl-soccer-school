<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Mockery;

class AuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new AuthController();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
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
    public function it_can_login_with_valid_credentials()
    {
        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'school_subdomain' => 'testschool'
        ]);

        // Mock JWT token generation
        $mockToken = 'mock.jwt.token';
        JWTAuth::shouldReceive('attempt')
            ->once()
            ->with([
                'email' => 'test@example.com',
                'password' => 'password123'
            ])
            ->andReturn($mockToken);

        JWTAuth::shouldReceive('factory->getTTL')
            ->once()
            ->andReturn(15);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Login successful', $responseData['message']);
        $this->assertArrayHasKey('access_token', $responseData['data']);
        $this->assertEquals($mockToken, $responseData['data']['access_token']);
    }

    /** @test */
    public function it_fails_login_with_invalid_credentials()
    {
        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
            'school_subdomain' => 'testschool'
        ]);

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andReturn(false);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid credentials', $responseData['message']);
    }

    /** @test */
    public function it_fails_login_with_inactive_school()
    {
        // Create inactive school
        $inactiveSchool = School::factory()->create([
            'subdomain' => 'inactiveschool',
            'is_active' => false
        ]);

        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'school_subdomain' => 'inactiveschool'
        ]);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School is not active', $responseData['message']);
    }

    /** @test */
    public function it_fails_login_with_inactive_user()
    {
        // Create inactive user
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => false
        ]);

        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'inactive@example.com',
            'password' => 'password123',
            'school_subdomain' => 'testschool'
        ]);

        JWTAuth::shouldReceive('attempt')
            ->once()
            ->andReturn('mock.token');

        JWTAuth::shouldReceive('user')
            ->once()
            ->andReturn($inactiveUser);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User account is not active', $responseData['message']);
    }

    /** @test */
    public function it_validates_required_fields_for_login()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/login', 'POST', [
            // Missing required fields
        ]);

        $this->controller->login($request);
    }

    /** @test */
    public function it_can_register_new_user()
    {
        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student'
        ]);

        // Mock JWT token generation
        $mockToken = 'mock.jwt.token';
        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->andReturn($mockToken);

        JWTAuth::shouldReceive('factory->getTTL')
            ->once()
            ->andReturn(15);

        $response = $this->controller->register($request);
        $responseData = $response->getData(true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('User registered successfully', $responseData['message']);
        $this->assertArrayHasKey('access_token', $responseData['data']);
        
        // Verify user was created
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'school_id' => $this->school->id
        ]);
    }

    /** @test */
    public function it_fails_register_with_duplicate_email()
    {
        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'test@example.com', // Already exists
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student'
        ]);

        $this->expectException(ValidationException::class);
        $this->controller->register($request);
    }

    /** @test */
    public function it_can_get_authenticated_user_info()
    {
        // Mock authenticated user
        JWTAuth::shouldReceive('parseToken->authenticate')
            ->once()
            ->andReturn($this->user);

        $request = Request::create('/api/v1/auth/me', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->me($request);
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals($this->user->id, $responseData['data']['user']['id']);
        $this->assertEquals($this->user->email, $responseData['data']['user']['email']);
    }

    /** @test */
    public function it_can_logout_user()
    {
        // Mock JWT invalidation
        JWTAuth::shouldReceive('parseToken->invalidate')
            ->once()
            ->andReturn(true);

        $request = Request::create('/api/v1/auth/logout', 'POST');
        
        $response = $this->controller->logout($request);
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Successfully logged out', $responseData['message']);
    }

    /** @test */
    public function it_handles_logout_with_invalid_token()
    {
        // Mock JWT exception
        JWTAuth::shouldReceive('parseToken->invalidate')
            ->once()
            ->andThrow(new JWTException('Token invalid'));

        $request = Request::create('/api/v1/auth/logout', 'POST');
        
        $response = $this->controller->logout($request);
        $responseData = $response->getData(true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Failed to logout, token invalid', $responseData['message']);
    }

    /** @test */
    public function it_can_refresh_token()
    {
        $newToken = 'new.jwt.token';
        
        // Mock JWT refresh
        JWTAuth::shouldReceive('parseToken->refresh')
            ->once()
            ->andReturn($newToken);

        JWTAuth::shouldReceive('factory->getTTL')
            ->once()
            ->andReturn(15);

        $request = Request::create('/api/v1/auth/refresh', 'POST');
        
        $response = $this->controller->refresh($request);
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Token refreshed successfully', $responseData['message']);
        $this->assertEquals($newToken, $responseData['data']['access_token']);
    }

    /** @test */
    public function it_handles_refresh_with_invalid_token()
    {
        // Mock JWT exception
        JWTAuth::shouldReceive('parseToken->refresh')
            ->once()
            ->andThrow(new JWTException('Token invalid'));

        $request = Request::create('/api/v1/auth/refresh', 'POST');
        
        $response = $this->controller->refresh($request);
        $responseData = $response->getData(true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Token could not be refreshed', $responseData['message']);
    }

    /** @test */
    public function it_respects_rate_limiting_for_login()
    {
        // Mock rate limiter to simulate too many attempts
        RateLimiter::shouldReceive('tooManyAttempts')
            ->once()
            ->andReturn(true);

        RateLimiter::shouldReceive('availableIn')
            ->once()
            ->andReturn(60);

        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'school_subdomain' => 'testschool'
        ]);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertStringContainsString('Too many login attempts', $responseData['message']);
    }

    /** @test */
    public function it_validates_school_subdomain_format()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'school_subdomain' => 'invalid-subdomain!' // Invalid format
        ]);

        $this->controller->login($request);
    }

    /** @test */
    public function it_handles_nonexistent_school_subdomain()
    {
        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => 'test@example.com',
            'password' => 'password123',
            'school_subdomain' => 'nonexistent'
        ]);

        $response = $this->controller->login($request);
        $responseData = $response->getData(true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School not found', $responseData['message']);
    }
}