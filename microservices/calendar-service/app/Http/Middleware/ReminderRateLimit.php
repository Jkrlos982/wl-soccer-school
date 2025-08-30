<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ReminderRateLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user()?->id ?? $request->ip();
        $schoolId = $request->header('X-School-ID') ?? $request->input('school_id');
        
        if (!$this->checkRateLimit($userId, $schoolId)) {
            Log::warning('Reminder rate limit exceeded', [
                'user_id' => $userId,
                'school_id' => $schoolId,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);
            
            return response()->json([
                'error' => 'Rate limit exceeded',
                'message' => 'Too many reminder requests. Please try again later.',
                'retry_after' => $this->getRetryAfter($userId)
            ], 429);
        }
        
        $response = $next($request);
        
        // Increment counter after successful request
        $this->incrementCounter($userId, $schoolId);
        
        return $response;
    }
    
    /**
     * Check if the user has exceeded rate limits
     */
    private function checkRateLimit(string $userId, ?string $schoolId): bool
    {
        $config = config('reminders.notifications.rate_limiting');
        
        if (!$config['enabled']) {
            return true;
        }
        
        $hourlyKey = "reminder_rate_limit:hourly:{$userId}";
        $dailyKey = "reminder_rate_limit:daily:{$userId}";
        
        $hourlyCount = Cache::get($hourlyKey, 0);
        $dailyCount = Cache::get($dailyKey, 0);
        
        $maxPerHour = $config['max_per_user_per_hour'];
        $maxPerDay = $config['max_per_user_per_day'];
        
        // Check school-specific limits if school ID is provided
        if ($schoolId) {
            $schoolHourlyKey = "reminder_rate_limit:school_hourly:{$schoolId}";
            $schoolDailyKey = "reminder_rate_limit:school_daily:{$schoolId}";
            
            $schoolHourlyCount = Cache::get($schoolHourlyKey, 0);
            $schoolDailyCount = Cache::get($schoolDailyKey, 0);
            
            // School limits (higher than individual limits)
            $schoolMaxPerHour = $maxPerHour * 10;
            $schoolMaxPerDay = $maxPerDay * 10;
            
            if ($schoolHourlyCount >= $schoolMaxPerHour || $schoolDailyCount >= $schoolMaxPerDay) {
                return false;
            }
        }
        
        return $hourlyCount < $maxPerHour && $dailyCount < $maxPerDay;
    }
    
    /**
     * Increment the rate limit counters
     */
    private function incrementCounter(string $userId, ?string $schoolId): void
    {
        $hourlyKey = "reminder_rate_limit:hourly:{$userId}";
        $dailyKey = "reminder_rate_limit:daily:{$userId}";
        
        // Increment user counters
        Cache::increment($hourlyKey, 1);
        Cache::increment($dailyKey, 1);
        
        // Set expiration if this is the first increment
        if (Cache::get($hourlyKey) === 1) {
            Cache::expire($hourlyKey, 3600); // 1 hour
        }
        
        if (Cache::get($dailyKey) === 1) {
            Cache::expire($dailyKey, 86400); // 24 hours
        }
        
        // Increment school counters if school ID is provided
        if ($schoolId) {
            $schoolHourlyKey = "reminder_rate_limit:school_hourly:{$schoolId}";
            $schoolDailyKey = "reminder_rate_limit:school_daily:{$schoolId}";
            
            Cache::increment($schoolHourlyKey, 1);
            Cache::increment($schoolDailyKey, 1);
            
            if (Cache::get($schoolHourlyKey) === 1) {
                Cache::expire($schoolHourlyKey, 3600);
            }
            
            if (Cache::get($schoolDailyKey) === 1) {
                Cache::expire($schoolDailyKey, 86400);
            }
        }
    }
    
    /**
     * Get the retry after time in seconds
     */
    private function getRetryAfter(string $userId): int
    {
        $hourlyKey = "reminder_rate_limit:hourly:{$userId}";
        $ttl = Cache::ttl($hourlyKey);
        
        return $ttl > 0 ? $ttl : 3600;
    }
    
    /**
     * Get current rate limit status for a user
     */
    public static function getRateLimitStatus(string $userId, ?string $schoolId = null): array
    {
        $config = config('reminders.notifications.rate_limiting');
        
        $hourlyKey = "reminder_rate_limit:hourly:{$userId}";
        $dailyKey = "reminder_rate_limit:daily:{$userId}";
        
        $hourlyCount = Cache::get($hourlyKey, 0);
        $dailyCount = Cache::get($dailyKey, 0);
        
        $status = [
            'user' => [
                'hourly' => [
                    'current' => $hourlyCount,
                    'limit' => $config['max_per_user_per_hour'],
                    'remaining' => max(0, $config['max_per_user_per_hour'] - $hourlyCount),
                    'reset_at' => now()->addSeconds(Cache::ttl($hourlyKey))
                ],
                'daily' => [
                    'current' => $dailyCount,
                    'limit' => $config['max_per_user_per_day'],
                    'remaining' => max(0, $config['max_per_user_per_day'] - $dailyCount),
                    'reset_at' => now()->addSeconds(Cache::ttl($dailyKey))
                ]
            ]
        ];
        
        if ($schoolId) {
            $schoolHourlyKey = "reminder_rate_limit:school_hourly:{$schoolId}";
            $schoolDailyKey = "reminder_rate_limit:school_daily:{$schoolId}";
            
            $schoolHourlyCount = Cache::get($schoolHourlyKey, 0);
            $schoolDailyCount = Cache::get($schoolDailyKey, 0);
            
            $schoolMaxPerHour = $config['max_per_user_per_hour'] * 10;
            $schoolMaxPerDay = $config['max_per_user_per_day'] * 10;
            
            $status['school'] = [
                'hourly' => [
                    'current' => $schoolHourlyCount,
                    'limit' => $schoolMaxPerHour,
                    'remaining' => max(0, $schoolMaxPerHour - $schoolHourlyCount),
                    'reset_at' => now()->addSeconds(Cache::ttl($schoolHourlyKey))
                ],
                'daily' => [
                    'current' => $schoolDailyCount,
                    'limit' => $schoolMaxPerDay,
                    'remaining' => max(0, $schoolMaxPerDay - $schoolDailyCount),
                    'reset_at' => now()->addSeconds(Cache::ttl($schoolDailyKey))
                ]
            ];
        }
        
        return $status;
    }
    
    /**
     * Clear rate limit for a user (admin function)
     */
    public static function clearRateLimit(string $userId, ?string $schoolId = null): void
    {
        $keys = [
            "reminder_rate_limit:hourly:{$userId}",
            "reminder_rate_limit:daily:{$userId}"
        ];
        
        if ($schoolId) {
            $keys[] = "reminder_rate_limit:school_hourly:{$schoolId}";
            $keys[] = "reminder_rate_limit:school_daily:{$schoolId}";
        }
        
        Cache::deleteMultiple($keys);
        
        Log::info('Rate limit cleared', [
            'user_id' => $userId,
            'school_id' => $schoolId,
            'cleared_keys' => $keys
        ]);
    }
}