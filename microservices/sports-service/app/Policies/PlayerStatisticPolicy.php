<?php

namespace App\Policies;

use App\Models\PlayerStatistic;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PlayerStatisticPolicy
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
    public function view(User $user, PlayerStatistic $playerStatistic): bool
    {
        // Admin can view all statistics
        if ($user->role === 'admin') {
            return true;
        }

        // Users can only view statistics from their school
        if ($user->school_id !== $playerStatistic->school_id) {
            return false;
        }

        // Coach and director can view statistics from their school
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
    public function update(User $user, PlayerStatistic $playerStatistic): bool
    {
        // Admin can update all statistics
        if ($user->role === 'admin') {
            return true;
        }

        // Users can only update statistics from their school
        if ($user->school_id !== $playerStatistic->school_id) {
            return false;
        }

        // Only the recorder or director can update the statistic
        return $user->id === $playerStatistic->recorded_by || $user->role === 'director';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PlayerStatistic $playerStatistic): bool
    {
        // Admin can delete all statistics
        if ($user->role === 'admin') {
            return true;
        }

        // Users can only delete statistics from their school
        if ($user->school_id !== $playerStatistic->school_id) {
            return false;
        }

        // Only the recorder or director can delete the statistic
        return $user->id === $playerStatistic->recorded_by || $user->role === 'director';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PlayerStatistic $playerStatistic): bool
    {
        return $this->delete($user, $playerStatistic);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PlayerStatistic $playerStatistic): bool
    {
        return $user->role === 'admin';
    }
}