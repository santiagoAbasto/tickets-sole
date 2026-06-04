<?php

namespace Database\Seeders;

use App\Models\TicketPriority;
use Illuminate\Database\Seeder;

class TicketPrioritySeeder extends Seeder
{
    public function run(): void
    {
        $priorities = [
            ['name' => 'Baja', 'slug' => 'baja', 'color' => '#3b82f6', 'level' => 1, 'response_hours' => 24, 'resolution_hours' => 48, 'sort_order' => 1],
            ['name' => 'Media', 'slug' => 'media', 'color' => '#f59e0b', 'level' => 2, 'response_hours' => 8,  'resolution_hours' => 24, 'sort_order' => 2],
            ['name' => 'Alta', 'slug' => 'alta', 'color' => '#f97316', 'level' => 3, 'response_hours' => 4,  'resolution_hours' => 8,  'sort_order' => 3],
            ['name' => 'Urgente', 'slug' => 'urgente', 'color' => '#dc2626', 'level' => 4, 'response_hours' => 1,  'resolution_hours' => 2,  'sort_order' => 4],
        ];

        foreach ($priorities as $priority) {
            TicketPriority::updateOrCreate(['slug' => $priority['slug']], $priority);
        }
    }
}
