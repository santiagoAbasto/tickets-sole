@php
    use Illuminate\Support\Carbon;
    $timeline = collect($ticket['messages'])
        ->concat(collect($ticket['notes'] ?? [])->map(fn ($n) => array_merge($n, ['author_type' => 'note'])))
        ->sortBy('created_at')->values();
    $lastMessageId = collect($ticket['messages'])->max('id') ?? 0;
    $replyAsAgent = auth()->user()?->hasAnyRole(['Super Admin', 'Admin'])
        && data_get($ticket, 'agent.id')
        && data_get($ticket, 'agent.id') !== auth()->id();
    $actIcon = ['created'=>'plus','assigned'=>'user-plus','unassigned'=>'user-minus','claimed'=>'hand','status_changed'=>'refresh-cw','priority_changed'=>'flag','replied'=>'message-square','note_added'=>'lock','customer_notified'=>'mail-check','whatsapp_contacted'=>'message-circle','delegation_requested'=>'git-pull-request-arrow','delegation_approved'=>'check-check','delegation_rejected'=>'x','attachment_added'=>'paperclip','attachment_removed'=>'paperclip','resolved'=>'circle-check-big','closed'=>'circle-x','reopened'=>'rotate-ccw','escalated'=>'trending-up'];
    $fmtDt = fn ($d) => $d ? Carbon::parse($d)->translatedFormat('d M Y · H:i') : '—';
    $dayLabel = function ($c) {
        if (! $c) return '';
        if ($c->isToday()) return 'Hoy';
        if ($c->isYesterday()) return 'Ayer';
        return $c->translatedFormat('d M Y');
    };
    $origins = [
        'web' => ['label' => 'Web', 'icon' => 'globe', 'cls' => 'bg-sky-50 text-sky-700 ring-sky-200', 'title' => 'Enviado desde el formulario público'],
        'portal' => ['label' => 'Portal', 'icon' => 'user-round', 'cls' => 'bg-violet-50 text-violet-700 ring-violet-200', 'title' => 'Creado por el cliente desde el portal'],
        'admin' => ['label' => 'Interno', 'icon' => 'building-2', 'cls' => 'bg-slate-100 text-slate-600 ring-slate-200', 'title' => 'Cargado por el equipo'],
    ];
    $origin = $origins[$ticket['source'] ?? 'admin'] ?? $origins['admin'];
@endphp

