<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram — ticket alerts (outgoing, notify-only)
    |--------------------------------------------------------------------------
    | Free, official Bot API. Create a bot with @BotFather to get a token, then
    | each recipient (or a group) provides a chat_id. We only SEND a short
    | "new ticket" message — there is no inbound / reply handling.
    |
    | The bot token and recipients are managed from the panel
    | (Configuración → Avisos Telegram) and stored in site_settings.
    */

    // Seconds to wait for Telegram before giving up. The send runs deferred
    // (after the HTTP response) so this never delays ticket creation.
    'timeout' => (int) env('TELEGRAM_TIMEOUT', 15),
];
