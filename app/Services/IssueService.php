<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\IssueHistory;
use App\Models\User;

/**
 * Drives the Issue workflow:
 *   open ─▶ (CTO queue) ─▶ in progress ─▶ verifying ─▶ resolved ─▶ closed
 *                                  ▲                 │
 *                                  └──── (not done) ─┘
 */
class IssueService
{
    /** CTO/Chief records their decision; the issue moves on to the PIC. */
    public function decide(Issue $issue, User $decider, string $notes): void
    {
        $from = $issue->status;

        $issue->update([
            'cto_decision_notes' => $notes,
            'decided_by' => $decider->id,
            'decided_at' => now(),
            'status' => Issue::STATUS_IN_PROGRESS,
        ]);

        $this->record($issue, $decider, 'cto_decided', $from, Issue::STATUS_IN_PROGRESS, $notes);
    }

    /** PIC performs the action and sends the issue back for manager verification. */
    public function performAction(Issue $issue, User $pic, string $notes): void
    {
        $from = $issue->status;

        $issue->update([
            'action_notes' => $notes,
            'status' => Issue::STATUS_VERIFYING,
        ]);

        $this->record($issue, $pic, 'action_performed', $from, Issue::STATUS_VERIFYING, $notes);
    }

    /** Manager confirms the issue is solved and records the solution & prevention. */
    public function resolve(Issue $issue, User $manager, string $solution, string $prevention): void
    {
        $from = $issue->status;

        $issue->update([
            'solution' => $solution,
            'prevention' => $prevention,
            'resolved_at' => now(),
            'status' => Issue::STATUS_RESOLVED,
        ]);

        $this->record($issue, $manager, 'resolved', $from, Issue::STATUS_RESOLVED, 'Solution & prevention recorded');
    }

    /** Manager judges the work incomplete; bounce it back to the PIC. */
    public function rejectVerification(Issue $issue, User $manager, string $reason): void
    {
        $from = $issue->status;

        $issue->update([
            'status' => Issue::STATUS_IN_PROGRESS,
        ]);

        $this->record($issue, $manager, 'verification_rejected', $from, Issue::STATUS_IN_PROGRESS, $reason);
    }

    /** Manager closes the resolved issue. */
    public function close(Issue $issue, User $manager): void
    {
        $from = $issue->status;

        $issue->update([
            'closed_by' => $manager->id,
            'closed_at' => now(),
            'status' => Issue::STATUS_CLOSED,
        ]);

        $this->record($issue, $manager, 'closed', $from, Issue::STATUS_CLOSED, 'Issue closed');
    }

    protected function record(Issue $issue, User $user, string $action, ?string $from, ?string $to, ?string $notes): void
    {
        IssueHistory::create([
            'issue_id' => $issue->id,
            'user_id' => $user->id,
            'action' => $action,
            'from_status' => $from,
            'to_status' => $to,
            'notes' => $notes,
        ]);
    }
}
