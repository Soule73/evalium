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
        // Créer le Super Admin
        $superAdmin = \App\Models\User::create([
            'name' => 'Super Administrateur',
            'email' => 'superadmin@examena.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'avatar' => null,
            'active' => true,
        ]);
        $superAdmin->assignRole('super_admin');

        // Créer un Admin normal
        $admin = \App\Models\User::create([
            'name' => 'Admin Système',
            'email' => 'admin@examena.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'avatar' => null,
            'active' => true,
        ]);
        $admin->assignRole('admin');

        $teachers = [
            [
                'name' => 'Prof. Mathematics',
                'email' => 'math.teacher@examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Prof. Physics',
                'email' => 'physics.teacher@examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Prof. Computer Science',
                'email' => 'cs.teacher@examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Prof. English',
                'email' => 'english.teacher@examena.com',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($teachers as $teacherData) {
            $teacher = \App\Models\User::create(array_merge($teacherData, [
                'email_verified_at' => now(),
                'avatar' => null,
                'active' => true,
            ]));
            $teacher->assignRole('teacher');
        }

        $students = [];
        for ($i = 1; $i <= 20; $i++) {
            $students[] = [
                'name' => "Student {$i}",
                'email' => "student{$i}@examena.com",
                'password' => Hash::make('password'),
            ];
        }

        foreach ($students as $studentData) {
            $student = \App\Models\User::create(array_merge($studentData, [
                'email_verified_at' => now(),
                'avatar' => null,
                'active' => true,
            ]));
            $student->assignRole('student');
        }

        $this->command->info('Utilisateurs créés avec succès !');
        $this->command->info('- 1 super administrateur (superadmin@examena.com)');
        $this->command->info('- 1 administrateur (admin@examena.com)');
        $this->command->info('- '.count($teachers).' enseignants');
        $this->command->info('- '.count($students).' étudiants');
        $this->command->info('- Mot de passe pour tous: password');
    }
}
