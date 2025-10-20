<?php

namespace Database\Seeders;

use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer quelques groupes pour l'année académique actuelle
        $currentYear = Carbon::now()->year;
        $academicYear = Carbon::now()->month >= 9
            ? "{$currentYear}-" . ($currentYear + 1)
            : ($currentYear - 1) . "-{$currentYear}";

        // Récupérer les niveaux existants
        $bts1 = \App\Models\Level::where('code', 'bts_1')->first();
        $bts2 = \App\Models\Level::where('code', 'bts_2')->first();
        $licence1 = \App\Models\Level::where('code', 'licence_1')->first();
        $licence2 = \App\Models\Level::where('code', 'licence_2')->first();

        $groups = [
            [
                'level_id' => $bts1->id,
                'start_date' => Carbon::parse('2024-09-01'),
                'end_date' => Carbon::parse('2025-06-30'),
                'max_students' => 25,
                'is_active' => true,
                'academic_year' => $academicYear,
            ],
            [
                'level_id' => $bts1->id,
                'start_date' => Carbon::parse('2024-09-01'),
                'end_date' => Carbon::parse('2025-06-30'),
                'max_students' => 25,
                'is_active' => true,
                'academic_year' => $academicYear,
            ],
            [
                'level_id' => $bts2->id,
                'start_date' => Carbon::parse('2024-09-01'),
                'end_date' => Carbon::parse('2025-06-30'),
                'max_students' => 30,
                'is_active' => true,
                'academic_year' => $academicYear,
            ],
            [
                'level_id' => $licence1->id,
                'start_date' => Carbon::parse('2024-09-01'),
                'end_date' => Carbon::parse('2025-06-30'),
                'max_students' => 20,
                'is_active' => true,
                'academic_year' => $academicYear,
            ],
        ];

        foreach ($groups as $groupData) {
            Group::create($groupData);
        }

        // Assigner des étudiants existants aux groupes
        $students = User::role('student')->get();
        $createdGroups = Group::all();

        if ($students->isNotEmpty() && $createdGroups->isNotEmpty()) {
            foreach ($students as $student) {
                $randomGroup = $createdGroups->random();

                // Vérifier si le groupe a encore de la place
                if ($randomGroup->activeStudents()->count() < $randomGroup->max_students) {
                    $student->groups()->attach($randomGroup->id, [
                        'enrolled_at' => Carbon::now()->subDays(rand(1, 30)),
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
