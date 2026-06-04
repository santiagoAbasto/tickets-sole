<x-layouts.admin title="Estados">
    <div class="space-y-5"
         x-data="{ open:false, mode:'new', id:null, form:{ name:'', color:'#3b82f6', is_final:false, is_resolved:false, is_default:false, is_active:true },
                   newItem(){ this.mode='new'; this.id=null; this.form={ name:'', color:'#3b82f6', is_final:false, is_resolved:false, is_default:false, is_active:true }; this.open=true; },
                   editItem(row){ this.mode='edit'; this.id=row.id; this.form={ name:row.name, color:row.color, is_final:!!row.is_final, is_resolved:!!row.is_resolved, is_default:!!row.is_default, is_active:!!row.is_active }; this.open=true; },
                   get action(){ return this.mode==='edit' ? '{{ url('admin/ticket-settings/statuses') }}/'+this.id : '{{ route('admin.ticket-settings.statuses.store') }}'; } }">

        <div class="flex items-end justify-between gap-4">
            <div><h1 class="text-xl font-semibold tracking-tight text-slate-900">Estados</h1><p class="mt-1 text-sm text-slate-500">Etapas del flujo. Los finales salen del cálculo de atrasos.</p></div>
            <x-button @click="newItem()"><i data-lucide="plus" class="h-4 w-4"></i> Nuevo estado</x-button>
        </div>

        <x-card class="overflow-hidden">
            <table class="w-full text-sm">
                <thead><tr class="border-b border-slate-200 text-left text-xs font-semibold uppercase tracking-wide text-slate-400"><th class="px-5 py-3">Nombre</th><th class="px-3 py-3">Final</th><th class="px-3 py-3">Cuenta resuelto</th><th class="px-3 py-3">Tickets</th><th class="px-5 py-3 text-right">Acciones</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($statuses as $s)
                        <tr class="hover:bg-slate-50">
                            <td class="px-5 py-3"><span class="inline-flex items-center gap-2.5"><span class="h-3 w-3 rounded-full" style="background-color: {{ $s->color }}"></span><span class="font-medium text-slate-800">{{ $s->name }}</span>@if($s->is_default)<span class="rounded-full bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-500">default</span>@endif</span></td>
                            <td class="px-3 py-3 text-slate-600">{{ $s->is_final ? 'Sí' : 'No' }}</td>
                            <td class="px-3 py-3 text-slate-600">{{ $s->is_resolved ? 'Sí' : 'No' }}</td>
                            <td class="px-3 py-3 tabular-nums text-slate-500">{{ $s->tickets_count }}</td>
                            <td class="px-5 py-3"><div class="flex items-center justify-end gap-1">
                                <button type="button" @click='editItem(@json($s))' class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700"><i data-lucide="pencil" class="h-4 w-4"></i></button>
                                <x-confirm-form :action="route('admin.ticket-settings.statuses.destroy', $s->id)" title="Eliminar estado" :message="'¿Eliminar &quot;'.$s->name.'&quot;?'" class="rounded-lg p-1.5 text-slate-400 hover:bg-rose-50 hover:text-rose-600"><i data-lucide="trash-2" class="h-4 w-4"></i></x-confirm-form>
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
                        <h3 class="text-base font-semibold text-slate-900" x-text="mode==='edit' ? 'Editar estado' : 'Nuevo estado'"></h3>
                        <form :action="action" method="POST" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="_method" :value="mode==='edit' ? 'PUT' : 'POST'">
                            <div><label class="label">Nombre</label><input name="name" x-model="form.name" class="input"></div>
                            <div><label class="label">Color</label><div class="flex items-center gap-2"><input type="color" x-model="form.color" class="h-10 w-12 cursor-pointer rounded-lg border border-slate-200 bg-white p-1"><input name="color" x-model="form.color" class="input font-mono"></div></div>
                            <div class="space-y-2 rounded-lg bg-slate-50 p-3">
                                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_final" value="1" x-model="form.is_final" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Estado final (cierra el ticket)</label>
                                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_resolved" value="1" x-model="form.is_resolved" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Cuenta como resuelto (métricas)</label>
                                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_default" value="1" x-model="form.is_default" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Estado por defecto al crear</label>
                                <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="is_active" value="1" x-model="form.is_active" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500"> Activo</label>
                            </div>
                            <div class="flex justify-end gap-2 pt-2"><x-button variant="secondary" type="button" @click="open=false">Cancelar</x-button><x-button type="submit">Guardar</x-button></div>
                        </form>
                    </div>
                </div>
            </div>
        </template>
    </div>
</x-layouts.admin>
