@props(['title' => null, 'heading' => null, 'subtitle' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', ['title' => $title])
</head>
<body class="h-full bg-canvas font-sans text-slate-700 antialiased">
    <div class="flex min-h-dvh">
        {{-- Brand panel --}}
        <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-sidebar p-12 text-white lg:flex">
            <div class="pointer-events-none absolute -right-24 -top-24 h-96 w-96 rounded-full bg-brand-600/20 blur-3xl"></div>
            <div class="relative flex items-center gap-2.5">
                <img src="{{ asset('img/logo.svg') }}" alt="Osole" class="h-10 w-10 shrink-0">

                <span class="text-lg font-semibold tracking-tight">Osole Helpdesk</span>
            </div>
            <div class="relative">
                <p class="text-2xl font-semibold leading-snug tracking-tight">Mesa de ayuda premium para tu equipo.</p>
                <p class="mt-3 max-w-md text-sm text-slate-400">Triá tickets, medí productividad y respondé a tiempo. Todo en un panel rápido y claro.</p>
            </div>
            <p class="relative text-xs text-slate-500">© {{ date('Y') }} Osole.com.ar</p>
        </div>

        {{-- Form panel --}}
        <div class="flex w-full flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-20">
            <div class="mx-auto w-full max-w-sm">
                <div class="mb-8 flex items-center gap-2.5 lg:hidden">
                    <img src="{{ asset('img/logo.svg') }}" alt="Osole" class="h-10 w-10 shrink-0">

                    <span class="text-lg font-semibold tracking-tight text-slate-900">Osole Helpdesk</span>
                </div>
                @if ($heading)<h1 class="text-2xl font-semibold tracking-tight text-slate-900">{{ $heading }}</h1>@endif
                @if ($subtitle)<p class="mt-1.5 text-sm text-slate-500">{{ $subtitle }}</p>@endif
                <div class="mt-8">{{ $slot }}</div>
            </div>
        </div>
    </div>
    @include('partials.flash')
</body>
</html>
