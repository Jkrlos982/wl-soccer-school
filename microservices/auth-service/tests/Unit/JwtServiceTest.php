<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Carbon\Carbon;
use Mockery;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true
        ]);
        
        // Create a test user
        $this->user = User::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@testschool.com',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
    }

    /** @test */
    public function it_can_generate_jwt_token_for_user()
    {
        $token = JWTAuth::fromUser($this->user);
        
        $this->assertNotNull($token);
        $this->assertIsString($token);
        $this->assertStringContainsString('.', $token); // JWT format has dots
    }

    /** @test */
    public function it_can_authenticate_user_with_valid_token()
    {
        $token = JWTAuth::fromUser($this->user);
        
        // Set the token for authentication
        JWTAuth::setToken($token);
        $authenticatedUser = JWTAuth::authenticate();
        
        $this->assertNotNull($authenticatedUser);
        $this->assertEquals($this->user->id, $authenticatedUser->id);
        $this->assertEquals($this->user->email, $authenticatedUser->email);
    }

    /** @test */
    public function it_can_get_user_from_token()
    {
        $token = JWTAuth::fromUser($this->user);
        
        JWTAuth::setToken($token);
        $user = JWTAuth::user();
        
        $this->assertNotNull($user);
        $this->assertEquals($this->user->id, $user->id);
        $this->assertEquals($this->user->email, $user->email);
    }

    /** @test */
    public function it_can_parse_token_from_request()
    {
        $token = JWTAuth::fromUser($this->user);
        
        // Simulate setting token in request header
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token
        ]);
        
        $parsedToken = JWTAuth::parseToken()->getToken();
        
        $this->assertNotNull($parsedToken);
        $this->assertEquals($token, $parsedToken->get());
    }

    /** @test */
    public function it_can_refresh_jwt_token()
    {
        $originalToken = JWTAuth::fromUser($this->user);
        
        JWTAuth::setToken($originalToken);
        $refreshedToken = JWTAuth::refresh();
        
        $this->assertNotNull($refreshedToken);
        $this->assertIsString($refreshedToken);
        $this->assertNotEquals($originalToken, $refreshedToken);
    }

    /** @test */
    public function it_can_invalidate_jwt_token()
    {
        $token = JWTAuth::fromUser($this->user);
        
        JWTAuth::setToken($token);
        $result = JWTAuth::invalidate();
        
        $this->assertTrue($result);
        
        // Try to use the invalidated token
        $this->expectException(TokenInvalidException::class);
        JWTAuth::setToken($token);
        JWTAuth::authenticate();
    }

    /** @test */
    public function it_can_get_jwt_payload()
    {
        $token = JWTAuth::fromUser($this->user);
        
        JWTAuth::setToken($token);
        $payload = JWTAuth::getPayload();
        
        $this->assertNotNull($payload);
        $this->assertEquals($this->user->id, $payload->get('sub'));
        $this->assertArrayHasKey('iat', $payload->toArray());
        $this->assertArrayHasKey('exp', $payload->toArray());
    }

    /** @test */
    public function it_can_check_token_validity()
    {
        $token = JWTAuth::fromUser($this->user);
        
        JWTAuth::setToken($token);
        $isValid = JWTAuth::check();
        
        $this->assertTrue($isValid);
    }

    /** @test */
    public function it_returns_false_for_invalid_token_check()
    {
        $invalidToken = 'invalid.jwt.token';
        
        JWTAuth::setToken($invalidToken);
        $isValid = JWTAuth::check();
        
        $this->assertFalse($isValid);
    }

    /** @test */
    public function it_can_get_token_ttl()
    {
        $ttl = JWTAuth::factory()->getTTL();
        
        $this->assertIsInt($ttl);
        $this->assertGreaterThan(0, $ttl);
        $this->assertEquals(config('jwt.ttl'), $ttl);
    }

    /** @test */
    public function it_can_create_token_with_custom_claims()
    {
        $customClaims = [
            'role' => 'admin',
            'school_id' => $this->school->id
        ];
        
        $payload = JWTFactory::customClaims($customClaims)->make();
        $token = JWTAuth::encode($payload);
        
        $this->assertNotNull($token);
        
        JWTAuth::setToken($token);
        $decodedPayload = JWTAuth::getPayload();
        
        $this->assertEquals('admin', $decodedPayload->get('role'));
        $this->assertEquals($this->school->id, $decodedPayload->get('school_id'));
    }

    /** @test */
    public function it_throws_exception_for_expired_token()
    {
        // Create a token with very short TTL
        $shortTtl = 1; // 1 minute
        config(['jwt.ttl' => $shortTtl]);
        
        $token = JWTAuth::fromUser($this->user);
        
        // Simulate token expiration by manipulating time
        Carbon::setTestNow(Carbon::now()->addMinutes(2));
        
        $this->expectException(TokenExpiredException::class);
        
        JWTAuth::setToken($token);
        JWTAuth::authenticate();
    }

    /** @test */
    public function it_throws_exception_for_malformed_token()
    {
        $malformedToken = 'this.is.not.a.valid.jwt.token';
        
        $this->expectException(TokenInvalidException::class);
        
        JWTAuth::setToken($malformedToken);
        JWTAuth::authenticate();
    }

    /** @test */
    public function it_can_attempt_authentication_with_credentials()
    {
        $credentials = [
            'email' => $this->user->email,
            'password' => 'password' // Default factory password
        ];
        
        $token = JWTAuth::attempt($credentials);
        
        $this->assertNotNull($token);
        $this->assertIsString($token);
    }

    /** @test */
    public function it_returns_false_for_invalid_credentials()
    {
        $invalidCredentials = [
            'email' => $this->user->email,
            'password' => 'wrongpassword'
        ];
        
        $token = JWTAuth::attempt($invalidCredentials);
        
        $this->assertFalse($token);
    }

    /** @test */
    public function it_can_get_jwt_custom_claims_from_user()
    {
        $customClaims = $this->user->getJWTCustomClaims();
        
        $this->assertIsArray($customClaims);
        // Test that custom claims can be added to JWT
        $token = JWTAuth::fromUser($this->user);
        $this->assertNotNull($token);
    }

    /** @test */
    public function it_can_get_jwt_identifier_from_user()
    {
        $identifier = $this->user->getJWTIdentifier();
        
        $this->assertEquals($this->user->id, $identifier);
    }

    /** @test */
    public function it_handles_blacklisted_tokens()
    {
        if (!config('jwt.blacklist_enabled')) {
            $this->markTestSkipped('JWT blacklist is not enabled');
        }
        
        $token = JWTAuth::fromUser($this->user);
        
        // Invalidate the token (adds to blacklist)
        JWTAuth::setToken($token);
        JWTAuth::invalidate();
        
        // Try to use the blacklisted token
        $this->expectException(TokenInvalidException::class);
        JWTAuth::setToken($token);
        JWTAuth::authenticate();
    }

    /** @test */
    public function it_can_decode_token_without_validation()
    {
        $token = JWTAuth::fromUser($this->user);
        
        $payload = JWTAuth::setToken($token)->getPayload();
        
        $this->assertNotNull($payload);
        $this->assertEquals($this->user->id, $payload->get('sub'));
    }

    /** @test */
    public function it_handles_token_not_provided_exception()
    {
        $this->expectException(JWTException::class);
        $this->expectExceptionMessage('Token not provided');
        
        // Try to authenticate without setting a token
        JWTAuth::authenticate();
    }

    /** @test */
    public function it_can_set_and_get_token()
    {
        $token = JWTAuth::fromUser($this->user);
        
        JWTAuth::setToken($token);
        $retrievedToken = JWTAuth::getToken();
        
        $this->assertNotNull($retrievedToken);
        $this->assertEquals($token, $retrievedToken->get());
    }

    /** @test */
    public function it_can_unset_token()
    {
        $token = JWTAuth::fromUser($this->user);
        JWTAuth::setToken($token);
        
        $this->assertNotNull(JWTAuth::getToken());
        
        JWTAuth::unsetToken();
        
        $this->assertNull(JWTAuth::getToken());
    }

    protected function tearDown(): void
    {
        // Reset Carbon test time
        Carbon::setTestNow();
        
        parent::tearDown();
    }
}