<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenExpiredException;
use PHPOpenSourceSaver\JWTAuth\Exceptions\TokenInvalidException;
use Illuminate\Support\Facades\Log;

class JwtAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            // Check if token is present
            $token = JWTAuth::getToken();
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided',
                    'errors' => ['auth' => ['Authorization token is required']]
                ], 401);
            }

            // Authenticate user
            $user = JWTAuth::authenticate($token);
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'errors' => ['auth' => ['User associated with token not found']]
                ], 401);
            }

            // Check if user is active
            if (!$user->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Account is deactivated',
                    'errors' => ['auth' => ['User account is not active']]
                ], 403);
            }

            // Check if user's school is active (if user belongs to a school)
            if ($user->school && !$user->school->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'School is deactivated',
                    'errors' => ['auth' => ['User\'s school is not active']]
                ], 403);
            }

            // Add user to request for easy access in controllers
            $request->merge(['authenticated_user' => $user]);

        } catch (TokenExpiredException $e) {
            Log::warning('JWT Token expired', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token has expired',
                'errors' => ['auth' => ['Token has expired, please login again']]
            ], 401);

        } catch (TokenInvalidException $e) {
            Log::warning('JWT Token invalid', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token is invalid',
                'errors' => ['auth' => ['Token is invalid']]
            ], 401);

        } catch (JWTException $e) {
            Log::error('JWT Exception', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Token error',
                'errors' => ['auth' => ['Authorization token error']]
            ], 401);

        } catch (\Exception $e) {
            Log::error('Authentication middleware error', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Authentication error',
                'errors' => ['auth' => ['An authentication error occurred']]
            ], 500);
        }

        return $next($request);
    }
}