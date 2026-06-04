<?php

namespace App\Http\Controllers\Admin;

use App\Events\TicketMessageCreated;
use App\Http\Controllers\Concerns\HandlesTicketAttachments;
use App\Http\Controllers\Concerns\SerializesTicketMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Services\TicketActivityLoggerService;
use App\Services\TicketNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TicketMessageController extends Controller
{
    use HandlesTicketAttachments, SerializesTicketMessages;

    public function __construct(
        private TicketActivityLoggerService $logger,
        private TicketNotificationService $notifier,
    ) {}

    public function index(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'messages' => $this->ticketMessagesPayload($ticket, $request->integer('after_id') ?: null),
        ]);
    }

    public function store(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $user = $request->user();
        $authorType = $user->isStaff()
            ? TicketMessage::AUTHOR_AGENT
            : TicketMessage::AUTHOR_CUSTOMER;
        $publicAuthor = $this->publicReplyAuthor($ticket, $user);

        $message = DB::transaction(function () use ($request, $ticket, $user, $authorType, $publicAuthor) {
            $message = $ticket->messages()->create([
                'user_id' => $authorType === TicketMessage::AUTHOR_AGENT ? $publicAuthor->id : null,
                'customer_id' => $authorType === TicketMessage::AUTHOR_CUSTOMER ? $ticket->customer_id : null,
                'author_type' => $authorType,
                'body' => $request->validated()['body'],
            ]);

            $this->storeAttachments($ticket, $request->file('attachments'), $user, $message->id);

            if ($authorType === TicketMessage::AUTHOR_AGENT && ! $ticket->first_response_at) {
                $ticket->first_response_at = now();
            }
            $ticket->last_activity_at = now();
            $ticket->save();

            $this->logger->replied($ticket, $authorType, $user);

            return $message;
        });

        $this->notifier->ticketReplied($ticket, $message);
        TicketMessageCreated::dispatch($ticket, $message);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->ticketMessagePayload($message),
            ], 201);
        }

        return back()->with('success', 'Respuesta enviada.');
    }

    private function publicReplyAuthor(Ticket $ticket, User $actor): User
    {
        if (! $actor->hasAnyRole(['Super Admin', 'Admin'])) {
            return $actor;
        }

        $ticket->loadMissing('assignee');

        return $ticket->assignee ?? $actor;
    }
}
