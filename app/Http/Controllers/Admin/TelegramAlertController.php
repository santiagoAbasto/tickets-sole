<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\TelegramNotifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Outgoing ticket alerts via Telegram — notify-only. Admin / Super Admin
 * (route gated by `permission:settings.manage`).
 */
class TelegramAlertController extends Controller
{
    public function __construct(private TelegramNotifier $notifier) {}

    public function edit(): View
    {
        return view('admin.telegram-alerts.edit', [
            'enabled' => $this->notifier->enabled(),
            'token' => $this->notifier->token(),
            'recipients' => $this->notifier->recipients(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'token' => ['nullable', 'string', 'max:120'],
            'recipients' => ['array'],
            'recipients.*.name' => ['nullable', 'string', 'max:80'],
            'recipients.*.chat_id' => ['nullable', 'string', 'max:40'],
        ]);

        // Keep only rows that carry a chat_id.
        $recipients = collect($data['recipients'] ?? [])
            ->map(fn ($r) => [
                'name' => trim((string) ($r['name'] ?? '')),
                'chat_id' => trim((string) ($r['chat_id'] ?? '')),
            ])
            ->filter(fn ($r) => $r['chat_id'] !== '')
            ->values()
            ->all();

        SiteSetting::setMany([
            'telegram_alerts_enabled' => $request->boolean('enabled') ? '1' : '0',
            'telegram_bot_token' => trim((string) ($data['token'] ?? '')),
            'telegram_alerts_recipients' => json_encode($recipients),
        ]);

        return back()->with('success', 'Avisos por Telegram actualizados.');
    }

    public function test(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'chat_id' => ['required', 'string', 'max:40'],
        ]);

        $token = $this->notifier->token();

        if (! $token) {
            return back()->with('error', 'Primero guardá el token del bot y volvé a probar.');
        }

        $ok = $this->notifier->send(
            $token,
            $data['chat_id'],
            'Osole Helpdesk: prueba de aviso. Si ves esto, los avisos por Telegram ya funcionan.',
        );

        return $ok
            ? back()->with('success', 'Mensaje de prueba enviado. Revisá Telegram.')
            : back()->with('error', 'No se pudo enviar. Revisá el token y que la persona le haya escrito al bot primero.');
    }

    public function detect(): RedirectResponse
    {
        $token = $this->notifier->token();

        if (! $token) {
            return back()->with('error', 'Primero guardá el token del bot.');
        }

        $chats = $this->notifier->detectChats($token);

        if ($chats === []) {
            return back()->with('info', 'No detecté chats. Pedile a cada persona que le escriba algo al bot (por ejemplo /start) y volvé a tocar Detectar.');
        }

        $summary = collect($chats)
            ->map(fn ($c) => ($c['name'] !== '' ? $c['name'] : 'Sin nombre').' → '.$c['id'])
            ->implode('   ·   ');

        return back()->with('success', 'Chat IDs detectados:   '.$summary);
    }
}
