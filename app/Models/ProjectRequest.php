<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use App\Models\Scopes\DivisionScope;

#[ScopedBy([DivisionScope::class])]
class ProjectRequest extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'division_id',
        'title',
        'description',
        'business_justification',
        'priority',
        'requested_deadline',
        'status',
        'requested_by',
        'analyst_id',
        'requirement_analysis',
        'feasibility_study',
        'technical_notes',
        'analysis_submitted_at',
        'manager_recommendation',
        'manager_recommendation_reason',
        'recommended_by',
        'recommended_at',
        'cto_decision',
        'cto_decision_reason',
        'decided_by',
        'decided_at',
        'project_id',
    ];

    protected $casts = [
        'requested_deadline' => 'date',
        'analysis_submitted_at' => 'datetime',
        'recommended_at' => 'datetime',
        'decided_at' => 'datetime',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PENDING_ANALYSIS = 'pending_analysis';
    public const STATUS_ANALYSIS_SUBMITTED = 'analysis_submitted';
    public const STATUS_RECOMMENDED_APPROVE = 'recommended_approve';
    public const STATUS_RECOMMENDED_REJECT = 'recommended_reject';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    public static function getStatuses(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_PENDING_ANALYSIS => 'Pending Analysis',
            self::STATUS_ANALYSIS_SUBMITTED => 'Analysis Submitted',
            self::STATUS_RECOMMENDED_APPROVE => 'Recommended (Approve)',
            self::STATUS_RECOMMENDED_REJECT => 'Recommended (Reject)',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_CRITICAL => 'Critical',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_PENDING_ANALYSIS => 'info',
            self::STATUS_ANALYSIS_SUBMITTED => 'warning',
            self::STATUS_RECOMMENDED_APPROVE => 'success',
            self::STATUS_RECOMMENDED_REJECT => 'danger',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default => 'gray',
        };
    }

    public static function getPriorityColor(string $priority): string
    {
        return match ($priority) {
            self::PRIORITY_LOW => 'gray',
            self::PRIORITY_MEDIUM => 'info',
            self::PRIORITY_HIGH => 'warning',
            self::PRIORITY_CRITICAL => 'danger',
            default => 'gray',
        };
    }

    protected static function booted(): void
    {
        static::creating(function (ProjectRequest $request) {
            if (empty($request->requested_by) && auth()->id()) {
                $request->requested_by = auth()->id();
            }
        });

        static::updating(function (ProjectRequest $request) {
            if ($request->isDirty('status')) {
                ProjectRequestHistory::create([
                    'project_request_id' => $request->id,
                    'user_id' => auth()->id(),
                    'action' => 'status_changed',
                    'from_status' => $request->getOriginal('status'),
                    'to_status' => $request->status,
                ]);
            }
        });
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function analyst(): BelongsTo
    {
        return $this->belongsTo(User::class, 'analyst_id');
    }

    public function recommender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recommended_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function histories(): HasMany
    {
        return $this->hasMany(ProjectRequestHistory::class)->orderBy('created_at', 'desc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectRequestAttachment::class);
    }
}
