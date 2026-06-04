<?php

namespace App\Http\Controllers\Portal;

use App\Events\TicketMessageCreated;
use App\Http\Controllers\Concerns\HandlesTicketAttachments;
use App\Http\Controllers\Concerns\SerializesTicketMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\PortalStoreTicketRequest;
use App\Http\Requests\StoreTicketMessageRequest;
use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketMessage;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Services\TicketActivityLoggerService;
use App\Services\TicketCodeGeneratorService;
use App\Services\TicketNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class TicketController extends Controller
{
    use HandlesTicketAttachments, SerializesTicketMessages;

    public function __construct(
        private TicketActivityLoggerService $logger,
        private TicketNotificationService $notifier,
    ) {}

    public function index(): View
    {
        $customer = $this->customer();

        $tickets = Ticket::query()
            ->where('customer_id', $customer?->id ?? 0)
            ->with(['priority', 'status', 'category:id,name,slug,color', 'assignee:id,name'])
            ->latest()
            ->paginate(10)
            ->through(fn (Ticket $t) => [
                'id' => $t->id,
                'code' => $t->code,
                'subject' => $t->subject,
                'category' => $t->category?->only(['name', 'color']),
                'priority' => $t->priority?->only(['name', 'slug', 'color']),
                'status' => $t->status?->only(['name', 'slug', 'color']),
                'is_overdue' => $t->is_overdue,
                'created_at' => $t->created_at?->toIso8601String(),
                'last_activity_at' => $t->last_activity_at?->toIso8601String(),
            ]);

        return view('portal.tickets.index', [
            'tickets' => $tickets,
            'stats' => [
                'open' => Ticket::where('customer_id', $customer?->id ?? 0)->open()->count(),
                'total' => Ticket::where('customer_id', $customer?->id ?? 0)->count(),
            ],
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        return view('portal.tickets.create', [
            'categories' => TicketCategory::active()->ordered()->get(['id', 'name', 'slug', 'color']),
            'priorities' => TicketPriority::active()->ordered()->get(['id', 'name', 'slug', 'color']),
        ]);
    }

    public function store(PortalStoreTicketRequest $request): RedirectResponse
    {
        $customer = $this->customer();
        abort_unless($customer, HttpResponse::HTTP_FORBIDDEN);

        $data = $request->validated();
        // The customer is always the logged-in client; never trust the payload.
        $data['customer_id'] = $customer->id;
        $data['company_id'] = $customer->company_id;

        $ticket = DB::transaction(function () use ($data, $request, $customer) {
            $priority = TicketPriority::findOrFail($data['priority_id']);

            $ticket = new Ticket($data);
            $ticket->code = app(TicketCodeGeneratorService::class)->generate();
            $ticket->status_id = TicketStatus::defaultId();
            $ticket->assigned_to = null;
            $ticket->created_by = $request->user()->id;
            $ticket->due_at = now()->addHours($priority->resolution_hours);
            $ticket->last_activity_at = now();
            $ticket->save();

            $message = $ticket->messages()->create([
                'customer_id' => $customer->id,
                'author_type' => TicketMessage::AUTHOR_CUSTOMER,
                'body' => $ticket->description,
            ]);

            $this->storeAttachments($ticket, $request->file('attachments'), $request->user(), $message->id);
            $this->logger->created($ticket, $request->user());

            return $ticket;
        });

        $this->notifier->ticketCreated($ticket);

        return redirect()
            ->route('portal.tickets.show', $ticket)
            ->with('success', "Tu consulta {$ticket->code} fue creada. Te avisamos por email cuando respondamos.");
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket); // policy: customer can only see own ticket

        $ticket->load([
            'category', 'priority', 'status', 'assignee:id,name,avatar_path,job_title',
            'messages' => fn ($q) => $q->where('author_type', '!=', TicketMessage::AUTHOR_SYSTEM),
            'messages.user:id,name,avatar_path', 'messages.customer:id,name,avatar_path', 'messages.attachments',
        ]);

        return view('portal.tickets.show', [
            'ticket' => [
                'id' => $ticket->id,
                'code' => $ticket->code,
                'subject' => $ticket->subject,
                'category' => $ticket->category?->only(['name', 'color']),
                'priority' => $ticket->priority?->only(['name', 'slug', 'color']),
                'status' => $ticket->status?->only(['name', 'slug', 'color']),
                'agent' => $ticket->assignee ? [
                    'name' => $ticket->assignee->name,
                    'job_title' => $ticket->assignee->job_title,
                    'initials' => $ticket->assignee->initials(),
                    'avatar_url' => $ticket->assignee->avatarUrl(),
                ] : null,
                'created_at' => $ticket->created_at?->toIso8601String(),
                'resolved_at' => $ticket->resolved_at?->toIso8601String(),
                // NOTE: internal notes are deliberately never serialized here.
                'messages' => $ticket->messages->map(fn ($m) => [
                    'id' => $m->id,
                    'author_type' => $m->author_type,
                    'body' => $m->body,
                    'author' => $m->author_type === 'customer'
                        ? ['name' => $m->customer?->name ?? 'Vos', 'avatar_url' => $m->customer?->avatarUrl()]
                        : ['name' => $m->user?->name ?? 'Soporte', 'avatar_url' => $m->user?->avatarUrl()],
                    'attachments' => $m->attachments->map(fn ($a) => [
                        'id' => $a->id, 'name' => $a->original_name, 'url' => $a->url, 'size' => $a->human_size, 'is_image' => $a->is_image,
                    ]),
                    'created_at' => $m->created_at?->toIso8601String(),
                ]),
            ],
            'can' => ['reply' => auth()->user()->can('reply', $ticket)],
        ]);
    }

    public function messages(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        return response()->json([
            'messages' => $this->ticketMessagesPayload($ticket, $request->integer('after_id') ?: null),
        ]);
    }

    public function reply(StoreTicketMessageRequest $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $customer = $this->customer();

        $message = DB::transaction(function () use ($request, $ticket, $customer) {
            $message = $ticket->messages()->create([
                'customer_id' => $customer?->id,
                'author_type' => TicketMessage::AUTHOR_CUSTOMER,
                'body' => $request->validated()['body'],
            ]);

            $this->storeAttachments($ticket, $request->file('attachments'), $request->user(), $message->id);
            $ticket->forceFill(['last_activity_at' => now()])->save();
            $this->logger->replied($ticket, TicketMessage::AUTHOR_CUSTOMER, $request->user());

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

    private function customer(): ?Customer
    {
        return auth()->user()->customer;
    }
}
