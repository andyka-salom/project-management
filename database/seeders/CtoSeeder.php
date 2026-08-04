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
            // Project Requests
            'view_project::request', 'view_any_project::request', 'update_project::request',
            'approve_project_request',
            // Projects
            'view_project', 'view_any_project',
            'manage_sdlc_phase',
            // Issues
            'view_issue', 'view_any_issue', 
            'decide_issue',
            // Tasks (Tickets) & Comments
            'view_ticket', 'view_any_ticket',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            // General & Organization
            'view_user', 'view_any_user',
            'view_division', 'view_any_division',
            'view_notification', 'view_any_notification',
            // Pages & Widgets
            'page_Dashboard', 'widget_StatsOverview', 'widget_ProjectTimeline', 'page_ProjectTimeline',
            'page_Leaderboard', 'page_UserContributions', 'page_EpicsOverview',
            'widget_MonthlyTicketTrendChart', 'widget_TicketsPerProjectChart'
        ])->get();

        $cto->syncPermissions($ctoPermissions);

        $this->command->info('cto role seeded with ' . $ctoPermissions->count() . ' permissions (SDLC aligned).');
    }
}
