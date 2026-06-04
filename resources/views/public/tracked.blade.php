@php
    use Illuminate\Support\Carbon;
    $resolved = ! empty($ticket['resolved_at']);
    $lastMessageId = collect($ticket['messages'])->max('id') ?? 0;
@endphp
<x-layouts.public :title="$ticket['code']" immersive>
    <section class="mx-auto max-w-2xl px-4 py-10">
        <a href="{{ route('public.track.form') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-slate-300 transition-colors hover:text-white"><i data-lucide="arrow-left" class="h-4 w-4"></i> Buscar otro ticket</a>

        {{-- Header --}}
        <div class="mt-4 rounded-2xl border border-white/10 bg-surface p-5 shadow-2xl shadow-slate-950/30">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <span class="font-mono text-sm text-slate-400">{{ $ticket['code'] }}</span>
                    <h1 class="mt-1 text-xl font-semibold tracking-tight text-slate-900">{{ $ticket['subject'] }}</h1>
                </div>
                <x-status-badge :status="$ticket['status']" />
            </div>
            <div class="mt-3 flex flex-wrap items-center gap-x-5 gap-y-1 text-xs text-slate-500">
                <span class="inline-flex items-center gap-1.5"><x-priority-badge :priority="$ticket['priority']" /></span>
                @if (data_get($ticket, 'agent'))<span class="inline-flex items-center gap-1.5"><i data-lucide="user" class="h-3.5 w-3.5"></i> Atiende: {{ data_get($ticket, 'agent.name') }}</span>@endif
                <span class="inline-flex items-center gap-1.5"><i data-lucide="calendar" class="h-3.5 w-3.5"></i> Creado el {{ Carbon::parse($ticket['created_at'])->translatedFormat('d M Y') }}</span>
            </div>
        </div>

        @if ($resolved)
            <div class="mt-4 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-200">
                <i data-lucide="circle-check-big" class="h-5 w-5 text-emerald-600"></i>
                Resuelto el {{ Carbon::parse($ticket['resolved_at'])->translatedFormat('d M Y · H:i') }}. Si seguís con dudas, respondé y lo reabrimos.
            </div>
        @endif

        {{-- Conversation (chat) — clean white surface so it stays prominent over the backdrop --}}
        <div class="mt-4 space-y-4 rounded-2xl border border-white/10 bg-surface p-5 shadow-2xl shadow-slate-950/30"
             data-realtime-chat
             data-endpoint="{{ route('public.track.messages') }}"
             data-last-message-id="{{ $lastMessageId }}"
             data-customer-name="Vos">
            @foreach ($ticket['messages'] as $m)
                <x-ticket.message :item="$m" customer-name="Vos" />
            @endforeach
        </div>

        {{-- Reply --}}
        <form method="POST" action="{{ route('public.track.reply') }}" enctype="multipart/form-data" class="mt-4 rounded-2xl border border-white/10 bg-surface p-4 shadow-2xl shadow-slate-950/30" data-realtime-chat-form>
            @csrf
            <textarea name="body" rows="3" class="textarea" placeholder="Escribí tu respuesta…">{{ old('body') }}</textarea>
            @error('body')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            <div class="mt-3"><x-attachment-input /></div>
            <div class="mt-3 flex justify-end">
                <x-button type="submit"><i data-lucide="send" class="h-4 w-4"></i> Enviar</x-button>
            </div>
        </form>
    </section>
</x-layouts.public>
