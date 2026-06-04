<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Soporte', 'slug' => 'soporte', 'color' => '#6366f1', 'sort_order' => 1],
            ['name' => 'Desarrollo', 'slug' => 'desarrollo', 'color' => '#0ea5e9', 'sort_order' => 2],
            ['name' => 'Infraestructura', 'slug' => 'infraestructura', 'color' => '#14b8a6', 'sort_order' => 3],
            ['name' => 'Administración', 'slug' => 'administracion', 'color' => '#f59e0b', 'sort_order' => 4],
        ];

        foreach ($departments as $department) {
            Department::updateOrCreate(['slug' => $department['slug']], $department);
        }
    }
}
