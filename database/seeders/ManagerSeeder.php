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
            'view_ticket::priority', 'view_any_ticket::priority',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_division', 'view_any_division',
            'view_project::request', 'view_any_project::request',
            'create_project::request', 'update_project::request',
            'assign_analyst_project_request',
            'recommend_project_request',
            'view_issue', 'view_any_issue', 'create_issue', 'update_issue', 'delete_issue', 'verify_issue',
            'page_Dashboard', 'page_ProjectBoard', 'page_ProjectTimeline', 'widget_StatsOverview', 'widget_TicketsPerProjectChart', 'page_Schedule'
        ])->get();

        $manager->syncPermissions($managerPermissions);

        $this->command->info('manager role seeded with ' . $managerPermissions->count() . ' permissions.');
    }
}
