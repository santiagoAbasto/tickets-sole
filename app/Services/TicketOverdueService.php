<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TicketOverdueService
{
    /** Base query for overdue tickets (open + past due). */
    public function query(): Builder
    {
        return Ticket::query()->overdue();
    }

    public function count(): int
    {
        return $this->query()->count();
    }

    /**
     * Most-overdue tickets first, with the relations needed for the UI.
     */
    public function list(int $limit = 10): Collection
    {
        return $this->query()
            ->with(['customer:id,name,email,avatar_path', 'assignee:id,name,avatar_path', 'priority', 'status'])
            ->orderBy('due_at') // oldest due date first = most overdue
            ->limit($limit)
            ->get()
            ->map(fn (Ticket $ticket) => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'subject' => $ticket->subject,
                'customer' => $ticket->customer?->name,
                'agent' => $ticket->assignee?->name,
                'priority' => $ticket->priority?->only(['name', 'slug', 'color']),
                'status' => $ticket->status?->only(['name', 'slug', 'color']),
                'due_at' => $ticket->due_at?->toIso8601String(),
                'overdue_hours' => $ticket->overdue_hours,
                'overdue_human' => $ticket->overdueForHumans(),
            ]);
    }

    public function hoursOverdue(Ticket $ticket): float
    {
        return $ticket->overdue_hours;
    }
}
