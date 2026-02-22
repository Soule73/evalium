<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add a dedicated `is_published` boolean column to the assessments table,
     * migrating the existing value from the `settings` JSON field.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->boolean('is_published')->default(false)->after('settings');
        });

        DB::table('assessments')->lazyById()->each(function (object $row) {
            $settings = json_decode($row->settings ?? '{}', true) ?? [];
            $isPublished = (bool) ($settings['is_published'] ?? false);

            unset($settings['is_published']);

            DB::table('assessments')
                ->where('id', $row->id)
                ->update([
                    'is_published' => $isPublished,
                    'settings' => json_encode($settings),
                ]);
        });
    }

    /**
     * Reverse the migration: move `is_published` back into the JSON settings column.
     */
    public function down(): void
    {
        DB::table('assessments')->lazyById()->each(function (object $row) {
            $settings = json_decode($row->settings ?? '{}', true) ?? [];
            $settings['is_published'] = (bool) $row->is_published;

            DB::table('assessments')
                ->where('id', $row->id)
                ->update(['settings' => json_encode($settings)]);
        });

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
