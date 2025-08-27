<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\School;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;

class SimpleRolePermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--env' => 'testing']);
    }

    /** @test */
    public function it_can_create_basic_roles_and_permissions()
    {
        // Create basic permission
        $permission = Permission::create([
            'name' => 'users.view',
            'guard_name' => 'api'
        ]);
        
        $this->assertDatabaseHas('permissions', [
            'name' => 'users.view',
            'guard_name' => 'api'
        ]);

        // Create basic role
        $role = Role::create([
            'name' => 'admin',
            'guard_name' => 'api'
        ]);
        
        $this->assertDatabaseHas('roles', [
            'name' => 'admin',
            'guard_name' => 'api'
        ]);

        // Assign permission to role
        $role->givePermissionTo($permission);
        
        $this->assertTrue($role->hasPermissionTo('users.view'));
    }

    /** @test */
    public function it_can_create_school_and_user_with_role()
    {
        // Create school
        $school = School::create([
            'name' => 'Test School',
            'subdomain' => 'test',
            'is_active' => true
        ]);
        
        $this->assertDatabaseHas('schools', [
            'name' => 'Test School',
            'subdomain' => 'test'
        ]);

        // Create user with school_id
        $user = User::create([
            'school_id' => $school->id,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'is_active' => true
        ]);
        
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'school_id' => $school->id
        ]);

        // Create role and assign to user
        $role = Role::create([
            'name' => 'test_role',
            'guard_name' => 'api'
        ]);
        
        $user->assignRole($role);
        
        $this->assertTrue($user->hasRole('test_role'));
    }

    /** @test */
    public function it_verifies_database_schema()
    {
        // Check that all required tables exist
        $this->assertTrue(Schema::hasTable('schools'));
        $this->assertTrue(Schema::hasTable('users'));
        $this->assertTrue(Schema::hasTable('roles'));
        $this->assertTrue(Schema::hasTable('permissions'));
        
        // Check that users table has required columns
        $this->assertTrue(Schema::hasColumn('users', 'school_id'));
        $this->assertTrue(Schema::hasColumn('users', 'first_name'));
        $this->assertTrue(Schema::hasColumn('users', 'last_name'));
        $this->assertTrue(Schema::hasColumn('users', 'is_active'));
    }
}