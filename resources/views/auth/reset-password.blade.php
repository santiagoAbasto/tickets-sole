<x-layouts.auth title="Nueva contraseña" heading="Creá una nueva contraseña" subtitle="Este enlace es temporal. Usá una clave segura para volver a entrar.">
    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" autocomplete="email" class="input">
            @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label">Nueva contraseña</label>
            <input id="password" type="password" name="password" autocomplete="new-password" class="input">
            @error('password')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirmar contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="input">
        </div>

        <x-button type="submit" size="lg" class="w-full">Guardar contraseña</x-button>
    </form>
</x-layouts.auth>
