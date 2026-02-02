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
        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign(['assignment_id']);
            $table->renameColumn('assignment_id', 'assessment_assignment_id');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->foreign('assessment_assignment_id')->references('id')->on('assessment_assignments')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropForeign(['assessment_assignment_id']);
            $table->renameColumn('assessment_assignment_id', 'assignment_id');
        });

        Schema::table('answers', function (Blueprint $table) {
            $table->foreign('assignment_id')->references('id')->on('exam_assignments')->cascadeOnDelete();
        });
    }
};
