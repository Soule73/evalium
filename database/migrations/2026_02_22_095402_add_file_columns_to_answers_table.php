<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrate file-submission architecture from AssignmentAttachment (disconnected) to
 * Answer (unified). File columns are added to answers so a QuestionType::File question
 * produces a single Answer row holding the uploaded file metadata.
 *
 * Also removes per-assessment file constraint columns (max_files, max_file_size,
 * allowed_extensions) — these are now system-wide via config/assessment.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('answer_text');
            $table->string('file_path')->nullable()->after('file_name');
            $table->unsignedInteger('file_size')->nullable()->after('file_path');
            $table->string('mime_type')->nullable()->after('file_size');
        });

        Schema::dropIfExists('assignment_attachments');

        Schema::table('assessments', function (Blueprint $table) {
            $table->dropColumn(['max_files', 'max_file_size', 'allowed_extensions']);
        });
    }

    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->unsignedInteger('max_file_size')->nullable();
            $table->string('allowed_extensions')->nullable();
            $table->unsignedInteger('max_files')->default(0);
        });

        Schema::create('assignment_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_assignment_id')->constrained('assessment_assignments')->cascadeOnDelete();
            $table->string('file_name');
            $table->string('file_path');
            $table->unsignedInteger('file_size');
            $table->string('mime_type');
            $table->timestamp('uploaded_at');
            $table->timestamps();

            $table->index('assessment_assignment_id');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_path', 'file_size', 'mime_type']);
        });
    }
};