<x-layouts.admin :title="$ticket['code']">
    <div class="space-y-5">
        <a href="{{ route('admin.tickets.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i> Volver a tickets</a>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <div class="flex items-center gap-2">
                    <span class="font-mono text-sm text-slate-400">{{ $ticket['code'] }}</span>
                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $origin['cls'] }}" title="{{ $origin['title'] }}"><i data-lucide="{{ $origin['icon'] }}" class="h-3 w-3"></i> {{ $origin['label'] }}</span>
                    @if ($ticket['is_overdue'])<x-overdue-badge :label="'Atrasado ' . $ticket['overdue_human']" />@endif
                </div>
                <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">{{ $ticket['subject'] }}</h1>
            </div>
            <div class="flex shrink-0 items-center gap-2"><x-status-badge :status="$ticket['status']" /><x-priority-badge :priority="$ticket['priority']" /></div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            {{-- Conversation --}}
            <div class="lg:col-span-2">
                <x-card class="flex flex-col">
                    <div class="border-b border-slate-200 p-5">
                        <div class="flex items-start gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100">
                                <i data-lucide="clipboard-list" class="h-5 w-5"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">Resumen interno del caso</p>
                                <p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $ticket['description'] }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4 p-5"
                         data-realtime-chat
                         data-endpoint="{{ route('admin.tickets.messages.index', $ticket['id']) }}"
                         data-last-message-id="{{ $lastMessageId }}"
                         data-customer-name="{{ data_get($ticket, 'customer.name') ?: 'Cliente' }}">
                        @php $lastDate = null; $prevItem = null; @endphp
                        @forelse ($timeline as $item)
                            @php
                                $created = ! empty($item['created_at']) ? Carbon::parse($item['created_at']) : null;
                                $dateKey = $created?->toDateString();
                                $showDay = $dateKey && $dateKey !== $lastDate;
                                $type = $item['author_type'] ?? 'agent';
                                $grouped = false;
                                if ($prevItem && ! $showDay && in_array($type, ['agent', 'customer'], true) && ($prevItem['author_type'] ?? null) === $type) {
                                    $prevTime = ! empty($prevItem['created_at']) ? Carbon::parse($prevItem['created_at']) : null;
                                    $sameAuthor = data_get($prevItem, 'author.name') === data_get($item, 'author.name');
                                    if ($sameAuthor && $prevTime && $created && $prevTime->diffInMinutes($created) <= 5) {
                                        $grouped = true;
                                    }
                                }
                                if ($showDay) { $lastDate = $dateKey; }
                                $prevItem = $item;
                            @endphp
                            @if ($showDay)
                                <div class="flex items-center justify-center pt-1">
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">{{ $dayLabel($created) }}</span>
                                </div>
                            @endif
                            <x-ticket.message :item="$item" :customer-name="data_get($ticket, 'customer.name')" :grouped="$grouped" />
                        @empty
                            <div data-chat-empty class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center">
                                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-slate-400 ring-1 ring-slate-200">
                                    <i data-lucide="messages-square" class="h-6 w-6"></i>
                                </span>
                                <h2 class="mt-4 text-sm font-semibold text-slate-900">El chat todavía está vacío</h2>
                                <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">Este ticket fue creado desde el panel interno. Avisá al cliente por email o escribí la primera respuesta cuando quieras iniciar la conversación.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($can['reply'] || $can['note'])
                        <div class="border-t border-slate-200 p-4" x-data="{ tab: '{{ $can['reply'] ? 'reply' : 'note' }}' }">
                            @if ($can['reply'] && $can['note'])
                                <div class="mb-3 flex items-center gap-1 border-b border-slate-200">
                                    <button type="button" @click="tab='reply'" :class="tab==='reply' ? 'text-brand-700 border-brand-600' : 'text-slate-500 border-transparent hover:text-slate-800'" class="border-b-2 px-3 py-2.5 text-sm font-medium">Responder</button>
                                    <button type="button" @click="tab='note'" :class="tab==='note' ? 'text-brand-700 border-brand-600' : 'text-slate-500 border-transparent hover:text-slate-800'" class="border-b-2 px-3 py-2.5 text-sm font-medium">Nota interna</button>
                                </div>
                            @endif

                            @if ($can['reply'])
                                <form x-show="tab==='reply'" method="POST" action="{{ route('admin.tickets.messages.store', $ticket['id']) }}" enctype="multipart/form-data" data-realtime-chat-form>
                                    @csrf
                                    @if ($replyAsAgent)
                                        <div class="mb-3 flex items-center gap-3 rounded-xl border border-brand-100 bg-brand-50/70 px-3 py-2 text-sm text-brand-800">
                                            <x-avatar :name="data_get($ticket, 'agent.name')" :src="data_get($ticket, 'agent.avatar_url')" size="sm" />
                                            <p>
                                                La respuesta pública saldrá como
                                                <span class="font-semibold">{{ data_get($ticket, 'agent.name') }}</span>.
                                                Tu usuario queda registrado solo en la actividad interna.
                                            </p>
                                        </div>
                                    @endif
                                    <textarea name="body" rows="3" class="textarea" placeholder="Escribí tu respuesta al cliente…">{{ old('body') }}</textarea>
                                    @error('body')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                                    <div class="mt-3"><x-attachment-input /></div>
                                    <div class="mt-3 flex justify-end">
                                        <x-button type="submit"><i data-lucide="send" class="h-4 w-4"></i> Enviar respuesta</x-button>
                                    </div>
                                </form>
                            @endif

                            @if ($can['note'])
                                <form x-show="tab==='note'" method="POST" action="{{ route('admin.tickets.notes.store', $ticket['id']) }}" @if(!$can['reply']) x-cloak @endif>
                                    @csrf
                                    <textarea name="body" rows="3" class="textarea bg-amber-50/40" placeholder="Nota visible solo para el equipo…"></textarea>
                                    <div class="mt-3 flex justify-end"><x-button type="submit" variant="secondary">Guardar nota</x-button></div>
                                </form>
                            @endif
                        </div>
                    @else
                        <div class="border-t border-slate-200 p-4">
                            <div class="flex flex-col gap-3 rounded-xl bg-slate-50 px-3.5 py-3 text-sm text-slate-600 ring-1 ring-inset ring-slate-200 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-center gap-2.5">
                                    <i data-lucide="eye" class="h-4 w-4 shrink-0 text-slate-400"></i>
                                    <p>
                                        @if (data_get($ticket,'agent.name'))
                                            Asignado a <span class="font-medium text-slate-800">{{ data_get($ticket,'agent.name') }}</span>. Podés verlo, pero no responder.
                                        @else
                                            Este ticket no tiene a nadie atendiéndolo. Podés verlo, pero no responder.
                                        @endif
                                    </p>
                                </div>
                                @if ($can['claim'])
                                    <form method="POST" action="{{ route('admin.tickets.claim', $ticket['id']) }}" class="shrink-0">
                                        @csrf
                                        <x-button type="submit" class="w-full sm:w-auto"><i data-lucide="hand" class="h-4 w-4"></i> Seguir ticket</x-button>
                                    </form>
                                @endif
                            </div>
                            @if ($can['claim'])
                                <p class="mt-2 text-xs leading-5 text-slate-500">Si lo seguís, el ticket pasa a ser <span class="font-medium text-slate-600">tuyo</span> y vas a poder responder con tu nombre. Queda registrado en la actividad.</p>
                            @endif
                        </div>
                    @endif
                </x-card>
            </div>

            {{-- Side panel --}}
            <div class="space-y-5">
                <x-card class="divide-y divide-slate-100">
                    <div class="p-5">
                        <h3 class="mb-3 text-sm font-semibold text-slate-900">Cliente</h3>
                        <div class="flex items-center gap-3">
                            <x-avatar :name="data_get($ticket,'customer.name')" :src="data_get($ticket,'customer.avatar_url')" size="md" />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium text-slate-800">{{ data_get($ticket,'customer.name') }}</p>
                                <p class="flex items-center gap-1 truncate text-xs text-slate-500"><i data-lucide="mail" class="h-3 w-3"></i> {{ data_get($ticket,'customer.email') ?: '—' }}</p>
                                @if (data_get($ticket,'customer.company'))<p class="flex items-center gap-1 text-xs text-slate-500"><i data-lucide="building-2" class="h-3 w-3"></i> {{ data_get($ticket,'customer.company') }}</p>@endif
                            </div>
                        </div>

                        {{-- Link DIRECTO y firmado: el cliente lo abre y entra al chat de su ticket al instante, sin código ni email --}}
                        @php $trackLink = \Illuminate\Support\Facades\URL::signedRoute('public.track.direct', ['ticket' => $ticket['id']]); @endphp
                        <div class="mt-4" x-data="{ copied: false }">
                            <button type="button"
                                    @click="navigator.clipboard.writeText(@js($trackLink)).then(() => { copied = true; setTimeout(() => copied = false, 2000) })"
                                    class="inline-flex w-full items-center justify-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 transition-colors hover:bg-slate-50">
                                <i data-lucide="link" class="h-4 w-4 text-slate-400"></i>
                                <span x-text="copied ? '¡Link copiado!' : 'Copiar link de seguimiento'"></span>
                            </button>
                            <p class="mt-1.5 text-xs leading-5 text-slate-400">Link directo y seguro: el cliente lo abre y ve el chat de su ticket al instante, sin cargar código ni email.</p>
                        </div>
                    </div>

                    @if ($can['notifyCustomer'])
                        <div class="p-5">
                            <h3 class="mb-3 text-sm font-semibold text-slate-900">Canales de contacto</h3>
                            <div class="space-y-3">
                                {{-- Email --}}
                                @if (data_get($ticket, 'customer.email'))
                                    <form method="POST" action="{{ route('admin.tickets.notify-customer', $ticket['id']) }}"
                                          class="flex items-center justify-between gap-3 rounded-xl bg-slate-50/70 p-3.5 ring-1 ring-inset ring-slate-200">
                                        @csrf
                                        <div class="flex min-w-0 items-center gap-2.5">
                                            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-sky-50 text-sky-600 ring-1 ring-inset ring-sky-100"><i data-lucide="mail" class="h-4 w-4"></i></span>
                                            <div class="min-w-0 leading-tight">
                                                <p class="text-sm font-semibold text-slate-900">Email</p>
                                                <p class="truncate text-xs text-slate-500">Código + enlace de seguimiento</p>
                                            </div>
                                        </div>
                                        <button type="submit" class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg bg-white px-3 text-sm font-medium text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2" title="Enviar el código del ticket y el enlace de seguimiento por email">
                                            <i data-lucide="send" class="h-3.5 w-3.5"></i> Avisar
                                        </button>
                                    </form>
                                @endif

                                {{-- WhatsApp --}}
                                <x-ticket.whatsapp-panel :ticket-id="$ticket['id']" :whatsapp="$whatsapp" />
                            </div>
                        </div>
                    @endif

                    <div class="space-y-3 p-5">
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-400">Estado</label>
                            @if ($can['changeStatus'])
                                <form method="POST" action="{{ route('admin.tickets.status', $ticket['id']) }}">@csrf
                                    <select name="status_id" onchange="this.form.submit()" class="select">
                                        @foreach ($options['statuses'] as $s)<option value="{{ $s['id'] }}" @selected($s['id'] === $ticket['status']['id'])>{{ $s['name'] }}</option>@endforeach
                                    </select>
                                </form>
                            @else<x-status-badge :status="$ticket['status']" />@endif
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium uppercase tracking-wide text-slate-400">Agente</label>
                            @if ($can['assign'])
                                <form method="POST" action="{{ route('admin.tickets.assign', $ticket['id']) }}">@csrf
                                    <select name="assigned_to" onchange="this.form.submit()" class="select">
                                        <option value="">Sin asignar</option>
                                        @foreach ($options['agents'] as $a)<option value="{{ $a['id'] }}" @selected(data_get($ticket,'agent.id') === $a['id'])>{{ $a['name'] }}</option>@endforeach
                                    </select>
                                </form>
                            @else<span class="text-sm text-slate-700">{{ data_get($ticket,'agent.name') ?? 'Sin asignar' }}</span>@endif
                        </div>

                        {{-- Delegación de soporte --}}
                        @if ($delegation)
                            <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5">
                                <p class="flex items-center gap-1.5 text-xs font-semibold text-amber-700"><i data-lucide="git-pull-request-arrow" class="h-3.5 w-3.5"></i> Delegación pendiente</p>
                                <p class="mt-1.5 text-sm text-slate-700">Pasar el soporte a <span class="font-medium">{{ $delegation['requested_to'] }}</span></p>
                                <p class="text-xs text-slate-500">Solicitado por {{ $delegation['requested_by'] }}</p>
                                @if ($delegation['note'])<p class="mt-1.5 whitespace-pre-wrap rounded-lg bg-white px-2.5 py-1.5 text-xs text-slate-600 ring-1 ring-inset ring-slate-200">{{ $delegation['note'] }}</p>@endif
                                @if ($can['reviewDelegation'])
                                    <div class="mt-3 flex gap-2">
                                        <form method="POST" action="{{ route('admin.tickets.delegations.approve', [$ticket['id'], $delegation['id']]) }}" class="flex-1">@csrf
                                            <button type="submit" class="inline-flex min-h-9 w-full items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-3 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2"><i data-lucide="check" class="h-4 w-4"></i> Aprobar</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.tickets.delegations.reject', [$ticket['id'], $delegation['id']]) }}">@csrf
                                            <button type="submit" class="inline-flex min-h-9 items-center justify-center rounded-lg bg-white px-3 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-200 transition-colors hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">Rechazar</button>
                                        </form>
                                    </div>
                                @elseif ($delegation['is_mine'])
                                    <form method="POST" action="{{ route('admin.tickets.delegations.cancel', [$ticket['id'], $delegation['id']]) }}" class="mt-3">@csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-medium text-slate-500 transition-colors hover:text-slate-700 hover:underline">Cancelar solicitud</button>
                                    </form>
                                @else
                                    <p class="mt-2 text-xs text-slate-500">Esperando la aprobación de un administrador.</p>
                                @endif
                            </div>
                        @elseif ($can['delegate'])
                            <div x-data="{ open: false }">
                                <button type="button" @click="open = ! open" class="inline-flex items-center gap-1.5 text-sm font-medium text-brand-600 transition-colors hover:text-brand-700">
                                    <i data-lucide="git-pull-request-arrow" class="h-4 w-4"></i> Delegar soporte
                                </button>
                                <form x-show="open" x-cloak method="POST" action="{{ route('admin.tickets.delegations.store', $ticket['id']) }}" class="mt-2 space-y-2"
                                      x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-1" x-transition:enter-end="opacity-100 translate-y-0">
                                    @csrf
                                    <p class="text-xs leading-5 text-slate-500">Pedí reasignar este ticket a otro agente. Un administrador debe aprobarlo.</p>
                                    <select name="requested_to" class="select">
                                        <option value="">Elegí un agente…</option>
                                        @foreach ($options['agents'] as $a)
                                            @if ($a['id'] !== data_get($ticket,'agent.id'))<option value="{{ $a['id'] }}" @selected(old('requested_to') == $a['id'])>{{ $a['name'] }}</option>@endif
                                        @endforeach
                                    </select>
                                    @error('requested_to')<p class="text-xs text-rose-600">{{ $message }}</p>@enderror
                                    <textarea name="note" rows="2" class="textarea" placeholder="Motivo (opcional)…">{{ old('note') }}</textarea>
                                    <div class="flex justify-end"><x-button type="submit" size="sm"><i data-lucide="send" class="h-4 w-4"></i> Enviar solicitud</x-button></div>
                                </form>
                            </div>
                        @endif
                    </div>

                    <dl class="space-y-2.5 p-5 text-sm">
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Categoría</dt><dd class="text-right font-medium" style="color: {{ data_get($ticket,'category.color') }}">{{ data_get($ticket,'category.name') }}</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Prioridad</dt><dd><x-priority-badge :priority="$ticket['priority']" /></dd></div>
                        @if (data_get($ticket,'department'))<div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Departamento</dt><dd class="font-medium text-slate-700">{{ data_get($ticket,'department.name') }}</dd></div>@endif
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Origen</dt><dd><span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $origin['cls'] }}"><i data-lucide="{{ $origin['icon'] }}" class="h-3 w-3"></i> {{ $origin['label'] }}</span></dd></div>
                        @if (data_get($ticket,'creator.name'))<div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Cargado por</dt><dd class="font-medium text-slate-700">{{ data_get($ticket,'creator.name') }}</dd></div>@endif
                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Creado</dt><dd class="text-slate-600">{{ $fmtDt($ticket['created_at']) }}</dd></div>
                        <div class="flex items-center justify-between gap-3"><dt class="inline-flex items-center gap-1 text-slate-400"><i data-lucide="calendar-clock" class="h-3.5 w-3.5"></i> Vence</dt>
                            <dd>@if ($ticket['is_overdue'])<span class="font-medium text-rose-600">{{ $ticket['overdue_human'] }} atrasado</span>@else<span class="text-slate-600">{{ $fmtDt($ticket['due_at']) }}</span>@endif</dd></div>
                        @if ($ticket['resolved_at'])<div class="flex items-center justify-between gap-3"><dt class="inline-flex items-center gap-1 text-slate-400"><i data-lucide="circle-check-big" class="h-3.5 w-3.5"></i> Resuelto</dt><dd class="text-slate-600">{{ $ticket['resolution_hours'] !== null ? number_format($ticket['resolution_hours'],1,',','.').' h' : '—' }}</dd></div>@endif
                    </dl>

                    @if (!empty($ticket['attachments']))
                        <div class="p-5">
                            <h3 class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-slate-900"><i data-lucide="paperclip" class="h-4 w-4"></i> Archivos ({{ count($ticket['attachments']) }})</h3>
                            <ul class="space-y-1">
                                @foreach ($ticket['attachments'] as $a)
                                    <li><a href="{{ $a['url'] }}" target="_blank" class="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm hover:bg-slate-50"><span class="truncate text-slate-600">{{ $a['name'] }}</span><span class="text-xs text-slate-400">{{ $a['size'] }}</span></a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- Eliminar ticket — Admin / Super Admin (para sacar duplicados) --}}
                    @if ($can['delete'])
                        <div class="p-5" x-data="{ confirm: false }">
                            <button type="button" @click="confirm = true" x-show="!confirm" class="inline-flex items-center gap-1.5 text-sm font-medium text-rose-600 transition-colors hover:text-rose-700">
                                <i data-lucide="trash-2" class="h-4 w-4"></i> Eliminar ticket
                            </button>
                            <div x-show="confirm" x-cloak class="space-y-2.5">
                                <p class="text-sm text-slate-600">¿Eliminar <span class="font-medium text-slate-800">{{ $ticket['code'] }}</span>? Se quita de la lista de tickets.</p>
                                <div class="flex flex-wrap gap-2">
                                    <form method="POST" action="{{ route('admin.tickets.destroy', $ticket['id']) }}">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="inline-flex min-h-9 items-center gap-1.5 rounded-lg bg-rose-600 px-3 text-sm font-semibold text-white transition-colors hover:bg-rose-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-500 focus-visible:ring-offset-2">
                                            <i data-lucide="trash-2" class="h-4 w-4"></i> Sí, eliminar
                                        </button>
                                    </form>
                                    <button type="button" @click="confirm = false" class="inline-flex min-h-9 items-center rounded-lg bg-white px-3 text-sm font-medium text-slate-600 ring-1 ring-inset ring-slate-200 transition-colors hover:bg-slate-50">Cancelar</button>
                                </div>
                            </div>
                        </div>
                    @endif
                </x-card>

                {{-- Internal credentials (staff only) --}}
                @if ($can['credentials'])
                    @php $cr = $ticket['credentials'] ?? null; @endphp
                    <div class="rounded-2xl border border-slate-200 bg-surface p-5 shadow-sm" x-data="{ editing: false, revealed: false, hasData: @js((bool) $cr), password: null, loadingPassword: false, editHosting: @js($cr['hosting_type'] ?? ''), editShow: false, copied: false, async revealPassword() { if (this.password !== null) { this.revealed = ! this.revealed; return; } this.loadingPassword = true; const response = await fetch(@js(route('admin.tickets.credentials.reveal-password', $ticket['id'])), { method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' } }); this.loadingPassword = false; if (!response.ok) return; const data = await response.json(); this.password = data.password || ''; this.revealed = true; }, async copyPassword() { if (this.password === null) { await this.revealPassword(); } this.copy(this.password); }, copy(t) { if (t) { navigator.clipboard?.writeText(t); this.copied = true; setTimeout(() => this.copied = false, 1200); } } }">
                        <div class="flex items-center justify-between gap-2">
                            <h3 class="flex items-center gap-1.5 text-sm font-semibold text-slate-900">
                                <i data-lucide="key-round" class="h-4 w-4 text-slate-400"></i> Acceso / credenciales
                                <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 ring-1 ring-inset ring-amber-200">Interno</span>
                            </h3>
                            <button type="button" @click="editing = ! editing; revealed = false" class="shrink-0 text-xs font-medium text-brand-600 hover:underline" x-text="editing ? 'Cancelar' : (hasData ? 'Editar' : 'Agregar')"></button>
                        </div>

                        <form method="POST" action="{{ route('admin.tickets.credentials.link-host', $ticket['id']) }}" class="mt-3 rounded-xl border border-slate-200 bg-slate-50/70 p-3">
                            @csrf
                            <label class="label">Vincular host cargado</label>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <select name="host_credential_id" class="select min-w-0 flex-1" @disabled($hostCredentials->isEmpty())>
                                    @if ($hostCredentials->isEmpty())
                                        <option value="">No hay hosts disponibles</option>
                                    @else
                                        <option value="">Elegí un host/acceso...</option>
                                        @foreach ($hostCredentials as $hostCredential)
                                            @php
                                                $hostName = $hostCredential->name ?: ($hostCredential->website_url ?: ($hostCredential->server_url ?: 'Host sin nombre'));
                                                $hostMeta = collect([$hostCredential->hosting_provider, $hostCredential->cpanel_user])->filter()->implode(' · ');
                                            @endphp
                                            <option value="{{ $hostCredential->id }}">
                                                {{ $hostName }}{{ $hostMeta ? ' — '.$hostMeta : '' }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-1.5 rounded-lg bg-white px-3 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50" @disabled($hostCredentials->isEmpty())>
                                    <i data-lucide="link-2" class="h-4 w-4 text-slate-400"></i>
                                    Vincular
                                </button>
                            </div>
                            <p class="mt-1.5 text-xs leading-5 text-slate-500">Solo visible para el equipo. Copia el acceso guardado al ticket interno sin exponerlo al cliente.</p>
                            @error('host_credential_id')
                                <p class="mt-1.5 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </form>

                        {{-- View mode --}}
                        <div x-show="!editing" class="mt-3">
                            @if ($cr)
                                <dl class="space-y-2.5 text-sm">
                                    @if ($cr['hosting_type'])
                                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Hosting</dt><dd class="text-right font-medium text-slate-700">{{ $cr['hosting_type'] === 'osole' ? 'Osole' : 'Externo' }}</dd></div>
                                    @endif
                                    @if ($cr['hosting_provider'])
                                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Plataforma</dt><dd class="text-right font-medium text-slate-700">{{ $cr['hosting_provider'] }}</dd></div>
                                    @endif
                                    @if ($cr['server_url'])
                                        <div class="flex items-start justify-between gap-3"><dt class="shrink-0 text-slate-400">Servidor</dt><dd class="min-w-0 truncate text-right"><a href="{{ $cr['server_url'] }}" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline">{{ $cr['server_url'] }}</a></dd></div>
                                    @endif
                                    @if ($cr['cpanel_user'])
                                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Usuario</dt><dd class="flex items-center gap-1.5"><span class="font-mono text-slate-700">{{ $cr['cpanel_user'] }}</span><button type="button" @click="copy(@js($cr['cpanel_user']))" class="text-slate-400 transition-colors hover:text-slate-600" aria-label="Copiar usuario"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></dd></div>
                                    @endif
                                    @if ($cr['has_password'])
                                        <div class="flex items-center justify-between gap-3"><dt class="text-slate-400">Contraseña</dt><dd class="flex items-center gap-1.5"><span class="font-mono text-slate-700" x-text="revealed ? (password || 'Sin contraseña') : '••••••••'"></span><button type="button" @click="revealPassword()" class="text-slate-400 transition-colors hover:text-slate-600 disabled:opacity-50" :aria-label="revealed ? 'Ocultar' : 'Mostrar'" :disabled="loadingPassword"><i data-lucide="eye" x-show="!revealed" class="h-3.5 w-3.5"></i><i data-lucide="eye-off" x-show="revealed" x-cloak class="h-3.5 w-3.5"></i></button><button type="button" @click="copyPassword()" class="text-slate-400 transition-colors hover:text-slate-600 disabled:opacity-50" aria-label="Copiar contraseña" :disabled="loadingPassword"><i data-lucide="copy" class="h-3.5 w-3.5"></i></button></dd></div>
                                    @endif
                                    @if ($cr['notes'])
                                        <div><dt class="mb-1 text-slate-400">Notas</dt><dd class="whitespace-pre-wrap text-slate-600">{{ $cr['notes'] }}</dd></div>
                                    @endif
                                </dl>
                                <p x-show="copied" x-cloak class="mt-2 text-xs font-medium text-emerald-600">Copiado ✓</p>
                            @else
                                <p class="text-sm text-slate-400">Sin credenciales cargadas. <button type="button" @click="editing = true" class="font-medium text-brand-600 hover:underline">Agregar</button></p>
                            @endif
                        </div>

                        {{-- Edit mode --}}
                        <form x-show="editing" x-cloak method="POST" action="{{ route('admin.tickets.credentials.update', $ticket['id']) }}" class="mt-3 space-y-3">
                            @csrf
                            @method('PUT')
                            <div>
                                <label class="label">Hosting</label>
                                <div class="flex gap-2">
                                    <label :class="editHosting==='osole' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'" class="flex flex-1 cursor-pointer items-center justify-center rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors"><input type="radio" name="hosting_type" value="osole" x-model="editHosting" class="sr-only"> Osole</label>
                                    <label :class="editHosting==='external' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'" class="flex flex-1 cursor-pointer items-center justify-center rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors"><input type="radio" name="hosting_type" value="external" x-model="editHosting" class="sr-only"> Externo</label>
                                </div>
                            </div>
                            <div>
                                <label class="label">Plataforma / Panel</label>
                                <select name="hosting_provider" class="select">
                                    <option value="">Seleccionar…</option>
                                    @foreach (['cPanel', 'Plesk', 'AWS', 'Digital Ocean', 'Otro'] as $p)
                                        <option value="{{ $p }}" @selected(($cr['hosting_provider'] ?? '') === $p)>{{ $p }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="label">URL del panel / servidor</label>
                                <input name="server_url" value="{{ $cr['server_url'] ?? '' }}" class="input" inputmode="url" placeholder="https://servidor:2083">
                            </div>
                            <div>
                                <label class="label">Usuario de servidor</label>
                                <input name="cpanel_user" value="{{ $cr['cpanel_user'] ?? '' }}" class="input" autocomplete="off">
                            </div>
                            <div>
                                <label class="label">Contraseña de servidor</label>
                                <div class="relative">
                                    <input :type="editShow ? 'text' : 'password'" name="cpanel_password" class="input pr-10" autocomplete="new-password" placeholder="Dejar vacío para mantener la actual">
                                    <button type="button" @click="editShow = ! editShow" :aria-label="editShow ? 'Ocultar' : 'Mostrar'" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600"><i x-show="!editShow" data-lucide="eye" class="h-4 w-4"></i><i x-show="editShow" x-cloak data-lucide="eye-off" class="h-4 w-4"></i></button>
                                </div>
                            </div>
                            <div>
                                <label class="label">Notas internas</label>
                                <textarea name="notes" rows="2" class="textarea">{{ $cr['notes'] ?? '' }}</textarea>
                            </div>
                            <div class="flex justify-end gap-2 pt-1">
                                <button type="button" @click="editing = false" class="inline-flex h-9 items-center rounded-lg px-3 text-sm font-medium text-slate-600 hover:bg-slate-100">Cancelar</button>
                                <x-button type="submit" size="sm"><i data-lucide="check" class="h-4 w-4"></i> Guardar</x-button>
                            </div>
                        </form>
                    </div>
                @endif

                {{-- Activity --}}
                <x-card class="p-5">
                    <h3 class="mb-4 text-sm font-semibold text-slate-900">Actividad</h3>
                    @if (empty($ticket['activity']))
                        <p class="px-1 text-sm text-slate-400">Sin actividad registrada.</p>
                    @else
                        <ol class="relative space-y-4 pl-1">
                            @foreach ($ticket['activity'] as $a)
                                <li class="relative flex gap-3">
                                    @if (!$loop->last)<span class="absolute left-[11px] top-7 h-[calc(100%-4px)] w-px bg-slate-200"></span>@endif
                                    <span class="relative z-10 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-slate-500 ring-4 ring-white"><i data-lucide="{{ $actIcon[$a['action']] ?? 'refresh-cw' }}" class="h-3 w-3"></i></span>
                                    <div class="min-w-0 pb-1"><p class="text-sm text-slate-700">{{ $a['description'] }}</p><p class="text-xs text-slate-400">{{ data_get($a,'user.name') ? data_get($a,'user.name').' · ' : '' }}{{ $fmtDt($a['created_at']) }}</p></div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </x-card>
            </div>
        </div>
    </div>
</x-layouts.admin>
