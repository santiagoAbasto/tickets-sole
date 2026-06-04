<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ ($title ?? null) ? $title . ' · ' : '' }}{{ config('app.name', 'Osole Tickets') }}</title>
<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="{{ asset('img/logo.svg') }}" type="image/svg+xml">

{{-- Fonts (privacy-friendly, no build) --}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

{{-- Pre-compiled Tailwind CSS — generated locally, served statically (no build on the server) --}}
<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: '1' }}">

{{-- Interactivity via CDN: ApexCharts (charts), Lucide (icons), Alpine (behaviour) --}}
<script src="https://cdn.jsdelivr.net/npm/apexcharts@3.54.1/dist/apexcharts.min.js"></script>
<script src="https://unpkg.com/lucide@0.460.0/dist/umd/lucide.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3.14.8/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/intersect@3.14.8/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>

<script>
    // (Re)hydrate Lucide icons on load and whenever Alpine swaps DOM.
    function renderIcons() { if (window.lucide) window.lucide.createIcons(); }
    document.addEventListener('DOMContentLoaded', renderIcons);
    document.addEventListener('alpine:initialized', renderIcons);

    // Reusable file uploader with image previews (accumulates via DataTransfer).
    document.addEventListener('alpine:init', () => {
        window.Alpine && Alpine.data('uploader', () => ({
            items: [],
            fmtSize(b) { const u = ['B', 'KB', 'MB']; let i = b > 0 ? Math.floor(Math.log(b) / Math.log(1024)) : 0; i = Math.min(i, 2); return (b / Math.pow(1024, i)).toFixed(i ? 1 : 0) + ' ' + u[i]; },
            add(e) {
                const dt = new DataTransfer();
                this.items.forEach((it) => dt.items.add(it.file));
                Array.from(e.target.files).forEach((f) => {
                    const isImage = f.type.startsWith('image/');
                    this.items.push({ file: f, name: f.name, size: this.fmtSize(f.size), isImage, url: isImage ? URL.createObjectURL(f) : null });
                    dt.items.add(f);
                });
                e.target.files = dt.files;
                this.$nextTick(() => window.renderIcons && window.renderIcons());
            },
            remove(i, input) {
                if (this.items[i] && this.items[i].url) URL.revokeObjectURL(this.items[i].url);
                this.items.splice(i, 1);
                const dt = new DataTransfer();
                this.items.forEach((it) => dt.items.add(it.file));
                input.files = dt.files;
            },
            reset(input) {
                this.items.forEach((it) => it.url && URL.revokeObjectURL(it.url));
                this.items = [];
                if (input) input.value = '';
            },
        }));
    });

    // Live ticket chat: append new messages without reloading the page.
    (function () {
        const POLL_MS = 2000;
        const HIDDEN_POLL_MS = 6000;
        const nf = new Intl.DateTimeFormat('es-AR', {
            day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
        });

        function esc(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function initials(name) {
            const parts = String(name || 'U').trim().split(/\s+/).filter(Boolean).slice(0, 2);
            return (parts.map((part) => part.charAt(0)).join('') || 'U').toUpperCase();
        }

        function when(iso) {
            if (!iso) return '';
            try { return nf.format(new Date(iso)).replace(',', ' ·'); } catch (_) { return ''; }
        }

        function avatar(name, src) {
            if (src) {
                return `<img src="${esc(src)}" alt="${esc(name)}" class="mt-0.5 h-8 w-8 shrink-0 rounded-full object-cover ring-1 ring-slate-200">`;
            }

            return `<span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-brand-100 text-xs font-semibold text-brand-700 ring-1 ring-inset ring-black/5" aria-hidden="true">${esc(initials(name))}</span>`;
        }

        function attachments(items, isCustomer) {
            if (!Array.isArray(items) || !items.length) return '';

            return `<div class="mt-2 flex flex-wrap gap-2 ${isCustomer ? '' : 'justify-end'}">` + items.map((item) => `
                <a href="${esc(item.url)}" target="_blank" rel="noreferrer" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs text-slate-600 transition hover:border-brand-300 hover:text-brand-700">
                    <i data-lucide="${item.is_image ? 'image' : 'file-text'}" class="h-3.5 w-3.5"></i>
                    <span class="max-w-[12rem] truncate">${esc(item.name)}</span>
                    <span class="text-slate-400">${esc(item.size)}</span>
                </a>
            `).join('') + '</div>';
        }

        function renderMessage(message, customerName) {
            const isCustomer = message.author_type === 'customer';
            const name = message.author?.name || (isCustomer ? customerName : 'Soporte');
            const bodyClass = isCustomer
                ? 'rounded-tl-sm bg-white text-slate-700 ring-1 ring-inset ring-slate-200'
                : 'rounded-tr-sm bg-brand-600 text-white';

            return `
                <div data-message-id="${esc(message.id)}" class="flex gap-3 ${isCustomer ? '' : 'flex-row-reverse'}">
                    ${avatar(name, message.author?.avatar_url)}
                    <div class="min-w-0 max-w-[78%]">
                        <div class="mb-1 flex items-center gap-2 text-xs ${isCustomer ? '' : 'justify-end'}">
                            <span class="font-medium text-slate-700">${esc(name)}</span>
                            <span class="text-slate-400">${esc(when(message.created_at))}</span>
                        </div>
                        <div class="rounded-2xl px-4 py-2.5 text-sm ${bodyClass}">
                            <p class="whitespace-pre-wrap break-words">${esc(message.body)}</p>
                        </div>
                        ${attachments(message.attachments, isCustomer)}
                    </div>
                </div>
            `;
        }

        function appendMessages(chat, messages) {
            if (!Array.isArray(messages) || !messages.length) return;

            const customerName = chat.dataset.customerName || 'Cliente';
            const seen = new Set([...chat.querySelectorAll('[data-message-id]')].map((node) => String(node.dataset.messageId)));
            const fresh = messages.filter((message) => message?.id && !seen.has(String(message.id)));

            if (!fresh.length) return;

            const nearBottom = window.innerHeight + window.scrollY >= document.body.scrollHeight - 180;
            chat.querySelector('[data-chat-empty]')?.remove();
            chat.insertAdjacentHTML('beforeend', fresh.map((message) => renderMessage(message, customerName)).join(''));
            chat.dataset.lastMessageId = String(Math.max(Number(chat.dataset.lastMessageId || 0), ...fresh.map((message) => Number(message.id) || 0)));

            window.renderIcons && window.renderIcons();
            if (nearBottom) window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }

        function pollUrl(chat) {
            const url = new URL(chat.dataset.endpoint, window.location.origin);
            url.searchParams.set('after_id', chat.dataset.lastMessageId || '0');
            return url;
        }

        function initChat(chat) {
            if (chat.dataset.liveReady === '1') return;
            chat.dataset.liveReady = '1';

            let timer = null;
            let busy = false;

            const tick = async () => {
                if (busy) return;
                busy = true;

                try {
                    const response = await fetch(pollUrl(chat), {
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });

                    if (response.ok) {
                        const data = await response.json();
                        appendMessages(chat, data.messages || []);
                    }
                } catch (_) {
                    // Keep polling. A transient network hiccup should not freeze the chat.
                } finally {
                    busy = false;
                    timer = window.setTimeout(tick, document.hidden ? HIDDEN_POLL_MS : POLL_MS);
                }
            };

            timer = window.setTimeout(tick, 500);
            document.addEventListener('visibilitychange', () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(tick, document.hidden ? HIDDEN_POLL_MS : 250);
            });
        }

        function formError(form, message) {
            let target = form.querySelector('[data-chat-error]');
            if (!target) {
                target = document.createElement('p');
                target.dataset.chatError = '1';
                target.className = 'mt-2 text-sm text-rose-600';
                form.appendChild(target);
            }
            target.textContent = message;
        }

        function firstError(payload) {
            const errors = payload?.errors || {};
            const key = Object.keys(errors)[0];
            return key ? errors[key][0] : (payload?.message || 'No se pudo enviar el mensaje.');
        }

        function initForm(form) {
            if (form.dataset.liveReady === '1') return;
            form.dataset.liveReady = '1';

            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const chat = document.querySelector('[data-realtime-chat]');
                const submit = form.querySelector('[type="submit"]');
                const error = form.querySelector('[data-chat-error]');
                error?.remove();

                if (submit) {
                    submit.disabled = true;
                    submit.classList.add('opacity-70');
                }

                try {
                    const response = await fetch(form.action, {
                        method: (form.method || 'POST').toUpperCase(),
                        body: new FormData(form),
                        credentials: 'same-origin',
                        headers: { Accept: 'application/json' },
                    });
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok) throw new Error(firstError(payload));

                    if (chat && payload.message) appendMessages(chat, [payload.message]);
                    form.querySelectorAll('textarea').forEach((textarea) => { textarea.value = ''; });
                    form.querySelectorAll('input[type="file"]').forEach((input) => { input.value = ''; });
                    window.dispatchEvent(new CustomEvent('chat-reset'));
                } catch (err) {
                    formError(form, err.message || 'No se pudo enviar el mensaje.');
                } finally {
                    if (submit) {
                        submit.disabled = false;
                        submit.classList.remove('opacity-70');
                    }
                }
            });
        }

        function boot() {
            document.querySelectorAll('[data-realtime-chat]').forEach(initChat);
            document.querySelectorAll('[data-realtime-chat-form]').forEach(initForm);
        }

        window.osoleBootRealtimeChat = boot;
        document.addEventListener('DOMContentLoaded', boot);
        document.addEventListener('alpine:initialized', boot);
    })();

    // Shared ApexCharts defaults (premium, on-brand, reduced-motion aware).
    window.osoleChartBase = function () {
        const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        return {
            chart: { fontFamily: "'Inter', system-ui, sans-serif", foreColor: '#94a3b8', toolbar: { show: false },
                animations: { enabled: !reduce, easing: 'easeinout', speed: 500 } },
            grid: { borderColor: '#eef2f6', strokeDashArray: 4, padding: { left: 4, right: 4 } },
            tooltip: { theme: 'light', style: { fontSize: '12px' } },
            dataLabels: { enabled: false },
            legend: { position: 'bottom', markers: { radius: 12, width: 9, height: 9 }, fontSize: '12px', labels: { colors: '#64748b' } },
        };
    };
</script>
