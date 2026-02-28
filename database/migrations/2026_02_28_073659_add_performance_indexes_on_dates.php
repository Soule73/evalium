<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->index('scheduled_at');
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->index('enrolled_at');
        });

        Schema::table('class_subjects', function (Blueprint $table) {
            $table->index('valid_from');
        });

        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->index('started_at');
            $table->index('submitted_at');
            $table->index('graded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex(['scheduled_at']);
        });

        Schema::table('enrollments', function (Blueprint $table) {
            $table->dropIndex(['enrolled_at']);
        });

        Schema::table('class_subjects', function (Blueprint $table) {
            $table->dropIndex(['valid_from']);
        });

        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropIndex(['started_at']);
            $table->dropIndex(['submitted_at']);
            $table->dropIndex(['graded_at']);
        });
    }
};
