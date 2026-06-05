<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DefaultAssigneeTest extends TestCase
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

    private function agent(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge(['is_agent' => true], $attributes));
        $user->assignRole('Agente');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole('Admin');

        return $user;
    }

    private function setDefault(User $agent): void
    {
        SiteSetting::setMany(['default_assignee_id' => (string) $agent->id]);
    }

    private function ticketPayload(array $overrides = []): array
    {
        return array_merge([
            'subject' => 'Falla en el sitio',
            'description' => 'El cliente reporta un error 500.',
            'customer_name' => 'Cliente X',
            'customer_email' => 'clientex@example.com',
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
        ], $overrides);
    }

    public function test_agent_can_create_a_ticket(): void
    {
        $author = $this->agent();
        $default = $this->agent();
        $this->setDefault($default);

        $this->actingAs($author)
            ->post(route('admin.tickets.store'), $this->ticketPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', ['subject' => 'Falla en el sitio']);
    }

    public function test_agent_created_ticket_falls_to_the_default_assignee(): void
    {
        $author = $this->agent();
        $default = $this->agent();
        $this->setDefault($default);

        $this->actingAs($author)->post(route('admin.tickets.store'), $this->ticketPayload());

        $ticket = Ticket::where('subject', 'Falla en el sitio')->firstOrFail();
        $this->assertSame($default->id, $ticket->assigned_to);
    }

    public function test_agent_cannot_choose_the_assignee_even_by_tampering(): void
    {
        $author = $this->agent();
        $default = $this->agent();
        $other = $this->agent();
        $this->setDefault($default);

        // Agent has no tickets.assign, so a forged assigned_to must be ignored.
        $this->actingAs($author)->post(route('admin.tickets.store'), $this->ticketPayload([
            'assigned_to' => $other->id,
        ]));

        $ticket = Ticket::where('subject', 'Falla en el sitio')->firstOrFail();
        $this->assertSame($default->id, $ticket->assigned_to);
    }

    public function test_admin_can_choose_a_specific_assignee(): void
    {
        $admin = $this->admin();
        $default = $this->agent();
        $chosen = $this->agent();
        $this->setDefault($default);

        $this->actingAs($admin)->post(route('admin.tickets.store'), $this->ticketPayload([
            'assigned_to' => $chosen->id,
        ]));

        $ticket = Ticket::where('subject', 'Falla en el sitio')->firstOrFail();
        $this->assertSame($chosen->id, $ticket->assigned_to);
    }

    public function test_admin_leaving_it_blank_falls_to_the_default(): void
    {
        $admin = $this->admin();
        $default = $this->agent();
        $this->setDefault($default);

        $this->actingAs($admin)->post(route('admin.tickets.store'), $this->ticketPayload([
            'assigned_to' => '',
        ]));

        $ticket = Ticket::where('subject', 'Falla en el sitio')->firstOrFail();
        $this->assertSame($default->id, $ticket->assigned_to);
    }

    public function test_public_ticket_falls_to_the_default_assignee(): void
    {
        $default = $this->agent();
        $this->setDefault($default);

        $this->post(route('public.support.store'), [
            'name' => 'Visitante',
            'email' => 'visitante@example.com',
            'subject' => 'Consulta pública',
            'category_id' => TicketCategory::query()->value('id'),
            'description' => 'Tengo una duda sobre el servicio.',
        ])->assertRedirect();

        $ticket = Ticket::where('subject', 'Consulta pública')->firstOrFail();
        $this->assertSame($default->id, $ticket->assigned_to);
    }

    public function test_helper_returns_null_when_default_is_inactive(): void
    {
        $agent = $this->agent(['is_active' => false]);
        $this->setDefault($agent);

        $this->assertNull(SiteSetting::defaultAssigneeId());
    }

    public function test_helper_returns_null_when_default_is_not_an_agent(): void
    {
        $user = User::factory()->create(['is_agent' => false]);
        SiteSetting::setMany(['default_assignee_id' => (string) $user->id]);

        $this->assertNull(SiteSetting::defaultAssigneeId());
    }

    public function test_assignment_settings_is_forbidden_for_agents(): void
    {
        $this->actingAs($this->agent())
            ->get(route('admin.assignment-settings.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_open_and_save_the_default_assignee(): void
    {
        $admin = $this->admin();
        $agent = $this->agent();

        $this->actingAs($admin)
            ->get(route('admin.assignment-settings.edit'))
            ->assertOk();

        $this->actingAs($admin)
            ->put(route('admin.assignment-settings.update'), ['default_assignee_id' => $agent->id])
            ->assertRedirect();

        $this->assertSame($agent->id, SiteSetting::defaultAssigneeId());
    }
}
