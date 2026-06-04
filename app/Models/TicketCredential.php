<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Internal hosting/cPanel credentials for a ticket. Staff-only.
 * The password is encrypted at rest and must never reach a customer-facing response.
 */
class TicketCredential extends Model
{
    protected $fillable = [
        'ticket_id', 'cpanel_user', 'cpanel_password',
        'server_url', 'hosting_type', 'hosting_provider', 'notes',
    ];

    protected $casts = [
        'cpanel_password' => 'encrypted',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /** True when no field carries data (used to skip creating empty rows). */
    public function isBlank(): bool
    {
        return blank($this->cpanel_user)
            && blank($this->cpanel_password)
            && blank($this->server_url)
            && blank($this->hosting_type)
            && blank($this->hosting_provider)
            && blank($this->notes);
    }
}
