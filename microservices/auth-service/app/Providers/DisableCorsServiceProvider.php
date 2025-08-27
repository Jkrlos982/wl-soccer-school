<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Http\Middleware\HandleCors;

class DisableCorsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Get the kernel instance
        $kernel = $this->app->make(\Illuminate\Contracts\Http\Kernel::class);
        
        // Use reflection to access the protected middleware property
        $reflection = new \ReflectionClass($kernel);
        $property = $reflection->getProperty('middleware');
        $property->setAccessible(true);
        $middleware = $property->getValue($kernel);
        
        // Remove HandleCors from the middleware stack if it exists
        $filteredMiddleware = array_filter($middleware, function ($middlewareClass) {
            return $middlewareClass !== HandleCors::class && 
                   $middlewareClass !== '\\Illuminate\\Http\\Middleware\\HandleCors' &&
                   $middlewareClass !== 'Illuminate\\Http\\Middleware\\HandleCors';
        });
        
        // Replace the middleware stack
        $property->setValue($kernel, array_values($filteredMiddleware));
    }
}