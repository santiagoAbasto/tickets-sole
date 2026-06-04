<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketPriority extends Model
{
    protected $fillable = [
        'name', 'slug', 'color', 'response_hours', 'resolution_hours',
        'level', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'response_hours' => 'integer',
        'resolution_hours' => 'integer',
        'level' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'priority_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('level');
    }
}
