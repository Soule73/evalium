<?php

namespace Tests\Feature\Commands;

use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\ClassSubject;
use App\Models\Enrollment;
use App\Notifications\AssessmentStartingSoonNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class SendAssessmentRemindersTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    /**
     * Build a supervised assessment scheduled in the reminder window with enrolled students.
     *
     * @return array{assessment: Assessment, students: \Illuminate\Support\Collection}
     */
    private function setupAssessmentInReminderWindow(int $minutesFromNow = 10, int $studentCount = 2): array
    {
        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->addMinutes($minutesFromNow),
            'duration_minutes' => 60,
            'is_published' => true,
            'reminder_sent_at' => null,
        ]);

        $students = collect();
        for ($i = 0; $i < $studentCount; $i++) {
            $student = $this->createStudent();
            Enrollment::factory()->create([
                'class_id' => $class->id,
                'student_id' => $student->id,
            ]);
            $students->push($student);
        }

        return ['assessment' => $assessment, 'students' => $students];
    }

    public function test_sends_reminders_to_enrolled_students(): void
    {
        Notification::fake();

        ['assessment' => $assessment, 'students' => $students] = $this->setupAssessmentInReminderWindow(10, 3);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        foreach ($students as $student) {
            Notification::assertSentTo($student, AssessmentStartingSoonNotification::class);
        }

        $assessment->refresh();
        $this->assertNotNull($assessment->reminder_sent_at);
    }

    public function test_does_not_send_duplicate_reminders(): void
    {
        Notification::fake();

        ['assessment' => $assessment] = $this->setupAssessmentInReminderWindow(10);

        $assessment->update(['reminder_sent_at' => now()]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_skips_unpublished_assessments(): void
    {
        Notification::fake();

        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->addMinutes(10),
            'duration_minutes' => 60,
            'is_published' => false,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_skips_assessments_without_scheduled_at(): void
    {
        Notification::fake();

        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        Assessment::factory()->homework()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => null,
            'due_date' => now()->addDay(),
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_skips_assessments_scheduled_too_far_in_future(): void
    {
        Notification::fake();

        $this->setupAssessmentInReminderWindow(30);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_skips_assessments_already_started(): void
    {
        Notification::fake();

        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->subMinutes(5),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class->id, 'student_id' => $student->id]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_notify_withdrawn_students(): void
    {
        Notification::fake();

        $classSubject = ClassSubject::factory()->create();
        $class = ClassModel::find($classSubject->class_id);

        Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->addMinutes(10),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $withdrawn = $this->createStudent();
        Enrollment::factory()->withdrawn()->create([
            'class_id' => $class->id,
            'student_id' => $withdrawn->id,
        ]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Notification::assertNotSentTo($withdrawn, AssessmentStartingSoonNotification::class);
    }

    public function test_sets_reminder_sent_at_even_with_no_active_students(): void
    {
        Notification::fake();

        $classSubject = ClassSubject::factory()->create();

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->addMinutes(10),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        $assessment->refresh();
        $this->assertNotNull($assessment->reminder_sent_at);

        Notification::assertNothingSent();
    }

    public function test_handles_multiple_assessments_in_window(): void
    {
        Notification::fake();

        $classSubject1 = ClassSubject::factory()->create();
        $class1 = ClassModel::find($classSubject1->class_id);

        $assessment1 = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject1->id,
            'teacher_id' => $classSubject1->teacher_id,
            'scheduled_at' => now()->addMinutes(8),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $classSubject2 = ClassSubject::factory()->create();
        $class2 = ClassModel::find($classSubject2->class_id);

        $assessment2 = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject2->id,
            'teacher_id' => $classSubject2->teacher_id,
            'scheduled_at' => now()->addMinutes(12),
            'duration_minutes' => 90,
            'is_published' => true,
        ]);

        $student1 = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class1->id, 'student_id' => $student1->id]);

        $student2 = $this->createStudent();
        Enrollment::factory()->create(['class_id' => $class2->id, 'student_id' => $student2->id]);

        $this->artisan('notifications:send-reminders')->assertSuccessful();

        Notification::assertSentTo($student1, AssessmentStartingSoonNotification::class);
        Notification::assertSentTo($student2, AssessmentStartingSoonNotification::class);

        $assessment1->refresh();
        $assessment2->refresh();
        $this->assertNotNull($assessment1->reminder_sent_at);
        $this->assertNotNull($assessment2->reminder_sent_at);
    }

    public function test_notification_contains_correct_data(): void
    {
        $classSubject = ClassSubject::factory()->create();

        $assessment = Assessment::factory()->supervised()->create([
            'class_subject_id' => $classSubject->id,
            'teacher_id' => $classSubject->teacher_id,
            'scheduled_at' => now()->addMinutes(10),
            'duration_minutes' => 60,
            'is_published' => true,
        ]);

        $student = $this->createStudent();
        $notification = new AssessmentStartingSoonNotification($assessment);
        $data = $notification->toDatabase($student);

        $this->assertSame('assessment_starting_soon', $data['type']);
        $this->assertSame($assessment->id, $data['assessment_id']);
        $this->assertSame($assessment->title, $data['assessment_title']);
        $this->assertArrayHasKey('scheduled_at', $data);
        $this->assertArrayHasKey('url', $data);
    }
}
