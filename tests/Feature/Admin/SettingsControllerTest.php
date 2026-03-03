<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Settings\BulletinSettings;
use App\Settings\GeneralSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use Tests\Traits\CreatesTestUsers;

class SettingsControllerTest extends TestCase
{
    use CreatesTestUsers, RefreshDatabase;

    private User $admin;

    private User $teacher;

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRolesAndPermissions();
        config()->set('inertia.testing.page_paths', [resource_path('ts/Pages')]);

        $this->admin = $this->createAdmin();
        $this->teacher = $this->createTeacher();
        $this->student = $this->createStudent();
    }

    // ---------------------------------------------------------------
    // Index
    // ---------------------------------------------------------------

    public function test_guest_cannot_access_settings(): void
    {
        $this->get(route('admin.settings.index'))->assertRedirect(route('login'));
    }

    public function test_student_cannot_access_settings(): void
    {
        $this->actingAs($this->student)->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_teacher_cannot_access_settings(): void
    {
        $this->actingAs($this->teacher)->get(route('admin.settings.index'))->assertForbidden();
    }

    public function test_admin_can_access_settings(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.settings.index'))
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component('Admin/Settings/Index')
                    ->has('general')
                    ->has('bulletin')
            );
    }

    // ---------------------------------------------------------------
    // Update General
    // ---------------------------------------------------------------

    public function test_admin_can_update_general_settings(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update-general'), [
                'school_name' => 'Test School',
                'default_locale' => 'en',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = app(GeneralSettings::class);
        $this->assertEquals('Test School', $settings->school_name);
        $this->assertEquals('en', $settings->default_locale);
    }

    public function test_general_settings_validation_requires_school_name(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update-general'), [
                'school_name' => '',
                'default_locale' => 'fr',
            ])
            ->assertSessionHasErrors('school_name');
    }

    public function test_general_settings_validation_rejects_invalid_locale(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update-general'), [
                'school_name' => 'Test',
                'default_locale' => 'de',
            ])
            ->assertSessionHasErrors('default_locale');
    }

    public function test_teacher_cannot_update_general_settings(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('admin.settings.update-general'), [
                'school_name' => 'Hacked',
                'default_locale' => 'en',
            ])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Update Bulletin
    // ---------------------------------------------------------------

    public function test_admin_can_update_bulletin_settings(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.settings.update-bulletin'), [
                'show_ranking' => false,
                'show_class_average' => false,
                'show_min_max' => true,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = app(BulletinSettings::class);
        $this->assertFalse($settings->show_ranking);
        $this->assertFalse($settings->show_class_average);
        $this->assertTrue($settings->show_min_max);
    }

    public function test_teacher_cannot_update_bulletin_settings(): void
    {
        $this->actingAs($this->teacher)
            ->put(route('admin.settings.update-bulletin'), [
                'show_ranking' => false,
                'show_class_average' => false,
                'show_min_max' => false,
            ])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Upload Logo
    // ---------------------------------------------------------------

    public function test_admin_can_upload_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.settings.upload-logo'), [
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = app(GeneralSettings::class);
        $this->assertNotNull($settings->logo_path);
        Storage::disk('public')->assertExists($settings->logo_path);
    }

    public function test_logo_upload_rejects_non_image_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.settings.upload-logo'), [
                'logo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('logo');
    }

    public function test_logo_upload_rejects_oversized_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin)
            ->post(route('admin.settings.upload-logo'), [
                'logo' => UploadedFile::fake()->image('big.png')->size(3000),
            ])
            ->assertSessionHasErrors('logo');
    }

    public function test_teacher_cannot_upload_logo(): void
    {
        Storage::fake('public');

        $this->actingAs($this->teacher)
            ->post(route('admin.settings.upload-logo'), [
                'logo' => UploadedFile::fake()->image('logo.png', 200, 200),
            ])
            ->assertForbidden();
    }

    // ---------------------------------------------------------------
    // Delete Logo
    // ---------------------------------------------------------------

    public function test_admin_can_delete_logo(): void
    {
        Storage::fake('public');

        $settings = app(GeneralSettings::class);
        Storage::disk('public')->put('logos/test.png', 'fake-content');
        $settings->logo_path = 'logos/test.png';
        $settings->save();

        $this->actingAs($this->admin)
            ->delete(route('admin.settings.delete-logo'))
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = app(GeneralSettings::class);
        $this->assertNull($settings->logo_path);
        Storage::disk('public')->assertMissing('logos/test.png');
    }

    public function test_teacher_cannot_delete_logo(): void
    {
        $this->actingAs($this->teacher)
            ->delete(route('admin.settings.delete-logo'))
            ->assertForbidden();
    }
}
