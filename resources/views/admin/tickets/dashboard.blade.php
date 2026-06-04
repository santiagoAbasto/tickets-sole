@php
    use Illuminate\Support\Carbon;

    $iconMap = [
        'Tickets' => 'ticket',
        'Inbox' => 'inbox',
        'Loader' => 'loader',
        'AlarmClock' => 'alarm-clock',
        'CheckCircle' => 'circle-check-big',
        'CalendarCheck' => 'calendar-check-2',
        'Zap' => 'zap',
        'Timer' => 'timer',
    ];

    $toneMap = [
        'brand' => ['icon' => 'bg-brand-50 text-brand-700 ring-brand-200', 'panel' => 'dashboard-metric-brand'],
        'blue' => ['icon' => 'bg-sky-50 text-sky-700 ring-sky-200', 'panel' => 'dashboard-metric-blue'],
        'indigo' => ['icon' => 'bg-indigo-50 text-indigo-700 ring-indigo-200', 'panel' => 'dashboard-metric-indigo'],
        'danger' => ['icon' => 'bg-rose-50 text-rose-700 ring-rose-200', 'panel' => 'dashboard-metric-danger'],
        'success' => ['icon' => 'bg-emerald-50 text-emerald-700 ring-emerald-200', 'panel' => 'dashboard-metric-success'],
        'amber' => ['icon' => 'bg-amber-50 text-amber-700 ring-amber-200', 'panel' => 'dashboard-metric-amber'],
        'violet' => ['icon' => 'bg-violet-50 text-violet-700 ring-violet-200', 'panel' => 'dashboard-metric-violet'],
    ];

    $fmt = function ($v, $format) {
        if ($v === null) return 'Sin dato';

        return match ($format) {
            'hours' => number_format($v, 1, ',', '.').' h',
            'percent' => $v.'%',
            default => number_format($v, 0, ',', '.'),
        };
    };

    $reportsHref = auth()->user()?->can('reports.view') ? route('admin.reports.index') : route('admin.tickets.index');

    $cardLinks = [
        'total' => route('admin.tickets.index'),
        'open' => route('admin.tickets.index', ['flag' => 'open']),
        'in_process' => route('admin.tickets.index', ['flag' => 'open']),
        'overdue' => route('admin.tickets.index', ['flag' => 'overdue']),
        'resolved_today' => route('admin.tickets.index', ['flag' => 'resolved']),
        'resolved_month' => route('admin.tickets.index', ['flag' => 'resolved']),
        'avg_first_response' => $reportsHref,
        'avg_resolution' => $reportsHref,
    ];

    $severityFor = function (float|int|null $hours) {
        $hours = (float) ($hours ?? 0);
        $heat = min(82, max(16, 14 + sqrt(max($hours, 0)) * 9));
        $meter = min(100, max(10, round($hours / 72 * 100)));

        if ($hours >= 72) {
            return ['label' => 'Crítico extremo', 'icon' => 'siren', 'color' => '#991b1b', 'heat' => $heat, 'meter' => $meter];
        }

        if ($hours >= 24) {
            return ['label' => 'Crítico', 'icon' => 'flame', 'color' => '#dc2626', 'heat' => $heat, 'meter' => $meter];
        }

        if ($hours >= 8) {
            return ['label' => 'Alto', 'icon' => 'triangle-alert', 'color' => '#e11d48', 'heat' => $heat, 'meter' => $meter];
        }

        if ($hours >= 4) {
            return ['label' => 'Atención', 'icon' => 'alarm-clock', 'color' => '#f43f5e', 'heat' => $heat, 'meter' => $meter];
        }

        return ['label' => 'Leve', 'icon' => 'clock', 'color' => '#fb7185', 'heat' => $heat, 'meter' => $meter];
    };

    $ticketState = function (array $ticket) use ($severityFor) {
        if ($ticket['is_overdue'] ?? false) {
            $severity = $severityFor((float) ($ticket['overdue_hours'] ?? 0));

            return [
                'label' => $severity['label'],
                'icon' => $severity['icon'],
                'class' => 'ticket-row-overdue',
                'style' => "--sla-heat: {$severity['heat']}%; --sla-color: {$severity['color']};",
                'tone' => 'text-rose-700 bg-rose-50 ring-rose-200',
            ];
        }

        $created = ! empty($ticket['created_at']) ? Carbon::parse($ticket['created_at']) : null;
        if ($created && $created->greaterThanOrEqualTo(now()->subDay())) {
            return [
                'label' => 'Nuevo',
                'icon' => 'sparkles',
                'class' => 'ticket-row-fresh',
                'style' => '',
                'tone' => 'text-sky-700 bg-sky-50 ring-sky-200',
            ];
        }

        $dueAt = ! empty($ticket['due_at']) ? Carbon::parse($ticket['due_at']) : null;
        if ($dueAt && $dueAt->isFuture() && $dueAt->lessThanOrEqualTo(now()->addHours(8))) {
            return [
                'label' => 'Por vencer',
                'icon' => 'clock',
                'class' => 'ticket-row-due',
                'style' => '',
                'tone' => 'text-amber-700 bg-amber-50 ring-amber-200',
            ];
        }

        return [
            'label' => 'En cola',
            'icon' => 'circle-dot',
            'class' => 'ticket-row-normal',
            'style' => '',
            'tone' => 'text-slate-600 bg-slate-50 ring-slate-200',
        ];
    };

    $oldestHours = $queuePulse['oldest_overdue_hours'] ?? null;
    $operationFocus = match (true) {
        ($queuePulse['critical_overdue'] ?? 0) > 0 => 'Rescatar tickets críticos antes de abrir nueva cola.',
        ($queuePulse['due_soon_8h'] ?? 0) > 0 => 'Cerrar o responder lo que vence durante la jornada.',
        ($queuePulse['unassigned'] ?? 0) > 0 => 'Asignar tickets sin dueño para evitar puntos ciegos.',
        default => 'Mantener ritmo de respuesta y documentar los cierres.',
    };
