<?php

namespace App\Http\Controllers\Public;

use App\Events\TicketMessageCreated;
use App\Http\Controllers\Concerns\HandlesTicketAttachments;
use App\Http\Controllers\Concerns\SerializesTicketMessages;
use App\Http\Controllers\Controller;
use App\Http\Requests\Concerns\ValidatesAttachments;
use App\Http\Requests\SubmitPublicTicketRequest;
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

class PublicTicketController extends Controller
{
    use HandlesTicketAttachments, SerializesTicketMessages, ValidatesAttachments;

    public function __construct(
        private TicketActivityLoggerService $logger,
        private TicketNotificationService $notifier,
    ) {}

    public function create(): View
    {
        return view('public.support', [
            'categories' => TicketCategory::active()->ordered()->get(['id', 'name', 'slug']),
        ]);
    }

    public function store(SubmitPublicTicketRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $ticket = DB::transaction(function () use ($data, $request) {
            // Reuse an existing customer by email; never overwrite their data.
            $customer = Customer::firstOrCreate(
                ['email' => $data['email']],
                ['name' => $data['name'], 'phone' => $data['phone'] ?? null],
            );

            $priority = TicketPriority::where('slug', 'media')->first() ?? TicketPriority::ordered()->first();

            // Prepend the referenced website to the description when provided.
            $body = filled($data['site_url'] ?? null)
                ? "🌐 Sitio web: {$data['site_url']}\n\n{$data['description']}"
                : $data['description'];

            $ticket = new Ticket([
                'customer_id' => $customer->id,
                'company_id' => $customer->company_id,
                'category_id' => $data['category_id'],
                'priority_id' => $priority->id,
                'subject' => $data['subject'],
                'description' => $body,
                'source' => 'web',
            ]);
            $ticket->code = app(TicketCodeGeneratorService::class)->generate();
            $ticket->status_id = TicketStatus::defaultId();
            $ticket->created_by = null; // anonymous public submission
            $ticket->due_at = now()->addHours($priority->resolution_hours);
            $ticket->last_activity_at = now();
            $ticket->save();

            $message = $ticket->messages()->create([
                'customer_id' => $customer->id,
                'author_type' => TicketMessage::AUTHOR_CUSTOMER,
                'body' => $body,
            ]);

            $this->storeAttachments($ticket, $request->file('attachments'), null, $message->id);
            $this->logger->log($ticket, 'created', 'Ticket creado desde el formulario público');

            return $ticket;
        });

        $this->notifier->ticketCreated($ticket);

        return redirect()
            ->route('public.support.thanks')
            ->with('ticket_code', $ticket->code);
    }

    public function thanks(): View|RedirectResponse
    {
        $code = session('ticket_code');

        if (! $code) {
            return redirect()->route('public.support.create');
        }

        return view('public.thanks', ['code' => $code]);
    }

    // ----------------------------------------------------------------------
    // Public ticket tracking (code + email, no account)
    // ----------------------------------------------------------------------

    public function track(): View
    {
        return view('public.track');
    }

    /** Verify code + customer email, then unlock the ticket in the session. */
    public function lookup(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:40'],
            'email' => ['required', 'email', 'max:180'],
        ], [
            'code.required' => 'Ingresá el código de tu ticket.',
            'email.required' => 'Ingresá el email con el que lo creaste.',
        ]);

        $ticket = Ticket::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($data['code']))])
            ->whereHas('customer', fn ($q) => $q->whereRaw('LOWER(email) = ?', [mb_strtolower(trim($data['email']))]))
            ->first();

        if (! $ticket) {
            return back()->withInput()->with('error', 'No encontramos un ticket con ese código y email. Revisá los datos.');
        }

        session(['tracked_ticket_id' => $ticket->id]);

        return redirect()->route('public.track.show');
    }

    public function tracked(): View|RedirectResponse
    {
        $id = session('tracked_ticket_id');

        if (! $id || ! ($ticket = Ticket::find($id))) {
            session()->forget('tracked_ticket_id');

            return redirect()->route('public.track.form');
        }

        $ticket->load([
            'category', 'priority', 'status', 'assignee:id,name,avatar_path,job_title',
            'messages' => fn ($q) => $q->where('author_type', '!=', TicketMessage::AUTHOR_SYSTEM),
            'messages.user:id,name,avatar_path', 'messages.customer:id,name,avatar_path', 'messages.attachments',
        ]);

        return view('public.tracked', ['ticket' => $this->trackedPayload($ticket)]);
    }

    public function trackedMessages(Request $request): JsonResponse
    {
        $id = session('tracked_ticket_id');

        if (! $id || ! ($ticket = Ticket::find($id))) {
            return response()->json(['messages' => []], 403);
        }

        return response()->json([
            'messages' => $this->ticketMessagesPayload($ticket, $request->integer('after_id') ?: null),
        ]);
    }

    public function trackedReply(Request $request): JsonResponse|RedirectResponse
    {
        $id = session('tracked_ticket_id');

        if (! $id || ! ($ticket = Ticket::find($id))) {
            return redirect()->route('public.track.form');
        }

        $data = $request->validate(
            array_merge(['body' => ['required', 'string', 'max:20000']], $this->attachmentRules()),
            ['body.required' => 'Escribí tu respuesta.'],
        );

        $message = DB::transaction(function () use ($data, $request, $ticket) {
            $message = $ticket->messages()->create([
                'customer_id' => $ticket->customer_id,
                'author_type' => TicketMessage::AUTHOR_CUSTOMER,
                'body' => $data['body'],
            ]);

            $this->storeAttachments($ticket, $request->file('attachments'), null, $message->id);
            $ticket->forceFill(['last_activity_at' => now()])->save();
            $this->logger->replied($ticket, TicketMessage::AUTHOR_CUSTOMER);

            return $message;
        });

        $this->notifier->ticketReplied($ticket, $message);
        TicketMessageCreated::dispatch($ticket->fresh(), $message);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->ticketMessagePayload($message),
            ], 201);
        }

        return back()->with('success', 'Respuesta enviada. Te avisamos por email cuando respondamos.');
    }

    /**
     * @return array<string, mixed>
     */
    private function trackedPayload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'code' => $ticket->code,
            'subject' => $ticket->subject,
            'category' => $ticket->category?->only(['name', 'color']),
            'priority' => $ticket->priority?->only(['name', 'slug', 'color']),
            'status' => $ticket->status?->only(['name', 'slug', 'color']),
            'agent' => $ticket->assignee ? ['name' => $ticket->assignee->name, 'job_title' => $ticket->assignee->job_title, 'avatar_url' => $ticket->assignee->avatarUrl()] : null,
            'created_at' => $ticket->created_at?->toIso8601String(),
            'resolved_at' => $ticket->resolved_at?->toIso8601String(),
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
        ];
    }
}
