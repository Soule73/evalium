<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - Add performance indexes
     */
    public function up(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->index(['assessment_assignment_id', 'question_id'], 'answers_assignment_question_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index(['email', 'is_active'], 'users_email_active_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table) {
            $table->dropIndex('answers_assignment_question_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_email_active_idx');
        });
    }
};
