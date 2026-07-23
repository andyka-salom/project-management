<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\ProjectRequestHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProjectRequestService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    public function assignAnalyst(ProjectRequest $request, User $analyst, User $assignedBy): void
    {
        $request->update([
            'analyst_id' => $analyst->id,
            'status' => ProjectRequest::STATUS_PENDING_ANALYSIS,
        ]);

        ProjectRequestHistory::create([
            'project_request_id' => $request->id,
            'user_id' => $assignedBy->id,
            'action' => 'assigned_analyst',
            'from_status' => ProjectRequest::STATUS_DRAFT,
            'to_status' => ProjectRequest::STATUS_PENDING_ANALYSIS,
            'notes' => "Assigned {$analyst->name} as analyst",
        ]);

        $this->notificationService->notifyAnalystAssigned($request);
    }

    public function submitAnalysis(ProjectRequest $request, User $analyst): void
    {
        $request->update([
            'status' => ProjectRequest::STATUS_ANALYSIS_SUBMITTED,
            'analysis_submitted_at' => now(),
        ]);

        ProjectRequestHistory::create([
            'project_request_id' => $request->id,
            'user_id' => $analyst->id,
            'action' => 'analysis_submitted',
            'from_status' => ProjectRequest::STATUS_PENDING_ANALYSIS,
            'to_status' => ProjectRequest::STATUS_ANALYSIS_SUBMITTED,
        ]);

        $this->notificationService->notifyAnalysisSubmitted($request);
    }

    public function submitRecommendation(ProjectRequest $request, User $manager, string $recommendation, string $reason): void
    {
        $status = $recommendation === 'approve'
            ? ProjectRequest::STATUS_RECOMMENDED_APPROVE
            : ProjectRequest::STATUS_RECOMMENDED_REJECT;

        $request->update([
            'manager_recommendation' => $recommendation,
            'manager_recommendation_reason' => $reason,
            'recommended_by' => $manager->id,
            'recommended_at' => now(),
            'status' => $status,
        ]);

        ProjectRequestHistory::create([
            'project_request_id' => $request->id,
            'user_id' => $manager->id,
            'action' => 'recommended',
            'from_status' => ProjectRequest::STATUS_ANALYSIS_SUBMITTED,
            'to_status' => $status,
            'notes' => "Recommended: {$recommendation}. Reason: {$reason}",
        ]);

        $this->notificationService->notifyRecommendationSubmitted($request);
    }

    public function approveAndCreateProject(ProjectRequest $request, User $cto, string $reason): Project
    {
        return DB::transaction(function () use ($request, $cto, $reason) {
            $request->update([
                'cto_decision' => 'approve',
                'cto_decision_reason' => $reason,
                'decided_by' => $cto->id,
                'decided_at' => now(),
                'status' => ProjectRequest::STATUS_APPROVED,
            ]);

            $prefix = Str::upper(Str::substr(Str::slug($request->title, ''), 0, 4));
            if (strlen($prefix) < 3) {
                $prefix = 'PRJ';
            }

            $project = Project::create([
                'division_id' => $request->division_id,
                'name' => $request->title,
                'description' => $request->description,
                'ticket_prefix' => $prefix,
                'sdlc_phase' => 'planning',
                'project_request_id' => $request->id,
                'start_date' => now(),
                'end_date' => $request->requested_deadline,
            ]);

            $defaultStatuses = [
                ['name' => 'Backlog', 'color' => '#6B7280', 'sort_order' => 0],
                ['name' => 'To Do', 'color' => '#F59E0B', 'sort_order' => 1],
                ['name' => 'In Progress', 'color' => '#3B82F6', 'sort_order' => 2],
                ['name' => 'Review', 'color' => '#8B5CF6', 'sort_order' => 3],
                ['name' => 'Done', 'color' => '#10B981', 'sort_order' => 4, 'is_completed' => true],
            ];

            foreach ($defaultStatuses as $status) {
                $project->ticketStatuses()->create($status);
            }

            $memberIds = collect([$request->requested_by, $request->analyst_id, $cto->id])
                ->filter()
                ->unique()
                ->toArray();

            $project->members()->syncWithoutDetaching($memberIds);

            $request->update(['project_id' => $project->id]);

            ProjectRequestHistory::create([
                'project_request_id' => $request->id,
                'user_id' => $cto->id,
                'action' => 'approved',
                'from_status' => $request->getOriginal('status'),
                'to_status' => ProjectRequest::STATUS_APPROVED,
                'notes' => "Approved. Project '{$project->name}' created. Reason: {$reason}",
            ]);

            $this->notificationService->notifyRequestDecision($request);

            return $project;
        });
    }

    public function reject(ProjectRequest $request, User $cto, string $reason): void
    {
        $request->update([
            'cto_decision' => 'reject',
            'cto_decision_reason' => $reason,
            'decided_by' => $cto->id,
            'decided_at' => now(),
            'status' => ProjectRequest::STATUS_REJECTED,
        ]);

        ProjectRequestHistory::create([
            'project_request_id' => $request->id,
            'user_id' => $cto->id,
            'action' => 'rejected',
            'from_status' => $request->getOriginal('status'),
            'to_status' => ProjectRequest::STATUS_REJECTED,
            'notes' => "Rejected. Reason: {$reason}",
        ]);

        $this->notificationService->notifyRequestDecision($request);
    }
}
