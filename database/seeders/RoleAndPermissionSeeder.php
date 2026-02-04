<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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

            // Academic Year Management
            'view academic years',
            'create academic years',
            'update academic years',
            'delete academic years',
            'archive academic years',

            // Subject Management
            'view subjects',
            'create subjects',
            'update subjects',
            'delete subjects',

            // Class Management
            'view classes',
            'create classes',
            'update classes',
            'delete classes',

            // Enrollment Management
            'view enrollments',
            'create enrollments',
            'update enrollments',
            'delete enrollments',
            'transfer enrollments',

            // ClassSubject Management
            'view class subjects',
            'create class subjects',
            'update class subjects',
            'delete class subjects',
            'replace teacher class subjects',

            // Assessment Management
            'view assessments',
            'create assessments',
            'update assessments',
            'delete assessments',
            'publish assessments',
            'grade assessments',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create roles and assign permissions

        // Super Admin Role - All permissions
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $superAdminRole->syncPermissions(Permission::all());

        // Admin Role - User, Level, and Role management + MCD modules
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions([
            // Users
            'view users',
            'create users',
            'update users',
            'delete users',
            'manage students',

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

            // Academic Years
            'view academic years',
            'create academic years',
            'update academic years',
            'delete academic years',
            'archive academic years',

            // Subjects
            'view subjects',
            'create subjects',
            'update subjects',
            'delete subjects',

            // Classes
            'view classes',
            'create classes',
            'update classes',
            'delete classes',

            // Enrollments
            'view enrollments',
            'create enrollments',
            'update enrollments',
            'delete enrollments',
            'transfer enrollments',

            // ClassSubjects
            'view class subjects',
            'create class subjects',
            'update class subjects',
            'delete class subjects',
            'replace teacher class subjects',

            // Assessments (view only for admin)
            'view assessments',
        ]);

        // Teacher Role - Assessment management
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);
        $teacherRole->syncPermissions([
            // Classes (view only - assigned classes)
            'view classes',

            // Assessments (full CRUD for own assessments)
            'view assessments',
            'create assessments',
            'update assessments',
            'delete assessments',
            'publish assessments',
            'grade assessments',
        ]);

        // Student Role - Assessment taking
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $studentRole->syncPermissions([
            'view assessments',
        ]);

        $this->command->info('All roles and permissions created successfully!');
        $this->command->info('Permission counts:');
        $this->command->info("   - Super Admin: {$superAdminRole->permissions->count()} permissions");
        $this->command->info("   - Admin: {$adminRole->permissions->count()} permissions");
        $this->command->info("   - Teacher: {$teacherRole->permissions->count()} permissions");
        $this->command->info("   - Student: {$studentRole->permissions->count()} permissions");
    }
}
