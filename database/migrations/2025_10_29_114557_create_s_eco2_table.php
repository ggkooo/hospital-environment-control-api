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
        Schema::create('s_eco2', function (Blueprint $table) {
            $table->id();
            $table->integer('value')->comment('CO2 equivalente em ppm');
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
        Schema::dropIfExists('s_eco2');
    }
};
