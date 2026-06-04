<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SlaEscalationService
{
    public function __construct(
        private TicketActivityLoggerService $logger,
        private TicketAssignmentService $assignments,
    ) {}

    /**
     * Escalate a breached ticket: bump priority one level and, if unassigned,
     * route it to an admin. Idempotent-ish via the caller's overdue guard.
     */
    public function escalate(Ticket $ticket): void
    {
        DB::transaction(function () use ($ticket) {
            $ticket->loadMissing('priority');
            $actions = [];

            $higher = TicketPriority::where('level', '>', $ticket->priority->level)
                ->orderBy('level')
                ->first();

            if ($higher) {
                $from = $ticket->priority->name;
                $ticket->priority_id = $higher->id;
                $this->logger->priorityChanged($ticket, $from, $higher->name);
                $actions[] = "prioridad {$from} → {$higher->name}";
            }

            $ticket->escalated_at = now();
            $ticket->save();

            if (! $ticket->assigned_to) {
                $admin = User::role(['Admin', 'Super Admin'])->where('is_active', true)->first();
                if ($admin) {
                    $this->assignments->assign($ticket->fresh(), $admin);
                    $actions[] = "asignado a {$admin->name}";
                }
            }

            $this->logger->log(
                $ticket,
                'escalated',
                'Escalamiento automático por SLA'.($actions ? ': '.implode(', ', $actions) : ''),
                ['actions' => $actions],
            );
        });
    }
}
