<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    public const AUTHOR_CUSTOMER = 'customer';

    public const AUTHOR_AGENT = 'agent';

    public const AUTHOR_SYSTEM = 'system';

    protected $fillable = [
        'ticket_id', 'user_id', 'customer_id', 'author_type', 'body', 'is_email',
    ];

    protected $casts = [
        'is_email' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class, 'message_id');
    }

    public function isFromCustomer(): bool
    {
        return $this->author_type === self::AUTHOR_CUSTOMER;
    }
}
