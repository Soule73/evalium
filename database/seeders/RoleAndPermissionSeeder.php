<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer toutes les permissions pour tous les models
        $permissions = [
            // User permissions
            'view users',
            'create users',
            'update users',
            'delete users',
            'restore users',
            'force delete users',
            'manage students',
            'manage teachers',
            'manage admins',
            'toggle user status',

            // Exam permissions
            'view exams',
            'view any exams',
            'create exams',
            'update exams',
            'delete exams',
            'restore exams',
            'force delete exams',
            'publish exams',
            'assign exams',
            'correct exams',
            'grade exams',
            'view exam results',

            // Question permissions
            'view questions',
            'create questions',
            'update questions',
            'delete questions',

            // Answer permissions
            'view answers',
            'create answers',
            'update answers',
            'delete answers',
            'grade answers',

            // ExamAssignment permissions
            'view assignments',
            'create assignments',
            'update assignments',
            'delete assignments',
            'submit assignments',
            'grade assignments',

            // Group permissions
            'view groups',
            'create groups',
            'update groups',
            'delete groups',
            'manage group students',
            'assign group exams',
            'toggle group status',

            // Level permissions
            'view levels',
            'create levels',
            'update levels',
            'delete levels',

            // Role & Permission management
            'view roles',
            'create roles',
            'update roles',
            'delete roles',
            'assign permissions',
            'view permissions',
            'create permissions',
            'delete permissions',

            // Dashboard & Reports
            'view admin dashboard',
            'view teacher dashboard',
            'view student dashboard',
            'view reports',
            'export reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Créer les rôles et assigner les permissions

        // Rôle Super Admin - Toutes les permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions(Permission::all());

        // Rôle Admin - Gestion des utilisateurs, groupes, niveaux
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            // Users
            'view users',
            'create users',
            'update users',
            'delete users',
            'manage students',
            'manage teachers',
            'toggle user status',

            // Exams (view only)
            'view exams',
            'view any exams',
            'view exam results',

            // Groups
            'view groups',
            'create groups',
            'update groups',
            'delete groups',
            'manage group students',
            'assign group exams',
            'toggle group status',

            // Levels
            'view levels',
            'create levels',
            'update levels',
            'delete levels',

            // Roles
            'view roles',
            'create roles',
            'update roles',
            'delete roles',

            // Assignments
            'view assignments',

            // Dashboard
            'view admin dashboard',
            'view reports',
        ]);

        // Rôle Teacher - Gestion des examens et corrections
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $teacherRole->syncPermissions([
            // Exams (own exams only - NOT 'view any exams')
            'view exams',
            'create exams',
            'update exams',
            'delete exams',
            'publish exams',
            'assign exams',
            'assign group exams', // Permission pour assigner aux groupes
            'correct exams',
            'grade exams',
            'view exam results',

            // Questions
            'view questions',
            'create questions',
            'update questions',
            'delete questions',

            // Assignments
            'view assignments',
            'create assignments',
            'update assignments',
            'grade assignments',

            // Answers
            'view answers',
            'grade answers',

            // Groups (view only)
            'view groups',

            // Dashboard
            'view teacher dashboard',
            'view reports',
            'export reports',
        ]);

        // Rôle Student - Passage d'examens uniquement
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $studentRole->syncPermissions([
            // Exams (assigned only)
            'view exams',

            // Assignments
            'view assignments',
            'submit assignments',

            // Answers
            'view answers',
            'create answers',
            'update answers',

            // Dashboard
            'view student dashboard',
        ]);

        $this->command->info('All roles and permissions created successfully!');
        $this->command->info('Permission counts:');
        $this->command->info("   - Super Admin: {$superAdminRole->permissions->count()} permissions");
        $this->command->info("   - Admin: {$adminRole->permissions->count()} permissions");
        $this->command->info("   - Teacher: {$teacherRole->permissions->count()} permissions");
        $this->command->info("   - Student: {$studentRole->permissions->count()} permissions");
    }
}
