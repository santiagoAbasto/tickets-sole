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
use Tests\TestCase;

class PublicSeoMetadataTest extends TestCase
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

    public function test_public_support_page_has_professional_share_metadata(): void
    {
        $this->get(route('public.support.create'))
            ->assertOk()
            ->assertSee('<title>Soporte Osole · '.config('app.name', 'Osole Tickets').'</title>', false)
            ->assertSee('<meta name="description" content="Abrí un ticket de soporte con Osole, adjuntá capturas y seguí cada respuesta desde un chat claro con código de seguimiento.">', false)
            ->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false)
            ->assertSee('<link rel="canonical" href="'.route('public.support.create').'">', false)
            ->assertSee('<meta property="og:site_name" content="Osole Soporte">', false)
            ->assertSee('<meta property="og:logo" content="'.asset('img/logo.svg').'">', false)
            ->assertSee('<meta property="og:image" content="'.asset('favicon/web-app-manifest-512x512.png').'">', false)
            ->assertSee('<meta name="twitter:card" content="summary">', false)
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('"logo":"'.asset('img/logo.svg').'"', false);
    }

    public function test_private_or_internal_pages_are_not_indexed(): void
    {
        $ticket = $this->ticket();

        $this->withSession(['tracked_ticket_id' => $ticket->id])
            ->get(route('public.track.show'))
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<title>Iniciar sesión · '.config('app.name', 'Osole Tickets').'</title>', false)
            ->assertSee('<meta property="og:title" content="Osole Soporte · Mesa de ayuda digital">', false)
            ->assertSee('<meta name="twitter:title" content="Osole Soporte · Mesa de ayuda digital">', false)
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    private function ticket(): Ticket
    {
        $customer = Customer::create([
            'name' => 'Cliente',
            'email' => 'seo@example.com',
            'is_active' => true,
        ]);

        return Ticket::create([
            'code' => 'SEO-1',
            'customer_id' => $customer->id,
            'category_id' => TicketCategory::query()->value('id'),
            'priority_id' => TicketPriority::query()->value('id'),
            'status_id' => TicketStatus::defaultId(),
            'subject' => 'Consulta SEO',
            'description' => 'Detalle',
        ]);
    }
}
