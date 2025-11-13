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
        Schema::create('h_eco2', function (Blueprint $table) {
            $table->id();
            $table->decimal('avg_value', 8, 2);
            $table->decimal('min_value', 8, 2);
            $table->decimal('max_value', 8, 2);
            $table->decimal('std_dev', 8, 4)->nullable();
            $table->integer('minute_count');
            $table->decimal('variation_range', 8, 2);
            $table->decimal('hourly_trend', 8, 4)->nullable();
            $table->timestamp('hour_timestamp');

            $table->index('hour_timestamp');
            $table->unique('hour_timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('h_eco2');
    }
};
