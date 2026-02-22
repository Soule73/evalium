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
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_subject_id')->constrained('class_subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['homework', 'exam', 'practical', 'quiz', 'project']);
            $table->enum('delivery_mode', ['supervised', 'homework'])->default('supervised');
            $table->decimal('coefficient', 5, 2);
            $table->integer('duration_minutes')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('due_date')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_published')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['class_subject_id', 'type']);
            $table->index(['teacher_id', 'scheduled_at']);
            $table->index(['delivery_mode']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE assessments ADD CONSTRAINT check_assessment_coefficient CHECK (coefficient > 0)');
            DB::statement('ALTER TABLE assessments ADD CONSTRAINT check_duration CHECK (duration_minutes IS NULL OR duration_minutes > 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assessments');
    }
};
