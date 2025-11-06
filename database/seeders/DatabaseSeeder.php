<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Ordre d'exécution :
     * 1. Rôles et permissions
     * 2. Niveaux scolaires
     * 3. Utilisateurs (admin, teachers, students)
     * 4. Groupes avec affectation des étudiants
     * 5. Examens avec questions et choix
     * 6. Assignation des examens aux groupes
     */
    public function run(): void
    {
        $this->call([
            // 1. Configuration de base
            RoleAndPermissionSeeder::class,
            LevelSeeder::class,

            // 2. Utilisateurs (admin + teachers + students)
            UserSeeder::class,

            // 3. Groupes (affecte automatiquement les étudiants)
            GroupSeeder::class,

            // 4. Examens (créé les questions et choix)
            ExamSeeder::class,

            // 5. Assigner les examens aux groupes
            ExamGroupAssignmentSeeder::class,
        ]);
    }
}
