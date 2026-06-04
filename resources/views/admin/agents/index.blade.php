<x-layouts.admin title="Agentes">
    <div class="space-y-5">
        <div class="flex items-end justify-between gap-4">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Agentes</h1>
                <p class="mt-1 text-sm text-slate-500">{{ $agents->count() }} agentes · productividad del mes</p>
            </div>
            <x-button :href="route('admin.agents.create')"><i data-lucide="user-plus" class="h-4 w-4"></i> Nuevo agente</x-button>
        </div>

        @if ($agents->isEmpty())
            <x-card><x-empty-state icon="users" title="Sin agentes" description="Creá tu primer agente para empezar a asignar tickets." /></x-card>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($agents as $i => $a)
                    <a href="{{ route('admin.agents.show', $a['id']) }}" class="animate-rise block rounded-2xl border border-slate-200 bg-surface p-5 shadow-sm transition-shadow hover:shadow-md" style="animation-delay: {{ $i * 40 }}ms">
                        <div class="flex items-center gap-3">
                            <x-avatar :name="$a['name']" :src="$a['avatar_url']" size="md" />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-800">{{ $a['name'] }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $a['job_title'] ?? 'Agente' }}</p>
                            </div>
                            @if ($a['efficiency'] !== null)<span class="ml-auto rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">{{ $a['efficiency'] }}%</span>@endif
                        </div>
                        <div class="mt-4 grid grid-cols-4 gap-2 border-t border-slate-100 pt-4 text-center">
                            <div><p class="text-base font-semibold tabular-nums text-slate-900">{{ $a['resolved_month'] }}</p><p class="text-[11px] text-slate-400">Mes</p></div>
                            <div><p class="text-base font-semibold tabular-nums text-indigo-600">{{ $a['pending'] }}</p><p class="text-[11px] text-slate-400">Pend.</p></div>
                            <div><p class="text-base font-semibold tabular-nums {{ $a['overdue'] > 0 ? 'text-rose-600' : 'text-slate-900' }}">{{ $a['overdue'] }}</p><p class="text-[11px] text-slate-400">Atras.</p></div>
                            <div><p class="text-base font-semibold tabular-nums text-slate-900">{{ $a['avg_resolution_hours'] !== null ? number_format($a['avg_resolution_hours'],1,',','.').'h' : '—' }}</p><p class="text-[11px] text-slate-400">Prom.</p></div>
                        </div>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.admin>
