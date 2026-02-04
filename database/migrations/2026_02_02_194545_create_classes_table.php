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
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('level_id')->constrained('levels')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('max_students')->nullable();
            $table->timestamps();

            $table->unique(['academic_year_id', 'level_id', 'name']);
            $table->index(['academic_year_id', 'level_id']);
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE classes ADD CONSTRAINT check_max_students CHECK (max_students IS NULL OR max_students > 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
