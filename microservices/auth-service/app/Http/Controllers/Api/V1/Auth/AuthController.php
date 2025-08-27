<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Carbon\Carbon;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'refresh']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request): JsonResponse
    {
        // Rate limiting
        $key = 'login-attempts:' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            return response()->json([
                'success' => false,
                'message' => 'Too many login attempts. Please try again in ' . $seconds . ' seconds.',
                'errors' => ['rate_limit' => ['Too many attempts']]
            ], 429);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
            'school_subdomain' => 'sometimes|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('email', 'password');
        
        try {
            // Find user and validate school if provided
            $user = User::where('email', $credentials['email'])->first();
            
            if (!$user) {
                RateLimiter::hit($key, 300); // 5 minutes
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'errors' => ['auth' => ['Invalid email or password']]
                ], 401);
            }

            // Check if user belongs to the specified school
            if ($request->has('school_subdomain')) {
                $school = School::where('subdomain', $request->school_subdomain)->first();
                if (!$school || !$user->belongsToSchool($school->id)) {
                    RateLimiter::hit($key, 300);
                    return response()->json([
                        'success' => false,
                        'message' => 'User does not belong to this school',
                        'errors' => ['school' => ['Invalid school access']]
                    ], 403);
                }
            }

            // Check if user is active
            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated',
                    'errors' => ['account' => ['Account is not active']]
                ], 403);
            }

            // Attempt to create token
            if (!$token = JWTAuth::attempt($credentials)) {
                RateLimiter::hit($key, 300);
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid credentials',
                    'errors' => ['auth' => ['Invalid email or password']]
                ], 401);
            }

            // Clear rate limiting on successful login
            RateLimiter::clear($key);

            // Update last login
            $user->updateLastLogin();

            // Log successful login
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'school_id' => $user->school_id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return $this->respondWithToken($token, $user);

        } catch (JWTException $e) {
            Log::error('JWT Exception during login', [
                'error' => $e->getMessage(),
                'email' => $credentials['email'],
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Could not create token',
                'errors' => ['token' => ['Token creation failed']]
            ], 500);
        }
    }

    /**
     * Register a new user.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_subdomain' => 'required|string|exists:schools,subdomain',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'sometimes|string|max:20',
            'role' => 'sometimes|string|exists:roles,name',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Get school
            $school = School::where('subdomain', $request->school_subdomain)
                          ->where('is_active', true)
                          ->first();

            if (!$school) {
                return response()->json([
                    'success' => false,
                    'message' => 'School not found or inactive',
                    'errors' => ['school' => ['Invalid school']]
                ], 404);
            }

            // Check if school has active subscription
            if (!$school->hasActiveSubscription()) {
                return response()->json([
                    'success' => false,
                    'message' => 'School subscription is expired',
                    'errors' => ['subscription' => ['School subscription expired']]
                ], 403);
            }

            // Create user
            $user = User::create([
                'school_id' => $school->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'is_active' => true,
            ]);

            // Assign role
            $role = $request->role ?? 'student';
            $user->assignRole($role);

            // Generate token
            $token = JWTAuth::fromUser($user);

            DB::commit();

            // Log successful registration
            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'school_id' => $user->school_id,
                'role' => $role,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'full_name' => $user->full_name,
                        'phone' => $user->phone,
                        'avatar_url' => $user->avatar_url,
                        'is_active' => $user->is_active,
                        'email_verified' => !is_null($user->email_verified_at),
                        'school' => [
                            'id' => $school->id,
                            'name' => $school->name,
                            'subdomain' => $school->subdomain,
                        ],
                        'roles' => $user->roles->map(function ($role) {
                            return [
                                'id' => $role->id,
                                'name' => $role->name,
                                'guard_name' => $role->guard_name,
                            ];
                        })->toArray(),
                        'permissions' => $user->getDirectPermissions()->map(function ($permission) {
                             return [
                                 'id' => $permission->id,
                                 'name' => $permission->name,
                                 'guard_name' => $permission->guard_name,
                             ];
                         })->toArray(),
                    ],
                    'access_token' => $token,
                    'token_type' => 'bearer',
                    'expires_in' => config('jwt.ttl') * 60
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Registration failed', [
                'error' => $e->getMessage(),
                'email' => $request->email,
                'school_subdomain' => $request->school_subdomain,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'errors' => ['registration' => ['An error occurred during registration']]
            ], 500);
        }
    }

    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function me(): JsonResponse
    {
        $user = auth('api')->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'errors' => ['auth' => ['User not authenticated']]
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'is_active' => $user->is_active,
                    'email_verified' => !is_null($user->email_verified_at),
                    'last_login_at' => $user->last_login_at,
                    'school' => [
                        'id' => $user->school->id,
                        'name' => $user->school->name,
                        'subdomain' => $user->school->subdomain,
                        'logo_url' => $user->school->logo_url,
                    ],
                    'roles' => $user->roles->map(function ($role) {
                        return [
                            'id' => $role->id,
                            'name' => $role->name,
                            'guard_name' => $role->guard_name,
                        ];
                    })->toArray(),
                    'permissions' => $user->getDirectPermissions()->map(function ($permission) {
                         return [
                             'id' => $permission->id,
                             'name' => $permission->name,
                             'guard_name' => $permission->guard_name,
                         ];
                     })->toArray(),
                ]
            ]
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout(): JsonResponse
    {
        try {
            $user = auth('api')->user();
            
            // Log logout
            if ($user) {
                Log::info('User logged out', [
                    'user_id' => $user->id,
                    'email' => $user->email
                ]);
            }

            auth('api')->logout();

            return response()->json([
                'success' => true,
                'message' => 'Successfully logged out'
            ]);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout',
                'errors' => ['logout' => ['Could not invalidate token']]
            ], 500);
        }
    }

    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh(): JsonResponse
    {
        try {
            $token = JWTAuth::refresh(JWTAuth::getToken());
            $user = auth('api')->user();

            return $this->respondWithToken($token, $user);

        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not refresh token',
                'errors' => ['token' => ['Token refresh failed']]
            ], 401);
        }
    }

    /**
     * Get the token array structure.
     *
     * @param string $token
     * @param User $user
     * @return JsonResponse
     */
    protected function respondWithToken(string $token, User $user): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->full_name,
                    'phone' => $user->phone,
                    'avatar_url' => $user->avatar_url,
                    'is_active' => $user->is_active,
                    'email_verified' => $user->hasVerifiedEmail(),
                    'last_login_at' => $user->last_login_at,
                    'school' => [
                        'id' => $user->school->id,
                        'name' => $user->school->name,
                        'subdomain' => $user->school->subdomain,
                        'logo_url' => $user->school->logo_url,
                    ],
                    'roles' => $user->getFormattedRoles(),
                    'permissions' => $user->getFormattedPermissions(),
                ],
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => config('jwt.ttl') * 60
            ]
        ]);
    }
}