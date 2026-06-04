<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketAttachment extends Model
{
    protected $fillable = [
        'ticket_id', 'message_id', 'note_id', 'uploaded_by',
        'original_name', 'file_path', 'mime_type', 'size',
    ];

    protected $casts = [
        'size' => 'integer',
    ];

    protected $appends = ['url', 'human_size', 'is_image'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function message(): BelongsTo
    {
        return $this->belongsTo(TicketMessage::class, 'message_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): string
    {
        return '/storage/'.ltrim((string) $this->file_path, '/');
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with((string) $this->mime_type, 'image/');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = $bytes > 0 ? (int) floor(log($bytes, 1024)) : 0;
        $i = min($i, count($units) - 1);

        return round($bytes / (1024 ** $i), $i === 0 ? 0 : 1).' '.$units[$i];
    }

    /** Remove the underlying file when the record is deleted. */
    protected static function booted(): void
    {
        static::deleting(function (TicketAttachment $attachment) {
            Storage::disk('public')->delete($attachment->file_path);
        });
    }
}
