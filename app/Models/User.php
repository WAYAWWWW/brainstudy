<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'nip',
        'nisn',
        'kelas',
        'guru_id',
        'is_active',
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
        ];
    }

    // Relationships
    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function murid()
    {
        return $this->hasMany(User::class, 'guru_id');
    }

    public function examSets()
    {
        return $this->hasMany(ExamSet::class, 'guru_id');
    }

    public function studentExams()
    {
        return $this->hasMany(StudentExam::class, 'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function announcements()
    {
        return $this->hasMany(Announcement::class, 'guru_id');
    }

    // Scopes
    public function scopeGuru($query)
    {
        return $query->where('role', 'guru');
    }

    public function scopeMurid($query)
    {
        return $query->where('role', 'murid');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
