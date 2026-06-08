<x-layouts.public title="Soporte Osole" :hero="true">
    <section id="inicio" x-data="supportHero" @mousemove="track($event)"
             class="relative isolate overflow-hidden bg-sidebar pt-[72px] text-white">
        <div id="hero-3d" class="pointer-events-none absolute inset-0 z-0 opacity-70"></div>
        <div class="support-grid pointer-events-none absolute inset-0 z-0 opacity-60"></div>
        <div class="aurora pointer-events-none absolute inset-0 z-0 opacity-35 mix-blend-screen"></div>
        <div class="support-beam pointer-events-none absolute inset-x-0 top-0 z-0 h-px"></div>
        <div class="pointer-events-none absolute inset-0 z-0 bg-[radial-gradient(circle_at_18%_18%,rgba(20,184,166,.24),transparent_32%),radial-gradient(circle_at_78%_20%,rgba(79,70,229,.34),transparent_34%),radial-gradient(circle_at_82%_78%,rgba(249,115,22,.14),transparent_28%),linear-gradient(180deg,rgba(11,18,32,.22),#0b1220_86%)]"></div>

        <div class="relative z-10 mx-auto grid min-h-[calc(100dvh-72px)] max-w-[1224px] items-start gap-8 px-4 py-5 sm:px-6 lg:grid-cols-[0.82fr_1.18fr] lg:gap-10 lg:py-6 xl:px-0">
            <div class="order-2 max-w-2xl self-center lg:order-1">
                <div class="animate-rise inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/10 px-3 py-1.5 text-sm font-medium text-cyan-100 shadow-2xl shadow-brand-950/30 backdrop-blur">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 shadow-[0_0_18px_rgba(52,211,153,.85)]"></span>
                    Soporte con seguimiento real
                </div>

                <h1 class="animate-rise mt-6 max-w-xl text-4xl font-semibold leading-tight tracking-tight text-white [animation-delay:80ms] [text-wrap:balance] sm:text-5xl">
                    Abrí tu ticket y entrá al chat.
                </h1>

                <p class="animate-rise mt-5 max-w-xl text-base leading-8 text-slate-100 [animation-delay:150ms] [text-wrap:pretty] sm:text-lg">
                    Completá el formulario, adjuntá lo importante y quedás dentro del chat del caso al instante. El equipo sigue la conversación desde un único hilo.
                </p>

                <div class="animate-rise mt-7 flex flex-col gap-3 [animation-delay:220ms] sm:flex-row">
                    <a href="{{ route('public.track.form') }}"
                       class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-cyan-200/20 bg-white/10 px-5 py-3 text-sm font-semibold text-white shadow-xl shadow-black/20 backdrop-blur transition hover:-translate-y-0.5 hover:bg-white/16 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-sidebar">
                        <i data-lucide="search-check" class="h-4 w-4 text-brand-200"></i>
                        Ver estado del ticket
                    </a>
                </div>

                <div class="mt-7 grid max-w-xl grid-cols-3 divide-x divide-cyan-100/10 border-y border-cyan-100/15 text-sm">
                    <div class="py-4 pr-4">
                        <span class="block text-2xl font-semibold text-cyan-100">3 min</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-300">para dejar el caso claro</span>
                    </div>
                    <div class="px-4 py-4">
                        <span class="block text-2xl font-semibold text-cyan-100">Hábil</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-300">respuesta según prioridad</span>
                    </div>
                    <div class="py-4 pl-4">
                        <span class="block text-2xl font-semibold text-cyan-100">1 código</span>
                        <span class="mt-1 block text-xs leading-5 text-slate-300">para seguir el hilo</span>
                    </div>
                </div>
            </div>

            <div id="form" class="order-1 animate-rise relative [animation-delay:130ms] lg:order-2">
                <div class="support-ring support-ring-one" aria-hidden="true"></div>
                <div class="support-ring support-ring-two" aria-hidden="true"></div>
                <div class="support-node support-node-a hidden motion-reduce:hidden lg:flex" aria-hidden="true"><i data-lucide="mail-check" class="h-4 w-4"></i></div>
                <div class="support-node support-node-b hidden motion-reduce:hidden lg:flex" aria-hidden="true"><i data-lucide="paperclip" class="h-4 w-4"></i></div>
                <div class="support-node support-node-c hidden motion-reduce:hidden lg:flex" aria-hidden="true"><i data-lucide="message-circle" class="h-4 w-4"></i></div>

                <form method="POST" action="{{ route('public.support.store') }}" enctype="multipart/form-data"
                      class="relative z-10 overflow-hidden rounded-2xl border border-cyan-100/20 bg-white p-4 text-slate-700 shadow-2xl shadow-black/35 sm:p-5"
                      x-data="{ sending: false }"
                      @submit="sending = true">
                    @csrf

                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-500 via-sky-500 to-emerald-400"></div>

                    <div class="mb-4 flex flex-col gap-2 border-b border-slate-200 pb-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-brand-700">Nuevo ticket</p>
                            <h2 class="text-2xl font-semibold tracking-tight text-slate-950">Contanos qué pasó</h2>
                        </div>
                        <span class="inline-flex w-fit items-center gap-2 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 ring-1 ring-emerald-200">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                            Entrás al chat al enviar
                        </span>
                    </div>

                    <div aria-hidden="true" style="position:absolute; left:-9999px; top:auto; width:1px; height:1px; overflow:hidden;">
                        <label>No completar este campo<input type="text" name="company_fax" tabindex="-1" autocomplete="off"></label>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label for="name" class="label">Nombre <span class="text-rose-500">*</span></label>
                            <input id="name" name="name" value="{{ old('name') }}" class="input" placeholder="Tu nombre" @error('name') aria-invalid="true" @enderror>
                            @error('name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="label">Email <span class="text-rose-500">*</span></label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" class="input" placeholder="vos@email.com" @error('email') aria-invalid="true" @enderror>
                            @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="label">Teléfono <span class="text-slate-400">(opcional)</span></label>
                            <input id="phone" name="phone" value="{{ old('phone') }}" class="input" placeholder="+54 ...">
                        </div>
                        <div>
                            <label for="category_id" class="label">Categoría <span class="text-rose-500">*</span></label>
                            <select id="category_id" name="category_id" class="select" @error('category_id') aria-invalid="true" @enderror>
                                <option value="">Elegí una opción...</option>
                                @foreach ($categories as $c)<option value="{{ $c['id'] }}" @selected(old('category_id') == $c['id'])>{{ $c['name'] }}</option>@endforeach
                            </select>
                            @error('category_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="mt-3">
                        <label for="site_url" class="label">Sitio web / sistema <span class="text-slate-400">(opcional)</span></label>
                        <div class="relative">
                            <i data-lucide="globe" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400"></i>
                            <input id="site_url" name="site_url" value="{{ old('site_url') }}" class="input pl-9" placeholder="https://tusitio.com o sistema afectado">
                        </div>
                        @error('site_url')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-3">
                        <label for="subject" class="label">Asunto <span class="text-rose-500">*</span></label>
                        <input id="subject" name="subject" value="{{ old('subject') }}" class="input" placeholder="Ej. No puedo acceder al panel" @error('subject') aria-invalid="true" @enderror>
                        @error('subject')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-3">
                        <label for="description" class="label">Qué está pasando <span class="text-rose-500">*</span></label>
                        <textarea id="description" name="description" rows="4" class="textarea" placeholder="Contanos qué ocurre, qué esperabas ver y desde cuándo pasa." @error('description') aria-invalid="true" @enderror>{{ old('description') }}</textarea>
                        @error('description')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-3">
                        <label class="label">Adjuntos <span class="text-slate-400">(opcional)</span></label>
                        <x-attachment-input compact />
                        @error('attachments.0')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="mt-4 flex flex-col-reverse items-stretch justify-between gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center">
                        <p class="flex items-center gap-2 text-sm leading-6 text-slate-500"><i data-lucide="lock" class="h-4 w-4 text-emerald-600"></i> Tus datos solo se usan para responderte.</p>
                        <button type="submit"
                                :disabled="sending"
                                class="group inline-flex min-h-12 w-full cursor-pointer items-center justify-center gap-2 rounded-xl bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 transition hover:-translate-y-0.5 hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 sm:w-auto">
                            <i data-lucide="send" class="h-4 w-4 transition-transform group-hover:-translate-y-0.5 group-hover:translate-x-0.5" x-show="!sending"></i>
                            <i data-lucide="loader-2" class="h-4 w-4 animate-spin" x-show="sending" x-cloak></i>
                            <span x-text="sending ? 'Enviando...' : 'Enviar y abrir chat'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="relative overflow-hidden border-y border-slate-200 bg-surface">
        <div class="support-section-line pointer-events-none absolute inset-x-0 top-0 h-px"></div>
        <div class="mx-auto grid max-w-[1224px] gap-4 px-4 py-6 sm:px-6 lg:grid-cols-3 lg:py-7 xl:px-0">
            @php
                $assurances = [
                    [
                        'icon' => 'shield-check',
                        'tone' => 'emerald',
                        'title' => 'Sin registro obligatorio',
                        'copy' => 'Abrís el caso con nombre, email y contexto. Nada de cuentas ni pasos extras para pedir ayuda.',
                        'meta' => 'Datos mínimos, atención completa',
                    ],
                    [
                        'icon' => 'mail-check',
                        'tone' => 'brand',
                        'title' => 'Respuesta por email',
                        'copy' => 'Cada avance queda asociado al ticket y llega a tu casilla con el hilo ordenado.',
                        'meta' => 'Hilo único por ticket',
                    ],
                    [
                        'icon' => 'scan-search',
                        'tone' => 'sky',
                        'title' => 'Seguimiento claro',
                        'copy' => 'Consultás el estado con tu código y email, sin depender de mensajes sueltos.',
                        'meta' => 'Código único para revisar el estado',
                    ],
                ];
            @endphp

            @foreach ($assurances as $item)
                <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70 p-5 shadow-sm transition duration-300 hover:-translate-y-0.5 hover:border-brand-200 hover:bg-white hover:shadow-xl hover:shadow-slate-900/[.07]">
                    <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-600 via-sky-500 to-emerald-500 opacity-0 transition duration-300 group-hover:opacity-100"></div>
                    <div class="flex items-start gap-4">
                        <span @class([
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ring-1',
                            'bg-emerald-50 text-emerald-700 ring-emerald-200' => $item['tone'] === 'emerald',
                            'bg-brand-50 text-brand-700 ring-brand-200' => $item['tone'] === 'brand',
                            'bg-sky-50 text-sky-700 ring-sky-200' => $item['tone'] === 'sky',
                        ])>
                            <i data-lucide="{{ $item['icon'] }}" class="h-5 w-5"></i>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-sm font-semibold tracking-tight text-slate-950">{{ $item['title'] }}</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $item['copy'] }}</p>
                            <p class="mt-4 inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-500 ring-1 ring-slate-200">
                                <span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span>
                                {{ $item['meta'] }}
                            </p>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section id="pasos" class="border-y border-slate-200 bg-surface py-20 sm:py-24">
        <div class="mx-auto max-w-[1224px] px-4 sm:px-6 xl:px-0">
            <div class="grid gap-8 lg:grid-cols-[0.7fr_1.3fr] lg:items-end">
                <div>
                    <p class="text-sm font-semibold text-brand-700">Cómo funciona</p>
                    <h2 class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 [text-wrap:balance]">Un flujo simple, con trazabilidad real.</h2>
                </div>
                <p class="max-w-2xl text-base leading-7 text-slate-600 lg:justify-self-end">Cada paso reduce ida y vuelta: recibimos el contexto, lo convertimos en ticket y mantenemos las respuestas en el mismo hilo.</p>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-3">
                @php $steps = [
                    ['icon'=>'pencil-line','t'=>'Escribís el caso','d'=>'Completás el formulario con datos de contacto, categoría, descripción y adjuntos útiles.'],
                    ['icon'=>'inbox','t'=>'Lo ordenamos','d'=>'El sistema genera un código, guarda el historial y deja el ticket listo para asignación.'],
                    ['icon'=>'mail-check','t'=>'Seguís la respuesta','d'=>'Recibís novedades por email y podés consultar el estado con tu código cuando quieras.'],
                ]; @endphp
                @foreach ($steps as $index => $step)
                    <article class="group relative overflow-hidden rounded-2xl border border-slate-200 bg-surface p-6 shadow-sm transition hover:-translate-y-1 hover:border-brand-200 hover:shadow-xl hover:shadow-slate-900/[.08]">
                        <div class="absolute inset-x-0 top-0 h-1 bg-gradient-to-r from-brand-600 via-sky-500 to-emerald-500 opacity-0 transition group-hover:opacity-100"></div>
                        <div class="flex items-center justify-between">
                            <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600 ring-1 ring-brand-100"><i data-lucide="{{ $step['icon'] }}" class="h-5 w-5"></i></span>
                            <span class="font-mono text-sm text-slate-300">{{ str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>
                        </div>
                        <h3 class="mt-5 text-lg font-semibold tracking-tight text-slate-950">{{ $step['t'] }}</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600">{{ $step['d'] }}</p>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    @push('scripts')
        <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r134/three.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/vanta@0.5.24/dist/vanta.net.min.js"></script>
        <script>
            document.addEventListener('alpine:init', () => {
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                Alpine.data('supportHero', () => ({
                    mx: 0,
                    my: 0,
                    track(e) {
                        if (reduce) return;
                        this.mx = (e.clientX / window.innerWidth) - 0.5;
                        this.my = (e.clientY / window.innerHeight) - 0.5;
                    },
                    panel(factor) {
                        if (reduce) return '';
                        return `transform: translate3d(${this.mx * factor * 20}px, ${this.my * factor * 24}px, 0)`;
                    },
                }));
            });
            window.addEventListener('load', () => {
                const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                const el = document.getElementById('hero-3d');
                if (reduce || !el || !window.VANTA || !window.VANTA.NET) return;
                try {
                    window.VANTA.NET({
                        el,
                        mouseControls: true,
                        touchControls: true,
                        gyroControls: false,
                        minHeight: 200.0,
                        minWidth: 200.0,
                        scale: 1.0,
                        scaleMobile: 1.0,
                        color: 0x22d3ee,
                        backgroundColor: 0x0b1220,
                        points: 11.0,
                        maxDistance: 24.0,
                        spacing: 17.0,
                    });
                } catch (e) {
                    el.style.display = 'none';
                }
            });
        </script>
    @endpush
</x-layouts.public>
