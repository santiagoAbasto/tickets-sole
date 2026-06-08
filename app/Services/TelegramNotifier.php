<?php

namespace App\Services;

use App\Models\SiteSetting;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Outgoing ticket alerts via the Telegram Bot API. Notify-only: it pushes a
 * short "new ticket" message to every configured chat. No inbound, no reply.
 *
 * Config lives in site_settings (manageable from /admin/telegram-alerts):
 *   - telegram_alerts_enabled    '1' | '0'
 *   - telegram_bot_token         the @BotFather token
 *   - telegram_alerts_recipients JSON [{ name, chat_id }]
 */
class TelegramNotifier
{
    private const API = 'https://api.telegram.org/bot';

    public function enabled(): bool
    {
        return SiteSetting::getBool('telegram_alerts_enabled');
    }

    public function token(): ?string
    {
        return SiteSetting::get('telegram_bot_token') ?: null;
    }

    /** @return array<int, array{name:string, chat_id:string}> */
    public function recipients(): array
    {
        $raw = SiteSetting::get('telegram_alerts_recipients');

        if (! $raw) {
            return [];
        }

        $list = json_decode($raw, true);

        return is_array($list) ? array_values($list) : [];
    }

    /** Fire a "new ticket" message to every configured chat. */
    public function ticketCreated(Ticket $ticket): void
    {
        if (! $this->enabled()) {
            return;
        }

        $token = $this->token();

        if (! $token) {
            return;
        }

        $recipients = $this->recipients();

        if ($recipients === []) {
            return;
        }

        $text = $this->ticketMessage($ticket);

        foreach ($recipients as $r) {
            $chatId = trim((string) ($r['chat_id'] ?? ''));

            if ($chatId === '') {
                continue;
            }

            $this->send($token, $chatId, $text);
        }
    }

    /**
     * Low-level send. Never throws — a failed alert must not break the ticket
     * flow; it is logged and swallowed.
     */
    public function send(string $token, string $chatId, string $text): bool
    {
        try {
            $response = Http::timeout((int) config('telegram.timeout', 15))
                ->asForm()
                ->post(self::API.$token.'/sendMessage', [
                    'chat_id' => $chatId,
                    'text' => $text,
                    'disable_web_page_preview' => true,
                ]);

            if ($response->successful() && $response->json('ok') === true) {
                return true;
            }

            Log::warning('Telegram alert failed', [
                'chat_id' => $chatId,
                'status' => $response->status(),
                'description' => $response->json('description'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram alert error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }

    /**
     * Recent chats that messaged the bot — to help fill chat_ids during setup.
     *
     * @return array<int, array{id:string, name:string}>
     */
    public function detectChats(string $token): array
    {
        try {
            $response = Http::timeout((int) config('telegram.timeout', 15))
                ->get(self::API.$token.'/getUpdates');

            if (! $response->successful() || $response->json('ok') !== true) {
                return [];
            }

            $chats = [];

            foreach ((array) $response->json('result', []) as $update) {
                $chat = $update['message']['chat']
                    ?? $update['my_chat_member']['chat']
                    ?? null;

                if (! $chat || ! isset($chat['id'])) {
                    continue;
                }

                $name = trim(($chat['first_name'] ?? '').' '.($chat['last_name'] ?? ''));
                $name = $name ?: ($chat['title'] ?? $chat['username'] ?? '');

                $chats[(string) $chat['id']] = ['id' => (string) $chat['id'], 'name' => $name];
            }

            return array_values($chats);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function ticketMessage(Ticket $ticket): string
    {
        $ticket->loadMissing(['priority', 'customer']);

        $lines = array_filter([
            "Nuevo ticket {$ticket->code}",
            $ticket->subject,
            $ticket->customer?->name ? "Cliente: {$ticket->customer->name}" : null,
            $ticket->priority?->name ? "Prioridad: {$ticket->priority->name}" : null,
            rtrim((string) config('app.url'), '/')."/admin/tickets/{$ticket->id}",
        ]);

        return implode("\n", $lines);
    }
}
