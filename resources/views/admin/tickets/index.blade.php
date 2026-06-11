@php
    use Illuminate\Support\Carbon;
    $origins = [
        'web' => ['label' => 'Web', 'icon' => 'globe', 'cls' => 'bg-sky-50 text-sky-700 ring-sky-200'],
        'portal' => ['label' => 'Portal', 'icon' => 'user-round', 'cls' => 'bg-violet-50 text-violet-700 ring-violet-200'],
        'admin' => ['label' => 'Interno', 'icon' => 'building-2', 'cls' => 'bg-slate-100 text-slate-500 ring-slate-200'],
    ];
    $currentFlag = request('flag', 'mine');
@endphp
<x-layouts.admin title="Tickets">
    <div class="space-y-5">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Tickets</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $tickets->total() }} {{ $tickets->total() === 1 ? 'ticket' : 'tickets' }} en total</p>
            </div>
            <x-button :href="route('admin.tickets.create')"><i data-lucide="plus" class="h-4 w-4"></i> Nuevo ticket</x-button>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('admin.tickets.index') }}" class="space-y-3">
            {{-- keeps the current view when other filters change --}}
            <input type="hidden" name="flag" value="{{ $currentFlag }}">

            {{-- Prominent quick-view toggle --}}
            <div class="flex flex-wrap items-center gap-2">
                <div class="inline-flex rounded-xl border border-slate-200 bg-white p-1 shadow-sm">
                    @foreach (['mine' => ['Mis tickets', 'user-round'], 'all' => ['Todos', 'layers']] as $v => $meta)
                        <button type="submit" name="flag" value="{{ $v }}"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-sm font-semibold transition-colors {{ $currentFlag === $v ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/20' : 'text-slate-600 hover:bg-slate-50' }}">
                            <i data-lucide="{{ $meta[1] }}" class="h-4 w-4"></i> {{ $meta[0] }}
                        </button>
                    @endforeach
                </div>
                @foreach (['overdue' => ['Atrasados', 'alarm-clock'], 'unassigned' => ['Sin asignar', 'user-x']] as $v => $meta)
                    <button type="submit" name="flag" value="{{ $v }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-sm font-medium transition-colors {{ $currentFlag === $v ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                        <i data-lucide="{{ $meta[1] }}" class="h-4 w-4"></i> {{ $meta[0] }}
                    </button>
                @endforeach
            </div>

            {{-- Search + dimension filters --}}
            <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">
                <div class="relative w-full sm:max-w-xs">
                    <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input name="search" value="{{ request('search') }}" placeholder="Buscar por código, asunto o cliente…" class="input pl-9">
                </div>
                <select name="status" onchange="this.form.submit()" class="select w-auto min-w-[8.5rem]">
                    <option value="">Estado</option>
                    @foreach ($options['statuses'] as $s)<option value="{{ $s['slug'] }}" @selected(request('status') === $s['slug'])>{{ $s['name'] }}</option>@endforeach
                </select>
                <select name="priority" onchange="this.form.submit()" class="select w-auto min-w-[8.5rem]">
                    <option value="">Prioridad</option>
                    @foreach ($options['priorities'] as $p)<option value="{{ $p['slug'] }}" @selected(request('priority') === $p['slug'])>{{ $p['name'] }}</option>@endforeach
                </select>
                <select name="agent" onchange="this.form.submit()" class="select w-auto min-w-[8.5rem]">
                    <option value="">Agente</option>
                    @foreach ($options['agents'] as $a)<option value="{{ $a['id'] }}" @selected(request('agent') == $a['id'])>{{ $a['name'] }}</option>@endforeach
                </select>
                <noscript><x-button type="submit" size="sm" variant="secondary">Filtrar</x-button></noscript>
                @if (request()->hasAny(['search','status','priority','agent','category']) || $currentFlag !== 'mine')
                    <a href="{{ route('admin.tickets.index') }}" class="inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-sm font-medium text-slate-500 hover:bg-slate-100 hover:text-slate-700"><i data-lucide="x" class="h-4 w-4"></i> Limpiar</a>
                @endif
            </div>
        </form>

        {{-- Table --}}
        <x-card class="overflow-hidden">
            @if ($tickets->isEmpty())
                <x-empty-state icon="ticket" title="No hay tickets que coincidan" description="Probá ajustar los filtros o creá un ticket nuevo.">
                    <x-slot:action><x-button :href="route('admin.tickets.create')">Nuevo ticket</x-button></x-slot:action>
                </x-empty-state>
            @else
                {{-- Desktop --}}
                <div class="hidden overflow-x-auto md:block">
                    <table class="w-full min-w-[76rem] text-sm">
                        <thead>
                            <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                                <th class="px-5 py-3">Código</th><th class="px-3 py-3">Asunto</th><th class="px-3 py-3">Estado</th>
                                <th class="px-3 py-3">Prioridad</th><th class="px-3 py-3">Agente</th><th class="px-3 py-3">Último cambio de estado</th><th class="px-3 py-3">Creado</th><th class="px-5 py-3 text-right">Vence</th>
                                @can('tickets.delete')<th class="px-3 py-3"><span class="sr-only">Acciones</span></th>@endcan
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($tickets as $t)
                                <tr class="cursor-pointer transition-colors hover:bg-slate-50" onclick="window.location='{{ route('admin.tickets.show', $t['id']) }}'">
                                    <td class="whitespace-nowrap px-5 py-3">
                                        <a href="{{ route('admin.tickets.show', $t['id']) }}" onclick="event.stopPropagation()" class="font-mono text-xs font-medium text-brand-600 hover:underline">{{ $t['code'] }}</a>
                                        @php $o = $origins[$t['source'] ?? 'admin'] ?? $origins['admin']; @endphp
                                        <span class="ml-1.5 inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset {{ $o['cls'] }}" title="{{ $o['label'] }}"><i data-lucide="{{ $o['icon'] }}" class="h-2.5 w-2.5"></i> {{ $o['label'] }}</span>
                                    </td>
                                    <td class="max-w-xs px-3 py-3"><p class="truncate font-medium text-slate-800">{{ $t['subject'] }}</p><p class="truncate text-xs text-slate-500">{{ data_get($t, 'customer.name') }}</p></td>
                                    <td class="px-3 py-3"><x-status-badge :status="$t['status']" /></td>
                                    <td class="px-3 py-3"><x-priority-badge :priority="$t['priority']" /></td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        @if (data_get($t, 'agent'))<span class="flex items-center gap-2"><x-avatar :name="data_get($t,'agent.name')" :src="data_get($t,'agent.avatar_url')" size="xs" /><span class="text-slate-600">{{ data_get($t,'agent.name') }}</span></span>
                                        @else<span class="text-xs text-slate-400">Sin asignar</span>@endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        @if ($t['last_status_change_at'])
                                            @php $statusChangedAt = Carbon::parse($t['last_status_change_at']); @endphp
                                            <span class="block text-slate-600" title="{{ $statusChangedAt->format('d/m/Y H:i') }}">{{ $statusChangedAt->diffForHumans() }}</span>
                                            @if ($t['last_status_change_label'])
                                                <span class="block max-w-[10rem] truncate text-xs text-slate-400">{{ $t['last_status_change_label'] }}</span>
                                            @endif
                                        @else
                                            <span class="text-xs text-slate-400">Sin cambios</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 text-slate-500">{{ $t['created_at'] ? Carbon::parse($t['created_at'])->diffForHumans() : '—' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right">
                                        @if ($t['is_overdue'])<x-overdue-badge :label="$t['overdue_human']" />
                                        @else<span class="text-xs text-slate-400">{{ $t['due_at'] ? Carbon::parse($t['due_at'])->diffForHumans() : '—' }}</span>@endif
                                    </td>
                                    @can('tickets.delete')
                                        <td class="whitespace-nowrap px-3 py-3 text-right" onclick="event.stopPropagation()">
                                            <form method="POST" action="{{ route('admin.tickets.destroy', $t['id']) }}" onsubmit="return confirm('¿Eliminar el ticket {{ $t['code'] }}? Se quita de la lista.')">
                                                @csrf @method('DELETE')
                                                <button type="submit" title="Eliminar ticket" aria-label="Eliminar ticket" class="rounded-lg p-1.5 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-600"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                            </form>
                                        </td>
                                    @endcan
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                {{-- Mobile --}}
                <ul class="divide-y divide-slate-100 md:hidden">
                    @foreach ($tickets as $t)
                        <li class="flex items-stretch">
                            <a href="{{ route('admin.tickets.show', $t['id']) }}" class="block flex-1 px-4 py-3.5 hover:bg-slate-50">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="flex items-center gap-1.5"><span class="font-mono text-xs text-slate-400">{{ $t['code'] }}</span>@php $o = $origins[$t['source'] ?? 'admin'] ?? $origins['admin']; @endphp<span class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[10px] font-medium ring-1 ring-inset {{ $o['cls'] }}"><i data-lucide="{{ $o['icon'] }}" class="h-2.5 w-2.5"></i> {{ $o['label'] }}</span></span>
                                    @if ($t['is_overdue'])<x-overdue-badge :label="$t['overdue_human']" />@endif
                                </div>
                                <p class="mt-1 truncate font-medium text-slate-800">{{ $t['subject'] }}</p>
                                <p class="truncate text-xs text-slate-500">{{ data_get($t, 'customer.name') }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-2"><x-status-badge :status="$t['status']" /><x-priority-badge :priority="$t['priority']" /></div>
                                <p class="mt-2 text-xs text-slate-500">
                                    Último cambio de estado:
                                    @if ($t['last_status_change_at'])
                                        @php $statusChangedAt = Carbon::parse($t['last_status_change_at']); @endphp
                                        <span title="{{ $statusChangedAt->format('d/m/Y H:i') }}">{{ $statusChangedAt->diffForHumans() }}</span>
                                        @if ($t['last_status_change_label'])
                                            <span class="text-slate-400">({{ $t['last_status_change_label'] }})</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400">sin cambios</span>
                                    @endif
                                </p>
                            </a>
                            @can('tickets.delete')
                                <form method="POST" action="{{ route('admin.tickets.destroy', $t['id']) }}" onsubmit="return confirm('¿Eliminar el ticket {{ $t['code'] }}? Se quita de la lista.')" class="flex items-center px-3">
                                    @csrf @method('DELETE')
                                    <button type="submit" title="Eliminar ticket" aria-label="Eliminar ticket" class="rounded-lg p-2 text-slate-300 transition-colors hover:bg-rose-50 hover:text-rose-600"><i data-lucide="trash-2" class="h-4 w-4"></i></button>
                                </form>
                            @endcan
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-card>

        <div>{{ $tickets->withQueryString()->links() }}</div>
    </div>
</x-layouts.admin>
