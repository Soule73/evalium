<?php

namespace Tests\Feature\Services\Core;

use App\Services\Core\GradeReport\RemarkGeneratorService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RemarkGeneratorServiceTest extends TestCase
{
    private RemarkGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RemarkGeneratorService;
    }

    #[Test]
    public function it_returns_excellent_for_grade_above_16(): void
    {
        $result = $this->service->forGrade(18.0);
        $this->assertEquals(__('messages.remark_excellent'), $result);
    }

    #[Test]
    public function it_returns_good_for_grade_between_14_and_16(): void
    {
        $result = $this->service->forGrade(15.0);
        $this->assertEquals(__('messages.remark_good'), $result);
    }

    #[Test]
    public function it_returns_fairly_good_for_grade_between_12_and_14(): void
    {
        $result = $this->service->forGrade(13.0);
        $this->assertEquals(__('messages.remark_fairly_good'), $result);
    }

    #[Test]
    public function it_returns_satisfactory_for_grade_between_10_and_12(): void
    {
        $result = $this->service->forGrade(10.5);
        $this->assertEquals(__('messages.remark_satisfactory'), $result);
    }

    #[Test]
    public function it_returns_insufficient_for_grade_below_10(): void
    {
        $result = $this->service->forGrade(7.0);
        $this->assertEquals(__('messages.remark_insufficient'), $result);
    }

    #[Test]
    public function it_returns_no_grade_for_null(): void
    {
        $result = $this->service->forGrade(null);
        $this->assertEquals(__('messages.remark_no_grade'), $result);
    }

    #[Test]
    public function it_generates_remarks_for_multiple_subjects(): void
    {
        $subjects = [
            ['class_subject_id' => 1, 'subject_name' => 'Math', 'grade' => 18.0],
            ['class_subject_id' => 2, 'subject_name' => 'French', 'grade' => 8.0],
            ['class_subject_id' => 3, 'subject_name' => 'Physics', 'grade' => null],
        ];

        $result = $this->service->forSubjects($subjects);

        $this->assertCount(3, $result);
        $this->assertEquals(1, $result[0]['class_subject_id']);
        $this->assertEquals(__('messages.remark_excellent'), $result[0]['remark']);
        $this->assertTrue($result[0]['auto_generated']);
        $this->assertEquals(__('messages.remark_insufficient'), $result[1]['remark']);
        $this->assertEquals(__('messages.remark_no_grade'), $result[2]['remark']);
    }

    #[Test]
    public function it_generates_general_remark_for_average(): void
    {
        $this->assertEquals(__('messages.remark_excellent'), $this->service->forOverallAverage(17.0));
        $this->assertEquals(__('messages.remark_insufficient'), $this->service->forOverallAverage(5.0));
        $this->assertEquals(__('messages.remark_no_grade'), $this->service->forOverallAverage(null));
    }

    #[Test]
    public function it_handles_boundary_values_correctly(): void
    {
        $this->assertEquals(__('messages.remark_excellent'), $this->service->forGrade(16.0));
        $this->assertEquals(__('messages.remark_good'), $this->service->forGrade(14.0));
        $this->assertEquals(__('messages.remark_fairly_good'), $this->service->forGrade(12.0));
        $this->assertEquals(__('messages.remark_satisfactory'), $this->service->forGrade(10.0));
        $this->assertEquals(__('messages.remark_insufficient'), $this->service->forGrade(9.99));
    }
}
