<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Permissions & Roles
        $this->call(RoleSeeder::class);

        // 2. Individual role seeders (re-sync permissions per role)
        $this->call(SuperAdminSeeder::class);
        $this->call(AdminSeeder::class);
        $this->call(MemberSeeder::class);
        $this->call(CtoSeeder::class);
        $this->call(ManagerSeeder::class);
        $this->call(SystemAnalystSeeder::class);
        $this->call(ProgrammerSeeder::class);
        $this->call(QaSeeder::class);

        // 3. Test users per role
        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@example.com', 'role' => 'super_admin'],
            ['name' => 'Admin User', 'email' => 'admin@example.com', 'role' => 'admin'],
            ['name' => 'CTO User', 'email' => 'cto@example.com', 'role' => 'cto'],
            ['name' => 'Manager User', 'email' => 'manager@example.com', 'role' => 'manager'],
            ['name' => 'System Analyst', 'email' => 'analyst@example.com', 'role' => 'system_analyst'],
            ['name' => 'Programmer User', 'email' => 'programmer@example.com', 'role' => 'programmer'],
            ['name' => 'QA User', 'email' => 'qa@example.com', 'role' => 'qa'],
            ['name' => 'Member User', 'email' => 'member@example.com', 'role' => 'member'],
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
        $this->command->info('All test users password: password');

        // 4. Demo data
        // $this->call(DemoDataSeeder::class);

        // 5. Divisions — create org structure and assign existing users/projects/requests
        $this->call(DivisionSeeder::class);
    }
}
