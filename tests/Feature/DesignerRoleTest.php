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

class DesignerRoleTest extends TestCase
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

    private function designer(): User
    {
        $user = User::factory()->create();
        $user->assignRole('Diseñadora industrial');

        return $user;
    }

    public function test_designer_is_staff_but_not_an_assignable_agent(): void
    {
        $designer = $this->designer();

        $this->assertTrue($designer->isStaff());
        $this->assertFalse((bool) $designer->is_agent);
    }

    public function test_designer_can_create_a_ticket_and_is_recorded_as_creator(): void
    {
        $designer = $this->designer();

        $this->actingAs($designer)
            ->post(route('admin.tickets.store'), [
                'subject' => 'Queja por WhatsApp',
                'description' => 'El cliente escribió al número',
                'customer_name' => 'Cliente X',
                'customer_email' => 'clientex@example.com',
                'category_id' => TicketCategory::query()->value('id'),
                'priority_id' => TicketPriority::query()->value('id'),
            ])
            ->assertRedirect();

        $ticket = Ticket::where('subject', 'Queja por WhatsApp')->firstOrFail();

        $this->assertSame($designer->id, $ticket->created_by);
        $this->assertSame('admin', $ticket->source); // marked "Interno"
        $this->assertTrue($designer->can('view', $ticket));
    }

    public function test_designer_cannot_reply_or_notify_the_customer(): void
    {
        $designer = $this->designer();
        $customer = Customer::create(['name' => 'C', 'email' => 'c@x.com', 'is_active' => true]);
        $ticket = Ticket::create([
            'code' => 'OSL-DES-1',
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'subject' => 'S',
            'description' => 'D',
        ]);

        $this->assertFalse($designer->can('reply', $ticket));
        $this->assertFalse($designer->can('notifyCustomer', $ticket));
    }
}
