<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Project;
use App\Support\DivisionAccess;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * The division wall: a user may only act on a project inside a division they
     * belong to (global operators and unassigned/legacy projects are exempt).
     */
    protected function withinDivision(AuthUser $authUser, Project $project): bool
    {
        if (DivisionAccess::hasGlobalAccess($authUser)) {
            return true;
        }

        if (is_null($project->division_id)) {
            return true;
        }

        return in_array($project->division_id, $authUser->divisionIds(), true);
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_project');
    }

    public function view(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('view_project') && $this->withinDivision($authUser, $project);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project');
    }

    public function update(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('update_project') && $this->withinDivision($authUser, $project);
    }

    public function delete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('delete_project') && $this->withinDivision($authUser, $project);
    }

    public function restore(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('restore_project');
    }

    public function forceDelete(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('force_delete_project');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_project');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_project');
    }

    public function replicate(AuthUser $authUser, Project $project): bool
    {
        return $authUser->can('replicate_project');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project');
    }

}