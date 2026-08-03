<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use App\Models\Scopes\DivisionScope;
use Carbon\Carbon;

#[ScopedBy([DivisionScope::class])]
class Project extends Model
{
    use HasFactory, SoftDeletes;

    public const SDLC_PHASES = [
        'planning' => 'Planning',
        'requirements_analysis' => 'Requirements Analysis',
        'system_design' => 'System Design',
        'implementation' => 'Implementation',
        'testing' => 'Testing',
        'deployment' => 'Deployment',
        'maintenance' => 'Maintenance',
        'completed' => 'Completed',
    ];

    public const SDLC_PHASE_ORDER = [
        'planning',
        'requirements_analysis',
        'system_design',
        'implementation',
        'testing',
        'deployment',
        'maintenance',
        'completed',
    ];

    protected $fillable = [
        'division_id',
        'name',
        'description',
        'ticket_prefix',
        'color',
        'start_date',
        'end_date',
        'pinned_date',
        'sdlc_phase',
        'project_request_id',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'pinned_date' => 'datetime',
    ];

    public function getIsPinnedAttribute(): bool
    {
        return !is_null($this->pinned_date);
    }

    public function pin(): void
    {
        $this->update(['pinned_date' => now()]);
    }

    public function unpin(): void
    {
        $this->update(['pinned_date' => null]);
    }

    public function division(): BelongsTo
    {
        return $this->belongsTo(Division::class);
    }

    public function ticketStatuses(): HasMany
    {
        return $this->hasMany(TicketStatus::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'project_members')
            ->withTimestamps();
    }

    public function epics(): HasMany
    {
        return $this->hasMany(Epic::class);
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class);
    }

    public function getRemainingDaysAttribute()
    {
        if (!$this->end_date) {
            return null;
        }

        $today = Carbon::today();
        $endDate = Carbon::parse($this->end_date);

        if ($today->gt($endDate)) {
            return 0;
        }

        return $today->diffInDays($endDate);
    }
    
    public function getProgressPercentageAttribute(): float
    {
        $totalTickets = $this->tickets()->count();
        
        if ($totalTickets === 0) {
            return 0.0;
        }
        
        $completedTickets = $this->tickets()
            ->whereHas('status', function ($query) {
                $query->where('is_completed', true);
            })
            ->count();
        
        return round(($completedTickets / $totalTickets) * 100, 1);
    }
    
    public function projectRequest(): BelongsTo
    {
        return $this->belongsTo(ProjectRequest::class);
    }

    public function getNextSdlcPhaseAttribute(): ?string
    {
        if (!$this->sdlc_phase) {
            return null;
        }

        $currentIndex = array_search($this->sdlc_phase, self::SDLC_PHASE_ORDER);
        if ($currentIndex === false || $currentIndex >= count(self::SDLC_PHASE_ORDER) - 1) {
            return null;
        }

        return self::SDLC_PHASE_ORDER[$currentIndex + 1];
    }

    public static function getSdlcPhaseColor(string $phase): string
    {
        return match ($phase) {
            'planning' => 'gray',
            'requirements_analysis' => 'info',
            'system_design' => 'purple',
            'implementation' => 'warning',
            'testing' => 'orange',
            'deployment' => 'success',
            'maintenance' => 'primary',
            'completed' => 'success',
            default => 'gray',
        };
    }

    public function externalAccess(): HasOne
    {
        return $this->hasOne(ExternalAccess::class);
    }
    
    public function generateExternalAccess()
    {
        $this->externalAccess()?->delete();
    
        return ExternalAccess::generateForProject($this->id);
    }
}
