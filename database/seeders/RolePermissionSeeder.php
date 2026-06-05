<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Full permission catalogue. Grouped by concern for readability.
     */
    public const PERMISSIONS = [
        // Tickets
        'tickets.viewAny',
        'tickets.view',
        'tickets.create',
        'tickets.update',
        'tickets.delete',
        'tickets.assign',
        'tickets.changeStatus',
        'tickets.changePriority',
        'tickets.reply',
        'tickets.note',        // internal notes (staff only)
        'tickets.attach',
        // People
        'agents.manage',
        'customers.manage',
        'admins.manage',       // create/manage admin accounts (Super Admin)
        // Configuration & insight
        'settings.manage',     // categories / priorities / statuses
        'reports.view',
        'audit.view',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Super Admin — gets everything (also covered by Gate::before, but
        // assigning all keeps the role self-describing).
        $superAdmin = Role::findOrCreate('Super Admin', 'web');
        $superAdmin->syncPermissions(self::PERMISSIONS);

        // Admin — full operational control, minus admin account management & audit.
        $admin = Role::findOrCreate('Admin', 'web');
        $admin->syncPermissions([
            'tickets.viewAny', 'tickets.view', 'tickets.create', 'tickets.update', 'tickets.delete',
            'tickets.assign', 'tickets.changeStatus', 'tickets.changePriority',
            'tickets.reply', 'tickets.note', 'tickets.attach',
            'agents.manage', 'customers.manage', 'settings.manage', 'reports.view',
        ]);

        // Agente — works the tickets assigned to them / their department.
        $agent = Role::findOrCreate('Agente', 'web');
        $agent->syncPermissions([
            'tickets.viewAny', 'tickets.view', 'tickets.create',
            'tickets.changeStatus', 'tickets.reply', 'tickets.note', 'tickets.attach',
        ]);

        // Diseñadora industrial — loads tickets from complaints received by WhatsApp.
        // Can create and follow tickets (and attach), but does not work/answer them.
        $designer = Role::findOrCreate('Diseñadora industrial', 'web');
        $designer->syncPermissions([
            'tickets.viewAny', 'tickets.view', 'tickets.create', 'tickets.attach',
        ]);

        // Cliente — customer portal: own tickets only.
        $cliente = Role::findOrCreate('Cliente', 'web');
        $cliente->syncPermissions([
            'tickets.view', 'tickets.create', 'tickets.reply', 'tickets.attach',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
