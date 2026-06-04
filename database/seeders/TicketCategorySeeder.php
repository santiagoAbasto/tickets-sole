<?php

namespace Database\Seeders;

use App\Models\TicketCategory;
use Illuminate\Database\Seeder;

class TicketCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Soporte técnico', 'slug' => 'soporte-tecnico', 'color' => '#6366f1', 'icon' => 'LifeBuoy', 'sort_order' => 1],
            ['name' => 'Desarrollo web', 'slug' => 'desarrollo-web', 'color' => '#0ea5e9', 'icon' => 'Code2', 'sort_order' => 2],
            ['name' => 'Hosting / Servidor', 'slug' => 'hosting-servidor', 'color' => '#14b8a6', 'icon' => 'Server', 'sort_order' => 3],
            ['name' => 'Correo corporativo', 'slug' => 'correo-corporativo', 'color' => '#f59e0b', 'icon' => 'Mail', 'sort_order' => 4],
            ['name' => 'Diseño / UI', 'slug' => 'diseno-ui', 'color' => '#ec4899', 'icon' => 'Palette', 'sort_order' => 5],
            ['name' => 'Facturación', 'slug' => 'facturacion', 'color' => '#22c55e', 'icon' => 'Receipt', 'sort_order' => 6],
            ['name' => 'Otro', 'slug' => 'otro', 'color' => '#64748b', 'icon' => 'CircleDot', 'sort_order' => 7],
        ];

        foreach ($categories as $category) {
            TicketCategory::updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
