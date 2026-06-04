<x-layouts.auth title="Recuperar contraseña" heading="Recuperá tu acceso" subtitle="Te enviamos un enlace seguro al email de tu cuenta.">
    @if (session('status'))
        <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf
        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" autofocus class="input" placeholder="vos@osole.com.ar" @error('email') aria-invalid="true" @enderror>
            @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <x-button type="submit" size="lg" class="w-full">Enviar enlace</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:text-brand-700">Volver al login</a>
    </p>
</x-layouts.auth>
