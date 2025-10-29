<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

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
            Log::info('Iniciando processamento dos dados do sensor', [
                'file' => $this->tempFilePath,
                'timestamp' => $this->sensorData['timestamp']
            ]);

            if (!Storage::exists($this->tempFilePath)) {
                throw new \Exception("Arquivo temporário não encontrado: {$this->tempFilePath}");
            }

            $fileContent = Storage::get($this->tempFilePath);
            $data = json_decode($fileContent, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Erro ao decodificar JSON: ' . json_last_error_msg());
            }

            $this->processTemperature($data['temperature']);
            $this->processHumidity($data['humidity']);
            $this->processAirQuality($data['eco2'], $data['tvoc']);
            $this->processNoise($data['noise']);
            $this->processPressure($data['pression']);

            $processedPath = 'processed/' . basename($this->tempFilePath);
            Storage::put($processedPath, $fileContent);

            Storage::delete($this->tempFilePath);

            Log::info('Processamento dos dados do sensor concluído com sucesso', [
                'file_moved_to' => $processedPath,
                'temperature' => $data['temperature'],
                'humidity' => $data['humidity']
            ]);

        } catch (\Exception $e) {
            Log::error('Erro no processamento dos dados do sensor', [
                'file' => $this->tempFilePath,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            throw $e;
        }
    }

    private function processTemperature(float $temperature): void
    {
        if ($temperature < 18 || $temperature > 24) {
            Log::warning('Temperatura fora da faixa ideal', [
                'temperature' => $temperature,
                'ideal_range' => '18-24°C'
            ]);
        }
    }

    private function processHumidity(float $humidity): void
    {
        if ($humidity < 30 || $humidity > 60) {
            Log::warning('Umidade fora da faixa ideal', [
                'humidity' => $humidity,
                'ideal_range' => '30-60%'
            ]);
        }
    }

    private function processAirQuality(int $eco2, int $tvoc): void
    {
        if ($eco2 > 1000) {
            Log::warning('Níveis de CO2 elevados', [
                'eco2' => $eco2,
                'threshold' => 1000
            ]);
        }

        if ($tvoc > 220) {
            Log::warning('Níveis de TVOC elevados', [
                'tvoc' => $tvoc,
                'threshold' => 220
            ]);
        }
    }

    private function processNoise(float $noise): void
    {
        if ($noise > 45) {
            Log::warning('Níveis de ruído elevados', [
                'noise' => $noise,
                'threshold' => 45
            ]);
        }
    }

    private function processPressure(float $pressure): void
    {
        if ($pressure < 963 || $pressure > 1063) {
            Log::info('Pressão atmosférica fora da faixa normal', [
                'pressure' => $pressure,
                'normal_range' => '963-1063 hPa'
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
        }
    }
}
