<x-layouts.admin title="Avisos por Telegram">
    @php
        $rows = count($recipients) ? array_values($recipients) : [['name' => '', 'chat_id' => '']];
    @endphp

    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Avisos por Telegram</h1>
            <p class="mt-1 text-sm text-slate-500">Cuando entra un ticket nuevo (por agente, formulario web o portal), se manda un mensaje de Telegram a los destinatarios de acá. Es <span class="font-medium text-slate-600">solo aviso</span>: llega la notificación, no se responde desde Telegram.</p>
        </div>

        {{-- Cómo crear el bot + obtener chat_id --}}
        <div class="rounded-2xl border border-sky-200 bg-sky-50 p-4">
            <p class="flex items-center gap-2 text-sm font-semibold text-sky-900">
                <i data-lucide="send" class="h-4 w-4"></i> Configuración inicial (una sola vez)
            </p>
            <ol class="mt-2 list-decimal space-y-1 pl-5 text-sm text-sky-800">
                <li>En Telegram, abrí <span class="font-semibold">@BotFather</span> y mandá <span class="font-mono text-xs">/newbot</span>. Elegí nombre y usuario; te da un <span class="font-semibold">token</span> (algo como <span class="font-mono text-xs">123456:ABC...</span>). Pegalo abajo y tocá Guardar.</li>
                <li>Cada persona que quiera recibir avisos tiene que <span class="font-semibold">escribirle algo al bot primero</span> (por ejemplo <span class="font-mono text-xs">/start</span>). Si no, Telegram no deja que el bot le escriba.</li>
                <li>Tocá <span class="font-semibold">Detectar chat IDs</span>: te muestra el ID de cada persona que le escribió. Cargalos abajo y guardá.</li>
            </ol>
        </div>

        {{-- Configuración --}}
        <form method="POST" action="{{ route('admin.telegram-alerts.update') }}"
              x-data="{ rows: @js($rows) }">
            @csrf
            @method('PUT')
            <x-card class="space-y-6 p-5 lg:p-6">
                {{-- Toggle activar --}}
                <label class="flex cursor-pointer items-center gap-3">
                    <input type="checkbox" name="enabled" value="1" @checked($enabled) class="peer sr-only">
                    <span class="relative h-6 w-11 shrink-0 rounded-full bg-slate-200 transition-colors peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500 peer-focus-visible:ring-offset-2 after:absolute after:left-0.5 after:top-0.5 after:h-5 after:w-5 after:rounded-full after:bg-white after:shadow after:transition-transform peer-checked:after:translate-x-5"></span>
                    <span class="text-sm font-medium text-slate-700">Activar avisos por Telegram</span>
                </label>

                {{-- Token --}}
                <div>
                    <label for="token" class="label">Token del bot</label>
                    <input id="token" name="token" value="{{ old('token', $token) }}" class="input" autocomplete="off" placeholder="123456789:AAE...">
                    @error('token')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-xs leading-5 text-slate-500">El token que te dio @BotFather. Solo lo ve el equipo.</p>
                </div>

                {{-- Destinatarios --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="label mb-0">Destinatarios</span>
                        <button type="button" @click="rows.push({ name: '', chat_id: '' })"
                                class="inline-flex items-center gap-1 text-xs font-medium text-brand-600 hover:text-brand-700">
                            <i data-lucide="plus" class="h-3.5 w-3.5"></i> Agregar
                        </button>
                    </div>

                    <template x-for="(row, i) in rows" :key="i">
                        <div class="flex items-center gap-2">
                            <input :name="`recipients[${i}][name]`" x-model="row.name" class="input" placeholder="Nombre (opcional)">
                            <input :name="`recipients[${i}][chat_id]`" x-model="row.chat_id" class="input" inputmode="numeric" placeholder="chat ID (ej: 123456789)">
                            <button type="button" @click="rows.splice(i, 1)" x-show="rows.length > 1"
                                    class="shrink-0 rounded-lg px-2.5 py-2 text-xs font-medium text-rose-600 transition-colors hover:bg-rose-50">Quitar</button>
                        </div>
                    </template>

                    <p class="text-xs leading-5 text-slate-500">Las filas sin chat ID se descartan al guardar.</p>
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 pt-5">
                    <span class="text-xs text-slate-400">Guardá antes de Detectar o Probar.</span>
                    <x-button type="submit"><i data-lucide="check" class="h-4 w-4"></i> Guardar</x-button>
                </div>
            </x-card>
        </form>

        {{-- Detectar chat IDs --}}
        <x-card class="flex flex-wrap items-center justify-between gap-3 p-5">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Detectar chat IDs</h2>
                <p class="text-xs text-slate-500">Muestra el ID de quienes ya le escribieron al bot. Copialos a los destinatarios.</p>
            </div>
            <form method="POST" action="{{ route('admin.telegram-alerts.detect') }}">
                @csrf
                <x-button type="submit" variant="secondary"><i data-lucide="search" class="h-4 w-4"></i> Detectar</x-button>
            </form>
        </x-card>

        {{-- Probar envío --}}
        <x-card class="space-y-4 p-5">
            <div>
                <h2 class="text-sm font-semibold text-slate-900">Probar envío</h2>
                <p class="text-xs text-slate-500">Mandá un mensaje de prueba a un chat ID para confirmar que funciona.</p>
            </div>
            <form method="POST" action="{{ route('admin.telegram-alerts.test') }}" class="flex flex-wrap items-start gap-3">
                @csrf
                <div class="flex-1">
                    <input name="chat_id" value="{{ old('chat_id') }}" class="input" inputmode="numeric" placeholder="chat ID">
                    @error('chat_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
                <x-button type="submit" variant="secondary"><i data-lucide="send" class="h-4 w-4"></i> Enviar prueba</x-button>
            </form>
        </x-card>
    </div>
</x-layouts.admin>
