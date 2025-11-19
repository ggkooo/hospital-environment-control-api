<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    public function index(Request $request)
    {
        $apiRoutes = $this->getApiRoutes();
        $translations = $this->getTranslations();

        return view('api-documentation', compact('apiRoutes', 'translations'));
    }

    private function getApiRoutes()
    {
        return $this->getEnglishRoutes();
    }

    private function getTranslations()
    {
        return [
            'title' => 'Hospital Environment Control API',
            'description' => 'API Documentation for hospital environmental monitoring',
            'authentication' => 'Authentication: X-API-Key',
            'base_url' => 'Base URL: /api',
            'headers' => 'Headers',
            'parameters' => 'Parameters',
            'query_params' => 'Query Parameters',
            'request_example' => 'Request Example',
            'request_body' => 'Request Body',
            'response_example' => 'Response Example',
            'footer_text' => 'Hospital Environment Control API v1.0'
        ];
    }

    private function getEnglishRoutes()
    {
        return [
            [
                'group' => 'Data Storage',
                'routes' => [
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/sensor-data/single',
                        'description' => 'Stores a single reading containing all sensor types',
                        'headers' => [
                            'X-API-Key' => 'your-api-key',
                            'Content-Type' => 'application/json'
                        ],
                        'body' => [
                            'temperature' => 25.5,
                            'humidity' => 60.0,
                            'noise' => 45.2,
                            'pression' => 1013.25,
                            'eco2' => 400,
                            'tvoc' => 150,
                            'timestamp' => '2025-11-14 10:00:00'
                        ],
                        'response' => [
                            'status' => 'success',
                            'message' => 'Data received and processing started',
                            'batch_id' => 'f2f62e15-16eb-4290-87e7-b19d84f0aa9c',
                            'total_records' => 1,
                            'received_at' => '2025-11-14 10:00:00',
                            'processing' => 'background'
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/sensor-data',
                        'description' => 'Stores multiple sensor readings in batch',
                        'headers' => [
                            'X-API-Key' => 'your-api-key',
                            'Content-Type' => 'application/json'
                        ],
                        'body' => [
                            'data' => [
                                [
                                    'temperature' => 25.5,
                                    'humidity' => 60.0,
                                    'noise' => 45.2,
                                    'pression' => 1013.25,
                                    'eco2' => 400,
                                    'tvoc' => 150,
                                    'timestamp' => '2025-11-14 10:00:00'
                                ],
                                [
                                    'temperature' => 26.0,
                                    'humidity' => 58.0,
                                    'noise' => 42.1,
                                    'pression' => 1012.80,
                                    'eco2' => 405,
                                    'tvoc' => 148,
                                    'timestamp' => '2025-11-14 10:01:00'
                                ]
                            ]
                        ],
                        'response' => [
                            'status' => 'success',
                            'message' => 'Data received and processing started',
                            'batch_id' => 'f2f62e15-16eb-4290-87e7-b19d84f0aa9c',
                            'total_records' => 2,
                            'received_at' => '2025-11-14 10:00:00',
                            'processing' => 'background'
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Raw Data',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/latest',
                        'description' => 'Gets the latest data from all sensors',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'response' => [
                            'status' => 'success',
                            'timestamp' => '2025-11-14 10:05:00',
                            'data' => [
                                'temperature' => [
                                    'value' => 25.5,
                                    'timestamp' => '2025-11-14 10:04:30'
                                ],
                                'humidity' => [
                                    'value' => 60.0,
                                    'timestamp' => '2025-11-14 10:04:30'
                                ],
                                'noise' => [
                                    'value' => 45.2,
                                    'timestamp' => '2025-11-14 10:04:30'
                                ],
                                'pression' => [
                                    'value' => 1013.25,
                                    'timestamp' => '2025-11-14 10:04:30'
                                ],
                                'eco2' => [
                                    'value' => 400,
                                    'timestamp' => '2025-11-14 10:04:30'
                                ],
                                'tvoc' => [
                                    'value' => 150,
                                    'timestamp' => '2025-11-14 10:04:30'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/all',
                        'description' => 'Gets raw data from all sensors',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-1000, default: 100)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/raw/all?limit=50&start_date=2025-11-14&order=desc',
                            'description' => 'Get last 50 records from 14/11/2025'
                        ],
                        'response' => [
                            'status' => 'success',
                            'total_records' => 50,
                            'limit' => 50,
                            'order' => 'desc',
                            'data' => [
                                [
                                    'id' => 12345,
                                    'temperature' => 25.5,
                                    'humidity' => 60.0,
                                    'noise' => 45.2,
                                    'pression' => 1013.25,
                                    'eco2' => 400,
                                    'tvoc' => 150,
                                    'timestamp' => '2025-11-14 10:00:00',
                                    'created_at' => '2025-11-14 10:00:05'
                                ],
                                [
                                    'id' => 12344,
                                    'temperature' => 25.3,
                                    'humidity' => 59.8,
                                    'noise' => 44.9,
                                    'pression' => 1013.10,
                                    'eco2' => 398,
                                    'tvoc' => 149,
                                    'timestamp' => '2025-11-14 09:59:00',
                                    'created_at' => '2025-11-14 09:59:05'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/{type}',
                        'description' => 'Gets raw data from a specific sensor',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-1000, default: 100)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/raw/temperature?limit=100&start_date=2025-11-14',
                            'description' => 'Get 100 temperature records from 14/11/2025'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'total_records' => 100,
                            'limit' => 100,
                            'order' => 'desc',
                            'data' => [
                                [
                                    'id' => 12345,
                                    'value' => 25.5,
                                    'timestamp' => '2025-11-14 10:00:00',
                                    'created_at' => '2025-11-14 10:00:05'
                                ],
                                [
                                    'id' => 12344,
                                    'value' => 25.3,
                                    'timestamp' => '2025-11-14 09:59:00',
                                    'created_at' => '2025-11-14 09:59:05'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/{type}/stats',
                        'description' => 'Calculates statistics from raw sensor data',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (required, format: Y-m-d)',
                            'end_date' => 'End date (required, format: Y-m-d)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'period' => [
                                'start_date' => '2025-11-14',
                                'end_date' => '2025-11-14'
                            ],
                            'statistics' => [
                                'total_readings' => 1440,
                                'average' => 25.32,
                                'minimum' => 22.1,
                                'maximum' => 28.5,
                                'standard_deviation' => 1.85
                            ]
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Minute Aggregated Data',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/all',
                        'description' => 'Gets minute-aggregated data from all sensors',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-1000, default: 100)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'aggregation' => 'minute',
                            'total_records' => 100,
                            'total_types' => 6,
                            'data' => [
                                'temperature' => [
                                    [
                                        'minute_timestamp' => '2025-11-14 10:00:00',
                                        'avg_value' => 25.32,
                                        'min_value' => 24.9,
                                        'max_value' => 25.8,
                                        'std_dev' => 0.25,
                                        'total_readings' => 60
                                    ]
                                ],
                                'humidity' => [
                                    [
                                        'minute_timestamp' => '2025-11-14 10:00:00',
                                        'avg_value' => 59.8,
                                        'min_value' => 58.5,
                                        'max_value' => 61.2,
                                        'std_dev' => 0.85,
                                        'total_readings' => 60
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/{type}',
                        'description' => 'Gets minute-aggregated data from a specific sensor',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-1000, default: 100)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'aggregation' => 'minute',
                            'total_records' => 100,
                            'limit' => 100,
                            'order' => 'desc',
                            'data' => [
                                [
                                    'minute_timestamp' => '2025-11-14 10:00:00',
                                    'avg_value' => 25.32,
                                    'min_value' => 24.9,
                                    'max_value' => 25.8,
                                    'std_dev' => 0.25,
                                    'total_readings' => 60
                                ],
                                [
                                    'minute_timestamp' => '2025-11-14 09:59:00',
                                    'avg_value' => 25.18,
                                    'min_value' => 24.7,
                                    'max_value' => 25.6,
                                    'std_dev' => 0.30,
                                    'total_readings' => 60
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/{type}/variations',
                        'description' => 'Gets minutes with significant data variations',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-1000, default: 100)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)',
                            'min_range' => 'Minimum variation to filter',
                            'min_std_dev' => 'Minimum standard deviation to filter'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'aggregation' => 'minute',
                            'filter' => [
                                'min_range' => 2.0,
                                'min_std_dev' => 0.5
                            ],
                            'total_records' => 25,
                            'data' => [
                                [
                                    'minute_timestamp' => '2025-11-14 14:23:00',
                                    'avg_value' => 27.45,
                                    'min_value' => 25.2,
                                    'max_value' => 29.8,
                                    'range' => 4.6,
                                    'std_dev' => 1.25,
                                    'total_readings' => 60,
                                    'variation_reason' => 'high_fluctuation'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/{type}/comparison',
                        'description' => 'Compares raw data with aggregated data from a specific minute',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'minute' => 'Specific minute (required, format: Y-m-d H:i:s)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'minute_timestamp' => '2025-11-14 10:00:00',
                            'aggregated_data' => [
                                'avg_value' => 25.32,
                                'min_value' => 24.9,
                                'max_value' => 25.8,
                                'std_dev' => 0.25,
                                'total_readings' => 60
                            ],
                            'raw_data' => [
                                [
                                    'value' => 25.1,
                                    'timestamp' => '2025-11-14 10:00:00'
                                ],
                                [
                                    'value' => 25.3,
                                    'timestamp' => '2025-11-14 10:00:01'
                                ]
                            ],
                            'comparison' => [
                                'accuracy' => 99.8,
                                'data_integrity' => 'excellent'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Hourly Aggregated Data',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/hourly/all',
                        'description' => 'Gets hourly-aggregated data from all sensors',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-1000, default: 100)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'aggregation' => 'hourly',
                            'total_types' => 6,
                            'data' => [
                                'temperature' => [
                                    [
                                        'hour_timestamp' => '2025-11-14 10:00:00',
                                        'avg_value' => 25.32,
                                        'min_value' => 24.1,
                                        'max_value' => 26.8,
                                        'std_dev' => 0.85,
                                        'total_readings' => 3600
                                    ]
                                ],
                                'humidity' => [
                                    [
                                        'hour_timestamp' => '2025-11-14 10:00:00',
                                        'avg_value' => 59.8,
                                        'min_value' => 57.2,
                                        'max_value' => 62.5,
                                        'std_dev' => 1.15,
                                        'total_readings' => 3600
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/hourly/{type}',
                        'description' => 'Gets hourly-aggregated data from a specific sensor',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-1000, default: 100)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'aggregation' => 'hourly',
                            'total_records' => 24,
                            'limit' => 100,
                            'order' => 'desc',
                            'data' => [
                                [
                                    'hour_timestamp' => '2025-11-14 10:00:00',
                                    'avg_value' => 25.32,
                                    'min_value' => 24.1,
                                    'max_value' => 26.8,
                                    'std_dev' => 0.85,
                                    'total_readings' => 3600
                                ],
                                [
                                    'hour_timestamp' => '2025-11-14 09:00:00',
                                    'avg_value' => 24.98,
                                    'min_value' => 23.5,
                                    'max_value' => 26.2,
                                    'std_dev' => 0.92,
                                    'total_readings' => 3600
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/hourly/{type}/stats',
                        'description' => 'Calculates hourly statistics from a sensor',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (required, format: Y-m-d)',
                            'end_date' => 'End date (required, format: Y-m-d)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'aggregation' => 'hourly',
                            'period' => [
                                'start_date' => '2025-11-14',
                                'end_date' => '2025-11-14'
                            ],
                            'statistics' => [
                                'total_hours' => 24,
                                'overall_average' => 25.32,
                                'absolute_minimum' => 22.1,
                                'absolute_maximum' => 28.5,
                                'average_std_dev' => 1.25
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/hourly/{type}/trends',
                        'description' => 'Analyzes hourly trends from a sensor',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (required, format: Y-m-d)',
                            'end_date' => 'End date (required, format: Y-m-d)',
                            'period' => 'Analysis period: 24h|7d|30d (default: 24h)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'aggregation' => 'hourly',
                            'period' => '24h',
                            'trend_analysis' => [
                                'direction' => 'ascending',
                                'percentage_change' => 2.5,
                                'first_value' => 24.8,
                                'last_value' => 25.4,
                                'peak_hour' => '2025-11-14 14:00:00',
                                'lowest_hour' => '2025-11-14 06:00:00'
                            ],
                            'total_records' => 24,
                            'data' => [
                                [
                                    'hour_timestamp' => '2025-11-14 00:00:00',
                                    'avg_value' => 24.8
                                ],
                                [
                                    'hour_timestamp' => '2025-11-14 01:00:00',
                                    'avg_value' => 24.9
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Daily Aggregated Data',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/daily/all',
                        'description' => 'Gets daily-aggregated data from all sensors',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-365, default: 30)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'aggregation' => 'daily',
                            'total_records' => 30,
                            'total_types' => 6,
                            'data' => [
                                'temperature' => [
                                    [
                                        'date' => '2025-11-14',
                                        'avg_value' => 25.32,
                                        'min_value' => 22.1,
                                        'max_value' => 28.5,
                                        'std_dev' => 1.85,
                                        'total_readings' => 86400
                                    ]
                                ],
                                'humidity' => [
                                    [
                                        'date' => '2025-11-14',
                                        'avg_value' => 59.8,
                                        'min_value' => 55.2,
                                        'max_value' => 65.1,
                                        'std_dev' => 2.45,
                                        'total_readings' => 86400
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/daily/{type}',
                        'description' => 'Gets daily-aggregated data from a specific sensor',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'limit' => 'Maximum number of records (1-365, default: 30)',
                            'start_date' => 'Start date (format: Y-m-d)',
                            'end_date' => 'End date (format: Y-m-d)',
                            'order' => 'Order: asc or desc (default: desc)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'aggregation' => 'daily',
                            'total_records' => 30,
                            'limit' => 30,
                            'order' => 'desc',
                            'data' => [
                                [
                                    'date' => '2025-11-14',
                                    'avg_value' => 25.32,
                                    'min_value' => 22.1,
                                    'max_value' => 28.5,
                                    'std_dev' => 1.85,
                                    'total_readings' => 86400
                                ],
                                [
                                    'date' => '2025-11-13',
                                    'avg_value' => 24.98,
                                    'min_value' => 21.8,
                                    'max_value' => 27.9,
                                    'std_dev' => 1.92,
                                    'total_readings' => 86400
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/daily/{type}/stats',
                        'description' => 'Calculates daily statistics from a sensor',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (required, format: Y-m-d)',
                            'end_date' => 'End date (required, format: Y-m-d)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'period' => [
                                'start_date' => '2025-11-14',
                                'end_date' => '2025-11-14'
                            ],
                            'statistics' => [
                                'total_readings' => 86400,
                                'average' => 25.32,
                                'minimum' => 22.1,
                                'maximum' => 28.5,
                                'standard_deviation' => 1.85
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/daily/{type}/monthly',
                        'description' => 'Gets monthly view of daily data',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'year' => 'Year (required, format: YYYY, between 2020-2030)',
                            'month' => 'Optional month (1-12). If not provided, returns whole year'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/daily/temperature/monthly?year=2025&month=11',
                            'description' => 'Get daily temperature data for November 2025'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'view' => 'monthly',
                            'period' => [
                                'year' => 2025,
                                'month' => 11,
                                'month_name' => 'November',
                                'total_days' => 30
                            ],
                            'total_records' => 14,
                            'data' => [
                                [
                                    'date' => '2025-11-14',
                                    'avg_value' => 25.32,
                                    'min_value' => 22.1,
                                    'max_value' => 28.5,
                                    'std_dev' => 1.85,
                                    'total_readings' => 86400
                                ],
                                [
                                    'date' => '2025-11-13',
                                    'avg_value' => 24.98,
                                    'min_value' => 21.8,
                                    'max_value' => 27.9,
                                    'std_dev' => 1.92,
                                    'total_readings' => 86400
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/daily/{type}/weekly',
                        'description' => 'Gets weekly view of daily data',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'type' => 'Sensor type: temperature|humidity|noise|pressure|eco2|tvoc'
                        ],
                        'query_params' => [
                            'week_start' => 'Week start date (required, format: Y-m-d)',
                            'weeks' => 'Number of weeks to include (1-12, default: 1)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/daily/temperature/weekly?week_start=2025-11-11&weeks=2',
                            'description' => 'Get daily temperature data for 2 weeks from 11/11/2025'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'view' => 'weekly',
                            'period' => [
                                'start_date' => '2025-11-11',
                                'end_date' => '2025-11-24',
                                'weeks_count' => 2
                            ],
                            'total_records' => 14,
                            'data' => [
                                [
                                    'week_start' => '2025-11-11',
                                    'week_end' => '2025-11-17',
                                    'week_number' => 46,
                                    'days' => [
                                        [
                                            'date' => '2025-11-11',
                                            'avg_value' => 24.85,
                                            'min_value' => 22.3,
                                            'max_value' => 27.2,
                                            'std_dev' => 1.75
                                        ],
                                        [
                                            'date' => '2025-11-12',
                                            'avg_value' => 25.12,
                                            'min_value' => 22.8,
                                            'max_value' => 28.1,
                                            'std_dev' => 1.88
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Users',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/users',
                        'description' => 'List all users',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'response' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'John Doe',
                                    'email' => 'john@example.com',
                                    'phone' => '123456789',
                                    'sector' => 'Cardiology',
                                    'role' => 'Doctor',
                                    'active' => true,
                                    'last_login' => '2025-11-14 10:00:00'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/users',
                        'description' => 'Create a new user',
                        'headers' => [
                            'X-API-Key' => 'your-api-key',
                            'Content-Type' => 'application/json'
                        ],
                        'body' => [
                            'name' => 'Jane Doe',
                            'email' => 'jane@example.com',
                            'password' => 'password123',
                            'phone' => '987654321',
                            'sector' => 'Neurology',
                            'role' => 'Nurse'
                        ],
                        'response' => [
                            'data' => [
                                'id' => 2,
                                'name' => 'Jane Doe',
                                'email' => 'jane@example.com',
                                'phone' => '987654321',
                                'sector' => 'Neurology',
                                'role' => 'Nurse',
                                'active' => true,
                                'created_at' => '2025-11-14 10:00:00'
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/users/{id}',
                        'description' => 'Get a specific user',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'id' => 'User ID'
                        ],
                        'response' => [
                            'data' => [
                                'id' => 1,
                                'name' => 'John Doe',
                                'email' => 'john@example.com',
                                'phone' => '123456789',
                                'sector' => 'Cardiology',
                                'role' => 'Doctor',
                                'active' => true,
                                'last_login' => '2025-11-14 10:00:00'
                            ]
                        ]
                    ],
                    [
                        'method' => 'PUT',
                        'endpoint' => '/api/users/{id}',
                        'description' => 'Update a user',
                        'headers' => [
                            'X-API-Key' => 'your-api-key',
                            'Content-Type' => 'application/json'
                        ],
                        'parameters' => [
                            'id' => 'User ID'
                        ],
                        'body' => [
                            'name' => 'John Smith',
                            'phone' => '111222333'
                        ],
                        'response' => [
                            'data' => [
                                'id' => 1,
                                'name' => 'John Smith',
                                'email' => 'john@example.com',
                                'phone' => '111222333',
                                'sector' => 'Cardiology',
                                'role' => 'Doctor',
                                'active' => true,
                                'updated_at' => '2025-11-14 10:00:00'
                            ]
                        ]
                    ],
                    [
                        'method' => 'DELETE',
                        'endpoint' => '/api/users/{id}',
                        'description' => 'Delete a user',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'parameters' => [
                            'id' => 'User ID'
                        ],
                        'response' => [
                            'message' => 'User deleted successfully'
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Sectors',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sectors',
                        'description' => 'List all sectors',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'response' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Cardiology',
                                    'description' => 'Heart care department'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/sectors',
                        'description' => 'Create a new sector',
                        'headers' => [
                            'X-API-Key' => 'your-api-key',
                            'Content-Type' => 'application/json'
                        ],
                        'body' => [
                            'name' => 'Neurology',
                            'description' => 'Brain care department'
                        ],
                        'response' => [
                            'data' => [
                                'id' => 2,
                                'name' => 'Neurology',
                                'description' => 'Brain care department',
                                'created_at' => '2025-11-14 10:00:00'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Roles',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/roles',
                        'description' => 'List all roles',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'response' => [
                            'data' => [
                                [
                                    'id' => 1,
                                    'name' => 'Doctor',
                                    'description' => 'Medical doctor'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/roles',
                        'description' => 'Create a new role',
                        'headers' => [
                            'X-API-Key' => 'your-api-key',
                            'Content-Type' => 'application/json'
                        ],
                        'body' => [
                            'name' => 'Nurse',
                            'description' => 'Nursing staff'
                        ],
                        'response' => [
                            'data' => [
                                'id' => 2,
                                'name' => 'Nurse',
                                'description' => 'Nursing staff',
                                'created_at' => '2025-11-14 10:00:00'
                            ]
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Password Reset',
                'routes' => [
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/password/reset-link',
                        'description' => 'Send password reset link to user email (public)',
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'body' => [
                            'email' => 'user@example.com'
                        ],
                        'response' => [
                            'message' => 'Password reset link sent to your email.'
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/password/reset',
                        'description' => 'Reset user password with token (public)',
                        'headers' => [
                            'Content-Type' => 'application/json'
                        ],
                        'body' => [
                            'token' => 'reset-token-from-email',
                            'email' => 'user@example.com',
                            'password' => 'newpassword',
                            'password_confirmation' => 'newpassword'
                        ],
                        'response' => [
                            'message' => 'Password has been reset.'
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Email',
                'routes' => [
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/send-email',
                        'description' => 'Send custom email with attachments',
                        'headers' => [
                            'X-API-Key' => 'your-api-key',
                            'Content-Type' => 'multipart/form-data'
                        ],
                        'body' => [
                            'to[]' => 'recipient1@example.com',
                            'to[]' => 'recipient2@example.com',
                            'subject' => 'Email Subject',
                            'body' => 'Email content here',
                            'attachments[]' => 'file1.pdf',
                            'attachments[]' => 'file2.jpg'
                        ],
                        'response' => [
                            'message' => 'Email sent successfully.'
                        ]
                    ]
                ]
            ]
        ];
    }
}
