<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code', 'customer_id', 'company_id', 'department_id', 'category_id',
        'priority_id', 'status_id', 'sla_policy_id', 'assigned_to', 'created_by', 'source',
        'subject', 'description', 'due_at', 'first_response_at', 'resolved_at',
        'closed_at', 'last_activity_at',
        'due_soon_notified_at', 'overdue_notified_at', 'escalated_at',
    ];

    protected $casts = [
        // Foreign keys cast to int so strict comparisons in policies
        // (e.g. assigned_to === $user->id) hold under MySQL/PDO, which can
        // otherwise return these columns as strings and silently break
        // staffCanAct() — leaving the assigned agent unable to reply, change
        // status or contact the customer.
        'assigned_to' => 'integer',
        'created_by' => 'integer',
        'customer_id' => 'integer',
        'company_id' => 'integer',
        'category_id' => 'integer',
        'priority_id' => 'integer',
        'status_id' => 'integer',
        'department_id' => 'integer',
        'due_at' => 'datetime',
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'due_soon_notified_at' => 'datetime',
        'overdue_notified_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    /** Computed attributes exposed to the frontend. */
    protected $appends = ['is_overdue', 'overdue_hours'];

    // ----------------------------------------------------------------------
    // Relationships
    // ----------------------------------------------------------------------

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TicketCategory::class, 'category_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(TicketPriority::class, 'priority_id');
    }

    public function status(): BelongsTo
    {
        return $this->belongsTo(TicketStatus::class, 'status_id');
    }

    public function slaPolicy(): BelongsTo
    {
        return $this->belongsTo(SlaPolicy::class, 'sla_policy_id');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('created_at');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(TicketNote::class)->latest();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public function credentials(): HasOne
    {
        return $this->hasOne(TicketCredential::class);
    }

    public function delegationRequests(): HasMany
    {
        return $this->hasMany(TicketDelegationRequest::class)->latest();
    }

    public function pendingDelegation(): HasOne
    {
        return $this->hasOne(TicketDelegationRequest::class)
            ->where('status', TicketDelegationRequest::STATUS_PENDING)
            ->latestOfMany();
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TicketActivityLog::class)->latest();
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(TicketAssignment::class);
    }

    // ----------------------------------------------------------------------
    // Scopes
    // ----------------------------------------------------------------------

    /** Tickets in a non-terminal status. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereRelation('status', 'is_final', false);
    }

    /** Past due and still open. */
    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->whereRelation('status', 'is_final', false);
    }

    public function scopeResolved(Builder $query): Builder
    {
        return $query->whereNotNull('resolved_at');
    }

    public function scopeAssignedTo(Builder $query, int $userId): Builder
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeUnassigned(Builder $query): Builder
    {
        return $query->whereNull('assigned_to');
    }

    public function scopeByPriority(Builder $query, int $priorityId): Builder
    {
        return $query->where('priority_id', $priorityId);
    }

    public function scopeByStatus(Builder $query, int $statusId): Builder
    {
        return $query->where('status_id', $statusId);
    }

    public function scopeByCategory(Builder $query, int $categoryId): Builder
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByDateRange(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('created_at', '<=', $to));
    }

    /** Restrict visibility to a customer's own tickets (portal/Cliente role). */
    public function scopeForCustomerUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('customer', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    // ----------------------------------------------------------------------
    // Accessors (computed, no extra queries when relations are eager loaded)
    // ----------------------------------------------------------------------

    public function getIsOverdueAttribute(): bool
    {
        if (! $this->due_at) {
            return false;
        }

        if ($this->resolved_at || $this->closed_at) {
            return false;
        }

        if ($this->relationLoaded('status') && $this->status?->is_final) {
            return false;
        }

        return $this->due_at->isPast();
    }

    /** Hours overdue (decimal), or 0 when not overdue. */
    public function getOverdueHoursAttribute(): float
    {
        if (! $this->is_overdue) {
            return 0.0;
        }

        return round($this->due_at->diffInMinutes(now()) / 60, 1);
    }

    public function overdueForHumans(): ?string
    {
        if (! $this->is_overdue) {
            return null;
        }

        $minutes = (int) $this->due_at->diffInMinutes(now());
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;

        return $hours > 0 ? "{$hours} h {$mins} min" : "{$mins} min";
    }

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    public function resolutionHours(): ?float
    {
        if (! $this->resolved_at) {
            return null;
        }

        return round($this->created_at->diffInMinutes($this->resolved_at) / 60, 1);
    }

    public function markActivity(?CarbonInterface $at = null): void
    {
        $this->forceFill(['last_activity_at' => $at ?? now()])->save();
    }
}
