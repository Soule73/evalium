<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Bulletin (grade report) display settings.
 */
class BulletinSettings extends Settings
{
    public bool $show_ranking;

    public bool $show_class_average;

    public bool $show_min_max;

    public static function group(): string
    {
        return 'bulletin';
    }
}
