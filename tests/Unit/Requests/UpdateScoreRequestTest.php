<?php

namespace Tests\Unit\Requests;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\User;
use App\Models\Question;
use App\Models\ExamAssignment;
use Spatie\Permission\Models\Role;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\Exam\UpdateScoreRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdateScoreRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private Exam $exam;
    private Question $question;
    private \App\Models\ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer les rôles
        Role::create(['name' => 'teacher']);
        Role::create(['name' => 'student']);

        // Créer un enseignant
        $this->teacher = User::factory()->create([
            'email' => 'teacher@test.com',
        ]);
        $this->teacher->assignRole('teacher');

        // Créer un étudiant
        $this->student = User::factory()->create([
            'email' => 'student@test.com',
        ]);
        $this->student->assignRole('student');

        // Créer un examen
        $this->exam = Exam::factory()->create([
            'teacher_id' => $this->teacher->id,
            'title' => 'Test Exam'
        ]);

        // Créer une question
        $this->question = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 10
        ]);

        // Créer une assignation
        $this->assignment = \App\Models\ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'status' => 'submitted'
        ]);
    }

    #[Test]
    public function it_validates_required_fields()
    {
        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        $validator = Validator::make([], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('exam_id'));
        $this->assertTrue($validator->errors()->has('student_id'));
        $this->assertTrue($validator->errors()->has('question_id'));
        $this->assertTrue($validator->errors()->has('score'));
    }

    #[Test]
    public function it_validates_score_is_numeric()
    {
        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id,
            'score' => 'not-a-number'
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('score'));
    }

    #[Test]
    public function it_validates_score_minimum()
    {
        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id,
            'score' => -1
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('score'));
    }

    #[Test]
    public function it_validates_question_exists()
    {
        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => 999, // ID qui n'existe pas
            'score' => 8.5
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('question_id'));
    }

    #[Test]
    public function it_validates_teacher_notes_string()
    {
        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id,
            'score' => 8.5,
            'feedback' => ['not', 'a', 'string']
        ], $rules);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('feedback'));
    }

    #[Test]
    public function it_passes_validation_with_valid_data()
    {
        // Créer une réponse pour que l'étudiant ait bien répondu à la question
        \App\Models\Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'answer_text' => 'Some answer'
        ]);

        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id,
            'score' => 8.5,
            'feedback' => 'Good answer but could be improved'
        ], $rules);

        // Appliquer le withValidator pour les validations customisées
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_passes_validation_without_optional_teacher_notes()
    {
        // Créer une réponse pour que l'étudiant ait bien répondu à la question
        \App\Models\Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $this->question->id,
            'answer_text' => 'Some answer'
        ]);

        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        $validator = Validator::make([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $this->question->id,
            'score' => 8.5
        ], $rules);

        // Appliquer le withValidator pour les validations customisées
        $request->withValidator($validator);

        $this->assertFalse($validator->fails());
    }

    #[Test]
    public function it_has_correct_error_messages()
    {
        $request = new UpdateScoreRequest();
        $messages = $request->messages();

        $this->assertArrayHasKey('exam_id.required', $messages);
        $this->assertArrayHasKey('exam_id.exists', $messages);
        $this->assertArrayHasKey('student_id.required', $messages);
        $this->assertArrayHasKey('student_id.exists', $messages);
        $this->assertArrayHasKey('question_id.required', $messages);
        $this->assertArrayHasKey('question_id.exists', $messages);
        $this->assertArrayHasKey('score.required', $messages);
        $this->assertArrayHasKey('score.numeric', $messages);
        $this->assertArrayHasKey('score.min', $messages);
    }

    #[Test]
    public function it_validates_score_against_question_points()
    {
        // Créer une question avec 5 points maximum
        $questionWith5Points = Question::factory()->create([
            'exam_id' => $this->exam->id,
            'type' => 'text',
            'points' => 5
        ]);

        // Créer une réponse pour que l'étudiant ait bien répondu à la question
        \App\Models\Answer::factory()->create([
            'assignment_id' => $this->assignment->id,
            'question_id' => $questionWith5Points->id,
            'answer_text' => 'Some answer'
        ]);

        $request = new UpdateScoreRequest();
        $rules = $request->rules();

        // Tenter de donner un score de 10 (plus que les 5 points maximum)
        $validator = Validator::make([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'question_id' => $questionWith5Points->id,
            'score' => 10
        ], $rules);

        // Appliquer le withValidator pour les validations customisées
        $request->withValidator($validator);

        // Maintenant cette validation devrait échouer grâce au withValidator
        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('score'));
    }
}
