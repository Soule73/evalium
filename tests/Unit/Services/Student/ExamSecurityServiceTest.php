<?php

namespace Tests\Unit\Services\Student;

use App\Models\Exam;
use App\Models\ExamAssignment;
use App\Models\User;
use App\Services\Student\ExamSecurityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Tests unitaires pour ExamSecurityService
 * 
 * Couvre :
 * - Logging des violations
 * - Détermination de la terminaison d'examen
 * - Soumission forcée suite à violation
 * - Validation de l'environnement d'examen
 * - Gestion de l'historique des violations
 */
class ExamSecurityServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamSecurityService $securityService;
    private Exam $exam;
    private User $student;
    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer le rôle student
        Role::create(['name' => 'student']);

        $this->securityService = new ExamSecurityService();

        $this->exam = Exam::factory()->create([
            'title' => 'Test Exam',
            'duration' => 60,
        ]);

        $this->student = User::factory()->create([
            'name' => 'Test Student',
            'email' => 'student@test.com',
        ]);
        $this->student->assignRole('student');

        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'student_id' => $this->student->id,
            'started_at' => Carbon::now(),
            'status' => 'started',
        ]);
    }

    /** @test */
    public function logs_security_violation()
    {
        Log::shouldReceive('warning')
            ->once()
            ->with('Security violation detected', \Mockery::on(function ($context) {
                return $context['assignment_id'] === $this->assignment->id
                    && $context['violation_type'] === ExamSecurityService::VIOLATION_TAB_SWITCH
                    && $context['student_id'] === $this->student->id;
            }));

        $this->securityService->logViolation(
            $this->assignment,
            ExamSecurityService::VIOLATION_TAB_SWITCH,
            'Student switched to another tab'
        );

        $this->assignment->refresh();
        $this->assertEquals(ExamSecurityService::VIOLATION_TAB_SWITCH, $this->assignment->security_violation);
    }

    /** @test */
    public function tab_switch_should_terminate_exam()
    {
        $shouldTerminate = $this->securityService->shouldTerminateExam(
            ExamSecurityService::VIOLATION_TAB_SWITCH
        );

        $this->assertTrue($shouldTerminate);
    }

    /** @test */
    public function fullscreen_exit_should_terminate_exam()
    {
        $shouldTerminate = $this->securityService->shouldTerminateExam(
            ExamSecurityService::VIOLATION_FULLSCREEN_EXIT
        );

        $this->assertTrue($shouldTerminate);
    }

    /** @test */
    public function browser_change_should_terminate_exam()
    {
        $shouldTerminate = $this->securityService->shouldTerminateExam(
            ExamSecurityService::VIOLATION_BROWSER_CHANGE
        );

        $this->assertTrue($shouldTerminate);
    }

    /** @test */
    public function copy_paste_should_not_terminate_exam()
    {
        $shouldTerminate = $this->securityService->shouldTerminateExam(
            ExamSecurityService::VIOLATION_COPY_PASTE
        );

        $this->assertFalse($shouldTerminate);
    }

    /** @test */
    public function suspicious_activity_should_not_terminate_exam()
    {
        $shouldTerminate = $this->securityService->shouldTerminateExam(
            ExamSecurityService::VIOLATION_SUSPICIOUS_ACTIVITY
        );

        $this->assertFalse($shouldTerminate);
    }

    /** @test */
    public function network_disconnect_should_not_terminate_exam()
    {
        $shouldTerminate = $this->securityService->shouldTerminateExam(
            ExamSecurityService::VIOLATION_NETWORK_DISCONNECT
        );

        $this->assertFalse($shouldTerminate);
    }

    /** @test */
    public function forces_submission_due_to_violation()
    {
        Log::shouldReceive('warning')->once();

        Carbon::setTestNow(Carbon::parse('2025-10-23 15:30:00'));

        $this->securityService->forceSubmitDueToViolation(
            $this->assignment,
            ExamSecurityService::VIOLATION_TAB_SWITCH,
            'Student opened a new tab'
        );

        $this->assignment->refresh();

        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertEquals(ExamSecurityService::VIOLATION_TAB_SWITCH, $this->assignment->security_violation);
        $this->assertTrue($this->assignment->forced_submission);
        $this->assertNotNull($this->assignment->submitted_at);
        $this->assertEquals('2025-10-23 15:30:00', $this->assignment->submitted_at->toDateTimeString());

        Carbon::setTestNow();
    }

    /** @test */
    public function handle_violation_terminates_exam_for_terminal_violations()
    {
        Log::shouldReceive('warning')->once();

        $terminated = $this->securityService->handleViolation(
            $this->assignment,
            ExamSecurityService::VIOLATION_TAB_SWITCH,
            'Tab switch detected'
        );

        $this->assertTrue($terminated);
        $this->assignment->refresh();
        $this->assertEquals('submitted', $this->assignment->status);
        $this->assertTrue($this->assignment->forced_submission);
    }

    /** @test */
    public function handle_violation_does_not_terminate_for_non_terminal_violations()
    {
        Log::shouldReceive('warning')->once();

        $terminated = $this->securityService->handleViolation(
            $this->assignment,
            ExamSecurityService::VIOLATION_COPY_PASTE,
            'Copy paste detected'
        );

        $this->assertFalse($terminated);
        $this->assignment->refresh();
        $this->assertEquals('started', $this->assignment->status);
        $this->assertFalse($this->assignment->forced_submission);
    }

    /** @test */
    public function gets_violation_history_with_no_violations()
    {
        $history = $this->securityService->getViolationHistory($this->assignment);

        $this->assertFalse($history['has_violation']);
        $this->assertNull($history['violation_type']);
        $this->assertFalse($history['forced_submission']);
    }

    /** @test */
    public function gets_violation_history_with_violations()
    {
        $this->assignment->update([
            'security_violation' => ExamSecurityService::VIOLATION_TAB_SWITCH,
            'forced_submission' => true,
            'submitted_at' => Carbon::parse('2025-10-23 15:00:00'),
        ]);

        $history = $this->securityService->getViolationHistory($this->assignment);

        $this->assertTrue($history['has_violation']);
        $this->assertEquals(ExamSecurityService::VIOLATION_TAB_SWITCH, $history['violation_type']);
        $this->assertTrue($history['forced_submission']);
        $this->assertEquals('2025-10-23 15:00:00', $history['submitted_at']);
    }

    /** @test */
    public function checks_if_assignment_has_violations()
    {
        $this->assertFalse($this->securityService->hasViolations($this->assignment));

        $this->assignment->update([
            'security_violation' => ExamSecurityService::VIOLATION_TAB_SWITCH,
        ]);

        $this->assertTrue($this->securityService->hasViolations($this->assignment));
    }

    /** @test */
    public function gets_violation_type()
    {
        $this->assertNull($this->securityService->getViolationType($this->assignment));

        $this->assignment->update([
            'security_violation' => ExamSecurityService::VIOLATION_FULLSCREEN_EXIT,
        ]);

        $this->assertEquals(
            ExamSecurityService::VIOLATION_FULLSCREEN_EXIT,
            $this->securityService->getViolationType($this->assignment)
        );
    }

    /** @test */
    public function checks_if_was_forced_submission()
    {
        $this->assertFalse($this->securityService->wasForcedSubmission($this->assignment));

        $this->assignment->update(['forced_submission' => true]);

        $this->assertTrue($this->securityService->wasForcedSubmission($this->assignment));
    }

    /** @test */
    public function returns_all_supported_violation_types()
    {
        $types = $this->securityService->getSupportedViolationTypes();

        $this->assertIsArray($types);
        $this->assertCount(6, $types);
        $this->assertContains(ExamSecurityService::VIOLATION_TAB_SWITCH, $types);
        $this->assertContains(ExamSecurityService::VIOLATION_COPY_PASTE, $types);
        $this->assertContains(ExamSecurityService::VIOLATION_FULLSCREEN_EXIT, $types);
        $this->assertContains(ExamSecurityService::VIOLATION_SUSPICIOUS_ACTIVITY, $types);
        $this->assertContains(ExamSecurityService::VIOLATION_BROWSER_CHANGE, $types);
        $this->assertContains(ExamSecurityService::VIOLATION_NETWORK_DISCONNECT, $types);
    }

    /** @test */
    public function returns_terminal_violations()
    {
        $terminals = $this->securityService->getTerminalViolations();

        $this->assertIsArray($terminals);
        $this->assertCount(3, $terminals);
        $this->assertContains(ExamSecurityService::VIOLATION_TAB_SWITCH, $terminals);
        $this->assertContains(ExamSecurityService::VIOLATION_FULLSCREEN_EXIT, $terminals);
        $this->assertContains(ExamSecurityService::VIOLATION_BROWSER_CHANGE, $terminals);
    }

    /** @test */
    public function validates_violation_type()
    {
        $this->assertTrue($this->securityService->isValidViolationType(
            ExamSecurityService::VIOLATION_TAB_SWITCH
        ));

        $this->assertTrue($this->securityService->isValidViolationType(
            ExamSecurityService::VIOLATION_COPY_PASTE
        ));

        $this->assertFalse($this->securityService->isValidViolationType('invalid_type'));
        $this->assertFalse($this->securityService->isValidViolationType(''));
    }
}
