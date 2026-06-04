@props([
    'action',
    'method' => 'DELETE',
    'title' => '¿Confirmás la acción?',
    'message' => null,
    'confirm' => 'Eliminar',
])
<div x-data="{ open: false }" class="contents">
    <button type="button" @click="open = true" {{ $attributes }}>
        {{ $slot->isEmpty() ? 'Eliminar' : $slot }}
    </button>

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-[100]" role="dialog" aria-modal="true" @keydown.escape.window="open = false">
            <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/40"></div>
            <div class="fixed inset-0 flex items-center justify-center p-4">
                <div x-show="open"
                     x-transition
                     x-trap.noscroll="open"
                     @click.outside="open = false"
                     class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-900/5">
                    <div class="flex gap-4">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-rose-50 text-rose-600">
                            <i data-lucide="alert-triangle" class="h-5 w-5"></i>
                        </span>
                        <div class="min-w-0">
                            <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                            @if ($message)<p class="mt-1 text-sm text-slate-500">{{ $message }}</p>@endif
                        </div>
                    </div>
                    <div class="mt-6 flex justify-end gap-2">
                        <button type="button" @click="open = false" class="inline-flex h-10 items-center rounded-lg bg-white px-3.5 text-sm font-medium text-slate-700 ring-1 ring-inset ring-slate-200 hover:bg-slate-50">Cancelar</button>
                        <form method="POST" action="{{ $action }}">
                            @csrf
                            @method($method)
                            <button type="submit" class="inline-flex h-10 items-center rounded-lg bg-rose-600 px-3.5 text-sm font-medium text-white hover:bg-rose-700">{{ $confirm }}</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
