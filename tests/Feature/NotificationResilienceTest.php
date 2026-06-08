<?php

namespace Tests\Feature;

use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

class NotificationResilienceTest extends TestCase
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

        // Make every mail send blow up, like a broken SMTP server (550 / auth / down).
        Mail::extend('exploding', fn () => new class implements TransportInterface
        {
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                throw new TransportException('550 No Such User Here');
            }

            public function __toString(): string
            {
                return 'exploding';
            }
        });
        config([
            'mail.default' => 'exploding',
            'mail.mailers.exploding' => ['transport' => 'exploding'],
        ]);
    }

    private function admin(): User
    {
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole('Admin');

        return $user;
    }

    private function createTicket(User $actor, string $subject): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($actor)->post(route('admin.tickets.store'), [
            'subject' => $subject,
            'description' => 'descripcion',
            'customer_name' => 'Cliente',
            'customer_email' => 'cliente@example.com',
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
        ]);
    }

    public function test_creating_a_ticket_survives_a_broken_mailer(): void
    {
        $this->createTicket($this->admin(), 'Pese al mail roto')
            ->assertRedirect(); // not a 500

        $this->assertDatabaseHas('tickets', ['subject' => 'Pese al mail roto']);
    }

    public function test_in_app_notification_still_lands_when_mail_fails(): void
    {
        $admin = $this->admin();

        $this->createTicket($admin, 'Campanita igual llega')->assertRedirect();

        // The database channel runs before mail in via(), so the bell survives.
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $admin->id]);
    }
}
