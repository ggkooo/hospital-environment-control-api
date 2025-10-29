<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithoutMiddleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use App\Jobs\ProcessSensorData;

class SensorDataApiTest extends TestCase
{
    use WithoutMiddleware;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Queue::fake();
    }

    public function test_can_receive_sensor_data_successfully()
    {
        $sensorData = [
            'temperature' => 23.4,
            'humidity' => 45.7,
            'noise' => 56.2,
            'pression' => 1013.2,
            'eco2' => 412,
            'tvoc' => 23,
            'timestamp' => '2025-10-29 09:20:15'
        ];

        $response = $this->postJson('/api/sensor-data', $sensorData);

        $response->assertStatus(200)
                ->assertJson([
                    'status' => 'success',
                    'message' => 'Dados recebidos e processamento iniciado'
                ])
                ->assertJsonStructure([
                    'status',
                    'message',
                    'file'
                ]);

        Queue::assertPushed(ProcessSensorData::class);
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/sensor-data', []);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Dados inválidos'
                ])
                ->assertJsonStructure([
                    'error',
                    'details' => [
                        'temperature',
                        'humidity',
                        'noise',
                        'pression',
                        'eco2',
                        'tvoc',
                        'timestamp'
                    ]
                ]);
    }

    public function test_validates_numeric_fields()
    {
        $invalidData = [
            'temperature' => 'invalid',
            'humidity' => 'invalid',
            'noise' => 'invalid',
            'pression' => 'invalid',
            'eco2' => 'invalid',
            'tvoc' => 'invalid',
            'timestamp' => '2025-10-29 09:20:15'
        ];

        $response = $this->postJson('/api/sensor-data', $invalidData);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Dados inválidos'
                ]);
    }

    public function test_validates_timestamp_format()
    {
        $invalidData = [
            'temperature' => 23.4,
            'humidity' => 45.7,
            'noise' => 56.2,
            'pression' => 1013.2,
            'eco2' => 412,
            'tvoc' => 23,
            'timestamp' => 'invalid-date'
        ];

        $response = $this->postJson('/api/sensor-data', $invalidData);

        $response->assertStatus(400)
                ->assertJson([
                    'error' => 'Dados inválidos'
                ]);
    }

    public function test_creates_temporary_file()
    {
        $sensorData = [
            'temperature' => 23.4,
            'humidity' => 45.7,
            'noise' => 56.2,
            'pression' => 1013.2,
            'eco2' => 412,
            'tvoc' => 23,
            'timestamp' => '2025-10-29 09:20:15'
        ];

        $this->postJson('/api/sensor-data', $sensorData);

        $tempFiles = Storage::files('temp');
        $this->assertCount(1, $tempFiles);

        $fileContent = Storage::get($tempFiles[0]);
        $decodedData = json_decode($fileContent, true);

        $this->assertEquals($sensorData['temperature'], $decodedData['temperature']);
        $this->assertEquals($sensorData['humidity'], $decodedData['humidity']);
        $this->assertArrayHasKey('received_at', $decodedData);
    }
}
