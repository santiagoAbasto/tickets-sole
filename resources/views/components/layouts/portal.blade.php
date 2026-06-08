@props(['title' => null, 'showNew' => true])
@php $u = auth()->user(); @endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', ['title' => $title, 'robots' => 'noindex, nofollow'])
</head>
<body class="h-full bg-canvas font-sans text-slate-700 antialiased">
    <div class="min-h-dvh">
        <header class="sticky top-0 z-30 border-b border-slate-200 bg-surface/90 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-3xl items-center gap-3 px-4">
                <a href="{{ route('portal.tickets.index') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('img/logo.svg') }}" alt="Osole" class="h-9 w-9 shrink-0">

                    <span class="text-[15px] font-semibold tracking-tight text-slate-900">Osole · Soporte</span>
                </a>
                <div class="ml-auto flex items-center gap-2">
                    @if ($showNew)
                        <x-button :href="route('portal.tickets.create')" size="sm">
                            <i data-lucide="plus" class="h-4 w-4"></i> Nueva consulta
                        </x-button>
                    @endif
                    <div class="relative" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center gap-2 rounded-lg p-1 pr-2 transition-colors hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                            <x-avatar :name="$u?->name" :src="$u?->avatarUrl()" size="sm" />
                            <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400"></i>
                        </button>
                        <div x-show="open" x-cloak @click.outside="open = false" x-transition
                             class="absolute right-0 z-50 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg ring-1 ring-slate-900/5">
                            <div class="px-2.5 py-2">
                                <p class="text-sm font-medium text-slate-800">{{ $u?->name }}</p>
                                <p class="truncate text-xs text-slate-400">{{ $u?->email }}</p>
                            </div>
                            <div class="my-1 h-px bg-slate-100"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-rose-600 hover:bg-rose-50">
                                    <i data-lucide="log-out" class="h-4 w-4"></i> Cerrar sesión
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-3xl px-4 py-8">{{ $slot }}</main>
    </div>
    @include('partials.flash')
    @stack('scripts')
</body>
</html>
