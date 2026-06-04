@php use Illuminate\Support\Carbon; @endphp
<x-layouts.portal title="Mis consultas">
    <div class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Mis consultas</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $stats['open'] }} {{ $stats['open'] === 1 ? 'abierta' : 'abiertas' }} · {{ $stats['total'] }} en total</p>
        </div>
    </div>

    @if ($tickets->isEmpty())
        <div class="rounded-2xl border border-slate-200 bg-surface p-2 shadow-sm">
            <x-empty-state icon="message-square-plus" title="Todavía no tenés consultas" description="Cuando necesites ayuda, abrí una consulta y nuestro equipo te responde.">
                <x-slot:action><x-button :href="route('portal.tickets.create')"><i data-lucide="plus" class="h-4 w-4"></i> Crear consulta</x-button></x-slot:action>
            </x-empty-state>
        </div>
    @else
        <ul class="space-y-3">
            @foreach ($tickets as $i => $t)
                <li class="animate-rise" style="animation-delay: {{ $i * 40 }}ms">
                    <a href="{{ route('portal.tickets.show', $t['id']) }}" class="group flex items-center gap-4 rounded-2xl border border-slate-200 bg-surface p-4 shadow-sm transition-shadow hover:shadow-md">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs text-slate-400">{{ $t['code'] }}</span><span class="text-xs text-slate-300">·</span>
                                <span class="text-xs text-slate-400">{{ Carbon::parse($t['last_activity_at'] ?? $t['created_at'])->diffForHumans() }}</span>
                            </div>
                            <p class="mt-0.5 truncate font-medium text-slate-800">{{ $t['subject'] }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2"><x-status-badge :status="$t['status']" /><x-priority-badge :priority="$t['priority']" /></div>
                        </div>
                        <i data-lucide="chevron-right" class="h-5 w-5 shrink-0 text-slate-300 transition-transform group-hover:translate-x-0.5 group-hover:text-slate-400"></i>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $tickets->links() }}</div>
    @endif
</x-layouts.portal>
