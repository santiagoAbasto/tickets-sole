<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SiteSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->seed(RolePermissionSeeder::class);
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_model_stores_reads_and_busts_cache(): void
    {
        SiteSetting::setMany(['whatsapp_number' => '+54 9 11 1234 5678', 'whatsapp_enabled' => '1']);

        $this->assertSame('+54 9 11 1234 5678', SiteSetting::get('whatsapp_number'));
        $this->assertTrue(SiteSetting::getBool('whatsapp_enabled'));
        $this->assertFalse(SiteSetting::getBool('missing_key', false));

        SiteSetting::setMany(['whatsapp_enabled' => '0']);
        $this->assertFalse(SiteSetting::getBool('whatsapp_enabled'));
    }

    public function test_super_admin_can_update_settings(): void
    {
        $this->actingAs($this->user('Super Admin'))
            ->put(route('admin.site-settings.update'), [
                'whatsapp_number' => '11 1234-5678',
                'whatsapp_enabled' => '1',
                'whatsapp_greeting' => 'Hola, necesito ayuda.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('site_settings', ['key' => 'whatsapp_number', 'value' => '11 1234-5678']);
        $this->assertDatabaseHas('site_settings', ['key' => 'whatsapp_enabled', 'value' => '1']);
    }

    public function test_invalid_number_is_rejected(): void
    {
        $this->actingAs($this->user('Super Admin'))
            ->put(route('admin.site-settings.update'), ['whatsapp_number' => 'no-es-numero'])
            ->assertSessionHasErrors('whatsapp_number');

        $this->assertDatabaseMissing('site_settings', ['key' => 'whatsapp_number']);
    }

    public function test_non_super_admin_cannot_access_the_page(): void
    {
        $this->actingAs($this->user('Admin'))
            ->get(route('admin.site-settings.edit'))
            ->assertForbidden();
    }

    public function test_public_widget_shows_when_configured(): void
    {
        SiteSetting::setMany([
            'whatsapp_number' => '11 3416-6916',
            'whatsapp_enabled' => '1',
            'whatsapp_greeting' => 'Hola',
        ]);

        $this->get(route('public.track.form'))
            ->assertOk()
            ->assertSee('wa.me/5491134166916', false)
            ->assertSee('Abrir el chat de WhatsApp');
    }

    public function test_public_widget_hidden_when_disabled_or_unset(): void
    {
        // Unset → hidden
        $this->get(route('public.track.form'))->assertDontSee('Abrir el chat de WhatsApp');

        // Configured but disabled → hidden
        SiteSetting::setMany(['whatsapp_number' => '11 3416-6916', 'whatsapp_enabled' => '0']);
        $this->get(route('public.track.form'))->assertDontSee('Abrir el chat de WhatsApp');
    }
}
