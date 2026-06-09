<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketDelegationRequest;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketDelegationTest extends TestCase
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

    private function agent(): User
    {
        $user = User::factory()->create(['is_agent' => true, 'is_active' => true]);
        $user->assignRole('Agente');

        return $user;
    }

    private function withRole(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function ticketFor(?int $agentId): Ticket
    {
        $customer = Customer::create(['name' => 'C', 'email' => uniqid().'@x.com', 'is_active' => true]);

        return Ticket::create([
            'code' => 'OSL-DG-'.uniqid(),
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'assigned_to' => $agentId,
            'subject' => 'S',
            'description' => 'D',
        ]);
    }

    public function test_agent_can_only_reply_to_own_assigned_ticket(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $ticket = $this->ticketFor($bruno->id);

        $this->assertTrue($bruno->can('reply', $ticket));
        $this->assertFalse($rodrigo->can('reply', $ticket));

        // Non-assignee agent is blocked at the endpoint.
        $this->actingAs($rodrigo)
            ->post(route('admin.tickets.messages.store', $ticket), ['body' => 'no debería'])
            ->assertForbidden();

        // The assignee can reply.
        $this->actingAs($bruno)
            ->post(route('admin.tickets.messages.store', $ticket), ['body' => 'hola'])
            ->assertRedirect();
    }

    public function test_admin_can_reply_to_any_ticket(): void
    {
        $admin = $this->withRole('Admin');
        $ticket = $this->ticketFor($this->agent()->id);

        $this->assertTrue($admin->can('reply', $ticket));
    }

    public function test_assignee_requests_delegation_and_super_admin_approves_reassigns(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);

        // Bruno (assignee) requests delegation to Rodrigo.
        $this->actingAs($bruno)
            ->post(route('admin.tickets.delegations.store', $ticket), [
                'requested_to' => $rodrigo->id,
                'note' => 'Me voy de licencia.',
            ])
            ->assertRedirect();

        $delegation = TicketDelegationRequest::firstOrFail();
        $this->assertSame('pending', $delegation->status);
        $this->assertSame($rodrigo->id, $delegation->requested_to);

        // Super Admin approves → reassigned.
        $this->actingAs($super)
            ->post(route('admin.tickets.delegations.approve', [$ticket, $delegation]))
            ->assertRedirect();

        $this->assertSame($rodrigo->id, $ticket->fresh()->assigned_to);
        $this->assertSame('approved', $delegation->fresh()->status);

        // Now Rodrigo can reply; Bruno can't.
        $this->assertTrue($rodrigo->can('reply', $ticket->fresh()));
        $this->assertFalse($bruno->can('reply', $ticket->fresh()));
    }

    public function test_opening_approve_url_with_get_does_not_approve_and_returns_to_ticket(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);
        $delegation = $ticket->delegationRequests()->create([
            'requested_by' => $bruno->id,
            'requested_to' => $rodrigo->id,
            'status' => 'pending',
        ]);

        $this->actingAs($super)
            ->get(route('admin.tickets.delegations.review', [$ticket, $delegation]))
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHas('info');

        $this->assertSame($bruno->id, $ticket->fresh()->assigned_to);
        $this->assertSame('pending', $delegation->fresh()->status);
    }

    public function test_get_approve_url_for_delegation_from_another_ticket_redirects_to_real_ticket(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);
        $otherTicket = $this->ticketFor($bruno->id);
        $delegation = $otherTicket->delegationRequests()->create([
            'requested_by' => $bruno->id,
            'requested_to' => $rodrigo->id,
            'status' => 'pending',
        ]);

        $this->actingAs($super)
            ->get(route('admin.tickets.delegations.review', [$ticket, $delegation]))
            ->assertRedirect(route('admin.tickets.show', $otherTicket))
            ->assertSessionHas('info');
    }

    public function test_get_approve_url_for_missing_delegation_returns_to_ticket_with_error(): void
    {
        $bruno = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);

        $this->actingAs($super)
            ->get(route('admin.tickets.delegations.review', [$ticket, 999999]))
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHas('error');
    }

    public function test_get_approve_url_with_missing_ticket_but_existing_delegation_redirects_to_real_ticket(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);
        $delegation = $ticket->delegationRequests()->create([
            'requested_by' => $bruno->id,
            'requested_to' => $rodrigo->id,
            'status' => 'pending',
        ]);

        $this->actingAs($super)
            ->get("/admin/tickets/999999/delegations/{$delegation->id}/approve")
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHas('info');
    }

    public function test_get_approve_url_with_missing_ticket_and_missing_delegation_redirects_to_dashboard(): void
    {
        $super = $this->withRole('Super Admin');

        $this->actingAs($super)
            ->get('/admin/tickets/999999/delegations/888888/approve')
            ->assertRedirect(route('admin.tickets.dashboard'))
            ->assertSessionHas('error');
    }

    public function test_post_approve_url_for_delegation_from_another_ticket_approves_the_real_ticket(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);
        $otherTicket = $this->ticketFor($bruno->id);
        $delegation = $otherTicket->delegationRequests()->create([
            'requested_by' => $bruno->id,
            'requested_to' => $rodrigo->id,
            'status' => 'pending',
        ]);

        $this->actingAs($super)
            ->post(route('admin.tickets.delegations.approve', [$ticket, $delegation]))
            ->assertRedirect(route('admin.tickets.show', $otherTicket))
            ->assertSessionHas('success');

        $this->assertSame($rodrigo->id, $otherTicket->fresh()->assigned_to);
        $this->assertSame('approved', $delegation->fresh()->status);
    }

    public function test_post_approve_url_for_missing_delegation_returns_to_ticket_with_error(): void
    {
        $bruno = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);

        $this->actingAs($super)
            ->post(route('admin.tickets.delegations.approve', [$ticket, 999999]))
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHas('error');
    }

    public function test_post_approve_url_for_processed_delegation_returns_to_ticket_with_error(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $super = $this->withRole('Super Admin');
        $ticket = $this->ticketFor($bruno->id);
        $delegation = $ticket->delegationRequests()->create([
            'requested_by' => $bruno->id,
            'requested_to' => $rodrigo->id,
            'status' => 'rejected',
        ]);

        $this->actingAs($super)
            ->post(route('admin.tickets.delegations.approve', [$ticket, $delegation]))
            ->assertRedirect(route('admin.tickets.show', $ticket))
            ->assertSessionHas('error');

        $this->assertSame($bruno->id, $ticket->fresh()->assigned_to);
        $this->assertSame('rejected', $delegation->fresh()->status);
    }

    public function test_non_assignee_agent_cannot_request_delegation(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $ticket = $this->ticketFor($bruno->id);

        $this->actingAs($rodrigo)
            ->post(route('admin.tickets.delegations.store', $ticket), ['requested_to' => $bruno->id])
            ->assertForbidden();
    }

    public function test_agent_cannot_approve_a_delegation(): void
    {
        $bruno = $this->agent();
        $rodrigo = $this->agent();
        $ticket = $this->ticketFor($bruno->id);

        $delegation = $ticket->delegationRequests()->create([
            'requested_by' => $bruno->id,
            'requested_to' => $rodrigo->id,
            'status' => 'pending',
        ]);

        $this->actingAs($rodrigo)
            ->post(route('admin.tickets.delegations.approve', [$ticket, $delegation]))
            ->assertForbidden();
    }
}
