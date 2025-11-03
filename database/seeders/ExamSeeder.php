<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $teachers = \App\Models\User::role('teacher')->get();

        if ($teachers->isEmpty()) {
            echo "Aucun enseignant trouvé. Veuillez d'abord créer des enseignants.\n";
            return;
        }

        foreach ($teachers as $teacher) {
            $this->createMathExam($teacher);
            $this->createComputerScienceExam($teacher);
            $this->createPhilosophyExam($teacher);
        }
    }

    private function createMathExam($teacher)
    {
        // Examen 1: Mathématiques avec tous les types de questions
        $mathExam = \App\Models\Exam::create([
            'title' => 'Examen de Mathématiques - Algèbre et Géométrie',
            'description' => 'Test complet sur les équations, fonctions et géométrie avec différents types de questions',
            'teacher_id' => $teacher->id,
            'duration' => 90,
            'start_time' => now()->addDays(7),
            'end_time' => now()->addDays(10),
            'is_active' => true,
        ]);

        $question1 = \App\Models\Question::create([
            'exam_id' => $mathExam->id,
            'content' => '
## Équation du Second Degré

Résolvez l\'équation quadratique suivante :

$$x^2 - 5x + 6 = 0$$

### Méthode suggérée :
Utiliser la factorisation ou la formule quadratique.

Quelle est la solution correcte ?
                        ',
            'type' => 'one_choice',
            'points' => 5,
        ]);

        $choices1 = [
            ['content' => 'x = 2 et x = 3', 'is_correct' => true],
            ['content' => 'x = 1 et x = 6', 'is_correct' => false],
            ['content' => 'x = -2 et x = -3', 'is_correct' => false],
            ['content' => 'x = 0 et x = 5', 'is_correct' => false],
        ];

        foreach ($choices1 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question1->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 2: Choix multiples (multiple)
        $question2 = \App\Models\Question::create([
            'exam_id' => $mathExam->id,
            'content' => '
## Propriétés des Nombres

Quelles sont les propriétés vraies pour le nombre **12** ?

### Sélectionnez toutes les bonnes réponses :',
            'type' => 'multiple',
            'points' => 6,
        ]);

        $choices2 = [
            ['content' => '12 est un nombre pair', 'is_correct' => true],
            ['content' => '12 est divisible par 3', 'is_correct' => true],
            ['content' => '12 est un nombre premier', 'is_correct' => false],
            ['content' => '12 est divisible par 4', 'is_correct' => true],
            ['content' => '12 est impair', 'is_correct' => false],
        ];

        foreach ($choices2 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question2->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 3: Vrai/Faux (boolean)
        $question3 = \App\Models\Question::create([
            'exam_id' => $mathExam->id,
            'content' =>
            '
## Géométrie

### Affirmation :
Dans un triangle rectangle, le carré de l\'hypoténuse est égal à la somme des carrés des deux autres côtés.

**Cette affirmation décrit-elle le théorème de Pythagore ?**',
            'type' => 'boolean',
            'points' => 3,
        ]);

        $choices3 = [
            ['content' => 'Vrai', 'is_correct' => true],
            ['content' => 'Faux', 'is_correct' => false],
        ];

        foreach ($choices3 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question3->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 4: Question ouverte (text)
        \App\Models\Question::create([
            'exam_id' => $mathExam->id,
            'content' =>
            '
## Démonstration

### Exercice :
Démontrez que la somme des angles intérieurs d\'un triangle est égale à 180°.

### Instructions :
- Utilisez une méthode géométrique claire
- Dessinez un schéma si nécessaire
- Expliquez chaque étape de votre raisonnement
- Votre réponse doit être complète et rigoureuse

### Critères d\'évaluation :
- Clarté du raisonnement (5 points)
- Justesse mathématique (5 points)
- Présentation (2 points)',
            'type' => 'text',
            'points' => 12,
        ]);
    }

    private function createComputerScienceExam($teacher)
    {
        // Examen 2: Informatique
        $csExam = \App\Models\Exam::create([
            'title' => 'Examen d\'Informatique - Programmation et Algorithmes',
            'description' => 'Test sur les concepts de programmation, structures de données et algorithmes',
            'teacher_id' => $teacher->id,
            'duration' => 120,
            'start_time' => now()->addDays(5),
            'end_time' => now()->addDays(8),
            'is_active' => false,
        ]);

        // Question 1: Choix unique sur les langages
        $question1 = \App\Models\Question::create([
            'exam_id' => $csExam->id,
            'content' => '
## Langages de Programmation

### Question :
Quel langage de programmation est principalement utilisé pour le développement web côté serveur et a été créé par Rasmus Lerdorf ?

```php
<?php
    echo "Hello World!";
?>
```

### Indice :
Ce langage est très populaire pour les sites web dynamiques.',
            'type' => 'one_choice',
            'points' => 3,
        ]);

        $choices1 = [
            ['content' => 'PHP', 'is_correct' => true],
            ['content' => 'JavaScript', 'is_correct' => false],
            ['content' => 'Python', 'is_correct' => false],
            ['content' => 'Java', 'is_correct' => false],
        ];

        foreach ($choices1 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question1->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 2: Choix multiples sur les structures de données
        $question2 = \App\Models\Question::create([
            'exam_id' => $csExam->id,
            'content' => '
## Structures de Données

### Question :
Quelles sont les caractéristiques d\'une **pile** (stack) en informatique ?

**Sélectionnez toutes les bonnes réponses :**',
            'type' => 'multiple',
            'points' => 8,
        ]);

        $choices2 = [
            ['content' => 'Suit le principe LIFO (Last In, First Out)', 'is_correct' => true],
            ['content' => 'Permet d\'ajouter des éléments uniquement au sommet', 'is_correct' => true],
            ['content' => 'Suit le principe FIFO (First In, First Out)', 'is_correct' => false],
            ['content' => 'Permet de retirer des éléments uniquement du sommet', 'is_correct' => true],
            ['content' => 'Permet l\'accès direct à n\'importe quel élément', 'is_correct' => false],
        ];

        foreach ($choices2 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question2->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 3: Vrai/Faux sur les algorithmes
        $question3 = \App\Models\Question::create([
            'exam_id' => $csExam->id,
            'content' => '
## Complexité Algorithmique

### Affirmation :
L\'algorithme de tri rapide (QuickSort) a une complexité temporelle moyenne de O(n log n).

**Cette affirmation est-elle vraie ?**',
            'type' => 'boolean',
            'points' => 4,
        ]);

        $choices3 = [
            ['content' => 'Vrai', 'is_correct' => true],
            ['content' => 'Faux', 'is_correct' => false],
        ];

        foreach ($choices3 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question3->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 4: Question ouverte sur la programmation
        \App\Models\Question::create([
            'exam_id' => $csExam->id,
            'content' => '
## Programmation Orientée Objet

### Exercice :
Expliquez les quatre principes fondamentaux de la programmation orientée objet.

### Instructions :
Pour chaque principe, vous devez :
1. **Définir** le concept clairement
2. **Expliquer** pourquoi il est important
3. **Donner un exemple concret** en pseudocode ou dans un langage de votre choix

### Les quatre principes à expliquer :
- Encapsulation
- Héritage  
- Polymorphisme
- Abstraction

### Critères d\'évaluation :
- Exactitude des définitions (8 points)
- Qualité des exemples (6 points)
- Clarté de l\'explication (4 points)
- Structure de la réponse (2 points)',
            'type' => 'text',
            'points' => 20,
        ]);
    }

    private function createPhilosophyExam($teacher)
    {
        // Examen 3: Philosophie
        $philoExam = \App\Models\Exam::create([
            'title' => 'Examen de Philosophie - Éthique et Métaphysique',
            'description' => 'Questions sur les grands concepts philosophiques et l\'éthique',
            'teacher_id' => $teacher->id,
            'duration' => 150,
            'start_time' => now()->addDays(12),
            'end_time' => now()->addDays(15),
            'is_active' => true,
        ]);

        // Question 1: Choix unique sur les philosophes
        $question1 = \App\Models\Question::create([
            'exam_id' => $philoExam->id,
            'content' => '
## Histoire de la Philosophie

### Question :
Quel philosophe grec est considéré comme le fondateur de la philosophie occidentale et a développé la méthode dialectique ?

### Contexte :
Ce philosophe n\'a laissé aucun écrit et nous le connaissons principalement par les dialogues de son élève Platon.',
            'type' => 'one_choice',
            'points' => 4,
        ]);

        $choices1 = [
            ['content' => 'Socrate', 'is_correct' => true],
            ['content' => 'Aristote', 'is_correct' => false],
            ['content' => 'Platon', 'is_correct' => false],
            ['content' => 'Pythagore', 'is_correct' => false],
        ];

        foreach ($choices1 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question1->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 2: Choix multiples sur l'éthique
        $question2 = \App\Models\Question::create([
            'exam_id' => $philoExam->id,
            'content' => '
## Éthique et Morale

### Question :
Quelles sont les caractéristiques de l\'éthique déontologique selon Kant ?

**Sélectionnez toutes les propositions correctes :**',
            'type' => 'multiple',
            'points' => 10,
        ]);

        $choices2 = [
            ['content' => 'Se base sur l\'intention plutôt que sur les conséquences', 'is_correct' => true],
            ['content' => 'Utilise l\'impératif catégorique comme principe', 'is_correct' => true],
            ['content' => 'Juge les actions uniquement par leurs résultats', 'is_correct' => false],
            ['content' => 'Considère que certaines actions sont intrinsèquement bonnes ou mauvaises', 'is_correct' => true],
            ['content' => 'Prône le relativisme moral', 'is_correct' => false],
        ];

        foreach ($choices2 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question2->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 3: Vrai/Faux sur la métaphysique
        $question3 = \App\Models\Question::create([
            'exam_id' => $philoExam->id,
            'content' => '
## Métaphysique

### Affirmation :
Selon René Descartes, la seule chose dont on ne peut douter est l\'existence de la pensée elle-même, d\'où sa célèbre formule "Cogito ergo sum" (Je pense, donc je suis).

**Cette affirmation résume-t-elle correctement le cogito cartésien ?**',
            'type' => 'boolean',
            'points' => 5,
        ]);

        $choices3 = [
            ['content' => 'Vrai', 'is_correct' => true],
            ['content' => 'Faux', 'is_correct' => false],
        ];

        foreach ($choices3 as $choiceData) {
            \App\Models\Choice::create([
                'question_id' => $question3->id,
                'content' => $choiceData['content'],
                'is_correct' => $choiceData['is_correct'],
            ]);
        }

        // Question 4: Question ouverte sur l'éthique
        \App\Models\Question::create([
            'exam_id' => $philoExam->id,
            'content' => '
## Dissertation Philosophique

### Sujet :
**"La liberté consiste-t-elle à faire ce que l\'on veut ?"**

### Instructions :
Rédigez une dissertation philosophique structurée qui répond à cette question.

### Structure attendue :
1. **Introduction** (présentation du problème, définition des termes, annonce du plan)
2. **Développement** en plusieurs parties argumentées
3. **Conclusion** (synthèse et ouverture)

### Critères d\'évaluation :
- **Compréhension du sujet** (5 points)
- **Qualité de l\'argumentation** (10 points)
- **Références philosophiques** (5 points)
- **Structure et expression** (5 points)

### Conseils :
- Mobilisez vos connaissances sur les philosophes étudiés
- Distinguez liberté formelle et liberté réelle
- Interrogez les notions de volonté, contrainte, et autodétermination',
            'type' => 'text',
            'points' => 25,
        ]);
    }
}
