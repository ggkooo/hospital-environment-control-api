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
        Schema::create('s_pressure', function (Blueprint $table) {
            $table->id();
            $table->decimal('value', 7, 2)->comment('Pressão atmosférica em hPa');
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
        Schema::dropIfExists('s_pressure');
    }
};
