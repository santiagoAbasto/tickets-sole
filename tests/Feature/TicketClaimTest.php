<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketActivityLog;
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

class TicketClaimTest extends TestCase
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

    private static int $seq = 0;

    private function ticketAssignedTo(?int $userId): Ticket
    {
        $customer = Customer::create(['name' => 'Cliente', 'email' => 'c'.(++self::$seq).'@x.com', 'is_active' => true]);

        return Ticket::create([
            'code' => 'TK-CLAIM-'.self::$seq,
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'subject' => 'Falla',
            'description' => 'Detalle',
            'assigned_to' => $userId,
        ]);
    }

    public function test_agent_can_claim_a_ticket_assigned_to_someone_else(): void
    {
        $owner = $this->agent();
        $taker = $this->agent();
        $ticket = $this->ticketAssignedTo($owner->id);

        $this->actingAs($taker)
            ->post(route('admin.tickets.claim', $ticket))
            ->assertRedirect();

        $this->assertSame($taker->id, $ticket->refresh()->assigned_to);
    }

    public function test_after_claiming_the_agent_can_reply(): void
    {
        $owner = $this->agent();
        $taker = $this->agent();
        $ticket = $this->ticketAssignedTo($owner->id);

        $this->assertFalse($taker->can('reply', $ticket));

        $this->actingAs($taker)->post(route('admin.tickets.claim', $ticket))->assertRedirect();

        $this->assertTrue($taker->can('reply', $ticket->refresh()));
    }

    public function test_claiming_pulls_the_ticket_away_from_the_previous_assignee(): void
    {
        $owner = $this->agent();
        $taker = $this->agent();
        $ticket = $this->ticketAssignedTo($owner->id);

        $this->actingAs($taker)->post(route('admin.tickets.claim', $ticket))->assertRedirect();
        $ticket->refresh();

        // The old owner can no longer act, but can take it back if needed.
        $this->assertFalse($owner->can('reply', $ticket));
        $this->assertTrue($owner->can('claim', $ticket));
    }

    public function test_claim_is_recorded_in_the_activity_log(): void
    {
        $owner = $this->agent();
        $taker = $this->agent();
        $ticket = $this->ticketAssignedTo($owner->id);

        $this->actingAs($taker)->post(route('admin.tickets.claim', $ticket))->assertRedirect();

        $this->assertTrue(
            TicketActivityLog::where('ticket_id', $ticket->id)
                ->where('action', 'claimed')
                ->where('user_id', $taker->id)
                ->exists()
        );
    }

    public function test_agent_cannot_claim_a_ticket_already_theirs(): void
    {
        $agent = $this->agent();
        $ticket = $this->ticketAssignedTo($agent->id);

        $this->actingAs($agent)
            ->post(route('admin.tickets.claim', $ticket))
            ->assertForbidden();
    }

    public function test_designer_cannot_claim(): void
    {
        $designer = User::factory()->create();
        $designer->assignRole('Diseñadora industrial');
        $ticket = $this->ticketAssignedTo($this->agent()->id);

        $this->actingAs($designer)
            ->post(route('admin.tickets.claim', $ticket))
            ->assertForbidden();
    }
}
