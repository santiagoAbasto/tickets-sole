<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDelegationRequest;
use App\Models\Ticket;
use App\Models\TicketDelegationRequest;
use App\Models\User;
use App\Services\TicketActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketDelegationController extends Controller
{
    public function __construct(private TicketActivityLoggerService $logger) {}

    /** The assigned agent requests to delegate the ticket to another agent. */
    public function store(StoreDelegationRequest $request, Ticket $ticket): RedirectResponse
    {
        if ($ticket->pendingDelegation()->exists()) {
            return back()->with('error', 'Ya hay una solicitud de delegación pendiente para este ticket.');
        }

        $target = User::findOrFail($request->validated()['requested_to']);

        $ticket->delegationRequests()->create([
            'requested_by' => $request->user()->id,
            'requested_to' => $target->id,
            'note' => $request->validated()['note'] ?? null,
            'status' => TicketDelegationRequest::STATUS_PENDING,
        ]);

        $this->logger->delegationRequested($ticket, $target, $request->user());

        return back()->with('success', 'Solicitud de delegación enviada. Un administrador debe aprobarla.');
    }

    /** Super Admin / Admin approves: the ticket is reassigned to the requested agent. */
    public function approve(Request $request, Ticket $ticket, TicketDelegationRequest $delegation): RedirectResponse
    {
        $this->authorize('reviewDelegation', $ticket);
        $this->assertBelongsAndPending($ticket, $delegation);

        DB::transaction(function () use ($ticket, $delegation, $request) {
            $ticket->loadMissing('assignee');
            $from = $ticket->assignee?->name ?? 'Sin asignar';
            $to = $delegation->target->name;

            $ticket->forceFill(['assigned_to' => $delegation->requested_to])->save();
            $ticket->markActivity();

            $delegation->update([
                'status' => TicketDelegationRequest::STATUS_APPROVED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $this->logger->delegationApproved($ticket, $from, $to, $request->user());
        });

        return back()->with('success', 'Delegación aprobada. El ticket se reasignó.');
    }

    /** Super Admin / Admin rejects the request. */
    public function reject(Request $request, Ticket $ticket, TicketDelegationRequest $delegation): RedirectResponse
    {
        $this->authorize('reviewDelegation', $ticket);
        $this->assertBelongsAndPending($ticket, $delegation);

        $delegation->update([
            'status' => TicketDelegationRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->logger->delegationRejected($ticket, $delegation->target, $request->user());

        return back()->with('success', 'Solicitud de delegación rechazada.');
    }

    /** The requesting agent cancels their own pending request. */
    public function cancel(Request $request, Ticket $ticket, TicketDelegationRequest $delegation): RedirectResponse
    {
        abort_unless($delegation->requested_by === $request->user()->id, 403);
        $this->assertBelongsAndPending($ticket, $delegation);

        $delegation->update(['status' => TicketDelegationRequest::STATUS_CANCELLED]);

        return back()->with('success', 'Solicitud de delegación cancelada.');
    }

    private function assertBelongsAndPending(Ticket $ticket, TicketDelegationRequest $delegation): void
    {
        abort_unless($delegation->ticket_id === $ticket->id, 404);
        abort_unless($delegation->status === TicketDelegationRequest::STATUS_PENDING, 422);
    }
}
