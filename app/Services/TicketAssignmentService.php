<?php

namespace App\Services;

use App\Events\TicketUpdated;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TicketAssignmentService
{
    public function __construct(private TicketActivityLoggerService $logger) {}

    public function assign(Ticket $ticket, User $agent, ?User $actor = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $agent, $actor) {
            if ($ticket->assigned_to === $agent->id) {
                return $ticket;
            }

            // Close the currently open assignment, if any.
            $ticket->assignments()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);

            $ticket->assignments()->create([
                'assigned_to' => $agent->id,
                'assigned_by' => ($actor ?? auth()->user())?->id,
                'assigned_at' => now(),
            ]);

            $ticket->update([
                'assigned_to' => $agent->id,
                'last_activity_at' => now(),
            ]);

            $this->logger->assigned($ticket, $agent, $actor);

            $fresh = $ticket->refresh();
            TicketUpdated::dispatch($fresh);

            return $fresh;
        });
    }

    public function unassign(Ticket $ticket, ?User $actor = null): Ticket
    {
        return DB::transaction(function () use ($ticket, $actor) {
            $ticket->assignments()->whereNull('unassigned_at')->update(['unassigned_at' => now()]);

            $ticket->update([
                'assigned_to' => null,
                'last_activity_at' => now(),
            ]);

            $this->logger->unassigned($ticket, $actor);

            $fresh = $ticket->refresh();
            TicketUpdated::dispatch($fresh);

            return $fresh;
        });
    }
}
