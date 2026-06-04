<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Production seed: the founding Super Admin account.
 * Idempotent; credentials come from environment variables, never hardcoded.
 */
class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::updateOrCreate(
            ['email' => env('SUPER_ADMIN_EMAIL', 'admin@osole.com.ar')],
            [
                'name' => env('SUPER_ADMIN_NAME', 'Super Admin'),
                'password' => env('SUPER_ADMIN_PASSWORD', 'change-me-now'),
                'is_agent' => true,
                'is_active' => true,
                'email_verified_at' => now(),
            ],
        );

        if (! $user->hasRole('Super Admin')) {
            $user->assignRole('Super Admin');
        }
    }
}
