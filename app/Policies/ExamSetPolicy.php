<?php

namespace App\Policies;

use App\Models\User;
use App\Models\ExamSet;
use Illuminate\Auth\Access\Response;

class ExamSetPolicy
{
    /**
     * Determine if the user can view the model.
     */
    public function view(User $user, ExamSet $examSet): bool
    {
        return $user->id === $examSet->guru_id && $user->role === 'guru';
    }

    /**
     * Determine if the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === 'guru';
    }

    /**
     * Determine if the user can update the model.
     */
    public function update(User $user, ExamSet $examSet): bool
    {
        return $user->id === $examSet->guru_id && $user->role === 'guru';
    }

    /**
     * Determine if the user can delete the model.
     */
    public function delete(User $user, ExamSet $examSet): bool
    {
        return $user->id === $examSet->guru_id && $user->role === 'guru';
    }
}
