<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcessSensorData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected string $tempFilePath;
    protected array $sensorData;

    public function __construct(string $tempFilePath, array $sensorData)
    {
        $this->tempFilePath = $tempFilePath;
        $this->sensorData = $sensorData;
    }

    public function handle(): void
    {
        try {
            Log::info('Iniciando processamento do lote de dados dos sensores', [
                'file' => $this->tempFilePath,
                'total_records' => count($this->sensorData)
            ]);

            if (!Storage::exists($this->tempFilePath)) {
                throw new \Exception("Arquivo temporário não encontrado: {$this->tempFilePath}");
            }

            $dataArray = $this->loadAndValidateData();

            DB::transaction(function () use ($dataArray) {
                $this->saveRawData($dataArray);
                $this->processMinuteAggregates($dataArray);
            });

            $this->processAlerts($dataArray);

            // Verifica e dispara processamento de hora completa se necessário
            $this->checkAndTriggerHourlyProcessing($dataArray);

            Storage::delete($this->tempFilePath);

            Log::info('Processamento concluído com sucesso', [
                'file_deleted' => $this->tempFilePath,
                'total_records_processed' => count($dataArray)
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no processamento dos dados dos sensores', [
                'file' => $this->tempFilePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function loadAndValidateData(): array
    {
        $fileContent = Storage::get($this->tempFilePath);
        $dataArray = json_decode($fileContent, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
        }

        if (!is_array($dataArray)) {
            throw new \Exception('Dados devem ser um array de objetos');
        }

        return $dataArray;
    }

    private function saveRawData(array $dataArray): void
    {
        $batchData = [
            'temperature' => [],
            'humidity' => [],
            'noise' => [],
            'pressure' => [],
            'eco2' => [],
            'tvoc' => []
        ];

        foreach ($dataArray as $data) {
            $timestamp = Carbon::parse($data['timestamp']);
            $batchData['temperature'][] = ['value' => $data['temperature'], 'timestamp' => $timestamp];
            $batchData['humidity'][] = ['value' => $data['humidity'], 'timestamp' => $timestamp];
            $batchData['noise'][] = ['value' => $data['noise'], 'timestamp' => $timestamp];
            $batchData['pressure'][] = ['value' => $data['pression'], 'timestamp' => $timestamp];
            $batchData['eco2'][] = ['value' => $data['eco2'], 'timestamp' => $timestamp];
            $batchData['tvoc'][] = ['value' => $data['tvoc'], 'timestamp' => $timestamp];
        }

        foreach ($batchData as $sensorType => $data) {
            DB::table("s_{$sensorType}")->insert($data);
        }

        Log::info('Dados brutos salvos com sucesso', [
            'total_records' => count($dataArray),
            'tables_updated' => count($batchData)
        ]);
    }

    private function processMinuteAggregates(array $dataArray): void
    {
        $minuteGroups = $this->groupDataByMinute($dataArray);

        $processedMinutes = 0;
        $ignoredMinutes = 0;

        foreach ($minuteGroups as $minuteTimestamp => $sensors) {
            $minuteProcessed = false;

            foreach (['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'] as $sensorType) {
                if (!empty($sensors[$sensorType])) {
                    // Verifica se tem exatamente 12 leituras antes de processar
                    if (count($sensors[$sensorType]) === 12) {
                        $this->saveMinuteData($sensorType, $sensors[$sensorType], $minuteTimestamp);
                        $minuteProcessed = true;
                    } else {
                        Log::info("Minuto ignorado por dados incompletos - {$sensorType}", [
                            'minute' => $minuteTimestamp,
                            'expected_readings' => 12,
                            'actual_readings' => count($sensors[$sensorType]),
                            'status' => 'IGNORADO'
                        ]);
                    }
                }
            }

            if ($minuteProcessed) {
                $processedMinutes++;
            } else {
                $ignoredMinutes++;
            }
        }

        Log::info('Agregação por minuto concluída', [
            'total_minutes_found' => count($minuteGroups),
            'minutes_processed' => $processedMinutes,
            'minutes_ignored' => $ignoredMinutes,
            'success_rate' => round(($processedMinutes / count($minuteGroups)) * 100, 1) . '%'
        ]);
    }

    private function groupDataByMinute(array $dataArray): array
    {
        $minuteGroups = [];

        foreach ($dataArray as $data) {
            $minuteKey = Carbon::parse($data['timestamp'])->format('Y-m-d H:i:00');

            if (!isset($minuteGroups[$minuteKey])) {
                $minuteGroups[$minuteKey] = [
                    'temperature' => [],
                    'humidity' => [],
                    'noise' => [],
                    'pressure' => [],
                    'eco2' => [],
                    'tvoc' => []
                ];
            }

            $minuteGroups[$minuteKey]['temperature'][] = $data['temperature'];
            $minuteGroups[$minuteKey]['humidity'][] = $data['humidity'];
            $minuteGroups[$minuteKey]['noise'][] = $data['noise'];
            $minuteGroups[$minuteKey]['pressure'][] = $data['pression'];
            $minuteGroups[$minuteKey]['eco2'][] = $data['eco2'];
            $minuteGroups[$minuteKey]['tvoc'][] = $data['tvoc'];
        }

        return $minuteGroups;
    }

    private function saveMinuteData(string $sensorType, array $values, string $minuteTimestamp): void
    {
        // VALIDAÇÃO RIGOROSA: Verificar se tem exatamente 12 leituras no minuto
        $expectedReadings = 12; // 12 leituras por minuto (uma a cada 5 segundos)
        $actualReadings = count($values);

        if ($actualReadings !== $expectedReadings) {
            Log::info("Dados insuficientes para minuto - {$sensorType}", [
                'minute' => $minuteTimestamp,
                'expected_readings' => $expectedReadings,
                'actual_readings' => $actualReadings,
                'coverage_percentage' => round(($actualReadings / $expectedReadings) * 100, 1) . '%',
                'status' => 'IGNORADO - Dados incompletos'
            ]);
            return; // NÃO processa se não tiver exatamente 12 leituras
        }

        $existing = DB::table("m_{$sensorType}")
            ->where('minute_timestamp', $minuteTimestamp)
            ->first();

        if ($existing) {
            Log::info("Dados do minuto já existem para {$sensorType}", ['minute' => $minuteTimestamp]);
            return;
        }

        // APROVADO: Exatamente 12 leituras - processando minuto
        Log::info("Minuto aprovado para processamento - {$sensorType}", [
            'minute' => $minuteTimestamp,
            'readings_count' => $actualReadings,
            'quality' => 'COMPLETO',
            'status' => 'PROCESSANDO'
        ]);

        $stats = $this->calculateStatistics($values);

        DB::table("m_{$sensorType}")->insert([
            'avg_value' => round($stats['average'], 2),
            'min_value' => $stats['minimum'],
            'max_value' => $stats['maximum'],
            'std_dev' => round($stats['std_dev'], 4),
            'reading_count' => count($values),
            'variation_range' => round($stats['range'], 2),
            'minute_timestamp' => $minuteTimestamp
        ]);

        $this->checkForAbruptVariations($sensorType, $stats, $minuteTimestamp);

        Log::info("Minuto processado com sucesso - {$sensorType}", [
            'minute' => $minuteTimestamp,
            'readings_processed' => $actualReadings,
            'avg_value' => round($stats['average'], 2),
            'quality' => '100% de cobertura'
        ]);
    }

    private function calculateStatistics(array $values): array
    {
        $count = count($values);
        $sum = array_sum($values);
        $average = $sum / $count;
        $minimum = min($values);
        $maximum = max($values);

        $variance = 0;
        foreach ($values as $value) {
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

    private function checkForAbruptVariations(string $sensorType, array $stats, string $minuteTimestamp): void
    {
        $thresholds = $this->getVariationThresholds()[$sensorType] ?? null;

        if (!$thresholds) {
            return;
        }

        if ($stats['range'] > $thresholds['range']) {
            Log::warning("Variação brusca detectada - {$sensorType}", [
                'minute' => $minuteTimestamp,
                'range' => $stats['range'],
                'threshold' => $thresholds['range'],
                'min_value' => $stats['minimum'],
                'max_value' => $stats['maximum'],
                'avg_value' => round($stats['average'], 2)
            ]);
        }

        if ($stats['std_dev'] > $thresholds['std_dev']) {
            Log::warning("Alto desvio padrão detectado - {$sensorType}", [
                'minute' => $minuteTimestamp,
                'std_dev' => round($stats['std_dev'], 4),
                'threshold' => $thresholds['std_dev'],
                'avg_value' => round($stats['average'], 2)
            ]);
        }
    }

    private function processAlerts(array $dataArray): void
    {
        foreach ($dataArray as $data) {
            $this->checkSensorThresholds($data);
        }
    }

    private function checkSensorThresholds(array $data): void
    {
        $thresholds = [
            'temperature' => ['min' => 18, 'max' => 24],
            'humidity' => ['min' => 30, 'max' => 60],
            'eco2' => ['max' => 1000],
            'tvoc' => ['max' => 220],
            'noise' => ['max' => 45],
            'pression' => ['min' => 963, 'max' => 1063]
        ];

        foreach ($thresholds as $sensor => $limits) {
            $value = $data[$sensor] ?? null;
            if ($value === null) continue;

            if (isset($limits['min']) && $value < $limits['min']) {
                Log::warning("{$sensor} abaixo do limite", [$sensor => $value, 'limit' => $limits['min']]);
            }

            if (isset($limits['max']) && $value > $limits['max']) {
                Log::warning("{$sensor} acima do limite", [$sensor => $value, 'limit' => $limits['max']]);
            }
        }
    }

    private function getVariationThresholds(): array
    {
        return [
            'temperature' => ['range' => 3.0, 'std_dev' => 1.5],
            'humidity' => ['range' => 10.0, 'std_dev' => 5.0],
            'noise' => ['range' => 15.0, 'std_dev' => 8.0],
            'pressure' => ['range' => 5.0, 'std_dev' => 2.0],
            'eco2' => ['range' => 100.0, 'std_dev' => 50.0],
            'tvoc' => ['range' => 50.0, 'std_dev' => 25.0]
        ];
    }

    private function checkAndTriggerHourlyProcessing(array $dataArray): void
    {
        if (empty($dataArray)) {
            return;
        }

        // Obtém as horas únicas dos dados processados
        $hoursProcessed = [];
        foreach ($dataArray as $data) {
            $hour = Carbon::parse($data['timestamp'])->format('Y-m-d H:00:00');
            $hoursProcessed[$hour] = true;
        }

        // Para cada hora processada, verifica se está completa
        foreach (array_keys($hoursProcessed) as $hourTimestamp) {
            if ($this->isHourComplete($hourTimestamp)) {
                $this->dispatchHourlyProcessing($hourTimestamp);
            }
        }
    }

    private function isHourComplete(string $hourTimestamp): bool
    {
        $hour = Carbon::parse($hourTimestamp);

        // Verifica se já existe dados agregados para esta hora
        if ($this->hourlyDataExists($hourTimestamp)) {
            return false;
        }

        // VALIDAÇÃO RIGOROSA: Verifica se temos pelo menos 50 minutos de dados
        $minMinutesRequired = 50;
        $sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];

        foreach ($sensorTypes as $sensorType) {
            $minuteCount = DB::table("m_{$sensorType}")
                ->where('minute_timestamp', '>=', $hour->format('Y-m-d H:00:00'))
                ->where('minute_timestamp', '<=', $hour->format('Y-m-d H:59:59'))
                ->count();

            if ($minuteCount < $minMinutesRequired) {
                return false;
            }

            // VALIDAÇÃO ADICIONAL: Verificar cobertura temporal
            $firstRecord = DB::table("m_{$sensorType}")
                ->where('minute_timestamp', '>=', $hour->format('Y-m-d H:00:00'))
                ->where('minute_timestamp', '<=', $hour->format('Y-m-d H:59:59'))
                ->orderBy('minute_timestamp')
                ->first();

            $lastRecord = DB::table("m_{$sensorType}")
                ->where('minute_timestamp', '>=', $hour->format('Y-m-d H:00:00'))
                ->where('minute_timestamp', '<=', $hour->format('Y-m-d H:59:59'))
                ->orderBy('minute_timestamp', 'desc')
                ->first();

            if ($firstRecord && $lastRecord) {
                $firstMinute = Carbon::parse($firstRecord->minute_timestamp);
                $lastMinute = Carbon::parse($lastRecord->minute_timestamp);
                $coverageMinutes = $lastMinute->diffInMinutes($firstMinute) + 1;

                // Requer pelo menos 45 minutos de cobertura contínua
                if ($coverageMinutes < 45) {
                    return false;
                }
            }
        }

        return true;
    }

    private function hourlyDataExists(string $hourTimestamp): bool
    {
        $sensorTypes = ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc'];

        foreach ($sensorTypes as $sensorType) {
            $exists = DB::table("h_{$sensorType}")
                ->where('hour_timestamp', $hourTimestamp)
                ->exists();

            if ($exists) {
                return true;
            }
        }
        return false;
    }

    private function dispatchHourlyProcessing(string $hourTimestamp): void
    {
        try {
            // Importa a classe do Job de agregação por hora
            $hourlyJobClass = \App\Jobs\ProcessHourlyAggregation::class;

            // Dispatch do job para processar esta hora
            $hourlyJobClass::dispatch($hourTimestamp);

            Log::info('Job de agregação por hora disparado automaticamente', [
                'hour' => $hourTimestamp,
                'triggered_by' => 'ProcessSensorData'
            ]);

        } catch (\Exception $e) {
            Log::error('Erro ao disparar job de agregação por hora', [
                'hour' => $hourTimestamp,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Job ProcessSensorData falhou permanentemente', [
            'file' => $this->tempFilePath,
            'error' => $exception->getMessage(),
            'sensor_data' => $this->sensorData
        ]);

        if (Storage::exists($this->tempFilePath)) {
            $errorPath = 'errors/' . basename($this->tempFilePath);
            Storage::move($this->tempFilePath, $errorPath);

            Log::info('Arquivo movido para pasta de erros', [
                'original_path' => $this->tempFilePath,
                'error_path' => $errorPath
            ]);
        }
    }
}
