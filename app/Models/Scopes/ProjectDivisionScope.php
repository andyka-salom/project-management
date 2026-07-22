<?php

namespace App\Models\Scopes;

use App\Support\DivisionAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * The division wall for models that belong to a Project but have no own
 * `division_id` (Ticket, Epic, ProjectNote). They inherit the boundary through
 * their parent: `whereHas('project')` runs the Project query WITH its own
 * DivisionScope, so these stay in sync automatically with no denormalized column.
 */
class ProjectDivisionScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (! Auth::check()) {
            return;
        }

        if (DivisionAccess::hasGlobalAccess(Auth::user())) {
            return;
        }

        $builder->whereHas('project');
    }
}