@endphp

<x-layouts.admin title="Dashboard">
    <div class="mx-auto max-w-[1500px] space-y-6">
        <section class="dashboard-command overflow-hidden rounded-2xl border border-slate-800 bg-slate-950 px-5 py-5 text-white shadow-sm sm:px-6 lg:px-7">
            <div class="relative z-10 grid gap-6 lg:grid-cols-[minmax(0,1fr)_minmax(22rem,32rem)] lg:items-end">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/8 px-2.5 py-1 text-xs font-semibold text-sky-100 ring-1 ring-white/12">
                            <i data-lucide="radio-tower" class="h-3.5 w-3.5"></i>
                            {{ $agentView ? 'Vista agente' : 'Vista equipo' }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-400/10 px-2.5 py-1 text-xs font-semibold text-emerald-100 ring-1 ring-emerald-300/20">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                            Operación activa
                        </span>
                    </div>
                    <h1 class="mt-4 text-2xl font-semibold text-white sm:text-3xl">Panel de operación</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                        {{ $operationFocus }}
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2">
                        <x-button :href="route('admin.tickets.create')" size="sm">
                            <i data-lucide="plus" class="h-4 w-4"></i>
                            Crear ticket
                        </x-button>
                        <a href="{{ route('admin.tickets.index', ['flag' => 'overdue']) }}" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-white/10 px-3 text-xs font-medium text-white ring-1 ring-inset ring-white/15 transition-colors hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <i data-lucide="alarm-clock" class="h-4 w-4"></i>
                            Revisar atrasados
                        </a>
                        <a href="{{ route('admin.tickets.index', ['flag' => 'unassigned']) }}" class="inline-flex h-8 items-center justify-center gap-1.5 rounded-lg bg-white/10 px-3 text-xs font-medium text-white ring-1 ring-inset ring-white/15 transition-colors hover:bg-white/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-400">
                            <i data-lucide="user-round-check" class="h-4 w-4"></i>
                            Asignar cola
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5">
                    @foreach ([
                        ['label' => 'Nuevos 24 h', 'value' => $queuePulse['new_24h'] ?? 0, 'icon' => 'sparkles', 'tone' => 'text-sky-200 bg-sky-400/10 ring-sky-300/20'],
                        ['label' => 'Vencen 8 h', 'value' => $queuePulse['due_soon_8h'] ?? 0, 'icon' => 'clock', 'tone' => 'text-amber-100 bg-amber-400/10 ring-amber-300/20'],
                        ['label' => 'Críticos +24 h', 'value' => $queuePulse['critical_overdue'] ?? 0, 'icon' => 'flame', 'tone' => 'text-rose-100 bg-rose-400/12 ring-rose-300/25'],
                        ['label' => 'Sin asignar', 'value' => $queuePulse['unassigned'] ?? 0, 'icon' => 'user-x', 'tone' => 'text-indigo-100 bg-indigo-400/10 ring-indigo-300/20'],
                    ] as $pulse)
                        <div class="rounded-xl border border-white/10 bg-white/[0.045] p-3">
                            <div class="flex items-center justify-between gap-3">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg ring-1 {{ $pulse['tone'] }}">
                                    <i data-lucide="{{ $pulse['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span class="text-xl font-semibold tabular-nums text-white">{{ number_format($pulse['value'], 0, ',', '.') }}</span>
                            </div>
                            <p class="mt-2 text-xs font-medium text-slate-300">{{ $pulse['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            @foreach ($cards as $i => $card)
                @php
                    $trend = $card['trend'] ?? null;
                    $isUp = $trend && $trend['direction'] === 'up';
                    $good = $trend ? (($card['lower_is_better'] ?? false) ? ! $isUp : $isUp) : null;
                    $tone = $toneMap[$card['tone']] ?? $toneMap['brand'];
                @endphp
                <a href="{{ $cardLinks[$card['key']] ?? route('admin.tickets.index') }}"
                   class="dashboard-metric {{ $tone['panel'] }} animate-rise group rounded-2xl border border-slate-200 bg-surface p-4 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-slate-300 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                   style="animation-delay: {{ $i * 35 }}ms">
                    <div class="flex items-center justify-between gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1 {{ $tone['icon'] }}">
                            <i data-lucide="{{ $iconMap[$card['icon']] ?? 'ticket' }}" class="h-5 w-5"></i>
                        </span>
                        @if ($trend && $trend['direction'] !== 'flat')
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $good ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-rose-200' }}">
                                <i data-lucide="{{ $isUp ? 'trending-up' : 'trending-down' }}" class="h-3 w-3"></i>
                                {{ $trend['percent'] }}%
                            </span>
                        @else
                            <i data-lucide="arrow-up-right" class="h-4 w-4 text-slate-300 transition group-hover:translate-x-0.5 group-hover:-translate-y-0.5 group-hover:text-slate-500"></i>
                        @endif
                    </div>
                    <p class="mt-3 text-2xl font-semibold text-slate-950 tabular-nums">{{ $fmt($card['value'], $card['format']) }}</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-600">{{ $card['label'] }}</p>
                    @if (! empty($card['caption']))
                        <p class="mt-1 text-xs leading-5 text-slate-500">{{ $card['caption'] }}</p>
                    @endif
                </a>
            @endforeach
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.35fr)_minmax(22rem,0.65fr)]">
            <x-card class="flex h-full flex-col overflow-hidden border-rose-900/20 bg-rose-950">
                <div class="flex flex-col gap-3 border-b border-white/10 bg-rose-950 px-5 py-4 text-white sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-rose-100 ring-1 ring-white/15">
                            <i data-lucide="shield-alert" class="h-5 w-5"></i>
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-white">SLA en riesgo</h2>
                            <p class="text-xs leading-5 text-rose-100/75">
                                {{ $oldestHours ? 'Mayor atraso: '.number_format($oldestHours, 1, ',', '.').' h' : 'Sin tickets vencidos por ahora' }}
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('admin.tickets.index', ['flag' => 'overdue']) }}" class="inline-flex items-center gap-1.5 rounded-lg bg-white/10 px-2.5 py-1.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/15 transition hover:bg-white/15">
                        Ver cola SLA
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>

                @if (count($overdueTickets) === 0)
                    <x-empty-state icon="circle-check-big" title="SLA bajo control" description="No hay tickets fuera de plazo. Aprovechá para cerrar pendientes o documentar respuestas." />
                @else
                    <div class="flex flex-1 flex-col divide-y divide-white/10 bg-rose-950">
                        @foreach ($overdueTickets as $t)
                            @php
                                $severity = $severityFor((float) ($t['overdue_hours'] ?? 0));
                                $priorityColor = data_get($t, 'priority.color', '#fecdd3');
                            @endphp
                            <a href="{{ route('admin.tickets.show', $t['id']) }}"
                               class="sla-heat-row group grid flex-1 gap-3 px-5 py-4 transition md:grid-cols-[minmax(0,1fr)_11rem_8rem] md:items-center"
                               style="--sla-heat: {{ $severity['heat'] }}%; --sla-meter: {{ $severity['meter'] }}%; --sla-color: {{ $severity['color'] }};">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs font-semibold text-rose-100/75">{{ $t['code'] }}</span>
                                        @if ($t['priority'])
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-2 py-0.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/15">
                                                <span class="h-1.5 w-1.5 rounded-full" style="background-color: {{ $priorityColor }}"></span>
                                                {{ data_get($t, 'priority.name') }}
                                            </span>
                                        @endif
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 px-2 py-0.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/20">
                                            <i data-lucide="{{ $severity['icon'] }}" class="h-3.5 w-3.5"></i>
                                            {{ $severity['label'] }}
                                        </span>
                                    </div>
                                    <p class="mt-1 truncate text-sm font-semibold text-white">{{ $t['subject'] }}</p>
                                    <p class="mt-0.5 truncate text-xs text-rose-100/75">{{ $t['customer'] ?? 'Sin cliente' }} · {{ $t['agent'] ?? 'Sin asignar' }}</p>
                                </div>

                                <div class="min-w-0">
                                    <div class="flex items-center justify-between gap-3 text-xs">
                                        <span class="font-medium text-rose-100/80">Intensidad</span>
                                        <span class="font-semibold tabular-nums text-white">{{ number_format($t['overdue_hours'], 1, ',', '.') }} h</span>
                                    </div>
                                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/15 ring-1 ring-inset ring-white/15">
                                        <div class="sla-heat-meter h-full rounded-full"></div>
                                    </div>
                                </div>

                                <div class="text-left md:text-right">
                                    <p class="text-lg font-semibold tabular-nums text-white">{{ $t['overdue_human'] }}</p>
                                    <p class="text-xs text-rose-100/75">fuera de plazo</p>
                                </div>
                            </a>
                        @endforeach
                    </div>
                @endif
            </x-card>

            <div class="space-y-5">
                <x-card title="Mapa SLA" subtitle="Presión actual de la cola">
                    <div class="space-y-3 p-5">
                        @php $maxBand = max(collect($slaBands)->pluck('value')->push(1)->all()); @endphp
                        @foreach ($slaBands as $band)
                            @php $pct = max(7, round(($band['value'] / $maxBand) * 100)); @endphp
                            <div>
                                <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                    <span class="font-medium text-slate-700">{{ $band['label'] }}</span>
                                    <span class="font-semibold tabular-nums text-slate-950">{{ number_format($band['value'], 0, ',', '.') }}</span>
                                </div>
                                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full" style="width: {{ $pct }}%; background-color: {{ $band['color'] }}"></div>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $band['caption'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </x-card>

                <x-card title="Por estado" subtitle="Distribución de la operación">
                    <div class="p-3"><div id="chartStatus"></div></div>
                </x-card>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <x-card title="Creados vs resueltos" subtitle="Ritmo de los últimos 14 días" class="lg:col-span-2">
                <div class="p-3"><div id="chartCreatedResolved"></div></div>
            </x-card>
            <x-card title="Por prioridad" subtitle="Carga por urgencia">
                <div class="p-3"><div id="chartPriority"></div></div>
            </x-card>
        </section>

        <section class="grid grid-cols-1 items-start gap-5 {{ $agentView ? '' : 'xl:grid-cols-[minmax(0,1fr)_24rem]' }}">
            <x-card class="overflow-hidden">
                <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-sm font-semibold text-slate-950">{{ $agentView ? 'Mis últimos tickets' : 'Últimos tickets' }}</h2>
                        <p class="text-xs leading-5 text-slate-500">Azul para nuevos, ámbar para próximos a vencer, rojo progresivo para atrasados.</p>
                    </div>
                    <a href="{{ route('admin.tickets.index') }}" class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-brand-700 hover:bg-brand-50">
                        Ver todos
                        <i data-lucide="arrow-right" class="h-3.5 w-3.5"></i>
                    </a>
                </div>

                @forelse ($latestTickets as $t)
                    @php $state = $ticketState($t); @endphp
                    <a href="{{ route('admin.tickets.show', $t['id']) }}"
                       class="{{ $state['class'] }} group grid gap-3 border-b border-slate-100 px-5 py-3.5 transition last:border-b-0 md:grid-cols-[minmax(0,1fr)_11rem_9rem] md:items-center"
                       style="{{ $state['style'] }}">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-slate-500">{{ $t['code'] }}</span>
                                @if (($t['source'] ?? null) === 'web')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-sky-50 px-2 py-0.5 text-xs font-medium text-sky-700 ring-1 ring-inset ring-sky-200">
                                        <i data-lucide="globe" class="h-3 w-3"></i>
                                        Web
                                    </span>
                                @endif
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold ring-1 {{ $state['tone'] }}">
                                    <i data-lucide="{{ $state['icon'] }}" class="h-3 w-3"></i>
                                    {{ $state['label'] }}
                                </span>
                            </div>
                            <p class="mt-1 truncate text-sm font-semibold text-slate-950">{{ $t['subject'] }}</p>
                            <p class="truncate text-xs text-slate-600">{{ data_get($t, 'customer.name') ?? 'Sin cliente' }} · {{ data_get($t, 'agent.name') ?? 'Sin asignar' }}</p>
                        </div>

                        <div class="flex flex-wrap items-center gap-2 md:justify-end">
                            <x-status-badge :status="$t['status']" />
                            <x-priority-badge :priority="$t['priority']" />
                        </div>

                        <div class="text-left md:text-right">
                            @if ($t['is_overdue'])
                                <p class="text-sm font-semibold tabular-nums text-rose-700">{{ $t['overdue_human'] }}</p>
                                <p class="text-xs text-rose-500">atrasado</p>
                            @elseif (! empty($t['due_at']))
                                <p class="text-sm font-semibold tabular-nums text-slate-700">{{ Carbon::parse($t['due_at'])->diffForHumans() }}</p>
                                <p class="text-xs text-slate-500">vence</p>
                            @else
                                <p class="text-sm font-semibold tabular-nums text-slate-700">{{ ! empty($t['created_at']) ? Carbon::parse($t['created_at'])->diffForHumans() : 'Sin dato' }}</p>
                                <p class="text-xs text-slate-500">creado</p>
                            @endif
                        </div>
                    </a>
                @empty
                    <x-empty-state icon="ticket" title="Sin tickets todavía" description="La cola aparecerá acá cuando entre el primer caso." />
                @endforelse
            </x-card>

            @unless ($agentView)
                <div class="space-y-5">
                    <x-card title="Productividad por agente" subtitle="Resueltos, pendientes y atrasados">
                        <div class="p-3"><div id="chartAgents"></div></div>
                    </x-card>

                    <x-card title="Ranking del mes" subtitle="Resueltos este mes">
                        <div class="space-y-1 p-2">
                            @php $max = max($leaderboard->pluck('resolved_month')->push(1)->all()); @endphp
                            @forelse ($leaderboard->where('resolved_month', '>', 0)->values() as $i => $a)
                                <a href="{{ route('admin.agents.show', $a['id']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition-colors hover:bg-slate-50">
                                    <span class="flex h-7 w-7 items-center justify-center rounded-lg bg-slate-100 text-sm font-semibold tabular-nums text-slate-600">{{ $i + 1 }}</span>
                                    <x-avatar :name="$a['name']" :src="$a['avatar_url']" size="sm" />
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="truncate text-sm font-semibold text-slate-800">{{ $a['name'] }}</p>
                                            <p class="shrink-0 text-sm font-semibold tabular-nums text-slate-950">{{ $a['resolved_month'] }}</p>
                                        </div>
                                        <div class="mt-1.5 h-1.5 w-full overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full rounded-full bg-brand-600" style="width: {{ round($a['resolved_month'] / $max * 100) }}%"></div>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <x-empty-state icon="users" title="Sin actividad este mes" />
                            @endforelse
                        </div>
                    </x-card>

                    <x-card title="Embudo de tickets" subtitle="De creación a cierre">
                        <div class="p-5">@include('admin.tickets._funnel', ['funnel' => $charts['funnel']])</div>
                    </x-card>
                </div>
            @else
                <x-card title="Embudo de mis tickets" subtitle="De creación a cierre">
                    <div class="p-5">@include('admin.tickets._funnel', ['funnel' => $charts['funnel']])</div>
                </x-card>
            @endunless
        </section>
    </div>

    @push('scripts')
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                if (!window.ApexCharts) return;
                const base = window.osoleChartBase();

                const cr = @json($charts['createdVsResolved']);
                new ApexCharts(document.querySelector('#chartCreatedResolved'), {
                    ...base,
                    chart: { ...base.chart, type: 'area', height: 300 },
                    series: [
                        { name: 'Creados', data: cr.map(d => d.created) },
                        { name: 'Resueltos', data: cr.map(d => d.resolved) },
                    ],
                    colors: ['#4f46e5', '#10b981'],
                    stroke: { curve: 'smooth', width: 2.5 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.24, opacityTo: 0.03, stops: [0, 92] } },
                    xaxis: { categories: cr.map(d => d.label), axisBorder: { show: false }, axisTicks: { show: false }, tickAmount: 6, labels: { rotate: 0, style: { fontSize: '11px' } } },
                    yaxis: { labels: { formatter: v => Math.round(v) } },
                }).render();

                const st = @json($charts['byStatus']).filter(d => d.value > 0);
                new ApexCharts(document.querySelector('#chartStatus'), {
                    ...base,
                    chart: { ...base.chart, type: 'donut', height: 286 },
                    series: st.map(d => d.value),
                    labels: st.map(d => d.name),
                    colors: st.map(d => d.color),
                    stroke: { width: 3, colors: ['#fff'] },
                    plotOptions: { pie: { donut: { size: '70%', labels: { show: true,
                        total: { show: true, label: 'Tickets', color: '#64748b', fontSize: '13px', formatter: w => w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString('es-AR') },
                        value: { color: '#0f172a', fontSize: '26px', fontWeight: 700 } } } } },
                }).render();

                const pr = @json($charts['byPriority']);
                new ApexCharts(document.querySelector('#chartPriority'), {
                    ...base,
                    chart: { ...base.chart, type: 'bar', height: 300 },
                    series: [{ name: 'Tickets', data: pr.map(d => ({ x: d.name, y: d.value, fillColor: d.color })) }],
                    plotOptions: { bar: { horizontal: true, borderRadius: 7, barHeight: '52%', distributed: true } },
                    colors: pr.map(d => d.color),
                    legend: { show: false },
                    xaxis: { labels: { formatter: v => Math.round(v) } },
                }).render();

                const agEl = document.querySelector('#chartAgents');
                if (agEl) {
                    const ag = @json($leaderboard->values());
                    new ApexCharts(agEl, {
                        ...base,
                        chart: { ...base.chart, type: 'bar', height: 282 },
                        series: [
                            { name: 'Resueltos', data: ag.map(a => a.resolved_month) },
                            { name: 'Pendientes', data: ag.map(a => a.pending) },
                            { name: 'Atrasados', data: ag.map(a => a.overdue) },
                        ],
                        colors: ['#10b981', '#4f46e5', '#f43f5e'],
                        plotOptions: { bar: { borderRadius: 5, columnWidth: '58%' } },
                        xaxis: { categories: ag.map(a => a.name.split(' ')[0]), axisBorder: { show: false }, axisTicks: { show: false } },
                        yaxis: { labels: { formatter: v => Math.round(v) } },
                    }).render();
                }
            });
        </script>
    @endpush
</x-layouts.admin>
