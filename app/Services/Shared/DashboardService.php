<?php

namespace App\Services\Shared;

use App\Models\User;
use App\Helpers\PermissionHelper;
use Illuminate\Support\Facades\Auth;

class DashboardService
{
    /**
     * Déterminer le type de dashboard selon le rôle de l'utilisateur
     */
    public function getDashboardType(?User $user = null): string
    {
        $user = $user ?? Auth::user();

        if (!$user) {
            throw new \Exception('Utilisateur non authentifié');
        }

        $role = PermissionHelper::getUserDashboardType();

        if (!$role) {
            throw new \Exception('Aucun rôle assigné à l\'utilisateur');
        }

        return $role;
    }

    /**
     * Obtenir l'URL de redirection appropriée selon le rôle
     */
    public function getDashboardRoute(?User $user = null): string
    {
        $role = $this->getDashboardType($user);

        return match ($role) {
            'super_admin' => 'admin.dashboard',
            'admin' => 'admin.dashboard',
            'teacher' => 'teacher.dashboard',
            'student' => 'student.dashboard',
            default => throw new \Exception('Rôle non reconnu: ' . $role)
        };
    }

    /**
     * Vérifier si l'utilisateur a accès au type de dashboard demandé
     */
    public function canAccessDashboard(string $dashboardType, ?User $user = null): bool
    {
        $userRole = $this->getDashboardType($user);

        // Super admin peut accéder au dashboard admin
        if ($userRole === 'super_admin' && $dashboardType === 'admin') {
            return true;
        }

        return $userRole === $dashboardType;
    }
}
