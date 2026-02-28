<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Super Admin
        $superAdmin = \App\Models\User::create([
            'name' => 'Super Administrator',
            'email' => 'superadmin@evalium.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'avatar' => null,
            'is_active' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        // Create a regular Admin
        $admin = \App\Models\User::create([
            'name' => 'System Admin',
            'email' => 'admin@evalium.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'avatar' => null,
            'is_active' => true,
        ]);
        $admin->assignRole('admin');

        $teachers = [
            [
                'name' => 'Prof. Mathematics',
                'email' => 'math.teacher@evalium.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Prof. Physics',
                'email' => 'physics.teacher@evalium.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Prof. Computer Science',
                'email' => 'cs.teacher@evalium.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Prof. English',
                'email' => 'english.teacher@evalium.com',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($teachers as $teacherData) {
            $teacher = \App\Models\User::create(array_merge($teacherData, [
                'email_verified_at' => now(),
                'avatar' => null,
                'is_active' => true,
            ]));
            $teacher->assignRole('teacher');
        }

        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $students[] = [
                'name' => "Student {$i}",
                'email' => "student{$i}@evalium.com",
                'password' => Hash::make('password'),
            ];
        }

        foreach ($students as $studentData) {
            $student = \App\Models\User::create(array_merge($studentData, [
                'email_verified_at' => now(),
                'avatar' => null,
                'is_active' => true,
            ]));
            $student->assignRole('student');
        }

        $this->command->info('Users created successfully!');
        $this->command->info('- 1 super administrator (superadmin@evalium.com)');
        $this->command->info('- 1 administrator (admin@evalium.com)');
        $this->command->info('- '.count($teachers).' teachers');
        $this->command->info('- '.count($students).' students');
        $this->command->info('- Password for all: password');
    }
}
