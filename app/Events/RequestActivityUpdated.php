<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired whenever a single request's own detail page (web `requests/show`,
 * Android `RequestDetailScreen`) should refetch — a new response, a donor
 * selected, a mutual confirmation, or the request's own status/verification
 * changing. Same "signal to refetch, not a payload" + `ShouldBroadcastNow`
 * reasoning as RequestFeedUpdated — see that class and
 * .claude-progress.md.
 */
class RequestActivityUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $requestId,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel("request.{$this->requestId}")];
    }

    public function broadcastAs(): string
    {
        return 'RequestActivityUpdated';
    }
}
