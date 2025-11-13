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
        Schema::create('h_temperature', function (Blueprint $table) {
            $table->id();
            $table->decimal('avg_value', 8, 2);
            $table->decimal('min_value', 8, 2);
            $table->decimal('max_value', 8, 2);
            $table->decimal('std_dev', 8, 4)->nullable();
            $table->integer('minute_count'); // número de minutos processados na hora
            $table->decimal('variation_range', 8, 2);
            $table->decimal('hourly_trend', 8, 4)->nullable(); // tendência da hora (slope)
            $table->timestamp('hour_timestamp'); // timestamp da hora (YYYY-MM-DD HH:00:00)

            $table->index('hour_timestamp');
            $table->unique('hour_timestamp'); // garante uma entrada por hora
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('h_temperature');
    }
};
