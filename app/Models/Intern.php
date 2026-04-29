<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Intern extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nisn',
        'nim',
        'institution',
        'education_level',
        'major',
        'phone',
        'address',
        'date_of_birth',
        'gender',
        'photo',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function application()
    {
        return $this->hasMany(Application::class, 'user_id', 'user_id');
    }

    public function applications()
    {
        return $this->application();
    }

    public function internships()
    {
        return $this->hasManyThrough(
            Internship::class,
            Application::class,
            'user_id',
            'application_id',
            'user_id',
            'id'
        );
    }

    public function activeInternship(): ?Internship
    {
        return $this->internships()
            ->where('internships.status', Internship::STATUS_ACTIVE)
            ->latest('internships.start_date')
            ->first();
    }

    public function getNisAttribute(): ?string
    {
        return $this->nim ?: $this->nisn;
    }

    public function getClassAttribute(): ?string
    {
        return $this->education_level;
    }

    public function getInstitutionLabelAttribute(): string
    {
        return $this->institution ?: 'Umum / Mandiri';
    }

    public function getBirthDateAttribute()
    {
        return $this->date_of_birth;
    }
}
