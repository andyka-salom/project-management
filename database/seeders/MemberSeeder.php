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

        // member hanya mendapatkan permission view/view_any untuk semua resource
        // ditambah update_ticket untuk keperluan drag & drop kanban
        $memberPermissions = Permission::whereIn('name', [
            // Project
            'view_project',
            'view_any_project',

            // Ticket
            'view_ticket',
            'view_any_ticket',
            'update_ticket',         // untuk drag & drop status kanban

            // Ticket Priority
            'view_ticket_priority',
            'view_any_ticket_priority',

            // Ticket Comment
            'view_ticket_comment',
            'view_any_ticket_comment',
            'create_ticket_comment', // member boleh berkomentar

            // Notification
            'view_notification',
            'view_any_notification',
        ])->get();

        $member->syncPermissions($memberPermissions);

        $this->command->info('member role seeded with ' . $memberPermissions->count() . ' permissions.');
    }
}
