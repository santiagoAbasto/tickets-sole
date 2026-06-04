<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateTicketCredentialRequest;
use App\Models\Ticket;
use Illuminate\Http\RedirectResponse;

class TicketCredentialController extends Controller
{
    /** Upsert the internal hosting/cPanel credentials for a ticket. Staff only. */
    public function update(UpdateTicketCredentialRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->credentials()->updateOrCreate(
            ['ticket_id' => $ticket->id],
            $request->validated(),
        );

        return back()->with('success', 'Credenciales internas guardadas.');
    }
}
