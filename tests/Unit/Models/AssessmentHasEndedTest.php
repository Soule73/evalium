<?php

namespace Tests\Unit\Models;

use App\Enums\DeliveryMode;
use App\Models\Assessment;
use Tests\TestCase;

class AssessmentHasEndedTest extends TestCase
{
    // Homework — due_date

    public function test_homework_has_ended_when_due_date_is_in_the_past(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Homework,
            'due_date' => now()->subDay(),
        ]);

        $this->assertTrue($assessment->hasEnded());
    }

    public function test_homework_has_not_ended_when_due_date_is_in_the_future(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Homework,
            'due_date' => now()->addDay(),
        ]);

        $this->assertFalse($assessment->hasEnded());
    }

    public function test_homework_has_not_ended_when_due_date_is_null(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Homework,
            'due_date' => null,
        ]);

        $this->assertFalse($assessment->hasEnded());
    }

    // Supervised — scheduled_at + duration_minutes

    public function test_supervised_has_ended_when_ends_at_is_in_the_past(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => now()->subHours(2),
            'duration_minutes' => 60,
        ]);

        $this->assertTrue($assessment->hasEnded());
    }

    public function test_supervised_has_not_ended_when_ends_at_is_in_the_future(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => now()->subMinutes(10),
            'duration_minutes' => 60,
        ]);

        $this->assertFalse($assessment->hasEnded());
    }

    // Edge case 4.9 — scheduled_at set, duration_minutes null

    public function test_supervised_has_ended_when_scheduled_at_passed_and_duration_is_null(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => now()->subMinutes(5),
            'duration_minutes' => null,
        ]);

        $this->assertTrue($assessment->hasEnded());
    }

    public function test_supervised_has_not_ended_when_scheduled_at_is_future_and_duration_is_null(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => now()->addHour(),
            'duration_minutes' => null,
        ]);

        $this->assertFalse($assessment->hasEnded());
    }

    public function test_supervised_has_not_ended_when_both_scheduled_at_and_duration_are_null(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => null,
            'duration_minutes' => null,
        ]);

        $this->assertFalse($assessment->hasEnded());
    }

    // getEndsAtAttribute edge cases

    public function test_ends_at_returns_scheduled_at_plus_duration_when_both_set(): void
    {
        $scheduledAt = now()->subHours(2);

        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 60,
        ]);

        $expected = $scheduledAt->copy()->addMinutes(60)->toDateTimeString();

        $this->assertEquals($expected, $assessment->ends_at->toDateTimeString());
    }

    public function test_ends_at_returns_scheduled_at_when_duration_is_null(): void
    {
        $scheduledAt = now()->subHour();

        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => null,
        ]);

        $this->assertEquals($scheduledAt->toDateTimeString(), $assessment->ends_at->toDateTimeString());
    }

    public function test_ends_at_returns_null_when_scheduled_at_is_null(): void
    {
        $assessment = Assessment::make([
            'delivery_mode' => DeliveryMode::Supervised,
            'scheduled_at' => null,
            'duration_minutes' => 60,
        ]);

        $this->assertNull($assessment->ends_at);
    }
}
