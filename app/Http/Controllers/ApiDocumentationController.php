<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ApiDocumentationController extends Controller
{
    public function index()
    {
        $apiRoutes = [
            [
                'group' => 'Individual Reading',
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
                            'timestamp' => '2025-10-30 10:00:00'
                        ],
                        'response' => [
                            'status' => 'success',
                            'message' => 'Data received successfully',
                            'file' => 'sensor_data_batch_f2f62e15-16eb-4290-87e7-b19d84f0aa9c.json',
                            'total_records' => 1
                        ]
                    ],
                    [
                        'method' => 'POST',
                        'endpoint' => '/api/sensor-data',
                        'description' => 'Stores multiple sensor readings in batch (each reading must contain all sensor types)',
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
                                    'timestamp' => '2025-10-30 10:00:00'
                                ],
                                [
                                    'temperature' => 26.0,
                                    'humidity' => 58.5,
                                    'noise' => 47.1,
                                    'pression' => 1012.80,
                                    'eco2' => 420,
                                    'tvoc' => 160,
                                    'timestamp' => '2025-10-30 10:01:00'
                                ]
                            ]
                        ],
                        'response' => [
                            'status' => 'success',
                            'message' => 'Data received successfully',
                            'file' => 'sensor_data_batch_1a3370e5-1896-422d-8014-4c7774a13c74.json',
                            'total_records' => 2
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Raw Readings',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/latest',
                        'description' => 'Gets the latest readings from all sensor types',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/raw/latest',
                            'description' => 'Retrieves the most recent reading from each sensor type (temperature, humidity, noise, pressure, eco2, tvoc)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'timestamp' => '2025-10-30 15:30:00',
                            'data' => [
                                'temperature' => [
                                    'value' => '25.50',
                                    'timestamp' => '2025-10-30 15:29:45'
                                ],
                                'humidity' => [
                                    'value' => '60.00',
                                    'timestamp' => '2025-10-30 15:29:50'
                                ],
                                'noise' => [
                                    'value' => '45.20',
                                    'timestamp' => '2025-10-30 15:29:48'
                                ],
                                'pressure' => [
                                    'value' => '1013.25',
                                    'timestamp' => '2025-10-30 15:29:52'
                                ],
                                'eco2' => [
                                    'value' => '400',
                                    'timestamp' => '2025-10-30 15:29:46'
                                ],
                                'tvoc' => [
                                    'value' => '150',
                                    'timestamp' => '2025-10-30 15:29:49'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/all',
                        'description' => 'Gets all sensor readings',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'limit' => 'Number of records per page (default: 100)',
                            'page' => 'Page number (default: 1)',
                            'start_date' => 'Start date (format: Y-m-d H:i:s)',
                            'end_date' => 'End date (format: Y-m-d H:i:s)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/raw/all?limit=50&page=2&start_date=2025-10-30 08:00:00&end_date=2025-10-30 18:00:00',
                            'description' => 'Retrieves 50 records from page 2, between 08:00 and 18:00 on 2025-10-30'
                        ],
                        'response' => [
                            'success' => true,
                            'data' => [
                                'current_page' => 2,
                                'data' => [
                                    [
                                        'id' => 51,
                                        'sensor_type' => 'temperature',
                                        'value' => '25.50',
                                        'timestamp' => '2025-10-30 08:30:00'
                                    ],
                                    [
                                        'id' => 52,
                                        'sensor_type' => 'humidity',
                                        'value' => '60.00',
                                        'timestamp' => '2025-10-30 08:30:00'
                                    ],
                                    [
                                        'id' => 53,
                                        'sensor_type' => 'noise',
                                        'value' => '45.20',
                                        'timestamp' => '2025-10-30 08:31:00'
                                    ]
                                ],
                                'total' => 1000,
                                'per_page' => 50,
                                'last_page' => 20,
                                'from' => 51,
                                'to' => 100
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/{type}',
                        'description' => 'Gets readings from a specific sensor type',
                        'parameters' => [
                            'type' => 'Sensor type (temperature, humidity, noise, pressure, eco2, tvoc)'
                        ],
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'limit' => 'Number of records per page (default: 100)',
                            'start_date' => 'Start date (format: Y-m-d H:i:s)',
                            'end_date' => 'End date (format: Y-m-d H:i:s)',
                            'order' => 'Result order: asc or desc (default: desc)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/raw/temperature?limit=100&start_date=2025-10-30 06:00:00&end_date=2025-10-30 12:00:00&order=asc',
                            'description' => 'Retrieves up to 100 temperature readings between 06:00 and 12:00, ordered chronologically (oldest first)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'total_records' => 48,
                            'data' => [
                                ['id' => 1, 'value' => '25.50', 'timestamp' => '2025-10-30 06:15:00'],
                                ['id' => 2, 'value' => '25.80', 'timestamp' => '2025-10-30 06:30:00'],
                                ['id' => 3, 'value' => '24.90', 'timestamp' => '2025-10-30 06:45:00'],
                                ['id' => 4, 'value' => '26.10', 'timestamp' => '2025-10-30 07:00:00'],
                                ['id' => 5, 'value' => '25.75', 'timestamp' => '2025-10-30 07:15:00']
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/raw/{type}/stats',
                        'description' => 'Gets statistics for readings from a sensor type',
                        'parameters' => [
                            'type' => 'Sensor type (temperature, humidity, noise, pressure, eco2, tvoc)'
                        ],
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (required, format: Y-m-d H:i:s)',
                            'end_date' => 'End date (required, format: Y-m-d H:i:s)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/raw/temperature/stats?start_date=2025-10-30 06:00:00&end_date=2025-10-30 18:00:00',
                            'description' => 'Gets temperature statistics between 06:00 and 18:00. Both start_date and end_date parameters are required'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'period' => [
                                'start_date' => '2025-10-30 06:00:00',
                                'end_date' => '2025-10-30 18:00:00'
                            ],
                            'statistics' => [
                                'total_readings' => 39,
                                'average' => 25.74,
                                'minimum' => '25.10',
                                'maximum' => '26.30',
                                'standard_deviation' => 0.35
                            ]
                        ]
                    ]
                ]
            ],
            [
                'group' => 'Aggregated by Minute',
                'routes' => [
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/all',
                        'description' => 'Gets minute-aggregated data from all sensor types',
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (format: Y-m-d H:i:s)',
                            'end_date' => 'End date (format: Y-m-d H:i:s)',
                            'limit' => 'Number of records (default: 100)',
                            'order' => 'Result order: asc or desc (default: desc)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/minute/all?start_date=2025-10-30 08:00:00&end_date=2025-10-30 16:00:00&limit=50&order=asc',
                            'description' => 'Retrieves up to 50 minute-aggregated records from all sensors, between 08:00 and 16:00, ordered chronologically (oldest first)'
                        ],
                        'response' => [
                            'status' => 'success',
                            'aggregation' => 'minute',
                            'total_types' => 6,
                            'data' => [
                                'temperature' => [
                                    [
                                        'avg_value' => '25.50',
                                        'min_value' => '24.00',
                                        'max_value' => '27.00',
                                        'std_dev' => '1.25',
                                        'reading_count' => 60,
                                        'variation_range' => '3.00',
                                        'minute_timestamp' => '2025-10-30 08:00:00'
                                    ],
                                    [
                                        'avg_value' => '25.80',
                                        'min_value' => '24.50',
                                        'max_value' => '27.20',
                                        'std_dev' => '1.10',
                                        'reading_count' => 58,
                                        'variation_range' => '2.70',
                                        'minute_timestamp' => '2025-10-30 08:01:00'
                                    ]
                                ],
                                'humidity' => [
                                    [
                                        'avg_value' => '60.00',
                                        'min_value' => '58.00',
                                        'max_value' => '62.00',
                                        'std_dev' => '2.15',
                                        'reading_count' => 60,
                                        'variation_range' => '4.00',
                                        'minute_timestamp' => '2025-10-30 08:00:00'
                                    ]
                                ],
                                'noise' => [
                                    [
                                        'avg_value' => '45.20',
                                        'min_value' => '42.00',
                                        'max_value' => '48.50',
                                        'std_dev' => '3.25',
                                        'reading_count' => 60,
                                        'variation_range' => '6.50',
                                        'minute_timestamp' => '2025-10-30 08:00:00'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/{type}',
                        'description' => 'Gets minute-aggregated data from a specific sensor type',
                        'parameters' => [
                            'type' => 'Sensor type (temperature, humidity, noise, pressure, eco2, tvoc)'
                        ],
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (format: Y-m-d H:i:s)',
                            'end_date' => 'End date (format: Y-m-d H:i:s)',
                            'limit' => 'Number of records (default: 100)',
                            'order' => 'Result order: asc or desc (default: desc)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/minute/temperature?start_date=2025-10-30 10:00:00&end_date=2025-10-30 14:00:00&limit=30&order=asc',
                            'description' => 'Retrieves up to 30 minute-aggregated temperature records, between 10:00 and 14:00, ordered chronologically'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'aggregation' => 'minute',
                            'total_records' => 30,
                            'data' => [
                                [
                                    'avg_value' => '25.50',
                                    'min_value' => '24.00',
                                    'max_value' => '27.00',
                                    'std_dev' => '1.25',
                                    'reading_count' => 60,
                                    'variation_range' => '3.00',
                                    'minute_timestamp' => '2025-10-30 10:00:00'
                                ],
                                [
                                    'avg_value' => '25.80',
                                    'min_value' => '24.20',
                                    'max_value' => '27.30',
                                    'std_dev' => '1.35',
                                    'reading_count' => 58,
                                    'variation_range' => '3.10',
                                    'minute_timestamp' => '2025-10-30 10:01:00'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/{type}/variations',
                        'description' => 'Gets variations in minute-aggregated data based on configurable thresholds',
                        'parameters' => [
                            'type' => 'Sensor type (temperature, humidity, noise, pressure, eco2, tvoc)'
                        ],
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'start_date' => 'Start date (format: Y-m-d H:i:s)',
                            'end_date' => 'End date (format: Y-m-d H:i:s)',
                            'limit' => 'Number of records (default: 100)',
                            'order' => 'Result order: asc or desc (default: desc)',
                            'min_range' => 'Minimum variation_range value to filter (numeric)',
                            'min_std_dev' => 'Minimum standard deviation value to filter (numeric)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/minute/temperature/variations?start_date=2025-10-30 08:00:00&end_date=2025-10-30 16:00:00&min_range=2.0&min_std_dev=1.0&limit=20&order=desc',
                            'description' => 'Retrieves temperature variations with range > 2.0 or standard deviation > 1.0, between 08:00 and 16:00, limited to 20 records, ordered by descending timestamp'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'filter' => 'variations',
                            'thresholds' => [
                                'min_range' => 2.0,
                                'min_std_dev' => 1.0
                            ],
                            'total_records' => 15,
                            'data' => [
                                [
                                    'avg_value' => '26.20',
                                    'min_value' => '23.50',
                                    'max_value' => '28.10',
                                    'std_dev' => '1.85',
                                    'reading_count' => 45,
                                    'variation_range' => '4.60',
                                    'minute_timestamp' => '2025-10-30 14:30:00'
                                ],
                                [
                                    'avg_value' => '25.80',
                                    'min_value' => '23.20',
                                    'max_value' => '27.40',
                                    'std_dev' => '1.65',
                                    'reading_count' => 52,
                                    'variation_range' => '4.20',
                                    'minute_timestamp' => '2025-10-30 12:15:00'
                                ]
                            ]
                        ]
                    ],
                    [
                        'method' => 'GET',
                        'endpoint' => '/api/sensor-data/minute/{type}/comparison',
                        'description' => 'Compares raw data with aggregated data for a specific minute',
                        'parameters' => [
                            'type' => 'Sensor type (temperature, humidity, noise, pressure, eco2, tvoc)'
                        ],
                        'headers' => [
                            'X-API-Key' => 'your-api-key'
                        ],
                        'query_params' => [
                            'minute' => 'Specific minute timestamp (required, format: Y-m-d H:i:s)'
                        ],
                        'example_request' => [
                            'url' => '/api/sensor-data/minute/temperature/comparison?minute=2025-10-30 14:30:00',
                            'description' => 'Compares raw temperature data with aggregated data for minute 14:30:00'
                        ],
                        'response' => [
                            'status' => 'success',
                            'type' => 'temperature',
                            'minute' => '2025-10-30 14:30:00',
                            'raw_data' => [
                                'count' => 58,
                                'readings' => [
                                    [
                                        'value' => '25.20',
                                        'timestamp' => '2025-10-30 14:30:05'
                                    ],
                                    [
                                        'value' => '25.80',
                                        'timestamp' => '2025-10-30 14:30:15'
                                    ],
                                    [
                                        'value' => '26.10',
                                        'timestamp' => '2025-10-30 14:30:25'
                                    ]
                                ]
                            ],
                            'aggregate_data' => [
                                'avg_value' => '25.70',
                                'min_value' => '24.50',
                                'max_value' => '27.20',
                                'std_dev' => '1.25',
                                'reading_count' => 58,
                                'variation_range' => '2.70',
                                'minute_timestamp' => '2025-10-30 14:30:00'
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return view('api-documentation', compact('apiRoutes'));
    }
}
