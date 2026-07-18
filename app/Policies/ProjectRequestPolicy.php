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
        return $authUser->can('view_any_project_request');
    }

    public function view(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('view_project_request');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_project_request');
    }

    public function update(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('update_project_request');
    }

    public function delete(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('delete_project_request');
    }

    public function restore(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('restore_project_request');
    }

    public function forceDelete(AuthUser $authUser, ProjectRequest $projectRequest): bool
    {
        return $authUser->can('force_delete_project_request');
    }

    public function assignAnalyst(AuthUser $authUser): bool
    {
        return $authUser->can('assign_analyst_project_request');
    }

    public function submitAnalysis(AuthUser $authUser): bool
    {
        return $authUser->can('submit_analysis_project_request');
    }

    public function recommend(AuthUser $authUser): bool
    {
        return $authUser->can('recommend_project_request');
    }

    public function approve(AuthUser $authUser): bool
    {
        return $authUser->can('approve_project_request');
    }
}
