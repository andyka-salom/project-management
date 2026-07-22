<?php

namespace App\Support;

use App\Models\User;

/**
 * Central authority for "which divisions can this user touch".
 *
 * The division wall is the OUTER security boundary of the app: unless a user has
 * global access (super_admin / admin), they can only ever see data belonging to
 * divisions they are a member of. Within a division, chiefs and managers see
 * everything; staff only see what they are assigned to.
 */
class DivisionAccess
{
    /**
     * Roles that are exempt from the division wall entirely (company-wide operators).
     *
     * @var array<int, string>
     */
    public const GLOBAL_ROLES = ['super_admin', 'admin'];

    public static function hasGlobalAccess(?User $user): bool
    {
        if (! $user || ! method_exists($user, 'hasRole')) {
            return false;
        }

        return $user->hasRole(self::GLOBAL_ROLES);
    }

    /**
     * IDs of divisions the user may see any data from.
     *
     * @return array<int, int>
     */
    public static function accessibleDivisionIds(User $user): array
    {
        return $user->divisionIds();
    }

    /**
     * True when the user should see EVERY project/request in the given division
     * (they are a chief or manager there), as opposed to only assigned items.
     */
    public static function canSeeAllInDivision(User $user, int $divisionId): bool
    {
        return in_array($divisionId, $user->ledDivisionIds(), true);
    }

    /**
     * True when the user leads (chief/manager) at least one division.
     */
    public static function leadsAnyDivision(User $user): bool
    {
        return $user->leadsAnyDivision();
    }
}
