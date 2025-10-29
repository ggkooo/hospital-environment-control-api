<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorDataController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Endpoint principal para arrays de dados dos sensores
Route::post('/sensor-data', [SensorDataController::class, 'store'])
    ->name('sensor.data.store');

// Endpoint para compatibilidade com dados únicos
Route::post('/sensor-data/single', [SensorDataController::class, 'storeSingle'])
    ->name('sensor.data.store.single');
