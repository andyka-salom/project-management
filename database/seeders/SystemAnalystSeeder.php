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
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket',
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
            'view_project_request', 'view_any_project_request', 'update_project_request',
            'submit_analysis_project_request',
            'view_issue', 'view_any_issue', 'act_issue',
        ])->get();

        $systemAnalyst->syncPermissions($saPermissions);

        $this->command->info('system_analyst role seeded with ' . $saPermissions->count() . ' permissions.');
    }
}
