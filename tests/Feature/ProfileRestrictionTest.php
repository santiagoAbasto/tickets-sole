<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_non_super_admin_cannot_change_identity_fields_but_can_change_phone(): void
    {
        $user = User::factory()->create([
            'name' => 'Original',
            'email' => 'orig@osole.com.ar',
            'job_title' => 'Dev',
            'phone' => null,
        ]);
        $user->assignRole('Agente');

        $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => 'Hackeado',
                'email' => 'hackeado@osole.com.ar',
                'job_title' => 'Jefe',
                'phone' => '+54 9 11 5555-5555',
            ])
            ->assertRedirect();

        $user->refresh();

        // Identity fields ignored server-side.
        $this->assertSame('Original', $user->name);
        $this->assertSame('orig@osole.com.ar', $user->email);
        $this->assertSame('Dev', $user->job_title);

        // Phone updated.
        $this->assertSame('+54 9 11 5555-5555', $user->phone);
    }

    public function test_non_super_admin_profile_page_renders_identity_as_read_only(): void
    {
        $user = User::factory()->create(['name' => 'Cynthia Klein', 'job_title' => 'Diseñadora']);
        $user->assignRole('Agente');

        $this->actingAs($user)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertDontSee('name="name"', false)
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="job_title"', false)
            ->assertSee('name="phone"', false)
            ->assertSee('Cynthia Klein')
            ->assertSee('gestiona un administrador');
    }

    public function test_super_admin_profile_page_renders_identity_inputs(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="job_title"', false);
    }

    public function test_super_admin_can_change_identity_fields(): void
    {
        $user = User::factory()->create(['name' => 'Original', 'email' => 'orig@osole.com.ar']);
        $user->assignRole('Super Admin');

        $this->actingAs($user)
            ->put(route('admin.profile.update'), [
                'name' => 'Nombre Nuevo',
                'email' => 'nuevo@osole.com.ar',
                'phone' => '+54 9 11 0000-0000',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('Nombre Nuevo', $user->name);
        $this->assertSame('nuevo@osole.com.ar', $user->email);
    }
}
