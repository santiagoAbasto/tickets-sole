<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SlaPolicy extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'priority_id', 'response_hours',
        'resolution_hours', 'business_hours_only', 'is_active',
    ];

    protected $casts = [
        'response_hours' => 'integer',
        'resolution_hours' => 'integer',
        'business_hours_only' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'sla_policy_id');
    }
}
