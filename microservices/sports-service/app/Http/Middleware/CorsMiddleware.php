<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CorsMiddleware
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
        $response = $next($request);
        
        $allowedOrigins = explode(',', env('CORS_ALLOWED_ORIGINS', '*'));
        $allowedMethods = env('CORS_ALLOWED_METHODS', 'GET,POST,PUT,DELETE,OPTIONS');
        $allowedHeaders = env('CORS_ALLOWED_HEADERS', 'Content-Type,Authorization,X-Requested-With');
        
        $origin = $request->header('Origin');
        
        if (in_array('*', $allowedOrigins) || in_array($origin, $allowedOrigins)) {
            $response->headers->set('Access-Control-Allow-Origin', $origin ?: '*');
        }
        
        $response->headers->set('Access-Control-Allow-Methods', $allowedMethods);
        $response->headers->set('Access-Control-Allow-Headers', $allowedHeaders);
        $response->headers->set('Access-Control-Allow-Credentials', 'true');
        $response->headers->set('Access-Control-Max-Age', '86400');
        
        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
        }
        
        return $response;
    }
}