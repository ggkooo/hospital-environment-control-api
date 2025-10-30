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
        Schema::create('m_pressure', function (Blueprint $table) {
            $table->id();
            $table->decimal('avg_value', 8, 2);
            $table->decimal('min_value', 8, 2);
            $table->decimal('max_value', 8, 2);
            $table->decimal('std_dev', 8, 4)->nullable();
            $table->integer('reading_count');
            $table->decimal('variation_range', 8, 2);
            $table->timestamp('minute_timestamp');

            $table->index('minute_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('m_pressure');
    }
};
