<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\Group;
use Illuminate\Database\Seeder;

class ExamGroupAssignmentSeeder extends Seeder
{
    /**
     * Assigner les examens aux groupes (via la table pivot exam_group).
     */
    public function run(): void
    {
        $exams = Exam::where('is_active', true)->get();

        $groups = Group::where('is_active', true)->get();

        if ($exams->isEmpty()) {
            return;
        }

        if ($groups->isEmpty()) {
            return;
        }

        foreach ($exams as $exam) {
            // Assigner chaque examen à 1-3 groupes aléatoires
            $groupsToAssign = $groups->random(rand(1, min(3, $groups->count())));

            foreach ($groupsToAssign as $group) {
                // Vérifier si l'assignation existe déjà
                $exists = $exam->groups()->where('group_id', $group->id)->exists();

                if (! $exists) {
                    $exam->groups()->attach($group->id, [
                        'assigned_by' => $exam->teacher_id,
                        'assigned_at' => now()->subDays(rand(1, 10)),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}
