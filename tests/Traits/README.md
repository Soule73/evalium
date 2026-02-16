# Test Traits Documentation

## Vue d'ensemble

Ce système de traits réutilisables simplifie la création de données de test et élimine la duplication de code dans les tests PHPUnit.

## Traits disponibles

### 1. `InteractsWithTestData` (Trait principal - recommandé)

Ce trait combine tous les autres traits et configure automatiquement les rôles et permissions.

**Usage de base :**
```php
use Tests\Traits\InteractsWithTestData;

class MyTest extends TestCase
{
    use InteractsWithTestData;
    
    // setUp() appelle automatiquement seedRolesAndPermissions()
}
```

**Méthodes rapides :**
```php
// Configuration basique (admin, teacher, student)
$data = $this->setupBasicTestData();
$admin = $data->admin;
$teacher = $data->teacher;
$student = $data->student;

// Scénario examen simple
$data = $this->setupExamTestData(studentCount: 3);
$exam = $data->exam;
$teacher = $data->teacher;
$students = $data->students; // Array de 3 étudiants

// Scénario avec groupes
$data = $this->setupGroupTestData(studentsPerGroup: 5, groupCount: 2);
$groups = $data->groups; // Array de 2 groupes avec 5 étudiants chacun

// Scénario complet (examen + groupe + assignments variés)
$data = $this->setupCompleteExamScenario();
$exam = $data->exam;
$group = $data->group; // Groupe avec 3 étudiants
$assignments = $data->assignments; // 1 started, 1 submitted, 1 graded
```

---

### 2. `CreatesTestUsers`

Gestion des utilisateurs avec rôles.

**Méthodes :**
```php
// Seeder automatique (appelé dans InteractsWithTestData::setUp())
$this->seedRolesAndPermissions();

// Créer des utilisateurs avec rôles
$admin = $this->createAdmin(['email' => 'admin@test.com']);
$teacher = $this->createTeacher(['name' => 'John Teacher']);
$student = $this->createStudent();

// Rôle personnalisé
$user = $this->createUserWithRole('custom_role', ['email' => 'custom@test.com']);

// Créer plusieurs utilisateurs
$students = $this->createMultipleStudents(5);
$teachers = $this->createMultipleTeachers(3);
```

---

### 3. `CreatesTestExams`

Création d'examens avec questions et choix.

**Méthodes :**
```php
// Examen avec 3 questions à choix unique (par défaut)
$exam = $this->createExamWithQuestions($teacher);

// Examen avec 5 questions
$exam = $this->createExamWithQuestions(
    teacher: $teacher, 
    examAttributes: ['title' => 'Math Test'], 
    questionCount: 5
);

// Examen avec question texte
$exam = $this->createTextQuestionExam($teacher);

// Examen avec question à choix multiples
$exam = $this->createMultipleChoiceQuestionExam($teacher);

// Ajouter une question à un examen existant
$question = $this->createQuestionForExam(
    exam: $exam, 
    type: 'one_choice',
    attributes: ['points' => 15]
);

// Types supportés: 'one_choice', 'multiple', 'text', 'boolean'
```

---

### 4. `CreatesTestGroups`

Gestion des groupes et niveaux.

**Méthodes :**
```php
// Groupe avec 3 étudiants
$group = $this->createGroupWithStudents(3, ['name' => 'Group A']);

// Groupe vide
$group = $this->createEmptyGroup(['name' => 'Empty Group']);

// Ajouter des étudiants à un groupe existant
$students = $this->createMultipleStudents(5);
$this->addStudentsToGroup($group, $students);

// Créer un niveau
$level = $this->createLevel(['name' => 'Niveau 1']);

// Créer plusieurs groupes
$groups = $this->createMultipleGroups(
    count: 3, 
    studentsPerGroup: 4
);
```

---

### 5. `CreatesTestAssignments`

Création d'assignments et réponses.

**Méthodes :**
```php
// Assignment simple (not started)
$assignment = $this->createAssignmentForStudent($exam, $student);

// Assignment commencé
$assignment = $this->createStartedAssignment($exam, $student);

// Assignment soumis
$assignment = $this->createSubmittedAssignment($exam, $student);

// Assignment noté
$assignment = $this->createGradedAssignment(
    exam: $exam, 
    student: $student, 
    score: 85.5
);

// Assigner examen à un groupe
$this->assignExamToGroup($exam, $group, $teacher);

// Créer des réponses
$answer = $this->createAnswerForQuestion($assignment, $question);
$answers = $this->createAnswersForAllQuestions($assignment, $exam);

// Créer plusieurs assignments avec statuts variés
$assignments = $this->createMultipleAssignments(
    exam: $exam, 
    students: $students, 
    status: 'graded'
);
// status: null, 'started', 'submitted', 'graded'
```

---

## Exemples d'utilisation

### Exemple 1: Test simple de service

```php
use Tests\Traits\InteractsWithTestData;
use PHPUnit\Framework\Attributes\Test;

class ExamServiceTest extends TestCase
{
    use InteractsWithTestData;

    #[Test]
    public function teacher_can_create_exam()
    {
        $teacher = $this->createTeacher();
        
        $exam = $this->examService->createExam([
            'title' => 'Math Exam',
            'teacher_id' => $teacher->id,
        ]);
        
        $this->assertInstanceOf(Exam::class, $exam);
    }
}
```

### Exemple 2: Test avec données complexes

