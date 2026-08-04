<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class QaSeeder extends Seeder
{
    public function run(): void
    {
        $qa = Role::firstOrCreate(['name' => 'qa']);

        $qaPermissions = Permission::whereIn('name', [
            // Projects
            'view_project', 'view_any_project',
            // Issues
            'create_issue', 'view_issue', 'view_any_issue', 
            'act_issue',
            // Tasks (Tickets) & Comments
            'create_ticket', 'view_ticket', 'view_any_ticket', 'update_ticket',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_ticket::priority', 'view_any_ticket::priority',
            // General
            'view_notification', 'view_any_notification',
            // Pages & Widgets
            'page_Dashboard', 'widget_MyTasksWidget', 'page_ProjectBoard', 'page_Schedule',
            'page_TicketTimeline', 'page_Leaderboard', 'page_UserContributions'
        ])->get();

        $qa->syncPermissions($qaPermissions);

        $this->command->info('qa role seeded with ' . $qaPermissions->count() . ' permissions (SDLC aligned).');
    }
}
