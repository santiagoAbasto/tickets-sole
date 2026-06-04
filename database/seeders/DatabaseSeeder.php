<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Catalogues & access control (idempotent).
            RolePermissionSeeder::class,
            DepartmentSeeder::class,
            TicketStatusSeeder::class,
            TicketPrioritySeeder::class,
            TicketCategorySeeder::class,

            // Production: the single founding Super Admin (no demo data).
            SuperAdminSeeder::class,
        ]);
    }
}
