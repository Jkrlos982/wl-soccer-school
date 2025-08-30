<?php

namespace App\Http\Controllers;

use App\Services\ReminderService;
use App\Jobs\ReminderProcessingJob;
use App\Http\Middleware\ReminderRateLimit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

class ReminderController extends Controller
{
    public function __construct(
        private ReminderService $reminderService
    ) {
        $this->middleware(ReminderRateLimit::class)->only(['sendImmediate', 'processReminders']);
    }

    /**
     * Get reminder statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $schoolId = $request->input('school_id');
            $startDate = $request->input('start_date', now()->startOfDay());
            $endDate = $request->input('end_date', now()->endOfDay());

            $stats = $this->reminderService->getReminderStats($schoolId, $startDate, $endDate);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get reminder statistics', [
                'error' => $e->getMessage(),
                'school_id' => $request->input('school_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to retrieve statistics',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send immediate reminder for an event
     */
    public function sendImmediate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'event_id' => 'required|string',
            'school_id' => 'required|string',
            'attendee_ids' => 'nullable|array',
            'attendee_ids.*' => 'string',
            'message' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $eventId = $request->input('event_id');
            $schoolId = $request->input('school_id');
            $attendeeIds = $request->input('attendee_ids');
            $customMessage = $request->input('message');

            $result = $this->reminderService->sendImmediateReminder(
                $eventId,
                $schoolId,
                $attendeeIds,
                $customMessage
            );

            return response()->json([
                'success' => true,
                'message' => 'Immediate reminder sent successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send immediate reminder', [
                'error' => $e->getMessage(),
                'event_id' => $request->input('event_id'),
                'school_id' => $request->input('school_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to send immediate reminder',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process reminders manually
     */
    public function processReminders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'school_id' => 'nullable|string',
            'async' => 'nullable|boolean',
            'process_birthdays' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $schoolId = $request->input('school_id');
            $async = $request->boolean('async', true);
            $processBirthdays = $request->boolean('process_birthdays', false);

            if ($async) {
                // Process asynchronously using jobs
                ReminderProcessingJob::dispatch($schoolId, $processBirthdays);

                return response()->json([
                    'success' => true,
                    'message' => 'Reminder processing job dispatched successfully',
                    'async' => true
                ]);
            } else {
                // Process synchronously
                $stats = $this->reminderService->processEventReminders($schoolId);

                if ($processBirthdays) {
                    $birthdayStats = $this->reminderService->createBirthdayReminders($schoolId);
                    $stats['birthday_reminders'] = $birthdayStats['birthday_reminders'];
                    $stats['failed_birthdays'] = $birthdayStats['failed_birthdays'];
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Reminders processed successfully',
                    'data' => $stats,
                    'async' => false
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process reminders', [
                'error' => $e->getMessage(),
                'school_id' => $request->input('school_id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to process reminders',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get rate limit status for current user
     */
    public function getRateLimitStatus(Request $request): JsonResponse
    {
        try {
            $userId = $request->user()?->id ?? $request->ip();
            $schoolId = $request->header('X-School-ID') ?? $request->input('school_id');

            $status = ReminderRateLimit::getRateLimitStatus($userId, $schoolId);

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get rate limit status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get system health status
     */
    public function getHealthStatus(): JsonResponse
    {
        try {
            $failureRate = Cache::get('reminder_failure_rate', 0);
            $consecutiveFailures = Cache::get('reminder_consecutive_failures', 0);
            $lastProcessed = Cache::get('reminder_last_processed');
            $queueSize = Cache::get('reminder_queue_size', 0);

            $status = [
                'healthy' => $failureRate < 10 && $consecutiveFailures < 5,
                'failure_rate' => $failureRate,
                'consecutive_failures' => $consecutiveFailures,
                'last_processed' => $lastProcessed,
                'queue_size' => $queueSize,
                'timestamp' => now()
            ];

            $httpStatus = $status['healthy'] ? 200 : 503;

            return response()->json([
                'success' => $status['healthy'],
                'data' => $status
            ], $httpStatus);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to get health status',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get reminder configuration
     */
    public function getConfig(): JsonResponse
    {
        try {
            $config = [
                'default_reminders' => config('reminders.default_reminders'),
                'birthdays' => config('reminders.birthdays'),
                'notifications' => [
                    'channels' => config('reminders.notifications.channels'),
                    'rate_limiting' => config('reminders.notifications.rate_limiting')
                ],
                'processing' => [
                    'batch_size' => config('reminders.processing.batch_size'),
                    'max_retries' => config('reminders.processing.max_retries')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $config
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get configuration',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear rate limit for a user (admin only)
     */
    public function clearRateLimit(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|string',
            'school_id' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors()
            ], 422);
        }

        try {
            $userId = $request->input('user_id');
            $schoolId = $request->input('school_id');

            ReminderRateLimit::clearRateLimit($userId, $schoolId);

            return response()->json([
                'success' => true,
                'message' => 'Rate limit cleared successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to clear rate limit',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}