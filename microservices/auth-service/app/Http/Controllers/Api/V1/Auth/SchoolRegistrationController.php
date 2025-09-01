<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\School;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SchoolRegistrationController extends Controller
{
    public function registerSchool(Request $request)
    {
        $request->validate([
            'school_name' => ['required', 'string', 'max:255', 'unique:schools,name'],
            'subdomain' => ['required', 'string', 'max:255', 'unique:schools,subdomain', 'regex:/^[a-z0-9-]+$/'],
            'school_logo' => ['nullable', 'image', 'max:2048'], // Max 2MB
            'theme_config' => ['nullable', 'array'],
            'admin_first_name' => ['required', 'string', 'max:255'],
            'admin_last_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'admin_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        DB::beginTransaction();

        try {
            // 1. Create the School
            $school = School::create([
                'name' => $request->school_name,
                'subdomain' => $request->subdomain,
                'theme_config' => $request->theme_config ?? [],
                'is_active' => true,
                'subscription_type' => 'free', // Default to free subscription
            ]);

            // Handle school logo upload
            if ($request->hasFile('school_logo')) {
                $logoPath = $request->file('school_logo')->store('logos', 'public');
                $school->logo = $logoPath;
                $school->save();
            }

            // 2. Create the School Admin User
            $adminUser = User::create([
                'school_id' => $school->id,
                'first_name' => $request->admin_first_name,
                'last_name' => $request->admin_last_name,
                'email' => $request->admin_email,
                'password' => Hash::make($request->admin_password),
                'is_active' => true,
                'email_verified_at' => now(), // Auto-verify for simplicity, can be changed
            ]);

            // 3. Assign 'school_admin' role to the user
            $schoolAdminRole = Role::where('name', 'school_admin')->first();
            if (!$schoolAdminRole) {
                throw new \Exception('Role "school_admin" not found. Please seed roles.');
            }
            $adminUser->assignRole($schoolAdminRole);

            DB::commit();

            return response()->json([
                'message' => 'School and School Admin registered successfully.',
                'school' => $school,
                'admin_user' => $adminUser,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'School registration failed.', 'error' => $e->getMessage()], 500);
        }
    }
}