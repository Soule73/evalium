<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Set delivery_mode based on assessment type for existing records.
     *
     * examen/controle -> supervised, others -> homework
     */
    public function up(): void
    {
        DB::table('assessments')
            ->whereNotIn('type', ['examen', 'controle'])
            ->where('delivery_mode', 'supervised')
            ->update(['delivery_mode' => 'homework']);
    }

    /**
     * Revert all delivery_mode values to supervised (original default).
     */
    public function down(): void
    {
        DB::table('assessments')
            ->where('delivery_mode', 'homework')
            ->update(['delivery_mode' => 'supervised']);
    }
};
