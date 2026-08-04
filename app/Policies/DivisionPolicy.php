<?php

declare(strict_types=1);

namespace App\Policies;

use Illuminate\Foundation\Auth\User as AuthUser;
use App\Models\Division;
use Illuminate\Auth\Access\HandlesAuthorization;

class DivisionPolicy
{
    use HandlesAuthorization;
    
    public function viewAny(AuthUser $authUser): bool
    {
        return $authUser->can('view_any_division');
    }

    public function view(AuthUser $authUser, Division $division): bool
    {
        return $authUser->can('view_division');
    }

    public function create(AuthUser $authUser): bool
    {
        return $authUser->can('create_division');
    }

    public function update(AuthUser $authUser, Division $division): bool
    {
        return $authUser->can('update_division');
    }

    public function delete(AuthUser $authUser, Division $division): bool
    {
        return $authUser->can('delete_division');
    }

    public function restore(AuthUser $authUser, Division $division): bool
    {
        return $authUser->can('restore_division');
    }

    public function forceDelete(AuthUser $authUser, Division $division): bool
    {
        return $authUser->can('force_delete_division');
    }

    public function forceDeleteAny(AuthUser $authUser): bool
    {
        return $authUser->can('force_delete_any_division');
    }

    public function restoreAny(AuthUser $authUser): bool
    {
        return $authUser->can('restore_any_division');
    }

    public function replicate(AuthUser $authUser, Division $division): bool
    {
        return $authUser->can('replicate_division');
    }

    public function reorder(AuthUser $authUser): bool
    {
        return $authUser->can('reorder_division');
    }

}