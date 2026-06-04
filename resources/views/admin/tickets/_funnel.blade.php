@php $funnelColors = ['#6366f1','#0ea5e9','#14b8a6','#10b981','#64748b']; $top = ($funnel[0]['value'] ?? 0) ?: 1; @endphp
<div class="space-y-2.5">
    @foreach ($funnel as $i => $s)
        @php $pct = round($s['value'] / $top * 100); @endphp
        <div>
            <div class="mb-1 flex items-center justify-between text-sm">
                <span class="font-medium text-slate-600">{{ $s['stage'] }}</span>
                <span class="tabular-nums text-slate-400"><span class="font-semibold text-slate-900">{{ number_format($s['value'], 0, ',', '.') }}</span> · {{ $pct }}%</span>
            </div>
            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                <div class="h-full rounded-full" style="width: {{ $pct }}%; background-color: {{ $funnelColors[$i % count($funnelColors)] }}"></div>
            </div>
        </div>
    @endforeach
</div>
