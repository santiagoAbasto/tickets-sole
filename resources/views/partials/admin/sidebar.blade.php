@php
    $linkBase = 'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors';
    $active = 'bg-sidebar-active text-white';
    $idle = 'text-slate-400 hover:bg-sidebar-hover hover:text-slate-100';
    $iconActive = 'text-brand-300';
    $iconIdle = 'text-slate-500 group-hover:text-slate-300';
@endphp

<div class="flex h-full flex-col bg-sidebar">
    <div class="flex h-16 items-center gap-2.5 px-5">
        <img src="{{ asset('img/logo.svg') }}" alt="Osole" class="h-9 w-9 shrink-0">

        <span class="text-[15px] font-semibold tracking-tight text-white">Osole Helpdesk</span>
    </div>

    <div class="px-3">
        <x-button :href="route('admin.tickets.create')" class="w-full">
            <i data-lucide="plus" class="h-4 w-4"></i> Nuevo ticket
        </x-button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto scrollbar-thin px-3 pb-4">
        <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-600">Operación</p>

        @can('tickets.viewAny')
            @php $on = request()->routeIs('admin.tickets.dashboard'); @endphp
            <a href="{{ route('admin.tickets.dashboard') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="layout-dashboard" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Dashboard
            </a>
            @php $on = request()->routeIs('admin.tickets.index') || request()->routeIs('admin.tickets.show') || request()->routeIs('admin.tickets.create'); @endphp
            <a href="{{ route('admin.tickets.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="ticket" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Tickets
            </a>
        @endcan

        @can('agents.manage')
            @php $on = request()->routeIs('admin.agents.*'); @endphp
            <a href="{{ route('admin.agents.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="users" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Agentes
            </a>
            @php $on = request()->routeIs('admin.users.*'); @endphp
            <a href="{{ route('admin.users.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="shield-check" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Usuarios
            </a>
        @endcan

        @if (auth()->user()?->isStaff())
            @php $on = request()->routeIs('admin.host-credentials.*'); @endphp
            <a href="{{ route('admin.host-credentials.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="server-cog" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Hosts / accesos
            </a>
        @endif

        @can('reports.view')
            @php $on = request()->routeIs('admin.reports.*'); @endphp
            <a href="{{ route('admin.reports.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="bar-chart-3" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Reportes
            </a>
        @endcan

        @can('settings.manage')
            <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-600">Configuración</p>
            @php $on = request()->routeIs('admin.assignment-settings.*'); @endphp
            <a href="{{ route('admin.assignment-settings.edit') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="user-check" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Asignación de tickets
            </a>
            @php $on = request()->routeIs('admin.telegram-alerts.*'); @endphp
            <a href="{{ route('admin.telegram-alerts.edit') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="send" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Avisos Telegram
            </a>
            @php $on = request()->routeIs('admin.ticket-settings.departments.*'); @endphp
            <a href="{{ route('admin.ticket-settings.departments.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="building-2" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Departamentos
            </a>
            @php $on = request()->routeIs('admin.ticket-settings.categories.*'); @endphp
            <a href="{{ route('admin.ticket-settings.categories.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="tags" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Categorías
            </a>
            @php $on = request()->routeIs('admin.ticket-settings.priorities.*'); @endphp
            <a href="{{ route('admin.ticket-settings.priorities.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="flag" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Prioridades
            </a>
            @php $on = request()->routeIs('admin.ticket-settings.statuses.*'); @endphp
            <a href="{{ route('admin.ticket-settings.statuses.index') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="circle-dot" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Estados
            </a>
        @endcan

        <p class="px-3 pb-1 pt-4 text-[11px] font-semibold uppercase tracking-wider text-slate-600">Cuenta</p>
        @role('Super Admin')
            @php $on = request()->routeIs('admin.site-settings.*'); @endphp
            <a href="{{ route('admin.site-settings.edit') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
                <i data-lucide="panel-top" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Sitio público
            </a>
        @endrole
        @php $on = request()->routeIs('admin.profile.*'); @endphp
        <a href="{{ route('admin.profile.edit') }}" class="{{ $linkBase }} {{ $on ? $active : $idle }}">
            <i data-lucide="circle-user-round" class="h-5 w-5 shrink-0 {{ $on ? $iconActive : $iconIdle }}"></i> Mi perfil
        </a>
    </nav>
</div>
