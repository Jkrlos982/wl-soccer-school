<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use App\Jobs\ProcessScheduledNotificationsJob;

class ProcessNotificationQueuesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process-queues 
                            {--queue=all : Specific queue to process (high|default|low|all)}
                            {--workers=3 : Number of workers per queue}
                            {--timeout=60 : Worker timeout in seconds}
                            {--memory=128 : Memory limit in MB}
                            {--sleep=3 : Sleep time when no jobs available}
                            {--tries=3 : Number of attempts per job}
                            {--daemon : Run as daemon}
                            {--stop-when-empty : Stop when queue is empty}
                            {--process-scheduled : Also process scheduled notifications}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process notification queues with priority-based workers';

    /**
     * Queue configurations
     *
     * @var array
     */
    protected $queueConfig = [
        'high' => [
            'connection' => 'notifications-high',
            'queue' => 'notifications-high',
            'priority' => 1
        ],
        'default' => [
            'connection' => 'notifications-default', 
            'queue' => 'notifications-default',
            'priority' => 2
        ],
        'low' => [
            'connection' => 'notifications-low',
            'queue' => 'notifications-low', 
            'priority' => 3
        ]
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Notification Queue Processing...');
        
        // Process scheduled notifications if requested
        if ($this->option('process-scheduled')) {
            $this->processScheduledNotifications();
        }

        $queueToProcess = $this->option('queue');
        $workers = (int) $this->option('workers');
        $timeout = (int) $this->option('timeout');
        $memory = (int) $this->option('memory');
        $sleep = (int) $this->option('sleep');
        $tries = (int) $this->option('tries');
        $daemon = $this->option('daemon');
        $stopWhenEmpty = $this->option('stop-when-empty');

        if ($queueToProcess === 'all') {
            $this->processAllQueues($workers, $timeout, $memory, $sleep, $tries, $daemon, $stopWhenEmpty);
        } else {
            $this->processSingleQueue($queueToProcess, $workers, $timeout, $memory, $sleep, $tries, $daemon, $stopWhenEmpty);
        }

        return 0;
    }

    /**
     * Process all notification queues
     */
    protected function processAllQueues($workers, $timeout, $memory, $sleep, $tries, $daemon, $stopWhenEmpty)
    {
        $this->info('Processing all notification queues...');
        
        // Sort queues by priority (high priority first)
        $sortedQueues = collect($this->queueConfig)
            ->sortBy('priority')
            ->keys()
            ->toArray();

        $processes = [];

        foreach ($sortedQueues as $queueName) {
            $this->info("Starting workers for {$queueName} priority queue...");
            
            for ($i = 0; $i < $workers; $i++) {
                $process = $this->startQueueWorker(
                    $queueName, 
                    $timeout, 
                    $memory, 
                    $sleep, 
                    $tries, 
                    $daemon, 
                    $stopWhenEmpty
                );
                
                if ($process) {
                    $processes[] = $process;
                }
            }
        }

        if ($daemon) {
            $this->info('Queue workers started as daemon processes.');
            $this->displayQueueStatus();
        } else {
            $this->info('Queue processing completed.');
        }
    }

    /**
     * Process a single queue
     */
    protected function processSingleQueue($queueName, $workers, $timeout, $memory, $sleep, $tries, $daemon, $stopWhenEmpty)
    {
        if (!isset($this->queueConfig[$queueName])) {
            $this->error("Invalid queue name: {$queueName}");
            $this->info('Available queues: ' . implode(', ', array_keys($this->queueConfig)));
            return;
        }

        $this->info("Processing {$queueName} priority queue...");
        
        for ($i = 0; $i < $workers; $i++) {
            $this->startQueueWorker(
                $queueName, 
                $timeout, 
                $memory, 
                $sleep, 
                $tries, 
                $daemon, 
                $stopWhenEmpty
            );
        }

        if ($daemon) {
            $this->info("Queue worker for {$queueName} started as daemon process.");
        } else {
            $this->info("Queue processing for {$queueName} completed.");
        }
    }

    /**
     * Start a queue worker process
     */
    protected function startQueueWorker($queueName, $timeout, $memory, $sleep, $tries, $daemon, $stopWhenEmpty)
    {
        $config = $this->queueConfig[$queueName];
        
        $command = [
            'queue:work',
            $config['connection'],
            '--queue=' . $config['queue'],
            '--timeout=' . $timeout,
            '--memory=' . $memory,
            '--sleep=' . $sleep,
            '--tries=' . $tries,
        ];

        if ($daemon) {
            $command[] = '--daemon';
        }

        if ($stopWhenEmpty) {
            $command[] = '--stop-when-empty';
        }

        try {
            if ($daemon) {
                // For daemon mode, we need to start the process in background
                $process = new \Symfony\Component\Process\Process($command);
                $process->start();
                
                $this->info("Started worker for {$queueName} queue (PID: {$process->getPid()})");
                return $process;
            } else {
                // For non-daemon mode, run synchronously
                $exitCode = Artisan::call(implode(' ', $command));
                
                if ($exitCode === 0) {
                    $this->info("Worker for {$queueName} queue completed successfully");
                } else {
                    $this->error("Worker for {$queueName} queue failed with exit code: {$exitCode}");
                }
                
                return null;
            }
        } catch (\Exception $e) {
            $this->error("Failed to start worker for {$queueName} queue: " . $e->getMessage());
            Log::error('Queue worker start failed', [
                'queue' => $queueName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Process scheduled notifications
     */
    protected function processScheduledNotifications()
    {
        $this->info('Processing scheduled notifications...');
        
        try {
            ProcessScheduledNotificationsJob::dispatch();
            $this->info('Scheduled notifications job dispatched successfully.');
        } catch (\Exception $e) {
            $this->error('Failed to dispatch scheduled notifications job: ' . $e->getMessage());
            Log::error('Scheduled notifications processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Display current queue status
     */
    protected function displayQueueStatus()
    {
        $this->info('\n=== Queue Status ===');
        
        foreach ($this->queueConfig as $queueName => $config) {
            try {
                $connection = $config['connection'];
                $queueKey = $config['queue'];
                
                // Get queue size from Redis
                $size = Redis::connection('default')->llen("queues:{$queueKey}");
                $delayed = Redis::connection('default')->zcard("queues:{$queueKey}:delayed");
                $reserved = Redis::connection('default')->zcard("queues:{$queueKey}:reserved");
                
                $this->info(sprintf(
                    '%s: %d pending, %d delayed, %d reserved',
                    ucfirst($queueName),
                    $size,
                    $delayed,
                    $reserved
                ));
            } catch (\Exception $e) {
                $this->warn("Could not get status for {$queueName} queue: " . $e->getMessage());
            }
        }
        
        $this->info('==================\n');
    }

    /**
     * Stop all queue workers
     */
    public function stopWorkers()
    {
        $this->info('Stopping all notification queue workers...');
        
        try {
            // Send restart signal to all workers
            Artisan::call('queue:restart');
            $this->info('All queue workers have been signaled to restart.');
        } catch (\Exception $e) {
            $this->error('Failed to stop workers: ' . $e->getMessage());
        }
    }

    /**
     * Get queue statistics
     */
    protected function getQueueStats()
    {
        $stats = [];
        
        foreach ($this->queueConfig as $queueName => $config) {
            try {
                $queueKey = $config['queue'];
                
                $stats[$queueName] = [
                    'pending' => Redis::connection('default')->llen("queues:{$queueKey}"),
                    'delayed' => Redis::connection('default')->zcard("queues:{$queueKey}:delayed"),
                    'reserved' => Redis::connection('default')->zcard("queues:{$queueKey}:reserved"),
                    'failed' => Redis::connection('default')->llen("queues:{$queueKey}:failed"),
                ];
            } catch (\Exception $e) {
                $stats[$queueName] = [
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $stats;
    }
}