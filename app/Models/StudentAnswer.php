<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAnswer extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_exam_id',
        'question_id',
        'jawaban_dipilih',
        'jawaban_essay',
        'is_correct',
        'nilai_didapat',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    // Relationships
    public function studentExam()
    {
        return $this->belongsTo(StudentExam::class, 'student_exam_id');
    }

    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function selectedOption()
    {
        return $this->belongsTo(QuestionOption::class, 'jawaban_dipilih');
    }
}
