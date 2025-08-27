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
            Role::firstOrCreate(['name' => $roleName]);
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
            Permission::firstOrCreate(['name' => $permissionName]);
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
            $superAdmin->assignRole('super_admin');
            $superAdmin->givePermissionTo(Permission::all());

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
            $admin->assignRole('admin');
            $admin->givePermissionTo(['manage_users', 'manage_finances', 'manage_sports', 'view_dashboard', 'view_reports']);

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
            $teacher->assignRole('teacher');
            $teacher->givePermissionTo(['view_students', 'manage_calendar', 'view_dashboard']);

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
            $student->assignRole('student');
            $student->givePermissionTo(['view_dashboard', 'edit_profile']);

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
            $parent->assignRole('parent');
            $parent->givePermissionTo(['view_dashboard', 'edit_profile']);
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
            $sanJoseAdmin->assignRole('admin');
            $sanJoseAdmin->givePermissionTo(['manage_users', 'view_dashboard', 'view_reports']);
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
            $bilingueAdmin->assignRole('admin');
            $bilingueAdmin->givePermissionTo(['manage_users', 'manage_finances', 'view_dashboard', 'view_reports']);
        }
    }
}
