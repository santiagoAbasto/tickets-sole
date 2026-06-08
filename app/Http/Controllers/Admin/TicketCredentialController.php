<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTicketCredentialRequest;
use App\Models\HostCredential;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketCredentialController extends Controller
{
    /** Upsert the internal hosting/cPanel credentials for a ticket. Staff only. */
    public function update(UpdateTicketCredentialRequest $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validated();
        if (blank($data['cpanel_password'] ?? null)) {
            unset($data['cpanel_password']);
        }

        $credential = $ticket->credentials()->updateOrCreate(
            ['ticket_id' => $ticket->id],
            $data,
        );

        HostCredential::syncFromTicketCredential($credential, $request->user());

        return back()->with('success', 'Credenciales internas guardadas.');
    }

    public function revealPassword(Request $request, Ticket $ticket): JsonResponse
    {
        abort_unless($request->user()->isStaff() && $request->user()->can('view', $ticket), 403);

        $credential = $ticket->credentials()->first();

        Log::info('Ticket credential password revealed', [
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'password' => $credential?->cpanel_password,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, private');
    }

    /** Copy a saved host/access into the ticket's internal credentials. */
    public function linkHost(Request $request, Ticket $ticket): RedirectResponse
    {
        abort_unless($request->user()->isStaff() && $request->user()->can('view', $ticket), 403);

        $data = $request->validate([
            'host_credential_id' => ['required', 'integer'],
        ]);

        $host = HostCredential::query()
            ->visibleTo($request->user())
            ->findOrFail($data['host_credential_id']);

        $credential = $ticket->credentials()->updateOrCreate(
            ['ticket_id' => $ticket->id],
            [
                'cpanel_user' => $host->cpanel_user,
                'cpanel_password' => $host->cpanel_password,
                'server_url' => $host->server_url,
                'hosting_type' => $host->hosting_type,
                'hosting_provider' => $host->hosting_provider,
                'notes' => $host->notes,
            ],
        );

        HostCredential::syncFromTicketCredential($credential, $request->user());

        return back()->with('success', 'Host vinculado al ticket.');
    }
}
