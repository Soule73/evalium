<?php

declare(strict_types=1);

namespace App\Services\Core;

use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Service for handling role-based dashboard redirection logic.
 *
 * Centralizes the logic for determining which dashboard a user should access
 * based on their role, eliminating duplication across controllers.
 */
class RoleBasedRedirectService
{
    /**
     * Get the dashboard type for the authenticated user.
     *
     * @return string|null Returns 'admin', 'teacher', 'student', or null if not authenticated
     */
    public function getDashboardType(?User $user = null): ?string
    {
        /** @var User $user */
        $user = $user ?? Auth::user();

        if (! $user) {
            return null;
        }

        if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
            return 'admin';
        }

        if ($user->hasRole('teacher')) {
            return 'teacher';
        }

        if ($user->hasRole('student')) {
            return 'student';
        }

        return null;
    }

    /**
     * Get the dashboard route URL for the authenticated user.
     *
     * @return string The dashboard route URL based on user's role
     *
     * @throws \RuntimeException When user has no valid role
     */
    public function getDashboardRoute(?User $user = null): string
    {
        $type = $this->getDashboardType($user);

        return match ($type) {
            'admin', 'student' => route('dashboard'),
            'teacher' => route('teacher.dashboard'),
            default => throw new \RuntimeException(__('messages.user_has_no_valid_role'))
        };
    }

    /**
     * Check if user has access to admin dashboard.
     *
     * @return bool True if user is admin or super_admin
     */
    public function isAdmin(?User $user = null): bool
    {
        /** @var User $user */
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('admin') || $user->hasRole('super_admin');
    }

    /**
     * Check if user has access to teacher dashboard.
     *
     * @return bool True if user is a teacher
     */
    public function isTeacher(?User $user = null): bool
    {
        /** @var User $user */
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('teacher');
    }

    /**
     * Check if user has access to student dashboard.
     *
     * @return bool True if user is a student
     */
    public function isStudent(?User $user = null): bool
    {
        /** @var User $user */
        $user = $user ?? Auth::user();

        if (! $user) {
            return false;
        }

        return $user->hasRole('student');
    }
}
