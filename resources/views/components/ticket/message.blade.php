@props(['item', 'customerName' => 'Cliente', 'grouped' => false])
@php
    use Illuminate\Support\Carbon;
    $type = $item['author_type'] ?? 'agent';
    $when = !empty($item['created_at']) ? Carbon::parse($item['created_at'])->translatedFormat('d M Y · H:i') : '';
@endphp

@if ($type === 'system')
    <div @if(isset($item['id'])) data-message-id="{{ $item['id'] }}" @endif class="my-2 text-center text-xs text-slate-400">{{ $item['body'] }}</div>
@elseif ($type === 'note')
    @php $preview = str($item['body'])->squish()->limit(70); @endphp
    <div @if(isset($item['id'])) data-note-id="{{ $item['id'] }}" @endif
         x-data="{ open: false }"
         class="overflow-hidden rounded-xl bg-amber-50 ring-1 ring-inset ring-amber-200/70">
        <button type="button" @click="open = ! open" :aria-expanded="open"
                class="flex w-full items-center gap-1.5 px-4 py-2.5 text-left text-xs font-semibold text-amber-700 transition-colors hover:bg-amber-100/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-brand-500">
            <i data-lucide="lock" class="h-3.5 w-3.5 shrink-0"></i>
            <span class="shrink-0">Nota interna · {{ data_get($item, 'author.name') }}</span>
            @if (data_get($item, 'channel') === 'whatsapp')
                <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-100 px-1.5 py-0.5 text-[10px] font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200"><x-icon.whatsapp class="h-2.5 w-2.5" /> Enviado por WhatsApp</span>
            @endif
            <span class="shrink-0 font-normal text-amber-600/70">{{ $when }}</span>
            <span x-show="!open" class="min-w-0 flex-1 truncate font-normal text-amber-800/70">{{ $preview }}</span>
            <i data-lucide="chevron-down" class="ml-auto h-4 w-4 shrink-0 text-amber-500 transition-transform duration-200" :class="open && 'rotate-180'"></i>
        </button>
        <div x-show="open" x-cloak>
            <p class="whitespace-pre-wrap px-4 pb-3 text-sm text-amber-900">{{ $item['body'] }}</p>
        </div>
    </div>
@else
    @php
        $isCustomer = $type === 'customer';
        $name = data_get($item, 'author.name') ?: ($isCustomer ? $customerName : 'Agente');
        $avatar = data_get($item, 'author.avatar_url')
            ?: (data_get($item, 'author.avatar_path') ? asset('storage/'.data_get($item, 'author.avatar_path')) : null);
        $tail = $isCustomer ? 'rounded-tl-sm' : 'rounded-tr-sm';
        $bubble = $isCustomer ? 'bg-white text-slate-700 ring-1 ring-inset ring-slate-200' : 'bg-brand-600 text-white';
    @endphp
    <div data-message-id="{{ $item['id'] }}" class="flex gap-3 {{ $isCustomer ? '' : 'flex-row-reverse' }} {{ $grouped ? '-mt-2.5' : '' }}">
        @if ($grouped)
            <span class="h-8 w-8 shrink-0" aria-hidden="true"></span>
        @else
            <x-avatar :name="$name" :src="$avatar" size="sm" class="mt-0.5 shrink-0" />
        @endif
        <div class="min-w-0 max-w-[78%]">
            @unless ($grouped)
                <div class="mb-1 flex items-center gap-2 text-xs {{ $isCustomer ? '' : 'justify-end' }}">
                    <span class="font-medium text-slate-700">{{ $name }}</span>
                    <span class="text-slate-400">{{ $when }}</span>
                </div>
            @endunless
            <div class="rounded-2xl px-4 py-2.5 text-sm shadow-sm {{ $grouped ? '' : $tail }} {{ $bubble }}">
                <p class="whitespace-pre-wrap break-words">{{ $item['body'] }}</p>
            </div>
            @if (!empty($item['attachments']))
                <div class="mt-2 flex flex-wrap gap-2 {{ $isCustomer ? '' : 'justify-end' }}">
                    @foreach ($item['attachments'] as $a)
                        <a href="{{ $a['url'] }}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-600 transition hover:border-brand-300 hover:text-brand-700">
                            <i data-lucide="{{ $a['is_image'] ? 'image' : 'file-text' }}" class="h-3.5 w-3.5"></i>
                            <span class="max-w-[12rem] truncate">{{ $a['name'] }}</span>
                            <span class="text-slate-400">{{ $a['size'] }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endif
