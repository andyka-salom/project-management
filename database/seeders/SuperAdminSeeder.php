<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);

        // super_admin mendapatkan semua permission yang tersedia
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('super_admin role seeded with ALL permissions (' . Permission::count() . ' total).');
    }
}
