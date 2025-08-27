<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleController extends Controller
{
    /**
     * Get all available roles
     *
     * @return JsonResponse
     */
    public function getRoles(): JsonResponse
    {
        try {
            $roles = Role::with('permissions')
                ->select('id', 'name', 'display_name', 'description')
                ->orderBy('name')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->id,
                        'name' => $role->name,
                        'display_name' => $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)),
                        'description' => $role->description,
                        'permissions_count' => $role->permissions->count(),
                        'permissions' => $role->permissions->pluck('name')
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $roles,
                'message' => 'Roles retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving roles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving roles',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Assign roles to a user
     *
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function assignRolesToUser(Request $request, int $userId): JsonResponse
    {
        try {
            $request->validate([
                'roles' => 'required|array|min:1',
                'roles.*' => 'required|string|exists:roles,name'
            ]);

            $user = User::findOrFail($userId);
            
            /** @var User $authUser */
            /** @var User $authUser */
            $authUser = auth()->user();
            // Check if user belongs to the same school (unless super admin)
            if (!$authUser->hasRole('super_admin')) {
                if ($user->school_id !== $authUser->school_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only manage users from your school'
                    ], 403);
                }
            }

            DB::beginTransaction();

            // Remove existing roles and assign new ones
            $user->syncRoles($request->roles);

            DB::commit();

            // Load user with updated roles
            $user->load('roles');

            Log::info('Roles assigned to user', [
                'user_id' => $userId,
                'roles' => $request->roles,
                'assigned_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->full_name,
                    'roles' => $user->roles->pluck('name')
                ],
                'message' => 'Roles assigned successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning roles to user: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error assigning roles',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get all available permissions
     *
     * @return JsonResponse
     */
    public function getPermissions(): JsonResponse
    {
        try {
            $permissions = Permission::select('id', 'name', 'display_name', 'module')
                ->orderBy('module')
                ->orderBy('name')
                ->get()
                ->groupBy('module')
                ->map(function ($modulePermissions, $module) {
                    return [
                        'module' => $module,
                        'permissions' => $modulePermissions->map(function ($permission) {
                            return [
                                'id' => $permission->id,
                                'name' => $permission->name,
                                'display_name' => $permission->display_name ?? ucfirst(str_replace(['_', '.'], [' ', ' '], $permission->name))
                            ];
                        })
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => array_values($permissions->toArray()),
                'message' => 'Permissions retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving permissions: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving permissions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Assign permissions to a role
     *
     * @param Request $request
     * @param int $roleId
     * @return JsonResponse
     */
    public function assignPermissionsToRole(Request $request, int $roleId): JsonResponse
    {
        try {
            $request->validate([
                'permissions' => 'required|array|min:1',
                'permissions.*' => 'required|string|exists:permissions,name'
            ]);

            $role = Role::findOrFail($roleId);
            
            /** @var User $authUser */
            $authUser = auth()->user();
            // Prevent modification of super_admin role by non-super-admins
            if ($role->name === 'super_admin' && !$authUser->hasRole('super_admin')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot modify super admin role permissions'
                ], 403);
            }

            DB::beginTransaction();

            // Sync permissions to role
            $role->syncPermissions($request->permissions);

            DB::commit();

            // Load role with updated permissions
            $role->load('permissions');

            Log::info('Permissions assigned to role', [
                'role_id' => $roleId,
                'role_name' => $role->name,
                'permissions' => $request->permissions,
                'assigned_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')
                ],
                'message' => 'Permissions assigned successfully'
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning permissions to role: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error assigning permissions',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get user roles and permissions
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getUserRoles(int $userId): JsonResponse
    {
        try {
            $user = User::with(['roles.permissions'])->findOrFail($userId);
            
            /** @var User $authUser */
            $authUser = auth()->user();
            // Check if user belongs to the same school (unless super admin)
            if (!$authUser->hasRole('super_admin')) {
                if ($user->school_id !== $authUser->school_id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You can only view users from your school'
                    ], 403);
                }
            }

            $roles = $user->roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'display_name' => $role->display_name ?? ucfirst(str_replace('_', ' ', $role->name)),
                    'permissions' => $role->permissions->pluck('name')
                ];
            });

            $allPermissions = $user->getAllPermissions()->pluck('name');

            return response()->json([
                'success' => true,
                'data' => [
                    'user_id' => $user->id,
                    'user_name' => $user->full_name,
                    'roles' => $roles,
                    'all_permissions' => $allPermissions
                ],
                'message' => 'User roles retrieved successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error retrieving user roles: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving user roles',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}