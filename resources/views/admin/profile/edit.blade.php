<x-layouts.admin title="Mi perfil">
    @php $canEditIdentity = $user->hasRole('Super Admin'); @endphp
    <div class="mx-auto max-w-2xl space-y-5">
        <div>
            <h1 class="text-xl font-semibold tracking-tight text-slate-900">Mi perfil</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $canEditIdentity ? 'Actualizá tus datos, foto y contraseña.' : 'Actualizá tu teléfono, foto y contraseña.' }} Tu foto se refleja en las conversaciones con clientes.</p>
        </div>

        <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <x-card class="p-5">
                <div class="space-y-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                        <x-avatar :name="$user->name" :src="$user->avatarUrl()" size="lg" />
                        <div class="min-w-0 flex-1">
                            <label class="label">Foto de perfil</label>
                            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:h-10 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
                            @error('avatar')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            @if ($user->avatar_path)
                                <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                                    <input type="checkbox" name="remove_avatar" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                    Quitar foto actual
                                </label>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @if ($canEditIdentity)
                            <div>
                                <label class="label">Nombre</label>
                                <input name="name" value="{{ old('name', $user->name) }}" class="input">
                                @error('name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="label">Email</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="input">
                                @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="label">Puesto</label>
                                <input name="job_title" value="{{ old('job_title', $user->job_title) }}" class="input">
                            </div>
                        @else
                            <div>
                                <label class="label">Nombre</label>
                                <div class="flex h-10 items-center rounded-lg bg-slate-50 px-3 text-sm text-slate-500 ring-1 ring-inset ring-slate-200">{{ $user->name }}</div>
                            </div>
                            <div>
                                <label class="label">Email</label>
                                <div class="flex h-10 items-center rounded-lg bg-slate-50 px-3 text-sm text-slate-500 ring-1 ring-inset ring-slate-200">{{ $user->email }}</div>
                            </div>
                            <div>
                                <label class="label">Puesto</label>
                                <div class="flex h-10 items-center rounded-lg bg-slate-50 px-3 text-sm text-slate-500 ring-1 ring-inset ring-slate-200">{{ $user->job_title ?: '—' }}</div>
                            </div>
                        @endif
                        <div>
                            <label class="label">Teléfono</label>
                            <input name="phone" value="{{ old('phone', $user->phone) }}" class="input">
                            @error('phone')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    @unless ($canEditIdentity)
                        <p class="-mt-2 flex items-center gap-1.5 text-xs text-slate-400"><i data-lucide="lock" class="h-3.5 w-3.5"></i> Tu nombre, email y puesto los gestiona un administrador. Podés actualizar tu teléfono y contraseña.</p>
                    @endunless

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h2 class="text-sm font-semibold text-slate-900">Cambiar contraseña</h2>
                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="label">Contraseña actual</label>
                                <input type="password" name="current_password" autocomplete="current-password" class="input">
                                @error('current_password')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="label">Nueva contraseña</label>
                                <input type="password" name="password" autocomplete="new-password" class="input">
                                @error('password')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="label">Confirmar contraseña</label>
                                <input type="password" name="password_confirmation" autocomplete="new-password" class="input">
                            </div>
                        </div>
                    </div>
                </div>
            </x-card>
            <div class="mt-4 flex justify-end">
                <x-button type="submit">Guardar perfil</x-button>
            </div>
        </form>
    </div>
</x-layouts.admin>
