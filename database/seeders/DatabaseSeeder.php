<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Jalankan RoleSeeder (Permission → SuperAdmin → Admin → Member)
        $this->call(RoleSeeder::class);
        $this->call(SuperAdminSeeder::class);
        $this->call(AdminSeeder::class);

        // Buat test user dan assign role super_admin
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $user->assignRole('super_admin');

    }
}
