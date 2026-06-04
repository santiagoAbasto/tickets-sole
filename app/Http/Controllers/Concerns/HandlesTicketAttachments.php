<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\User;
use Illuminate\Http\UploadedFile;

trait HandlesTicketAttachments
{
    /**
     * Persist uploaded files for a ticket (optionally tied to a message).
     *
     * @param  array<int, UploadedFile>|null  $files
     */
    protected function storeAttachments(Ticket $ticket, ?array $files, ?User $uploader, ?int $messageId = null): void
    {
        if (empty($files)) {
            return;
        }

        foreach ($files as $file) {
            if (! $file instanceof UploadedFile || ! $file->isValid()) {
                continue;
            }

            $path = $file->store("tickets/{$ticket->id}", 'public');

            TicketAttachment::create([
                'ticket_id' => $ticket->id,
                'message_id' => $messageId,
                'uploaded_by' => $uploader?->id,
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }
}
