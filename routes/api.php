<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorDataController;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::middleware('api.key')->group(function () {
    Route::post('/sensor-data', [SensorDataController::class, 'store'])
        ->name('sensor.data.store');

    Route::post('/sensor-data/single', [SensorDataController::class, 'storeSingle'])
        ->name('sensor.data.store.single');

    Route::get('/sensor-data/raw/latest', [SensorDataController::class, 'getLatestData'])
        ->name('sensor.data.raw.latest');

    Route::get('/sensor-data/raw/all', [SensorDataController::class, 'getAllData'])
        ->name('sensor.data.raw.all');

    Route::get('/sensor-data/raw/{type}', [SensorDataController::class, 'getData'])
        ->name('sensor.data.raw.get')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/raw/{type}/stats', [SensorDataController::class, 'getStats'])
        ->name('sensor.data.raw.stats')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/minute/all', [SensorDataController::class, 'getAllMinuteData'])
        ->name('sensor.data.minute.all');

    Route::get('/sensor-data/minute/{type}', [SensorDataController::class, 'getMinuteData'])
        ->name('sensor.data.minute.get')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/minute/{type}/variations', [SensorDataController::class, 'getVariations'])
        ->name('sensor.data.minute.variations')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/minute/{type}/comparison', [SensorDataController::class, 'getComparison'])
        ->name('sensor.data.minute.comparison')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');
});
