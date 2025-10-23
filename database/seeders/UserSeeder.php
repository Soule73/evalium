<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
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
                'name' => 'Dr. Marie Dupont',
                'email' => 'marie.dupont@examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Prof. Jean Martin',
                'email' => 'jean.martin@examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Dr. Sophie Bernard',
                'email' => 'sophie.bernard@examena.com',
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

        $students = [
            [
                'name' => 'Alice Johnson',
                'email' => 'alice.johnson@student.examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Bob Smith',
                'email' => 'bob.smith@student.examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Claire Davis',
                'email' => 'claire.davis@student.examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'David Wilson',
                'email' => 'david.wilson@student.examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Emma Brown',
                'email' => 'emma.brown@student.examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Frank Miller',
                'email' => 'frank.miller@student.examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Grace Taylor',
                'email' => 'grace.taylor@student.examena.com',
                'password' => Hash::make('password'),
            ],
            [
                'name' => 'Henry Anderson',
                'email' => 'henry.anderson@student.examena.com',
                'password' => Hash::make('password'),
            ],
        ];

        foreach ($students as $studentData) {
            $student = \App\Models\User::create(array_merge($studentData, [
                'email_verified_at' => now(),
                'avatar' => null,
                'active' => true,
            ]));
            $student->assignRole('student');
        }

        echo "Utilisateurs créés avec succès !\n";
        echo "- 1 administrateur\n";
        echo "- " . count($teachers) . " enseignants\n";
        echo "- " . count($students) . " étudiants\n";
    }
}
