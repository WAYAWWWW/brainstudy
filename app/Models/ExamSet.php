<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'guru_id',
        'judul',
        'mata_pelajaran',
        'kelas_target',
        'deskripsi',
        'token',
        'durasi_menit',
        'nilai_kkm',
        'status',
        'tanggal_mulai',
        'tanggal_selesai',
        'acak_soal',
        'acak_jawaban',
        'max_attempt',
        'show_leaderboard',
    ];

    protected $casts = [
        'tanggal_mulai' => 'datetime',
        'tanggal_selesai' => 'datetime',
        'acak_soal' => 'boolean',
        'acak_jawaban' => 'boolean',
        'show_leaderboard' => 'boolean',
    ];

    // Relationships
    public function guru()
    {
        return $this->belongsTo(User::class, 'guru_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class, 'exam_set_id')->orderBy('urutan');
    }

    public function studentExams()
    {
        return $this->hasMany(StudentExam::class, 'exam_set_id');
    }

    // Methods
    public function isActive()
    {
        return $this->status === 'aktif';
    }

    public function isDraft()
    {
        return $this->status === 'draft';
    }

    public function generateToken()
    {
        $this->token = strtoupper(\Str::random(6));
        return $this->token;
    }

    public function getTotalNilaiAttribute()
    {
        return $this->questions()->sum('nilai_soal');
    }

    public function getAverageScoreAttribute()
    {
        return round($this->studentExams()->whereNotNull('nilai_akhir')->avg('nilai_akhir'), 2);
    }
}
