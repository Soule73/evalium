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
            ['type' => 'devoir', 'coefficient' => 1.0, 'title_suffix' => 'Quiz'],
            ['type' => 'examen', 'coefficient' => 2.0, 'title_suffix' => 'Midterm Exam'],
        ];

        $count = 0;
        $dayCounter = 7;
        foreach ($classSubjects as $classSubject) {
            foreach ($assessmentTypes as $assessmentData) {
                $assessment = Assessment::create([
                    'class_subject_id' => $classSubject->id,
                    'teacher_id' => $classSubject->teacher_id,
                    'title' => $classSubject->subject->name.' - '.$assessmentData['title_suffix'],
                    'description' => 'Assessment for '.$classSubject->subject->name,
                    'type' => $assessmentData['type'],
                    'coefficient' => $assessmentData['coefficient'],
                    'duration_minutes' => $assessmentData['type'] === 'examen' ? 90 : 45,
                    'scheduled_at' => now()->addDays($dayCounter),
                    'settings' => [],
                ]);

                $this->createSampleQuestions($assessment, $assessmentData['type']);

                $count++;
                $dayCounter = ($dayCounter % 20) + 5;
            }
        }

        $this->command->info("✓ {$count} Assessments created (2 per subject: Quiz + Midterm with sample questions)");
    }

    private function createSampleQuestions(Assessment $assessment, string $type): void
    {
        $questionsCount = $type === 'examen' ? 5 : 3;

        for ($i = 1; $i <= $questionsCount; $i++) {
            $question = $assessment->questions()->create([
                'content' => "Question {$i} for {$assessment->title}",
                'type' => $i === 1 ? 'text' : ($i === 2 ? 'one_choice' : 'multiple'),
                'points' => $type === 'examen' ? 4 : 2,
                'order_index' => $i,
            ]);

            if ($question->type === 'one_choice') {
                $question->choices()->createMany([
                    ['content' => 'Option A', 'is_correct' => true, 'order_index' => 1],
                    ['content' => 'Option B', 'is_correct' => false, 'order_index' => 2],
                    ['content' => 'Option C', 'is_correct' => false, 'order_index' => 3],
                ]);
            } elseif ($question->type === 'multiple') {
                $question->choices()->createMany([
                    ['content' => 'Option A', 'is_correct' => true, 'order_index' => 1],
                    ['content' => 'Option B', 'is_correct' => true, 'order_index' => 2],
                    ['content' => 'Option C', 'is_correct' => false, 'order_index' => 3],
                    ['content' => 'Option D', 'is_correct' => false, 'order_index' => 4],
                ]);
            }
        }
    }
}
