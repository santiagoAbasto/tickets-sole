<x-layouts.public
    title="Consulta recibida"
    description="Tu consulta fue recibida por Osole Soporte. Guardá el código del ticket para seguir el estado y las respuestas."
    robots="noindex, nofollow">
    <section class="mx-auto flex max-w-lg flex-col items-center px-4 py-20 text-center">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-200">
            <i data-lucide="circle-check-big" class="h-8 w-8"></i>
        </span>
        <h1 class="mt-6 text-2xl font-semibold tracking-tight text-slate-900">¡Recibimos tu consulta!</h1>
        <p class="mt-2 text-sm leading-relaxed text-slate-500">
            Nuestro equipo ya la tiene y te vamos a responder por email a la brevedad. Guardá este número de seguimiento.
        </p>

        <div class="mt-6 inline-flex items-center gap-3 rounded-xl border border-slate-200 bg-surface px-5 py-3 shadow-sm">
            <span class="text-xs font-medium uppercase tracking-wide text-slate-400">Tu ticket</span>
            <span class="font-mono text-lg font-semibold tracking-tight text-brand-700">{{ $code }}</span>
        </div>

        <div class="mt-8 flex flex-wrap items-center justify-center gap-2">
            <x-button :href="route('public.track.form')"><i data-lucide="search-check" class="h-4 w-4"></i> Seguir mi ticket</x-button>
            <x-button :href="route('public.support.create')" variant="secondary"><i data-lucide="plus" class="h-4 w-4"></i> Otra consulta</x-button>
        </div>
    </section>
</x-layouts.public>
