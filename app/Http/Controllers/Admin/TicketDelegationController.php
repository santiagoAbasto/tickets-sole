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
    public function reviewFromLink(Request $request, string $ticket, string $delegation): RedirectResponse
    {
        $delegationRequest = TicketDelegationRequest::find($delegation);

        if ($delegationRequest) {
            $actualTicket = $delegationRequest->ticket()->first();

            if ($actualTicket) {
                $this->authorize('reviewDelegation', $actualTicket);

                $message = (string) $delegationRequest->ticket_id === $ticket
                    ? 'Para aprobar la delegación usá el botón "Aprobar" dentro del ticket.'
                    : 'La delegación pertenece a otro ticket. Te llevamos al ticket correcto para revisarla.';

                return redirect()
                    ->route('admin.tickets.show', $actualTicket)
                    ->with('info', $message);
            }
        }

        $requestedTicket = Ticket::find($ticket);

        if ($requestedTicket) {
            $this->authorize('reviewDelegation', $requestedTicket);

            return redirect()
                ->route('admin.tickets.show', $requestedTicket)
                ->with('error', 'La solicitud de delegación ya no existe o ya fue procesada.');
        }

        abort_unless($request->user()->hasPermissionTo('tickets.assign'), 403);

        return redirect()
            ->route('admin.tickets.dashboard')
            ->with('error', 'El ticket o la solicitud de delegación ya no existen.');
    }

    /** Super Admin / Admin approves: the ticket is reassigned to the requested agent. */
    public function approve(Request $request, Ticket $ticket, string $delegation): RedirectResponse
    {
        $delegationRequest = TicketDelegationRequest::find($delegation);

        if (! $delegationRequest) {
            $this->authorize('reviewDelegation', $ticket);

            return redirect()
                ->route('admin.tickets.show', $ticket)
                ->with('error', 'La solicitud de delegación ya no existe o ya fue procesada.');
        }

        $actualTicket = $delegationRequest->ticket()->first();

        if (! $actualTicket) {
            $this->authorize('reviewDelegation', $ticket);

            return redirect()
                ->route('admin.tickets.show', $ticket)
                ->with('error', 'La solicitud de delegación ya no existe o ya fue procesada.');
        }

        $this->authorize('reviewDelegation', $actualTicket);

        if ($delegationRequest->status !== TicketDelegationRequest::STATUS_PENDING) {
            return redirect()
                ->route('admin.tickets.show', $actualTicket)
                ->with('error', 'La solicitud de delegación ya fue procesada.');
        }

        DB::transaction(function () use ($actualTicket, $delegationRequest, $request) {
            $actualTicket->loadMissing('assignee');
            $from = $actualTicket->assignee?->name ?? 'Sin asignar';
            $to = $delegationRequest->target->name;

            $actualTicket->forceFill(['assigned_to' => $delegationRequest->requested_to])->save();
            $actualTicket->markActivity();

            $delegationRequest->update([
                'status' => TicketDelegationRequest::STATUS_APPROVED,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $this->logger->delegationApproved($actualTicket, $from, $to, $request->user());
        });

        return redirect()
            ->route('admin.tickets.show', $actualTicket)
            ->with('success', 'Delegación aprobada. El ticket se reasignó.');
    }

    /** Super Admin / Admin rejects the request. */
    public function reject(Request $request, Ticket $ticket, string $delegation): RedirectResponse
    {
        $delegationRequest = TicketDelegationRequest::find($delegation);

        if (! $delegationRequest) {
            $this->authorize('reviewDelegation', $ticket);

            return redirect()
                ->route('admin.tickets.show', $ticket)
                ->with('error', 'La solicitud de delegación ya no existe o ya fue procesada.');
        }

        $actualTicket = $delegationRequest->ticket()->first();

        if (! $actualTicket) {
            $this->authorize('reviewDelegation', $ticket);

            return redirect()
                ->route('admin.tickets.show', $ticket)
                ->with('error', 'La solicitud de delegación ya no existe o ya fue procesada.');
        }

        $this->authorize('reviewDelegation', $actualTicket);

        if ($delegationRequest->status !== TicketDelegationRequest::STATUS_PENDING) {
            return redirect()
                ->route('admin.tickets.show', $actualTicket)
                ->with('error', 'La solicitud de delegación ya fue procesada.');
        }

        $delegationRequest->update([
            'status' => TicketDelegationRequest::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->logger->delegationRejected($actualTicket, $delegationRequest->target, $request->user());

        return redirect()
            ->route('admin.tickets.show', $actualTicket)
            ->with('success', 'Solicitud de delegación rechazada.');
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
