<?php

namespace Database\Seeders;

use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Assessment;
use App\Models\AssessmentAssignment;
use App\Models\Enrollment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Generates realistic assessment assignments and student answers for demo purposes.
 *
 * Creates assignments with varied statuses (graded, submitted, in_progress, not_started)
 * and answers with distributed scores to populate dashboard charts.
 */
class DemoAssignmentSeeder extends Seeder
{
    private const GRADE_PROFILES = [
        'excellent' => ['min' => 0.80, 'max' => 1.00],
        'good' => ['min' => 0.60, 'max' => 0.85],
        'average' => ['min' => 0.40, 'max' => 0.65],
        'weak' => ['min' => 0.20, 'max' => 0.45],
        'failing' => ['min' => 0.05, 'max' => 0.30],
    ];

    private const PROFILE_WEIGHTS = [
        'excellent' => 15,
        'good' => 30,
        'average' => 30,
        'weak' => 15,
        'failing' => 10,
    ];

    private const FEEDBACK_TEMPLATES = [
        'excellent' => ['Excellent travail.', 'Tres bonne maitrise du sujet.', 'Reponse complete et bien structuree.'],
        'good' => ['Bon travail dans l\'ensemble.', 'Quelques details manquants.', 'Bonne comprehension generale.'],
        'average' => ['Passable. Des lacunes a combler.', 'Reponse incomplete.', 'Effort correct mais insuffisant.'],
        'weak' => ['Insuffisant. Revoir le cours.', 'Beaucoup d\'erreurs.', 'Comprehension partielle du sujet.'],
        'failing' => ['Travail non satisfaisant.', 'Le sujet n\'est pas compris.', 'A refaire integralement.'],
    ];

    /**
     * Seed assessment assignments and answers with realistic score distributions.
     */
    public function run(): void
    {
        $publishedAssessments = Assessment::where('is_published', true)
            ->with(['questions.choices', 'classSubject'])
            ->get();

        if ($publishedAssessments->isEmpty()) {
            $this->command->error('No published assessments found.');

            return;
        }

        $profileKeys = array_keys(self::PROFILE_WEIGHTS);
        $profilePool = [];
        foreach (self::PROFILE_WEIGHTS as $key => $weight) {
            for ($w = 0; $w < $weight; $w++) {
                $profilePool[] = $key;
            }
        }

        $studentProfiles = [];
        $assignmentCount = 0;
        $answerCount = 0;

        foreach ($publishedAssessments as $assessment) {
            $classId = $assessment->classSubject->class_id;
            $enrollments = Enrollment::where('class_id', $classId)
                ->where('status', 'active')
                ->get();

            if ($enrollments->isEmpty()) {
                continue;
            }

            $scheduledDate = $assessment->scheduled_at ?? $assessment->due_date ?? now()->subWeeks(rand(2, 8));
            $isPastAssessment = Carbon::parse($scheduledDate)->isPast();

            foreach ($enrollments as $enrollment) {
                if (! isset($studentProfiles[$enrollment->student_id])) {
                    $studentProfiles[$enrollment->student_id] = $profilePool[array_rand($profilePool)];
                }

                $profile = $studentProfiles[$enrollment->student_id];
                $status = $this->determineStatus($isPastAssessment, $profile);

                if ($status === 'skip') {
                    continue;
                }

                $startedAt = $this->generateStartedAt($scheduledDate, $status);
                $submittedAt = $this->generateSubmittedAt($startedAt, $status, $assessment->duration_minutes);
                $gradedAt = $this->generateGradedAt($submittedAt, $status);

                $assignment = AssessmentAssignment::create([
                    'assessment_id' => $assessment->id,
                    'enrollment_id' => $enrollment->id,
                    'started_at' => $startedAt,
                    'submitted_at' => $submittedAt,
                    'graded_at' => $gradedAt,
                    'teacher_notes' => $status === 'graded' ? $this->getTeacherNote($profile) : null,
                    'forced_submission' => $status === 'graded' && rand(1, 20) === 1,
                ]);

                $assignmentCount++;

                if (in_array($status, ['graded', 'submitted', 'in_progress'])) {
                    $answerCount += $this->createAnswers($assignment, $assessment, $profile, $status);
                }
            }
        }

        $this->command->info("{$assignmentCount} Assignments + {$answerCount} Answers created");
    }

    /**
     * Determine the assignment status based on timing and student profile.
     */
    private function determineStatus(bool $isPastAssessment, string $profile): string
    {
        if (! $isPastAssessment) {
            $rand = rand(1, 100);
            if ($rand <= 20) {
                return 'in_progress';
            }
            if ($rand <= 50) {
                return 'not_started';
            }

            return 'skip';
        }

        return match ($profile) {
            'excellent', 'good' => rand(1, 100) <= 95 ? 'graded' : 'submitted',
            'average' => rand(1, 100) <= 85 ? 'graded' : (rand(1, 2) === 1 ? 'submitted' : 'not_started'),
            'weak' => rand(1, 100) <= 70 ? 'graded' : (rand(1, 3) === 1 ? 'submitted' : 'not_started'),
            'failing' => rand(1, 100) <= 50 ? 'graded' : (rand(1, 3) === 1 ? 'submitted' : 'not_started'),
        };
    }

