<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\ClassSubject;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    /**
     * Create sample assessments for each class subject.
     */
    public function run(): void
    {
        $classSubjects = ClassSubject::with(['teacher', 'class', 'subject'])->get();

        if ($classSubjects->isEmpty()) {
            $this->command->error('✗ No class subjects found. Run ClassSubjectSeeder first.');
            return;
        }

        $assessmentTypes = [
            ['type' => 'devoir', 'coefficient' => 1.0, 'title_suffix' => 'Devoir Maison'],
            ['type' => 'examen', 'coefficient' => 2.0, 'title_suffix' => 'Examen'],
            ['type' => 'tp', 'coefficient' => 1.5, 'title_suffix' => 'TP'],
        ];

        $count = 0;
        foreach ($classSubjects as $classSubject) {
            foreach ($assessmentTypes as $assessmentData) {
                Assessment::create([
                    'class_subject_id' => $classSubject->id,
                    'teacher_id' => $classSubject->teacher_id,
                    'title' => $classSubject->subject->name . ' - ' . $assessmentData['title_suffix'],
                    'description' => 'Évaluation de ' . $classSubject->subject->name . ' pour ' . $classSubject->class->display_name,
                    'type' => $assessmentData['type'],
                    'coefficient' => $assessmentData['coefficient'],
                    'duration_minutes' => $assessmentData['type'] === 'examen' ? 120 : 60,
                    'scheduled_at' => now()->addDays(rand(7, 30)),
                    'settings' => [],
                ]);
                $count++;
            }
        }

        $this->command->info("✓ {$count} Assessments created across all class subjects");
    }
}
