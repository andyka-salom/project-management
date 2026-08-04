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
            // Projects
            'view_project', 'view_any_project',
            // Issues
            'view_issue', 'view_any_issue',
            // Tasks (Tickets) & Comments
            'view_ticket', 'view_any_ticket',
            'view_ticket::comment', 'view_any_ticket::comment', 'create_ticket::comment',
            // General
            'view_notification', 'view_any_notification',
            // Pages & Widgets
            'page_Dashboard'
        ])->get();

        $member->syncPermissions($memberPermissions);

        $this->command->info('member role seeded with ' . $memberPermissions->count() . ' permissions (SDLC aligned).');
    }
}
