<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * General application settings (school name, logo, locale).
 */
class GeneralSettings extends Settings
{
    public string $school_name;

    public ?string $logo_path;

    public string $default_locale;

    public static function group(): string
    {
        return 'general';
    }
}
