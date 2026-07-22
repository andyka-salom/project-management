<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DivisionSeeder extends Seeder
{
    public function run(): void
    {
        // code => [name, color]
        $divisions = [
            // Calvin's group
            'HR'     => ['Human Resources', '#3B82F6'],
            'GA'     => ['General Affairs', '#2563EB'],
            'LND'    => ['Learning & Development', '#1D4ED8'],
            'IT'     => ['Information Technology', '#0EA5E9'],
            // Ayu's group
            'FIN'    => ['Finance', '#10B981'],
            'ACC'    => ['Accounting', '#059669'],
            // Lita's group
            'MKTOPS' => ['Marketing Operasional', '#F59E0B'],
            'MKTAWR' => ['Marketing Awareness', '#F97316'],
            'MKTRND' => ['Marketing RND', '#EA580C'],
            'CRM'    => ['CRM', '#D97706'],
        ];

        foreach ($divisions as $code => [$name, $color]) {
            Division::firstOrCreate(
                ['code' => $code],
                ['name' => $name, 'color' => $color, 'is_active' => true],
            );
        }

        $it = Division::where('code', 'IT')->first();

        // Backfill: existing projects/requests without a division become IT's, so
        // nothing disappears behind the division wall after this ships.
        Project::whereNull('division_id')->update(['division_id' => $it->id]);
        ProjectRequest::whereNull('division_id')->update(['division_id' => $it->id]);

        // Existing users without a division join IT (their historical home) as staff.
        User::whereDoesntHave('divisions')->get()->each(function (User $user) use ($it) {
            $user->divisions()->syncWithoutDetaching([
                $it->id => ['position' => 'staff'],
            ]);
        });

        // Real org structure: each chief leads several divisions.
        Role::firstOrCreate(['name' => 'chief']);

        $chiefs = [
            'Calvin' => ['email' => 'calvin@heavenscent.com', 'codes' => ['HR', 'GA', 'LND', 'IT']],
            'Ayu'    => ['email' => 'ayu@heavenscent.com',    'codes' => ['FIN', 'ACC']],
            'Lita'   => ['email' => 'lita@heavenscent.com',   'codes' => ['MKTOPS', 'MKTAWR', 'MKTRND', 'CRM']],
        ];

        foreach ($chiefs as $name => $data) {
            $chief = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => "Chief {$name}",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                ],
            );

            $chief->syncRoles(['chief']);

            $divisionIds = Division::whereIn('code', $data['codes'])->pluck('id');

            // sync (not syncWithoutDetaching) so re-running converges the chief onto
            // exactly their divisions, all with the 'chief' position.
            $chief->divisions()->sync(
                $divisionIds->mapWithKeys(fn ($id) => [$id => ['position' => 'chief']])->all()
            );

            $this->command->info("Chief {$name} → " . implode(', ', $data['codes']));
        }

        $this->command->info('Divisions & chiefs seeded. All chief accounts password: password');
    }
}
