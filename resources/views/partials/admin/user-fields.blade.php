@php $isEdit = ! empty($managedUser); @endphp

<div class="space-y-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
        <x-avatar :name="old('name', $managedUser['name'] ?? '')" :src="$managedUser['avatar_url'] ?? null" size="lg" />
        <div class="min-w-0 flex-1">
            <label class="label">Foto de perfil</label>
            <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:h-10 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:text-sm file:font-medium file:text-slate-700 hover:file:bg-slate-200">
            <p class="mt-1 text-xs text-slate-500">JPG, PNG o WebP. Se verá en el chat cuando responda tickets.</p>
            @error('avatar')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
            @if ($isEdit && ! empty($managedUser['avatar_url']))
                <label class="mt-2 flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remove_avatar" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    Quitar foto actual
                </label>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div>
            <label class="label">Nombre <span class="text-rose-500">*</span></label>
            <input name="name" value="{{ old('name', $managedUser['name'] ?? '') }}" class="input">
            @error('name')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Email <span class="text-rose-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $managedUser['email'] ?? '') }}" class="input">
            @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">{{ $isEdit ? 'Nueva contraseña (opcional)' : 'Contraseña' }} @unless($isEdit)<span class="text-rose-500">*</span>@endunless</label>
            <input type="password" name="password" autocomplete="new-password" class="input">
            @error('password')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Confirmar contraseña</label>
            <input type="password" name="password_confirmation" autocomplete="new-password" class="input">
        </div>
        <div>
            <label class="label">Puesto</label>
            <input name="job_title" value="{{ old('job_title', $managedUser['job_title'] ?? '') }}" class="input" placeholder="Ej. Coordinador de soporte">
        </div>
        <div>
            <label class="label">Teléfono</label>
            <input name="phone" value="{{ old('phone', $managedUser['phone'] ?? '') }}" class="input">
        </div>
        <div>
            <label class="label">Departamento</label>
            <select name="department_id" class="select">
                <option value="">Sin departamento</option>
                @foreach ($options['departments'] as $d)
                    <option value="{{ $d['id'] }}" @selected(old('department_id', $managedUser['department_id'] ?? null) == $d['id'])>{{ $d['name'] }}</option>
                @endforeach
            </select>
            @error('department_id')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="label">Rol <span class="text-rose-500">*</span></label>
            <select name="role" class="select">
                @foreach ($options['roles'] as $r)
                    <option value="{{ $r }}" @selected(old('role', $managedUser['role'] ?? 'Agente') === $r)>{{ $r }}</option>
                @endforeach
            </select>
            @error('role')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-2 text-sm text-slate-600 sm:col-span-2">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $managedUser['is_active'] ?? true)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            Activo (puede iniciar sesión, recibir asignaciones y restablecer acceso)
        </label>
    </div>
</div>
