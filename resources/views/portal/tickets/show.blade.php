@php
    use Illuminate\Support\Carbon;
    $resolved = !empty($ticket['resolved_at']);
    $lastMessageId = collect($ticket['messages'])->max('id') ?? 0;
@endphp
<x-layouts.portal :title="$ticket['code']" :show-new="false">
    <a href="{{ route('portal.tickets.index') }}" class="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700"><i data-lucide="arrow-left" class="h-4 w-4"></i> Mis consultas</a>

    <div class="mb-5 mt-3">
        <span class="font-mono text-sm text-slate-400">{{ $ticket['code'] }}</span>
        <h1 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">{{ $ticket['subject'] }}</h1>
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <x-status-badge :status="$ticket['status']" />
            <x-priority-badge :priority="$ticket['priority']" />
            @if (data_get($ticket,'agent'))<span class="text-xs text-slate-500">Atiende: {{ data_get($ticket,'agent.name') }}</span>@endif
        </div>
    </div>

    @if ($resolved)
        <div class="mb-5 flex items-center gap-2 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800 ring-1 ring-inset ring-emerald-200">
            <i data-lucide="circle-check-big" class="h-5 w-5 text-emerald-600"></i>
            Esta consulta fue resuelta el {{ Carbon::parse($ticket['resolved_at'])->translatedFormat('d M Y · H:i') }}. Si seguís con dudas, respondé y la reabrimos.
        </div>
    @endif

    <div class="space-y-4 rounded-2xl border border-slate-200 bg-surface p-5 shadow-sm"
         data-realtime-chat
         data-endpoint="{{ route('portal.tickets.messages.index', $ticket['id']) }}"
         data-last-message-id="{{ $lastMessageId }}"
         data-customer-name="Vos">
        @foreach ($ticket['messages'] as $m)
            <x-ticket.message :item="$m" customer-name="Vos" />
        @endforeach
    </div>

    @if ($can['reply'])
        <form method="POST" action="{{ route('portal.tickets.messages.store', $ticket['id']) }}" enctype="multipart/form-data" class="mt-4 rounded-2xl border border-slate-200 bg-surface p-4 shadow-sm" data-realtime-chat-form>
            @csrf
            <textarea name="body" rows="3" class="textarea" placeholder="Escribí tu respuesta…">{{ old('body') }}</textarea>
            @error('body')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            <div class="mt-3"><x-attachment-input /></div>
            <div class="mt-3 flex justify-end">
                <x-button type="submit"><i data-lucide="send" class="h-4 w-4"></i> Enviar</x-button>
            </div>
        </form>
    @endif
</x-layouts.portal>
