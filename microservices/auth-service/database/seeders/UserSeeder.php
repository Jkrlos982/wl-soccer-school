<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\School;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create roles if they don't exist
        $roles = ['super_admin', 'admin', 'teacher', 'student', 'parent'];
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'api']);
        }

        // Create permissions if they don't exist
        $permissions = [
            'manage_users',
            'manage_schools',
            'manage_finances',
            'manage_sports',
            'manage_medical',
            'manage_payroll',
            'manage_reports',
            'manage_calendar',
            'view_dashboard',
            'view_reports',
            'view_students',
            'edit_profile'
        ];
        
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'api']);
        }

        // Get schools
        $demoSchool = School::where('subdomain', 'demo')->first();
        $sanJoseSchool = School::where('subdomain', 'sanjose')->first();
        $bilingueSchool = School::where('subdomain', 'bilingue')->first();

        // Create users for Demo School
        if ($demoSchool) {
            // Super Admin
            $superAdmin = User::firstOrCreate(
                ['email' => 'superadmin@demo.wlschool.com'],
                [
                'school_id' => $demoSchool->id,
                'first_name' => 'Super',
                'last_name' => 'Admin',
                'email' => 'superadmin@demo.wlschool.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => '+57 300 111 1111',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subDays(1)
                ]
            );
            $superAdminRole = Role::where('name', 'super_admin')->where('guard_name', 'api')->first();
            $superAdmin->assignRole($superAdminRole);
            $superAdmin->givePermissionTo(Permission::where('guard_name', 'api')->get());

            // School Admin
            $admin = User::firstOrCreate(
                ['email' => 'admin@demo.wlschool.com'],
                [
                'school_id' => $demoSchool->id,
                'first_name' => 'María',
                'last_name' => 'González',
                'email' => 'admin@demo.wlschool.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => '+57 300 222 2222',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subHours(2)
                ]
            );
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
            $admin->assignRole($adminRole);
            $adminPermissions = Permission::whereIn('name', ['manage_users', 'manage_finances', 'manage_sports', 'view_dashboard', 'view_reports'])
                ->where('guard_name', 'api')->get();
            $admin->givePermissionTo($adminPermissions);

            // Teacher
            $teacher = User::firstOrCreate(
                ['email' => 'teacher@demo.wlschool.com'],
                [
                'school_id' => $demoSchool->id,
                'first_name' => 'Carlos',
                'last_name' => 'Rodríguez',
                'email' => 'teacher@demo.wlschool.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => '+57 300 333 3333',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subMinutes(30)
                ]
            );
            $teacherRole = Role::where('name', 'teacher')->where('guard_name', 'api')->first();
            $teacher->assignRole($teacherRole);
            $teacherPermissions = Permission::whereIn('name', ['view_students', 'manage_calendar', 'view_dashboard'])
                ->where('guard_name', 'api')->get();
            $teacher->givePermissionTo($teacherPermissions);

            // Student
            $student = User::firstOrCreate(
                ['email' => 'student@demo.wlschool.com'],
                [
                'school_id' => $demoSchool->id,
                'first_name' => 'Ana',
                'last_name' => 'Martínez',
                'email' => 'student@demo.wlschool.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => '+57 300 444 4444',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subMinutes(10)
                ]
            );
            $studentRole = Role::where('name', 'student')->where('guard_name', 'api')->first();
            $student->assignRole($studentRole);
            $studentPermissions = Permission::whereIn('name', ['view_dashboard', 'edit_profile'])
                ->where('guard_name', 'api')->get();
            $student->givePermissionTo($studentPermissions);

            // Parent
            $parent = User::firstOrCreate(
                ['email' => 'parent@demo.wlschool.com'],
                [
                'school_id' => $demoSchool->id,
                'first_name' => 'Luis',
                'last_name' => 'Pérez',
                'email' => 'parent@demo.wlschool.com',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => '+57 300 555 5555',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subHours(1)
                ]
            );
            $parentRole = Role::where('name', 'parent')->where('guard_name', 'api')->first();
            $parent->assignRole($parentRole);
            $parentPermissions = Permission::whereIn('name', ['view_dashboard', 'edit_profile'])
                ->where('guard_name', 'api')->get();
            $parent->givePermissionTo($parentPermissions);
        }

        // Create admin for San José School
        if ($sanJoseSchool) {
            $sanJoseAdmin = User::firstOrCreate(
                ['email' => 'admin@sanjose.edu.co'],
                [
                'school_id' => $sanJoseSchool->id,
                'first_name' => 'Roberto',
                'last_name' => 'Silva',
                'email' => 'admin@sanjose.edu.co',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => '+57 301 666 6666',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subDays(2)
                ]
            );
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
            $sanJoseAdmin->assignRole($adminRole);
            $sanJosePermissions = Permission::whereIn('name', ['manage_users', 'view_dashboard', 'view_reports'])
                ->where('guard_name', 'api')->get();
            $sanJoseAdmin->givePermissionTo($sanJosePermissions);
        }

        // Create admin for Bilingüe School
        if ($bilingueSchool) {
            $bilingueAdmin = User::firstOrCreate(
                ['email' => 'admin@bilingue.edu.co'],
                [
                'school_id' => $bilingueSchool->id,
                'first_name' => 'Patricia',
                'last_name' => 'Johnson',
                'email' => 'admin@bilingue.edu.co',
                'email_verified_at' => now(),
                'password' => Hash::make('password123'),
                'phone' => '+57 302 777 7777',
                'is_active' => true,
                'last_login_at' => Carbon::now()->subHours(3)
                ]
            );
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
            $bilingueAdmin->assignRole($adminRole);
            $bilinguePermissions = Permission::whereIn('name', ['manage_users', 'manage_finances', 'view_dashboard', 'view_reports'])
                ->where('guard_name', 'api')->get();
            $bilingueAdmin->givePermissionTo($bilinguePermissions);
        }
    }
}
