<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Support\Whatsapp;
use Illuminate\Support\Facades\URL;

/**
 * Resolves the WhatsApp payload for a ticket: the customer's normalized number
 * and the message templates with their variables already substituted.
 */
class WhatsappTemplateService
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(Ticket $ticket, ?User $agent = null): array
    {
        $ticket->loadMissing('customer');
        $customer = $ticket->customer;

        $phone = $customer?->phone;
        $normalized = Whatsapp::normalize($phone);
        // Direct signed link → the customer opens their chat with one tap.
        $trackUrl = URL::signedRoute('public.track.direct', ['ticket' => $ticket->id]);

        $vars = [
            '{cliente}' => $this->firstName($customer?->name),
            '{codigo}' => $ticket->code,
            '{email}' => $customer?->email ?: '(sin email registrado)',
            '{link}' => $trackUrl,
            '{empresa}' => (string) config('app.name'),
            '{agente}' => $agent?->name ?? '',
        ];

        $templates = collect(config('whatsapp.templates', []))
            ->map(fn (array $t): array => [
                'key' => $t['key'],
                'label' => $t['label'],
                'icon' => $t['icon'] ?? 'message-circle',
                'text' => $this->tidy(strtr($t['text'], $vars)),
            ])
            ->values()
            ->all();

        return [
            'has_phone' => filled($normalized),
            'phone' => $phone,
            'phone_normalized' => $normalized,
            'wa_base' => $normalized ? 'https://wa.me/'.$normalized : null,
            'track_url' => $trackUrl,
            'templates' => $templates,
        ];
    }

    private function firstName(?string $name): string
    {
        $name = trim((string) $name);

        return $name === '' ? '' : explode(' ', $name)[0];
    }

    /** Collapse the double space left when an optional variable resolves empty. */
    private function tidy(string $text): string
    {
        return preg_replace('/ {2,}/', ' ', $text) ?? $text;
    }
}
