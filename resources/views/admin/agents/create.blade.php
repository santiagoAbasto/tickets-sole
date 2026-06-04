<x-layouts.admin title="Nuevo agente">
    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <a href="{{ route('admin.agents.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i> Volver a agentes</a>
            <h1 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">Nuevo agente</h1>
        </div>
        <form method="POST" action="{{ route('admin.agents.store') }}" enctype="multipart/form-data">
            @csrf
            <x-card class="p-5">
                @include('partials.admin.agent-fields', ['agent' => null, 'options' => $options])
            </x-card>
            <div class="mt-4 flex justify-end gap-2">
                <x-button variant="secondary" :href="route('admin.agents.index')">Cancelar</x-button>
                <x-button type="submit">Crear agente</x-button>
            </div>
        </form>
    </div>
</x-layouts.admin>
