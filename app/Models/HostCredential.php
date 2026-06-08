<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HostCredential extends Model
{
    protected $fillable = [
        'created_by', 'source_ticket_id', 'fingerprint', 'name', 'website_url',
        'server_url', 'hosting_type', 'hosting_provider', 'cpanel_user',
        'cpanel_password', 'notes',
    ];

    protected $casts = [
        'cpanel_password' => 'encrypted',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sourceTicket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class, 'source_ticket_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return $query;
        }

        return $query->where('created_by', $user->id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fingerprintFor(array $data): string
    {
        $server = self::cleanKey($data['server_url'] ?? null);
        $website = self::cleanKey($data['website_url'] ?? null);
        $user = self::cleanKey($data['cpanel_user'] ?? null);
        $provider = self::cleanKey($data['hosting_provider'] ?? null);
        $name = self::cleanKey($data['name'] ?? null);

        return hash('sha256', implode('|', [
            $server ?: $website ?: $provider ?: $name,
            $user,
            $provider,
        ]));
    }

    public static function syncFromTicketCredential(TicketCredential $credential, ?User $actor = null): ?self
    {
        if ($credential->isBlank()) {
            return null;
        }

        $credential->loadMissing('ticket');
        $ticket = $credential->ticket;

        $data = [
            'created_by' => $actor?->id ?? $ticket?->created_by ?? $ticket?->assigned_to,
            'source_ticket_id' => $ticket?->id,
            'name' => $ticket?->customer?->name ?: $ticket?->subject,
            'website_url' => null,
            'server_url' => $credential->server_url,
            'hosting_type' => $credential->hosting_type,
            'hosting_provider' => $credential->hosting_provider,
            'cpanel_user' => $credential->cpanel_user,
            'cpanel_password' => $credential->cpanel_password,
            'notes' => $credential->notes,
        ];
        $data['fingerprint'] = self::fingerprintFor($data);

        return self::updateOrCreate(
            ['fingerprint' => $data['fingerprint']],
            collect($data)->reject(fn ($value, $key) => $key === 'created_by' && blank($value))->all(),
        );
    }

    private static function cleanKey(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = preg_replace('#^https?://#', '', $value) ?? $value;

        return rtrim($value, "/ \t\n\r\0\x0B");
    }
}
