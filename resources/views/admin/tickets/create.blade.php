<x-layouts.admin title="Nuevo ticket">
    <div class="mx-auto max-w-4xl space-y-5">
        <div>
            <a href="{{ route('admin.tickets.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i> Volver a tickets</a>
            <h1 class="mt-2 text-xl font-semibold tracking-tight text-slate-900">Nuevo ticket</h1>
        </div>

        <form method="POST" action="{{ route('admin.tickets.store') }}" enctype="multipart/form-data"
              class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            @csrf
            <div class="space-y-5 lg:col-span-2">
                {{-- Cliente (find-or-create por email) --}}
                <x-card class="p-5">
                    <h3 class="mb-4 flex items-center gap-2 text-sm font-semibold text-slate-900"><i data-lucide="user" class="h-4 w-4 text-slate-400"></i> Cliente</h3>
                    <p class="-mt-2 mb-4 text-xs text-slate-500">Si el email no existe, se crea un cliente nuevo automáticamente.</p>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="label">Nombre <span class="text-rose-500">*</span></label>
                            <input name="customer_name" value="{{ old('customer_name') }}" class="input" placeholder="Nombre del cliente" list="customers-list">
                            @error('customer_name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label">Email <span class="text-rose-500">*</span></label>
                            <input type="email" name="customer_email" value="{{ old('customer_email') }}" class="input" placeholder="cliente@email.com">
                            @error('customer_email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="label">Teléfono <span class="text-slate-400">(opcional)</span></label>
                            <input name="customer_phone" value="{{ old('customer_phone') }}" class="input" placeholder="+54 …">
                        </div>
                    </div>
                    {{-- Autocompletar con clientes existentes --}}
                    <datalist id="customers-list">
                        @foreach ($options['customers'] as $c)<option value="{{ $c['name'] }}">{{ $c['email'] }}</option>@endforeach
                    </datalist>
                </x-card>

                <x-card class="space-y-5 p-5">
                    <div>
                        <label class="label">Asunto <span class="text-rose-500">*</span></label>
                        <input name="subject" value="{{ old('subject') }}" class="input" placeholder="Resumí el problema">
                        @error('subject')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Descripción <span class="text-rose-500">*</span></label>
                        <textarea name="description" rows="7" class="textarea" placeholder="Detallá el caso del cliente…">{{ old('description') }}</textarea>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Esta descripción queda como resumen interno del ticket. No se publica como mensaje del cliente en el chat.</p>
                        @error('description')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Adjuntos</label>
                        <x-attachment-input />
                        <p class="mt-2 text-xs leading-5 text-slate-500">Los adjuntos iniciales quedan asociados al ticket. Si querés enviarlos al cliente, usá una respuesta desde el chat.</p>
                        @error('attachments.0')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </x-card>

                {{-- Acceso / credenciales (interno, opcional) --}}
                @php $credOpen = collect(['cpanel_user','cpanel_password','server_url','hosting_type','hosting_provider','credentials_notes'])->contains(fn ($k) => filled(old($k))); @endphp
                <div class="rounded-2xl border border-slate-200 bg-surface p-5 shadow-sm" x-data="{ open: @js($credOpen), hosting: @js(old('hosting_type', '')) }">
                    <button type="button" @click="open = ! open" class="flex w-full items-center justify-between gap-3 text-left">
                        <span class="flex items-center gap-2 text-sm font-semibold text-slate-900">
                            <i data-lucide="key-round" class="h-4 w-4 text-slate-400"></i> Acceso / credenciales
                            <span class="rounded-full bg-amber-100 px-1.5 py-0.5 text-[10px] font-medium text-amber-700 ring-1 ring-inset ring-amber-200">Interno</span>
                        </span>
                        <span class="flex shrink-0 items-center gap-1 text-xs font-medium text-brand-600">
                            <span x-text="open ? 'Ocultar' : 'Agregar'"></span>
                            <i data-lucide="chevron-down" class="h-4 w-4 transition-transform duration-200" :class="open && 'rotate-180'"></i>
                        </span>
                    </button>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Datos de acceso al hosting/servidor para uso del equipo. La contraseña se guarda encriptada y nunca se muestra al cliente.</p>

                    <div x-show="open" x-cloak class="mt-4 space-y-4">
                        <div>
                            <label class="label">Hosting</label>
                            <div class="flex gap-2">
                                <label :class="hosting==='osole' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'" class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors">
                                    <input type="radio" name="hosting_type" value="osole" x-model="hosting" class="sr-only"> Osole
                                </label>
                                <label :class="hosting==='external' ? 'border-brand-300 bg-brand-50 text-brand-700' : 'border-slate-200 text-slate-600 hover:bg-slate-50'" class="flex flex-1 cursor-pointer items-center justify-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium transition-colors">
                                    <input type="radio" name="hosting_type" value="external" x-model="hosting" class="sr-only"> Externo
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="label">Plataforma / Panel</label>
                            <select name="hosting_provider" class="select">
                                <option value="">Seleccionar…</option>
                                @foreach (['cPanel', 'Plesk', 'AWS', 'Digital Ocean', 'Otro'] as $p)
                                    <option value="{{ $p }}" @selected(old('hosting_provider') === $p)>{{ $p }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="label">URL del panel / servidor</label>
                            <input name="server_url" value="{{ old('server_url') }}" class="input" inputmode="url" placeholder="https://servidor:2083">
                            @error('server_url')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="label">Usuario de servidor</label>
                                <input name="cpanel_user" value="{{ old('cpanel_user') }}" class="input" autocomplete="off" placeholder="usuario">
                            </div>
                            <div x-data="{ show: false }">
                                <label class="label">Contraseña de servidor</label>
                                <div class="relative">
                                    <input :type="show ? 'text' : 'password'" name="cpanel_password" value="{{ old('cpanel_password') }}" class="input pr-10" autocomplete="new-password" placeholder="••••••••">
                                    <button type="button" @click="show = ! show" :aria-label="show ? 'Ocultar contraseña' : 'Mostrar contraseña'" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 transition-colors hover:text-slate-600">
                                        <i x-show="!show" data-lucide="eye" class="h-4 w-4"></i>
                                        <i x-show="show" x-cloak data-lucide="eye-off" class="h-4 w-4"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="label">Notas internas</label>
                            <textarea name="credentials_notes" rows="2" class="textarea" placeholder="FTP, base de datos, detalles de acceso…">{{ old('credentials_notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-5">
                <x-card class="space-y-4 p-5">
                    <div>
                        <label class="label">Categoría <span class="text-rose-500">*</span></label>
                        <select name="category_id" class="select"><option value="">Seleccionar…</option>
                            @foreach ($options['categories'] as $c)<option value="{{ $c['id'] }}" @selected(old('category_id') == $c['id'])>{{ $c['name'] }}</option>@endforeach
                        </select>
                        @error('category_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="label">Prioridad <span class="text-rose-500">*</span></label>
                        <select name="priority_id" class="select">
                            @foreach ($options['priorities'] as $p)<option value="{{ $p['id'] }}" @selected(old('priority_id', $options['priorities']->firstWhere('slug','media')['id'] ?? null) == $p['id'])>{{ $p['name'] }}</option>@endforeach
                        </select>
                        @error('priority_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    @can('tickets.assign')
                        <div>
                            <label class="label">Agente asignado</label>
                            <select name="assigned_to" class="select"><option value="">Predeterminado</option>
                                @foreach ($options['agents'] as $a)<option value="{{ $a['id'] }}" @selected(old('assigned_to') == $a['id'])>{{ $a['name'] }}</option>@endforeach
                            </select>
                            <p class="mt-1.5 text-xs leading-5 text-slate-500">Si lo dejás en "Predeterminado", cae en el agente que figura en <span class="font-medium text-slate-600">Asignación de tickets</span>.</p>
                        </div>
                    @else
                        <div class="flex items-start gap-2 rounded-xl bg-slate-50 p-3 text-xs leading-5 text-slate-500 ring-1 ring-inset ring-slate-200">
                            <i data-lucide="info" class="mt-0.5 h-4 w-4 shrink-0 text-slate-400"></i>
                            <span>El ticket se asigna automáticamente al <span class="font-medium text-slate-600">agente predeterminado</span>. Si hace falta, ese agente puede pedir delegarlo.</span>
                        </div>
                    @endcan
                </x-card>
                <x-button type="submit" class="w-full">Crear ticket</x-button>
            </div>
        </form>
    </div>
</x-layouts.admin>
