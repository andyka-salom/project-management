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
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'create_ticket', 'update_ticket',
            'view_ticket::priority', 'view_any_ticket::priority',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_notification', 'view_any_notification',
            'view_issue', 'view_any_issue', 'act_issue',
            'page_Dashboard', 'widget_MyTasksWidget', 'page_ProjectBoard', 'page_Schedule'
        ])->get();

        $programmer->syncPermissions($programmerPermissions);

        $this->command->info('programmer role seeded with ' . $programmerPermissions->count() . ' permissions.');
    }
}
