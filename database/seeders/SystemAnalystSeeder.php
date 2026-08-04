<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SystemAnalystSeeder extends Seeder
{
    public function run(): void
    {
        $systemAnalyst = Role::firstOrCreate(['name' => 'system_analyst']);

        $saPermissions = Permission::whereIn('name', [
            // Project Requests
            'view_project::request', 'view_any_project::request', 'update_project::request',
            'submit_analysis_project_request',
            // Projects
            'view_project', 'view_any_project',
            // Issues
            'view_issue', 'view_any_issue', 
            'act_issue',
            // Tasks (Tickets) & Comments
            'create_ticket', 'view_ticket', 'view_any_ticket', 'update_ticket',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_ticket::priority', 'view_any_ticket::priority',
            // General
            'view_notification', 'view_any_notification',
            // Pages & Widgets
            'page_Dashboard', 'widget_MyTasksWidget', 'page_Schedule', 'page_ProjectBoard', 'page_TicketTimeline'
        ])->get();

        $systemAnalyst->syncPermissions($saPermissions);

        $this->command->info('system_analyst role seeded with ' . $saPermissions->count() . ' permissions (SDLC aligned).');
    }
}
