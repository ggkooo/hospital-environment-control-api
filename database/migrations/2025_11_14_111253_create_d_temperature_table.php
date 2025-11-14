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
        Schema::create('d_temperature', function (Blueprint $table) {
            $table->id();
            $table->decimal('avg_value', 8, 2);
            $table->decimal('min_value', 8, 2);
            $table->decimal('max_value', 8, 2);
            $table->decimal('std_dev', 8, 4)->nullable();
            $table->integer('hour_count'); // número de horas processadas no dia (máx. 24)
            $table->decimal('variation_range', 8, 2);
            $table->decimal('daily_trend', 8, 4)->nullable(); // tendência do dia (slope)
            $table->decimal('peak_hour_avg', 8, 2)->nullable(); // hora com maior média
            $table->decimal('valley_hour_avg', 8, 2)->nullable(); // hora com menor média
            $table->time('peak_hour')->nullable(); // horário do pico
            $table->time('valley_hour')->nullable(); // horário do vale
            $table->date('day_date'); // data do dia (YYYY-MM-DD)

            $table->index('day_date');
            $table->unique('day_date'); // garante uma entrada por dia
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('d_temperature');
    }
};
