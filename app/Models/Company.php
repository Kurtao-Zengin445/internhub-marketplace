<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'email',
        'contact_person',
        'contact_person_phone',
        'description',
        'industry',
        'logo',
        'website',
        'is_verified',
        'verification_document',
        'verified_at',
        'plan_type',
        'premium_until',
        'latitude',
        'longitude',
        'allowed_radius',
    ];

    protected $casts = [
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'allowed_radius' => 'integer',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'premium_until' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function programs()
    {
        return $this->hasMany(InternshipProgram::class);
    }

    public function applications()
    {
        return $this->hasManyThrough(
            Application::class,
            InternshipProgram::class,
            'company_id',
            'internship_program_id',
            'id',
            'id'
        );
    }

    public function subscriptions()
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function getActiveInternshipsCountAttribute(): int
    {
        return Internship::whereHas('application.program', function ($query) {
            $query->where('company_id', $this->id);
        })->where('status', 'active')->count();
    }

    public function getCompletedInternshipsCountAttribute(): int
    {
        return Internship::whereHas('application.program', function ($query) {
            $query->where('company_id', $this->id);
        })->where('status', 'completed')->count();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_verified ? 'Terverifikasi' : 'Menunggu Verifikasi';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_verified ? 'bg-green-500' : 'bg-yellow-500';
    }

    public function hasActivePremium(): bool
    {
        return $this->plan_type === 'premium'
            && $this->premium_until !== null
            && $this->premium_until->isFuture();
    }
}
