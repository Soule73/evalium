<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed users with realistic francophone names for demo purposes.
     */
    public function run(): void
    {
        $hashedPassword = Hash::make('password');
        $baseAttrs = [
            'password' => $hashedPassword,
            'email_verified_at' => now(),
            'avatar' => null,
            'is_active' => true,
        ];

        $superAdmin = User::create(array_merge($baseAttrs, [
            'name' => 'Jean-Pierre Moreau',
            'email' => 'superadmin@evalium.com',
        ]));
        $superAdmin->assignRole('super_admin');

        $admin = User::create(array_merge($baseAttrs, [
            'name' => 'Nathalie Girard',
            'email' => 'admin@evalium.com',
        ]));
        $admin->assignRole('admin');

        $teachers = [
            ['name' => 'Dr. Laurent Dupont', 'email' => 'l.dupont@evalium.com'],
            ['name' => 'Dr. Sophie Martin', 'email' => 's.martin@evalium.com'],
            ['name' => 'Dr. Marc Lefebvre', 'email' => 'm.lefebvre@evalium.com'],
            ['name' => 'Dr. Isabelle Rousseau', 'email' => 'i.rousseau@evalium.com'],
            ['name' => 'Dr. Philippe Bernard', 'email' => 'p.bernard@evalium.com'],
            ['name' => 'Dr. Claire Fontaine', 'email' => 'c.fontaine@evalium.com'],
        ];

        foreach ($teachers as $data) {
            $teacher = User::create(array_merge($baseAttrs, $data));
            $teacher->assignRole('teacher');
        }

        $students = [
            'Amine Belkacem', 'Camille Durand', 'Youssef El Amrani', 'Emma Petit',
            'Mehdi Benali', 'Lea Richard', 'Karim Hadj', 'Chloe Moreau',
            'Sofiane Boudjema', 'Manon Lefevre', 'Rayan Cherif', 'Juliette Lambert',
            'Nassim Khelifi', 'Sarah Dubois', 'Adam Meziane', 'Clara Bernard',
            'Bilal Aouadi', 'Louise Fontaine', 'Omar Hamidi', 'Marie Laurent',
            'Walid Bouzid', 'Alice Mercier', 'Ismael Djebbar', 'Pauline Garnier',
            'Farid Mekhloufi', 'Ines Roux', 'Zakaria Taleb', 'Julie Faure',
            'Nabil Rahmani', 'Margaux Simon', 'Hamza Slimani', 'Elise Gauthier',
            'Yanis Benmoussa', 'Oceane Perrin', 'Ayoub Larbi', 'Charlotte Blanc',
            'Rachid Boukhalfa', 'Valentine Clement', 'Ilyes Saidi', 'Amandine Chevalier',
            'Mourad Ziani', 'Lucie Noël', 'Adel Ferhat', 'Anais Morel',
            'Tarek Mansouri',
        ];

        foreach ($students as $index => $name) {
            $emailSlug = str_replace(' ', '.', mb_strtolower($this->removeAccents($name)));
            $student = User::create(array_merge($baseAttrs, [
                'name' => $name,
                'email' => "{$emailSlug}@evalium.com",
            ]));
            $student->assignRole('student');
        }

        $this->command->info(count($teachers).' teachers + '.count($students).' students created');
    }

    /**
     * Remove accented characters for email generation.
     */
    private function removeAccents(string $string): string
    {
        return strtr($string, [
            'e' => 'e', 'e' => 'e', 'e' => 'e', 'e' => 'e',
            'a' => 'a', 'a' => 'a', 'i' => 'i', 'i' => 'i',
            'o' => 'o', 'o' => 'o', 'u' => 'u', 'u' => 'u',
            'c' => 'c', 'n' => 'n',
            'E' => 'E', 'E' => 'E', 'E' => 'E', 'E' => 'E',
            'A' => 'A', 'A' => 'A', 'I' => 'I', 'I' => 'I',
            'O' => 'O', 'O' => 'O', 'U' => 'U', 'U' => 'U',
            'C' => 'C', 'N' => 'N',
        ]);
    }
}
