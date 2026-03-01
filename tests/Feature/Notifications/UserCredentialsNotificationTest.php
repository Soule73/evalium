<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\UserCredentialsNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class UserCredentialsNotificationTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
    }

    public function test_student_notification_uses_student_subject_and_features(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $mail = (new UserCredentialsNotification('secret123', 'student'))->toMail($user);

        $this->assertStringContainsStringIgnoringCase('student', $mail->subject);
        $this->assertStringContainsStringIgnoringCase('student', implode(' ', $mail->introLines));
    }

    public function test_teacher_notification_uses_teacher_subject_and_features(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $mail = (new UserCredentialsNotification('secret123', 'teacher'))->toMail($user);

        $this->assertStringContainsStringIgnoringCase('teacher', $mail->subject);
        $this->assertStringContainsStringIgnoringCase('teacher', implode(' ', $mail->introLines));
    }

    public function test_admin_notification_uses_admin_subject_and_features(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $mail = (new UserCredentialsNotification('secret123', 'admin'))->toMail($user);

        $this->assertStringContainsStringIgnoringCase('admin', $mail->subject);
        $this->assertStringContainsStringIgnoringCase('administrator', implode(' ', $mail->introLines));
    }

    public function test_super_admin_maps_to_admin_role_key(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $mail = (new UserCredentialsNotification('secret123', 'super_admin'))->toMail($user);

        $this->assertStringContainsStringIgnoringCase('admin', $mail->subject);
    }

    public function test_notification_includes_email_and_password_in_intro_lines(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com', 'locale' => 'en']);

        $mail = (new UserCredentialsNotification('my-pass-456', 'student'))->toMail($user);

        $lines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('student@example.com', $lines);
        $this->assertStringContainsString('my-pass-456', $lines);
    }

    public function test_notification_renders_in_french_for_french_locale_user(): void
    {
        $user = User::factory()->create(['locale' => 'fr']);

        $mail = (new UserCredentialsNotification('secret123', 'student'))->toMail($user);

        $this->assertStringContainsStringIgnoringCase('étudiant', $mail->subject);
    }

    public function test_notification_action_points_to_login_url(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $mail = (new UserCredentialsNotification('secret123', 'student'))->toMail($user);

        $this->assertStringContainsString('/login', $mail->actionUrl);
    }
}
