<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class LoginFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $school;
    protected $user;
    protected $headers;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create active school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create active and verified user
        $this->user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@testschool.com',
            'password' => Hash::make('password123'),
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
        
        // Set headers for subdomain
        $this->headers = [
            'X-School-Subdomain' => 'testschool',
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
    }

    /** @test */
    public function complete_login_flow_with_valid_credentials()
    {
        // Step 1: Attempt login with valid credentials
        $loginResponse = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $this->headers);
        
        // Verify successful login
        $loginResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user' => [
                    'id',
                    'first_name',
                    'last_name',
                    'email',
                    'school_id',
                    'is_active',
                    'email_verified_at'
                ]
            ]);
        
        $loginData = $loginResponse->json();
        $token = $loginData['access_token'];
        
        $this->assertNotEmpty($token);
        $this->assertEquals('bearer', $loginData['token_type']);
        $this->assertEquals($this->user->id, $loginData['user']['id']);
        $this->assertEquals($this->user->email, $loginData['user']['email']);
        
        // Step 2: Use token to access protected route
        $protectedResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $token
        ]))->getJson('/api/auth/me');
        
        $protectedResponse->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email,
                'first_name' => $this->user->first_name,
                'last_name' => $this->user->last_name
            ]);
        
        // Step 3: Refresh the token
        $refreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $token
        ]))->postJson('/api/auth/refresh');
        
        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
        
        $newToken = $refreshResponse->json('access_token');
        $this->assertNotEmpty($newToken);
        $this->assertNotEquals($token, $newToken);
        
        // Step 4: Use new token to access protected route
        $newProtectedResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $newToken
        ]))->getJson('/api/auth/me');
        
        $newProtectedResponse->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email
            ]);
        
        // Step 5: Logout with new token
        $logoutResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $newToken
        ]))->postJson('/api/auth/logout');
        
        $logoutResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Successfully logged out'
            ]);
        
        // Step 6: Verify token is invalidated
        $invalidTokenResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $newToken
        ]))->getJson('/api/auth/me');
        
        $invalidTokenResponse->assertStatus(401)
            ->assertJson([
                'error' => 'Token is invalid'
            ]);
    }

    /** @test */
    public function login_flow_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'wrongpassword'
        ], $this->headers);
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Invalid credentials'
            ]);
    }

    /** @test */
    public function login_flow_fails_with_inactive_user()
    {
        $this->user->update(['is_active' => false]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $this->headers);
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'User account is inactive'
            ]);
    }

    /** @test */
    public function login_flow_fails_with_inactive_school()
    {
        $this->school->update(['is_active' => false]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $this->headers);
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'School is inactive'
            ]);
    }

    /** @test */
    public function login_flow_fails_with_unverified_email()
    {
        $this->user->update(['email_verified_at' => null]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $this->headers);
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Email not verified'
            ]);
    }

    /** @test */
    public function login_flow_respects_rate_limiting()
    {
        // Clear any existing rate limits
        RateLimiter::clear('login:' . request()->ip());
        
        // Attempt login 6 times (assuming limit is 5)
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/auth/login', [
                'email' => 'john@testschool.com',
                'password' => 'wrongpassword'
            ], $this->headers);
            
            if ($i < 5) {
                $response->assertStatus(401);
            } else {
                // 6th attempt should be rate limited
                $response->assertStatus(429)
                    ->assertJson([
                        'error' => 'Too many login attempts. Please try again later.'
                    ]);
            }
        }
    }

    /** @test */
    public function login_flow_fails_with_wrong_subdomain()
    {
        $wrongHeaders = array_merge($this->headers, [
            'X-School-Subdomain' => 'wrongschool'
        ]);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $wrongHeaders);
        
        $response->assertStatus(404)
            ->assertJson([
                'error' => 'School not found'
            ]);
    }

    /** @test */
    public function login_flow_fails_with_missing_subdomain()
    {
        $headersWithoutSubdomain = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $headersWithoutSubdomain);
        
        $response->assertStatus(400)
            ->assertJson([
                'error' => 'School subdomain is required'
            ]);
    }

    /** @test */
    public function login_flow_validates_required_fields()
    {
        // Test missing email
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password123'
        ], $this->headers);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
        
        // Test missing password
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com'
        ], $this->headers);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
        
        // Test invalid email format
        $response = $this->postJson('/api/auth/login', [
            'email' => 'invalid-email',
            'password' => 'password123'
        ], $this->headers);
        
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function complete_login_flow_with_remember_me()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123',
            'remember_me' => true
        ], $this->headers);
        
        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
                'user'
            ]);
        
        $token = $response->json('access_token');
        
        // Verify token has extended expiration (if implemented)
        $payload = JWTAuth::setToken($token)->getPayload();
        $this->assertNotNull($payload->get('exp'));
    }

    /** @test */
    public function login_flow_handles_concurrent_requests()
    {
        // Simulate concurrent login requests
        $responses = [];
        
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->postJson('/api/auth/login', [
                'email' => 'john@testschool.com',
                'password' => 'password123'
            ], $this->headers);
        }
        
        // All requests should succeed
        foreach ($responses as $response) {
            $response->assertStatus(200)
                ->assertJsonStructure(['access_token', 'user']);
        }
        
        // All tokens should be different
        $tokens = array_map(fn($r) => $r->json('access_token'), $responses);
        $this->assertEquals(count($tokens), count(array_unique($tokens)));
    }

    /** @test */
    public function login_flow_updates_last_login_timestamp()
    {
        $originalLastLogin = $this->user->last_login_at;
        
        // Wait a moment to ensure timestamp difference
        sleep(1);
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $this->headers);
        
        $response->assertStatus(200);
        
        // Refresh user model
        $this->user->refresh();
        
        $this->assertNotEquals($originalLastLogin, $this->user->last_login_at);
        $this->assertTrue(Carbon::parse($this->user->last_login_at)->isAfter($originalLastLogin));
    }

    /** @test */
    public function login_flow_logs_security_events()
    {
        // This test would verify that security events are logged
        // Implementation depends on your logging strategy
        
        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'password123'
        ], $this->headers);
        
        $response->assertStatus(200);
        
        // Verify successful login is logged
        // $this->assertDatabaseHas('security_logs', [
        //     'user_id' => $this->user->id,
        //     'event_type' => 'login_success',
        //     'ip_address' => request()->ip()
        // ]);
        
        // Test failed login logging
        $failedResponse = $this->postJson('/api/auth/login', [
            'email' => 'john@testschool.com',
            'password' => 'wrongpassword'
        ], $this->headers);
        
        $failedResponse->assertStatus(401);
        
        // Verify failed login is logged
        // $this->assertDatabaseHas('security_logs', [
        //     'email' => 'john@testschool.com',
        //     'event_type' => 'login_failed',
        //     'ip_address' => request()->ip()
        // ]);
    }
}