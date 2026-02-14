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
        Schema::table('assessments', function (Blueprint $table) {
            $table->enum('delivery_mode', ['supervised', 'homework'])->default('supervised')->after('type');
            $table->timestamp('due_date')->nullable()->after('scheduled_at');
            $table->unsignedInteger('max_file_size')->nullable()->after('due_date');
            $table->string('allowed_extensions')->nullable()->after('max_file_size');
            $table->unsignedInteger('max_files')->default(0)->after('allowed_extensions');

            $table->index(['delivery_mode']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assessments', function (Blueprint $table) {
            $table->dropIndex(['delivery_mode']);
            $table->dropColumn(['delivery_mode', 'due_date', 'max_file_size', 'allowed_extensions', 'max_files']);
        });
    }
};
