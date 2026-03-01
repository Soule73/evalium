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
     * Create realistic assessments with varied question types and content.
     */
    public function run(): void
    {
        $classSubjects = ClassSubject::with(['teacher', 'class.level', 'subject'])->get();

        if ($classSubjects->isEmpty()) {
            $this->command->error('No class subjects found.');

            return;
        }

        $assessmentTemplates = [
            ['type' => AssessmentType::Exam, 'coefficient' => 2.0, 'suffix' => 'Examen Partiel'],
            ['type' => AssessmentType::Quiz, 'coefficient' => 1.0, 'suffix' => 'Controle Continu'],
            ['type' => AssessmentType::Project, 'coefficient' => 1.5, 'suffix' => 'Projet'],
        ];

        $count = 0;
        $dayOffset = -180;

        foreach ($classSubjects as $cs) {
            foreach ($assessmentTemplates as $tplIndex => $tpl) {
                $deliveryMode = DeliveryMode::defaultForType($tpl['type']);
                $isSupervisedMode = $deliveryMode === DeliveryMode::Supervised;

                $scheduledAt = now()->addDays($dayOffset);
                $isPublished = $dayOffset < 5;

                $assessment = Assessment::create([
                    'class_subject_id' => $cs->id,
                    'teacher_id' => $cs->teacher_id,
                    'title' => $cs->subject->name.' - '.$tpl['suffix'],
                    'description' => $this->generateDescription($cs->subject->name, $tpl['type']),
                    'type' => $tpl['type'],
                    'delivery_mode' => $deliveryMode,
                    'coefficient' => $tpl['coefficient'],
                    'duration_minutes' => $isSupervisedMode ? ($tpl['type'] === AssessmentType::Exam ? 120 : 45) : null,
                    'scheduled_at' => $isSupervisedMode ? $scheduledAt : null,
                    'due_date' => $isSupervisedMode ? null : $scheduledAt->copy()->addDays(14),
                    'is_published' => $isPublished,
                    'settings' => [],
                ]);

                $this->createRealisticQuestions($assessment, $cs->subject->code ?? $cs->subject->name, $tpl['type']);

                $count++;
                $dayOffset += 3;
            }
        }

        $this->command->info("{$count} Assessments created with realistic questions");
    }

    /**
     * Generate a realistic assessment description.
     */
    private function generateDescription(string $subjectName, AssessmentType $type): string
    {
        return match ($type) {
            AssessmentType::Exam => "Examen couvrant les chapitres principaux de {$subjectName}. Documents non autorises.",
            AssessmentType::Quiz => "Controle rapide sur les concepts recents de {$subjectName}.",
            AssessmentType::Project => "Projet pratique appliquant les notions de {$subjectName}.",
            default => "Evaluation de {$subjectName}.",
        };
    }

    /**
     * Create realistic questions with subject-appropriate content.
     *
     * @param  Assessment  $assessment  The assessment to attach questions to
     * @param  string  $subjectCode  The subject code for content generation
     * @param  AssessmentType  $type  The assessment type for question count
     */
    private function createRealisticQuestions(Assessment $assessment, string $subjectCode, AssessmentType $type): void
    {
        $questionsCount = match ($type) {
            AssessmentType::Exam => 5,
            AssessmentType::Quiz => 4,
            default => 3,
        };

        $pool = $this->getQuestionPool($subjectCode);
        $pointsPerQuestion = $type === AssessmentType::Exam ? 4.0 : ($type === AssessmentType::Quiz ? 2.5 : 3.0);

        for ($i = 0; $i < $questionsCount; $i++) {
            $questionData = $pool[$i % count($pool)];
            $questionType = $questionData['type'];

            $question = $assessment->questions()->create([
                'content' => $questionData['content'],
                'type' => $questionType,
                'points' => $pointsPerQuestion,
                'order_index' => $i + 1,
            ]);

            if ($questionType === QuestionType::OneChoice && isset($questionData['choices'])) {
                foreach ($questionData['choices'] as $idx => $choice) {
                    $question->choices()->create([
                        'content' => $choice['text'],
                        'is_correct' => $choice['correct'],
                        'order_index' => $idx + 1,
                    ]);
                }
            } elseif ($questionType === QuestionType::Multiple && isset($questionData['choices'])) {
                foreach ($questionData['choices'] as $idx => $choice) {
                    $question->choices()->create([
                        'content' => $choice['text'],
                        'is_correct' => $choice['correct'],
                        'order_index' => $idx + 1,
                    ]);
                }
            } elseif ($questionType === QuestionType::Boolean) {
                $question->choices()->createMany([
                    ['content' => 'Vrai', 'is_correct' => true, 'order_index' => 1],
                    ['content' => 'Faux', 'is_correct' => false, 'order_index' => 2],
                ]);
            }
        }
    }

    /**
     * Get a pool of realistic questions based on subject code.
     *
     * @return array<int, array{content: string, type: QuestionType, choices?: array}>
     */
    private function getQuestionPool(string $subjectCode): array
    {
        $prefix = explode('_', $subjectCode)[0] ?? '';

        return match ($prefix) {
            'MATH' => [
                ['content' => 'Calculer la derivee de f(x) = 3x^2 + 2x - 5.', 'type' => QuestionType::Text],
                ['content' => 'Quelle est la limite de (sin x)/x quand x tend vers 0 ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => '0', 'correct' => false],
                    ['text' => '1', 'correct' => true],
                    ['text' => 'Infini', 'correct' => false],
                    ['text' => 'N\'existe pas', 'correct' => false],
                ]],
                ['content' => 'Parmi les proprietes suivantes, lesquelles s\'appliquent a une fonction continue ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Theoreme des valeurs intermediaires', 'correct' => true],
                    ['text' => 'Derivable en tout point', 'correct' => false],
                    ['text' => 'Image d\'un ferme borne est un ferme borne', 'correct' => true],
                    ['text' => 'Toujours monotone', 'correct' => false],
                ]],
                ['content' => 'Toute fonction derivable est continue.', 'type' => QuestionType::Boolean],
                ['content' => 'Determiner les extrema de f(x) = x^3 - 3x + 2 sur [-2, 2].', 'type' => QuestionType::Text],
            ],
            'ALG' => [
                ['content' => 'Calculer le determinant de la matrice [[1,2],[3,4]].', 'type' => QuestionType::Text],
                ['content' => 'Quel est le rang de la matrice identite 3x3 ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => '1', 'correct' => false],
                    ['text' => '2', 'correct' => false],
                    ['text' => '3', 'correct' => true],
                    ['text' => '0', 'correct' => false],
                ]],
                ['content' => 'Quelles proprietes sont vraies pour un espace vectoriel ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Contient le vecteur nul', 'correct' => true],
                    ['text' => 'Stable par addition', 'correct' => true],
                    ['text' => 'Toujours de dimension finie', 'correct' => false],
                    ['text' => 'Stable par multiplication scalaire', 'correct' => true],
                ]],
                ['content' => 'Demontrer que les vecteurs (1,0,1) et (0,1,1) sont lineairement independants.', 'type' => QuestionType::Text],
                ['content' => 'Une matrice inversible a un determinant nul.', 'type' => QuestionType::Boolean],
            ],
            'PHYS', 'MDF' => [
                ['content' => 'Enoncer le principe fondamental de la dynamique (2eme loi de Newton).', 'type' => QuestionType::Text],
                ['content' => 'Quelle est l\'unite SI de la force ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => 'Joule', 'correct' => false],
                    ['text' => 'Newton', 'correct' => true],
                    ['text' => 'Watt', 'correct' => false],
                    ['text' => 'Pascal', 'correct' => false],
                ]],
                ['content' => 'Quels phenomenes sont lies a la mecanique des fluides ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Viscosite', 'correct' => true],
                    ['text' => 'Pression hydrostatique', 'correct' => true],
                    ['text' => 'Effet photoelectrique', 'correct' => false],
                    ['text' => 'Turbulence', 'correct' => true],
                ]],
                ['content' => 'La pression dans un fluide augmente avec la profondeur.', 'type' => QuestionType::Boolean],
                ['content' => 'Calculer l\'energie cinetique d\'un objet de 5 kg se deplacant a 10 m/s.', 'type' => QuestionType::Text],
            ],
            'ALGO', 'POO' => [
                ['content' => 'Ecrire un algorithme de tri par insertion pour un tableau d\'entiers.', 'type' => QuestionType::Text],
                ['content' => 'Quelle est la complexite du tri rapide (Quicksort) dans le cas moyen ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => 'O(n)', 'correct' => false],
                    ['text' => 'O(n log n)', 'correct' => true],
                    ['text' => 'O(n^2)', 'correct' => false],
                    ['text' => 'O(log n)', 'correct' => false],
                ]],
                ['content' => 'Quels sont les principes de la POO ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Encapsulation', 'correct' => true],
                    ['text' => 'Heritage', 'correct' => true],
                    ['text' => 'Compilation', 'correct' => false],
                    ['text' => 'Polymorphisme', 'correct' => true],
                ]],
                ['content' => 'Un algorithme recursif utilise toujours moins de memoire qu\'un iteratif.', 'type' => QuestionType::Boolean],
                ['content' => 'Expliquer la difference entre une pile et une file.', 'type' => QuestionType::Text],
            ],
            'OPT' => [
                ['content' => 'Formuler le probleme du sac a dos en programmation lineaire.', 'type' => QuestionType::Text],
                ['content' => 'Quelle methode est utilisee pour l\'optimisation lineaire ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => 'Methode du simplexe', 'correct' => true],
                    ['text' => 'Methode de Newton', 'correct' => false],
                    ['text' => 'Algorithme genetique', 'correct' => false],
                    ['text' => 'Recuit simule', 'correct' => false],
                ]],
                ['content' => 'Quelles conditions font partie des conditions KKT ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Stationnarite', 'correct' => true],
                    ['text' => 'Complementarite', 'correct' => true],
                    ['text' => 'Convexite stricte', 'correct' => false],
                    ['text' => 'Faisabilite primale', 'correct' => true],
                ]],
                ['content' => 'Tout probleme d\'optimisation lineaire admet une solution.', 'type' => QuestionType::Boolean],
                ['content' => 'Resoudre graphiquement: max 2x+3y, x+y<=4, x>=0, y>=0.', 'type' => QuestionType::Text],
            ],
            'IA' => [
                ['content' => 'Expliquer la difference entre apprentissage supervise et non supervise.', 'type' => QuestionType::Text],
                ['content' => 'Quel algorithme est utilise pour la classification binaire ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => 'K-Means', 'correct' => false],
                    ['text' => 'Regression logistique', 'correct' => true],
                    ['text' => 'ACP', 'correct' => false],
                    ['text' => 'DBSCAN', 'correct' => false],
                ]],
                ['content' => 'Quelles metriques evaluent un modele de classification ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Precision', 'correct' => true],
                    ['text' => 'Rappel', 'correct' => true],
                    ['text' => 'MSE', 'correct' => false],
                    ['text' => 'F1-score', 'correct' => true],
                ]],
                ['content' => 'Un reseau de neurones avec une seule couche peut resoudre le probleme XOR.', 'type' => QuestionType::Boolean],
                ['content' => 'Decrire le fonctionnement de l\'algorithme des k plus proches voisins (KNN).', 'type' => QuestionType::Text],
            ],
            'BDA' => [
                ['content' => 'Expliquer la difference entre les index B-Tree et Hash.', 'type' => QuestionType::Text],
                ['content' => 'Quelle forme normale elimine les dependances transitives ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => '1NF', 'correct' => false],
                    ['text' => '2NF', 'correct' => false],
                    ['text' => '3NF', 'correct' => true],
                    ['text' => 'BCNF', 'correct' => false],
                ]],
                ['content' => 'Quelles proprietes font partie du theoreme CAP ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Consistency', 'correct' => true],
                    ['text' => 'Availability', 'correct' => true],
                    ['text' => 'Partition tolerance', 'correct' => true],
                    ['text' => 'Durability', 'correct' => false],
                ]],
                ['content' => 'Les transactions NoSQL respectent toujours les proprietes ACID.', 'type' => QuestionType::Boolean],
                ['content' => 'Ecrire une requete SQL pour trouver les 3 meilleurs etudiants par moyenne.', 'type' => QuestionType::Text],
            ],
            default => [
                ['content' => 'Definir les concepts fondamentaux du cours.', 'type' => QuestionType::Text],
                ['content' => 'Quel element est central dans cette discipline ?', 'type' => QuestionType::OneChoice, 'choices' => [
                    ['text' => 'Option A', 'correct' => true],
                    ['text' => 'Option B', 'correct' => false],
                    ['text' => 'Option C', 'correct' => false],
                    ['text' => 'Option D', 'correct' => false],
                ]],
                ['content' => 'Quels aspects sont importants dans ce domaine ?', 'type' => QuestionType::Multiple, 'choices' => [
                    ['text' => 'Aspect theorique', 'correct' => true],
                    ['text' => 'Aspect pratique', 'correct' => true],
                    ['text' => 'Aspect decoratif', 'correct' => false],
                    ['text' => 'Aspect methodologique', 'correct' => true],
                ]],
                ['content' => 'Cette affirmation est-elle correcte ?', 'type' => QuestionType::Boolean],
                ['content' => 'Rediger une synthese des points abordes en cours.', 'type' => QuestionType::Text],
            ],
        };
    }
}
