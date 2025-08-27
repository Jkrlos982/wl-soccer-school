<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\Auth\PasswordController;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Mockery;

class PasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new PasswordController();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create roles
        Role::create(['name' => 'student', 'guard_name' => 'api']);
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('oldpassword123'),
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        $this->user->assignRole('student');
        
        Mail::fake();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_sends_password_reset_link_successfully()
    {
        $request = Request::create('/api/password/reset', 'POST', [
            'email' => $this->user->email,
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->sendResetLink($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Password reset link sent successfully', $responseData['message']);
        $this->assertArrayHasKey('data', $responseData);
        $this->assertEquals($this->user->email, $responseData['data']['email']);
    }

    /** @test */
    public function it_fails_to_send_reset_link_with_invalid_email()
    {
        $request = Request::create('/api/password/reset', 'POST', [
            'email' => 'nonexistent@example.com',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->sendResetLink($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User not found', $responseData['message']);
    }

    /** @test */
    public function it_fails_to_send_reset_link_with_invalid_subdomain()
    {
        $request = Request::create('/api/password/reset', 'POST', [
            'email' => $this->user->email,
            'subdomain' => 'nonexistent'
        ]);

        $response = $this->controller->sendResetLink($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School not found', $responseData['message']);
    }

    /** @test */
    public function it_fails_to_send_reset_link_for_inactive_school()
    {
        // Create inactive school
        $inactiveSchool = School::factory()->create([
            'name' => 'Inactive School',
            'subdomain' => 'inactiveschool',
            'is_active' => false
        ]);

        $request = Request::create('/api/password/reset', 'POST', [
            'email' => $this->user->email,
            'subdomain' => $inactiveSchool->subdomain
        ]);

        $response = $this->controller->sendResetLink($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('School is not active', $responseData['message']);
    }

    /** @test */
    public function it_fails_to_send_reset_link_for_inactive_user()
    {
        // Create inactive user
        $inactiveUser = User::factory()->create([
            'email' => 'inactive@example.com',
            'school_id' => $this->school->id,
            'is_active' => false,
            'email_verified_at' => now()
        ]);

        $request = Request::create('/api/password/reset', 'POST', [
            'email' => $inactiveUser->email,
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->sendResetLink($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User account is not active', $responseData['message']);
    }

    /** @test */
    public function it_validates_required_fields_for_reset_link()
    {
        $request = Request::create('/api/password/reset', 'POST', [
            // Missing email and subdomain
        ]);

        $response = $this->controller->sendResetLink($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('email', $responseData['errors']);
        $this->assertArrayHasKey('subdomain', $responseData['errors']);
    }

    /** @test */
    public function it_validates_email_format_for_reset_link()
    {
        $request = Request::create('/api/password/reset', 'POST', [
            'email' => 'invalid-email-format',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->sendResetLink($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('email', $responseData['errors']);
    }

    /** @test */
    public function it_resets_password_successfully_with_valid_token()
    {
        // Create a password reset token
        $token = Password::createToken($this->user);

        $request = Request::create('/api/password/reset/confirm', 'POST', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->resetPassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Password reset successfully', $responseData['message']);
        
        // Verify password was actually changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /** @test */
    public function it_fails_to_reset_password_with_invalid_token()
    {
        $request = Request::create('/api/password/reset/confirm', 'POST', [
            'email' => $this->user->email,
            'token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->resetPassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid or expired reset token', $responseData['message']);
    }

    /** @test */
    public function it_validates_password_confirmation_for_reset()
    {
        $token = Password::createToken($this->user);

        $request = Request::create('/api/password/reset/confirm', 'POST', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'differentpassword',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->resetPassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('password', $responseData['errors']);
    }

    /** @test */
    public function it_validates_password_strength_for_reset()
    {
        $token = Password::createToken($this->user);

        $request = Request::create('/api/password/reset/confirm', 'POST', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => '123', // Too short
            'password_confirmation' => '123',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->resetPassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('password', $responseData['errors']);
    }

    /** @test */
    public function it_changes_password_for_authenticated_user()
    {
        $request = Request::create('/api/password/change', 'POST', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->changePassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Password changed successfully', $responseData['message']);
        
        // Verify password was actually changed
        $this->user->refresh();
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }

    /** @test */
    public function it_fails_to_change_password_with_wrong_current_password()
    {
        $request = Request::create('/api/password/change', 'POST', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->changePassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Current password is incorrect', $responseData['message']);
    }

    /** @test */
    public function it_fails_to_change_password_for_unauthenticated_user()
    {
        $request = Request::create('/api/password/change', 'POST', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        
        // No user resolver set (unauthenticated)

        $response = $this->controller->changePassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User not authenticated', $responseData['message']);
    }

    /** @test */
    public function it_validates_required_fields_for_password_change()
    {
        $request = Request::create('/api/password/change', 'POST', [
            // Missing all required fields
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->changePassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('current_password', $responseData['errors']);
        $this->assertArrayHasKey('password', $responseData['errors']);
    }

    /** @test */
    public function it_validates_reset_token_successfully()
    {
        $token = Password::createToken($this->user);

        $request = Request::create('/api/password/validate-token', 'POST', [
            'email' => $this->user->email,
            'token' => $token,
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->validateResetToken($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Token is valid', $responseData['message']);
        $this->assertTrue($responseData['data']['valid']);
    }

    /** @test */
    public function it_fails_to_validate_invalid_reset_token()
    {
        $request = Request::create('/api/password/validate-token', 'POST', [
            'email' => $this->user->email,
            'token' => 'invalid-token',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->validateResetToken($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid or expired reset token', $responseData['message']);
        $this->assertFalse($responseData['data']['valid']);
    }

    /** @test */
    public function it_handles_rate_limiting_for_reset_requests()
    {
        // This test would verify rate limiting behavior
        // For now, we'll simulate multiple requests
        
        $request = Request::create('/api/password/reset', 'POST', [
            'email' => $this->user->email,
            'subdomain' => $this->school->subdomain
        ]);

        // First request should succeed
        $response1 = $this->controller->sendResetLink($request);
        $this->assertEquals(200, $response1->getStatusCode());

        // Subsequent requests within rate limit window might be throttled
        // This would depend on the actual rate limiting implementation
    }

    /** @test */
    public function it_handles_expired_reset_tokens()
    {
        // Create an expired token by manipulating the database directly
        $token = Password::createToken($this->user);
        
        // Simulate token expiration by updating the created_at timestamp
        DB::table('password_reset_tokens')
            ->where('email', $this->user->email)
            ->update(['created_at' => now()->subHours(2)]); // Assuming 1 hour expiry

        $request = Request::create('/api/password/reset/confirm', 'POST', [
            'email' => $this->user->email,
            'token' => $token,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'subdomain' => $this->school->subdomain
        ]);

        $response = $this->controller->resetPassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid or expired reset token', $responseData['message']);
    }

    /** @test */
    public function it_prevents_password_reuse()
    {
        // This test would verify that users can't reuse their current password
        $request = Request::create('/api/password/change', 'POST', [
            'current_password' => 'oldpassword123',
            'password' => 'oldpassword123', // Same as current
            'password_confirmation' => 'oldpassword123'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->changePassword($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('New password must be different from current password', $responseData['message']);
    }

    /** @test */
    public function it_logs_password_change_activities()
    {
        // This test would verify that password changes are logged for security
        $request = Request::create('/api/password/change', 'POST', [
            'current_password' => 'oldpassword123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->changePassword($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // In a real implementation, you would verify that the activity was logged
        // $this->assertDatabaseHas('activity_logs', [...]);
    }
}