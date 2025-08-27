<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  ...$roles
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$roles)
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

        // Check if no roles are specified (allow any authenticated user)
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has any of the required roles
        $hasRole = false;
        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                $hasRole = true;
                break;
            }
        }

        if (!$hasRole) {
            Log::warning('User access denied due to insufficient role', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_roles' => $user->getRoleNames()->toArray(),
                'required_roles' => $roles,
                'ip' => $request->ip(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient permissions',
                'errors' => [
                    'authorization' => [
                        'User does not have the required role(s): ' . implode(', ', $roles)
                    ]
                ]
            ], 403);
        }

        return $next($request);
    }

    /**
     * Handle role check with OR logic (user needs at least one of the roles)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handleOr(Request $request, Closure $next, ...$roles)
    {
        return $this->handle($request, $next, ...$roles);
    }

    /**
     * Handle role check with AND logic (user needs all of the roles)
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handleAnd(Request $request, Closure $next, ...$roles)
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

        // Check if no roles are specified (allow any authenticated user)
        if (empty($roles)) {
            return $next($request);
        }

        // Check if user has ALL of the required roles
        foreach ($roles as $role) {
            if (!$user->hasRole($role)) {
                Log::warning('User access denied due to missing required role', [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'user_roles' => $user->getRoleNames()->toArray(),
                    'required_roles' => $roles,
                    'missing_role' => $role,
                    'ip' => $request->ip(),
                    'url' => $request->fullUrl()
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient permissions',
                    'errors' => [
                        'authorization' => [
                            'User must have all required roles: ' . implode(', ', $roles)
                        ]
                    ]
                ], 403);
            }
        }

        return $next($request);
    }

    /**
     * Check if user has super admin role (bypass all role checks)
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    protected function isSuperAdmin($user): bool
    {
        return $user->hasRole('super-admin') || $user->hasRole('system-admin');
    }

    /**
     * Handle role check with super admin bypass
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return mixed
     */
    public function handleWithSuperAdminBypass(Request $request, Closure $next, ...$roles)
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

        // Regular role check
        return $this->handle($request, $next, ...$roles);
    }
}