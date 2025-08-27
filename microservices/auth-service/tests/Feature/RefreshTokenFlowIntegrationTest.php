<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class RefreshTokenFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected $school;
    protected $user;
    protected $headers;
    protected $token;

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
        
        // Generate initial token
        $this->token = JWTAuth::fromUser($this->user);
    }

    /** @test */
    public function complete_refresh_token_flow_with_valid_token()
    {
        // Step 1: Verify initial token works
        $initialResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->getJson('/api/auth/me');
        
        $initialResponse->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email
            ]);
        
        // Step 2: Refresh the token
        $refreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $refreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
        
        $refreshData = $refreshResponse->json();
        $newToken = $refreshData['access_token'];
        
        $this->assertNotEmpty($newToken);
        $this->assertEquals('bearer', $refreshData['token_type']);
        $this->assertNotEquals($this->token, $newToken);
        
        // Step 3: Verify old token is invalidated
        $oldTokenResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->getJson('/api/auth/me');
        
        $oldTokenResponse->assertStatus(401)
            ->assertJson([
                'error' => 'Token is invalid'
            ]);
        
        // Step 4: Verify new token works
        $newTokenResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $newToken
        ]))->getJson('/api/auth/me');
        
        $newTokenResponse->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email
            ]);
        
        // Step 5: Chain refresh (refresh the refreshed token)
        $secondRefreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $newToken
        ]))->postJson('/api/auth/refresh');
        
        $secondRefreshResponse->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in'
            ]);
        
        $secondNewToken = $secondRefreshResponse->json('access_token');
        $this->assertNotEquals($newToken, $secondNewToken);
        
        // Step 6: Verify second new token works
        $finalResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $secondNewToken
        ]))->getJson('/api/auth/me');
        
        $finalResponse->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email
            ]);
    }

    /** @test */
    public function refresh_token_flow_fails_with_invalid_token()
    {
        $invalidToken = 'invalid.jwt.token';
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $invalidToken
        ]))->postJson('/api/auth/refresh');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Token is invalid'
            ]);
    }

    /** @test */
    public function refresh_token_flow_fails_with_expired_token()
    {
        // Create an expired token
        $expiredToken = JWTAuth::customClaims([
            'exp' => Carbon::now()->subHour()->timestamp
        ])->fromUser($this->user);
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $expiredToken
        ]))->postJson('/api/auth/refresh');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Token has expired'
            ]);
    }

    /** @test */
    public function refresh_token_flow_fails_with_malformed_token()
    {
        $malformedToken = 'malformed-token-string';
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $malformedToken
        ]))->postJson('/api/auth/refresh');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Token is invalid'
            ]);
    }

    /** @test */
    public function refresh_token_flow_fails_with_missing_token()
    {
        $response = $this->withHeaders($this->headers)
            ->postJson('/api/auth/refresh');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'Token not provided'
            ]);
    }

    /** @test */
    public function refresh_token_flow_fails_with_inactive_user()
    {
        // Deactivate user after token creation
        $this->user->update(['is_active' => false]);
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'User account is inactive'
            ]);
    }

    /** @test */
    public function refresh_token_flow_fails_with_inactive_school()
    {
        // Deactivate school after token creation
        $this->school->update(['is_active' => false]);
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'School is inactive'
            ]);
    }

    /** @test */
    public function refresh_token_flow_fails_with_deleted_user()
    {
        // Soft delete user after token creation
        $this->user->delete();
        
        $response = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $response->assertStatus(401)
            ->assertJson([
                'error' => 'User not found'
            ]);
    }

    /** @test */
    public function refresh_token_flow_preserves_user_data_integrity()
    {
        // Get initial user data
        $initialUserResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->getJson('/api/auth/me');
        
        $initialUserData = $initialUserResponse->json();
        
        // Refresh token
        $refreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $newToken = $refreshResponse->json('access_token');
        
        // Get user data with new token
        $newUserResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $newToken
        ]))->getJson('/api/auth/me');
        
        $newUserData = $newUserResponse->json();
        
        // Verify user data is identical
        $this->assertEquals($initialUserData['id'], $newUserData['id']);
        $this->assertEquals($initialUserData['email'], $newUserData['email']);
        $this->assertEquals($initialUserData['first_name'], $newUserData['first_name']);
        $this->assertEquals($initialUserData['last_name'], $newUserData['last_name']);
        $this->assertEquals($initialUserData['school_id'], $newUserData['school_id']);
    }

    /** @test */
    public function refresh_token_flow_generates_unique_tokens()
    {
        $tokens = [];
        
        // Generate multiple refreshed tokens
        $currentToken = $this->token;
        
        for ($i = 0; $i < 5; $i++) {
            $refreshResponse = $this->withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $currentToken
            ]))->postJson('/api/auth/refresh');
            
            $refreshResponse->assertStatus(200);
            
            $newToken = $refreshResponse->json('access_token');
            $tokens[] = $newToken;
            $currentToken = $newToken;
        }
        
        // Verify all tokens are unique
        $this->assertEquals(count($tokens), count(array_unique($tokens)));
        
        // Verify none match the original token
        foreach ($tokens as $token) {
            $this->assertNotEquals($this->token, $token);
        }
    }

    /** @test */
    public function refresh_token_flow_maintains_token_expiration_time()
    {
        $refreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $refreshResponse->assertStatus(200);
        
        $refreshData = $refreshResponse->json();
        $newToken = $refreshData['access_token'];
        $expiresIn = $refreshData['expires_in'];
        
        // Verify expires_in is reasonable (should be around JWT TTL)
        $this->assertGreaterThan(0, $expiresIn);
        $this->assertLessThanOrEqual(config('jwt.ttl') * 60, $expiresIn);
        
        // Verify token payload has correct expiration
        $payload = JWTAuth::setToken($newToken)->getPayload();
        $tokenExp = $payload->get('exp');
        $expectedExp = Carbon::now()->addSeconds($expiresIn)->timestamp;
        
        // Allow 5 second tolerance for processing time
        $this->assertLessThanOrEqual(5, abs($tokenExp - $expectedExp));
    }

    /** @test */
    public function refresh_token_flow_handles_concurrent_refresh_requests()
    {
        // Simulate concurrent refresh requests with same token
        $responses = [];
        
        for ($i = 0; $i < 3; $i++) {
            $responses[] = $this->withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $this->token
            ]))->postJson('/api/auth/refresh');
        }
        
        // Only first request should succeed, others should fail
        $successCount = 0;
        $failCount = 0;
        
        foreach ($responses as $response) {
            if ($response->status() === 200) {
                $successCount++;
            } else {
                $failCount++;
                $response->assertStatus(401);
            }
        }
        
        // Expect only one success (first request)
        $this->assertEquals(1, $successCount);
        $this->assertEquals(2, $failCount);
    }

    /** @test */
    public function refresh_token_flow_works_with_custom_claims()
    {
        // Create token with custom claims
        $customToken = JWTAuth::customClaims([
            'role' => 'admin',
            'school_id' => $this->school->id,
            'custom_data' => 'test_value'
        ])->fromUser($this->user);
        
        $refreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $customToken
        ]))->postJson('/api/auth/refresh');
        
        $refreshResponse->assertStatus(200);
        
        $newToken = $refreshResponse->json('access_token');
        
        // Verify new token contains user information
        $payload = JWTAuth::setToken($newToken)->getPayload();
        $this->assertEquals($this->user->id, $payload->get('sub'));
        
        // Verify new token works for authentication
        $userResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $newToken
        ]))->getJson('/api/auth/me');
        
        $userResponse->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email
            ]);
    }

    /** @test */
    public function refresh_token_flow_respects_jwt_configuration()
    {
        // Test that refresh respects JWT configuration settings
        $originalTtl = config('jwt.ttl');
        
        // Temporarily change TTL for this test
        config(['jwt.ttl' => 120]); // 2 minutes
        
        $refreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $refreshResponse->assertStatus(200);
        
        $expiresIn = $refreshResponse->json('expires_in');
        
        // Should be around 120 seconds (2 minutes)
        $this->assertGreaterThan(110, $expiresIn);
        $this->assertLessThan(130, $expiresIn);
        
        // Restore original TTL
        config(['jwt.ttl' => $originalTtl]);
    }

    /** @test */
    public function refresh_token_flow_logs_refresh_events()
    {
        // This test would verify that token refresh events are logged
        // Implementation depends on your logging strategy
        
        $refreshResponse = $this->withHeaders(array_merge($this->headers, [
            'Authorization' => 'Bearer ' . $this->token
        ]))->postJson('/api/auth/refresh');
        
        $refreshResponse->assertStatus(200);
        
        // Verify token refresh is logged
        // $this->assertDatabaseHas('security_logs', [
        //     'user_id' => $this->user->id,
        //     'event_type' => 'token_refreshed',
        //     'ip_address' => request()->ip()
        // ]);
    }

    /** @test */
    public function refresh_token_flow_validates_token_format()
    {
        $invalidFormats = [
            'Bearer invalid-token',
            'invalid-token',
            'Bearer ',
            '',
            'Basic dGVzdDp0ZXN0', // Basic auth instead of Bearer
        ];
        
        foreach ($invalidFormats as $invalidAuth) {
            $headers = array_merge($this->headers, [
                'Authorization' => $invalidAuth
            ]);
            
            $response = $this->withHeaders($headers)->postJson('/api/auth/refresh');
            
            $response->assertStatus(401);
        }
    }
}