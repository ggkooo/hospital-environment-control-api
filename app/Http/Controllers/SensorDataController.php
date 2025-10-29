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
            'data' => 'required|array|min:1',
            'data.*.temperature' => 'required|numeric',
            'data.*.humidity' => 'required|numeric',
            'data.*.noise' => 'required|numeric',
            'data.*.pression' => 'required|numeric',
            'data.*.eco2' => 'required|numeric',
            'data.*.tvoc' => 'required|numeric',
            'data.*.timestamp' => 'required|date_format:Y-m-d H:i:s'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Dados inválidos',
                'details' => $validator->errors()
            ], 400);
        }

        try {
            $filename = 'sensor_data_batch_' . Str::uuid() . '.json';
            $tempPath = 'temp/' . $filename;
            $receivedAt = now()->toDateTimeString();

            $sensorDataArray = array_map(function ($item) use ($receivedAt) {
                $item['received_at'] = $receivedAt;
                return $item;
            }, $request->data);

            Storage::put($tempPath, json_encode($sensorDataArray));

            $response = response()->json([
                'status' => 'success',
                'message' => 'Dados recebidos com sucesso',
                'file' => $filename,
                'total_records' => count($sensorDataArray)
            ], 200);

            if (function_exists('fastcgi_finish_request')) {
                $response->send();
                fastcgi_finish_request();

                $job = new ProcessSensorData($tempPath, $sensorDataArray);
                $job->handle();
            } else {
                register_shutdown_function(function () use ($tempPath, $sensorDataArray) {
                    $job = new ProcessSensorData($tempPath, $sensorDataArray);
                    $job->handle();
                });
            }

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro interno do servidor',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function storeSingle(Request $request): JsonResponse
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

        $request->merge(['data' => [$request->only(['temperature', 'humidity', 'noise', 'pression', 'eco2', 'tvoc', 'timestamp'])]]);

        return $this->store($request);
    }
}
