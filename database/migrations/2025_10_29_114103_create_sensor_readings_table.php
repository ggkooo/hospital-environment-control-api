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
        Schema::create('sensor_readings', function (Blueprint $table) {
            $table->id();
            $table->decimal('temperature', 5, 2)->comment('Temperatura em graus Celsius');
            $table->decimal('humidity', 5, 2)->comment('Umidade relativa em %');
            $table->decimal('noise', 6, 2)->comment('Nível de ruído em dB');
            $table->decimal('pression', 7, 2)->comment('Pressão atmosférica em hPa');
            $table->integer('eco2')->comment('CO2 equivalente em ppm');
            $table->integer('tvoc')->comment('Total Volatile Organic Compounds em ppb');
            $table->timestamp('sensor_timestamp')->comment('Timestamp do sensor');
            $table->timestamp('received_at')->comment('Timestamp de recebimento');
            $table->string('file_origin')->nullable()->comment('Arquivo JSON de origem');
            $table->timestamps();

            // Índices para otimizar consultas
            $table->index('sensor_timestamp');
            $table->index('received_at');
            $table->index(['sensor_timestamp', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sensor_readings');
    }
};
