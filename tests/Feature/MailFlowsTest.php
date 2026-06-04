<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Notifications\CustomerTicketCreatedNotification;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MailFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_emails_a_reset_link(): void
    {
        Notification::fake();
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['email' => 'pablo@osole.com.ar']);

        $this->post(route('password.email'), ['email' => 'pablo@osole.com.ar'])
            ->assertSessionHasNoErrors();

        Notification::assertSentTo(
            $user,
            ResetPassword::class,
            fn ($notification, $channels) => in_array('mail', $channels),
        );
    }

    public function test_avisar_emails_the_customer(): void
    {
        Notification::fake();
        $this->seed([
            RolePermissionSeeder::class,
            TicketStatusSeeder::class,
            TicketPrioritySeeder::class,
            TicketCategorySeeder::class,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('Super Admin');

        $customer = Customer::create(['name' => 'Cliente', 'email' => 'cliente@example.com', 'is_active' => true]);
        $ticket = Ticket::create([
            'code' => 'OSL-MAIL-1',
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'subject' => 'S',
            'description' => 'D',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.tickets.notify-customer', $ticket))
            ->assertRedirect();

        // "Avisar" sends an on-demand mail notification to the customer's email.
        Notification::assertSentOnDemand(
            CustomerTicketCreatedNotification::class,
            fn ($notification, $channels, $notifiable) => in_array('mail', $channels)
                && ($notifiable->routes['mail'] ?? null) === 'cliente@example.com',
        );
    }
}
