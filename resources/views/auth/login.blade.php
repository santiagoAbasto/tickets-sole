<x-layouts.auth title="Iniciar sesión" heading="Iniciá sesión" subtitle="Ingresá a tu mesa de ayuda.">
    @if (session('status'))
        <div class="mb-5 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-200">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" autofocus
                   class="input" placeholder="vos@osole.com.ar" @error('email') aria-invalid="true" @enderror>
            @error('email')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="password" class="label">Contraseña</label>
            <input id="password" type="password" name="password" autocomplete="current-password"
                   class="input" placeholder="••••••••">
            @error('password')<p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="flex items-center justify-between gap-3">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                Mantener sesión iniciada
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-brand-600 hover:text-brand-700">Olvidé mi contraseña</a>
        </div>

        <x-button type="submit" size="lg" class="w-full">Ingresar</x-button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-500">
        ¿No tenés cuenta? <a href="{{ route('public.support.create') }}" class="font-medium text-brand-600 hover:text-brand-700">Enviá tu consulta sin registrarte</a>
    </p>
</x-layouts.auth>
