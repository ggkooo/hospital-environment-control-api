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
        Schema::create('d_noise', function (Blueprint $table) {
            $table->id();
            $table->decimal('avg_value', 8, 2);
            $table->decimal('min_value', 8, 2);
            $table->decimal('max_value', 8, 2);
            $table->decimal('std_dev', 8, 4)->nullable();
            $table->integer('hour_count');
            $table->decimal('variation_range', 8, 2);
            $table->decimal('daily_trend', 8, 4)->nullable();
            $table->decimal('peak_hour_avg', 8, 2)->nullable();
            $table->decimal('valley_hour_avg', 8, 2)->nullable();
            $table->time('peak_hour')->nullable();
            $table->time('valley_hour')->nullable();
            $table->date('day_date');

            $table->index('day_date');
            $table->unique('day_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('d_noise');
    }
};
