<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssignTicketRequest;
use App\Models\Ticket;
use App\Models\User;
use App\Services\TicketAssignmentService;
use App\Services\TicketNotificationService;
use Illuminate\Http\RedirectResponse;

class TicketAssignmentController extends Controller
{
    public function __construct(
        private TicketAssignmentService $assignments,
        private TicketNotificationService $notifier,
    ) {}

    public function store(AssignTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $agentId = $request->validated()['assigned_to'] ?? null;

        if ($agentId === null) {
            $this->assignments->unassign($ticket, $request->user());

            return back()->with('success', 'Asignación removida.');
        }

        $agent = User::findOrFail($agentId);
        $this->assignments->assign($ticket, $agent, $request->user());
        $this->notifier->ticketAssigned($ticket, $agent);

        return back()->with('success', "Ticket asignado a {$agent->name}.");
    }
}
