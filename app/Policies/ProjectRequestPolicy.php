<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\ProjectRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectRequestPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_project::request');
    }

    public function view(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('view_project::request');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project::request');
    }

    public function update(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('update_project::request');
    }

    public function delete(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('delete_project::request');
    }

    public function restore(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('restore_project::request');
    }

    public function forceDelete(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('force_delete_project::request');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_project::request');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_project::request');
    }

    public function replicate(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('replicate_project::request');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_project::request');
    }

}