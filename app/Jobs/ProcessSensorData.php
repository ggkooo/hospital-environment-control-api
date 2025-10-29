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

    protected $tempFilePath;
    protected $sensorData;

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
                'total_records' => is_array($this->sensorData) ? count($this->sensorData) : 1
            ]);

            if (!Storage::exists($this->tempFilePath)) {
                throw new \Exception("Arquivo temporário não encontrado: {$this->tempFilePath}");
            }

            $fileContent = Storage::get($this->tempFilePath);
            $dataArray = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
            }

            if (!is_array($dataArray)) {
                throw new \Exception('Dados devem ser um array de objetos');
            }

            DB::transaction(function () use ($dataArray) {
                $temperatureData = [];
                $humidityData = [];
                $noiseData = [];
                $pressureData = [];
                $eco2Data = [];
                $tvocData = [];

                foreach ($dataArray as $data) {
                    $timestamp = Carbon::parse($data['timestamp']);

                    $temperatureData[] = ['value' => $data['temperature'], 'timestamp' => $timestamp];
                    $humidityData[] = ['value' => $data['humidity'], 'timestamp' => $timestamp];
                    $noiseData[] = ['value' => $data['noise'], 'timestamp' => $timestamp];
                    $pressureData[] = ['value' => $data['pression'], 'timestamp' => $timestamp];
                    $eco2Data[] = ['value' => $data['eco2'], 'timestamp' => $timestamp];
                    $tvocData[] = ['value' => $data['tvoc'], 'timestamp' => $timestamp];
                }

                DB::table('s_temperature')->insert($temperatureData);
                DB::table('s_humidity')->insert($humidityData);
                DB::table('s_noise')->insert($noiseData);
                DB::table('s_pressure')->insert($pressureData);
                DB::table('s_eco2')->insert($eco2Data);
                DB::table('s_tvoc')->insert($tvocData);

                Log::info('Lote processado com sucesso', [
                    'total_records' => count($dataArray),
                    'tables_updated' => 6
                ]);
            });

            foreach ($dataArray as $data) {
                $this->processAlerts($data);
            }

            Storage::delete($this->tempFilePath);

            Log::info('Processamento do lote de dados dos sensores concluído com sucesso', [
                'file_deleted' => $this->tempFilePath,
                'total_records_processed' => count($dataArray)
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no processamento do lote de dados dos sensores', [
                'file' => $this->tempFilePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function processAlerts(array $data): void
    {
        if ($data['temperature'] < 18 || $data['temperature'] > 24) {
            Log::warning('Temperatura fora da faixa', ['temp' => $data['temperature']]);
        }
        if ($data['humidity'] < 30 || $data['humidity'] > 60) {
            Log::warning('Umidade fora da faixa', ['humidity' => $data['humidity']]);
        }
        if ($data['eco2'] > 1000) {
            Log::warning('CO2 elevado', ['eco2' => $data['eco2']]);
        }
        if ($data['tvoc'] > 220) {
            Log::warning('TVOC elevado', ['tvoc' => $data['tvoc']]);
        }
        if ($data['noise'] > 45) {
            Log::warning('Ruído elevado', ['noise' => $data['noise']]);
        }
        if ($data['pression'] < 963 || $data['pression'] > 1063) {
            Log::info('Pressão anormal', ['pressure' => $data['pression']]);
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
