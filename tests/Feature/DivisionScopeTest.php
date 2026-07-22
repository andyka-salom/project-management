<?php

use App\Models\Division;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

/**
 * Helpers ---------------------------------------------------------------------
 */
function makeDivision(string $code): Division
{
    return Division::create([
        'name' => $code . ' Division',
        'code' => $code,
        'is_active' => true,
    ]);
}

function makeProject(Division $division, string $name): Project
{
    return Project::create([
        'division_id' => $division->id,
        'name' => $name,
        'ticket_prefix' => strtoupper(substr($name, 0, 3)),
    ]);
}

/**
 * Tests -----------------------------------------------------------------------
 */
it('hides other divisions projects from a division member', function () {
    $it = makeDivision('IT');
    $finance = makeDivision('FIN');

    $itProject = makeProject($it, 'IT Portal');
    $financeProject = makeProject($finance, 'Budget Tool');

    $user = User::factory()->create();
    $user->divisions()->attach($it->id, ['position' => 'staff']);

    $this->actingAs($user);

    $visibleIds = Project::query()->pluck('id');

    expect($visibleIds)->toContain($itProject->id)
        ->not->toContain($financeProject->id);
});

it('lets a chief see every division they belong to but no others', function () {
    $it = makeDivision('IT');
    $finance = makeDivision('FIN');
    $marketing = makeDivision('MKT');

    $itProject = makeProject($it, 'IT Portal');
    $financeProject = makeProject($finance, 'Budget Tool');
    $marketingProject = makeProject($marketing, 'Campaign');

    $chief = User::factory()->create();
    $chief->divisions()->attach($it->id, ['position' => 'chief']);
    $chief->divisions()->attach($finance->id, ['position' => 'chief']);

    $this->actingAs($chief);

    $visibleIds = Project::query()->pluck('id');

    expect($visibleIds)->toContain($itProject->id)
        ->toContain($financeProject->id)
        ->not->toContain($marketingProject->id);
});

it('lets a super admin see all divisions', function () {
    Role::findOrCreate('super_admin', 'web');

    $it = makeDivision('IT');
    $finance = makeDivision('FIN');
    $itProject = makeProject($it, 'IT Portal');
    $financeProject = makeProject($finance, 'Budget Tool');

    $admin = User::factory()->create();
    $admin->assignRole('super_admin');
    // Intentionally attached to no division.

    $this->actingAs($admin);

    $visibleIds = Project::query()->pluck('id');

    expect($visibleIds)->toContain($itProject->id)
        ->toContain($financeProject->id);
});

it('walls tickets off through their parent project', function () {
    $it = makeDivision('IT');
    $finance = makeDivision('FIN');

    $itProject = makeProject($it, 'IT Portal');
    $financeProject = makeProject($finance, 'Budget Tool');

    $itTicket = \App\Models\Ticket::create([
        'project_id' => $itProject->id,
        'name' => 'IT task',
    ]);
    $financeTicket = \App\Models\Ticket::create([
        'project_id' => $financeProject->id,
        'name' => 'Finance task',
    ]);

    $user = User::factory()->create();
    $user->divisions()->attach($it->id, ['position' => 'staff']);

    $this->actingAs($user);

    $visibleTicketIds = \App\Models\Ticket::query()->pluck('id');

    expect($visibleTicketIds)->toContain($itTicket->id)
        ->not->toContain($financeTicket->id);
});
