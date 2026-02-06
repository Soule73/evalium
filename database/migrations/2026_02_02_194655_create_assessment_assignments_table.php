<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assessment_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained('assessments')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('teacher_notes')->nullable();
            $table->timestamps();

            $table->unique(['assessment_id', 'student_id']);
            $table->index(['student_id', 'submitted_at']);
            $table->index(['assessment_id', 'score']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE assessment_assignments ADD CONSTRAINT check_score CHECK (score IS NULL OR (score >= 0 AND score <= 20))');
            DB::statement('ALTER TABLE assessment_assignments ADD CONSTRAINT check_times CHECK (started_at IS NULL OR started_at >= assigned_at)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessment_assignments');
    }
};
