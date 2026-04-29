<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_USER = 'user';
    public const ROLE_SUPERVISOR = 'supervisor';
    public const ROLE_COMPANY = 'company';
    public const ROLE_INTERN = 'intern';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'plan_type',
        'premium_until',
        'is_active',
        'google_id',
        'avatar',
        'headline',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'premium_until' => 'datetime',
        ];
    }

    public function isUser(): bool
    {
        return in_array($this->role, [self::ROLE_USER, self::ROLE_INTERN], true);
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isSupervisor(): bool
    {
        return $this->role === self::ROLE_SUPERVISOR;
    }

    public function isCompany(): bool
    {
        return $this->role === self::ROLE_COMPANY;
    }

    public function isIntern(): bool
    {
        return in_array($this->role, [self::ROLE_INTERN, self::ROLE_USER], true);
    }

    public function intern()
    {
        return $this->hasOne(Intern::class);
    }

    public function supervisor()
    {
        return $this->hasOne(Supervisor::class);
    }

    public function company()
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function applications()
    {
        return $this->hasMany(Application::class, 'user_id');
    }

    public function supervisedInternships()
    {
        return $this->hasMany(Internship::class, 'supervisor_id');
    }

    public function companyInternships()
    {
        return $this->hasManyThrough(Internship::class, Company::class, 'user_id', 'company_id');
    }

    public function hasCompletedRoleProfile(): bool
    {
        return match ($this->role) {
            self::ROLE_USER => $this->intern()->exists(),
            self::ROLE_INTERN => $this->intern()->exists(),
            self::ROLE_SUPERVISOR => $this->supervisor()->exists(),
            self::ROLE_COMPANY => $this->company()->exists(),
            self::ROLE_ADMIN => true,
            default => true,
        };
    }

    public function hasPendingGoogleRegistration(): bool
    {
        return !empty($this->google_id) && !$this->hasCompletedRoleProfile();
    }

    public function profilePhotoUrl(): ?string
    {
        if (!$this->avatar) {
            return null;
        }

        if (Str::startsWith($this->avatar, ['http://', 'https://'])) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }

    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->implode('');
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'Administrator',
            self::ROLE_USER => 'Intern',
            self::ROLE_SUPERVISOR => 'Pembimbing',
            self::ROLE_COMPANY => 'Perusahaan',
            self::ROLE_INTERN => 'Intern',
            default => ucfirst((string) $this->role),
        };
    }

    public function getRoleBadgeClassAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => 'bg-red-500',
            self::ROLE_USER => 'bg-blue-500',
            self::ROLE_SUPERVISOR => 'bg-green-500',
            self::ROLE_COMPANY => 'bg-purple-500',
            self::ROLE_INTERN => 'bg-orange-500',
            default => 'bg-gray-500',
        };
    }

    public function hasActiveInternship(): bool
    {
        return $this->applications()
            ->whereHas('internship', function ($q) {
                $q->where('status', 'active')
                  ->where('start_date', '<=', now())
                  ->where('end_date', '>=', now());
            })->exists();
    }

    public function hasPendingApplications(): bool
    {
        return $this->applications()->where('status', 'pending')->exists();
    }

    public function canApplyForPrograms(): bool
    {
        return $this->isIntern() && !$this->hasActiveInternship();
    }

    public function hasActivePremium(): bool
    {
        return $this->plan_type === 'premium'
            && $this->premium_until !== null
            && $this->premium_until->isFuture();
    }

    public function applicationsLeft(): int
    {
        if ($this->hasActivePremium()) {
            return -1; // unlimited
        }
        $applied = $this->applications()->count();
        return max(0, 2 - $applied);
    }
}
