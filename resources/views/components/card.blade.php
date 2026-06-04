@props(['title' => null, 'subtitle' => null])
<div {{ $attributes->class(['rounded-2xl border border-slate-200 bg-surface shadow-sm']) }}>
    @if ($title)
        <div class="flex items-start justify-between gap-4 border-b border-slate-100 px-5 py-4">
            <div class="min-w-0">
                <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
                @if ($subtitle)<p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>@endif
            </div>
            @isset($action){{ $action }}@endisset
        </div>
    @endif
    {{ $slot }}
</div>
