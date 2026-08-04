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
            'view_ticket::priority', 'view_any_ticket::priority',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_notification', 'view_any_notification',
            'view_user', 'view_any_user',
            'view_division', 'view_any_division',
            'view_project::request', 'view_any_project::request', 'update_project::request',
            'approve_project_request',
            'manage_sdlc_phase',
            'view_issue', 'view_any_issue', 'decide_issue',
            'page_Dashboard', 'widget_StatsOverview', 'widget_ProjectTimeline', 'page_ProjectTimeline', 'page_Leaderboard'
        ])->get();

        $cto->syncPermissions($ctoPermissions);

        $this->command->info('cto role seeded with ' . $ctoPermissions->count() . ' permissions.');
    }
}
