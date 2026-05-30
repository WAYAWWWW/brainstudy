<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentExam extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'exam_set_id',
        'status',
        'started_at',
        'finished_at',
        'nilai_akhir',
        'total_benar',
        'total_soal',
        'lulus',
        'ip_address',
        'durasi_pengerjaan_detik',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'lulus' => 'boolean',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function examSet()
    {
        return $this->belongsTo(ExamSet::class, 'exam_set_id');
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'student_exam_id');
    }

    // Methods
    public function isStarted()
    {
        return $this->status === 'sedang_dikerjakan';
    }

    public function isFinished()
    {
        return $this->status === 'selesai';
    }

    public function getDurationMinutesAttribute()
    {
        if ($this->durasi_pengerjaan_detik) {
            return ceil($this->durasi_pengerjaan_detik / 60);
        }
        return 0;
    }
}
