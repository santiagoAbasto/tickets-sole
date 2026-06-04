@php
    $flashes = collect([
        ['type' => 'success', 'msg' => session('success'), 'icon' => 'check-circle-2', 'color' => 'text-emerald-500'],
        ['type' => 'error',   'msg' => session('error'),   'icon' => 'alert-circle',   'color' => 'text-rose-500'],
        ['type' => 'info',    'msg' => session('info'),    'icon' => 'info',           'color' => 'text-sky-500'],
    ])->filter(fn ($f) => filled($f['msg']));
@endphp

@if ($flashes->isNotEmpty())
    <div class="pointer-events-none fixed right-4 top-4 z-[1000] flex w-full max-w-sm flex-col gap-2">
        @foreach ($flashes as $f)
            <div
                x-data="{ show: false }"
                x-init="$nextTick(() => show = true); setTimeout(() => show = false, 4200)"
                x-show="show"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-x-4"
                x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-end="opacity-0 translate-x-4"
                class="pointer-events-auto flex items-start gap-3 rounded-xl bg-white px-4 py-3 text-sm text-slate-700 shadow-lg ring-1 ring-slate-900/5"
                role="status"
            >
                <i data-lucide="{{ $f['icon'] }}" class="mt-0.5 h-5 w-5 shrink-0 {{ $f['color'] }}"></i>
                <p class="flex-1">{{ $f['msg'] }}</p>
                <button @click="show = false" class="text-slate-300 hover:text-slate-500" aria-label="Cerrar">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
        @endforeach
    </div>
@endif
