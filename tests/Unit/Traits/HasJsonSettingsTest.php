<?php

namespace Tests\Unit\Traits;

use App\Traits\HasJsonSettings;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class HasJsonSettingsTest extends TestCase
{
    private Model $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new class extends Model
        {
            use HasJsonSettings;

            protected $casts = [
                'settings' => 'array',
            ];

            protected $attributes = [
                'settings' => '{}',
            ];

            public function test_get_setting_value(string $key, mixed $default = null): mixed
            {
                return $this->getSettingValue($key, $default);
            }

            public function test_set_setting_value(string $key, mixed $value): void
            {
                $this->setSettingValue($key, $value);
            }

            public function test_get_boolean_setting(string $key, bool $default = false): bool
            {
                return $this->getBooleanSetting($key, $default);
            }

            public function test_has_setting(string $key): bool
            {
                return $this->hasSetting($key);
            }

            public function test_remove_setting(string $key): void
            {
                $this->removeSetting($key);
            }

            public function test_get_all_settings(): array
            {
                return $this->getAllSettings();
            }

            public function test_merge_settings(array $newSettings): void
            {
                $this->mergeSettings($newSettings);
            }
        };
    }

    public function test_get_setting_value_returns_default_when_key_missing(): void
    {
        $result = $this->model->testGetSettingValue('nonexistent', 'default_value');

        $this->assertEquals('default_value', $result);
    }

    public function test_get_setting_value_returns_value_when_key_exists(): void
    {
        $this->model->settings = ['key' => 'value'];

        $result = $this->model->testGetSettingValue('key', 'default');

        $this->assertEquals('value', $result);
    }

    public function test_set_setting_value_updates_settings_column(): void
    {
        $this->model->testSetSettingValue('new_key', 'new_value');

        $this->assertEquals('new_value', $this->model->settings['new_key']);
    }

    public function test_set_setting_value_preserves_existing_keys(): void
    {
        $this->model->settings = ['existing' => 'value'];
        $this->model->testSetSettingValue('new_key', 'new_value');

        $this->assertEquals('value', $this->model->settings['existing']);
        $this->assertEquals('new_value', $this->model->settings['new_key']);
    }

    public function test_get_boolean_setting_casts_to_boolean(): void
    {
        $this->model->settings = ['truthy' => 1, 'falsy' => 0];

        $this->assertTrue($this->model->testGetBooleanSetting('truthy'));
        $this->assertFalse($this->model->testGetBooleanSetting('falsy'));
    }

    public function test_get_boolean_setting_returns_default_when_key_missing(): void
    {
        $result = $this->model->testGetBooleanSetting('nonexistent', true);

        $this->assertTrue($result);
    }

    public function test_has_setting_returns_true_when_key_exists(): void
    {
        $this->model->settings = ['key' => 'value'];

        $this->assertTrue($this->model->testHasSetting('key'));
    }

    public function test_has_setting_returns_false_when_key_missing(): void
    {
        $this->assertFalse($this->model->testHasSetting('nonexistent'));
    }

    public function test_remove_setting_deletes_key(): void
    {
        $this->model->settings = ['key1' => 'value1', 'key2' => 'value2'];

        $this->model->testRemoveSetting('key1');

        $this->assertFalse(isset($this->model->settings['key1']));
        $this->assertTrue(isset($this->model->settings['key2']));
    }

    public function test_get_all_settings_returns_all_as_array(): void
    {
        $expected = ['key1' => 'value1', 'key2' => 'value2'];
        $this->model->settings = $expected;

        $result = $this->model->testGetAllSettings();

        $this->assertEquals($expected, $result);
    }

    public function test_merge_settings_combines_new_and_existing(): void
    {
        $this->model->settings = ['existing' => 'value1', 'override' => 'old'];

        $this->model->testMergeSettings(['override' => 'new', 'new_key' => 'value2']);

        $this->assertEquals('value1', $this->model->settings['existing']);
        $this->assertEquals('new', $this->model->settings['override']);
        $this->assertEquals('value2', $this->model->settings['new_key']);
    }

    public function test_merge_settings_preserves_unmodified_keys(): void
    {
        $this->model->settings = ['key1' => 'value1', 'key2' => 'value2'];

        $this->model->testMergeSettings(['key3' => 'value3']);

        $this->assertEquals('value1', $this->model->settings['key1']);
        $this->assertEquals('value2', $this->model->settings['key2']);
        $this->assertEquals('value3', $this->model->settings['key3']);
    }
}
