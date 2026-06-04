<?php

namespace App\Support;

/**
 * Build wa.me "click to chat" links. No WhatsApp Business API, no Meta.
 *
 * normalize() turns a free-form phone string into digits-only international
 * format (no '+'), the shape wa.me expects. It handles the Argentine mobile
 * conventions (trunk 0, "15" prefix, the mandatory "9" after the country code).
 * The resolved number stays editable in the UI as a safety net.
 */
class Whatsapp
{
    public static function normalize(?string $raw, ?string $defaultCc = null): ?string
    {
        $defaultCc = $defaultCc ?: (string) config('whatsapp.default_country', '54');

        $raw = trim((string) $raw);
        if ($raw === '') {
            return null;
        }

        $compact = preg_replace('/\s+/', '', $raw);
        $international = str_starts_with($compact, '+') || str_starts_with($compact, '00');

        $digits = preg_replace('/\D+/', '', $raw) ?? '';
        if ($digits === '') {
            return null;
        }

        // International dialling prefix "00" → drop it, treat as already prefixed.
        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
            $international = true;
        }

        // Already carries the default country code (and is long enough): trust it.
        if (str_starts_with($digits, $defaultCc) && strlen($digits) >= strlen($defaultCc) + 8) {
            $digits = self::forceArgentineMobile($digits, $defaultCc);

            return strlen($digits) >= 8 ? $digits : null;
        }

        // Looked international (+/00) but for another country: trust as-is.
        if ($international) {
            return strlen($digits) >= 8 ? $digits : null;
        }

        // National number: strip trunk 0, strip the legacy mobile "15", add CC.
        $national = ltrim($digits, '0');
        $national = preg_replace('/^(\d{2,4})15(\d{6,8})$/', '$1$2', $national);

        $full = self::forceArgentineMobile($defaultCc.$national, $defaultCc);

        return strlen($full) >= strlen($defaultCc) + 8 ? $full : null;
    }

    public static function link(string $normalized, string $text): string
    {
        return 'https://wa.me/'.$normalized.'?text='.rawurlencode($text);
    }

    /**
     * Argentine mobiles on wa.me need the "9" right after the 54 country code
     * (e.g. 54 9 11 1234 5678). WhatsApp only works on mobiles, so forcing it is
     * the pragmatic correct behaviour. No-op for other countries.
     */
    private static function forceArgentineMobile(string $digits, string $cc): string
    {
        if ($cc === '54' && str_starts_with($digits, '54') && ! str_starts_with($digits, '549')) {
            return '549'.substr($digits, 2);
        }

        return $digits;
    }
}
