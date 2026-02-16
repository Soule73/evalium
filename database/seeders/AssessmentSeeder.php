<?php

namespace Database\Seeders;

use App\Enums\AssessmentType;
use App\Enums\DeliveryMode;
use App\Enums\QuestionType;
use App\Models\Assessment;
use App\Models\ClassSubject;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    /**
     * Create sample assessments for each class subject with both delivery modes.
     */
    public function run(): void
    {
        $classSubjects = ClassSubject::with(['teacher', 'class', 'subject'])->get();

        if ($classSubjects->isEmpty()) {
            $this->command->error('No class subjects found. Run ClassSubjectSeeder first.');

            return;
        }

        $assessmentTypes = [
            ['type' => AssessmentType::Exam, 'coefficient' => 2.0, 'title_suffix' => 'Midterm Exam'],
            ['type' => AssessmentType::Homework, 'coefficient' => 1.0, 'title_suffix' => 'Quiz'],
            ['type' => AssessmentType::Project, 'coefficient' => 1.5, 'title_suffix' => 'Project'],
        ];

        $count = 0;
        $dayCounter = 7;
        foreach ($classSubjects as $classSubject) {
            foreach ($assessmentTypes as $assessmentData) {
                $deliveryMode = DeliveryMode::defaultForType($assessmentData['type']);
                $isSupervisedMode = $deliveryMode === DeliveryMode::Supervised;

                $assessment = Assessment::create([
                    'class_subject_id' => $classSubject->id,
                    'teacher_id' => $classSubject->teacher_id,
                    'title' => $classSubject->subject->name.' - '.$assessmentData['title_suffix'],
                    'description' => 'Assessment for '.$classSubject->subject->name,
                    'type' => $assessmentData['type'],
                    'delivery_mode' => $deliveryMode,
                    'coefficient' => $assessmentData['coefficient'],
                    'duration_minutes' => $isSupervisedMode ? 90 : null,
                    'scheduled_at' => $isSupervisedMode ? now()->addDays($dayCounter) : null,
                    'due_date' => $isSupervisedMode ? null : now()->addDays($dayCounter + 7),
                    'max_files' => $isSupervisedMode ? 0 : 3,
                    'max_file_size' => $isSupervisedMode ? null : 5120,
                    'allowed_extensions' => $isSupervisedMode ? null : 'pdf,docx,zip',
                    'settings' => [],
                ]);

                $this->createSampleQuestions($assessment, $assessmentData['type']);

                $count++;
                $dayCounter = ($dayCounter % 20) + 5;
            }
        }

        $this->command->info("{$count} Assessments created (3 per subject: Exam supervised + Quiz homework + Project homework)");
    }

    /**
     * Create sample questions with choices for an assessment.
     */
    private function createSampleQuestions(Assessment $assessment, AssessmentType $type): void
    {
        $questionsCount = $type === AssessmentType::Exam ? 5 : 3;

        for ($i = 1; $i <= $questionsCount; $i++) {
            $question = $assessment->questions()->create([
                'content' => "Question {$i} for {$assessment->title}",
                'type' => $i === 1 ? 'text' : ($i === 2 ? 'one_choice' : 'multiple'),
                'points' => $type === AssessmentType::Exam ? 4 : 2,
                'order_index' => $i,
            ]);

            if ($question->type === QuestionType::OneChoice) {
                $question->choices()->createMany([
                    ['content' => 'Option A', 'is_correct' => true, 'order_index' => 1],
                    ['content' => 'Option B', 'is_correct' => false, 'order_index' => 2],
                    ['content' => 'Option C', 'is_correct' => false, 'order_index' => 3],
                ]);
            } elseif ($question->type === QuestionType::Multiple) {
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
