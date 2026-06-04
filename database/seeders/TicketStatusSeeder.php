<?php

namespace Database\Seeders;

use App\Models\TicketStatus;
use Illuminate\Database\Seeder;

class TicketStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'Abierto', 'slug' => 'abierto', 'color' => '#3b82f6', 'is_default' => true,  'is_final' => false, 'is_resolved' => false, 'sort_order' => 1],
            ['name' => 'En proceso', 'slug' => 'en-proceso', 'color' => '#6366f1', 'is_default' => false, 'is_final' => false, 'is_resolved' => false, 'sort_order' => 2],
            ['name' => 'Esperando cliente', 'slug' => 'esperando-cliente', 'color' => '#f59e0b', 'is_default' => false, 'is_final' => false, 'is_resolved' => false, 'sort_order' => 3],
            ['name' => 'Resuelto', 'slug' => 'resuelto', 'color' => '#10b981', 'is_default' => false, 'is_final' => true,  'is_resolved' => true,  'sort_order' => 4],
            ['name' => 'Cerrado', 'slug' => 'cerrado', 'color' => '#64748b', 'is_default' => false, 'is_final' => true,  'is_resolved' => false, 'sort_order' => 5],
            ['name' => 'Cancelado', 'slug' => 'cancelado', 'color' => '#ef4444', 'is_default' => false, 'is_final' => true,  'is_resolved' => false, 'sort_order' => 6],
        ];

        foreach ($statuses as $status) {
            TicketStatus::updateOrCreate(['slug' => $status['slug']], $status);
        }
    }
}
