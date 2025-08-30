<?php

namespace App\Events;

use App\Models\Calendar;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CalendarSyncCompleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * The calendar that was synced.
     *
     * @var Calendar
     */
    public $calendar;

    /**
     * Sync statistics.
     *
     * @var array
     */
    public $stats;

    /**
     * Whether the sync was successful.
     *
     * @var bool
     */
    public $success;

    /**
     * Error message if sync failed.
     *
     * @var string|null
     */
    public $error;

    /**
     * Create a new event instance.
     */
    public function __construct(Calendar $calendar, array $stats = [], bool $success = true, ?string $error = null)
    {
        $this->calendar = $calendar;
        $this->stats = $stats;
        $this->success = $success;
        $this->error = $error;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('calendar.' . $this->calendar->id),
            new PrivateChannel('user.' . $this->calendar->user_id . '.calendars'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'calendar.sync.completed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'calendar_id' => $this->calendar->id,
            'calendar_name' => $this->calendar->name,
            'success' => $this->success,
            'error' => $this->error,
            'stats' => $this->stats,
            'synced_at' => $this->calendar->last_sync_at?->toISOString(),
            'sync_status' => $this->calendar->sync_status,
        ];
    }

    /**
     * Determine if this event should be broadcast.
     */
    public function shouldBroadcast(): bool
    {
        // Only broadcast if the calendar belongs to an authenticated user
        return $this->calendar->user_id !== null;
    }
}
