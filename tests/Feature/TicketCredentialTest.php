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
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TicketCredentialTest extends TestCase
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

    private function staff(string $role = 'Super Admin'): User
    {
        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    private function makeTicket(): Ticket
    {
        $customer = Customer::create(['name' => 'Cli', 'email' => 'cli@x.com', 'is_active' => true]);

        return Ticket::create([
            'code' => 'OSL-CRED-1',
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'subject' => 'S',
            'description' => 'D',
        ]);
    }

    public function test_credentials_are_saved_on_create_and_password_is_encrypted(): void
    {
        $this->actingAs($this->staff())
            ->post(route('admin.tickets.store'), [
                'subject' => 'Web caída',
                'description' => 'No anda',
                'customer_name' => 'Juan',
                'customer_email' => 'juan@x.com',
                'category_id' => TicketCategory::query()->value('id'),
                'priority_id' => TicketPriority::query()->value('id'),
                'cpanel_user' => 'osoleuser',
                'cpanel_password' => 's3cr3t',
                'hosting_type' => 'osole',
                'server_url' => 'https://srv:2083',
            ])
            ->assertRedirect();

        $ticket = Ticket::where('subject', 'Web caída')->firstOrFail();

        $this->assertNotNull($ticket->credentials);
        $this->assertSame('osoleuser', $ticket->credentials->cpanel_user);
        $this->assertSame('s3cr3t', $ticket->credentials->cpanel_password); // decrypted via cast

        // Stored value must not be plaintext.
        $raw = DB::table('ticket_credentials')->where('ticket_id', $ticket->id)->value('cpanel_password');
        $this->assertNotSame('s3cr3t', $raw);
        $this->assertNotEmpty($raw);
    }

    public function test_no_credentials_row_when_none_provided(): void
    {
        $this->actingAs($this->staff())
            ->post(route('admin.tickets.store'), [
                'subject' => 'Sin credenciales',
                'description' => 'Y',
                'customer_name' => 'Juan',
                'customer_email' => 'juan@x.com',
                'category_id' => TicketCategory::query()->value('id'),
                'priority_id' => TicketPriority::query()->value('id'),
            ])
            ->assertRedirect();

        $this->assertDatabaseCount('ticket_credentials', 0);
    }

    public function test_agent_can_upsert_credentials(): void
    {
        $ticket = $this->makeTicket();

        $this->actingAs($this->staff('Agente'))
            ->put(route('admin.tickets.credentials.update', $ticket), [
                'cpanel_user' => 'u',
                'cpanel_password' => 'p',
                'hosting_type' => 'external',
                'hosting_provider' => 'Hostinger',
            ])
            ->assertRedirect();

        $cred = $ticket->credentials()->first();
        $this->assertSame('Hostinger', $cred->hosting_provider);
        $this->assertSame('p', $cred->cpanel_password);
    }

    public function test_customer_cannot_touch_credentials(): void
    {
        $ticket = $this->makeTicket();
        $cliente = User::factory()->create();
        $cliente->assignRole('Cliente');

        $this->actingAs($cliente)
            ->put(route('admin.tickets.credentials.update', $ticket), ['cpanel_user' => 'x'])
            ->assertForbidden();
    }
}
