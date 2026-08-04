<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class MemberSeeder extends Seeder
{
    public function run(): void
    {
        $member = Role::firstOrCreate(['name' => 'member']);

        $memberPermissions = Permission::whereIn('name', [
            'view_project', 'view_any_project',
            'view_ticket', 'view_any_ticket', 'update_ticket',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            'view_notification', 'view_any_notification',
            'view_issue', 'view_any_issue', 'act_issue',
            'page_Dashboard', 'widget_MyTasksWidget'
        ])->get();

        $member->syncPermissions($memberPermissions);

        $this->command->info('member role seeded with ' . $memberPermissions->count() . ' permissions.');
    }
}
