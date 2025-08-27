<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$permissions
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$permissions)
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

        // Check if no permissions are specified (allow any authenticated user)
        if (empty($permissions)) {
            return $next($request);
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            Log::warning('User access denied due to insufficient permissions', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'required_permissions' => $permissions,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'errors' => [
                    'authorization' => [
                        'User does not have the required permission(s): ' . implode(', ', $permissions)
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }

    /**
     * Handle permission check with OR logic (user needs at least one of the permissions)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$permissions
     * @return mixed
     */
    public function handleOr(Request $request, Closure $next, ...$permissions)
    {
        return $this->handle($request, $next, ...$permissions);
    }

    /**
     * Handle permission check with AND logic (user needs all of the permissions)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$permissions
     * @return mixed
     */
    public function handleAnd(Request $request, Closure $next, ...$permissions)
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

        // Check if no permissions are specified (allow any authenticated user)
        if (empty($permissions)) {
            return $next($request);
        }

        // Super admin bypass
        if ($this->isSuperAdmin($user)) {
            return $next($request);
        }

        // Check if user has ALL of the required permissions
        foreach ($permissions as $permission) {
            if (!$user->can($permission)) {
                Log::warning('User access denied due to missing required permission', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                    'required_permissions' => $permissions,
                    'missing_permission' => $permission,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'errors' => [
                        'authorization' => [
                            'User must have all required permissions: ' . implode(', ', $permissions)
                        ]
                    ]
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check if user has super admin role (bypass all permission checks)
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    protected function isSuperAdmin(User $user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('system-admin');
    }

    /**
     * Handle permission check for specific resource
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @param  string|null  $resourceId
     * @return mixed
     */
    public function handleResource(Request $request, Closure $next, string $permission, ?string $resourceId = null)
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

        // Get resource ID from route parameter if not provided
        if (!$resourceId) {
            $resourceId = $request->route('id') ?? $request->route('user') ?? $request->route('resource');
        }

        // Check permission with resource context
        $hasPermission = false;
        if ($resourceId) {
            // Check if user owns the resource or has permission to access it
            $hasPermission = $user->can($permission) || $this->userOwnsResource($user, $resourceId);
        } else {
            // General permission check
            $hasPermission = $user->can($permission);
        }

        if (!$hasPermission) {
            Log::warning('User access denied for resource', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'permission' => $permission,
                'resource_id' => $resourceId,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions for this resource',
                'errors' => [
                    'authorization' => [
                        'User does not have permission to access this resource'
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }

    /**
     * Check if user owns the resource
     *
     * @param  \App\Models\User  $user
     * @param  string  $resourceId
     * @return bool
     */
    protected function userOwnsResource(User $user, string $resourceId): bool
    {
        // This is a basic implementation - you might want to customize this
        // based on your specific resource ownership logic
        return $user->id == $resourceId;
    }

    /**
     * Handle school-scoped permission check
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $permission
     * @return mixed
     */
    public function handleSchoolScoped(Request $request, Closure $next, string $permission)
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

        // Check if user has the permission and belongs to a school
        if (!$user->can($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'errors' => [
                    'authorization' => [
                        'User does not have the required permission: ' . $permission
                    ]
                ]
            ], 403);
        }

        if (!$user->school_id) {
            return response()->json([
                'success' => false,
                'message' => 'User not associated with a school',
                'errors' => [
                    'authorization' => [
                        'User must be associated with a school to access this resource'
                    ]
                ]
            ], 403);
        }

        // Add school context to request
        $request->merge(['user_school_id' => $user->school_id]);

        return $next($request);
    }
}