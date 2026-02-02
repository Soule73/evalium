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
        Schema::create('class_subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('semester_id')->nullable()->constrained('semesters')->nullOnDelete();
            $table->decimal('coefficient', 5, 2);
            $table->date('valid_from');
            $table->date('valid_to')->nullable();
            $table->timestamps();

            $table->index(['class_id', 'subject_id', 'valid_to']);
            $table->index(['teacher_id', 'valid_to']);
            $table->index('semester_id');
        });

        DB::statement('ALTER TABLE class_subjects ADD CONSTRAINT check_coefficient CHECK (coefficient > 0)');
        DB::statement('ALTER TABLE class_subjects ADD CONSTRAINT check_valid_dates CHECK (valid_to IS NULL OR valid_to >= valid_from)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_subjects');
    }
};
