@php
    use App\Models\SiteSetting;
    use App\Support\Whatsapp;

    $enabled = SiteSetting::getBool('whatsapp_enabled', true);
    $number = SiteSetting::get('whatsapp_number');
    $normalized = $number ? Whatsapp::normalize($number) : null;
    $greeting = SiteSetting::get('whatsapp_greeting', 'Hola, tengo una consulta y me gustaría que me ayuden.');
    $waUrl = $normalized ? Whatsapp::link($normalized, $greeting) : null;
@endphp

@if ($enabled && $waUrl)
    <div
        x-data="{ open: false }"
        @keydown.escape.window="open = false"
        @click.outside="open = false"
        class="fixed bottom-5 right-5 z-50 flex flex-col items-end print:hidden sm:bottom-6 sm:right-6"
    >
        {{-- Expandable chat card --}}
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition duration-300 ease-[cubic-bezier(0.16,1,0.3,1)]"
            x-transition:enter-start="opacity-0 translate-y-3 scale-90"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="opacity-100 translate-y-0 scale-100"
            x-transition:leave-end="opacity-0 translate-y-3 scale-95"
            role="dialog"
            aria-label="Chat de WhatsApp con Osole Soporte"
            class="mb-3 w-80 max-w-[calc(100vw-2.5rem)] origin-bottom-right overflow-hidden rounded-2xl bg-white shadow-2xl shadow-slate-900/30 ring-1 ring-slate-900/10"
        >
            {{-- Header --}}
            <div class="relative bg-gradient-to-br from-[#128C7E] to-[#075E54] px-4 py-3.5 text-white">
                <div class="flex items-center gap-3 pr-7">
                    <span class="relative flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-white/15 ring-1 ring-white/25 backdrop-blur">
                        <x-icon.whatsapp class="h-6 w-6 text-white" />
                        <span class="absolute -bottom-0.5 -right-0.5 h-3 w-3 rounded-full border-2 border-[#0e7d6a] bg-emerald-400"></span>
                    </span>
                    <div class="min-w-0 leading-tight">
                        <p class="text-sm font-semibold">Osole Soporte</p>
                        <p class="flex items-center gap-1.5 text-xs text-white/80">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span> En línea · responde rápido
                        </p>
                    </div>
                </div>
                <button
                    type="button"
                    @click="open = false"
                    aria-label="Cerrar"
                    class="absolute right-2.5 top-2.5 inline-flex h-7 w-7 items-center justify-center rounded-full text-white/80 transition hover:bg-white/15 hover:text-white focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            {{-- Conversation surface --}}
            <div class="space-y-3 bg-[#ECE5DD]/45 px-4 py-4">
                <div
                    x-show="open"
                    x-transition:enter="transition delay-150 duration-300 ease-out"
                    x-transition:enter-start="opacity-0 translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="relative max-w-[15rem] rounded-2xl rounded-tl-sm bg-white px-3.5 py-2.5 text-sm leading-6 text-slate-700 shadow-sm"
                >
                    ¡Hola! 👋 ¿Te damos una mano? Escribinos por WhatsApp y te respondemos a la brevedad.
                    <span class="mt-1 block text-right text-[10px] font-medium text-slate-400">Osole Soporte</span>
                </div>
            </div>

            {{-- Action --}}
            <div class="border-t border-slate-100 bg-white px-4 py-3.5">
                <a
                    href="{{ $waUrl }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-[#075E54] px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-[#0b6f62] focus:outline-none focus-visible:ring-2 focus-visible:ring-[#075E54] focus-visible:ring-offset-2"
                >
                    <x-icon.whatsapp class="h-4 w-4" /> Iniciar chat
                </a>
                <p class="mt-2 text-center text-[11px] leading-4 text-slate-400">Te abrimos WhatsApp con el mensaje listo para enviar.</p>
            </div>
        </div>

        {{-- Floating action button --}}
        <button
            type="button"
            @click="open = ! open"
            :aria-label="open ? 'Cerrar el chat de WhatsApp' : 'Abrir el chat de WhatsApp'"
            :aria-expanded="open"
            class="group relative inline-flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-xl shadow-[#25D366]/40 transition duration-200 ease-out hover:scale-110 hover:shadow-2xl hover:shadow-[#25D366]/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#25D366] focus-visible:ring-offset-2 focus-visible:ring-offset-canvas active:scale-90"
        >
            {{-- "latent" double attention pulse (only when closed) --}}
            <span x-show="!open" class="pointer-events-none absolute inset-0 animate-ping rounded-full bg-[#25D366] opacity-50 motion-reduce:hidden"></span>
            <span x-show="!open" class="pointer-events-none absolute inset-0 animate-ping rounded-full bg-[#25D366] opacity-30 [animation-delay:0.8s] motion-reduce:hidden"></span>

            {{-- unread badge (only when closed) --}}
            <span x-show="!open" class="absolute -right-0.5 -top-0.5 flex h-5 w-5 items-center justify-center rounded-full bg-white text-[11px] font-bold text-[#075E54] shadow ring-2 ring-[#25D366]">1</span>

            {{-- icon: morphs between WhatsApp and X --}}
            <span x-show="!open"
                  x-transition:enter="transition duration-200 ease-out"
                  x-transition:enter-start="opacity-0 rotate-45 scale-50"
                  x-transition:enter-end="opacity-100 rotate-0 scale-100"
                  class="relative">
                <x-icon.whatsapp class="h-7 w-7" />
            </span>
            <i x-show="open" x-cloak
               x-transition:enter="transition duration-200 ease-out"
               x-transition:enter-start="opacity-0 -rotate-90 scale-50"
               x-transition:enter-end="opacity-100 rotate-0 scale-100"
               data-lucide="x" class="relative h-6 w-6"></i>
        </button>
    </div>
@endif
