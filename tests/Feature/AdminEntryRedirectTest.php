<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminEntryRedirectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    public function test_admin_root_redirects_staff_to_dashboard(): void
    {
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole('Agente');

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect(route('admin.tickets.dashboard'));
    }

    public function test_admin_login_redirects_authenticated_staff_to_dashboard(): void
    {
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole('Admin');

        $this->actingAs($user)
            ->get('/admin/login')
            ->assertRedirect(route('admin.tickets.dashboard'));
    }

    public function test_admin_login_redirects_authenticated_customer_to_portal(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Cliente');

        $this->actingAs($user)
            ->get('/admin/login')
            ->assertRedirect(route('portal.tickets.index'));
    }
}
