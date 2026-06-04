@php
    $metrics = [
        ['icon'=>'circle-check-big','tone'=>'bg-emerald-50 text-emerald-600','label'=>'Resueltos (mes)','val'=>$agent['resolved_month']],
        ['icon'=>'clock','tone'=>'bg-indigo-50 text-indigo-600','label'=>'Pendientes','val'=>$agent['pending']],
        ['icon'=>'alarm-clock','tone'=>'bg-rose-50 text-rose-600','label'=>'Atrasados','val'=>$agent['overdue']],
        ['icon'=>'timer','tone'=>'bg-violet-50 text-violet-600','label'=>'Prom. resolución','val'=>$agent['avg_resolution_hours'] !== null ? number_format($agent['avg_resolution_hours'],1,',','.').' h' : '—'],
        ['icon'=>'gauge','tone'=>'bg-brand-50 text-brand-600','label'=>'Eficiencia','val'=>$agent['efficiency'] !== null ? $agent['efficiency'].'%' : '—'],
    ];
@endphp
<x-layouts.admin title="Agente">
    <div class="space-y-5">
        <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i> Volver a agentes</a>

        <x-card class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <x-avatar :name="$agent['name']" :src="$agent['avatar_url']" size="lg" />
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-semibold tracking-tight text-slate-900">{{ $agent['name'] }}</h1>
                        @if (! $agent['is_active'])<span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-500">Inactivo</span>@endif
                    </div>
                    <p class="text-sm text-slate-500">{{ $agent['job_title'] ?? 'Agente' }} · {{ $agent['roles'][0] ?? '' }} {{ $agent['department'] ? '· '.$agent['department'] : '' }}</p>
                    <div class="mt-1 flex flex-wrap gap-3 text-xs text-slate-500">
                        <span class="inline-flex items-center gap-1"><i data-lucide="mail" class="h-3 w-3"></i> {{ $agent['email'] }}</span>
                        @if ($agent['phone'])<span class="inline-flex items-center gap-1"><i data-lucide="phone" class="h-3 w-3"></i> {{ $agent['phone'] }}</span>@endif
                    </div>
                </div>
            </div>
            <x-button variant="secondary" :href="route('admin.agents.edit', $agent['id'])"><i data-lucide="pencil" class="h-4 w-4"></i> Editar</x-button>
        </x-card>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            @foreach ($metrics as $m)
                <div class="rounded-2xl border border-slate-200 bg-surface p-4 shadow-sm">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl {{ $m['tone'] }}"><i data-lucide="{{ $m['icon'] }}" class="h-5 w-5"></i></span>
                    <p class="mt-3 text-2xl font-semibold tracking-tight text-slate-900 tabular-nums">{{ $m['val'] }}</p>
                    <p class="mt-0.5 text-sm font-medium text-slate-600">{{ $m['label'] }}</p>
                </div>
            @endforeach
        </div>

        <x-card class="overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4"><h3 class="text-sm font-semibold text-slate-900">Tickets recientes</h3></div>
            @forelse ($recentTickets as $t)
                <a href="{{ route('admin.tickets.show', $t['id']) }}" class="flex items-center gap-3 px-5 py-3 hover:bg-slate-50 {{ ! $loop->last ? 'border-b border-slate-100' : '' }}">
                    <span class="font-mono text-xs text-slate-400">{{ $t['code'] }}</span>
                    <span class="min-w-0 flex-1 truncate text-sm text-slate-700">{{ $t['subject'] }}</span>
                    <x-priority-badge :priority="$t['priority']" />
                    <x-status-badge :status="$t['status']" />
                </a>
            @empty
                <x-empty-state title="Sin tickets asignados" />
            @endforelse
        </x-card>
    </div>
</x-layouts.admin>
