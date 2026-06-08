@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    @include('partials.head', ['title' => $title, 'robots' => 'noindex, nofollow'])
</head>
<body class="h-full bg-canvas font-sans text-slate-700 antialiased" x-data="{ mobileNav: false }">
    {{-- Desktop sidebar --}}
    <aside class="fixed inset-y-0 left-0 hidden w-64 lg:block">
        @include('partials.admin.sidebar')
    </aside>

    {{-- Mobile drawer --}}
    <div x-show="mobileNav" x-cloak class="fixed inset-0 z-50 lg:hidden">
        <div x-show="mobileNav" x-transition.opacity class="fixed inset-0 bg-slate-900/50" @click="mobileNav = false"></div>
        <div x-show="mobileNav"
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
             class="fixed inset-y-0 left-0 w-64">
            @include('partials.admin.sidebar')
        </div>
    </div>

    <div class="flex min-h-dvh flex-col lg:pl-64">
        @include('partials.admin.topbar', ['title' => $title])
        <main class="flex-1 px-4 py-6 lg:px-8 lg:py-8">
            {{ $slot }}
        </main>
    </div>

    @include('partials.flash')

    {{-- Browser notifications for new tickets — Super Admin only --}}
    @role('Super Admin')
        <div x-data="{ show: false }"
             x-init="show = ('Notification' in window) && Notification.permission === 'default' && ! localStorage.getItem('osole_notif_dismissed')"
             x-show="show" x-cloak
             x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
             class="fixed bottom-5 left-5 z-50 max-w-xs rounded-xl border border-slate-200 bg-white p-4 shadow-xl ring-1 ring-slate-900/5 print:hidden">
            <div class="flex items-start gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100"><i data-lucide="bell-ring" class="h-5 w-5"></i></span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900">Avisos de nuevos tickets</p>
                    <p class="mt-0.5 text-xs leading-5 text-slate-500">Activá las notificaciones del navegador para enterarte al instante cuando entra un soporte.</p>
                    <div class="mt-2.5 flex items-center gap-2">
                        <button type="button" @click="Notification.requestPermission().finally(() => show = false)" class="inline-flex h-8 items-center rounded-lg bg-brand-600 px-3 text-xs font-semibold text-white transition-colors hover:bg-brand-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">Activar</button>
                        <button type="button" @click="localStorage.setItem('osole_notif_dismissed','1'); show = false" class="inline-flex h-8 items-center rounded-lg px-2.5 text-xs font-medium text-slate-500 transition-colors hover:bg-slate-100">Ahora no</button>
                    </div>
                </div>
            </div>
        </div>

        @push('scripts')
            <script>
                (function () {
                    if (! ('Notification' in window)) return;
                    const ALERT_URL = @js(route('admin.notifications.ticket-alerts'));
                    let lastId = null;

                    async function poll() {
                        if (Notification.permission !== 'granted') return;
                        try {
                            const url = ALERT_URL + (lastId !== null ? ('?after=' + lastId) : '');
                            const res = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                            if (! res.ok) return;
                            const data = await res.json();
                            if (lastId === null) { lastId = data.latest_id || 0; return; } // baseline: don't notify the backlog
                            (data.tickets || []).slice().reverse().forEach(function (t) {
                                const n = new Notification('Nuevo ticket · ' + t.code, {
                                    body: (t.subject || '') + (t.customer ? ' · ' + t.customer : ''),
                                    icon: '/img/logo.svg',
                                    tag: 'osole-ticket-' + t.id,
                                });
                                n.onclick = function () { window.focus(); window.location = t.url; };
                            });
                            if (data.latest_id) lastId = Math.max(lastId, data.latest_id);
                        } catch (e) { /* keep polling */ }
                    }

                    document.addEventListener('DOMContentLoaded', function () {
                        poll();
                        setInterval(poll, 45000);
                    });
                })();
            </script>
        @endpush
    @endrole

    @stack('scripts')
</body>
</html>
