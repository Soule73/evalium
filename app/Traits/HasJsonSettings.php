<?php

namespace App\Traits;

/**
 * Trait for models that store configuration in a JSON column.
 *
 * Provides helper methods to get/set individual keys within a JSON column
 * without needing to manually handle the array access pattern.
 */
trait HasJsonSettings
{
    /**
     * Get a value from the settings JSON column.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if key not found
     * @return mixed The setting value or default
     */
    protected function getSettingValue(string $key, mixed $default = null): mixed
    {
        $settings = $this->settings ?? [];

        return $settings[$key] ?? $default;
    }

    /**
     * Set a value in the settings JSON column.
     *
     * @param  string  $key  The setting key
     * @param  mixed  $value  The value to set
     */
    protected function setSettingValue(string $key, mixed $value): void
    {
        $settings = $this->settings ?? [];
        $settings[$key] = $value;
        $this->settings = $settings;
    }

    /**
     * Get a boolean value from the settings JSON column.
     *
     * @param  string  $key  The setting key
     * @param  bool  $default  Default value if key not found
     * @return bool The boolean value
     */
    protected function getBooleanSetting(string $key, bool $default = false): bool
    {
        return (bool) $this->getSettingValue($key, $default);
    }

    /**
     * Check if a setting key exists in the settings JSON column.
     *
     * @param  string  $key  The setting key
     * @return bool True if key exists
     */
    protected function hasSetting(string $key): bool
    {
        $settings = $this->settings ?? [];

        return array_key_exists($key, $settings);
    }

    /**
     * Remove a setting key from the settings JSON column.
     *
     * @param  string  $key  The setting key to remove
     */
    protected function removeSetting(string $key): void
    {
        $settings = $this->settings ?? [];
        unset($settings[$key]);
        $this->settings = $settings;
    }

    /**
     * Get all settings as an array.
     *
     * @return array All settings
     */
    protected function getAllSettings(): array
    {
        return $this->settings ?? [];
    }

    /**
     * Merge new settings with existing settings.
     *
     * @param  array  $newSettings  Settings to merge
     */
    protected function mergeSettings(array $newSettings): void
    {
        $settings = $this->settings ?? [];
        $this->settings = array_merge($settings, $newSettings);
    }
}
