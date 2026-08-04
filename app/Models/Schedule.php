<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'all_day',
        'color',
        'owner_id',
        'is_shared',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
        'is_shared' => 'boolean',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'schedule_user')
            ->withPivot(['status', 'is_organizer', 'responded_at'])
            ->withTimestamps();
    }

    /**
     * Participants who have accepted (excludes pending/declined invites).
     */
    public function acceptedParticipants(): BelongsToMany
    {
        return $this->participants()->wherePivot('status', 'accepted');
    }
}
