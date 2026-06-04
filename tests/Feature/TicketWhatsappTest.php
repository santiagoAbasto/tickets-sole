<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use App\Services\WhatsappTemplateService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketWhatsappTest extends TestCase
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

    private function makeTicket(?string $phone = null, string $email = 'juan@acme.com', ?int $assignedTo = null): Ticket
    {
        $customer = Customer::create([
            'name' => 'Juan Pérez',
            'email' => $email,
            'phone' => $phone,
            'is_active' => true,
        ]);

        return Ticket::create([
            'code' => 'OSL-TEST-1',
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::query()->value('id'),
            'assigned_to' => $assignedTo,
            'subject' => 'Problema de prueba',
            'description' => 'Descripción de prueba',
        ]);
    }

    private function agent(): User
    {
        $agent = User::factory()->create();
        $agent->assignRole('Agente');

        return $agent;
    }

    public function test_agent_can_save_a_whatsapp_number(): void
    {
        $agent = $this->agent();
        $ticket = $this->makeTicket(assignedTo: $agent->id);

        $this->actingAs($agent)
            ->putJson(route('admin.tickets.whatsapp.number', $ticket), ['phone' => '11 1234-5678'])
            ->assertOk()
            ->assertJson([
                'phone' => '11 1234-5678',
                'phone_normalized' => '5491112345678',
                'wa_base' => 'https://wa.me/5491112345678',
            ]);

        $this->assertSame('11 1234-5678', $ticket->customer->fresh()->phone);
    }

    public function test_invalid_number_is_rejected(): void
    {
        $agent = $this->agent();
        $ticket = $this->makeTicket(assignedTo: $agent->id);

        $this->actingAs($agent)
            ->putJson(route('admin.tickets.whatsapp.number', $ticket), ['phone' => 'no-es-numero'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_update_number_when_customer_is_missing_returns_422(): void
    {
        $agent = $this->agent();
        $ticket = $this->makeTicket(assignedTo: $agent->id);
        $ticket->customer->delete(); // soft-deleted → relationship resolves to null

        $this->actingAs($agent)
            ->putJson(route('admin.tickets.whatsapp.number', $ticket), ['phone' => '11 1234-5678'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_logging_records_activity_and_optional_note(): void
    {
        $agent = $this->agent();
        $ticket = $this->makeTicket('11 1234-5678', assignedTo: $agent->id);

        $this->actingAs($agent)
            ->postJson(route('admin.tickets.whatsapp.log', $ticket), [
                'body' => 'Hola Juan, te paso tu código OSL-TEST-1.',
                'template_key' => 'identificacion',
                'save_note' => true,
            ])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('ticket_activity_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'whatsapp_contacted',
            'user_id' => $agent->id,
        ]);

        $this->assertDatabaseHas('ticket_notes', [
            'ticket_id' => $ticket->id,
            'user_id' => $agent->id,
            'channel' => 'whatsapp',
            'body' => 'Hola Juan, te paso tu código OSL-TEST-1.',
        ]);
    }

    public function test_logging_without_save_note_creates_no_note(): void
    {
        $agent = $this->agent();
        $ticket = $this->makeTicket('11 1234-5678', assignedTo: $agent->id);

        $this->actingAs($agent)
            ->postJson(route('admin.tickets.whatsapp.log', $ticket), [
                'body' => 'Mensaje sin copia.',
                'save_note' => false,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('ticket_notes', ['ticket_id' => $ticket->id]);
        $this->assertDatabaseHas('ticket_activity_logs', [
            'ticket_id' => $ticket->id,
            'action' => 'whatsapp_contacted',
        ]);
    }

    public function test_customer_user_cannot_contact_via_whatsapp(): void
    {
        $ticket = $this->makeTicket('11 1234-5678');

        $cliente = User::factory()->create();
        $cliente->assignRole('Cliente');

        $this->actingAs($cliente)
            ->postJson(route('admin.tickets.whatsapp.log', $ticket), ['body' => 'x'])
            ->assertForbidden();
    }

    public function test_template_service_resolves_variables(): void
    {
        $ticket = $this->makeTicket('11 1234-5678');
        $agent = $this->agent();

        $payload = app(WhatsappTemplateService::class)->resolve($ticket, $agent);

        $this->assertTrue($payload['has_phone']);
        $this->assertSame('https://wa.me/5491112345678', $payload['wa_base']);

        $identificacion = collect($payload['templates'])->firstWhere('key', 'identificacion');
        $this->assertStringContainsString('OSL-TEST-1', $identificacion['text']);
        $this->assertStringContainsString('juan@acme.com', $identificacion['text']);
        $this->assertStringContainsString('Juan', $identificacion['text']);
    }

    public function test_template_email_falls_back_when_missing(): void
    {
        $ticket = $this->makeTicket('11 1234-5678', email: '');

        $payload = app(WhatsappTemplateService::class)->resolve($ticket, $this->agent());
        $identificacion = collect($payload['templates'])->firstWhere('key', 'identificacion');

        $this->assertStringContainsString('(sin email registrado)', $identificacion['text']);
    }
}
