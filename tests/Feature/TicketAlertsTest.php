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

class TicketAlertsTest extends TestCase
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

    private function superAdmin(): User
    {
        $u = User::factory()->create();
        $u->assignRole('Super Admin');

        return $u;
    }

    private function ticket(string $code, ?int $createdBy = null): Ticket
    {
        $c = Customer::create(['name' => 'C'.$code, 'email' => $code.'@x.com', 'is_active' => true]);

        return Ticket::create([
            'code' => $code,
            'customer_id' => $c->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'created_by' => $createdBy,
            'source' => 'web',
            'subject' => 'S '.$code,
            'description' => 'D',
        ]);
    }

    public function test_super_admin_gets_new_tickets_after_a_given_id(): void
    {
        $a = $this->ticket('OSL-A');
        $b = $this->ticket('OSL-B');

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.notifications.ticket-alerts', ['after' => $a->id]))
            ->assertOk()
            ->assertJsonPath('latest_id', $b->id)
            ->assertJsonCount(1, 'tickets')
            ->assertJsonPath('tickets.0.code', 'OSL-B');
    }

    public function test_baseline_call_returns_latest_id_without_listing_tickets(): void
    {
        $a = $this->ticket('OSL-A');

        $this->actingAs($this->superAdmin())
            ->getJson(route('admin.notifications.ticket-alerts'))
            ->assertOk()
            ->assertJsonPath('latest_id', $a->id)
            ->assertJsonCount(0, 'tickets');
    }

    public function test_excludes_tickets_the_admin_created_themselves(): void
    {
        $admin = $this->superAdmin();
        $a = $this->ticket('OSL-A');
        $b = $this->ticket('OSL-B', $admin->id); // created by the admin

        $this->actingAs($admin)
            ->getJson(route('admin.notifications.ticket-alerts', ['after' => $a->id]))
            ->assertOk()
            ->assertJsonCount(0, 'tickets');
    }

    public function test_non_super_admin_is_forbidden(): void
    {
        $agent = User::factory()->create();
        $agent->assignRole('Agente');

        $this->actingAs($agent)
            ->getJson(route('admin.notifications.ticket-alerts'))
            ->assertForbidden();
    }
}
