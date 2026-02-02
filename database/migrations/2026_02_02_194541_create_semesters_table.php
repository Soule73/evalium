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
        Schema::create('semesters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->tinyInteger('order_number');
            $table->timestamps();

            $table->unique(['academic_year_id', 'order_number']);
            $table->index('academic_year_id');
        });

        DB::statement('ALTER TABLE semesters ADD CONSTRAINT check_semester_end_date CHECK (end_date > start_date)');
        DB::statement('ALTER TABLE semesters ADD CONSTRAINT check_semester_order CHECK (order_number BETWEEN 1 AND 2)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semesters');
    }
};
