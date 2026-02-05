<?php

namespace Tests\Traits;

use App\Models\Assessment;
use App\Models\Choice;
use App\Models\Question;
use App\Models\User;

trait CreatesTestAssessments
{
    /**
     * Create an assessment with questions and choices.
     */
    protected function createAssessmentWithQuestions(
        ?User $teacher = null,
        array $assessmentAttributes = [],
        int $questionCount = 3
    ): Assessment {
        if (is_array($teacher)) {
            $assessmentAttributes = $teacher;
            $teacher = null;
        }

        if (is_int($assessmentAttributes)) {
            $questionCount = $assessmentAttributes;
            $assessmentAttributes = [];
        }

        $teacher = $teacher ?? $this->createTeacher();

        if (! isset($assessmentAttributes['class_subject_id'])) {
            $academicYear = \App\Models\AcademicYear::firstOrCreate(
                ['is_current' => true],
                ['name' => '2023/2024', 'start_date' => '2023-09-01', 'end_date' => '2024-06-30']
            );

            $semester = \App\Models\Semester::firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'order_number' => 1],
                ['name' => 'Semester 1', 'start_date' => '2023-09-01', 'end_date' => '2024-01-31']
            );

            $class = \App\Models\ClassModel::firstOrCreate(
                ['academic_year_id' => $academicYear->id, 'name' => 'Test Class'],
                ['level_id' => \App\Models\Level::factory()->create()->id]
            );

            $subject = \App\Models\Subject::firstOrCreate(
                ['name' => 'Test Subject'],
                ['code' => 'TST', 'level_id' => $class->level_id]
            );

            $classSubject = \App\Models\ClassSubject::firstOrCreate([
                'class_id' => $class->id,
                'subject_id' => $subject->id,
                'teacher_id' => $teacher->id,
                'semester_id' => $semester->id,
            ], ['coefficient' => 1, 'valid_from' => now()]);

            $assessmentAttributes['class_subject_id'] = $classSubject->id;
        }

        $assessment = Assessment::factory()->create(array_merge([
            'teacher_id' => $teacher->id,
        ], $assessmentAttributes));

        for ($i = 0; $i < $questionCount; $i++) {
            $question = Question::factory()->create([
                'assessment_id' => $assessment->id,
                'points' => 10,
            ]);

            Choice::factory()->count(2)->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]);

            Choice::factory()->count(2)->create([
                'question_id' => $question->id,
                'is_correct' => false,
            ]);
        }

        return $assessment->load('questions.choices');
    }
}
