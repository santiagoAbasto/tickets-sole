<?php

namespace App\Http\Controllers\Admin;

use App\Events\TicketUpdated;
use App\Http\Controllers\Concerns\HandlesTicketAttachments;
use App\Http\Controllers\Controller;
use App\Http\Requests\ChangeTicketStatusRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use App\Models\Customer;
use App\Models\Department;
use App\Models\HostCredential;
use App\Models\SiteSetting;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\TicketActivityLoggerService;
use App\Services\TicketCodeGeneratorService;
use App\Services\TicketNotificationService;
use App\Services\WhatsappTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class TicketController extends Controller
{
    use HandlesTicketAttachments;

    public function __construct(
        private TicketActivityLoggerService $logger,
        private TicketNotificationService $notifier,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $filters = $request->only([
            'search', 'status', 'priority', 'category', 'agent',
            'customer', 'date_from', 'date_to', 'flag', 'sort', 'direction',
        ]);

        // Default landing view: the user's own tickets. Explicit ?flag=all shows everything.
        if (! $request->has('flag')) {
            $filters['flag'] = 'mine';
        }

        $query = Ticket::query()
            ->with([
                'customer:id,name,email,avatar_path',
                'assignee:id,name,avatar_path',
                'priority', 'status', 'category:id,name,slug,color',
            ]);

        $this->applyVisibility($query, $request->user());
        $this->applyFilters($query, $filters);

        $sort = in_array($request->get('sort'), ['created_at', 'due_at', 'last_activity_at'], true)
            ? $request->get('sort') : 'created_at';
        $direction = $request->get('direction') === 'asc' ? 'asc' : 'desc';

        $tickets = $query->orderBy($sort, $direction)
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Ticket $t) => $this->transform($t));

        return view('admin.tickets.index', [
            'tickets' => $tickets,
            'filters' => (object) $filters,
            'options' => $this->filterOptions(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Ticket::class);

        return view('admin.tickets.create', [
            'options' => $this->formOptions(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $ticket = DB::transaction(function () use ($data, $request) {
            $priority = TicketPriority::findOrFail($data['priority_id']);
            $statusId = $data['status_id'] ?? TicketStatus::defaultId();

            // Resolve the customer by email — create one if it doesn't exist.
            $customer = Customer::firstOrCreate(
                ['email' => $data['customer_email']],
                ['name' => $data['customer_name'], 'phone' => $data['customer_phone'] ?? null],
            );

            $ticket = new Ticket($data);
            $ticket->customer_id = $customer->id;
            $ticket->company_id = $customer->company_id;
            $ticket->code = app(TicketCodeGeneratorService::class)->generate();
            $ticket->status_id = $statusId;
            $ticket->created_by = $request->user()->id;
            // Only Admin/Super Admin may pick the assignee; everyone else (and a blank
            // choice) falls to the default assignee configured in "Asignación de tickets".
            $ticket->assigned_to = ($request->user()->hasPermissionTo('tickets.assign') && filled($data['assigned_to'] ?? null))
                ? (int) $data['assigned_to']
                : SiteSetting::defaultAssigneeId();
            $ticket->due_at = now()->addHours($priority->resolution_hours);
            $ticket->last_activity_at = now();
            $ticket->save();

            $this->storeCredentials($ticket, $data);
            $this->storeAttachments($ticket, $request->file('attachments'), $request->user());
            $this->logger->created($ticket, $request->user());

            return $ticket;
        });

        $this->notifier->ticketCreated($ticket);

        return redirect()
            ->route('admin.tickets.show', $ticket)
            ->with('success', "Ticket {$ticket->code} creado correctamente.");
    }

    /**
     * Persist the optional internal hosting/cPanel credentials captured at creation.
     *
     * @param  array<string, mixed>  $data
     */
    private function storeCredentials(Ticket $ticket, array $data): void
    {
        $fields = [
            'cpanel_user' => $data['cpanel_user'] ?? null,
            'cpanel_password' => $data['cpanel_password'] ?? null,
            'server_url' => $data['server_url'] ?? null,
            'hosting_type' => $data['hosting_type'] ?? null,
            'hosting_provider' => $data['hosting_provider'] ?? null,
            'notes' => $data['credentials_notes'] ?? null,
        ];

        if (collect($fields)->filter(fn ($v) => filled($v))->isEmpty()) {
            return;
        }

        $credential = $ticket->credentials()->create($fields);

        HostCredential::syncFromTicketCredential($credential, request()->user());
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $isStaff = request()->user()->isStaff();

        $ticket->load([
            'customer.company', 'company', 'department', 'category', 'priority', 'status',
            'assignee:id,name,avatar_path,job_title', 'creator:id,name',
            'messages.user:id,name,avatar_path', 'messages.customer:id,name,avatar_path', 'messages.attachments',
            'attachments.uploader:id,name',
            'activityLogs.user:id,name',
        ]);

        if ($isStaff) {
            $ticket->load('notes.user:id,name,avatar_path', 'credentials', 'pendingDelegation.target:id,name', 'pendingDelegation.requester:id,name');
        }

        $user = request()->user();
        $canNotify = $user->can('notifyCustomer', $ticket);
        $pending = $isStaff ? $ticket->pendingDelegation : null;
        $hostCredentials = $isStaff
            ? HostCredential::query()
                ->visibleTo($user)
                ->latest()
                ->limit(100)
                ->get(['id', 'name', 'website_url', 'server_url', 'hosting_provider', 'cpanel_user'])
            : collect();

        return view('admin.tickets.show', [
            'ticket' => $this->transformDetail($ticket, $isStaff),
            'options' => $this->formOptions(),
            'hostCredentials' => $hostCredentials,
            'whatsapp' => $canNotify
                ? app(WhatsappTemplateService::class)->resolve($ticket, $user)
                : null,
            'delegation' => $pending ? [
                'id' => $pending->id,
                'requested_by' => $pending->requester?->name,
                'requested_to' => $pending->target?->name,
                'is_mine' => $pending->requested_by === $user->id,
                'note' => $pending->note,
            ] : null,
            'can' => [
                'reply' => $user->can('reply', $ticket),
                'note' => $user->can('addNote', $ticket),
                'assign' => $user->can('assign', $ticket),
                'changeStatus' => $user->can('changeStatus', $ticket),
                'update' => $user->can('update', $ticket),
                'attach' => $user->can('attach', $ticket),
                'notifyCustomer' => $canNotify,
                'credentials' => $isStaff,
                'delegate' => $user->can('delegate', $ticket),
                'reviewDelegation' => $user->can('reviewDelegation', $ticket),
                'claim' => $user->can('claim', $ticket),
                'delete' => $user->can('delete', $ticket),
            ],
        ]);
    }

    /**
     * "Seguir ticket": the acting agent takes the ticket for themselves so they
     * can answer it, pulling it away from the default assignee. Direct (no
     * approval) — that is what separates it from delegation.
     */
    public function claim(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('claim', $ticket);

        $agent = $request->user();

        $ticket->forceFill([
            'assigned_to' => $agent->id,
            'last_activity_at' => now(),
        ])->save();

        $this->logger->claimed($ticket, $agent);

        return back()->with('success', "Ahora seguís el ticket {$ticket->code}. Ya podés responder.");
    }

    public function notifyCustomer(Request $request, Ticket $ticket): RedirectResponse
    {
        $this->authorize('notifyCustomer', $ticket);

        $ticket->loadMissing('customer');

        if (! $ticket->customer?->email) {
            return back()->with('error', 'El cliente no tiene email cargado.');
        }

        $this->notifier->ticketCreatedForCustomer($ticket);
        $this->logger->customerNotified($ticket, $request->user());

        return back()->with('success', "Aviso enviado a {$ticket->customer->email}.");
    }

    public function update(UpdateTicketRequest $request, Ticket $ticket): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['priority_id']) && $data['priority_id'] != $ticket->priority_id) {
            $from = $ticket->priority->name;
            $to = TicketPriority::find($data['priority_id'])?->name ?? '—';
            $this->logger->priorityChanged($ticket, $from, $to, $request->user());
        }

        $ticket->fill($data);
        $ticket->last_activity_at = now();
        $ticket->save();

        return back()->with('success', 'Ticket actualizado.');
    }

    public function changeStatus(ChangeTicketStatusRequest $request, Ticket $ticket): RedirectResponse
    {
        $status = TicketStatus::findOrFail($request->validated()['status_id']);
        $from = $ticket->status->name;

        DB::transaction(function () use ($ticket, $status, $from, $request) {
            $ticket->status_id = $status->id;
            $ticket->last_activity_at = now();

            if ($status->is_resolved && ! $ticket->resolved_at) {
                $ticket->resolved_at = now();
            }
            if (! $status->is_resolved && ! $status->is_final) {
                $ticket->resolved_at = null; // reopened
            }
            if ($status->is_final) {
                $ticket->closed_at = now();
            } else {
                $ticket->closed_at = null;
            }

            $ticket->save();

            $this->logger->statusChanged($ticket, $from, $status->name, $request->user());

            if ($status->is_resolved) {
                $this->logger->resolved($ticket, $request->user());
            }
        });

        $this->notifier->statusChanged($ticket, $status->name);
        TicketUpdated::dispatch($ticket);

        return back()->with('success', "Estado actualizado a {$status->name}.");
    }

    public function destroy(Ticket $ticket): RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return redirect()
            ->route('admin.tickets.index')
            ->with('success', "Ticket {$ticket->code} eliminado.");
    }

    // ----------------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------------

    private function applyVisibility($query, User $user): void
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return;
        }

        // Staff (admins and agents) can browse every ticket. Agents narrow to
        // their own via the "Mis tickets" filter (flag=mine).
    }

    private function applyFilters($query, array $filters): void
    {
        $query
            ->when($filters['search'] ?? null, function ($q, $search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('code', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
                });
            })
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->whereHas('status', fn ($s) => $s->where('slug', $v)))
            ->when($filters['priority'] ?? null, fn ($q, $v) => $q->whereHas('priority', fn ($s) => $s->where('slug', $v)))
            ->when($filters['category'] ?? null, fn ($q, $v) => $q->whereHas('category', fn ($s) => $s->where('slug', $v)))
            ->when($filters['agent'] ?? null, fn ($q, $v) => $q->where('assigned_to', $v))
            ->when($filters['customer'] ?? null, fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['date_from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filters['date_to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->when($filters['flag'] ?? null, function ($q, $flag) {
                match ($flag) {
                    'mine' => $q->where('assigned_to', request()->user()?->id),
                    'overdue' => $q->overdue(),
                    'resolved' => $q->resolved(),
                    'unassigned' => $q->unassigned(),
                    'open' => $q->open(),
                    default => null,
                };
            });
    }

    private function transform(Ticket $t): array
    {
        return [
            'id' => $t->id,
            'code' => $t->code,
            'subject' => $t->subject,
            'customer' => $t->customer?->only(['id', 'name', 'email']),
            'agent' => $t->assignee ? [
                'id' => $t->assignee->id,
                'name' => $t->assignee->name,
                'avatar_url' => $t->assignee->avatarUrl(),
            ] : null,
            'category' => $t->category?->only(['name', 'color']),
            'priority' => $t->priority?->only(['name', 'slug', 'color']),
            'status' => $t->status?->only(['name', 'slug', 'color']),
            'source' => $t->source,
            'is_overdue' => $t->is_overdue,
            'overdue_human' => $t->overdueForHumans(),
            'due_at' => $t->due_at?->toIso8601String(),
            'created_at' => $t->created_at?->toIso8601String(),
        ];
    }

    private function transformDetail(Ticket $t, bool $isStaff): array
    {
        return [
            'id' => $t->id,
            'code' => $t->code,
            'subject' => $t->subject,
            'description' => $t->description,
            'customer' => $t->customer ? [
                'id' => $t->customer->id,
                'name' => $t->customer->name,
                'email' => $t->customer->email,
                'phone' => $t->customer->phone,
                'company' => $t->customer->company?->name,
                'initials' => $t->customer->initials(),
                'avatar_url' => $t->customer->avatarUrl(),
            ] : null,
            'company' => $t->company?->only(['id', 'name']),
            'department' => $t->department?->only(['id', 'name', 'color']),
            'category' => $t->category?->only(['id', 'name', 'slug', 'color']),
            'priority' => $t->priority?->only(['id', 'name', 'slug', 'color']),
            'status' => $t->status?->only(['id', 'name', 'slug', 'color']),
            'agent' => $t->assignee ? [
                'id' => $t->assignee->id,
                'name' => $t->assignee->name,
                'job_title' => $t->assignee->job_title,
                'avatar_url' => $t->assignee->avatarUrl(),
                'initials' => $t->assignee->initials(),
            ] : null,
            'creator' => $t->creator?->only(['id', 'name']),
            'source' => $t->source,
            'is_overdue' => $t->is_overdue,
            'overdue_human' => $t->overdueForHumans(),
            'resolution_hours' => $t->resolutionHours(),
            'due_at' => $t->due_at?->toIso8601String(),
            'first_response_at' => $t->first_response_at?->toIso8601String(),
            'resolved_at' => $t->resolved_at?->toIso8601String(),
            'closed_at' => $t->closed_at?->toIso8601String(),
            'created_at' => $t->created_at?->toIso8601String(),
            'messages' => $t->messages->map(fn ($m) => [
                'id' => $m->id,
                'author_type' => $m->author_type,
                'body' => $m->body,
                'author' => $m->author_type === 'customer'
                    ? [
                        'name' => $m->customer?->name ?? $t->customer?->name,
                        'avatar_url' => $m->customer?->avatarUrl(),
                    ]
                    : [
                        'name' => $m->user?->name ?? 'Soporte',
                        'avatar_url' => $m->user?->avatarUrl(),
                    ],
                'attachments' => $m->attachments->map(fn ($a) => $this->attachmentArray($a)),
                'created_at' => $m->created_at?->toIso8601String(),
            ]),
            'notes' => $isStaff ? $t->notes->map(fn ($n) => [
                'id' => $n->id,
                'body' => $n->body,
                'channel' => $n->channel,
                'author' => [
                    'name' => $n->user?->name,
                    'avatar_url' => $n->user?->avatarUrl(),
                ],
                'created_at' => $n->created_at?->toIso8601String(),
            ]) : [],
            'credentials' => ($isStaff && $t->credentials) ? [
                'cpanel_user' => $t->credentials->cpanel_user,
                'has_password' => filled($t->credentials->cpanel_password),
                'server_url' => $t->credentials->server_url,
                'hosting_type' => $t->credentials->hosting_type,
                'hosting_provider' => $t->credentials->hosting_provider,
                'notes' => $t->credentials->notes,
            ] : null,
            'attachments' => $t->attachments->map(fn ($a) => $this->attachmentArray($a)),
            'activity' => $t->activityLogs->map(fn ($l) => [
                'id' => $l->id,
                'action' => $l->action,
                'description' => $l->description,
                'user' => $l->user?->only(['name']),
                'created_at' => $l->created_at?->toIso8601String(),
            ]),
        ];
    }

    private function attachmentArray($a): array
    {
        return [
            'id' => $a->id,
            'name' => $a->original_name,
            'url' => $a->url,
            'size' => $a->human_size,
            'is_image' => $a->is_image,
            'message_id' => $a->message_id,
        ];
    }

    /** Lightweight options for filter dropdowns. */
    private function filterOptions(): array
    {
        return [
            'statuses' => TicketStatus::ordered()->get(['name', 'slug', 'color']),
            'priorities' => TicketPriority::ordered()->get(['name', 'slug', 'color']),
            'categories' => TicketCategory::ordered()->get(['name', 'slug', 'color']),
            'agents' => User::agents()->active()->get(['id', 'name']),
        ];
    }

    /** Full options for create/edit forms. */
    private function formOptions(): array
    {
        return [
            'statuses' => TicketStatus::ordered()->get(['id', 'name', 'slug', 'color', 'is_final', 'is_resolved']),
            'priorities' => TicketPriority::ordered()->get(['id', 'name', 'slug', 'color']),
            'categories' => TicketCategory::active()->ordered()->get(['id', 'name', 'slug', 'color']),
            'departments' => Department::orderBy('sort_order')->get(['id', 'name', 'color']),
            'agents' => User::agents()->active()->get(['id', 'name', 'job_title']),
            'customers' => Customer::active()->orderBy('name')->get(['id', 'name', 'email', 'company_id']),
        ];
    }
}
