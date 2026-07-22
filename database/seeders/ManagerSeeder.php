<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ManagerSeeder extends Seeder
{
    public function run(): void
    {
        $manager = Role::firstOrCreate(['name' => 'manager']);

        $managerPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project', 'update_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_project_request', 'view_any_project_request',
            'create_project_request', 'update_project_request',
            'assign_analyst_project_request',
            'recommend_project_request',
            'view_issue', 'view_any_issue', 'create_issue', 'update_issue', 'delete_issue', 'verify_issue',
        ])->get();

        $manager->syncPermissions($managerPermissions);

        $this->command->info('manager role seeded with ' . $managerPermissions->count() . ' permissions.');
    }
}
