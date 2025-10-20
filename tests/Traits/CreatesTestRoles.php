<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;

trait CreatesTestRoles
{
    /**
     * Créer tous les rôles nécessaires pour les tests
     */
    protected function createTestRoles(): void
    {
        if (!Role::where('name', 'admin')->exists()) {
            Role::create(['name' => 'admin']);
        }

        if (!Role::where('name', 'teacher')->exists()) {
            Role::create(['name' => 'teacher']);
        }

        if (!Role::where('name', 'student')->exists()) {
            Role::create(['name' => 'student']);
        }
    }

    /**
     * Créer un utilisateur avec un rôle spécifique
     */
    protected function createUserWithRole(string $role, array $attributes = []): \App\Models\User
    {
        $user = \App\Models\User::factory()->create($attributes);
        $user->assignRole($role);
        return $user;
    }
}
