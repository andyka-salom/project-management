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
            'view_ticket_priority', 'view_any_ticket_priority',
            'view_ticket_comment', 'view_any_ticket_comment', 'create_ticket_comment',
            'view_notification', 'view_any_notification',
        ])->get();

        $programmer->syncPermissions($programmerPermissions);

        $this->command->info('programmer role seeded with ' . $programmerPermissions->count() . ' permissions.');
    }
}
