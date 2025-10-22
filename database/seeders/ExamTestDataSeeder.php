<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Exam;
use App\Models\Question;
use App\Models\Choice;
use Spatie\Permission\Models\Role;

class ExamTestDataSeeder extends Seeder
{
    public function run()
    {
        $studentRole = Role::firstOrCreate(['name' => 'student']);
        $teacherRole = Role::firstOrCreate(['name' => 'teacher']);

        $student = User::firstOrCreate([
            'email' => 'etudiant@test.com'
        ], [
            'name' => 'Étudiant Test',
            'password' => bcrypt('password'),
        ]);
        $student->assignRole($studentRole);

        $teacher = User::firstOrCreate([
            'email' => 'professeur@test.com'
        ], [
            'name' => 'Professeur Test',
            'password' => bcrypt('password'),
        ]);
        $teacher->assignRole($teacherRole);

        $exam = Exam::firstOrCreate([
            'title' => 'Examen de Test TypeScript/React'
        ], [
            'description' => 'Un examen de démonstration pour tester l\'interface sécurisée avec React et TypeScript.',
            'duration' => 30,
            'max_attempts' => 3,
            'is_active' => true,
            'start_date' => now()->subHour(),
            'end_date' => now()->addDays(7),
            'creator_id' => $teacher->id,
        ]);

        $questions = [
            [
                'question_text' => 'Quel est le principal avantage de TypeScript par rapport à JavaScript ?',
                'points' => 10,
                'choices' => [
                    ['content' => 'Il est plus rapide à l\'exécution', 'is_correct' => false],
                    ['content' => 'Il offre un typage statique', 'is_correct' => true],
                    ['content' => 'Il nécessite moins de code', 'is_correct' => false],
                    ['content' => 'Il est plus facile à apprendre', 'is_correct' => false],
                ]
            ],
            [
                'question_text' => 'Quelle fonction React est utilisée pour gérer l\'état local d\'un composant ?',
                'points' => 10,
                'choices' => [
                    ['content' => 'useEffect', 'is_correct' => false],
                    ['content' => 'useState', 'is_correct' => true],
                    ['content' => 'useContext', 'is_correct' => false],
                    ['content' => 'useCallback', 'is_correct' => false],
                ]
            ],
            [
                'question_text' => 'Qu\'est-ce qu\'Inertia.js apporte au développement web ?',
                'points' => 10,
                'choices' => [
                    ['content' => 'Un framework CSS', 'is_correct' => false],
                    ['content' => 'Une base de données', 'is_correct' => false],
                    ['content' => 'Un pont entre backend et frontend SPA', 'is_correct' => true],
                    ['content' => 'Un serveur web', 'is_correct' => false],
                ]
            ],
            [
                'question_text' => 'Quel est le rôle principal des hooks personnalisés en React ?',
                'points' => 15,
                'choices' => [
                    ['content' => 'Améliorer les performances', 'is_correct' => false],
                    ['content' => 'Réutiliser la logique d\'état entre composants', 'is_correct' => true],
                    ['content' => 'Gérer le CSS', 'is_correct' => false],
                    ['content' => 'Remplacer les composants de classe', 'is_correct' => false],
                ]
            ],
            [
                'question_text' => 'Quelle est la meilleure pratique pour la sécurité des examens en ligne ?',
                'points' => 15,
                'choices' => [
                    ['content' => 'Bloquer tous les sites web', 'is_correct' => false],
                    ['content' => 'Surveiller les interactions utilisateur et limiter les actions suspectes', 'is_correct' => true],
                    ['content' => 'Désactiver le JavaScript', 'is_correct' => false],
                    ['content' => 'Utiliser uniquement des questions ouvertes', 'is_correct' => false],
                ]
            ],
        ];

        foreach ($questions as $index => $questionData) {
            $question = Question::firstOrCreate([
                'exam_id' => $exam->id,
                'question_text' => $questionData['question_text']
            ], [
                'type' => 'multiple_choice',
                'points' => $questionData['points'],
                'order_index' => $index + 1,
            ]);

            foreach ($questionData['choices'] as $choiceIndex => $choiceData) {
                Choice::firstOrCreate([
                    'question_id' => $question->id,
                    'content' => $choiceData['content']
                ], [
                    'is_correct' => $choiceData['is_correct'],
                    'order_index' => $choiceIndex + 1,
                ]);
            }
        }

        $this->command->info('Données de test créées avec succès !');
        $this->command->info('Étudiant: etudiant@test.com / password');
        $this->command->info('Professeur: professeur@test.com / password');
    }
}
