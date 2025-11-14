<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProcessHourlyAggregation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 300;

    protected string $hourTimestamp;
    protected array $sensorTypes;

    public function __construct(string $hourTimestamp)
    {
        $this->hourTimestamp = $hourTimestamp;
        $this->sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];
    }

    public function handle(): void
    {
        try {
            Log::info('Iniciando processamento de agregação por hora', [
                'hour' => $this->hourTimestamp,
                'sensors' => $this->sensorTypes
            ]);

            $hourStart = Carbon::parse($this->hourTimestamp);
            $hourEnd = $hourStart->copy()->addHour();

            // Verifica se já existe dados para esta hora
            if ($this->hourlyDataExists($hourStart->format('Y-m-d H:00:00'))) {
                Log::info('Dados da hora já processados, ignorando', [
                    'hour' => $this->hourTimestamp
                ]);
                return;
            }

            DB::transaction(function () use ($hourStart, $hourEnd) {
                foreach ($this->sensorTypes as $sensorType) {
                    $this->processHourlySensor($sensorType, $hourStart, $hourEnd);
                }
            });

            // Verifica e dispara processamento de dia completo se necessário
            $this->checkAndTriggerDailyProcessing($hourStart);

            Log::info('Agregação por hora concluída com sucesso', [
                'hour' => $this->hourTimestamp,
                'processed_sensors' => count($this->sensorTypes)
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no processamento da agregação por hora', [
                'hour' => $this->hourTimestamp,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function hourlyDataExists(string $hourTimestamp): bool
    {
        foreach ($this->sensorTypes as $sensorType) {
            $exists = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', $hourTimestamp)
                ->exists();

            if ($exists) {
                return true;
            }
        }
        return false;
    }

    private function processHourlySensor(string $sensorType, Carbon $hourStart, Carbon $hourEnd): void
    {
        // Busca todos os dados de minuto para esta hora COMPLETA (XX:00 até XX:59)
        $minuteData = DB::table("m_{$sensorType}")
            ->where('minute_timestamp', '>=', $hourStart->format('Y-m-d H:00:00'))
            ->where('minute_timestamp', '<=', $hourStart->format('Y-m-d H:59:59'))
            ->orderBy('minute_timestamp')
            ->get();

        if ($minuteData->isEmpty()) {
            Log::warning("Nenhum dado de minuto encontrado para {$sensorType}", [
                'hour_start' => $hourStart->format('Y-m-d H:00:00'),
                'hour_end' => $hourStart->format('Y-m-d H:59:59')
            ]);
            return;
        }

        // VALIDAÇÃO RIGOROSA: Verificar se tem pelo menos 50 minutos de dados
        $minuteCount = $minuteData->count();
        $minRequiredMinutes = 50; // Mínimo de 50 minutos para processar

        if ($minuteCount < $minRequiredMinutes) {
            Log::info("Dados insuficientes para hora completa - {$sensorType}", [
                'hour' => $hourStart->format('Y-m-d H:00:00'),
                'minutes_found' => $minuteCount,
                'minimum_required' => $minRequiredMinutes,
                'coverage_percentage' => round(($minuteCount / 60) * 100, 1) . '%'
            ]);
            return; // NÃO processa se não tiver dados suficientes
        }

        // VALIDAÇÃO DE COBERTURA: Verificar se os dados cobrem a hora adequadamente
        $firstMinute = Carbon::parse($minuteData->first()->minute_timestamp);
        $lastMinute = Carbon::parse($minuteData->last()->minute_timestamp);

        // Verificar se a cobertura é de pelo menos 45 minutos contínuos
        $coverageMinutes = $lastMinute->diffInMinutes($firstMinute) + 1;
        $minCoverageMinutes = 45;

        if ($coverageMinutes < $minCoverageMinutes) {
            Log::info("Cobertura temporal insuficiente para hora - {$sensorType}", [
                'hour' => $hourStart->format('Y-m-d H:00:00'),
                'first_minute' => $firstMinute->format('H:i'),
                'last_minute' => $lastMinute->format('H:i'),
                'coverage_minutes' => $coverageMinutes,
                'minimum_coverage' => $minCoverageMinutes
            ]);
            return; // NÃO processa se a cobertura for insuficiente
        }

        // APROVADO: Dados suficientes e boa cobertura
        Log::info("Hora aprovada para processamento - {$sensorType}", [
            'hour' => $hourStart->format('Y-m-d H:00:00'),
            'minutes_found' => $minuteCount,
            'coverage_minutes' => $coverageMinutes,
            'coverage_percentage' => round(($minuteCount / 60) * 100, 1) . '%',
            'quality' => $minuteCount >= 55 ? 'EXCELENTE' : ($minuteCount >= 50 ? 'BOA' : 'ACEITÁVEL')
        ]);

        // Calcula estatísticas da hora baseadas nos dados de minuto
        $hourlyStats = $this->calculateHourlyStatistics($minuteData);
        $hourlyTrend = $this->calculateHourlyTrend($minuteData);

        // Salva os dados agregados da hora
        DB::table("h_{$sensorType}")->insert([
            'avg_value' => round($hourlyStats['average'], 2),
            'min_value' => $hourlyStats['minimum'],
            'max_value' => $hourlyStats['maximum'],
            'std_dev' => round($hourlyStats['std_dev'], 4),
            'minute_count' => $minuteData->count(),
            'variation_range' => round($hourlyStats['range'], 2),
            'hourly_trend' => $hourlyTrend,
            'hour_timestamp' => $hourStart->format('Y-m-d H:00:00')
        ]);

        $this->checkHourlyAlerts($sensorType, $hourlyStats, $hourStart->format('Y-m-d H:00:00'));

        Log::info("Dados da hora processados para {$sensorType}", [
            'hour' => $hourStart->format('Y-m-d H:00:00'),
            'minute_count' => $minuteData->count(),
            'avg_value' => round($hourlyStats['average'], 2),
            'trend' => $hourlyTrend,
            'data_quality' => round(($minuteData->count() / 60) * 100, 1) . '% de cobertura'
        ]);
    }

    private function calculateHourlyStatistics($minuteData): array
    {
        $avgValues = $minuteData->pluck('avg_value')->toArray();
        $minValues = $minuteData->pluck('min_value')->toArray();
        $maxValues = $minuteData->pluck('max_value')->toArray();

        $count = count($avgValues);
        $sum = array_sum($avgValues);
        $average = $sum / $count;
        $minimum = min($minValues);
        $maximum = max($maxValues);

        // Calcula desvio padrão baseado nas médias dos minutos
        $variance = 0;
        foreach ($avgValues as $value) {
            $variance += pow($value - $average, 2);
        }
        $stdDev = $count > 1 ? sqrt($variance / ($count - 1)) : 0;

        return [
            'average' => $average,
            'minimum' => $minimum,
            'maximum' => $maximum,
            'std_dev' => $stdDev,
            'range' => $maximum - $minimum
        ];
    }

    private function calculateHourlyTrend($minuteData): ?float
    {
        if ($minuteData->count() < 2) {
            return null;
        }

        $values = $minuteData->pluck('avg_value')->toArray();
        $n = count($values);

        // Calcula a tendência usando regressão linear simples
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $x = $i; // posição temporal (0, 1, 2, ..., n-1)
            $y = $values[$i];

            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        return round($slope, 4);
    }

    private function checkHourlyAlerts(string $sensorType, array $stats, string $hourTimestamp): void
    {
        $hourlyThresholds = $this->getHourlyThresholds()[$sensorType] ?? null;

        if (!$hourlyThresholds) {
            return;
        }

        // Verifica se a variação da hora está muito alta
        if ($stats['range'] > $hourlyThresholds['max_range']) {
            Log::warning("Variação horária excessiva detectada - {$sensorType}", [
                'hour' => $hourTimestamp,
                'range' => $stats['range'],
                'threshold' => $hourlyThresholds['max_range'],
                'avg_value' => round($stats['average'], 2)
            ]);
        }

        // Verifica se o desvio padrão está muito alto
        if ($stats['std_dev'] > $hourlyThresholds['max_std_dev']) {
            Log::warning("Instabilidade horária detectada - {$sensorType}", [
                'hour' => $hourTimestamp,
                'std_dev' => round($stats['std_dev'], 4),
                'threshold' => $hourlyThresholds['max_std_dev'],
                'avg_value' => round($stats['average'], 2)
            ]);
        }
    }

    private function getHourlyThresholds(): array
    {
        return [
            'temperature' => ['max_range' => 5.0, 'max_std_dev' => 2.0],
            'humidity' => ['max_range' => 15.0, 'max_std_dev' => 7.0],
            'noise' => ['max_range' => 20.0, 'max_std_dev' => 10.0],
            'pressure' => ['max_range' => 8.0, 'max_std_dev' => 3.0],
            'eco2' => ['max_range' => 150.0, 'max_std_dev' => 75.0],
            'tvoc' => ['max_range' => 75.0, 'max_std_dev' => 35.0]
        ];
    }

    private function checkAndTriggerDailyProcessing(Carbon $hourStart): void
    {
        $dayDate = $hourStart->format('Y-m-d');

        // Verifica se já existe dados agregados para este dia
        if ($this->dailyDataExists($dayDate)) {
            return; // Dia já processado
        }

        // VALIDAÇÃO RIGOROSA: Verifica se temos pelo menos 20 horas de dados no dia
        $minHoursRequired = 20;

        foreach ($this->sensorTypes as $sensorType) {
            $hourCount = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', '>=', $hourStart->format('Y-m-d 00:00:00'))
                ->where('hour_timestamp', '<=', $hourStart->format('Y-m-d 23:00:00'))
                ->count();

            if ($hourCount < $minHoursRequired) {
                return; // Não tem dados suficientes
            }

            // VALIDAÇÃO ADICIONAL: Verificar cobertura temporal
            $firstRecord = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', '>=', $hourStart->format('Y-m-d 00:00:00'))
                ->where('hour_timestamp', '<=', $hourStart->format('Y-m-d 23:00:00'))
                ->orderBy('hour_timestamp')
                ->first();

            $lastRecord = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', '>=', $hourStart->format('Y-m-d 00:00:00'))
                ->where('hour_timestamp', '<=', $hourStart->format('Y-m-d 23:00:00'))
                ->orderBy('hour_timestamp', 'desc')
                ->first();

            if ($firstRecord && $lastRecord) {
                $firstHour = Carbon::parse($firstRecord->hour_timestamp);
                $lastHour = Carbon::parse($lastRecord->hour_timestamp);
                $coverageHours = $lastHour->diffInHours($firstHour) + 1;

                // Requer pelo menos 18 horas de cobertura contínua
                if ($coverageHours < 18) {
                    return; // Cobertura insuficiente
                }
            }
        }

        // APROVADO: Dia completo - disparar processamento diário
        $this->dispatchDailyProcessing($dayDate);
    }

    private function dailyDataExists(string $dayDate): bool
    {
        foreach ($this->sensorTypes as $sensorType) {
            $exists = DB::table("d_{$sensorType}")
                ->where('day_date', $dayDate)
                ->exists();

            if ($exists) {
                return true;
            }
        }
        return false;
    }

    private function dispatchDailyProcessing(string $dayDate): void
    {
        try {
            // Importa a classe do Job de agregação diária
            $dailyJobClass = \App\Jobs\ProcessDailyAggregation::class;

            // Dispatch do job para processar este dia
            $dailyJobClass::dispatch($dayDate);

            Log::info('Job de agregação diária disparado automaticamente', [
                'day' => $dayDate,
                'triggered_by' => 'ProcessHourlyAggregation'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao disparar job de agregação diária', [
                'day' => $dayDate,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job ProcessHourlyAggregation falhou permanentemente', [
            'hour' => $this->hourTimestamp,
            'error' => $exception->getMessage()
        ]);
    }
}
