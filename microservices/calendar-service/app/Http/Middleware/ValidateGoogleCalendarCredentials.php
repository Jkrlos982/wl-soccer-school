<?php

namespace App\Http\Middleware;

use App\Models\CalendarIntegration;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as BaseResponse;

class ValidateGoogleCalendarCredentials
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): BaseResponse
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'error' => 'Unauthorized',
                'message' => 'User authentication required'
            ], 401);
        }
        
        // Check if user has Google Calendar integration
        $integration = CalendarIntegration::where('user_id', $user->id)
            ->where('provider', 'google')
            ->where('is_active', true)
            ->first();
            
        if (!$integration) {
            return response()->json([
                'error' => 'No Google Calendar Integration',
                'message' => 'User has not connected their Google Calendar account',
                'action' => 'Please connect your Google Calendar account first'
            ], 403);
        }
        
        // Check if token is expired
        if ($integration->isTokenExpired()) {
            return response()->json([
                'error' => 'Token Expired',
                'message' => 'Google Calendar access token has expired',
                'action' => 'Please re-authenticate your Google Calendar account'
            ], 403);
        }
        
        // Add integration to request for use in controllers
        $request->merge(['google_integration' => $integration]);
        
        return $next($request);
    }
}