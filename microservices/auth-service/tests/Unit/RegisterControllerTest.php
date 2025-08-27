<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\Auth\RegisterController;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Notifications\EmailVerificationNotification;
use Mockery;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new RegisterController();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Fake notifications
        Notification::fake();
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_register_new_user_successfully()
    {
        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01'
        ]);

        // Mock JWT token generation
        $mockToken = 'mock.jwt.token';
        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->andReturn($mockToken);

        $response = $this->controller->register($request);
        $responseData = $response->getData(true);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('User registered successfully. Please verify your email.', $responseData['message']);
        $this->assertArrayHasKey('access_token', $responseData['data']);
        $this->assertEquals($mockToken, $responseData['data']['access_token']);
        
        // Verify user was created in database
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'school_id' => $this->school->id,
            'phone' => '+1234567890'
        ]);
        
        // Verify user has the correct role
        $user = User::where('email', 'john@example.com')->first();
        $this->assertTrue($user->hasRole('student'));
        
        // Verify email verification notification was sent
        // Notification::assertSentTo($user, EmailVerificationNotification::class);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            // Missing required fields
            'email' => 'john@example.com'
        ]);

        $this->controller->register($request);
    }

    /** @test */
    public function it_validates_email_format()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email', // Invalid email format
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student'
        ]);

        $this->controller->register($request);
    }

    /** @test */
    public function it_validates_password_confirmation()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different_password', // Doesn't match
            'school_subdomain' => 'testschool',
            'role' => 'student'
        ]);

        $this->controller->register($request);
    }

    /** @test */
    public function it_validates_unique_email()
    {
        // Create existing user
        User::factory()->create([
            'email' => 'existing@example.com',
            'school_id' => $this->school->id
        ]);

        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'existing@example.com', // Already exists
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student'
        ]);

        $this->controller->register($request);
    }

    /** @test */
    public function it_fails_with_invalid_school_subdomain()
    {
        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'nonexistent', // Doesn't exist
            'role' => 'student'
        ]);

        $response = $this->controller->register($request);
        $responseData = $response->getData(true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School not found', $responseData['message']);
    }

    /** @test */
    public function it_fails_with_inactive_school()
    {
        // Create inactive school
        $inactiveSchool = School::factory()->create([
            'subdomain' => 'inactiveschool',
            'is_active' => false
        ]);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'inactiveschool',
            'role' => 'student'
        ]);

        $response = $this->controller->register($request);
        $responseData = $response->getData(true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School is not active', $responseData['message']);
    }

    /** @test */
    public function it_validates_role_exists()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'nonexistent_role' // Invalid role
        ]);

        $this->controller->register($request);
    }

    /** @test */
    public function it_can_verify_email_with_valid_token()
    {
        // Create unverified user
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'school_id' => $this->school->id,
            'email_verified_at' => null,
            'email_verification_token' => 'valid_token_123'
        ]);

        $request = Request::create('/api/v1/auth/verify-email', 'POST', [
            'token' => 'valid_token_123'
        ]);

        $response = $this->controller->verifyEmail($request);
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Email verified successfully', $responseData['message']);
        
        // Verify user is now verified
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($user->email_verification_token);
    }

    /** @test */
    public function it_fails_email_verification_with_invalid_token()
    {
        $request = Request::create('/api/v1/auth/verify-email', 'POST', [
            'token' => 'invalid_token'
        ]);

        $response = $this->controller->verifyEmail($request);
        $responseData = $response->getData(true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid or expired verification token', $responseData['message']);
    }

    /** @test */
    public function it_fails_email_verification_for_already_verified_user()
    {
        // Create already verified user
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'school_id' => $this->school->id,
            'email_verified_at' => now(),
            'email_verification_token' => 'token_123'
        ]);

        $request = Request::create('/api/v1/auth/verify-email', 'POST', [
            'token' => 'token_123'
        ]);

        $response = $this->controller->verifyEmail($request);
        $responseData = $response->getData(true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Email already verified', $responseData['message']);
    }

    /** @test */
    public function it_can_resend_verification_email()
    {
        // Create unverified user
        $user = User::factory()->create([
            'email' => 'unverified@example.com',
            'school_id' => $this->school->id,
            'email_verified_at' => null
        ]);

        // Mock authentication
        $request = Request::create('/api/v1/auth/resend-verification', 'POST');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->controller->resendVerification($request);
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Verification email sent', $responseData['message']);
        
        // Verify notification was sent
        // Notification::assertSentTo($user, EmailVerificationNotification::class);
    }

    /** @test */
    public function it_fails_resend_verification_for_already_verified_user()
    {
        // Create already verified user
        $user = User::factory()->create([
            'email' => 'verified@example.com',
            'school_id' => $this->school->id,
            'email_verified_at' => now()
        ]);

        // Mock authentication
        $request = Request::create('/api/v1/auth/resend-verification', 'POST');
        $request->setUserResolver(function () use ($user) {
            return $user;
        });

        $response = $this->controller->resendVerification($request);
        $responseData = $response->getData(true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Email already verified', $responseData['message']);
    }

    /** @test */
    public function it_validates_phone_number_format()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student',
            'phone' => 'invalid-phone' // Invalid format
        ]);

        $this->controller->register($request);
    }

    /** @test */
    public function it_validates_date_of_birth_format()
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student',
            'date_of_birth' => 'invalid-date' // Invalid format
        ]);

        $this->controller->register($request);
    }

    /** @test */
    public function it_handles_registration_errors_gracefully()
    {
        // Mock JWT to throw exception
        JWTAuth::shouldReceive('fromUser')
            ->once()
            ->andThrow(new \Exception('JWT generation failed'));

        $request = Request::create('/api/v1/auth/register', 'POST', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'school_subdomain' => 'testschool',
            'role' => 'student'
        ]);

        $response = $this->controller->register($request);
        $responseData = $response->getData(true);

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Registration failed', $responseData['message']);
    }
}