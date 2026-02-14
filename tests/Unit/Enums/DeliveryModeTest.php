<?php

namespace Tests\Unit\Enums;

use App\Enums\DeliveryMode;
use Tests\TestCase;

class DeliveryModeTest extends TestCase
{
    public function test_has_supervised_and_homework_cases(): void
    {
        $cases = DeliveryMode::cases();

        $this->assertCount(2, $cases);
        $this->assertSame('supervised', DeliveryMode::Supervised->value);
        $this->assertSame('homework', DeliveryMode::Homework->value);
    }

    public function test_values_returns_all_string_values(): void
    {
        $values = DeliveryMode::values();

        $this->assertSame(['supervised', 'homework'], $values);
    }

    public function test_default_for_type_examen_is_supervised(): void
    {
        $this->assertSame(DeliveryMode::Supervised, DeliveryMode::defaultForType('examen'));
    }

    public function test_default_for_type_controle_is_supervised(): void
    {
        $this->assertSame(DeliveryMode::Supervised, DeliveryMode::defaultForType('controle'));
    }

    public function test_default_for_type_devoir_is_homework(): void
    {
        $this->assertSame(DeliveryMode::Homework, DeliveryMode::defaultForType('devoir'));
    }

    public function test_default_for_type_tp_is_homework(): void
    {
        $this->assertSame(DeliveryMode::Homework, DeliveryMode::defaultForType('tp'));
    }

    public function test_default_for_type_projet_is_homework(): void
    {
        $this->assertSame(DeliveryMode::Homework, DeliveryMode::defaultForType('projet'));
    }

    public function test_is_supervised_mode_helper(): void
    {
        $this->assertTrue(DeliveryMode::Supervised->isSupervisedMode());
        $this->assertFalse(DeliveryMode::Homework->isSupervisedMode());
    }

    public function test_is_homework_mode_helper(): void
    {
        $this->assertTrue(DeliveryMode::Homework->isHomeworkMode());
        $this->assertFalse(DeliveryMode::Supervised->isHomeworkMode());
    }

    public function test_label_returns_readable_string(): void
    {
        $this->assertSame(__('messages.delivery_mode_supervised'), DeliveryMode::Supervised->label());
        $this->assertSame(__('messages.delivery_mode_homework'), DeliveryMode::Homework->label());
    }
}
