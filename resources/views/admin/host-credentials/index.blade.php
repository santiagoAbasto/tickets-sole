<x-layouts.admin title="Hosts / accesos">
    <div class="mx-auto max-w-6xl space-y-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight text-slate-900">Hosts / accesos</h1>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $canSeeAll ? 'Vista completa de accesos registrados por el equipo.' : 'Tus accesos registrados y los que cargues desde tickets.' }}
                </p>
            </div>
            <form method="GET" action="{{ route('admin.host-credentials.index') }}" class="flex gap-2">
                <input name="search" value="{{ $search }}" class="input min-w-0 sm:w-72" placeholder="Buscar host, usuario, panel...">
                <x-button type="submit" variant="secondary" aria-label="Buscar">
                    <i data-lucide="search" class="h-4 w-4"></i>
                </x-button>
            </form>
        </div>

        <section class="rounded-2xl border border-slate-200 bg-surface p-5 shadow-sm" x-data="{ showPassword: false }">
            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-sm font-semibold text-slate-900">Registrar host sin ticket</h2>
                    <p class="mt-1 text-xs text-slate-500">Guardá paneles, usuarios y notas internas en un listado único.</p>
                </div>
                <span class="hidden rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-700 sm:inline-flex">
                    Evita duplicados
                </span>
            </div>

            <form method="POST" action="{{ route('admin.host-credentials.store') }}" class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                @csrf
                <div>
                    <label class="label">Nombre / cliente</label>
                    <input name="name" value="{{ old('name') }}" class="input" placeholder="Cliente o proyecto">
                    @error('name')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Sitio web</label>
                    <input name="website_url" value="{{ old('website_url') }}" class="input" inputmode="url" placeholder="https://cliente.com">
                    @error('website_url')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">URL del panel / servidor</label>
                    <input name="server_url" value="{{ old('server_url') }}" class="input" inputmode="url" placeholder="https://servidor:2083">
                    @error('server_url')
                        <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="label">Hosting</label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                            <input type="radio" name="hosting_type" value="osole" class="h-4 w-4 border-slate-300 text-brand-600" @checked(old('hosting_type') === 'osole')>
                            Osole
                        </label>
                        <label class="flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50">
                            <input type="radio" name="hosting_type" value="external" class="h-4 w-4 border-slate-300 text-brand-600" @checked(old('hosting_type') === 'external')>
                            Externo
                        </label>
                    </div>
                </div>
                <div>
                    <label class="label">Plataforma / panel</label>
                    <select name="hosting_provider" class="select">
                        <option value="">Seleccionar...</option>
                        @foreach (['cPanel', 'Plesk', 'AWS', 'Digital Ocean', 'Hostinger', 'DonWeb', 'Otro'] as $provider)
                            <option value="{{ $provider }}" @selected(old('hosting_provider') === $provider)>{{ $provider }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="label">Usuario</label>
                    <input name="cpanel_user" value="{{ old('cpanel_user') }}" class="input" autocomplete="off" placeholder="usuario">
                </div>
                <div>
                    <label class="label">Contraseña</label>
                    <div class="relative">
                        <input :type="showPassword ? 'text' : 'password'" name="cpanel_password" class="input pr-10" autocomplete="new-password" placeholder="••••••••">
                        <button type="button" @click="showPassword = ! showPassword" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600" :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                            <i data-lucide="eye" x-show="!showPassword" class="h-4 w-4"></i>
                            <i data-lucide="eye-off" x-show="showPassword" x-cloak class="h-4 w-4"></i>
                        </button>
                    </div>
                </div>
                <div class="lg:col-span-2">
                    <label class="label">Notas internas</label>
                    <textarea name="notes" rows="2" class="textarea" placeholder="FTP, DNS, base de datos, vencimientos...">{{ old('notes') }}</textarea>
                </div>
                <div class="flex items-end justify-end lg:col-span-3">
                    <x-button type="submit">
                        <i data-lucide="save" class="h-4 w-4"></i>
                        Guardar host
                    </x-button>
                </div>
            </form>
        </section>

        <div class="grid gap-4 lg:grid-cols-2">
            @if ($hosts->isEmpty())
                <div class="rounded-2xl border border-dashed border-slate-300 bg-surface p-8 text-center lg:col-span-2">
                    <i data-lucide="server-cog" class="mx-auto h-10 w-10 text-slate-300"></i>
                    <h2 class="mt-3 text-sm font-semibold text-slate-900">Sin hosts registrados</h2>
                    <p class="mt-1 text-sm text-slate-500">Agregá uno manualmente o cargá credenciales dentro de un ticket.</p>
                </div>
            @endif

            @foreach ($hosts as $host)
                <article class="rounded-2xl border border-slate-200 bg-surface p-5 shadow-sm" x-data="{ editing: false, revealed: false, editPassword: false, password: null, loadingPassword: false, copied: false, async revealPassword() { if (this.password !== null) { this.revealed = ! this.revealed; return; } this.loadingPassword = true; const response = await fetch(@js(route('admin.host-credentials.reveal-password', $host)), { method: 'POST', headers: { 'X-CSRF-TOKEN': @js(csrf_token()), 'Accept': 'application/json' } }); this.loadingPassword = false; if (!response.ok) return; const data = await response.json(); this.password = data.password || ''; this.revealed = true; }, async copyPassword() { if (this.password === null) { await this.revealPassword(); } this.copy(this.password); }, copy(text) { if (!text) return; navigator.clipboard?.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 1200); } }">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="truncate text-base font-semibold tracking-tight text-slate-900">
                                {{ $host->name ?: ($host->website_url ?: $host->server_url ?: 'Host sin nombre') }}
                            </h2>
                            <div class="mt-1 flex flex-wrap gap-2 text-xs text-slate-500">
                                @if ($host->hosting_provider)
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5">{{ $host->hosting_provider }}</span>
                                @endif
                                @if ($host->hosting_type)
                                    <span class="rounded-full bg-brand-50 px-2 py-0.5 text-brand-700">
                                        {{ $host->hosting_type === 'osole' ? 'Osole' : 'Externo' }}
                                    </span>
                                @endif
                                @if ($host->sourceTicket)
                                    <a href="{{ route('admin.tickets.show', $host->sourceTicket) }}" class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700 hover:underline">
                                        Ticket {{ $host->sourceTicket->code }}
                                    </a>
                                @endif
                            </div>
                        </div>
                        <button type="button" @click="editing = ! editing" class="inline-flex min-h-9 shrink-0 items-center gap-1.5 rounded-lg px-2 text-xs font-semibold text-brand-600 hover:bg-brand-50">
                            <i data-lucide="pencil" class="h-3.5 w-3.5"></i>
                            <span x-text="editing ? 'Cerrar' : 'Editar'"></span>
                        </button>
                    </div>

                    <dl x-show="!editing" class="mt-4 space-y-2.5 text-sm">
                        @if ($host->website_url)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">Sitio</dt>
                                <dd class="min-w-0 truncate text-right">
                                    <a href="{{ $host->website_url }}" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline">{{ $host->website_url }}</a>
                                </dd>
                            </div>
                        @endif
                        @if ($host->server_url)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">Servidor</dt>
                                <dd class="min-w-0 truncate text-right">
                                    <a href="{{ $host->server_url }}" target="_blank" rel="noopener" class="font-medium text-brand-600 hover:underline">{{ $host->server_url }}</a>
                                </dd>
                            </div>
                        @endif
                        @if ($host->cpanel_user)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">Usuario</dt>
                                <dd class="flex min-w-0 items-center gap-1.5">
                                    <span class="truncate font-mono text-slate-700">{{ $host->cpanel_user }}</span>
                                    <button type="button" @click='copy(@json($host->cpanel_user))' class="rounded p-1 text-slate-400 hover:text-slate-600" aria-label="Copiar usuario">
                                        <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                                    </button>
                                </dd>
                            </div>
                        @endif
                        @if ($host->cpanel_password)
                            <div class="flex justify-between gap-3">
                                <dt class="text-slate-400">Contraseña</dt>
                                <dd class="flex min-w-0 items-center gap-1.5">
                                    <span x-show="!revealed" class="font-mono text-slate-700">••••••••</span>
                                    <span x-show="revealed" x-cloak class="truncate font-mono text-slate-700" x-text="password || 'Sin contraseña'"></span>
                                    <button type="button" @click="revealPassword()" class="rounded p-1 text-slate-400 hover:text-slate-600 disabled:opacity-50" :aria-label="revealed ? 'Ocultar contraseña' : 'Mostrar contraseña'" :disabled="loadingPassword">
                                        <i data-lucide="eye" x-show="!revealed" class="h-3.5 w-3.5"></i>
                                        <i data-lucide="eye-off" x-show="revealed" x-cloak class="h-3.5 w-3.5"></i>
                                    </button>
                                    <button type="button" @click="copyPassword()" class="rounded p-1 text-slate-400 hover:text-slate-600 disabled:opacity-50" aria-label="Copiar contraseña" :disabled="loadingPassword">
                                        <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                                    </button>
                                </dd>
                            </div>
                        @endif
                        @if ($host->notes)
                            <div>
                                <dt class="mb-1 text-slate-400">Notas</dt>
                                <dd class="whitespace-pre-wrap text-slate-600">{{ $host->notes }}</dd>
                            </div>
                        @endif
                        <div class="flex justify-between gap-3 pt-1 text-xs text-slate-400">
                            <dt>Registró</dt>
                            <dd>{{ $host->creator?->name ?? 'Sistema' }}</dd>
                        </div>
                    </dl>
                    <p x-show="copied" x-cloak class="mt-2 text-xs font-medium text-emerald-600">Copiado</p>

                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.host-credentials.update', $host) }}" class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @csrf
                        @method('PUT')
                        <div>
                            <label class="label">Nombre / cliente</label>
                            <input name="name" value="{{ $host->name }}" class="input">
                        </div>
                        <div>
                            <label class="label">Sitio web</label>
                            <input name="website_url" value="{{ $host->website_url }}" class="input" inputmode="url">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">URL del panel / servidor</label>
                            <input name="server_url" value="{{ $host->server_url }}" class="input" inputmode="url">
                        </div>
                        <div>
                            <label class="label">Hosting</label>
                            <div class="grid grid-cols-2 gap-2">
                                <label class="flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <input type="radio" name="hosting_type" value="osole" class="h-4 w-4 border-slate-300 text-brand-600" @checked($host->hosting_type === 'osole')>
                                    Osole
                                </label>
                                <label class="flex min-h-10 cursor-pointer items-center justify-center gap-2 rounded-lg border border-slate-200 px-3 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                    <input type="radio" name="hosting_type" value="external" class="h-4 w-4 border-slate-300 text-brand-600" @checked($host->hosting_type === 'external')>
                                    Externo
                                </label>
                            </div>
                        </div>
                        <div>
                            <label class="label">Plataforma</label>
                            <input name="hosting_provider" value="{{ $host->hosting_provider }}" class="input">
                        </div>
                        <div>
                            <label class="label">Usuario</label>
                            <input name="cpanel_user" value="{{ $host->cpanel_user }}" class="input" autocomplete="off">
                        </div>
                        <div>
                            <label class="label">Contraseña</label>
                            <div class="relative">
                                <input :type="editPassword ? 'text' : 'password'" name="cpanel_password" class="input pr-10" autocomplete="new-password" placeholder="Dejar vacío para mantener la actual">
                                <button type="button" @click="editPassword = ! editPassword" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600" :aria-label="editPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'">
                                    <i data-lucide="eye" x-show="!editPassword" class="h-4 w-4"></i>
                                    <i data-lucide="eye-off" x-show="editPassword" x-cloak class="h-4 w-4"></i>
                                </button>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="label">Notas</label>
                            <textarea name="notes" rows="2" class="textarea">{{ $host->notes }}</textarea>
                        </div>
                        <div class="flex flex-col-reverse gap-2 sm:col-span-2 sm:flex-row sm:items-center sm:justify-between">
                            <button type="submit" form="delete-host-{{ $host->id }}" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg px-3 text-sm font-semibold text-rose-600 hover:bg-rose-50" onclick="return confirm('¿Eliminar este host?')">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                Eliminar
                            </button>
                            <button type="submit" class="inline-flex min-h-9 items-center justify-center gap-1.5 rounded-lg bg-brand-600 px-3 text-sm font-semibold text-white hover:bg-brand-700">
                                <i data-lucide="check" class="h-4 w-4"></i>
                                Guardar cambios
                            </button>
                        </div>
                    </form>
                    <form id="delete-host-{{ $host->id }}" method="POST" action="{{ route('admin.host-credentials.destroy', $host) }}" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </article>
            @endforeach
        </div>

        {{ $hosts->links() }}
    </div>
</x-layouts.admin>
