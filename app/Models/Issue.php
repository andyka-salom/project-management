<?php

namespace App\Models;

use App\Models\Scopes\DivisionScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ScopedBy([DivisionScope::class])]
class Issue extends Model
{
    use HasFactory;

    public const STATUS_OPEN = 'open';
    public const STATUS_CTO_QUEUE = 'cto_queue';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_VERIFYING = 'verifying';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const LEVEL_LOW = 'low';
    public const LEVEL_MEDIUM = 'medium';
    public const LEVEL_HIGH = 'high';
    public const LEVEL_CRITICAL = 'critical';

    protected $fillable = [
        'division_id',
        'title',
        'description',
        'level',
        'pic_id',
        'deadline',
        'needs_cto_decision',
        'status',
        'cto_decision_notes',
        'decided_by',
        'decided_at',
        'action_notes',
        'solution',
        'prevention',
        'resolved_at',
        'created_by',
        'closed_by',
        'closed_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'needs_cto_decision' => 'boolean',
        'decided_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function getStatuses(): array
    {
        return [
            self::STATUS_OPEN => 'Open',
            self::STATUS_CTO_QUEUE => 'CTO Decision Queue',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_VERIFYING => 'Verifying',
            self::STATUS_RESOLVED => 'Resolved',
            self::STATUS_CLOSED => 'Closed',
        ];
    }

    public static function getLevels(): array
    {
        return [
            self::LEVEL_LOW => 'Low',
            self::LEVEL_MEDIUM => 'Medium',
            self::LEVEL_HIGH => 'High',
            self::LEVEL_CRITICAL => 'Critical',
        ];
    }

    public static function getStatusColor(string $status): string
    {
        return match ($status) {
            self::STATUS_OPEN => 'gray',
            self::STATUS_CTO_QUEUE => 'purple',
            self::STATUS_IN_PROGRESS => 'info',
            self::STATUS_VERIFYING => 'warning',
            self::STATUS_RESOLVED => 'success',
            self::STATUS_CLOSED => 'gray',
            default => 'gray',
        };
    }

    public static function getLevelColor(string $level): string
    {
        return match ($level) {
            self::LEVEL_LOW => 'gray',
            self::LEVEL_MEDIUM => 'info',
            self::LEVEL_HIGH => 'warning',
            self::LEVEL_CRITICAL => 'danger',
            default => 'gray',
        };
    }

    protected static function booted(): void
    {
        static::creating(function (Issue $issue) {
            if (empty($issue->created_by) && auth()->id()) {
                $issue->created_by = auth()->id();
            }

            // Route the fresh issue: straight to the CTO queue when a decision is
            // required, otherwise hand it to the PIC to act on.
            if (empty($issue->status) || $issue->status === self::STATUS_OPEN) {
                $issue->status = $issue->needs_cto_decision
                    ? self::STATUS_CTO_QUEUE
                    : self::STATUS_IN_PROGRESS;
            }
        });

        static::created(function (Issue $issue) {
            IssueHistory::create([
                'issue_id' => $issue->id,
                'user_id' => auth()->id(),
                'action' => 'created',
                'from_status' => null,
                'to_status' => $issue->status,
                'notes' => 'Issue created',
            ]);
        });
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function pic(): BelongsTo
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function histories(): HasMany
    {
        return $this->hasMany(IssueHistory::class)->orderByDesc('created_at');
    }
}
