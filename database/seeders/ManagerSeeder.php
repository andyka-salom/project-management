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
            // Project Requests
            'create_project::request', 'view_project::request', 'view_any_project::request', 'update_project::request',
            'assign_analyst_project_request', 'recommend_project_request',
            // Projects
            'create_project', 'view_project', 'view_any_project', 'update_project',
            // Issues
            'create_issue', 'view_issue', 'view_any_issue', 'update_issue', 'delete_issue',
            'verify_issue',
            // Tasks (Tickets), Comments & Priorities
            'create_ticket', 'view_ticket', 'view_any_ticket', 'update_ticket', 'delete_ticket',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_ticket::priority', 'view_any_ticket::priority',
            // General & Organization
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_division', 'view_any_division',
            // Pages & Widgets
            'page_Dashboard', 'page_ProjectBoard', 'page_ProjectTimeline', 'page_TicketTimeline',
            'page_Schedule', 'page_EpicsOverview', 'page_Leaderboard', 'page_UserContributions',
            'widget_StatsOverview', 'widget_TicketsPerProjectChart', 'widget_ApprovalQueueWidget'
        ])->get();

        $manager->syncPermissions($managerPermissions);

        $this->command->info('manager role seeded with ' . $managerPermissions->count() . ' permissions (SDLC aligned).');
    }
}
