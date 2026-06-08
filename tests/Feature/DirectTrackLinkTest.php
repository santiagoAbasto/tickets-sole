<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Database\Seeders\TicketCategorySeeder;
use Database\Seeders\TicketPrioritySeeder;
use Database\Seeders\TicketStatusSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class DirectTrackLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([
            TicketStatusSeeder::class,
            TicketPrioritySeeder::class,
            TicketCategorySeeder::class,
        ]);
    }

    private static int $seq = 0;

    private function ticket(): Ticket
    {
        $customer = Customer::create(['name' => 'Cliente', 'email' => 'cli'.(++self::$seq).'@example.com', 'is_active' => true]);

        return Ticket::create([
            'code' => 'TK-DL-'.self::$seq,
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::defaultId(),
            'subject' => 'Consulta',
            'description' => 'Detalle',
        ]);
    }

    public function test_signed_direct_link_opens_the_chat_without_code_or_email(): void
    {
        $ticket = $this->ticket();
        $url = URL::signedRoute('public.track.direct', ['ticket' => $ticket->id]);

        $this->get($url)->assertRedirect(route('public.track.show'));

        // The session is now unlocked → the tracking screen renders this ticket.
        $this->get(route('public.track.show'))
            ->assertOk()
            ->assertSee($ticket->code);
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $ticket = $this->ticket();

        $this->get('/seguimiento/t/'.$ticket->id)->assertForbidden();
    }

    public function test_a_tampered_link_pointing_at_another_ticket_is_rejected(): void
    {
        $ticket = $this->ticket();
        $other = $this->ticket();
        $url = URL::signedRoute('public.track.direct', ['ticket' => $ticket->id]);
        // Swap in a different (existing) ticket id → the signature no longer matches.
        $tampered = str_replace('/t/'.$ticket->id, '/t/'.$other->id, $url);

        $this->get($tampered)->assertForbidden();
    }
}
