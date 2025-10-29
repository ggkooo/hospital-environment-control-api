<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSensorData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class SensorDataController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'temperature' => 'required|numeric',
            'humidity' => 'required|numeric',
            'noise' => 'required|numeric',
            'pression' => 'required|numeric',
            'eco2' => 'required|numeric',
            'tvoc' => 'required|numeric',
            'timestamp' => 'required|date_format:Y-m-d H:i:s'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $filename = 'sensor_data_' . Str::uuid() . '.json';
            $tempPath = 'temp/' . $filename;

            $sensorData = [
                'temperature' => $request->temperature,
                'humidity' => $request->humidity,
                'noise' => $request->noise,
                'pression' => $request->pression,
                'eco2' => $request->eco2,
                'tvoc' => $request->tvoc,
                'timestamp' => $request->timestamp,
                'received_at' => now()->toDateTimeString()
            ];

            Storage::put($tempPath, json_encode($sensorData, JSON_PRETTY_PRINT));

            ProcessSensorData::dispatch($tempPath, $sensorData);

            return response()->json([
                'status' => 'success',
                'message' => 'Dados recebidos e processamento iniciado',
                'file' => $filename
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
