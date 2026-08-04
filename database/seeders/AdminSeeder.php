<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);

        // admin: all permissions except delete_user and force_delete related
        $adminPermissions = Permission::whereNotIn('name', [
            'delete_user', 
            'force_delete_project', 'force_delete_any_project',
            'force_delete_issue', 'force_delete_any_issue',
            'force_delete_ticket', 'force_delete_any_ticket',
            'force_delete_division', 'force_delete_any_division'
        ])->get();
        
        $admin->syncPermissions($adminPermissions);

        $this->command->info('admin role seeded with ' . $adminPermissions->count() . ' permissions.');
    }
}
