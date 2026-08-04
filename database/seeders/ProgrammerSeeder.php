<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProgrammerSeeder extends Seeder
{
    public function run(): void
    {
        $programmer = Role::firstOrCreate(['name' => 'programmer']);

        $programmerPermissions = Permission::whereIn('name', [
            // Projects
            'view_project', 'view_any_project',
            // Issues
            'view_issue', 'view_any_issue', 
            'act_issue',
            // Tasks (Tickets) & Comments
            'view_ticket', 'view_any_ticket', 'update_ticket', // NO create_ticket
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_ticket::priority', 'view_any_ticket::priority',
            // General
            'view_notification', 'view_any_notification',
            // Pages & Widgets
            'page_Dashboard', 'widget_MyTasksWidget', 'page_ProjectBoard', 'page_Schedule',
            'page_TicketTimeline', 'page_Leaderboard', 'page_UserContributions'
        ])->get();

        $programmer->syncPermissions($programmerPermissions);

        $this->command->info('programmer role seeded with ' . $programmerPermissions->count() . ' permissions (SDLC aligned).');
    }
}
