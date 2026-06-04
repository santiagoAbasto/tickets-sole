<?php

namespace App\Events;

use App\Models\Ticket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('ticket.'.$this->ticket->id),
            new PrivateChannel('dashboard'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ticket.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $t = $this->ticket->loadMissing('status', 'priority', 'assignee');

        return [
            'ticket_id' => $t->id,
            'status' => $t->status?->only(['id', 'name', 'slug', 'color']),
            'priority' => $t->priority?->only(['id', 'name', 'slug', 'color']),
            'agent' => $t->assignee ? ['id' => $t->assignee->id, 'name' => $t->assignee->name] : null,
        ];
    }
}
