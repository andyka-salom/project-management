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

        // admin mendapatkan semua permission KECUALI delete_user
        $adminPermissions = Permission::whereNotIn('name', [
            'delete_user',
        ])->get();

        $admin->syncPermissions($adminPermissions);

        $this->command->info('admin role seeded with ' . $adminPermissions->count() . ' permissions (excluding delete_user).');
    }
}
