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

        // Core permissions - Clean and organized
        $permissions = [
            // User Management
            'view users',
            'create users',
            'update users',
            'delete users',
            'restore users',
            'force delete users',
            'manage students',

            // Exam Management
            'view exams',
            'view any exams',
            'create exams',
            'update exams',
            'delete exams',
            'restore exams',
            'force delete exams',
            'assign exams',
            'correct exams',
            'view exam results',

            // Group Management
            'view groups',
            'create groups',
            'update groups',
            'delete groups',

            // Level Management
            'view levels',
            'create levels',
            'update levels',
            'delete levels',

            // Role & Permission Management
            'view roles',
            'create roles',
            'update roles',
            'delete roles',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin Role - All permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions(Permission::all());

        // Admin Role - User, Group, Level, and Role management
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            // Users
            'view users',
            'create users',
            'update users',
            'delete users',
            'manage students',

            // Exams (view only)
            'view exams',
            'view any exams',
            'view exam results',

            // Groups
            'view groups',
            'create groups',
            'update groups',
            'delete groups',

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
        ]);

        // Teacher Role - Exam creation, assignment and correction
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $teacherRole->syncPermissions([
            // Exams (own exams only)
            'view exams',
            'create exams',
            'update exams',
            'delete exams',
            'assign exams',
            'correct exams',
            'view exam results',

            // Groups (view only)
            'view groups',
        ]);

        // Student Role - Exam taking only
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $studentRole->syncPermissions([
            'view exams',
        ]);

        $this->command->info('All roles and permissions created successfully!');
        $this->command->info('Permission counts:');
        $this->command->info("   - Super Admin: {$superAdminRole->permissions->count()} permissions");
        $this->command->info("   - Admin: {$adminRole->permissions->count()} permissions");
        $this->command->info("   - Teacher: {$teacherRole->permissions->count()} permissions");
        $this->command->info("   - Student: {$studentRole->permissions->count()} permissions");
    }
}
