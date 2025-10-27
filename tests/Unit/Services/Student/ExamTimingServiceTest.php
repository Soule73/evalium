<?php

namespace Tests\Unit\Services\Student;

use Tests\TestCase;
use App\Models\Exam;
use App\Models\ExamAssignment;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\Student\ExamTimingService;

class ExamTimingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ExamTimingService $timingService;
    private Exam $exam;
    private ExamAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();

        // Fixer le temps actuel pour les tests
        $now = Carbon::now();
        Carbon::setTestNow($now);

        $this->timingService = new ExamTimingService();

        // Créer un examen de base (60 minutes) - CORRIGÉ : utilise 'duration'
        $this->exam = Exam::factory()->create([
            'duration' => 60,
            'start_time' => $now->copy()->subHour(),
            'end_time' => $now->copy()->addHours(3),
        ]);

        // Créer une assignation démarrée il y a 30 minutes
        $this->assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => $now->copy()->subMinutes(30),
            'status' => 'started',
        ]);

        // IMPORTANT : Recharger la relation pour avoir l'exam avec duration
        $this->assignment->load('exam');
    }

    protected function tearDown(): void
    {
        // Réinitialiser le temps après chaque test
        Carbon::setTestNow();
        parent::tearDown();
    }

    /** @test */
    public function exam_is_accessible_between_start_and_end_time()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::now()->subHour(),
            'end_time' => Carbon::now()->addHour(),
        ]);

        $result = $this->timingService->validateExamTiming($exam);

        $this->assertTrue($result);
    }

    /** @test */
    public function exam_is_not_accessible_before_start_time()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
        ]);

        $result = $this->timingService->validateExamTiming($exam);

        $this->assertFalse($result);
    }

    /** @test */
    public function exam_is_not_accessible_after_end_time()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
        ]);

        $result = $this->timingService->validateExamTiming($exam);

        $this->assertFalse($result);
    }

    /** @test */
    public function exam_without_timing_constraints_is_always_accessible()
    {
        $exam = Exam::factory()->create([
            'start_time' => null,
            'end_time' => null,
        ]);

        $result = $this->timingService->validateExamTiming($exam);

        $this->assertTrue($result);
    }

    /** @test */
    public function can_check_accessibility_with_custom_time()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::parse('2025-10-23 10:00:00'),
            'end_time' => Carbon::parse('2025-10-23 12:00:00'),
        ]);

        $testTime = Carbon::parse('2025-10-23 11:00:00');
        $result = $this->timingService->validateExamTiming($exam, $testTime);

        $this->assertTrue($result);
    }

    /** @test */
    public function is_exam_accessible_is_alias_of_validate_exam_timing()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::now()->subHour(),
            'end_time' => Carbon::now()->addHour(),
        ]);

        $validateResult = $this->timingService->validateExamTiming($exam);
        $accessibleResult = $this->timingService->isExamAccessible($exam);

        $this->assertEquals($validateResult, $accessibleResult);
    }

    /** @test */
    public function calculates_time_remaining_correctly()
    {
        // L'assignment a démarré il y a 30 minutes, durée de 60 minutes
        // Il devrait rester environ 30 minutes (1800 secondes)
        $timeRemaining = $this->timingService->getTimeRemaining($this->assignment);

        $this->assertNotNull($timeRemaining);
        $this->assertGreaterThan(1700, $timeRemaining); // ~28.3 min
        $this->assertLessThan(1900, $timeRemaining);    // ~31.6 min
    }

    /** @test */
    public function returns_null_for_time_remaining_when_not_started()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => null,
        ]);

        $timeRemaining = $this->timingService->getTimeRemaining($assignment);

        $this->assertNull($timeRemaining);
    }

    /** @test */
    public function returns_null_for_time_remaining_when_no_duration()
    {
        // Simuler un exam sans durée en utilisant 0
        $exam = Exam::factory()->create(['duration' => 0]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'started_at' => Carbon::now()->subMinutes(30),
        ]);
        $assignment->load('exam');

        $timeRemaining = $this->timingService->getTimeRemaining($assignment);

        $this->assertNull($timeRemaining);
    }

    /** @test */
    public function returns_zero_when_exam_time_is_expired()
    {
        // Assignment démarré il y a 90 minutes avec durée de 60 minutes
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => Carbon::now()->subMinutes(90),
        ]);
        $assignment->load('exam');

        $timeRemaining = $this->timingService->getTimeRemaining($assignment);

        $this->assertEquals(0, $timeRemaining);
    }

    /** @test */
    public function detects_expired_exam()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => Carbon::now()->subMinutes(90), // Dépassé de 30 min
        ]);
        $assignment->load('exam');

        $result = $this->timingService->isExamExpired($assignment);

        $this->assertTrue($result);
    }

    /** @test */
    public function detects_non_expired_exam()
    {
        $result = $this->timingService->isExamExpired($this->assignment);

        $this->assertFalse($result);
    }

    /** @test */
    public function exam_without_duration_never_expires()
    {
        // Simuler un exam sans limite de temps avec duration = 0
        $exam = Exam::factory()->create(['duration' => 0]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'started_at' => Carbon::now()->subDays(10), // Très vieux
        ]);
        $assignment->load('exam');

        $result = $this->timingService->isExamExpired($assignment);

        $this->assertFalse($result);
    }

    /** @test */
    public function calculates_exam_end_time_correctly()
    {
        $expectedEndTime = $this->assignment->started_at->copy()->addMinutes(60);
        $actualEndTime = $this->timingService->calculateExamEndTime($this->assignment);

        $this->assertEquals(
            $expectedEndTime->timestamp,
            $actualEndTime->timestamp
        );
    }

    /** @test */
    public function uses_default_duration_when_not_set()
    {
        // Utiliser duration = 0 pour simuler "pas de durée définie"
        $exam = Exam::factory()->create(['duration' => 0]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'started_at' => Carbon::now(),
        ]);
        $assignment->load('exam');

        $endTime = $this->timingService->calculateExamEndTime($assignment);
        $expectedEndTime = Carbon::now()->addMinutes(60); // Default 60

        $this->assertEquals(
            $expectedEndTime->timestamp,
            $endTime->timestamp
        );
    }

    /** @test */
    public function gets_exam_duration_from_exam()
    {
        $duration = $this->timingService->getExamDuration($this->exam);

        $this->assertEquals(60, $duration);
    }

    /** @test */
    public function returns_default_duration_when_not_set()
    {
        // Utiliser duration = 0 pour tester le cas par défaut
        $exam = Exam::factory()->create(['duration' => 0]);
        $duration = $this->timingService->getExamDuration($exam);

        $this->assertEquals(60, $duration);
    }

    /** @test */
    public function calculates_time_elapsed_percentage()
    {
        // Démarré il y a 30 minutes sur 60 minutes = 50%
        $percentage = $this->timingService->getTimeElapsedPercentage($this->assignment);

        $this->assertGreaterThan(49.0, $percentage);
        $this->assertLessThan(51.0, $percentage);
    }

    /** @test */
    public function returns_zero_percentage_when_not_started()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => null,
        ]);

        $percentage = $this->timingService->getTimeElapsedPercentage($assignment);

        $this->assertEquals(0.0, $percentage);
    }

    /** @test */
    public function returns_zero_percentage_when_no_duration()
    {
        // Utiliser duration = 0
        $exam = Exam::factory()->create(['duration' => 0]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'started_at' => Carbon::now()->subMinutes(30),
        ]);
        $assignment->load('exam');

        $percentage = $this->timingService->getTimeElapsedPercentage($assignment);

        $this->assertEquals(0.0, $percentage);
    }

    /** @test */
    public function percentage_caps_at_100()
    {
        // Démarré il y a 90 minutes sur 60 minutes = > 100%
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => Carbon::now()->subMinutes(90),
        ]);
        $assignment->load('exam');

        $percentage = $this->timingService->getTimeElapsedPercentage($assignment);

        $this->assertEquals(100.0, $percentage);
    }

    /** @test */
    public function detects_near_expiration()
    {
        // Durée 60 minutes, démarré il y a 57 minutes (reste 3 min = 5%)
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => Carbon::now()->subMinutes(57),
        ]);
        $assignment->load('exam');

        $result = $this->timingService->isNearExpiration($assignment);

        $this->assertTrue($result);
    }

    /** @test */
    public function does_not_detect_near_expiration_when_plenty_of_time()
    {
        // Durée 60 minutes, démarré il y a 30 minutes (reste 30 min = 50%)
        $result = $this->timingService->isNearExpiration($this->assignment);

        $this->assertFalse($result);
    }

    /** @test */
    public function formats_time_remaining_correctly()
    {
        // 30 minutes restantes = 00:30:00
        $formatted = $this->timingService->formatTimeRemaining($this->assignment);

        $this->assertMatchesRegularExpression('/^00:(29|30|31):.*$/', $formatted);
    }

    /** @test */
    public function formats_hours_correctly()
    {
        $exam = Exam::factory()->create(['duration' => 150]); // 2h30
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'started_at' => Carbon::now()->subMinutes(30), // Reste 2h
        ]);
        $assignment->load('exam');

        $formatted = $this->timingService->formatTimeRemaining($assignment);

        // Tolérance d'1 minute car le temps s'écoule pendant le test
        $this->assertMatchesRegularExpression('/^(01:59|02:00):.*$/', $formatted);
    }

    /** @test */
    public function returns_null_for_format_when_no_time_limit()
    {
        // Utiliser duration = 0
        $exam = Exam::factory()->create(['duration' => 0]);
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $exam->id,
            'started_at' => Carbon::now()->subMinutes(30),
        ]);
        $assignment->load('exam');

        $formatted = $this->timingService->formatTimeRemaining($assignment);

        $this->assertNull($formatted);
    }

    /** @test */
    public function returns_zero_time_when_expired()
    {
        $assignment = ExamAssignment::factory()->create([
            'exam_id' => $this->exam->id,
            'started_at' => Carbon::now()->subMinutes(90), // Expiré
        ]);
        $assignment->load('exam');

        $formatted = $this->timingService->formatTimeRemaining($assignment);

        $this->assertEquals('00:00:00', $formatted);
    }

    /** @test */
    public function is_within_availability_window_when_no_constraints()
    {
        $exam = Exam::factory()->create([
            'start_time' => null,
            'end_time' => null,
        ]);

        $result = $this->timingService->isWithinAvailabilityWindow($exam);

        $this->assertTrue($result);
    }

    /** @test */
    public function is_within_availability_window_when_in_range()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::now()->subHour(),
            'end_time' => Carbon::now()->addHour(),
        ]);

        $result = $this->timingService->isWithinAvailabilityWindow($exam);

        $this->assertTrue($result);
    }

    /** @test */
    public function is_not_within_availability_window_when_too_early()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::now()->addHour(),
            'end_time' => Carbon::now()->addHours(2),
        ]);

        $result = $this->timingService->isWithinAvailabilityWindow($exam);

        $this->assertFalse($result);
    }

    /** @test */
    public function is_not_within_availability_window_when_too_late()
    {
        $exam = Exam::factory()->create([
            'start_time' => Carbon::now()->subHours(2),
            'end_time' => Carbon::now()->subHour(),
        ]);

        $result = $this->timingService->isWithinAvailabilityWindow($exam);

        $this->assertFalse($result);
    }
}
