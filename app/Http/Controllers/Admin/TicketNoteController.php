<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketNoteRequest;
use App\Models\Ticket;
use App\Services\TicketActivityLoggerService;
use Illuminate\Http\RedirectResponse;

class TicketNoteController extends Controller
{
    public function __construct(private TicketActivityLoggerService $logger) {}

    public function store(StoreTicketNoteRequest $request, Ticket $ticket): RedirectResponse
    {
        $ticket->notes()->create([
            'user_id' => $request->user()->id,
            'body' => $request->validated()['body'],
        ]);

        $ticket->forceFill(['last_activity_at' => now()])->save();
        $this->logger->noteAdded($ticket, $request->user());

        return back()->with('success', 'Nota interna agregada.');
    }
}
