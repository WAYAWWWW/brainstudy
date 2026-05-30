<?php

namespace App\Policies;

use App\Models\User;
use App\Models\StudentExam;
use Illuminate\Auth\Access\Response;

class StudentExamPolicy
{
    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, StudentExam $studentExam): bool
    {
        // Guru can view any student exam from their exam sets
        if ($user->role === 'guru') {
            return $user->id === $studentExam->examSet->guru_id;
        }

        // Student can only view their own exam
        return $user->id === $studentExam->user_id;
    }
}
