<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketMessageCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Ticket $ticket, public TicketMessage $message) {}

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
        return 'message.created';
    }

    /**
     * Internal notes are never broadcast (they use a different flow), so this
     * payload only ever carries customer/agent messages — safe for the channel.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $m = $this->message->loadMissing('user', 'customer', 'attachments');

        return [
            'ticket_id' => $this->ticket->id,
            'message' => [
                'id' => $m->id,
                'author_type' => $m->author_type,
                'body' => $m->body,
                'author' => $m->author_type === 'customer'
                    ? ['name' => $m->customer?->name ?? 'Cliente', 'avatar_url' => $m->customer?->avatarUrl()]
                    : ['name' => $m->user?->name ?? 'Soporte', 'avatar_url' => $m->user?->avatarUrl()],
                'attachments' => $m->attachments->map(fn ($a) => [
                    'id' => $a->id, 'name' => $a->original_name, 'url' => $a->url, 'size' => $a->human_size, 'is_image' => $a->is_image,
                ])->all(),
                'created_at' => $m->created_at?->toIso8601String(),
            ],
        ];
    }
}
