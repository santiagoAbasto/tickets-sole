<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Support\Collection;

trait SerializesTicketMessages
{
    /**
     * @return array<int, array<string, mixed>>
     */
    protected function ticketMessagesPayload(Ticket $ticket, ?int $afterId = null): array
    {
        return $ticket->messages()
            ->where('author_type', '!=', TicketMessage::AUTHOR_SYSTEM)
            ->when($afterId, fn ($query) => $query->where('id', '>', $afterId))
            ->with(['user:id,name,avatar_path', 'customer:id,name,avatar_path', 'attachments'])
            ->orderBy('id')
            ->get()
            ->map(fn (TicketMessage $message) => $this->ticketMessagePayload($message))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function ticketMessagePayload(TicketMessage $message): array
    {
        $message->loadMissing(['user:id,name,avatar_path', 'customer:id,name,avatar_path', 'attachments']);

        return [
            'id' => $message->id,
            'author_type' => $message->author_type,
            'body' => $message->body,
            'author' => $message->author_type === TicketMessage::AUTHOR_CUSTOMER
                ? [
                    'name' => $message->customer?->name ?? 'Cliente',
                    'avatar_url' => $message->customer?->avatarUrl(),
                ]
                : [
                    'name' => $message->user?->name ?? 'Soporte',
                    'avatar_url' => $message->user?->avatarUrl(),
                ],
            'attachments' => $this->ticketAttachmentsPayload($message->attachments),
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, TicketAttachment>  $attachments
     * @return array<int, array<string, mixed>>
     */
    protected function ticketAttachmentsPayload(Collection $attachments): array
    {
        return $attachments
            ->map(fn (TicketAttachment $attachment) => [
                'id' => $attachment->id,
                'name' => $attachment->original_name,
                'url' => $attachment->url,
                'size' => $attachment->human_size,
                'is_image' => $attachment->is_image,
            ])
            ->all();
    }
}
