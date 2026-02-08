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
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->boolean('forced_submission')->default(false)->after('teacher_notes');
            $table->string('security_violation')->nullable()->after('forced_submission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropColumn(['forced_submission', 'security_violation']);
        });
    }
};
