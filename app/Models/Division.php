<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Division extends Model
{
    use HasFactory;

    public const POSITIONS = [
        'chief' => 'Chief',
        'manager' => 'Manager',
        'staff' => 'Staff',
    ];

    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'division_user')
            ->withPivot('position')
            ->withTimestamps();
    }

    public function chiefs(): BelongsToMany
    {
        return $this->users()->wherePivot('position', 'chief');
    }

    public function managers(): BelongsToMany
    {
        return $this->users()->wherePivot('position', 'manager');
    }

    public function staff(): BelongsToMany
    {
        return $this->users()->wherePivot('position', 'staff');
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function projectRequests(): HasMany
    {
        return $this->hasMany(ProjectRequest::class);
    }
}
