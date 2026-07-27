<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasName, MustVerifyEmail
{
    public function getFilamentName(): string
    {
        return trim((string) $this->name) ?: (string) $this->email;
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmailTrait, Notifiable;

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
        'email_otp',
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
            'email_otp_expires_at' => 'datetime',
        ];
    }

    /**
     * Generate a fresh 6-digit email verification OTP, store it hashed with an
     * expiry, and return the plaintext code so it can be emailed to the user.
     */
    public function generateEmailOtp(int $ttlMinutes = 10): string
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->forceFill([
            'email_otp' => Hash::make($otp),
            'email_otp_expires_at' => now()->addMinutes($ttlMinutes),
        ])->save();

        return $otp;
    }

    /**
     * True when the given code matches the stored OTP and has not expired.
     */
    public function verifyEmailOtp(string $otp): bool
    {
        if (empty($this->email_otp) || empty($this->email_otp_expires_at)) {
            return false;
        }

        if (now()->greaterThan($this->email_otp_expires_at)) {
            return false;
        }

        return Hash::check($otp, $this->email_otp);
    }

    public function hasPendingEmailOtp(): bool
    {
        return ! empty($this->email_otp)
            && ! empty($this->email_otp_expires_at)
            && now()->lessThanOrEqualTo($this->email_otp_expires_at);
    }

    public function clearEmailOtp(): void
    {
        $this->forceFill([
            'email_otp' => null,
            'email_otp_expires_at' => null,
        ])->save();
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
