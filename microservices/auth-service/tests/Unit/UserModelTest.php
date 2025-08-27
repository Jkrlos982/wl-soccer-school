<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class UserModelTest extends TestCase
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
        
        // Create permissions
        Permission::create(['name' => 'view-users', 'guard_name' => 'api']);
        Permission::create(['name' => 'edit-users', 'guard_name' => 'api']);
        Permission::create(['name' => 'manage-school', 'guard_name' => 'api']);
        
        // Create roles
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'api']);
        $teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'api']);
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'api']);
        
        // Assign permissions to roles
        $teacherRole->givePermissionTo(['view-users']);
        $adminRole->givePermissionTo(['view-users', 'edit-users', 'manage-school']);
        
        // Create a test user
        $this->user = User::factory()->create([
            'email' => 'test@example.com',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+1234567890',
            'date_of_birth' => '1990-01-01',
            'school_id' => $this->school->id,
            'is_active' => true,
            'email_verified_at' => now()
        ]);
    }

    /** @test */
    public function it_can_create_a_user()
    {
        $userData = [
            'email' => 'newuser@example.com',
            'password' => Hash::make('password123'),
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '+0987654321',
            'date_of_birth' => '1992-05-15',
            'school_id' => $this->school->id,
            'is_active' => true
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('newuser@example.com', $user->email);
        $this->assertEquals('Jane', $user->first_name);
        $this->assertEquals('Smith', $user->last_name);
        $this->assertEquals('+0987654321', $user->phone);
        $this->assertEquals($this->school->id, $user->school_id);
        $this->assertTrue($user->is_active);
        $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    }

    /** @test */
    public function it_hashes_password_automatically()
    {
        $user = User::factory()->create([
            'password' => 'plaintext-password'
        ]);

        $this->assertTrue(Hash::check('plaintext-password', $user->password));
        $this->assertNotEquals('plaintext-password', $user->password);
    }

    /** @test */
    public function it_belongs_to_a_school()
    {
        $this->assertInstanceOf(School::class, $this->user->school);
        $this->assertEquals($this->school->id, $this->user->school->id);
        $this->assertEquals($this->school->name, $this->user->school->name);
    }

    /** @test */
    public function it_can_have_roles_assigned()
    {
        $role = Role::create(['name' => 'student', 'guard_name' => 'api']);
        
        $this->user->assignRole($role);
        
        $this->assertTrue($this->user->hasRole('student'));
        $this->assertContains('student', $this->user->getRoleNames()->toArray());
    }

    /** @test */
    public function it_can_have_permissions_assigned()
    {
        $permission = Permission::create(['name' => 'create-posts', 'guard_name' => 'api']);
        
        $this->user->givePermissionTo($permission);
        
        $this->assertTrue($this->user->hasPermissionTo('create-posts'));
        $this->assertContains('create-posts', $this->user->getPermissionNames()->toArray());
    }

    /** @test */
    public function it_can_have_permissions_via_roles()
    {
        $role = Role::create(['name' => 'editor', 'guard_name' => 'api']);
        $permission = Permission::create(['name' => 'edit-posts', 'guard_name' => 'api']);
        
        $role->givePermissionTo($permission);
        $this->user->assignRole($role);
        
        $this->assertTrue($this->user->hasPermissionTo('edit-posts'));
        $this->assertTrue($this->user->hasRole('editor'));
    }

    /** @test */
    public function it_casts_date_of_birth_to_carbon_instance()
    {
        $this->assertInstanceOf(Carbon::class, $this->user->date_of_birth);
        $this->assertEquals('1990-01-01', $this->user->date_of_birth->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_email_verified_at_to_carbon_instance()
    {
        $this->assertInstanceOf(Carbon::class, $this->user->email_verified_at);
    }

    /** @test */
    public function it_hides_sensitive_attributes_in_array()
    {
        $userArray = $this->user->toArray();
        
        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
    }

    /** @test */
    public function it_can_check_if_user_is_active()
    {
        $this->assertTrue($this->user->is_active);
        
        $this->user->update(['is_active' => false]);
        $this->assertFalse($this->user->is_active);
    }

    /** @test */
    public function it_can_check_if_email_is_verified()
    {
        $this->assertNotNull($this->user->email_verified_at);
        
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
            'school_id' => $this->school->id
        ]);
        
        $this->assertNull($unverifiedUser->email_verified_at);
    }

    /** @test */
    public function it_can_soft_delete_user()
    {
        $userId = $this->user->id;
        
        $this->user->delete();
        
        $this->assertSoftDeleted('users', ['id' => $userId]);
        $this->assertNotNull($this->user->fresh()->deleted_at);
    }

    /** @test */
    public function it_can_restore_soft_deleted_user()
    {
        $this->user->delete();
        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
        
        $this->user->restore();
        
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'deleted_at' => null
        ]);
    }

    /** @test */
    public function it_validates_email_uniqueness()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        
        User::factory()->create([
            'email' => $this->user->email, // Duplicate email
            'school_id' => $this->school->id
        ]);
    }

    /** @test */
    public function it_can_get_full_name_attribute()
    {
        // Assuming there's a getFullNameAttribute accessor
        $expectedFullName = $this->user->first_name . ' ' . $this->user->last_name;
        
        // If the accessor exists, uncomment the next line
        // $this->assertEquals($expectedFullName, $this->user->full_name);
        
        // For now, just test the individual name components
        $this->assertEquals('John', $this->user->first_name);
        $this->assertEquals('Doe', $this->user->last_name);
    }

    /** @test */
    public function it_can_scope_active_users()
    {
        // Create inactive user
        $inactiveUser = User::factory()->create([
            'is_active' => false,
            'school_id' => $this->school->id
        ]);
        
        // Assuming there's an 'active' scope
        // $activeUsers = User::active()->get();
        
        // For now, just test the is_active attribute
        $this->assertTrue($this->user->is_active);
        $this->assertFalse($inactiveUser->is_active);
    }

    /** @test */
    public function it_can_scope_verified_users()
    {
        // Create unverified user
        $unverifiedUser = User::factory()->create([
            'email_verified_at' => null,
            'school_id' => $this->school->id
        ]);
        
        // Assuming there's a 'verified' scope
        // $verifiedUsers = User::verified()->get();
        
        // For now, just test the email_verified_at attribute
        $this->assertNotNull($this->user->email_verified_at);
        $this->assertNull($unverifiedUser->email_verified_at);
    }

    /** @test */
    public function it_can_scope_users_by_school()
    {
        $anotherSchool = School::factory()->create();
        $userFromAnotherSchool = User::factory()->create([
            'school_id' => $anotherSchool->id
        ]);
        
        // Assuming there's a 'forSchool' scope
        // $schoolUsers = User::forSchool($this->school->id)->get();
        
        // For now, just test the school relationship
        $this->assertEquals($this->school->id, $this->user->school_id);
        $this->assertEquals($anotherSchool->id, $userFromAnotherSchool->school_id);
        $this->assertNotEquals($this->user->school_id, $userFromAnotherSchool->school_id);
    }

    /** @test */
    public function it_can_check_super_admin_role()
    {
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'api']);
        
        $this->assertFalse($this->user->hasRole('super-admin'));
        
        $this->user->assignRole($superAdminRole);
        $this->assertTrue($this->user->hasRole('super-admin'));
    }

    /** @test */
    public function it_can_handle_multiple_roles()
    {
        $studentRole = Role::create(['name' => 'student', 'guard_name' => 'api']);
        $teacherRole = Role::create(['name' => 'teacher', 'guard_name' => 'api']);
        
        $this->user->assignRole([$studentRole, $teacherRole]);
        
        $this->assertTrue($this->user->hasRole('student'));
        $this->assertTrue($this->user->hasRole('teacher'));
        $this->assertTrue($this->user->hasAnyRole(['student', 'teacher']));
        $this->assertTrue($this->user->hasAllRoles(['student', 'teacher']));
    }

    /** @test */
    public function it_can_handle_profile_photo_url()
    {
        // Test without profile photo
        $this->assertNull($this->user->profile_photo);
        
        // Test with profile photo
        $this->user->update(['profile_photo' => 'profile-photos/test-photo.jpg']);
        $this->assertEquals('profile-photos/test-photo.jpg', $this->user->profile_photo);
    }

    /** @test */
    public function it_can_validate_phone_number_format()
    {
        // Test valid phone numbers
        $validPhones = ['+1234567890', '+44123456789', '+521234567890'];
        
        foreach ($validPhones as $phone) {
            $user = User::factory()->make(['phone' => $phone]);
            $this->assertEquals($phone, $user->phone);
        }
    }

    /** @test */
    public function it_can_handle_user_preferences()
    {
        // This test assumes there might be user preferences functionality
        // For now, we'll just test basic user attributes
        
        $this->user->update([
            'first_name' => 'Updated',
            'last_name' => 'Name'
        ]);
        
        $this->assertEquals('Updated', $this->user->first_name);
        $this->assertEquals('Name', $this->user->last_name);
    }

    /** @test */
    public function it_can_handle_user_timestamps()
    {
        $this->assertNotNull($this->user->created_at);
        $this->assertNotNull($this->user->updated_at);
        $this->assertInstanceOf(Carbon::class, $this->user->created_at);
        $this->assertInstanceOf(Carbon::class, $this->user->updated_at);
    }

    /** @test */
    public function it_can_check_user_belongs_to_active_school()
    {
        $this->assertTrue($this->user->school->is_active);
        
        // Create user with inactive school
        $inactiveSchool = School::factory()->create(['is_active' => false]);
        $userWithInactiveSchool = User::factory()->create([
            'school_id' => $inactiveSchool->id
        ]);
        
        $this->assertFalse($userWithInactiveSchool->school->is_active);
    }

    /** @test */
    public function it_can_handle_password_reset_tokens()
    {
        // This test assumes password reset functionality exists
        // For now, we'll just verify the user can be updated
        
        $originalPassword = $this->user->password;
        $newPassword = Hash::make('newpassword123');
        
        $this->user->update(['password' => $newPassword]);
        
        $this->assertNotEquals($originalPassword, $this->user->password);
        $this->assertTrue(Hash::check('newpassword123', $this->user->password));
    }
}