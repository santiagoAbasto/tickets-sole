<?php

namespace Tests\Feature;

use App\Models\Customer;
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

class AssignedAgentTest extends TestCase
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
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole('Agente');

        return $user;
    }

    private function admin(): User
    {
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole('Admin');

        return $user;
    }

    private static int $seq = 0;

    private function ticketAssignedTo(int $userId): Ticket
    {
        $customer = Customer::create(['name' => 'Cliente', 'email' => 'c'.(++self::$seq).'@x.com', 'is_active' => true]);

        return Ticket::create([
            'code' => 'TK-AA-'.self::$seq,
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::defaultId(),
            'subject' => 'Falla',
            'description' => 'Detalle',
            'assigned_to' => $userId,
        ]);
    }

    public function test_assigned_to_is_cast_to_integer(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketAssignedTo($agent->id);

        $this->assertIsInt($ticket->fresh()->assigned_to);
    }

    public function test_assigned_agent_can_reply_change_status_and_contact_the_customer(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketAssignedTo($agent->id);

        $this->assertTrue($agent->can('reply', $ticket));
        $this->assertTrue($agent->can('changeStatus', $ticket));
        $this->assertTrue($agent->can('notifyCustomer', $ticket));
    }

    public function test_assigned_agent_can_change_the_status_via_route(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketAssignedTo($agent->id);
        $newStatus = TicketStatus::where('id', '!=', $ticket->status_id)->value('id');

        $this->actingAs($agent)
            ->post(route('admin.tickets.status', $ticket), ['status_id' => $newStatus])
            ->assertRedirect();

        $this->assertSame($newStatus, $ticket->fresh()->status_id);
    }

    public function test_admin_can_delete_a_ticket(): void
    {
        $ticket = $this->ticketAssignedTo($this->agent()->id);

        $this->actingAs($this->admin())
            ->delete(route('admin.tickets.destroy', $ticket))
            ->assertRedirect(route('admin.tickets.index'));

        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }

    public function test_agent_cannot_delete_a_ticket(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketAssignedTo($agent->id);

        $this->actingAs($agent)
            ->delete(route('admin.tickets.destroy', $ticket))
            ->assertForbidden();
    }
}
