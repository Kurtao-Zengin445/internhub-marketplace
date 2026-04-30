<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Application extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'internship_program_id',
        'motivation_letter',
        'cv_file',
        'status',
        'rejection_reason',
        'status_note',
        'applied_at',
        'reviewed_at',
    ];

    protected $casts = [
        'applied_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function applicantUser()
    {
        return $this->user();
    }

    public function intern()
    {
        return $this->belongsTo(Intern::class, 'user_id', 'user_id');
    }

    public function program()
    {
        return $this->belongsTo(InternshipProgram::class, 'internship_program_id');
    }

    public function internship()
    {
        return $this->hasOne(Internship::class);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isReviewed(): bool
    {
        return $this->status === self::STATUS_REVIEWED;
    }

    public function isAccepted(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function isApproved(): bool
    {
        return $this->isAccepted();
    }

    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function approve(): void
    {
        $this->update([
            'status' => self::STATUS_ACCEPTED,
            'reviewed_at' => now(),
        ]);
    }

    public function reject(?string $reason = null): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }

    public function canBeReviewed(): bool
    {
        return $this->isPending();
    }

    public function hasInternship(): bool
    {
        return $this->internship()->exists();
    }

    public function getWaitingDaysAttribute(): int
    {
        return $this->applied_at->diffInDays(now());
    }

    public function isReviewExpired(): bool
    {
        return $this->applied_at->diffInDays(now()) > 7;
    }

    public function getApplicantNameAttribute(): string
    {
        return $this->user?->name ?? 'Nama tidak tersedia';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'Menunggu Review',
            self::STATUS_REVIEWED => 'Sudah Direview',
            self::STATUS_ACCEPTED => 'Diterima',
            self::STATUS_REJECTED => 'Ditolak',
            self::STATUS_CANCELLED => 'Dibatalkan',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'bg-yellow-500',
            self::STATUS_REVIEWED => 'bg-sky-500',
            self::STATUS_ACCEPTED => 'bg-green-500',
            self::STATUS_REJECTED => 'bg-red-500',
            self::STATUS_CANCELLED => 'bg-gray-500',
            default => 'bg-gray-500',
        };
    }

    public function scopePriorityOrder(Builder $query): Builder
    {
        return $query
            ->leftJoin('users as applicants', 'applications.user_id', '=', 'applicants.id')
            ->orderByRaw("
                CASE
                    WHEN applicants.plan_type = 'premium'
                     AND applicants.premium_until IS NOT NULL
                     AND applicants.premium_until > ?
                    THEN 1 ELSE 0
                END DESC
            ", [now()])
            ->orderByDesc('applications.applied_at')
            ->select('applications.*');
    }
}
