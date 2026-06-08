@props([
    'name' => 'attachments[]',
    'accept' => 'image/*,.pdf,.doc,.docx,.xls,.xlsx,.zip',
    'compact' => false,
])
<div x-data="uploader" @chat-reset.window="reset($refs.input)">
    <label @class([
        'group flex cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-slate-300 px-4 text-center text-sm text-slate-500 transition hover:border-brand-400 hover:bg-brand-50/40',
        'py-3' => $compact,
        'py-5' => ! $compact,
    ])>
        <i data-lucide="image-up" @class([
            'text-slate-400 transition-colors group-hover:text-brand-500',
            'h-4 w-4' => $compact,
            'h-5 w-5' => ! $compact,
        ])></i>
        <span>Arrastrá o <span class="font-medium text-brand-600">elegí archivos</span></span>
        <span class="text-xs text-slate-400">Imágenes y documentos · máx. 10 MB c/u</span>
        <input x-ref="input" type="file" name="{{ $name }}" multiple accept="{{ $accept }}" class="hidden" @change="add($event)">
    </label>

    <div x-show="items.length" x-cloak class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
        <template x-for="(it, i) in items" :key="i">
            <div class="group/item relative overflow-hidden rounded-xl border border-slate-200 bg-slate-50 shadow-sm">
                <template x-if="it.isImage">
                    <img :src="it.url" :alt="it.name" class="{{ $compact ? 'h-16' : 'h-24' }} w-full object-cover">
                </template>
                <template x-if="!it.isImage">
                    <div class="flex {{ $compact ? 'h-16' : 'h-24' }} w-full flex-col items-center justify-center gap-1 px-2 text-center">
                        <i data-lucide="file-text" class="h-6 w-6 text-slate-400"></i>
                    </div>
                </template>
                <div class="flex items-center justify-between gap-1 border-t border-slate-200/70 bg-white px-2 py-1.5">
                    <span class="truncate text-[11px] text-slate-600" x-text="it.name"></span>
                    <span class="shrink-0 text-[10px] text-slate-400" x-text="it.size"></span>
                </div>
                <button type="button" @click="remove(i, $refs.input)"
                        class="absolute right-1.5 top-1.5 flex h-6 w-6 items-center justify-center rounded-full bg-slate-900/55 text-white opacity-0 backdrop-blur transition hover:bg-rose-600 group-hover/item:opacity-100"
                        aria-label="Quitar archivo">
                    <i data-lucide="x" class="h-3.5 w-3.5"></i>
                </button>
            </div>
        </template>
    </div>
</div>
