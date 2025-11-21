<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SensorDataController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\SectorController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\ApiDocumentationController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccessLogController;

// Rotas de documentação (sem autenticação)
Route::get('/docs', [ApiDocumentationController::class, 'index'])->name('api.docs');
Route::get('/docs/{page}', [ApiDocumentationController::class, 'show'])->name('api.docs.page');

// Rota de login
Route::post('/login', [AuthController::class, 'login']);

// Rota padrão do Sanctum (mantém autenticação original)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Todas as rotas da API requerem chave de autenticação
Route::middleware('api.key')->group(function () {
    // Rotas para armazenar dados
    Route::post('/sensor-data', [SensorDataController::class, 'store'])
        ->name('sensor.data.store');

    Route::post('/sensor-data/single', [SensorDataController::class, 'storeSingle'])
        ->name('sensor.data.store.single');

    // Rotas para dados brutos (raw)
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

    // Rotas para dados agregados por minuto
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

    // Rotas para dados agregados por hora
    Route::get('/sensor-data/hourly/all', [SensorDataController::class, 'getAllHourlyData'])
        ->name('sensor.data.hourly.all');

    Route::get('/sensor-data/hourly/{type}', [SensorDataController::class, 'getHourlyData'])
        ->name('sensor.data.hourly.get')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/hourly/{type}/stats', [SensorDataController::class, 'getHourlyStats'])
        ->name('sensor.data.hourly.stats')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/hourly/{type}/trends', [SensorDataController::class, 'getHourlyTrends'])
        ->name('sensor.data.hourly.trends')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    // Rotas para dados agregados por dia
    Route::get('/sensor-data/daily/all', [SensorDataController::class, 'getAllDailyData'])
        ->name('sensor.data.daily.all');

    Route::get('/sensor-data/daily/{type}', [SensorDataController::class, 'getDailyData'])
        ->name('sensor.data.daily.get')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/daily/{type}/stats', [SensorDataController::class, 'getDailyStats'])
        ->name('sensor.data.daily.stats')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/daily/{type}/monthly', [SensorDataController::class, 'getDailyMonthlyView'])
        ->name('sensor.data.daily.monthly')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    Route::get('/sensor-data/daily/{type}/weekly', [SensorDataController::class, 'getDailyWeeklyView'])
        ->name('sensor.data.daily.weekly')
        ->where('type', 'temperature|humidity|noise|pressure|eco2|tvoc');

    // Rotas para usuários
    Route::apiResource('users', UserController::class);

    // Rotas para setores
    Route::apiResource('sectors', SectorController::class);
    // Rotas para roles
    Route::apiResource('roles', RoleController::class);

    // Rota para envio de emails
    Route::post('/send-email', [EmailController::class, 'send'])
        ->name('email.send');

    // Rotas para access logs
    Route::get('/access-logs', [AccessLogController::class, 'index'])
        ->name('access.logs.index');
    Route::post('/access-logs', [AccessLogController::class, 'store'])
        ->name('access.logs.store');
});

// Rotas públicas (sem autenticação)
Route::post('/password/reset-link', [PasswordResetController::class, 'sendResetLink'])
    ->name('password.reset.link');

Route::post('/password/reset', [PasswordResetController::class, 'reset'])
    ->name('password.reset');
