<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSiteSettingsRequest;
use App\Models\SiteSetting;
use App\Support\Whatsapp;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SiteSettingController extends Controller
{
    public const DEFAULT_GREETING = 'Hola, tengo una consulta y me gustaría que me ayuden.';

    /** Public-site configuration. Route is gated to Super Admin. */
    public function edit(): View
    {
        $number = SiteSetting::get('whatsapp_number');
        $normalized = $number ? Whatsapp::normalize($number) : null;

        return view('admin.site-settings.edit', [
            'settings' => [
                'whatsapp_number' => $number,
                'whatsapp_enabled' => SiteSetting::getBool('whatsapp_enabled', true),
                'whatsapp_greeting' => SiteSetting::get('whatsapp_greeting', self::DEFAULT_GREETING),
            ],
            'wa_link' => $normalized ? Whatsapp::link($normalized, '') : null,
        ]);
    }

    public function update(UpdateSiteSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        SiteSetting::setMany([
            'whatsapp_number' => $data['whatsapp_number'] ?? null,
            'whatsapp_enabled' => ($data['whatsapp_enabled'] ?? false) ? '1' : '0',
            'whatsapp_greeting' => $data['whatsapp_greeting'] ?? null,
        ]);

        return back()->with('success', 'Configuración del sitio público actualizada.');
    }
}
