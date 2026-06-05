<x-layouts.admin title="Asignación de tickets">
    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Asignación de tickets</h1>
            <p class="mt-1 text-sm text-slate-500">Elegí a quién le caen los tickets nuevos por defecto. Cuando un agente o el formulario web crea un ticket, se asigna automáticamente a esta persona, que luego puede solicitar delegarlo.</p>
        </div>

        <form method="POST" action="{{ route('admin.assignment-settings.update') }}">
            @csrf
            @method('PUT')
            <x-card class="p-5 lg:p-6">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-inset ring-brand-100">
                        <i data-lucide="user-check" class="h-5 w-5"></i>
                    </span>
                    <div>
                        <h2 class="text-sm font-semibold text-slate-900">Agente predeterminado</h2>
                        <p class="text-xs text-slate-500">A quién le cae cada ticket nuevo.</p>
                    </div>
                </div>

                <div class="mt-5">
                    <label for="default_assignee_id" class="label">Agente</label>
                    <select id="default_assignee_id" name="default_assignee_id" class="select">
                        <option value="">Sin predeterminado (queda sin asignar)</option>
                        @foreach ($agents as $a)
                            <option value="{{ $a->id }}" @selected((string) $current === (string) $a->id)>{{ $a->name }}@if ($a->job_title) · {{ $a->job_title }}@endif</option>
                        @endforeach
                    </select>
                    @error('default_assignee_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-2 text-xs leading-5 text-slate-500">Aplica a tickets creados por agentes, por el formulario público y por el portal del cliente. Un Admin o Super Admin puede igual elegir otro agente al crear el ticket.</p>
                </div>

                <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                    <x-button type="submit"><i data-lucide="check" class="h-4 w-4"></i> Guardar</x-button>
                </div>
            </x-card>
        </form>
    </div>
</x-layouts.admin>
