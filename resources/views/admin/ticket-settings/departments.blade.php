<x-layouts.admin title="Departamentos">
    <div class="space-y-5"
         x-data="{ open:false, mode:'new', id:null, form:{ name:'', slug:'', color:'#6366f1', description:'', sort_order:0, is_active:true },
                   newItem(){ this.mode='new'; this.id=null; this.form={ name:'', slug:'', color:'#6366f1', description:'', sort_order:0, is_active:true }; this.open=true; },
                   editItem(row){ this.mode='edit'; this.id=row.id; this.form={ name:row.name, slug:row.slug, color:row.color, description:row.description||'', sort_order:row.sort_order||0, is_active:!!row.is_active }; this.open=true; },
                   get action(){ return this.mode==='edit' ? '{{ url('admin/ticket-settings/departments') }}/'+this.id : '{{ route('admin.ticket-settings.departments.store') }}'; } }">

        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Departamentos</h1>
                <p class="mt-1 text-sm text-slate-500">Áreas internas para organizar tickets, agentes y reportes.</p>
            </div>
            <x-button @click="newItem()"><i data-lucide="plus" class="h-4 w-4"></i> Nuevo departamento</x-button>
        </div>

        <x-card class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400">
                            <th class="px-5 py-3">Departamento</th>
                            <th class="px-3 py-3">Slug</th>
                            <th class="px-3 py-3">Estado</th>
                            <th class="px-3 py-3">Agentes</th>
                            <th class="px-3 py-3">Tickets</th>
                            <th class="px-5 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($departments as $d)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-3">
                                    <span class="inline-flex items-center gap-2.5">
                                        <span class="h-3 w-3 rounded-full" style="background-color: {{ $d->color }}"></span>
                                        <span class="font-medium text-slate-800">{{ $d->name }}</span>
                                    </span>
                                    @if ($d->description)<p class="mt-1 max-w-md truncate text-xs text-slate-500">{{ $d->description }}</p>@endif
                                </td>
                                <td class="px-3 py-3 font-mono text-xs text-slate-500">{{ $d->slug }}</td>
                                <td class="px-3 py-3 text-slate-600">{{ $d->is_active ? 'Activo' : 'Inactivo' }}</td>
                                <td class="px-3 py-3 tabular-nums text-slate-500">{{ $d->agents_count }}</td>
                                <td class="px-3 py-3 tabular-nums text-slate-500">{{ $d->tickets_count }}</td>
                                <td class="px-5 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <button type="button" @click='editItem(@json($d))' class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                        <x-confirm-form :action="route('admin.ticket-settings.departments.destroy', $d->id)" title="Eliminar departamento" :message="'¿Eliminar &quot;'.$d->name.'&quot;? Solo se permite si no tiene usuarios ni tickets.'" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><i data-lucide="trash-2" class="h-4 w-4"></i></x-confirm-form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6"><x-empty-state icon="building-2" title="Sin departamentos" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>

        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-[100]" @keydown.escape.window="open=false">
                <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/40"></div>
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div x-show="open" x-transition x-trap.noscroll="open" @click.outside="open=false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-900/5">
                        <h3 class="text-base font-semibold text-slate-900" x-text="mode==='edit' ? 'Editar departamento' : 'Nuevo departamento'"></h3>
                        <form :action="action" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="_method" :value="mode==='edit' ? 'PUT' : 'POST'">
                            <div><label class="label">Nombre</label><input name="name" x-model="form.name" class="input"></div>
                            <div><label class="label">Slug</label><input name="slug" x-model="form.slug" class="input font-mono" placeholder="soporte"></div>
                            <div><label class="label">Color</label><div class="flex items-center gap-2"><input type="color" x-model="form.color" class="h-10 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white p-1"><input name="color" x-model="form.color" class="input font-mono"></div></div>
                            <div><label class="label">Descripción</label><input name="description" x-model="form.description" class="input"></div>
                            <div><label class="label">Orden</label><input type="number" min="0" name="sort_order" x-model="form.sort_order" class="input"></div>
                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Activo</label>
                            <div class="flex justify-end gap-2 pt-2"><x-button variant="secondary" type="button" @click="open=false">Cancelar</x-button><x-button type="submit">Guardar</x-button></div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.admin>
