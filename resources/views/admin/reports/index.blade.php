@php
    $k = $report['kpis'];
    $kpis = [
        ['label'=>'Creados','val'=>number_format($k['created'],0,',','.')],
        ['label'=>'Resueltos','val'=>number_format($k['resolved'],0,',','.')],
        ['label'=>'Tasa de resolución','val'=>$k['resolution_rate'].'%'],
        ['label'=>'Prom. 1ª respuesta','val'=>$k['avg_first_response_hours'] !== null ? number_format($k['avg_first_response_hours'],1,',','.').' h' : '—'],
        ['label'=>'Prom. resolución','val'=>$k['avg_resolution_hours'] !== null ? number_format($k['avg_resolution_hours'],1,',','.').' h' : '—'],
        ['label'=>'Atrasados (hoy)','val'=>number_format($k['overdue'],0,',','.')],
    ];
    $exportQuery = ['from' => $filters['from'], 'to' => $filters['to']];
@endphp
<x-layouts.admin title="Reportes">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div><h1 class="text-xl font-semibold tracking-tight text-slate-900">Reportes</h1><p class="mt-1 text-sm text-slate-500">{{ \Illuminate\Support\Carbon::parse($filters['from'])->translatedFormat('d M Y') }} — {{ \Illuminate\Support\Carbon::parse($filters['to'])->translatedFormat('d M Y') }}</p></div>
            <div class="flex flex-wrap items-end gap-2">
                <form method="GET" action="{{ route('admin.reports.index') }}" class="flex items-end gap-2">
                    <div><label class="label text-xs">Desde</label><input type="date" name="from" value="{{ $filters['from'] }}" class="input"></div>
                    <div><label class="label text-xs">Hasta</label><input type="date" name="to" value="{{ $filters['to'] }}" class="input"></div>
                    <x-button type="submit" variant="secondary">Aplicar</x-button>
                </form>
                <x-button :href="route('admin.reports.export.csv', $exportQuery)" variant="secondary"><i data-lucide="file-spreadsheet" class="h-4 w-4"></i> CSV</x-button>
                <x-button :href="route('admin.reports.export.pdf', $exportQuery)" variant="secondary"><i data-lucide="file-text" class="h-4 w-4"></i> PDF</x-button>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($kpis as $m)
                <div class="rounded-2xl border border-slate-200 bg-surface p-4 shadow-sm">
                    <p class="text-2xl font-semibold tracking-tight text-slate-900 tabular-nums">{{ $m['val'] }}</p>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">{{ $m['label'] }}</p>
                </div>
            @endforeach
        </div>

        <x-card title="Creados vs resueltos" subtitle="Por día">
            <div class="p-3"><div id="chartReportDaily"></div></div>
        </x-card>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            @foreach (['by_status' => 'Por estado', 'by_priority' => 'Por prioridad', 'by_category' => 'Por categoría'] as $key => $label)
                <x-card :title="$label">
                    <div class="space-y-2.5 p-5">
                        @php $max = max(collect($report[$key])->max('value') ?: 1, 1); @endphp
                        @forelse ($report[$key] as $row)
                            <div>
                                <div class="mb-1 flex items-center justify-between text-sm"><span class="text-slate-600">{{ $row['name'] }}</span><span class="font-semibold tabular-nums text-slate-900">{{ $row['value'] }}</span></div>
                                <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full" style="width: {{ round($row['value']/$max*100) }}%; background-color: {{ $row['color'] }}"></div></div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-400">Sin datos.</p>
                        @endforelse
                    </div>
                </x-card>
            @endforeach
        </div>

        <x-card title="Resueltos por agente" subtitle="En el rango seleccionado">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-400"><th class="px-5 py-3">Agente</th><th class="px-5 py-3 text-right">Resueltos</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($report['by_agent'] as $row)
                        <tr><td class="px-5 py-3"><span class="inline-flex items-center gap-2"><x-avatar :name="$row['name']" size="xs" /><span class="text-slate-700">{{ $row['name'] }}</span></span></td><td class="px-5 py-3 text-right font-semibold tabular-nums text-slate-900">{{ $row['resolved'] }}</td></tr>
                    @empty
                        <tr><td colspan="2" class="px-5 py-6 text-center text-sm text-slate-400">Sin actividad en el rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-card>
    </div>

    @push('scripts')
        <script>
            window.addEventListener('DOMContentLoaded', () => {
                if (!window.ApexCharts) return;
                const base = window.osoleChartBase();
                const d = @json($report['daily']);
                new ApexCharts(document.querySelector('#chartReportDaily'), {
                    ...base,
                    chart: { ...base.chart, type: 'area', height: 260 },
                    series: [
                        { name: 'Creados', data: d.map(x => x.created) },
                        { name: 'Resueltos', data: d.map(x => x.resolved) },
                    ],
                    colors: ['#6366f1', '#10b981'],
                    stroke: { curve: 'smooth', width: 2.5 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.28, opacityTo: 0.02, stops: [0, 90] } },
                    xaxis: { categories: d.map(x => x.label), axisBorder: { show: false }, axisTicks: { show: false }, tickAmount: 8, labels: { rotate: 0, style: { fontSize: '11px' } } },
                    yaxis: { labels: { formatter: v => Math.round(v) } },
                }).render();
            });
        </script>
    @endpush
</x-layouts.admin>
