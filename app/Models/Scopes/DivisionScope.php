<?php

namespace App\Models\Scopes;

use App\Support\DivisionAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * The division wall for models that own a `division_id` column
 * (Project, ProjectRequest). Applied automatically to every query.
 *
 * Guests / console / seeders / queue workers (no authenticated user) are NOT
 * restricted — the wall is a request-time boundary between logged-in divisions,
 * not a data-integrity constraint. Global operators (super_admin/admin) bypass it.
 */
class DivisionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        $user = Auth::user();

        if (DivisionAccess::hasGlobalAccess($user)) {
            return;
        }

        $divisionIds = DivisionAccess::accessibleDivisionIds($user);

        // No divisions => sees nothing behind the wall.
        $builder->whereIn($model->getTable() . '.division_id', $divisionIds ?: [0]);
    }
}
