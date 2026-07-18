<?php

namespace App\Services;

use Exception;
use App\Mail\ProjectAssignmentNotification;
use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function notifyCommentAdded(TicketComment $comment): void
    {
        $ticket = $comment->ticket;
        $commenter = $comment->user;
        
        $usersToNotify = $this->getUsersToNotifyForComment($ticket, $commenter);
        
        foreach ($usersToNotify as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'comment_added',
                'title' => 'New Comment on Ticket',
                'message' => "{$commenter->name} added a comment on ticket {$ticket->title}",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'commenter_id' => $commenter->id,
                    'commenter_name' => $commenter->name,
                ],
            ]);
        }
    }

    public function notifyCommentUpdated(TicketComment $comment): void
    {
        $ticket = $comment->ticket;
        $commenter = $comment->user;
        
        $usersToNotify = $this->getUsersToNotifyForComment($ticket, $commenter);
        
        foreach ($usersToNotify as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => 'comment_updated',
                'title' => 'Comment Updated',
                'message' => "{$commenter->name} updated a comment on ticket {$ticket->title}",
                'data' => [
                    'ticket_id' => $ticket->id,
                    'comment_id' => $comment->id,
                    'commenter_id' => $commenter->id,
                    'commenter_name' => $commenter->name,
                ],
            ]);
        }
    }

    private function getUsersToNotifyForComment(Ticket $ticket, User $commenter): Collection
    {
        $usersToNotify = collect();
        
        if ($ticket->creator && $ticket->creator->id !== $commenter->id) {
            $usersToNotify->push($ticket->creator);
        }
        
        $assignedUsers = $ticket->assignees()->where('users.id', '!=', $commenter->id)->get();
        $usersToNotify = $usersToNotify->merge($assignedUsers);
        
        $commenters = $ticket->comments()
            ->with('user')
            ->where('user_id', '!=', $commenter->id)
            ->get()
            ->pluck('user')
            ->unique('id');
        $usersToNotify = $usersToNotify->merge($commenters);
        
        return $usersToNotify->unique('id');
    }

    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = Notification::where('id', $notificationId)
            ->where('user_id', $userId)
            ->first();
            
        if ($notification) {
            $notification->markAsRead();
            return true;
        }
        
        return false;
    }

    public function markAllAsRead(int $userId): void
    {
        Notification::where('user_id', $userId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function notifyProjectAssignment(Project $project, User $assignedUser, User $assignedBy): void
    {
        try {
            Notification::create([
                'user_id' => $assignedUser->id,
                'type' => 'project_assigned',
                'title' => 'Added to Project',
                'message' => "You have been added to project '{$project->name}' by {$assignedBy->name}",
                'data' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'assigned_by_id' => $assignedBy->id,
                    'assigned_by_name' => $assignedBy->name,
                ],
            ]);
        } catch (Exception $e) {
            Log::error('Failed to create in-app notification: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $assignedUser->id,
            ]);
        }

        try {
            $mail = new ProjectAssignmentNotification($project, $assignedUser, $assignedBy);
            Mail::to($assignedUser->email)->send($mail);
        } catch (Exception $e) {
            // Log error but don't fail the assignment
            Log::error('Failed to send project assignment email: ' . $e->getMessage(), [
                'project_id' => $project->id,
                'user_id' => $assignedUser->id,
                'assigned_by_id' => $assignedBy->id,
                'to_email' => $assignedUser->email,
            ]);
        }
    }

    public function notifyProjectRemoval(Project $project, User $removedUser, User $removedBy): void
    {
        Notification::create([
            'user_id' => $removedUser->id,
            'type' => 'project_removed',
            'title' => 'Removed from Project',
            'message' => "You have been removed from project '{$project->name}' by {$removedBy->name}",
            'data' => [
                'project_id' => $project->id,
                'project_name' => $project->name,
                'removed_by_id' => $removedBy->id,
                'removed_by_name' => $removedBy->name,
            ],
        ]);
    }

    public function notifyAnalystAssigned(ProjectRequest $request): void
    {
        if (!$request->analyst_id) {
            return;
        }

        Notification::create([
            'user_id' => $request->analyst_id,
            'type' => 'analyst_assigned',
            'title' => 'Assigned as Analyst',
            'message' => "You have been assigned to analyze request '{$request->title}'",
            'data' => [
                'project_request_id' => $request->id,
                'request_title' => $request->title,
                'assigned_by' => $request->requester->name ?? 'Unknown',
            ],
        ]);
    }

    public function notifyAnalysisSubmitted(ProjectRequest $request): void
    {
        Notification::create([
            'user_id' => $request->requested_by,
            'type' => 'analysis_submitted',
            'title' => 'Analysis Submitted',
            'message' => "Analysis for request '{$request->title}' has been submitted by {$request->analyst->name}",
            'data' => [
                'project_request_id' => $request->id,
                'request_title' => $request->title,
                'analyst_name' => $request->analyst->name ?? 'Unknown',
            ],
        ]);
    }

    public function notifyRecommendationSubmitted(ProjectRequest $request): void
    {
        $ctos = User::role('cto')->get();

        foreach ($ctos as $cto) {
            Notification::create([
                'user_id' => $cto->id,
                'type' => 'recommendation_submitted',
                'title' => 'Request Recommendation Submitted',
                'message' => "Request '{$request->title}' has been recommended for {$request->manager_recommendation} by {$request->recommender->name}",
                'data' => [
                    'project_request_id' => $request->id,
                    'request_title' => $request->title,
                    'recommendation' => $request->manager_recommendation,
                    'recommended_by' => $request->recommender->name ?? 'Unknown',
                ],
            ]);
        }
    }

    public function notifyRequestDecision(ProjectRequest $request): void
    {
        $usersToNotify = collect();

        if ($request->requested_by) {
            $usersToNotify->push($request->requester);
        }
        if ($request->analyst_id) {
            $usersToNotify->push($request->analyst);
        }

        $decision = $request->cto_decision === 'approve' ? 'approved' : 'rejected';

        foreach ($usersToNotify->unique('id') as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => "request_{$decision}",
                'title' => 'Request ' . ucfirst($decision),
                'message' => "Request '{$request->title}' has been {$decision} by {$request->decider->name}",
                'data' => [
                    'project_request_id' => $request->id,
                    'request_title' => $request->title,
                    'decision' => $decision,
                    'decided_by' => $request->decider->name ?? 'Unknown',
                    'project_id' => $request->project_id,
                ],
            ]);
        }
    }

    public function notifyPhaseChanged(Project $project, string $oldPhase, string $newPhase): void
    {
        $members = $project->members;
        $phaseLabels = Project::SDLC_PHASES;

        foreach ($members as $member) {
            Notification::create([
                'user_id' => $member->id,
                'type' => 'phase_changed',
                'title' => 'Project Phase Changed',
                'message' => "Project '{$project->name}' moved from {$phaseLabels[$oldPhase]} to {$phaseLabels[$newPhase]}",
                'data' => [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'old_phase' => $oldPhase,
                    'new_phase' => $newPhase,
                ],
            ]);
        }
    }
}