```php
use Tests\Traits\InteractsWithTestData;
use PHPUnit\Framework\Attributes\Test;

class ExamStatsServiceTest extends TestCase
{
    use InteractsWithTestData;

    #[Test]
    public function calculates_group_stats_correctly()
    {
        // Setup rapide
        $data = $this->setupCompleteExamScenario();
        
        // $data contient: teacher, exam, group, students, assignments
        $stats = $this->examStatsService->calculateGroupStats(
            $data->exam, 
            $data->group
        );
        
        $this->assertEquals(3, $stats['total_assigned']);
        $this->assertEquals(1, $stats['in_progress']);
        $this->assertEquals(1, $stats['completed']);
    }
}
```

### Exemple 3: Test avant/après refactoring

**❌ AVANT (avec duplication):**
```php
public function test_student_can_submit_exam()
{
    // 15 lignes de setup répétitif
    Role::create(['name' => 'teacher']);
    Role::create(['name' => 'student']);
    
    $teacher = User::factory()->create();
    $teacher->assignRole('teacher');
    
    $student = User::factory()->create();
    $student->assignRole('student');
    
    $exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
    $question = Question::factory()->create(['exam_id' => $exam->id]);
    // ... plus de setup
    
    $assignment = ExamAssignment::factory()->create([
        'exam_id' => $exam->id,
        'student_id' => $student->id,
        'started_at' => now(),
    ]);
    
    // Test réel commence ici
    $this->examService->submit($assignment);
    $this->assertNotNull($assignment->fresh()->submitted_at);
}
```

**✅ APRÈS (avec traits):**
```php
use InteractsWithTestData;

public function test_student_can_submit_exam()
{
    // 3 lignes de setup
    $teacher = $this->createTeacher();
    $exam = $this->createExamWithQuestions($teacher);
    $assignment = $this->createStartedAssignment($exam, $this->createStudent());
    
    // Test réel
    $this->examService->submit($assignment);
    $this->assertNotNull($assignment->fresh()->submitted_at);
}
```

---

## Migration des tests existants

### Étape 1: Ajouter le trait

```php
use Tests\Traits\InteractsWithTestData;

class MyExistingTest extends TestCase
{
    use InteractsWithTestData; // Ajouter cette ligne
```

### Étape 2: Supprimer seedRolesAndPermissions() manuel

```php
// ❌ SUPPRIMER (géré automatiquement par le trait)
protected function setUp(): void
{
    parent::setUp();
    $this->seed(RoleAndPermissionSeeder::class);
}
```

### Étape 3: Remplacer création users

```php
// ❌ AVANT
$teacher = User::factory()->create();
$teacher->assignRole('teacher');

// ✅ APRÈS
$teacher = $this->createTeacher();
```

### Étape 4: Remplacer création exams

```php
// ❌ AVANT
$exam = Exam::factory()->create(['teacher_id' => $teacher->id]);
$question = Question::factory()->create([
    'exam_id' => $exam->id,
    'type' => 'one_choice',
    'points' => 10,
]);
Choice::factory()->count(4)->create(['question_id' => $question->id]);

// ✅ APRÈS
$exam = $this->createExamWithQuestions($teacher);
```

### Étape 5: Remplacer création assignments

```php
// ❌ AVANT
$assignment = ExamAssignment::factory()->create([
    'exam_id' => $exam->id,
    'student_id' => $student->id,
    'started_at' => now(),
]);

// ✅ APRÈS
$assignment = $this->createStartedAssignment($exam, $student);
```

---

## Conventions

### Nommage
- Méthodes `create*` retournent une instance
- Méthodes `setup*` retournent un objet avec plusieurs propriétés
- Méthodes `add*` modifient des données existantes

### Paramètres
- Toujours nommer les paramètres lors de l'appel pour la clarté:
  ```php
  // ✅ BIEN
  $exam = $this->createExamWithQuestions(
      teacher: $teacher,
      examAttributes: ['title' => 'Test'],
      questionCount: 5
  );
  
  // ❌ MAL
  $exam = $this->createExamWithQuestions($teacher, ['title' => 'Test'], 5);
  ```

### Performance
- `setupCompleteExamScenario()` crée beaucoup de données - utiliser seulement si nécessaire
- Préférer créer uniquement les données nécessaires au test

---

## Checklist de refactoring

- [ ] Ajouter `use InteractsWithTestData`
- [ ] Supprimer setup manuel de RoleAndPermissionSeeder
- [ ] Remplacer `User::factory()->create() + assignRole()` par `createTeacher()`
- [ ] Remplacer création manuelle d'exams par `createExamWithQuestions()`
- [ ] Remplacer création manuelle de groups par `createGroupWithStudents()`
- [ ] Remplacer création manuelle d'assignments par `createStartedAssignment()` etc.
- [ ] Vérifier que les tests passent: `php artisan test`
- [ ] Commit avec message: `refactor(tests): use InteractsWithTestData trait`

---

## Résultat attendu

**Avant refactoring:**
- ~2000 lignes de code dupliqué
- Setup complexe et répétitif
- Difficile à maintenir

**Après refactoring:**
- ~500 lignes de code (75% réduction)
- Setup en 1-3 lignes
- Facile à comprendre et maintenir
- Tests plus lisibles et focalisés sur la logique métier

---

## Support

Pour ajouter de nouvelles méthodes aux traits, suivre le pattern existant:
1. Créer la méthode dans le trait approprié
2. Ajouter la documentation ici
3. Tester avec un exemple concret
4. Commit et partager avec l'équipe
