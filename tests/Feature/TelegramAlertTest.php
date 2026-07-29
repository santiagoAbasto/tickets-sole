<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\SiteSetting;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\TelegramNotifier;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramAlertTest extends TestCase
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

    private function enableAlerts(string $token, array $recipients): void
    {
        SiteSetting::setMany([
            'telegram_alerts_enabled' => '1',
            'telegram_bot_token' => $token,
            'telegram_alerts_recipients' => json_encode($recipients),
        ]);
    }

    private function makeTicket(): Ticket
    {
        $customer = Customer::create(['name' => 'Cliente Z', 'email' => 'z@x.com', 'is_active' => true]);

        return Ticket::create([
            'code' => 'TK-TEST-1',
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'subject' => 'Problema X',
            'description' => 'Detalle',
        ]);
    }

    private function staff(string $role): User
    {
        $user = User::factory()->create(['is_agent' => true]);
        $user->assignRole($role);

        return $user;
    }

    public function test_sends_one_message_per_recipient_when_enabled(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $this->enableAlerts('TESTTOKEN', [
            ['name' => 'Pablo', 'chat_id' => '111'],
            ['name' => 'Ale', 'chat_id' => '222'],
        ]);

        app(TelegramNotifier::class)->ticketCreated($this->makeTicket());

        Http::assertSentCount(2);
        Http::assertSent(fn ($r) => str_contains($r->url(), '/botTESTTOKEN/sendMessage')
            && $r['chat_id'] === '111'
            && str_contains($r['text'], 'TK-TEST-1'));
        Http::assertSent(fn ($r) => $r['chat_id'] === '222');
    }

    public function test_does_not_send_when_disabled(): void
    {
        Http::fake();
        SiteSetting::setMany([
            'telegram_alerts_enabled' => '0',
            'telegram_bot_token' => 'TESTTOKEN',
            'telegram_alerts_recipients' => json_encode([['name' => 'P', 'chat_id' => '111']]),
        ]);

        app(TelegramNotifier::class)->ticketCreated($this->makeTicket());

        Http::assertNothingSent();
    }

    public function test_does_not_send_without_a_token(): void
    {
        Http::fake();
        $this->enableAlerts('', [['name' => 'P', 'chat_id' => '111']]);

        app(TelegramNotifier::class)->ticketCreated($this->makeTicket());

        Http::assertNothingSent();
    }

    public function test_does_not_send_without_recipients(): void
    {
        Http::fake();
        $this->enableAlerts('TESTTOKEN', []);

        app(TelegramNotifier::class)->ticketCreated($this->makeTicket());

        Http::assertNothingSent();
    }

    public function test_send_never_throws_on_failure(): void
    {
        Http::fake(fn () => throw new \RuntimeException('network down'));

        $ok = app(TelegramNotifier::class)->send('TESTTOKEN', '111', 'hola');

        $this->assertFalse($ok);
    }

    public function test_detect_chats_parses_get_updates(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response([
            'ok' => true,
            'result' => [
                ['message' => ['chat' => ['id' => 555, 'first_name' => 'Pablo', 'last_name' => 'C']]],
                ['message' => ['chat' => ['id' => 555, 'first_name' => 'Pablo']]], // dup → collapsed
                ['message' => ['chat' => ['id' => 777, 'title' => 'Equipo Osole']]],
            ],
        ], 200)]);

        $chats = app(TelegramNotifier::class)->detectChats('TESTTOKEN');

        // Duplicate chat id collapses to one entry (last occurrence wins).
        $this->assertCount(2, $chats);
        $this->assertSame('555', $chats[0]['id']);
        $this->assertSame('Pablo', $chats[0]['name']);
        $this->assertSame('Equipo Osole', $chats[1]['name']);
    }

    public function test_settings_page_is_forbidden_for_agents(): void
    {
        $this->actingAs($this->staff('Agente'))
            ->get(route('admin.telegram-alerts.edit'))
            ->assertForbidden();
    }

    public function test_admin_can_save_and_incomplete_rows_are_dropped(): void
    {
        $this->actingAs($this->staff('Admin'))
            ->put(route('admin.telegram-alerts.update'), [
                'enabled' => '1',
                'token' => '123:ABC',
                'recipients' => [
                    ['name' => 'Pablo', 'chat_id' => '111'],
                    ['name' => 'Sin id', 'chat_id' => ''],
                ],
            ])
            ->assertRedirect();

        $notifier = app(TelegramNotifier::class);
        $this->assertTrue($notifier->enabled());
        $this->assertSame('123:ABC', $notifier->token());
        $this->assertCount(1, $notifier->recipients());
        $this->assertSame('111', $notifier->recipients()[0]['chat_id']);
    }

    public function test_creating_a_ticket_still_works_with_alerts_enabled(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $this->enableAlerts('TESTTOKEN', [['name' => 'Pablo', 'chat_id' => '111']]);

        $this->actingAs($this->staff('Admin'))
            ->post(route('admin.tickets.store'), [
                'subject' => 'Nuevo con aviso',
                'description' => 'desc',
                'customer_name' => 'C',
                'customer_email' => 'c@x.com',
                'category_id' => TicketCategory::query()->value('id'),
                'priority_id' => TicketPriority::query()->value('id'),
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tickets', ['subject' => 'Nuevo con aviso']);
    }

    public function test_sends_telegram_alert_when_ticket_is_resolved(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $this->enableAlerts('TESTTOKEN', [['name' => 'Pablo', 'chat_id' => '111']]);

        $ticket = $this->makeTicket();
        $resolved = TicketStatus::where('slug', 'resuelto')->firstOrFail();

        $this->actingAs($this->staff('Admin'))
            ->post(route('admin.tickets.status', $ticket), ['status_id' => $resolved->id])
            ->assertRedirect();

        Http::assertSent(fn ($r) => str_contains($r->url(), '/botTESTTOKEN/sendMessage')
            && $r['chat_id'] === '111'
            && str_contains($r['text'], "Ticket Resuelto {$ticket->code}"));
    }

    public function test_sends_telegram_alert_when_ticket_is_closed(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $this->enableAlerts('TESTTOKEN', [['name' => 'Pablo', 'chat_id' => '111']]);

        $ticket = $this->makeTicket();
        $closed = TicketStatus::where('slug', 'cerrado')->firstOrFail();

        $this->actingAs($this->staff('Admin'))
            ->post(route('admin.tickets.status', $ticket), ['status_id' => $closed->id])
            ->assertRedirect();

        Http::assertSent(fn ($r) => $r['chat_id'] === '111'
            && str_contains($r['text'], "Ticket Cerrado {$ticket->code}"));
    }

    public function test_does_not_send_telegram_alert_for_intermediate_status_change(): void
    {
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true], 200)]);
        $this->enableAlerts('TESTTOKEN', [['name' => 'Pablo', 'chat_id' => '111']]);

        $ticket = $this->makeTicket();
        $inProcess = TicketStatus::where('slug', 'en-proceso')->firstOrFail();

        $this->actingAs($this->staff('Admin'))
            ->post(route('admin.tickets.status', $ticket), ['status_id' => $inProcess->id])
            ->assertRedirect();

        Http::assertNothingSent();
    }
}
