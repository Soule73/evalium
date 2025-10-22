<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = \App\Models\User::create([
            'name' => 'Administrateur Test',
            'email' => 'admin@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $admin->assignRole('admin');

        $teacher1 = \App\Models\User::create([
            'name' => 'Marie Dupont',
            'email' => 'marie.dupont@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $teacher1->assignRole('teacher');

        $teacher2 = \App\Models\User::create([
            'name' => 'Jean Martin',
            'email' => 'jean.martin@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $teacher2->assignRole('teacher');

        $student1 = \App\Models\User::create([
            'name' => 'Alice Bernard',
            'email' => 'alice.bernard@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $student1->assignRole('student');

        $student2 = \App\Models\User::create([
            'name' => 'Pierre Durand',
            'email' => 'pierre.durand@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $student2->assignRole('student');

        $student3 = \App\Models\User::create([
            'name' => 'Sophie Lemoine',
            'email' => 'sophie.lemoine@test.com',
            'password' => \Illuminate\Support\Facades\Hash::make('password123'),
        ]);
        $student3->assignRole('student');

        echo "Utilisateurs de test créés avec succès!\n";
        echo "Admin: admin@test.com / password123\n";
        echo "Enseignants: marie.dupont@test.com, jean.martin@test.com / password123\n";
        echo "Étudiants: alice.bernard@test.com, pierre.durand@test.com, sophie.lemoine@test.com / password123\n";
    }
}
