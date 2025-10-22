<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class StudentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $studentRole = Role::firstOrCreate(['name' => 'student']);

        $students = [
            ['name' => 'Alice Martin', 'email' => 'alice.martin@student.examena.com'],
            ['name' => 'Bob Dupont', 'email' => 'bob.dupont@student.examena.com'],
            ['name' => 'Claire Moreau', 'email' => 'claire.moreau@student.examena.com'],
            ['name' => 'David Leroy', 'email' => 'david.leroy@student.examena.com'],
            ['name' => 'Emma Bernard', 'email' => 'emma.bernard@student.examena.com'],
            ['name' => 'François Petit', 'email' => 'francois.petit@student.examena.com'],
            ['name' => 'Gabrielle Roux', 'email' => 'gabrielle.roux@student.examena.com'],
            ['name' => 'Hugo Lambert', 'email' => 'hugo.lambert@student.examena.com'],
            ['name' => 'Isabelle Morel', 'email' => 'isabelle.morel@student.examena.com'],
            ['name' => 'Julien Fournier', 'email' => 'julien.fournier@student.examena.com'],
            ['name' => 'Karine Girard', 'email' => 'karine.girard@student.examena.com'],
            ['name' => 'Louis Bonnet', 'email' => 'louis.bonnet@student.examena.com'],
            ['name' => 'Marine Dubois', 'email' => 'marine.dubois@student.examena.com'],
            ['name' => 'Nicolas André', 'email' => 'nicolas.andre@student.examena.com'],
            ['name' => 'Olivia Rousseau', 'email' => 'olivia.rousseau@student.examena.com'],
        ];

        foreach ($students as $studentData) {
            $student = User::factory()->create($studentData);
            $student->assignRole('student');
        }
    }
}
