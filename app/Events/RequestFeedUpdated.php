<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever the open-requests feed (web dashboard, Android
 * Requests/Home) should refetch — a new request posted, or an existing
 * one's status/verification changed. Deliberately `ShouldBroadcastNow`
 * rather than queued: there's no persistent worker driving the default
 * queue fast enough for this to feel real-time otherwise, and a broadcast
 * is cheap enough not to need queuing at all.
 *
 * Carries only IDs, not the request's data — both clients treat this as a
 * signal to refetch through their own existing (already-tested) fetch
 * path, not a payload to render directly. See .claude-progress.md.
 */
class RequestFeedUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $requestId,
        public string $action,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel('requests')];
    }

    public function broadcastAs(): string
    {
        return 'RequestFeedUpdated';
    }
}
