<?php

namespace Tests\Feature\Notifications;

use App\Models\Assessment;
use App\Models\ClassModel;
use App\Models\Enrollment;
use App\Models\User;
use App\Notifications\AssessmentGradedNotification;
use App\Notifications\AssessmentPublishedNotification;
use App\Notifications\AssessmentSubmittedNotification;
use App\Services\Core\AssessmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class AssessmentNotificationsTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    /**
     * Notification controller index returns notifications for authenticated user.
     */
    public function test_notification_index_returns_user_notifications(): void
    {
        $user = $this->createTeacher();

        DatabaseNotification::create([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'type' => AssessmentPublishedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode([
                'type' => 'assessment_published',
                'assessment_id' => 1,
                'assessment_title' => 'Test Assessment',
                'url' => '/student/assessments/1',
            ]),
            'read_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('notifications.index'));

        $response->assertOk()
            ->assertJsonStructure([
                'notifications',
                'unread_count',
                'has_more',
            ])
            ->assertJsonPath('unread_count', 1);
    }

    /**
     * Unauthenticated users cannot access the notification index.
     */
    public function test_notification_index_requires_auth(): void
    {
        $this->getJson(route('notifications.index'))
            ->assertUnauthorized();
    }

    /**
     * Mark a single notification as read updates read_at.
     */
    public function test_mark_notification_as_read(): void
    {
        $user = $this->createTeacher();

        $notificationId = \Illuminate\Support\Str::uuid()->toString();

        DatabaseNotification::create([
            'id' => $notificationId,
            'type' => AssessmentSubmittedNotification::class,
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'data' => json_encode(['type' => 'assessment_submitted', 'url' => '/']),
            'read_at' => null,
        ]);

        $this->actingAs($user)
            ->postJson(route('notifications.read', ['id' => $notificationId]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNotNull(
            DatabaseNotification::find($notificationId)?->read_at
        );
    }

    /**
     * Mark all notifications as read sets read_at on all unread entries.
     */
    public function test_mark_all_notifications_as_read(): void
    {
        $user = $this->createTeacher();

        foreach (range(1, 3) as $i) {
            DatabaseNotification::create([
                'id' => \Illuminate\Support\Str::uuid()->toString(),
                'type' => AssessmentSubmittedNotification::class,
                'notifiable_type' => User::class,
                'notifiable_id' => $user->id,
                'data' => json_encode(['type' => 'assessment_submitted', 'url' => '/']),
                'read_at' => null,
            ]);
        }

        $this->actingAs($user)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame(
            0,
            $user->unreadNotifications()->count()
        );
    }

    /**
     * Publishing an assessment sends AssessmentPublishedNotification to enrolled active students.
     */
    public function test_publish_assessment_sends_notification_to_active_students(): void
    {
        Notification::fake();

        $student = $this->createStudent();

        $class = ClassModel::factory()->create();
        Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
            'status' => 'active',
        ]);

        $classSubject = \App\Models\ClassSubject::factory()->create([
            'class_id' => $class->id,
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'is_published' => false,
        ]);

        $service = app(AssessmentService::class);
        $service->publishAssessment($assessment);

        Notification::assertSentTo($student, AssessmentPublishedNotification::class);
    }

    /**
     * Publishing an assessment does not notify withdrawn students.
     */
    public function test_publish_assessment_does_not_notify_withdrawn_students(): void
    {
        Notification::fake();

        $withdrawn = $this->createStudent();

        $class = ClassModel::factory()->create();
        Enrollment::factory()->withdrawn()->create([
            'class_id' => $class->id,
            'student_id' => $withdrawn->id,
        ]);

        $classSubject = \App\Models\ClassSubject::factory()->create([
            'class_id' => $class->id,
        ]);

        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
            'is_published' => false,
        ]);

        $service = app(AssessmentService::class);
        $service->publishAssessment($assessment);

        Notification::assertNotSentTo($withdrawn, AssessmentPublishedNotification::class);
    }

    /**
     * AssessmentPublishedNotification toDatabase returns the correct structure.
     */
    public function test_assessment_published_notification_to_database_has_expected_shape(): void
    {
        $classSubject = \App\Models\ClassSubject::factory()->create();
        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
        ]);

        $student = $this->createStudent();
        $notification = new AssessmentPublishedNotification($assessment);

        $data = $notification->toDatabase($student);

        $this->assertSame('assessment_published', $data['type']);
        $this->assertSame($assessment->id, $data['assessment_id']);
        $this->assertSame($assessment->title, $data['assessment_title']);
        $this->assertArrayHasKey('url', $data);
    }

    /**
     * AssessmentGradedNotification toDatabase returns the correct structure.
     */
    public function test_assessment_graded_notification_to_database_has_expected_shape(): void
    {
        $classSubject = \App\Models\ClassSubject::factory()->create();
        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
        ]);

        $student = $this->createStudent();
        $class = ClassModel::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);
        $assignment = \App\Models\AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $notification = new AssessmentGradedNotification($assessment, $assignment);
        $data = $notification->toDatabase($student);

        $this->assertSame('assessment_graded', $data['type']);
        $this->assertSame($assessment->id, $data['assessment_id']);
        $this->assertSame($assignment->id, $data['assignment_id']);
        $this->assertArrayHasKey('url', $data);
    }

    /**
     * AssessmentSubmittedNotification toDatabase returns the correct structure.
     */
    public function test_assessment_submitted_notification_to_database_has_expected_shape(): void
    {
        $teacher = $this->createTeacher();
        $student = $this->createStudent();
        $class = ClassModel::factory()->create();
        $enrollment = Enrollment::factory()->create([
            'class_id' => $class->id,
            'student_id' => $student->id,
        ]);
        $classSubject = \App\Models\ClassSubject::factory()->create([
            'teacher_id' => $teacher->id,
        ]);
        $assessment = Assessment::factory()->create([
            'class_subject_id' => $classSubject->id,
        ]);
        $assignment = \App\Models\AssessmentAssignment::factory()->create([
            'assessment_id' => $assessment->id,
            'enrollment_id' => $enrollment->id,
        ]);

        $notification = new AssessmentSubmittedNotification($assessment, $assignment);
        $data = $notification->toDatabase($teacher);

        $this->assertSame('assessment_submitted', $data['type']);
        $this->assertSame($assessment->id, $data['assessment_id']);
        $this->assertSame($assignment->id, $data['assignment_id']);
        $this->assertSame($student->name, $data['student_name']);
        $this->assertArrayHasKey('url', $data);
    }
}
