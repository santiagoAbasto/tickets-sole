<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal note — visible only to staff (Super Admin / Admin / Agente).
 * Must never be serialized into a customer-facing response.
 */
class TicketNote extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'body', 'channel',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
