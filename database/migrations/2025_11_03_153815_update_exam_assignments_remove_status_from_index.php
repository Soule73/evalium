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
        // Rien à faire pour les nouvelles installations car l'index est déjà correct dans la migration initiale
        // Cette migration est uniquement pour les bases de données existantes en production
        try {
            Schema::table('exam_assignments', function (Blueprint $table) {
                // Supprimer l'ancien index composite avec status s'il existe
                $table->dropIndex('exam_assignments_exam_id_student_id_status_index');
            });
        } catch (\Exception $e) {
            // L'index n'existe pas, probablement une nouvelle installation
        }

        try {
            Schema::table('exam_assignments', function (Blueprint $table) {
                // Recréer l'index sans status
                $table->index(['exam_id', 'student_id']);
            });
        } catch (\Exception $e) {
            // L'index existe déjà
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_assignments', function (Blueprint $table) {
            // Supprimer l'index sans status
            $table->dropIndex(['exam_id', 'student_id']);

            // Recréer l'index avec status
            $table->index(['exam_id', 'student_id', 'status']);
        });
    }
};
