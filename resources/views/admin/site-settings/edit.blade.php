<x-layouts.admin title="Sitio público">
    <div class="mx-auto max-w-5xl space-y-5">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Sitio público</h1>
            <p class="mt-1 text-sm text-slate-500">Configurá cómo se contactan tus visitantes desde el sitio de soporte público. Estos cambios se aplican en el formulario y el seguimiento.</p>
        </div>

        <form
            method="POST"
            action="{{ route('admin.site-settings.update') }}"
            x-data="{
                number: @js(old('whatsapp_number', $settings['whatsapp_number'] ?? '')),
                enabled: @js((bool) old('whatsapp_enabled', $settings['whatsapp_enabled'])),
                greeting: @js(old('whatsapp_greeting', $settings['whatsapp_greeting'])),
            }"
        >
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-[1.35fr_1fr]">
                {{-- Configuration --}}
                <x-card class="p-5 lg:p-6">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-100">
                            <x-icon.whatsapp class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">Widget de WhatsApp</h2>
                            <p class="text-xs text-slate-500">Botón flotante de contacto en el sitio público.</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-5">
                        {{-- Enable toggle --}}
                        <div class="flex items-start justify-between gap-4 rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-slate-900">Mostrar el widget</p>
                                <p class="mt-0.5 text-xs leading-5 text-slate-500">Activá o desactivá el botón flotante en todas las páginas públicas.</p>
                            </div>
                            <button
                                type="button"
                                role="switch"
                                :aria-checked="enabled"
                                @click="enabled = ! enabled"
                                :class="enabled ? 'bg-emerald-600' : 'bg-slate-300'"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2"
                            >
                                <span :class="enabled ? 'translate-x-[1.375rem]' : 'translate-x-0.5'" class="mt-0.5 inline-block h-5 w-5 rounded-full bg-white shadow transition-transform"></span>
                            </button>
                            <input type="hidden" name="whatsapp_enabled" :value="enabled ? '1' : '0'">
                        </div>

                        {{-- Number --}}
                        <div>
                            <label for="whatsapp_number" class="label">Número de WhatsApp</label>
                            <input
                                id="whatsapp_number"
                                name="whatsapp_number"
                                type="tel"
                                inputmode="tel"
                                autocomplete="tel"
                                x-model="number"
                                placeholder="+54 9 11 1234 5678"
                                class="input"
                                @error('whatsapp_number') aria-invalid="true" @enderror
                            >
                            @error('whatsapp_number')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @else
                                @if ($wa_link)
                                    <p class="mt-1.5 text-xs text-slate-500">Enlace generado: <a href="{{ $wa_link }}" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline">{{ $wa_link }}</a></p>
                                @else
                                    <p class="mt-1.5 text-xs text-slate-500">Incluí el código de país. Ejemplo: <span class="font-medium text-slate-600">+54 9 11 1234 5678</span>.</p>
                                @endif
                            @enderror
                        </div>

                        {{-- Greeting --}}
                        <div>
                            <label for="whatsapp_greeting" class="label">Mensaje de saludo</label>
                            <textarea
                                id="whatsapp_greeting"
                                name="whatsapp_greeting"
                                rows="3"
                                x-model="greeting"
                                class="textarea"
                                placeholder="Hola, tengo una consulta y me gustaría que me ayuden."
                                @error('whatsapp_greeting') aria-invalid="true" @enderror
                            >{{ old('whatsapp_greeting', $settings['whatsapp_greeting']) }}</textarea>
                            @error('whatsapp_greeting')
                                <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                            @else
                                <p class="mt-1.5 text-xs text-slate-500">Es el mensaje que el visitante enviará al abrir WhatsApp (se carga ya escrito).</p>
                            @enderror
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end border-t border-slate-100 pt-5">
                        <x-button type="submit"><i data-lucide="check" class="h-4 w-4"></i> Guardar cambios</x-button>
                    </div>
                </x-card>

                {{-- Live preview --}}
                <div class="lg:sticky lg:top-6 lg:self-start">
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Vista previa</p>
                    <div class="relative overflow-hidden rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-100 to-slate-200/70 p-5">
                        {{-- faux browser dots --}}
                        <div class="mb-4 flex items-center gap-1.5">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-300"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-300"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>
                        </div>

                        <div class="flex flex-col items-end transition-opacity duration-200" :class="enabled ? 'opacity-100' : 'opacity-40 saturate-0'">
                            {{-- Replica of the floating card --}}
                            <div class="w-72 max-w-full overflow-hidden rounded-2xl bg-white shadow-xl ring-1 ring-slate-900/10">
                                <div class="flex items-center gap-3 bg-gradient-to-br from-[#128C7E] to-[#075E54] px-4 py-3.5 text-white">
                                    <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/25">
                                        <x-icon.whatsapp class="h-5 w-5 text-white" />
                                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#0e7d6a] bg-emerald-400"></span>
                                    </span>
                                    <div class="leading-tight">
                                        <p class="text-sm font-semibold">Osole Soporte</p>
                                        <p class="flex items-center gap-1.5 text-xs text-white/80"><span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span> En línea · responde rápido</p>
                                    </div>
                                </div>
                                <div class="bg-[#ECE5DD]/45 px-4 py-4">
                                    <div class="max-w-[14rem] rounded-2xl rounded-tl-sm bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-700 shadow-sm">¡Hola! 👋 ¿Te damos una mano? Escribinos por WhatsApp y te respondemos.</div>
                                </div>
                                <div class="border-t border-slate-100 px-4 py-3.5">
                                    <span class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl bg-[#075E54] px-4 py-2 text-sm font-semibold text-white"><x-icon.whatsapp class="h-4 w-4" /> Iniciar chat</span>
                                </div>
                            </div>
                            {{-- Replica of the FAB --}}
                            <span class="mt-3 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-[#25D366]/35">
                                <x-icon.whatsapp class="h-7 w-7" />
                            </span>
                        </div>

                        <p x-show="!enabled" x-cloak class="absolute inset-x-0 bottom-3 text-center text-xs font-medium text-slate-500">Widget desactivado</p>
                    </div>
                    <p class="mt-3 px-1 text-xs leading-5 text-slate-500">El visitante enviará: <span class="font-medium text-slate-700" x-text="greeting || '—'"></span></p>
                </div>
            </div>
        </form>
    </div>
</x-layouts.admin>
