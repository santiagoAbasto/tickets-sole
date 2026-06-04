<x-layouts.portal title="Nueva consulta" :show-new="false">
    <a href="{{ route('portal.tickets.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i> Volver</a>
    <h1 class="mb-1 mt-3 text-2xl font-semibold tracking-tight text-slate-900">¿En qué te ayudamos?</h1>
    <p class="mb-6 text-sm text-slate-500">Contanos tu consulta y te respondemos a la brevedad.</p>

    <form method="POST" action="{{ route('portal.tickets.store') }}" enctype="multipart/form-data" class="space-y-5 rounded-2xl border border-slate-200 bg-surface p-6 shadow-sm">
        @csrf
        <div>
            <label class="label">Asunto <span class="text-rose-500">*</span></label>
            <input name="subject" value="{{ old('subject') }}" class="input" placeholder="Ej. No puedo acceder a mi correo">
            @error('subject')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label class="label">Categoría <span class="text-rose-500">*</span></label>
                <select name="category_id" class="select"><option value="">Elegí una opción…</option>
                    @foreach ($categories as $c)<option value="{{ $c['id'] }}" @selected(old('category_id') == $c['id'])>{{ $c['name'] }}</option>@endforeach
                </select>
                @error('category_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="label">Prioridad <span class="text-rose-500">*</span></label>
                <select name="priority_id" class="select">
                    @foreach ($priorities as $p)<option value="{{ $p['id'] }}" @selected(old('priority_id', $priorities->firstWhere('slug','media')['id'] ?? null) == $p['id'])>{{ $p['name'] }}</option>@endforeach
                </select>
                @error('priority_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
        <div>
            <label class="label">Descripción <span class="text-rose-500">*</span></label>
            <textarea name="description" rows="6" class="textarea" placeholder="Describí el problema con el mayor detalle posible…">{{ old('description') }}</textarea>
            @error('description')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Adjuntos (opcional)</label>
            <x-attachment-input />
        </div>
        <div class="flex justify-end gap-2 pt-1">
            <x-button variant="secondary" :href="route('portal.tickets.index')">Cancelar</x-button>
            <x-button type="submit">Enviar consulta</x-button>
        </div>
    </form>
</x-layouts.portal>
