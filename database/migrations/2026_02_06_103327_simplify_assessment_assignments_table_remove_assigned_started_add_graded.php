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
        if (! Schema::hasColumn('assessment_assignments', 'graded_at')) {
            Schema::table('assessment_assignments', function (Blueprint $table) {
                $table->timestamp('graded_at')->nullable()->after('submitted_at');
            });
        }

        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement('ALTER TABLE assessment_assignments DROP CHECK check_times');
            } catch (\Exception $e) {
                // Contrainte n'existe pas, continuer
            }
        }

        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->dropColumn(['assigned_at', 'started_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessment_assignments', function (Blueprint $table) {
            $table->timestamp('assigned_at')->after('student_id');
            $table->timestamp('started_at')->nullable()->after('assigned_at');
            $table->dropColumn('graded_at');
        });

        DB::statement('ALTER TABLE assessment_assignments ADD CONSTRAINT check_times CHECK (started_at IS NULL OR started_at >= assigned_at)');
    }
};
