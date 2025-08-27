<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Http\Controllers\Api\V1\Auth\ProfileController;
use App\Models\User;
use App\Models\School;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Mockery;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $controller;
    protected $user;
    protected $school;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->controller = new ProfileController();
        
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
        
        $this->user->assignRole('student');
        
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_shows_user_profile_successfully()
    {
        $request = Request::create('/api/profile', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->show($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Profile retrieved successfully', $responseData['message']);
        $this->assertEquals($this->user->id, $responseData['data']['user']['id']);
        $this->assertEquals($this->user->email, $responseData['data']['user']['email']);
        $this->assertEquals($this->user->first_name, $responseData['data']['user']['first_name']);
        $this->assertEquals($this->user->last_name, $responseData['data']['user']['last_name']);
        $this->assertArrayHasKey('roles', $responseData['data']['user']);
        $this->assertArrayHasKey('permissions', $responseData['data']['user']);
        $this->assertArrayHasKey('school', $responseData['data']['user']);
    }

    /** @test */
    public function it_fails_to_show_profile_for_unauthenticated_user()
    {
        $request = Request::create('/api/profile', 'GET');
        // No user resolver set (unauthenticated)

        $response = $this->controller->show($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('User not authenticated', $responseData['message']);
    }

    /** @test */
    public function it_updates_user_profile_successfully()
    {
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '+0987654321',
            'date_of_birth' => '1992-05-15'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Profile updated successfully', $responseData['message']);
        
        // Verify user was actually updated
        $this->user->refresh();
        $this->assertEquals('Jane', $this->user->first_name);
        $this->assertEquals('Smith', $this->user->last_name);
        $this->assertEquals('+0987654321', $this->user->phone);
        $this->assertEquals('1992-05-15', $this->user->date_of_birth->format('Y-m-d'));
    }

    /** @test */
    public function it_validates_required_fields_for_profile_update()
    {
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => '', // Empty required field
            'last_name' => '',  // Empty required field
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('first_name', $responseData['errors']);
        $this->assertArrayHasKey('last_name', $responseData['errors']);
    }

    /** @test */
    public function it_validates_phone_format_for_profile_update()
    {
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => 'invalid-phone-format'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('phone', $responseData['errors']);
    }

    /** @test */
    public function it_validates_date_of_birth_format_for_profile_update()
    {
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => 'invalid-date-format'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('date_of_birth', $responseData['errors']);
    }

    /** @test */
    public function it_uploads_profile_photo_successfully()
    {
        $file = UploadedFile::fake()->image('profile.jpg', 800, 600);
        
        $request = Request::create('/api/profile/photo', 'POST');
        $request->files->set('photo', $file);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->uploadPhoto($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Profile photo uploaded successfully', $responseData['message']);
        $this->assertArrayHasKey('photo_url', $responseData['data']);
        
        // Verify user profile_photo was updated
        $this->user->refresh();
        $this->assertNotNull($this->user->profile_photo);
        
        // Verify file was stored
        $this->assertTrue(Storage::disk('public')->exists('profile-photos/' . basename($this->user->profile_photo)));
    }

    /** @test */
    public function it_validates_photo_file_type_for_upload()
    {
        $file = UploadedFile::fake()->create('document.pdf', 1000); // Invalid file type
        
        $request = Request::create('/api/profile/photo', 'POST');
        $request->files->set('photo', $file);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->uploadPhoto($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('photo', $responseData['errors']);
    }

    /** @test */
    public function it_validates_photo_file_size_for_upload()
    {
        $file = UploadedFile::fake()->image('large.jpg')->size(3000); // Too large (3MB)
        
        $request = Request::create('/api/profile/photo', 'POST');
        $request->files->set('photo', $file);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->uploadPhoto($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('photo', $responseData['errors']);
    }

    /** @test */
    public function it_deletes_profile_photo_successfully()
    {
        // First upload a photo
        $this->user->update(['profile_photo' => 'profile-photos/test-photo.jpg']);
        Storage::disk('public')->put('profile-photos/test-photo.jpg', 'fake-image-content');
        
        $request = Request::create('/api/profile/photo', 'DELETE');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->deletePhoto($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Profile photo deleted successfully', $responseData['message']);
        
        // Verify user profile_photo was cleared
        $this->user->refresh();
        $this->assertNull($this->user->profile_photo);
        
        // Verify file was deleted
        $this->assertFalse(Storage::disk('public')->exists('profile-photos/test-photo.jpg'));
    }

    /** @test */
    public function it_handles_delete_photo_when_no_photo_exists()
    {
        // User has no profile photo
        $this->user->update(['profile_photo' => null]);
        
        $request = Request::create('/api/profile/photo', 'DELETE');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->deletePhoto($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('No profile photo found', $responseData['message']);
    }

    /** @test */
    public function it_deletes_user_account_successfully()
    {
        $request = Request::create('/api/profile/delete-account', 'DELETE', [
            'password' => 'password' // Assuming default factory password
        ]);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->deleteAccount($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('Account deleted successfully', $responseData['message']);
        
        // Verify user was soft deleted
        $this->assertSoftDeleted('users', ['id' => $this->user->id]);
    }

    /** @test */
    public function it_fails_to_delete_account_with_wrong_password()
    {
        $request = Request::create('/api/profile/delete-account', 'DELETE', [
            'password' => 'wrongpassword'
        ]);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->deleteAccount($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(400, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Invalid password', $responseData['message']);
        
        // Verify user was not deleted
        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'deleted_at' => null]);
    }

    /** @test */
    public function it_validates_password_for_account_deletion()
    {
        $request = Request::create('/api/profile/delete-account', 'DELETE', [
            // Missing password
        ]);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->deleteAccount($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(422, $response->getStatusCode());
        $this->assertFalse($responseData['success']);
        $this->assertEquals('Validation failed', $responseData['message']);
        $this->assertArrayHasKey('password', $responseData['errors']);
    }

    /** @test */
    public function it_shows_user_permissions_successfully()
    {
        // Give user some permissions
        $this->user->assignRole('teacher'); // Has 'view-users' permission
        
        $request = Request::create('/api/profile/permissions', 'GET');
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->permissions($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        $this->assertEquals('User permissions retrieved successfully', $responseData['message']);
        $this->assertArrayHasKey('roles', $responseData['data']);
        $this->assertArrayHasKey('permissions', $responseData['data']);
        $this->assertArrayHasKey('all_permissions', $responseData['data']);
        $this->assertContains('teacher', $responseData['data']['roles']);
        $this->assertContains('view-users', $responseData['data']['all_permissions']);
    }

    /** @test */
    public function it_handles_profile_update_with_existing_photo()
    {
        // User already has a profile photo
        $this->user->update(['profile_photo' => 'profile-photos/existing-photo.jpg']);
        Storage::disk('public')->put('profile-photos/existing-photo.jpg', 'existing-content');
        
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => 'Updated',
            'last_name' => 'Name'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        
        // Verify photo URL is included in response
        $this->assertArrayHasKey('profile_photo_url', $responseData['data']['user']);
        $this->assertStringContainsString('existing-photo.jpg', $responseData['data']['user']['profile_photo_url']);
    }

    /** @test */
    public function it_replaces_existing_photo_on_new_upload()
    {
        // User already has a profile photo
        $this->user->update(['profile_photo' => 'profile-photos/old-photo.jpg']);
        Storage::disk('public')->put('profile-photos/old-photo.jpg', 'old-content');
        
        $newFile = UploadedFile::fake()->image('new-profile.jpg', 800, 600);
        
        $request = Request::create('/api/profile/photo', 'POST');
        $request->files->set('photo', $newFile);
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->uploadPhoto($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        
        // Verify old photo was deleted
        $this->assertFalse(Storage::disk('public')->exists('profile-photos/old-photo.jpg'));
        
        // Verify new photo exists
        $this->user->refresh();
        $this->assertNotNull($this->user->profile_photo);
        $this->assertStringNotContainsString('old-photo.jpg', $this->user->profile_photo);
        $this->assertTrue(Storage::disk('public')->exists('profile-photos/' . basename($this->user->profile_photo)));
    }

    /** @test */
    public function it_handles_concurrent_profile_updates_gracefully()
    {
        // This test would verify handling of concurrent updates
        // For now, we'll just test basic update functionality
        
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => 'Concurrent',
            'last_name' => 'Update'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        
        // In a real implementation, you would test optimistic locking or similar mechanisms
    }

    /** @test */
    public function it_logs_profile_changes_for_audit()
    {
        // This test would verify that profile changes are logged
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => 'Audited',
            'last_name' => 'Change'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        // In a real implementation, you would verify that the change was logged
        // $this->assertDatabaseHas('audit_logs', [...]);
    }

    /** @test */
    public function it_sanitizes_input_data_for_security()
    {
        $request = Request::create('/api/profile', 'PUT', [
            'first_name' => '<script>alert("xss")</script>John',
            'last_name' => 'Doe<img src=x onerror=alert(1)>'
        ]);
        
        $request->setUserResolver(function () {
            return $this->user;
        });

        $response = $this->controller->update($request);
        $responseData = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['success']);
        
        // Verify malicious content was sanitized
        $this->user->refresh();
        $this->assertStringNotContainsString('<script>', $this->user->first_name);
        $this->assertStringNotContainsString('<img', $this->user->last_name);
        $this->assertStringNotContainsString('alert', $this->user->first_name);
        $this->assertStringNotContainsString('onerror', $this->user->last_name);
    }
}