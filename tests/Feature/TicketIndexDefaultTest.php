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

class TicketIndexDefaultTest extends TestCase
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

    private function ticket(string $code, ?int $assignedTo): Ticket
    {
        $customer = Customer::create(['name' => 'C'.$code, 'email' => $code.'@x.com', 'is_active' => true]);

        return Ticket::create([
            'code' => $code,
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'assigned_to' => $assignedTo,
            'subject' => 'Asunto '.$code,
            'description' => 'D',
        ]);
    }

    public function test_index_defaults_to_my_tickets_and_all_flag_shows_everything(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $mine = $this->ticket('OSL-MINE-1', $admin->id);
        $other = $this->ticket('OSL-OTHER-1', null);

        // Default landing → only my assigned tickets.
        $this->actingAs($admin)
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee($mine->code)
            ->assertDontSee($other->code);

        // Explicit "Todos" → everything.
        $this->actingAs($admin)
            ->get(route('admin.tickets.index', ['flag' => 'all']))
            ->assertOk()
            ->assertSee($mine->code)
            ->assertSee($other->code);
    }

    public function test_index_shows_last_status_change_column(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $ticket = $this->ticket('OSL-STATUS-1', $admin->id);
        $ticket->activityLogs()->create([
            'user_id' => $admin->id,
            'action' => 'status_changed',
            'description' => 'Estado: Abierto → Resuelto',
            'meta' => ['from' => 'Abierto', 'to' => 'Resuelto'],
            'created_at' => now()->subHours(3),
            'updated_at' => now()->subHours(3),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.tickets.index'))
            ->assertOk()
            ->assertSee('Último cambio de estado')
            ->assertSee('a Resuelto');
    }
}
