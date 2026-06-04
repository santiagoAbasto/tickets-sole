<x-layouts.admin title="Usuarios">
    <div class="space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Usuarios internos</h1>
                <p class="mt-1 text-sm text-slate-500">Super Admin, administradores y agentes. El Super Admin queda protegido ante cambios de Admin.</p>
            </div>
            <x-button :href="route('admin.users.create')"><i data-lucide="user-plus" class="h-4 w-4"></i> Nuevo usuario</x-button>
        </div>

        <x-card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Usuario</th>
                            <th class="px-3 py-3">Rol</th>
                            <th class="px-3 py-3">Departamento</th>
                            <th class="px-3 py-3">Estado</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $u)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <div class="flex items-center gap-3">
                                        <x-avatar :name="$u['name']" :src="$u['avatar_url']" size="sm" />
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-slate-800">{{ $u['name'] }}</p>
                                            <p class="truncate text-xs text-slate-500">{{ $u['email'] }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold ring-1 ring-inset {{ $u['role'] === 'Super Admin' ? 'bg-violet-50 text-violet-700 ring-violet-200' : ($u['role'] === 'Admin' ? 'bg-sky-50 text-sky-700 ring-sky-200' : 'bg-slate-50 text-slate-700 ring-slate-200') }}">
                                        {{ $u['role'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 text-slate-600">
                                    @if ($u['department'])
                                        <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background-color: {{ $u['department']['color'] }}"></span>{{ $u['department']['name'] }}</span>
                                    @else
                                        <span class="text-slate-400">Sin departamento</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold {{ $u['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                        {{ $u['is_active'] ? 'Activo' : 'Sin acceso' }}
                                    </span>
                                </td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1.5">
                                        @if ($u['can_manage'])
                                            <a href="{{ route('admin.users.edit', $u['id']) }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900">
                                                <i data-lucide="pencil" class="h-3.5 w-3.5"></i> Editar
                                            </a>
                                        @else
                                            <span class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-slate-400" title="Solo Super Admin puede modificar esta cuenta">
                                                <i data-lucide="lock" class="h-3.5 w-3.5"></i> Protegido
                                            </span>
                                        @endif

                                        @if ($u['can_remove_access'] && $u['is_active'])
                                            <x-confirm-form :action="route('admin.users.destroy', $u['id'])" title="Eliminar acceso" :message="'Se desactivará el acceso de '.$u['name'].'. El historial de tickets se mantiene.'" confirm="Eliminar acceso" class="inline-flex h-8 items-center gap-1.5 rounded-lg px-2.5 text-xs font-medium text-rose-600 hover:bg-rose-50">
                                                <i data-lucide="user-x" class="h-3.5 w-3.5"></i> Eliminar
                                            </x-confirm-form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5"><x-empty-state icon="users" title="Sin usuarios internos" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</x-layouts.admin>
