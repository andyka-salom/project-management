<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Schedule;
use App\Models\User;

class ScheduleService
{
    /**
     * Attach a participant to a schedule, deciding whether the invite is
     * auto-accepted or needs approval based on org rank.
     *
     * Rule: owner rank >= participant rank (invite peer/downward) => accepted.
     *       owner rank <  participant rank (invite upward)        => pending.
     */
    public function attachParticipant(Schedule $schedule, User $participant): void
    {
        // Never re-invite the owner as a plain participant.
        if ($participant->id === $schedule->owner_id) {
            return;
        }

        $autoAccept = $schedule->owner->positionRank() >= $participant->positionRank();

        $schedule->participants()->syncWithoutDetaching([
            $participant->id => [
                'status' => $autoAccept ? 'accepted' : 'pending',
                'is_organizer' => false,
                'responded_at' => $autoAccept ? now() : null,
            ],
        ]);

        Notification::create([
            'user_id' => $participant->id,
            'type' => $autoAccept ? 'schedule_invite' : 'schedule_approval',
            'title' => $autoAccept ? 'New schedule' : 'Schedule needs your approval',
            'message' => $autoAccept
                ? "{$schedule->owner->name} added you to \"{$schedule->title}\"."
                : "{$schedule->owner->name} invited you to \"{$schedule->title}\". Approve to add it to your calendar.",
            'data' => ['schedule_id' => $schedule->id],
        ]);
    }

    /**
     * Attach the owner as an accepted organizer.
     */
    public function attachOwner(Schedule $schedule): void
    {
        $schedule->participants()->syncWithoutDetaching([
            $schedule->owner_id => [
                'status' => 'accepted',
                'is_organizer' => true,
                'responded_at' => now(),
            ],
        ]);
    }

    /**
     * Record a participant's response (accept/decline) and notify the owner.
     */
    public function respond(Schedule $schedule, User $participant, string $status): void
    {
        if (! in_array($status, ['accepted', 'declined'], true)) {
            return;
        }

        $schedule->participants()->updateExistingPivot($participant->id, [
            'status' => $status,
            'responded_at' => now(),
        ]);

        Notification::create([
            'user_id' => $schedule->owner_id,
            'type' => 'schedule_response',
            'title' => 'Schedule invite '.$status,
            'message' => "{$participant->name} {$status} your schedule \"{$schedule->title}\".",
            'data' => ['schedule_id' => $schedule->id],
        ]);
    }
}
