<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessSensorData;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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

    public function getData(Request $request, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:1000',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'order' => 'in:asc,desc'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!$this->isValidSensorType($type)) {
            return $this->invalidSensorTypeResponse();
        }

        try {
            $tableName = 's_' . ($type === 'pressure' ? 'pressure' : $type);
            $query = DB::table($tableName);

            $this->applyDateFilters($query, $request);
            $query->orderBy('timestamp', $request->input('order', 'desc'));

            $data = $query->limit($request->input('limit', 100))->get();

            return response()->json([
                'status' => 'success',
                'type' => $type,
                'total_records' => $data->count(),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao buscar dados', $e->getMessage());
        }
    }

    public function getAllData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:1000',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'order' => 'in:asc,desc'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $sensorTypes = $this->getSensorTypes();
            $allData = [];

            foreach ($sensorTypes as $type => $table) {
                $query = DB::table($table)->select('value', 'timestamp');
                $this->applyDateFilters($query, $request);
                $query->orderBy('timestamp', $request->input('order', 'desc'));
                $allData[$type] = $query->limit($request->input('limit', 100))->get();
            }

            return response()->json([
                'status' => 'success',
                'total_types' => count($sensorTypes),
                'data' => $allData
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao buscar dados', $e->getMessage());
        }
    }

    public function getLatestData(): JsonResponse
    {
        try {
            $sensorTypes = $this->getSensorTypes();
            $latestData = [];

            foreach ($sensorTypes as $type => $table) {
                $latestData[$type] = DB::table($table)
                    ->select('value', 'timestamp')
                    ->orderBy('timestamp', 'desc')
                    ->first();
            }

            return response()->json([
                'status' => 'success',
                'timestamp' => now()->toDateTimeString(),
                'data' => $latestData
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao buscar dados mais recentes', $e->getMessage());
        }
    }

    public function getStats(Request $request, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!$this->isValidSensorType($type)) {
            return $this->invalidSensorTypeResponse();
        }

        try {
            $tableName = 's_' . ($type === 'pressure' ? 'pressure' : $type);
            $query = DB::table($tableName);

            $this->applyDateFilters($query, $request);

            $stats = $query->selectRaw('
                COUNT(*) as total_readings,
                AVG(value) as average,
                MIN(value) as minimum,
                MAX(value) as maximum,
                STDDEV(value) as standard_deviation
            ')->first();

            return response()->json([
                'status' => 'success',
                'type' => $type,
                'period' => [
                    'start_date' => $request->input('start_date'),
                    'end_date' => $request->input('end_date')
                ],
                'statistics' => [
                    'total_readings' => (int) $stats->total_readings,
                    'average' => round($stats->average, 2),
                    'minimum' => $stats->minimum,
                    'maximum' => $stats->maximum,
                    'standard_deviation' => round($stats->standard_deviation, 2)
                ]
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao calcular estatísticas', $e->getMessage());
        }
    }

    public function getMinuteData(Request $request, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:1000',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'order' => 'in:asc,desc'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!$this->isValidSensorType($type)) {
            return $this->invalidSensorTypeResponse();
        }

        try {
            $query = DB::table('m_' . $type);
            $this->applyDateFilters($query, $request, 'minute_timestamp');
            $query->orderBy('minute_timestamp', $request->input('order', 'desc'));

            $data = $query->limit($request->input('limit', 100))->get();

            return response()->json([
                'status' => 'success',
                'type' => $type,
                'aggregation' => 'minute',
                'total_records' => $data->count(),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao buscar dados agregados', $e->getMessage());
        }
    }

    public function getAllMinuteData(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:1000',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'order' => 'in:asc,desc'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        try {
            $sensorTypes = $this->getMinuteSensorTypes();
            $allData = [];

            foreach ($sensorTypes as $type => $table) {
                $query = DB::table($table);
                $this->applyDateFilters($query, $request, 'minute_timestamp');
                $query->orderBy('minute_timestamp', $request->input('order', 'desc'));
                $allData[$type] = $query->limit($request->input('limit', 100))->get();
            }

            return response()->json([
                'status' => 'success',
                'aggregation' => 'minute',
                'total_types' => count($sensorTypes),
                'data' => $allData
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao buscar dados agregados', $e->getMessage());
        }
    }

    public function getVariations(Request $request, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'limit' => 'integer|min:1|max:1000',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
            'order' => 'in:asc,desc',
            'min_range' => 'numeric|min:0',
            'min_std_dev' => 'numeric|min:0'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!$this->isValidSensorType($type)) {
            return $this->invalidSensorTypeResponse();
        }

        try {
            $thresholds = $this->getVariationThresholds()[$type];
            $minRange = $request->input('min_range', $thresholds['range']);
            $minStdDev = $request->input('min_std_dev', $thresholds['std_dev']);

            $query = DB::table('m_' . $type);
            $query->where(function($q) use ($minRange, $minStdDev) {
                $q->where('variation_range', '>', $minRange)
                  ->orWhere('std_dev', '>', $minStdDev);
            });

            $this->applyDateFilters($query, $request, 'minute_timestamp');
            $query->orderBy('minute_timestamp', $request->input('order', 'desc'));

            $data = $query->limit($request->input('limit', 100))->get();

            return response()->json([
                'status' => 'success',
                'type' => $type,
                'filter' => 'variations',
                'thresholds' => [
                    'min_range' => $minRange,
                    'min_std_dev' => $minStdDev
                ],
                'total_records' => $data->count(),
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao buscar variações', $e->getMessage());
        }
    }

    public function getComparison(Request $request, string $type): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'minute' => 'required|date_format:Y-m-d H:i:s'
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator->errors());
        }

        if (!$this->isValidSensorType($type)) {
            return $this->invalidSensorTypeResponse();
        }

        try {
            $minute = $request->input('minute');
            $minuteStart = Carbon::parse($minute)->startOfMinute();
            $minuteEnd = Carbon::parse($minute)->endOfMinute();
            $minuteKey = $minuteStart->format('Y-m-d H:i:00');

            $rawTable = 's_' . ($type === 'pressure' ? 'pressure' : $type);
            $rawData = DB::table($rawTable)
                ->where('timestamp', '>=', $minuteStart)
                ->where('timestamp', '<=', $minuteEnd)
                ->orderBy('timestamp')
                ->get();

            $aggregateData = DB::table('m_' . $type)
                ->where('minute_timestamp', $minuteKey)
                ->first();

            return response()->json([
                'status' => 'success',
                'type' => $type,
                'minute' => $minuteKey,
                'raw_data' => [
                    'count' => $rawData->count(),
                    'readings' => $rawData
                ],
                'aggregate_data' => $aggregateData
            ]);

        } catch (\Exception $e) {
            return $this->errorResponse('Erro ao buscar comparação', $e->getMessage());
        }
    }

    private function getSensorTypes(): array
    {
        return [
            'temperature' => 's_temperature',
            'humidity' => 's_humidity',
            'noise' => 's_noise',
            'pressure' => 's_pressure',
            'eco2' => 's_eco2',
            'tvoc' => 's_tvoc'
        ];
    }

    private function getMinuteSensorTypes(): array
    {
        return [
            'temperature' => 'm_temperature',
            'humidity' => 'm_humidity',
            'noise' => 'm_noise',
            'pressure' => 'm_pressure',
            'eco2' => 'm_eco2',
            'tvoc' => 'm_tvoc'
        ];
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

    private function isValidSensorType(string $type): bool
    {
        return in_array($type, ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc']);
    }

    private function applyDateFilters($query, Request $request, string $timestampColumn = 'timestamp'): void
    {
        if ($request->has('start_date')) {
            $query->where($timestampColumn, '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->where($timestampColumn, '<=', $request->end_date);
        }
    }

    private function validationErrorResponse($errors): JsonResponse
    {
        return response()->json([
            'error' => 'Parâmetros inválidos',
            'details' => $errors
        ], 400);
    }

    private function invalidSensorTypeResponse(): JsonResponse
    {
        return response()->json([
            'error' => 'Tipo de sensor inválido',
            'valid_types' => ['temperature', 'humidity', 'noise', 'pressure', 'eco2', 'tvoc']
        ], 400);
    }

    private function errorResponse(string $error, string $message): JsonResponse
    {
        return response()->json([
            'error' => $error,
            'message' => $message
        ], 500);
    }
}
