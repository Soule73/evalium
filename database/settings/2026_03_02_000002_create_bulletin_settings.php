<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('bulletin.show_ranking', true);
        $this->migrator->add('bulletin.show_class_average', true);
        $this->migrator->add('bulletin.show_min_max', false);
    }
};
