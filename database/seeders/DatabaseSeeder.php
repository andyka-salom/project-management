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
        $this->call(MemberSeeder::class);
        $this->call(CtoSeeder::class);
        $this->call(ManagerSeeder::class);
        $this->call(SystemAnalystSeeder::class);
        $this->call(ProgrammerSeeder::class);
        $this->call(QaSeeder::class);

        $this->call(InformationTechnologySeeder::class);

        // 4. Demo data
        // $this->call(DemoDataSeeder::class);

        // 5. Divisions — create org structure and assign existing users/projects/requests
        $this->call(DivisionSeeder::class);
    }
}
