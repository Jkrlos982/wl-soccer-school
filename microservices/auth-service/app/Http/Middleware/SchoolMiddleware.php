<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;
use App\Models\School;

class SchoolMiddleware
{
    /**
     * Handle an incoming request to verify school access.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  $schoolParam
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ?string $schoolParam = null)
    {
        // Get authenticated user
        /** @var User $user */
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
                'errors' => ['auth' => ['User must be authenticated to access this resource']]
            ], 401);
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Check if user belongs to a school
        if (!$user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'User not associated with a school',
                'errors' => [
                    'school' => ['User must be associated with a school to access this resource']
                ]
            ], 403);
        }

        // Check if user's school is active
        if (!$user->school || !$user->school->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'School is not active',
                'errors' => [
                    'school' => ['User\'s school is not active']
                ]
            ], 403);
        }

        // If specific school parameter is provided, verify access
        if ($schoolParam) {
            $schoolId = $this->getSchoolIdFromRequest($request, $schoolParam);
            
            if ($schoolId && !$this->userHasAccessToSchool($user, $schoolId)) {
                Log::warning('User attempted to access unauthorized school', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_school_id' => $user->school_id,
                    'requested_school_id' => $schoolId,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Access denied to this school',
                    'errors' => [
                        'school' => ['User does not have access to the requested school']
                    ]
                ], 403);
            }
        }

        // Add school context to request
        $request->merge([
            'user_school_id' => $user->school_id,
            'user_school' => $user->school
        ]);

        return $next($request);
    }

    /**
     * Handle request that requires user to belong to a specific school subdomain.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $subdomainParam
     * @return mixed
     */
    public function handleSubdomain(Request $request, Closure $next, string $subdomainParam = 'school_subdomain')
    {
        // Get authenticated user
        /** @var User $user */
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
                'errors' => ['auth' => ['User must be authenticated to access this resource']]
            ], 401);
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Get subdomain from request
        $subdomain = $request->input($subdomainParam) ?? $request->route($subdomainParam);
        
        if (!$subdomain) {
            return response()->json([
                'success' => false,
                'message' => 'School subdomain is required',
                'errors' => [
                    'school' => ['School subdomain parameter is missing']
                ]
            ], 400);
        }

        // Find school by subdomain
        $school = School::where('subdomain', $subdomain)
                       ->where('is_active', true)
                       ->first();

        if (!$school) {
            return response()->json([
                'success' => false,
                'message' => 'School not found or inactive',
                'errors' => [
                    'school' => ['School with the provided subdomain not found or inactive']
                ]
            ], 404);
        }

        // Check if user belongs to this school
        if ($user->school_id !== $school->id) {
            Log::warning('User attempted to access different school via subdomain', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_school_id' => $user->school_id,
                'requested_school_id' => $school->id,
                'requested_subdomain' => $subdomain,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Access denied to this school',
                'errors' => [
                    'school' => ['User does not belong to the requested school']
                ]
            ], 403);
        }

        // Add school context to request
        $request->merge([
            'validated_school' => $school,
            'user_school_id' => $user->school_id
        ]);

        return $next($request);
    }

    /**
     * Handle request that allows access to multiple schools (for admin users).
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$allowedRoles
     * @return mixed
     */
    public function handleMultiSchool(Request $request, Closure $next, ...$allowedRoles)
    {
        // Get authenticated user
        /** @var User $user */
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
                'errors' => ['auth' => ['User must be authenticated to access this resource']]
            ], 401);
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Check if user has any of the allowed roles for multi-school access
        $hasAllowedRole = false;
        if (!empty($allowedRoles)) {
            foreach ($allowedRoles as $role) {
                if ($user->hasRole($role)) {
                    $hasAllowedRole = true;
                    break;
                }
            }
        }

        if (!$hasAllowedRole && !empty($allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions for multi-school access',
                'errors' => [
                    'authorization' => [
                        'User does not have the required role for multi-school access'
                    ]
                ]
            ], 403);
        }

        // Add user context to request
        $request->merge([
            'user_school_id' => $user->school_id,
            'multi_school_access' => $hasAllowedRole
        ]);

        return $next($request);
    }

    /**
     * Check if user has super admin role.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('system-admin');
    }

    /**
     * Get school ID from request parameters.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $param
     * @return int|null
     */
    protected function getSchoolIdFromRequest(Request $request, string $param): ?int
    {
        // Try to get from route parameters first
        $schoolId = $request->route($param);
        
        // If not found, try request input
        if (!$schoolId) {
            $schoolId = $request->input($param);
        }

        return $schoolId ? (int) $schoolId : null;
    }

    /**
     * Check if user has access to a specific school.
     *
     * @param  \App\Models\User  $user
     * @param  int  $schoolId
     * @return bool
     */
    protected function userHasAccessToSchool(User $user, int $schoolId): bool
    {
        // Basic check: user belongs to the school
        if ($user->school_id === $schoolId) {
            return true;
        }

        // Additional checks for users with special roles
        // (e.g., district admin, system admin)
        if ($user->hasRole('district-admin') || $user->hasRole('system-admin')) {
            // You might want to implement additional logic here
            // to check if the user's district includes this school
            return true;
        }

        return false;
    }

    /**
     * Validate school subscription status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function validateSubscription(Request $request, Closure $next)
    {
        // Get authenticated user
        /** @var User $user */
        $user = auth('api')->user();

        if (!$user || !$user->school) {
            return response()->json([
                'success' => false,
                'message' => 'User or school not found',
                'errors' => ['auth' => ['User must be authenticated and associated with a school']]
            ], 401);
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Check if school has active subscription
        if (!$user->school->hasActiveSubscription()) {
            Log::warning('Access denied due to inactive school subscription', [
                'user_id' => $user->id,
                'school_id' => $user->school_id,
                'school_name' => $user->school->name,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'School subscription is inactive',
                'errors' => [
                    'subscription' => ['School subscription has expired or is inactive']
                ]
            ], 403);
        }

        return $next($request);
    }
}