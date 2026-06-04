@props(['ticketId', 'whatsapp' => []])
@php
    $wa = $whatsapp ?? [];
    $templates = $wa['templates'] ?? [];
@endphp

<div
    x-data="whatsappPanel({
        hasPhone: @js($wa['has_phone'] ?? false),
        phone: @js($wa['phone'] ?? ''),
        waBase: @js($wa['wa_base'] ?? null),
        templates: @js($templates),
        numberUrl: @js(route('admin.tickets.whatsapp.number', $ticketId)),
        logUrl: @js(route('admin.tickets.whatsapp.log', $ticketId)),
    })"
    class="rounded-xl bg-slate-50/70 p-3.5 ring-1 ring-inset ring-slate-200"
>
    {{-- Channel header --}}
    <div class="flex items-center justify-between gap-2">
        <div class="flex items-center gap-2.5">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-100">
                <x-icon.whatsapp class="h-4 w-4" />
            </span>
            <div class="leading-tight">
                <p class="text-sm font-semibold text-slate-900">WhatsApp</p>
                <p class="text-xs text-slate-500" x-text="hasPhone ? 'Número vinculado' : 'Sin número'"></p>
            </div>
        </div>
    </div>

    {{-- Number: display / inline edit --}}
    <div class="mt-3">
        {{-- Display mode --}}
        <div x-show="!editing" class="flex items-center justify-between gap-2">
            <template x-if="hasPhone">
                <span class="font-medium tabular-nums text-slate-700" x-text="phone"></span>
            </template>
            <template x-if="!hasPhone">
                <span class="text-sm text-slate-400">Todavía no cargado</span>
            </template>
            <button type="button" @click="startEdit()"
                class="inline-flex items-center gap-1 rounded-md px-1.5 py-1 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                <i data-lucide="link-2" class="h-3.5 w-3.5"></i>
                <span x-text="hasPhone ? 'Editar' : 'Vincular'"></span>
            </button>
        </div>

        {{-- Edit mode --}}
        <div x-show="editing" x-cloak
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
        >
            <label class="sr-only" :for="'wa-phone-' + $id('f')">Número de WhatsApp</label>
            <div class="flex items-center gap-2">
                <input :id="'wa-phone-' + $id('f')" x-ref="phoneField" x-model="phoneInput"
                    type="tel" inputmode="tel" autocomplete="tel"
                    placeholder="+54 9 11 1234 5678"
                    @keydown.enter.prevent="saveNumber()"
                    class="block w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-slate-800 shadow-sm ring-1 ring-inset ring-slate-300 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500">
                <button type="button" @click="saveNumber()" :disabled="savingNumber"
                    class="inline-flex h-9 shrink-0 items-center rounded-lg bg-emerald-700 px-3 text-sm font-medium text-white transition-colors hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2 disabled:opacity-60">
                    <span x-show="!savingNumber">Guardar</span>
                    <span x-show="savingNumber" x-cloak>Guardando…</span>
                </button>
                <button type="button" @click="cancelEdit()" class="shrink-0 rounded-lg p-2 text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500" aria-label="Cancelar">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>
            <p x-show="numberError" x-cloak class="mt-1.5 text-xs text-rose-600" role="alert" x-text="numberError"></p>
        </div>
    </div>

    {{-- Composer (only when a number is linked) --}}
    <div x-show="hasPhone" class="mt-3.5 border-t border-slate-200 pt-3.5">
        {{-- Template chips --}}
        <p class="mb-2 text-xs font-medium text-slate-500">Plantilla</p>
        <div class="flex flex-wrap gap-1.5" role="group" aria-label="Plantillas de WhatsApp">
            <template x-for="tpl in templates" :key="tpl.key">
                <button type="button" @click="pick(tpl)"
                    :aria-pressed="activeKey === tpl.key"
                    :class="activeKey === tpl.key
                        ? 'bg-emerald-50 text-emerald-800 ring-emerald-300'
                        : 'bg-white text-slate-600 ring-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500">
                    <i data-lucide="check" class="h-3 w-3" x-show="activeKey === tpl.key" x-cloak></i>
                    <span x-text="tpl.label"></span>
                </button>
            </template>
        </div>

        {{-- Editable preview --}}
        <div x-show="body !== ''" x-cloak class="mt-3"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
        >
            <label class="sr-only" :for="'wa-body-' + $id('f')">Mensaje de WhatsApp</label>
            <textarea :id="'wa-body-' + $id('f')" x-model="body" rows="5"
                @input="sent = false"
                class="block w-full resize-y rounded-lg border-0 bg-white px-3 py-2.5 text-sm leading-6 text-slate-700 shadow-sm ring-1 ring-inset ring-slate-200 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-brand-500"></textarea>

            <label class="mt-2.5 flex cursor-pointer items-center gap-2 text-xs text-slate-600">
                <input type="checkbox" x-model="saveNote" class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-brand-500">
                Guardar copia como nota interna
            </label>

            <div class="mt-3 flex items-center gap-2">
                <a :href="body.trim() ? waUrl() : '#'" target="_blank" rel="noopener"
                   @click="send($event)"
                   :aria-disabled="!body.trim()"
                   :class="body.trim() ? '' : 'pointer-events-none opacity-50'"
                   aria-label="Abrir WhatsApp con el cliente"
                   class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-500 focus-visible:ring-offset-2">
                    <template x-if="!sent">
                        <span class="inline-flex items-center gap-2"><x-icon.whatsapp class="h-4 w-4" /> Abrir WhatsApp</span>
                    </template>
                    <template x-if="sent">
                        <span class="inline-flex items-center gap-2"><i data-lucide="check" class="h-4 w-4"></i> Registrado</span>
                    </template>
                </a>
            </div>
            <p x-show="logError" x-cloak class="mt-2 text-xs text-amber-600" role="status" x-text="logError"></p>
            <p class="mt-2 text-xs leading-5 text-slate-400">Abre WhatsApp con el mensaje cargado. Queda registrado en la actividad del ticket.</p>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                window.Alpine && Alpine.data('whatsappPanel', (cfg) => ({
                    hasPhone: cfg.hasPhone,
                    phone: cfg.phone || '',
                    waBase: cfg.waBase,
                    templates: cfg.templates || [],
                    numberUrl: cfg.numberUrl,
                    logUrl: cfg.logUrl,

                    editing: false,
                    phoneInput: '',
                    numberError: '',
                    savingNumber: false,

                    activeKey: null,
                    body: '',
                    saveNote: true,
                    sending: false,
                    sent: false,
                    logError: '',

                    csrf() {
                        return document.querySelector('meta[name="csrf-token"]')?.content || '';
                    },

                    startEdit() {
                        this.phoneInput = this.phone;
                        this.editing = true;
                        this.numberError = '';
                        this.$nextTick(() => { this.$refs.phoneField && this.$refs.phoneField.focus(); window.renderIcons && window.renderIcons(); });
                    },
                    cancelEdit() {
                        this.editing = false;
                        this.numberError = '';
                    },

                    async saveNumber() {
                        if (this.savingNumber || !this.phoneInput.trim()) return;
                        this.savingNumber = true;
                        this.numberError = '';
                        try {
                            const res = await fetch(this.numberUrl, {
                                method: 'PUT',
                                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                                credentials: 'same-origin',
                                body: JSON.stringify({ phone: this.phoneInput }),
                            });
                            const data = await res.json().catch(() => ({}));
                            if (!res.ok) {
                                this.numberError = data?.errors?.phone?.[0] || data?.message || 'No se pudo guardar el número.';
                                return;
                            }
                            this.phone = data.phone;
                            this.waBase = data.wa_base;
                            this.hasPhone = true;
                            this.editing = false;
                            this.$nextTick(() => window.renderIcons && window.renderIcons());
                        } catch (e) {
                            this.numberError = 'No se pudo guardar el número.';
                        } finally {
                            this.savingNumber = false;
                        }
                    },

                    pick(tpl) {
                        this.activeKey = tpl.key;
                        this.body = tpl.text;
                        this.sent = false;
                        this.logError = '';
                        this.$nextTick(() => window.renderIcons && window.renderIcons());
                    },

                    waUrl() {
                        return this.waBase ? this.waBase + '?text=' + encodeURIComponent(this.body) : '#';
                    },

                    async send(e) {
                        if (!this.body.trim() || !this.waBase) {
                            if (e) e.preventDefault();
                            return;
                        }
                        // The anchor opens WhatsApp in a new tab; we log in the background.
                        this.sending = true;
                        this.logError = '';
                        try {
                            const res = await fetch(this.logUrl, {
                                method: 'POST',
                                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': this.csrf() },
                                credentials: 'same-origin',
                                body: JSON.stringify({ body: this.body, template_key: this.activeKey, save_note: this.saveNote }),
                            });
                            if (!res.ok) throw new Error();
                            this.sent = true;
                            this.$nextTick(() => window.renderIcons && window.renderIcons());
                            if (this.saveNote) window.setTimeout(() => window.location.reload(), 750);
                        } catch (err) {
                            this.logError = 'WhatsApp se abrió, pero no se pudo registrar la actividad.';
                        } finally {
                            this.sending = false;
                        }
                    },
                }));
            });
        </script>
    @endpush
@endonce
