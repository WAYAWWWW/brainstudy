<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'teks_opsi',
        'gambar_opsi',
        'is_correct',
        'urutan_opsi',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
    ];

    // Relationships
    public function question()
    {
        return $this->belongsTo(Question::class, 'question_id');
    }

    public function answers()
    {
        return $this->hasMany(StudentAnswer::class, 'jawaban_dipilih');
    }
}
