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
        Schema::create('s_humidity', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 5, 2)->comment('Umidade relativa em %');
            $table->timestamp('timestamp')->comment('Timestamp do sensor');

            // Índice para otimizar consultas
            $table->index('timestamp');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('s_humidity');
    }
};
