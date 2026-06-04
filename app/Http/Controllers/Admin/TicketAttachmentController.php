<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\HandlesTicketAttachments;
use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Services\TicketActivityLoggerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TicketAttachmentController extends Controller
{
    use HandlesTicketAttachments, ValidatesAttachments;

    public function __construct(private TicketActivityLoggerService $logger) {}

    public function store(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('attach', $ticket);

        $request->validate($this->attachmentRules(), $this->attachmentMessages());

        $files = $request->file('attachments');
        $this->storeAttachments($ticket, $files, $request->user());

        foreach ((array) $files as $file) {
            $this->logger->attachmentAdded($ticket, $file->getClientOriginalName(), $request->user());
        }

        return back()->with('success', 'Archivo(s) adjuntado(s).');
    }

    public function destroy(Request $request, Ticket $ticket, TicketAttachment $attachment): RedirectResponse
    {
        $this->authorize('attach', $ticket);

        abort_unless($attachment->ticket_id === $ticket->id, 404);

        $name = $attachment->original_name;
        $attachment->delete(); // file removed via model deleting event

        $this->logger->attachmentRemoved($ticket, $name, $request->user());

        return back()->with('success', 'Archivo eliminado.');
    }
}
