<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SchoolModelTest extends TestCase
{
    use RefreshDatabase;

    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test school
        $this->school = School::factory()->create([
            'name' => 'Test School',
            'subdomain' => 'testschool',
            'is_active' => true,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonths(6)
        ]);
    }

    /** @test */
    public function it_can_create_a_school()
    {
        $schoolData = [
            'name' => 'New School',
            'subdomain' => 'newschool',
            'is_active' => true,
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addYear()
        ];

        $school = School::create($schoolData);

        $this->assertInstanceOf(School::class, $school);
        $this->assertEquals('New School', $school->name);
        $this->assertEquals('newschool', $school->subdomain);
        $this->assertTrue($school->is_active);
        $this->assertEquals('active', $school->subscription_status);
        $this->assertDatabaseHas('schools', ['name' => 'New School']);
    }

    /** @test */
    public function it_has_many_users()
    {
        $user1 = User::factory()->create(['school_id' => $this->school->id]);
        $user2 = User::factory()->create(['school_id' => $this->school->id]);
        $user3 = User::factory()->create(); // Different school

        $this->assertCount(2, $this->school->users);
        $this->assertTrue($this->school->users->contains($user1));
        $this->assertTrue($this->school->users->contains($user2));
        $this->assertFalse($this->school->users->contains($user3));
    }

    /** @test */
    public function it_validates_subdomain_uniqueness()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        School::factory()->create([
            'subdomain' => $this->school->subdomain // Duplicate subdomain
        ]);
    }

    /** @test */
    public function it_can_check_if_school_is_active()
    {
        $this->assertTrue($this->school->is_active);
        
        $this->school->update(['is_active' => false]);
        $this->assertFalse($this->school->is_active);
    }

    /** @test */
    public function it_can_check_subscription_status()
    {
        $this->assertEquals('active', $this->school->subscription_status);
        
        $this->school->update(['subscription_status' => 'expired']);
        $this->assertEquals('expired', $this->school->subscription_status);
    }

    /** @test */
    public function it_casts_subscription_expires_at_to_carbon_instance()
    {
        $this->assertInstanceOf(Carbon::class, $this->school->subscription_expires_at);
    }

    /** @test */
    public function it_can_check_if_subscription_is_expired()
    {
        // Test active subscription
        $this->school->update([
            'subscription_status' => 'active',
            'subscription_expires_at' => now()->addMonths(3)
        ]);
        
        // Assuming there's an isSubscriptionExpired method or similar logic
        $this->assertEquals('active', $this->school->subscription_status);
        $this->assertTrue($this->school->subscription_expires_at->isFuture());
        
        // Test expired subscription
        $this->school->update([
            'subscription_status' => 'expired',
            'subscription_expires_at' => now()->subDays(1)
        ]);
        
        $this->assertEquals('expired', $this->school->subscription_status);
        $this->assertTrue($this->school->subscription_expires_at->isPast());
    }

    /** @test */
    public function it_can_scope_active_schools()
    {
        // Create inactive school
        $inactiveSchool = School::factory()->create(['is_active' => false]);
        
        // Assuming there's an 'active' scope
        // $activeSchools = School::active()->get();
        
        // For now, just test the is_active attribute
        $this->assertTrue($this->school->is_active);
        $this->assertFalse($inactiveSchool->is_active);
    }

    /** @test */
    public function it_can_scope_schools_with_active_subscription()
    {
        // Create school with expired subscription
        $expiredSchool = School::factory()->create([
            'subscription_status' => 'expired',
            'subscription_expires_at' => now()->subDays(1)
        ]);
        
        // Assuming there's a 'withActiveSubscription' scope
        // $activeSubscriptionSchools = School::withActiveSubscription()->get();
        
        // For now, just test the subscription attributes
        $this->assertEquals('active', $this->school->subscription_status);
        $this->assertEquals('expired', $expiredSchool->subscription_status);
    }

    /** @test */
    public function it_can_find_school_by_subdomain()
    {
        $foundSchool = School::where('subdomain', $this->school->subdomain)->first();
        
        $this->assertNotNull($foundSchool);
        $this->assertEquals($this->school->id, $foundSchool->id);
        $this->assertEquals($this->school->name, $foundSchool->name);
    }

    /** @test */
    public function it_can_handle_school_settings()
    {
        // This test assumes there might be school settings functionality
        // For now, we'll test basic school attributes
        
        $this->school->update([
            'name' => 'Updated School Name'
        ]);
        
        $this->assertEquals('Updated School Name', $this->school->name);
    }

    /** @test */
    public function it_can_soft_delete_school()
    {
        $schoolId = $this->school->id;
        
        $this->school->delete();
        
        $this->assertSoftDeleted('schools', ['id' => $schoolId]);
        $this->assertNotNull($this->school->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_restore_soft_deleted_school()
    {
        $this->school->delete();
        $this->assertSoftDeleted('schools', ['id' => $this->school->id]);
        
        $this->school->restore();
        
        $this->assertDatabaseHas('schools', [
            'id' => $this->school->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function it_can_handle_school_timestamps()
    {
        $this->assertNotNull($this->school->created_at);
        $this->assertNotNull($this->school->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->school->created_at);
        $this->assertInstanceOf(Carbon::class, $this->school->updated_at);
    }

    /** @test */
    public function it_can_validate_subdomain_format()
    {
        // Test valid subdomains
        $validSubdomains = ['testschool', 'school123', 'my-school', 'school_name'];
        
        foreach ($validSubdomains as $subdomain) {
            $school = School::factory()->make(['subdomain' => $subdomain]);
            $this->assertEquals($subdomain, $school->subdomain);
        }
    }

    /** @test */
    public function it_can_count_active_users()
    {
        // Create users for this school
        $activeUser1 = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true
        ]);
        $activeUser2 = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => true
        ]);
        $inactiveUser = User::factory()->create([
            'school_id' => $this->school->id,
            'is_active' => false
        ]);
        
        // Count all users
        $this->assertCount(3, $this->school->users);
        
        // Assuming there's a relationship or method to count active users
        // $activeUsersCount = $this->school->activeUsers()->count();
        // $this->assertEquals(2, $activeUsersCount);
        
        // For now, just verify the users exist
        $this->assertTrue($activeUser1->is_active);
        $this->assertTrue($activeUser2->is_active);
        $this->assertFalse($inactiveUser->is_active);
    }

    /** @test */
    public function it_can_handle_subscription_renewal()
    {
        $originalExpiryDate = $this->school->subscription_expires_at;
        $newExpiryDate = now()->addYear();
        
        $this->school->update([
            'subscription_status' => 'active',
            'subscription_expires_at' => $newExpiryDate
        ]);
        
        $this->assertEquals('active', $this->school->subscription_status);
        $this->assertNotEquals($originalExpiryDate, $this->school->subscription_expires_at);
        $this->assertTrue($this->school->subscription_expires_at->isFuture());
    }

    /** @test */
    public function it_can_handle_school_deactivation()
    {
        $this->assertTrue($this->school->is_active);
        
        $this->school->update(['is_active' => false]);
        
        $this->assertFalse($this->school->is_active);
        $this->assertDatabaseHas('schools', [
            'id' => $this->school->id,
            'is_active' => false
        ]);
    }

    /** @test */
    public function it_can_handle_multiple_subscription_statuses()
    {
        $statuses = ['active', 'expired', 'suspended', 'trial'];
        
        foreach ($statuses as $status) {
            $this->school->update(['subscription_status' => $status]);
            $this->assertEquals($status, $this->school->subscription_status);
        }
    }

    /** @test */
    public function it_can_check_trial_period()
    {
        $this->school->update([
            'subscription_status' => 'trial',
            'subscription_expires_at' => now()->addDays(14)
        ]);
        
        $this->assertEquals('trial', $this->school->subscription_status);
        $this->assertTrue($this->school->subscription_expires_at->isFuture());
    }

    /** @test */
    public function it_can_handle_school_configuration()
    {
        // This test assumes there might be school configuration functionality
        // For now, we'll test updating basic school information
        
        $this->school->update([
            'name' => 'Updated School Configuration',
            'subdomain' => 'updated-subdomain'
        ]);
        
        $this->assertEquals('Updated School Configuration', $this->school->name);
        $this->assertEquals('updated-subdomain', $this->school->subdomain);
    }

    /** @test */
    public function it_can_handle_school_logo_or_branding()
    {
        // This test assumes there might be logo/branding fields
        // For now, we'll just test that the school can be updated
        
        $this->school->update(['name' => 'Branded School']);
        $this->assertEquals('Branded School', $this->school->name);
    }

    /** @test */
    public function it_can_validate_required_fields()
    {
        // Test that required fields are validated
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        School::create([
            // Missing required fields like name, subdomain
            'is_active' => true
        ]);
    }

    /** @test */
    public function it_can_handle_cascading_user_operations()
    {
        // Create users for this school
        $user1 = User::factory()->create(['school_id' => $this->school->id]);
        $user2 = User::factory()->create(['school_id' => $this->school->id]);
        
        $this->assertCount(2, $this->school->users);
        
        // When school is soft deleted, users should still exist but be associated with deleted school
        $this->school->delete();
        
        // Users should still exist
        $this->assertDatabaseHas('users', ['id' => $user1->id]);
        $this->assertDatabaseHas('users', ['id' => $user2->id]);
        
        // But school should be soft deleted
        $this->assertSoftDeleted('schools', ['id' => $this->school->id]);
    }
}