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

class ProcessDailyAggregation implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 600; // 10 minutos para processar um dia completo

    protected string $dayDate;
    protected array $sensorTypes;

    public function __construct(string $dayDate)
    {
        $this->dayDate = $dayDate;
        $this->sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];
    }

    public function handle(): void
    {
        try {
            Log::info('Iniciando processamento de agregação diária', [
                'day' => $this->dayDate,
                'sensors' => $this->sensorTypes
            ]);

            $day = Carbon::parse($this->dayDate);

            // Verifica se já existe dados para este dia
            if ($this->dailyDataExists($day->format('Y-m-d'))) {
                Log::info('Dados do dia já processados, ignorando', [
                    'day' => $this->dayDate
                ]);
                return;
            }

            DB::transaction(function () use ($day) {
                foreach ($this->sensorTypes as $sensorType) {
                    $this->processDailySensor($sensorType, $day);
                }
            });

            Log::info('Agregação diária concluída com sucesso', [
                'day' => $this->dayDate,
                'processed_sensors' => count($this->sensorTypes)
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no processamento da agregação diária', [
                'day' => $this->dayDate,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
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

    private function processDailySensor(string $sensorType, Carbon $day): void
    {
        // Busca todos os dados de hora para este dia COMPLETO (00:00 até 23:00)
        $hourData = DB::table("h_{$sensorType}")
            ->where('hour_timestamp', '>=', $day->format('Y-m-d 00:00:00'))
            ->where('hour_timestamp', '<=', $day->format('Y-m-d 23:00:00'))
            ->orderBy('hour_timestamp')
            ->get();

        if ($hourData->isEmpty()) {
            Log::warning("Nenhum dado de hora encontrado para {$sensorType}", [
                'day_start' => $day->format('Y-m-d 00:00:00'),
                'day_end' => $day->format('Y-m-d 23:00:00')
            ]);
            return;
        }

        // VALIDAÇÃO RIGOROSA: Verificar se tem pelo menos 20 horas de dados (83% do dia)
        $hourCount = $hourData->count();
        $minRequiredHours = 20;

        if ($hourCount < $minRequiredHours) {
            Log::info("Dados insuficientes para dia completo - {$sensorType}", [
                'day' => $day->format('Y-m-d'),
                'hours_found' => $hourCount,
                'minimum_required' => $minRequiredHours,
                'coverage_percentage' => round(($hourCount / 24) * 100, 1) . '%'
            ]);
            return; // NÃO processa se não tiver dados suficientes
        }

        // VALIDAÇÃO DE COBERTURA: Verificar se os dados cobrem o dia adequadamente
        $firstHour = Carbon::parse($hourData->first()->hour_timestamp);
        $lastHour = Carbon::parse($hourData->last()->hour_timestamp);

        // Verificar se a cobertura é de pelo menos 18 horas contínuas
        $coverageHours = $lastHour->diffInHours($firstHour) + 1;
        $minCoverageHours = 18;

        if ($coverageHours < $minCoverageHours) {
            Log::info("Cobertura temporal insuficiente para dia - {$sensorType}", [
                'day' => $day->format('Y-m-d'),
                'first_hour' => $firstHour->format('H:00'),
                'last_hour' => $lastHour->format('H:00'),
                'coverage_hours' => $coverageHours,
                'minimum_coverage' => $minCoverageHours
            ]);
            return; // NÃO processa se a cobertura for insuficiente
        }

        // APROVADO: Dados suficientes e boa cobertura
        Log::info("Dia aprovado para processamento - {$sensorType}", [
            'day' => $day->format('Y-m-d'),
            'hours_found' => $hourCount,
            'coverage_hours' => $coverageHours,
            'coverage_percentage' => round(($hourCount / 24) * 100, 1) . '%',
            'quality' => $hourCount >= 22 ? 'EXCELENTE' : ($hourCount >= 20 ? 'BOA' : 'ACEITÁVEL')
        ]);

        // Calcula estatísticas do dia baseadas nos dados de hora
        $dailyStats = $this->calculateDailyStatistics($hourData);
        $dailyTrend = $this->calculateDailyTrend($hourData);
        $peakValley = $this->calculatePeakAndValley($hourData);

        // Salva os dados agregados do dia
        DB::table("d_{$sensorType}")->insert([
            'avg_value' => round($dailyStats['average'], 2),
            'min_value' => $dailyStats['minimum'],
            'max_value' => $dailyStats['maximum'],
            'std_dev' => round($dailyStats['std_dev'], 4),
            'hour_count' => $hourData->count(),
            'variation_range' => round($dailyStats['range'], 2),
            'daily_trend' => $dailyTrend,
            'peak_hour_avg' => $peakValley['peak_value'],
            'valley_hour_avg' => $peakValley['valley_value'],
            'peak_hour' => $peakValley['peak_hour'],
            'valley_hour' => $peakValley['valley_hour'],
            'day_date' => $day->format('Y-m-d')
        ]);

        $this->checkDailyAlerts($sensorType, $dailyStats, $peakValley, $day->format('Y-m-d'));

        Log::info("Dados do dia processados para {$sensorType}", [
            'day' => $day->format('Y-m-d'),
            'hour_count' => $hourData->count(),
            'avg_value' => round($dailyStats['average'], 2),
            'trend' => $dailyTrend,
            'peak_hour' => $peakValley['peak_hour'],
            'valley_hour' => $peakValley['valley_hour'],
            'data_quality' => round(($hourData->count() / 24) * 100, 1) . '% de cobertura'
        ]);
    }

    private function calculateDailyStatistics($hourData): array
    {
        $avgValues = $hourData->pluck('avg_value')->toArray();
        $minValues = $hourData->pluck('min_value')->toArray();
        $maxValues = $hourData->pluck('max_value')->toArray();

        $count = count($avgValues);
        $sum = array_sum($avgValues);
        $average = $sum / $count;
        $minimum = min($minValues);
        $maximum = max($maxValues);

        // Calcula desvio padrão baseado nas médias das horas
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

    private function calculateDailyTrend($hourData): ?float
    {
        if ($hourData->count() < 2) {
            return null;
        }

        $values = $hourData->pluck('avg_value')->toArray();
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

    private function calculatePeakAndValley($hourData): array
    {
        $peakHour = $hourData->sortByDesc('avg_value')->first();
        $valleyHour = $hourData->sortBy('avg_value')->first();

        return [
            'peak_value' => round($peakHour->avg_value, 2),
            'valley_value' => round($valleyHour->avg_value, 2),
            'peak_hour' => Carbon::parse($peakHour->hour_timestamp)->format('H:i'),
            'valley_hour' => Carbon::parse($valleyHour->hour_timestamp)->format('H:i')
        ];
    }

    private function checkDailyAlerts(string $sensorType, array $stats, array $peakValley, string $dayDate): void
    {
        $dailyThresholds = $this->getDailyThresholds()[$sensorType] ?? null;

        if (!$dailyThresholds) {
            return;
        }

        // Verifica se a variação do dia está muito alta
        if ($stats['range'] > $dailyThresholds['max_range']) {
            Log::warning("Variação diária excessiva detectada - {$sensorType}", [
                'day' => $dayDate,
                'range' => $stats['range'],
                'threshold' => $dailyThresholds['max_range'],
                'avg_value' => round($stats['average'], 2)
            ]);
        }

        // Verifica se o desvio padrão está muito alto
        if ($stats['std_dev'] > $dailyThresholds['max_std_dev']) {
            Log::warning("Instabilidade diária detectada - {$sensorType}", [
                'day' => $dayDate,
                'std_dev' => round($stats['std_dev'], 4),
                'threshold' => $dailyThresholds['max_std_dev'],
                'avg_value' => round($stats['average'], 2)
            ]);
        }

        // Verifica diferença extrema entre pico e vale
        $peakValleyDiff = $peakValley['peak_value'] - $peakValley['valley_value'];
        if ($peakValleyDiff > $dailyThresholds['max_peak_valley_diff']) {
            Log::warning("Diferença extrema entre pico e vale - {$sensorType}", [
                'day' => $dayDate,
                'peak_valley_diff' => round($peakValleyDiff, 2),
                'threshold' => $dailyThresholds['max_peak_valley_diff'],
                'peak_hour' => $peakValley['peak_hour'],
                'valley_hour' => $peakValley['valley_hour']
            ]);
        }
    }

    private function getDailyThresholds(): array
    {
        return [
            'temperature' => ['max_range' => 8.0, 'max_std_dev' => 3.0, 'max_peak_valley_diff' => 6.0],
            'humidity' => ['max_range' => 25.0, 'max_std_dev' => 10.0, 'max_peak_valley_diff' => 20.0],
            'noise' => ['max_range' => 30.0, 'max_std_dev' => 15.0, 'max_peak_valley_diff' => 25.0],
            'pressure' => ['max_range' => 12.0, 'max_std_dev' => 5.0, 'max_peak_valley_diff' => 10.0],
            'eco2' => ['max_range' => 200.0, 'max_std_dev' => 100.0, 'max_peak_valley_diff' => 150.0],
            'tvoc' => ['max_range' => 100.0, 'max_std_dev' => 50.0, 'max_peak_valley_diff' => 75.0]
        ];
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job ProcessDailyAggregation falhou permanentemente', [
            'day' => $this->dayDate,
            'error' => $exception->getMessage()
        ]);
    }
}
