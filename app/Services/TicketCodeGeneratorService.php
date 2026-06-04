<?php

namespace App\Services;

use App\Models\Ticket;

class TicketCodeGeneratorService
{
    /**
     * Generate the next sequential ticket code: TK-YYYY-000001.
     *
     * MUST be called inside the same DB transaction that inserts the ticket.
     * The lockForUpdate() row lock is held until that transaction commits,
     * which prevents two concurrent requests from claiming the same number.
     */
    public function generate(?int $year = null): string
    {
        $year ??= (int) now()->year;
        $prefix = sprintf('TK-%d-', $year);

        // Zero-padded 6-digit sequences sort lexicographically == numerically.
        $lastCode = Ticket::withTrashed()
            ->where('code', 'like', $prefix.'%')
            ->orderByDesc('code')
            ->lockForUpdate()
            ->value('code');

        $sequence = $lastCode
            ? ((int) substr($lastCode, strlen($prefix))) + 1
            : 1;

        return $prefix.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
