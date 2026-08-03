<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Issue;
use App\Support\DivisionAccess;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

class IssuePolicy
{
    use HandlesAuthorization;

    /**
     * The division wall: only act on an issue inside a division you belong to
     * (global operators and unassigned/legacy issues are exempt).
     */
    protected function withinDivision(AuthUser $authUser, Issue $issue): bool
    {
        if (DivisionAccess::hasGlobalAccess($authUser)) {
            return true;
        }

        if (is_null($issue->division_id)) {
            return true;
        }

        return in_array($issue->division_id, $authUser->divisionIds(), true);
    }

    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_issue');
    }

    public function view(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('view_issue') && $this->withinDivision($authUser, $issue);
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_issue');
    }

    public function update(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('update_issue') && $this->withinDivision($authUser, $issue);
    }

    public function delete(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('delete_issue') && $this->withinDivision($authUser, $issue);
    }

    public function restore(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('restore_issue');
    }

    public function forceDelete(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('force_delete_issue');
    }

    public function decide(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('decide_issue') && $this->withinDivision($authUser, $issue);
    }

    public function act(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('act_issue') && $this->withinDivision($authUser, $issue);
    }

    public function verify(AuthUser $authUser, Issue $issue): bool
    {
        return $authUser->can('verify_issue') && $this->withinDivision($authUser, $issue);
    }
}
