<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Issue;
use Illuminate\Auth\Access\HandlesAuthorization;

class IssuePolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_issue');
    }

    public function view(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('view_issue');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_issue');
    }

    public function update(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('update_issue');
    }

    public function delete(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('delete_issue');
    }

    public function restore(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('restore_issue');
    }

    public function forceDelete(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('force_delete_issue');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_issue');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_issue');
    }

    public function replicate(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('replicate_issue');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_issue');
    }

}