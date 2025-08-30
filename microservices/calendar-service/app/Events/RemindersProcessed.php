<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RemindersProcessed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $schoolId;
    public $stats;
    public $processBirthdays;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct($schoolId, array $stats, $processBirthdays = false)
    {
        $this->schoolId = $schoolId;
        $this->stats = $stats;
        $this->processBirthdays = $processBirthdays;
        $this->timestamp = now();
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        $channels = [];
        
        if ($this->schoolId) {
            $channels[] = new PrivateChannel("school.{$this->schoolId}.reminders");
        } else {
            $channels[] = new PrivateChannel('admin.reminders');
        }
        
        return $channels;
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'school_id' => $this->schoolId,
            'stats' => $this->stats,
            'process_birthdays' => $this->processBirthdays,
            'timestamp' => $this->timestamp->toISOString(),
            'message' => $this->generateMessage()
        ];
    }

    /**
     * Get the broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'reminders.processed';
    }

    /**
     * Generate a human-readable message about the processing results.
     */
    private function generateMessage(): string
    {
        $processed = $this->stats['reminders_sent'] ?? 0;
        $failed = $this->stats['failed_reminders'] ?? 0;
        $birthdays = $this->stats['birthday_reminders'] ?? 0;
        
        $message = "Processed {$processed} reminders";
        
        if ($failed > 0) {
            $message .= ", {$failed} failed";
        }
        
        if ($this->processBirthdays && $birthdays > 0) {
            $message .= ", {$birthdays} birthday reminders created";
        }
        
        return $message;
    }
}