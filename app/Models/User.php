<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName
{
    public function getFilamentName(): string
    {
        return trim((string) $this->name) ?: (string) $this->email;
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'google_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_members')
            ->withTimestamps();
    }

    public function divisions(): BelongsToMany
    {
        return $this->belongsToMany(Division::class, 'division_user')
            ->withPivot('position')
            ->withTimestamps();
    }

    /**
     * IDs of every division this user belongs to (any position).
     *
     * @return array<int, int>
     */
    public function divisionIds(): array
    {
        return $this->divisions()->pluck('divisions.id')->all();
    }

    /**
     * IDs of divisions where this user is a chief or manager — i.e. can see
     * ALL of that division's data, not just projects they are a member of.
     *
     * @return array<int, int>
     */
    public function ledDivisionIds(): array
    {
        return $this->divisions()
            ->wherePivotIn('position', ['chief', 'manager'])
            ->pluck('divisions.id')
            ->all();
    }

    public function isChiefOf(Division|int $division): bool
    {
        $divisionId = $division instanceof Division ? $division->id : $division;

        return $this->divisions()
            ->wherePivot('position', 'chief')
            ->where('divisions.id', $divisionId)
            ->exists();
    }

    public function leadsAnyDivision(): bool
    {
        return $this->divisions()
            ->wherePivotIn('position', ['chief', 'manager'])
            ->exists();
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function assignedTickets(): BelongsToMany
    {
        return $this->belongsToMany(Ticket::class, 'ticket_users');
    }

    public function createdTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'created_by');
    }

    public function isAssignedToTicket(Ticket $ticket): bool
    {
        return $this->assignedTickets()->where('ticket_id', $ticket->id)->exists();
    }

    public function assignToTicket(Ticket $ticket): void
    {
        $this->assignedTickets()->syncWithoutDetaching($ticket->id);
    }

    public function projectRequests(): HasMany
    {
        return $this->hasMany(ProjectRequest::class, 'requested_by');
    }

    public function assignedAnalysis(): HasMany
    {
        return $this->hasMany(ProjectRequest::class, 'analyst_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->orderBy('created_at', 'desc');
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)->unread()->orderBy('created_at', 'desc');
    }

    public function getUnreadNotificationsCountAttribute(): int
    {
        return $this->unreadNotifications()->count();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
}
