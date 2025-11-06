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
        // Mettre à jour les enregistrements existants avant de modifier la structure
        // Les assignments avec status 'assigned' ou 'started' n'ont pas encore été soumis
        // donc on met leur status à NULL (ils seront gérés par les timestamps)
        DB::table('exam_assignments')
            ->whereIn('status', ['assigned', 'started'])
            ->update(['status' => null]);

        // Pour SQLite, nous devons utiliser une approche différente
        if (DB::getDriverName() === 'sqlite') {
            // SQLite nécessite une reconstruction de table
            Schema::table('exam_assignments', function (Blueprint $table) {
                // Supprimer l'ancienne colonne
                $table->dropColumn('status');
            });

            Schema::table('exam_assignments', function (Blueprint $table) {
                // Recréer avec les nouveaux statuts
                $table->enum('status', ['submitted', 'graded'])->nullable()->after('auto_score');
            });
        } else {
            // Pour MySQL/MariaDB, utiliser ALTER TABLE MODIFY
            DB::statement("ALTER TABLE exam_assignments MODIFY COLUMN status ENUM('submitted', 'graded') NULL ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
