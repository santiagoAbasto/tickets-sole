@props([
    'title' => null,
    'description' => 'Osole Soporte permite abrir tickets, adjuntar evidencia y seguir cada respuesta desde un chat claro y trazable.',
    'image' => null,
    'canonical' => null,
    'robots' => 'index, follow, max-image-preview:large',
    'hero' => false,
    'immersive' => false,
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', [
        'title' => $title,
        'description' => $description,
        'image' => $image,
        'canonical' => $canonical,
        'robots' => $robots,
        'siteName' => 'Osole Soporte',
    ])
</head>
<body class="h-full font-sans antialiased {{ $immersive ? 'bg-sidebar text-slate-300' : 'bg-canvas text-slate-700' }}">
    @if ($immersive)<x-public.backdrop />@endif
    <div class="relative flex min-h-dvh flex-col">
        {{-- Public header: centered content, full-width divider. --}}
        <header x-data="{ scrolled: {{ $immersive || $hero ? 'false' : 'true' }} }"
                @if (! $immersive) @scroll.window="scrolled = {{ $hero ? 'window.scrollY > 30' : 'true' }}" @endif
                :class="scrolled ? 'border-b border-slate-200 bg-surface/90 shadow-sm backdrop-blur-xl' : '{{ $immersive ? 'border-b border-white/10 bg-sidebar/70 shadow-lg shadow-black/30 backdrop-blur-xl' : 'border-b border-white/10 bg-sidebar/10 backdrop-blur-sm' }}'"
                class="{{ $hero && ! $immersive ? 'fixed' : 'sticky' }} inset-x-0 top-0 z-40 transition duration-300">
            <div class="mx-auto flex h-[72px] max-w-[1224px] items-center justify-between px-4 sm:px-6 xl:px-0">
                <a href="{{ route('public.support.create') }}" class="flex items-center gap-2.5">
                    <img src="{{ asset('img/logo.svg') }}" alt="Osole" class="h-10 w-10 shrink-0">

                    <span class="leading-tight">
                        <span class="block text-[15px] font-semibold tracking-tight transition-colors" :class="scrolled ? 'text-slate-950' : 'text-white'">Osole Soporte</span>
                        <span class="hidden text-[11px] font-medium transition-colors sm:block" :class="scrolled ? 'text-slate-500' : 'text-white/55'">Mesa de ayuda digital</span>
                    </span>
                </a>

                <nav class="flex items-center gap-1 sm:gap-2">
                    <a href="{{ route('public.track.form') }}"
                       class="inline-flex min-h-11 items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition"
                       :class="scrolled ? 'bg-brand-600 text-white shadow-sm shadow-brand-600/20 hover:bg-brand-700' : 'bg-white text-slate-950 ring-1 ring-inset ring-white/20 hover:bg-brand-50'">
                        <i data-lucide="search-check" class="h-4 w-4"></i> Seguí tu ticket
                    </a>
                </nav>
            </div>
        </header>

        <main class="flex-1">{{ $slot }}</main>

        {{-- Dark footer with centered content and full-width separators. --}}
        <footer class="border-t border-white/10 bg-sidebar text-slate-400">
            <div class="mx-auto max-w-[1224px] px-4 py-14 sm:px-6 xl:px-0">
                <div class="grid gap-10 md:grid-cols-[1.25fr_0.8fr_0.8fr_0.9fr]">
                    <div>
                        <div class="flex items-center gap-2.5">
                            <img src="{{ asset('img/logo.svg') }}" alt="Osole" class="h-10 w-10 shrink-0">
                            <span class="text-[15px] font-semibold tracking-tight text-white">Osole Soporte</span>
                        </div>
                        <p class="mt-4 max-w-sm text-sm leading-6 text-slate-400">Mesa de ayuda para registrar consultas, adjuntar evidencia y seguir cada respuesta desde un mismo código.</p>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white">Soporte</h4>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li><a href="{{ route('public.support.create') }}#form" class="text-slate-300 transition-colors hover:text-white">Enviar consulta</a></li>
                            <li><a href="{{ route('public.track.form') }}" class="text-slate-300 transition-colors hover:text-white">Seguí tu ticket</a></li>
                            <li><a href="{{ route('public.support.create') }}#pasos" class="text-slate-300 transition-colors hover:text-white">Cómo funciona</a></li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white">Contacto</h4>
                        <ul class="mt-4 space-y-2.5 text-sm">
                            <li class="flex items-center gap-2 text-slate-300"><i data-lucide="mail" class="h-4 w-4 text-slate-500"></i> soporte@osole.com.ar</li>
                            <li class="flex items-center gap-2 text-slate-300"><i data-lucide="globe" class="h-4 w-4 text-slate-500"></i> osole.com.ar</li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-white">Horario</h4>
                        <p class="mt-4 flex items-start gap-2 text-sm leading-6 text-slate-300"><i data-lucide="clock" class="mt-1 h-4 w-4 text-slate-500"></i> Lun a Vie, 8 a 13 y 14 a 17 h</p>
                        <p class="mt-2 text-sm leading-6 text-slate-500">Las consultas quedan registradas fuera de horario y el equipo responde en la siguiente franja de atención.</p>
                    </div>
                </div>
                <div class="mt-12 flex flex-col items-center justify-between gap-3 border-t border-white/10 pt-6 text-xs text-slate-500 sm:flex-row">
                    <p>© {{ date('Y') }} Osole.com.ar · Todos los derechos reservados.</p>
                    <p>Tickets claros, respuestas trazables.</p>
                </div>
            </div>
        </footer>
    </div>
    <x-public.whatsapp-widget />
    @include('partials.flash')
    @stack('scripts')
</body>
</html>