    /**
     * Generate a realistic started_at timestamp.
     */
    private function generateStartedAt(Carbon|string $scheduledDate, string $status): ?Carbon
    {
        if ($status === 'not_started') {
            return null;
        }

        $base = Carbon::parse($scheduledDate);

        return $base->copy()->addMinutes(rand(0, 15));
    }

    /**
     * Generate a realistic submitted_at timestamp.
     */
    private function generateSubmittedAt(?Carbon $startedAt, string $status, ?int $durationMinutes): ?Carbon
    {
        if (! $startedAt || in_array($status, ['not_started', 'in_progress'])) {
            return null;
        }

        $duration = $durationMinutes ?? 60;

        return $startedAt->copy()->addMinutes(rand(intval($duration * 0.5), $duration));
    }

    /**
     * Generate a realistic graded_at timestamp.
     */
    private function generateGradedAt(?Carbon $submittedAt, string $status): ?Carbon
    {
        if (! $submittedAt || $status !== 'graded') {
            return null;
        }

        return $submittedAt->copy()->addDays(rand(1, 7));
    }

    /**
     * Create answers for an assignment based on the student profile.
     *
     * @return int Number of answers created
     */
    private function createAnswers(
        AssessmentAssignment $assignment,
        Assessment $assessment,
        string $profile,
        string $status
    ): int {
        $questions = $assessment->questions;
        $count = 0;
        $range = self::GRADE_PROFILES[$profile];

        $questionsToAnswer = $status === 'in_progress'
            ? $questions->take(max(1, intval($questions->count() * 0.5)))
            : $questions;

        foreach ($questionsToAnswer as $question) {
            $scoreFraction = $this->randomFloat($range['min'], $range['max']);
            $rawScore = round($question->points * $scoreFraction, 2);
            $shouldGrade = $status === 'graded';

            $answerData = [
                'assessment_assignment_id' => $assignment->id,
                'question_id' => $question->id,
                'score' => $shouldGrade ? $rawScore : null,
                'feedback' => $shouldGrade ? $this->getFeedback($profile) : null,
            ];

            if ($question->type === QuestionType::Text) {
                $answerData['answer_text'] = $this->generateTextAnswer($question->content, $profile);
            } elseif ($question->type === QuestionType::OneChoice) {
                $choices = $question->choices;
                if ($choices->isNotEmpty()) {
                    $correctChoice = $choices->firstWhere('is_correct', true);
                    $isCorrect = $scoreFraction > 0.5;
                    $answerData['choice_id'] = $isCorrect && $correctChoice
                        ? $correctChoice->id
                        : $choices->where('is_correct', false)->random()?->id ?? $choices->first()->id;
                }
            } elseif ($question->type === QuestionType::Multiple) {
                $correctChoice = $question->choices->firstWhere('is_correct', true);
                if ($correctChoice) {
                    $answerData['choice_id'] = $correctChoice->id;
                }
            } elseif ($question->type === QuestionType::Boolean) {
                $choices = $question->choices;
                if ($choices->isNotEmpty()) {
                    $isCorrect = $scoreFraction > 0.5;
                    $correctChoice = $choices->firstWhere('is_correct', true);
                    $answerData['choice_id'] = $isCorrect && $correctChoice
                        ? $correctChoice->id
                        : $choices->where('is_correct', false)->first()?->id ?? $choices->first()->id;
                }
            }

            Answer::create($answerData);
            $count++;
        }

        return $count;
    }

    /**
     * Generate a text answer based on student profile quality.
     */
    private function generateTextAnswer(string $questionContent, string $profile): string
    {
        return match ($profile) {
            'excellent' => 'La reponse detaillee couvre tous les aspects de la question avec des exemples precis et une argumentation rigoureuse.',
            'good' => 'Bonne reponse qui couvre les points principaux avec quelques exemples pertinents.',
            'average' => 'Reponse partielle qui aborde le sujet sans approfondir tous les aspects.',
            'weak' => 'Quelques elements de reponse mais manque de precision et de structure.',
            'failing' => 'Reponse tres incomplete, hors sujet ou manquant de comprehension.',
        };
    }

    /**
     * Get contextual feedback based on student profile.
     */
    private function getFeedback(string $profile): ?string
    {
        if (rand(1, 3) === 1) {
            return null;
        }

        $templates = self::FEEDBACK_TEMPLATES[$profile] ?? self::FEEDBACK_TEMPLATES['average'];

        return $templates[array_rand($templates)];
    }

    /**
     * Get teacher notes for graded assignments.
     */
    private function getTeacherNote(string $profile): ?string
    {
        if (rand(1, 4) !== 1) {
            return null;
        }

        return match ($profile) {
            'excellent' => 'Etudiant(e) brillant(e), continue ainsi.',
            'good' => 'Bon niveau, peut encore progresser.',
            'average' => 'Doit fournir plus d\'efforts reguliers.',
            'weak' => 'Necesssite un accompagnement supplementaire.',
            'failing' => 'Rendez-vous de suivi recommande.',
        };
    }

    /**
     * Generate a random float between min and max.
     */
    private function randomFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }
}
