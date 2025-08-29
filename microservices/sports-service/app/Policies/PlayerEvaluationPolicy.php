<?php

namespace App\Policies;

use App\Models\PlayerEvaluation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PlayerEvaluationPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['admin', 'coach', 'director']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PlayerEvaluation $playerEvaluation): bool
    {
        // Admin can view all evaluations
        if ($user->role === 'admin') {
            return true;
        }

        // Users can only view evaluations from their school
        if ($user->school_id !== $playerEvaluation->school_id) {
            return false;
        }

        // Coach and director can view evaluations from their school
        return in_array($user->role, ['coach', 'director']);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, ['admin', 'coach', 'director']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PlayerEvaluation $playerEvaluation): bool
    {
        // Admin can update all evaluations
        if ($user->role === 'admin') {
            return true;
        }

        // Users can only update evaluations from their school
        if ($user->school_id !== $playerEvaluation->school_id) {
            return false;
        }

        // Only the evaluator or director can update the evaluation
        return $user->id === $playerEvaluation->evaluator_id || $user->role === 'director';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PlayerEvaluation $playerEvaluation): bool
    {
        // Admin can delete all evaluations
        if ($user->role === 'admin') {
            return true;
        }

        // Users can only delete evaluations from their school
        if ($user->school_id !== $playerEvaluation->school_id) {
            return false;
        }

        // Only the evaluator or director can delete the evaluation
        return $user->id === $playerEvaluation->evaluator_id || $user->role === 'director';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlayerEvaluation $playerEvaluation): bool
    {
        return $this->delete($user, $playerEvaluation);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlayerEvaluation $playerEvaluation): bool
    {
        return $user->role === 'admin';
    }
}