<x-layouts.public title="Seguí tu ticket" immersive>
    <section class="mx-auto max-w-lg px-4 py-16 sm:py-24">
        <div class="text-center">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-cyan-200 ring-1 ring-inset ring-white/15 backdrop-blur">
                <i data-lucide="search-check" class="h-7 w-7"></i>
            </span>
            <h1 class="mt-5 text-2xl font-semibold tracking-tight text-white">Conocé el estado de tu ticket</h1>
            <p class="mt-2 text-sm text-slate-300">Ingresá el código que te dimos y el email con el que lo creaste.</p>
        </div>

        @if (session('error'))
            <div role="alert" class="mt-6 flex items-center gap-2 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                <i data-lucide="alert-circle" class="h-4 w-4 shrink-0"></i> {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('public.track.lookup') }}" class="mt-6 space-y-4 rounded-2xl border border-white/10 bg-surface p-6 shadow-2xl shadow-slate-950/30">
            @csrf
            <div>
                <label for="code" class="label">Código del ticket <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <i data-lucide="hash" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input id="code" name="code" value="{{ old('code') }}" class="input pl-9 font-mono" placeholder="TK-2026-000123">
                </div>
                @error('code')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="email" class="label">Email <span class="text-rose-500">*</span></label>
                <div class="relative">
                    <i data-lucide="mail" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" class="input pl-9" placeholder="vos@email.com">
                </div>
                @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <x-button type="submit" size="lg" class="w-full"><i data-lucide="search" class="h-4 w-4"></i> Ver mi ticket</x-button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-300">¿Todavía no enviaste una consulta? <a href="{{ route('public.support.create') }}" class="font-medium text-cyan-300 transition-colors hover:text-cyan-200">Creá una acá</a></p>
    </section>
</x-layouts.public>
