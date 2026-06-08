<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\HostCredential;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HostCredentialRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            RolePermissionSeeder::class,
            TicketStatusSeeder::class,
            TicketPrioritySeeder::class,
            TicketCategorySeeder::class,
        ]);
    }

    private function agent(string $role = 'Agente'): User
    {
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole($role);

        return $user;
    }

    private function ticket(?User $owner = null): Ticket
    {
        $customer = Customer::create(['name' => 'Cliente', 'email' => uniqid('c').'@example.com', 'is_active' => true]);

        return Ticket::create([
            'code' => 'HOST-'.uniqid(),
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'subject' => 'Panel web',
            'description' => 'Detalle',
            'created_by' => $owner?->id,
            'assigned_to' => $owner?->id,
        ]);
    }

    public function test_staff_can_register_a_host_without_a_ticket(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)
            ->post(route('admin.host-credentials.store'), [
                'name' => 'Cliente web',
                'server_url' => 'https://srv.example.com:2083',
                'hosting_provider' => 'cPanel',
                'cpanel_user' => 'cliente',
                'cpanel_password' => 'secret',
            ])
            ->assertRedirect();

        $host = HostCredential::firstOrFail();
        $this->assertSame($agent->id, $host->created_by);
        $this->assertSame('secret', $host->cpanel_password);
    }

    public function test_agents_only_see_their_own_hosts_and_admins_see_all(): void
    {
        $mine = $this->agent();
        $other = $this->agent();
        $admin = $this->agent('Admin');

        HostCredential::create([
            'created_by' => $mine->id,
            'fingerprint' => HostCredential::fingerprintFor(['server_url' => 'https://mine.test', 'cpanel_user' => 'u']),
            'name' => 'Mio',
            'server_url' => 'https://mine.test',
            'cpanel_user' => 'u',
        ]);
        HostCredential::create([
            'created_by' => $other->id,
            'fingerprint' => HostCredential::fingerprintFor(['server_url' => 'https://other.test', 'cpanel_user' => 'u']),
            'name' => 'Otro',
            'server_url' => 'https://other.test',
            'cpanel_user' => 'u',
        ]);

        $this->actingAs($mine)
            ->get(route('admin.host-credentials.index'))
            ->assertOk()
            ->assertSee('Mio')
            ->assertDontSee('https://other.test');

        $this->actingAs($admin)
            ->get(route('admin.host-credentials.index'))
            ->assertOk()
            ->assertSee('Mio')
            ->assertSee('Otro');
    }

    public function test_ticket_credentials_sync_into_the_global_host_registry_without_duplicates(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticket($agent);

        $payload = [
            'server_url' => 'https://srv.example.com:2083',
            'hosting_provider' => 'cPanel',
            'hosting_type' => 'external',
            'cpanel_user' => 'cliente',
            'cpanel_password' => 'secret',
            'notes' => 'Acceso principal',
        ];

        $this->actingAs($agent)
            ->put(route('admin.tickets.credentials.update', $ticket), $payload)
            ->assertRedirect();

        $this->actingAs($agent)
            ->put(route('admin.tickets.credentials.update', $ticket), array_merge($payload, ['notes' => 'Actualizado']))
            ->assertRedirect();

        $this->assertDatabaseCount('host_credentials', 1);
        $host = HostCredential::firstOrFail();
        $this->assertSame($ticket->id, $host->source_ticket_id);
        $this->assertSame('Actualizado', $host->notes);
        $this->assertSame('secret', $host->cpanel_password);
    }

    public function test_host_index_does_not_render_plaintext_password_and_reveals_visible_password(): void
    {
        $agent = $this->agent();
        $host = HostCredential::create([
            'created_by' => $agent->id,
            'fingerprint' => HostCredential::fingerprintFor([
                'server_url' => 'https://secure.example.com',
                'cpanel_user' => 'cliente',
            ]),
            'name' => 'Seguro',
            'server_url' => 'https://secure.example.com',
            'cpanel_user' => 'cliente',
            'cpanel_password' => 'host-secret',
        ]);

        $this->actingAs($agent)
            ->get(route('admin.host-credentials.index'))
            ->assertOk()
            ->assertDontSee('host-secret', false);

        $this->actingAs($agent)
            ->postJson(route('admin.host-credentials.reveal-password', $host))
            ->assertOk()
            ->assertJson(['password' => 'host-secret']);
    }

    public function test_agent_cannot_reveal_another_agents_host_password(): void
    {
        $agent = $this->agent();
        $other = $this->agent();
        $host = HostCredential::create([
            'created_by' => $other->id,
            'fingerprint' => HostCredential::fingerprintFor([
                'server_url' => 'https://other-secret.example.com',
                'cpanel_user' => 'cliente',
            ]),
            'name' => 'Otro',
            'server_url' => 'https://other-secret.example.com',
            'cpanel_user' => 'cliente',
            'cpanel_password' => 'other-secret',
        ]);

        $this->actingAs($agent)
            ->postJson(route('admin.host-credentials.reveal-password', $host))
            ->assertNotFound();
    }

    public function test_host_registry_rejects_non_http_urls(): void
    {
        $agent = $this->agent();

        $this->actingAs($agent)
            ->post(route('admin.host-credentials.store'), [
                'name' => 'Malicioso',
                'website_url' => 'javascript:alert(1)',
            ])
            ->assertSessionHasErrors('website_url');
    }
}
