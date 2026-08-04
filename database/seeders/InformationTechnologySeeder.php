<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class InformationTechnologySeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Admin IT', 'email' => 'superadmin@heavenscent.com', 'role' => 'super_admin'],
            ['name' => 'Calvin Klaus', 'email' => 'calvinheavenscent@gmail.com', 'role' => 'cto'],
            ['name' => 'Andyka Salom', 'email' => 'andykasalom@gmail.com', 'role' => 'manager'],
            ['name' => 'Athiya', 'email' => 'rzqthya@gmail.com', 'role' => 'system_analyst'],
            ['name' => 'Ilham Gusti', 'email' => 'ilhamgustisyahputro@gmail.com', 'role' => 'programmer'],
            ['name' => 'QA User', 'email' => 'qa@example.com', 'role' => 'qa'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ]
            );

            $user->syncRoles([$userData['role']]);

            $this->command->info("User '{$userData['name']}' ({$userData['email']}) → role: {$userData['role']}");
        }

        $this->command->info('');
        $this->command->info('IT Users seeded. Default password: password');
    }
}
