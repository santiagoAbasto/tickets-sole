@php
    $u = auth()->user();
    $unread = $u->unreadNotifications()->latest()->limit(12)->get();
    $unreadCount = $u->unreadNotifications()->count();
@endphp
<header class="sticky top-0 z-30 flex h-16 items-center gap-3 border-b border-slate-200 bg-surface/90 px-4 backdrop-blur lg:px-6">
    <button @click="mobileNav = true" class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 lg:hidden" aria-label="Abrir menú">
        <i data-lucide="menu" class="h-5 w-5"></i>
    </button>

    @if (($title ?? null))
        <h1 class="hidden text-base font-semibold text-slate-900 sm:block">{{ $title }}</h1>
    @endif

    <form method="GET" action="{{ route('admin.tickets.index') }}" class="ml-auto hidden w-full max-w-xs items-center sm:flex">
        <div class="relative w-full">
            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
            <input name="search" value="{{ request('search') }}" placeholder="Buscar tickets, clientes…"
                   class="h-10 w-full rounded-lg border border-slate-200 bg-canvas pl-9 pr-3 text-sm text-slate-700 placeholder:text-slate-400 transition focus:border-brand-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-brand-500/20">
        </div>
    </form>

    <div class="flex items-center gap-1 max-sm:ml-auto">
        {{-- Notifications bell --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="relative rounded-lg p-2 text-slate-500 transition-colors hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Notificaciones">
                <i data-lucide="bell" class="h-5 w-5"></i>
                @if ($unreadCount > 0)
                    <span class="absolute right-0.5 top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-bold leading-none text-white">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>
                @endif
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" x-transition
                 class="absolute right-0 z-50 mt-2 w-80 origin-top-right overflow-hidden rounded-xl border border-slate-200 bg-white shadow-lg ring-1 ring-slate-900/5">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2.5">
                    <span class="text-sm font-semibold text-slate-900">Notificaciones</span>
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('admin.notifications.read') }}">
                            @csrf
                            <button class="text-xs font-medium text-brand-600 hover:text-brand-700">Marcar leídas</button>
                        </form>
                    @endif
                </div>
                <div class="max-h-96 overflow-y-auto scrollbar-thin">
                    @forelse ($unread as $n)
                        <a href="{{ route('admin.notifications.open', $n->id) }}" class="flex items-start gap-3 border-b border-slate-50 px-4 py-3 transition-colors last:border-0 hover:bg-slate-50">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-brand-600"><i data-lucide="{{ $n->data['icon'] ?? 'bell' }}" class="h-4 w-4"></i></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-slate-800">{{ $n->data['title'] ?? 'Notificación' }}</p>
                                <p class="truncate text-xs text-slate-500">{{ $n->data['message'] ?? '' }}</p>
                                <p class="mt-0.5 text-[11px] text-slate-400">{{ $n->created_at->diffForHumans() }}</p>
                            </div>
                        </a>
                    @empty
                        <div class="px-4 py-10 text-center text-sm text-slate-400">Sin notificaciones nuevas</div>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- User menu --}}
        <div class="relative" x-data="{ open: false }">
            <button @click="open = !open" class="flex items-center gap-2 rounded-lg p-1 pr-2 transition-colors hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                <x-avatar :name="$u?->name" :src="$u?->avatarUrl()" size="sm" />
                <span class="hidden text-left md:block">
                    <span class="block text-sm font-medium leading-tight text-slate-800">{{ $u?->name }}</span>
                    <span class="block text-xs leading-tight text-slate-400">{{ $u?->getRoleNames()->first() }}</span>
                </span>
                <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400"></i>
            </button>
            <div x-show="open" x-cloak @click.outside="open = false" x-transition
                 class="absolute right-0 z-50 mt-2 w-56 origin-top-right rounded-xl border border-slate-200 bg-white p-1.5 shadow-lg ring-1 ring-slate-900/5">
                <div class="px-2.5 py-2">
                    <p class="text-sm font-medium text-slate-800">{{ $u?->name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ $u?->email }}</p>
                </div>
                <div class="my-1 h-px bg-slate-100"></div>
                <a href="{{ route('admin.profile.edit') }}" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-900">
                    <i data-lucide="circle-user-round" class="h-4 w-4"></i> Mi perfil
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-rose-600 hover:bg-rose-50">
                        <i data-lucide="log-out" class="h-4 w-4"></i> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
