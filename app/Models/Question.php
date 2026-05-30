<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_set_id',
        'urutan',
        'tipe_soal',
        'pertanyaan',
        'gambar',
        'nilai_soal',
    ];

    // Relationships
    public function examSet()
    {
        return $this->belongsTo(ExamSet::class, 'exam_set_id');
    }

    public function options()
    {
        return $this->hasMany(QuestionOption::class, 'question_id')->orderBy('urutan_opsi');
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'question_id');
    }

    // Methods
    public function getCorrectOption()
    {
        if ($this->tipe_soal === 'pilihan_ganda') {
            return $this->options()->where('is_correct', true)->first();
        }
        return null;
    }
}
