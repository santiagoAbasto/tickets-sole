<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketStatus extends Model
{
    protected $fillable = [
        'name', 'slug', 'color', 'is_final', 'is_resolved', 'is_default',
        'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_final' => 'boolean',
        'is_resolved' => 'boolean',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'status_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /** IDs of terminal statuses — used to exclude tickets from "overdue". */
    public static function finalIds(): array
    {
        return static::query()->where('is_final', true)->pluck('id')->all();
    }

    public static function defaultId(): ?int
    {
        return static::query()->where('is_default', true)->value('id')
            ?? static::query()->orderBy('sort_order')->value('id');
    }

    public static function resolvedId(): ?int
    {
        return static::query()->where('is_resolved', true)->value('id');
    }
}
