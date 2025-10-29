<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Jobs\ProcessSensorData;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessSensorDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Log::spy();
    }

    public function test_processes_sensor_data_successfully()
    {
        $sensorData = [
            'temperature' => 22.0,
            'humidity' => 50.0,
            'noise' => 40.0,
            'pression' => 1013.2,
            'eco2' => 500,
            'tvoc' => 100,
            'timestamp' => '2025-10-29 09:20:15',
            'received_at' => '2025-10-29 09:20:15'
        ];

        $tempPath = 'temp/test_file.json';
        Storage::put($tempPath, json_encode($sensorData));

        $job = new ProcessSensorData($tempPath, $sensorData);
        $job->handle();

        $this->assertFalse(Storage::exists($tempPath));
        $this->assertTrue(Storage::exists('processed/test_file.json'));

        Log::shouldHaveReceived('info')
            ->with('Iniciando processamento dos dados do sensor', \Mockery::any())
            ->once();

        Log::shouldHaveReceived('info')
            ->with('Processamento dos dados do sensor concluído com sucesso', \Mockery::any())
            ->once();
    }

    public function test_logs_temperature_warning_when_out_of_range()
    {
        $sensorData = [
            'temperature' => 30.0,
            'humidity' => 50.0,
            'noise' => 40.0,
            'pression' => 1013.2,
            'eco2' => 500,
            'tvoc' => 100,
            'timestamp' => '2025-10-29 09:20:15',
            'received_at' => '2025-10-29 09:20:15'
        ];

        $tempPath = 'temp/test_file.json';
        Storage::put($tempPath, json_encode($sensorData));

        $job = new ProcessSensorData($tempPath, $sensorData);
        $job->handle();

        Log::shouldHaveReceived('warning')
            ->with('Temperatura fora da faixa ideal', \Mockery::any())
            ->once();
    }

    public function test_logs_humidity_warning_when_out_of_range()
    {
        $sensorData = [
            'temperature' => 22.0,
            'humidity' => 80.0,
            'noise' => 40.0,
            'pression' => 1013.2,
            'eco2' => 500,
            'tvoc' => 100,
            'timestamp' => '2025-10-29 09:20:15',
            'received_at' => '2025-10-29 09:20:15'
        ];

        $tempPath = 'temp/test_file.json';
        Storage::put($tempPath, json_encode($sensorData));

        $job = new ProcessSensorData($tempPath, $sensorData);
        $job->handle();

        Log::shouldHaveReceived('warning')
            ->with('Umidade fora da faixa ideal', \Mockery::any())
            ->once();
    }

    public function test_logs_noise_warning_when_out_of_range()
    {
        $sensorData = [
            'temperature' => 22.0,
            'humidity' => 50.0,
            'noise' => 60.0,
            'pression' => 1013.2,
            'eco2' => 500,
            'tvoc' => 100,
            'timestamp' => '2025-10-29 09:20:15',
            'received_at' => '2025-10-29 09:20:15'
        ];

        $tempPath = 'temp/test_file.json';
        Storage::put($tempPath, json_encode($sensorData));

        $job = new ProcessSensorData($tempPath, $sensorData);
        $job->handle();

        Log::shouldHaveReceived('warning')
            ->with('Níveis de ruído elevados', \Mockery::any())
            ->once();
    }

    public function test_handles_missing_file_error()
    {
        $sensorData = [
            'temperature' => 22.0,
            'humidity' => 50.0,
            'noise' => 40.0,
            'pression' => 1013.2,
            'eco2' => 500,
            'tvoc' => 100,
            'timestamp' => '2025-10-29 09:20:15'
        ];

        $job = new ProcessSensorData('temp/nonexistent.json', $sensorData);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Arquivo temporário não encontrado');

        $job->handle();
    }

    public function test_moves_file_to_errors_on_failure()
    {
        $sensorData = [
            'temperature' => 22.0,
            'humidity' => 50.0,
            'noise' => 40.0,
            'pression' => 1013.2,
            'eco2' => 500,
            'tvoc' => 100,
            'timestamp' => '2025-10-29 09:20:15'
        ];

        $tempPath = 'temp/test_file.json';
        Storage::put($tempPath, json_encode($sensorData));

        $job = new ProcessSensorData($tempPath, $sensorData);
        $exception = new \Exception('Test exception');

        $job->failed($exception);
        $this->assertTrue(Storage::exists('errors/test_file.json'));
        $this->assertFalse(Storage::exists($tempPath));

        Log::shouldHaveReceived('error')
            ->with('Job ProcessSensorData falhou permanentemente', \Mockery::any())
            ->once();
    }
}
