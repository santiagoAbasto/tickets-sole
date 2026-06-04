<x-layouts.admin title="Prioridades">
    <div class="space-y-5"
         x-data="{ open:false, mode:'new', id:null, form:{ name:'', color:'#f59e0b', response_hours:8, resolution_hours:24, level:2, is_active:true },
                   newItem(){ this.mode='new'; this.id=null; this.form={ name:'', color:'#f59e0b', response_hours:8, resolution_hours:24, level:2, is_active:true }; this.open=true; },
                   editItem(row){ this.mode='edit'; this.id=row.id; this.form={ name:row.name, color:row.color, response_hours:row.response_hours, resolution_hours:row.resolution_hours, level:row.level, is_active:!!row.is_active }; this.open=true; },
                   get action(){ return this.mode==='edit' ? '{{ url('admin/ticket-settings/priorities') }}/'+this.id : '{{ route('admin.ticket-settings.priorities.store') }}'; } }">

        <div class="flex items-end justify-between gap-4">
            <div><h1 class="text-xl font-semibold tracking-tight text-slate-900">Prioridades</h1><p class="mt-1 text-sm text-slate-500">Definen el SLA: horas de respuesta y resolución.</p></div>
            <x-button @click="newItem()"><i data-lucide="plus" class="h-4 w-4"></i> Nueva prioridad</x-button>
        </div>

        <x-card class="overflow-hidden">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400"><th class="px-5 py-3">Nombre</th><th class="px-3 py-3">Respuesta</th><th class="px-3 py-3">Resolución</th><th class="px-3 py-3">Tickets</th><th class="px-5 py-3 text-right">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($priorities as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3"><span class="inline-flex items-center gap-2.5"><span class="h-3 w-3 rounded-full" style="background-color: {{ $p->color }}"></span><span class="font-medium text-slate-800">{{ $p->name }}</span></span></td>
                            <td class="px-3 py-3 text-slate-600">{{ $p->response_hours }} h</td>
                            <td class="px-3 py-3 text-slate-600">{{ $p->resolution_hours }} h</td>
                            <td class="px-3 py-3 tabular-nums text-slate-500">{{ $p->tickets_count }}</td>
                            <td class="px-5 py-3"><div class="flex items-center justify-end gap-1">
                                <button type="button" @click='editItem(@json($p))' class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                <x-confirm-form :action="route('admin.ticket-settings.priorities.destroy', $p->id)" title="Eliminar prioridad" :message="'¿Eliminar &quot;'.$p->name.'&quot;?'" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><i data-lucide="trash-2" class="h-4 w-4"></i></x-confirm-form>
                            </div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-card>

        <template x-teleport="body">
            <div x-show="open" x-cloak class="fixed inset-0 z-[100]" @keydown.escape.window="open=false">
                <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/40"></div>
                <div class="fixed inset-0 flex items-center justify-center p-4">
                    <div x-show="open" x-transition x-trap.noscroll="open" @click.outside="open=false" class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl ring-1 ring-slate-900/5">
                        <h3 class="text-base font-semibold text-slate-900" x-text="mode==='edit' ? 'Editar prioridad' : 'Nueva prioridad'"></h3>
                        <form :action="action" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="_method" :value="mode==='edit' ? 'PUT' : 'POST'">
                            <div><label class="label">Nombre</label><input name="name" x-model="form.name" class="input"></div>
                            <div><label class="label">Color</label><div class="flex items-center gap-2"><input type="color" x-model="form.color" class="h-10 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white p-1"><input name="color" x-model="form.color" class="input font-mono"></div></div>
                            <div class="grid grid-cols-2 gap-4">
                                <div><label class="label">Horas de respuesta</label><input type="number" min="1" name="response_hours" x-model="form.response_hours" class="input"></div>
                                <div><label class="label">Horas de resolución</label><input type="number" min="1" name="resolution_hours" x-model="form.resolution_hours" class="input"></div>
                            </div>
                            <div><label class="label">Nivel (1 baja → 4 urgente)</label><input type="number" min="1" max="10" name="level" x-model="form.level" class="input"></div>
                            <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Activa</label>
                            <div class="flex justify-end gap-2 pt-2"><x-button variant="secondary" type="button" @click="open=false">Cancelar</x-button><x-button type="submit">Guardar</x-button></div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.admin>
