<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Replace student_id with enrollment_id in assessment_assignments.
 *
 * Links assignments to enrollments instead of directly to users,
 * preserving class and academic year context.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->foreignId('enrollment_id')
                ->nullable()
                ->after('assessment_id')
                ->constrained('enrollments')
                ->cascadeOnDelete();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('
                UPDATE assessment_assignments aa
                JOIN assessments a ON a.id = aa.assessment_id
                JOIN class_subjects cs ON cs.id = a.class_subject_id
                JOIN enrollments e ON e.student_id = aa.student_id AND e.class_id = cs.class_id
                SET aa.enrollment_id = e.id
            ');
        }

        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropIndex(['student_id', 'submitted_at']);
            $table->dropUnique(['assessment_id', 'student_id']);
            $table->dropForeign(['student_id']);
            $table->dropColumn('student_id');

            $table->foreignId('enrollment_id')->nullable(false)->change();

            $table->unique(['assessment_id', 'enrollment_id']);
            $table->index(['enrollment_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->foreignId('student_id')
                ->nullable()
                ->after('assessment_id')
                ->constrained('users')
                ->cascadeOnDelete();
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('
                UPDATE assessment_assignments aa
                JOIN enrollments e ON e.id = aa.enrollment_id
                SET aa.student_id = e.student_id
            ');
        }

        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropIndex(['enrollment_id', 'submitted_at']);
            $table->dropUnique(['assessment_id', 'enrollment_id']);
            $table->dropForeign(['enrollment_id']);
            $table->dropColumn('enrollment_id');

            $table->foreignId('student_id')->nullable(false)->change();

            $table->unique(['assessment_id', 'student_id']);
            $table->index(['student_id', 'submitted_at']);
        });
    }
};
