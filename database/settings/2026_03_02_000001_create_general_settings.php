<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.school_name', 'Evalium');
        $this->migrator->add('general.logo_path', null);
        $this->migrator->add('general.default_locale', config('app.locale', 'en'));
    }
};
