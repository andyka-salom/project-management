<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CtoSeeder extends Seeder
{
    public function run(): void
    {
        $cto = Role::firstOrCreate(['name' => 'cto']);

        $ctoPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project', 'create_project', 'update_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket', 'delete_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_project_request', 'view_any_project_request', 'update_project_request',
            'approve_project_request',
            'manage_sdlc_phase',
        ])->get();

        $cto->syncPermissions($ctoPermissions);

        $this->command->info('cto role seeded with ' . $ctoPermissions->count() . ' permissions.');
    }
}
