<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SensorReading extends Model
{
    use HasFactory;

    protected $fillable = [
        'temperature',
        'humidity',
        'noise',
        'pression',
        'eco2',
        'tvoc',
        'sensor_timestamp',
        'received_at',
        'file_origin'
    ];

    protected $casts = [
        'temperature' => 'decimal:2',
        'humidity' => 'decimal:2',
        'noise' => 'decimal:2',
        'pression' => 'decimal:2',
        'eco2' => 'integer',
        'tvoc' => 'integer',
        'sensor_timestamp' => 'datetime',
        'received_at' => 'datetime',
    ];

    public function scopeRecent($query, $hours = 24)
    {
        return $query->where('sensor_timestamp', '>=', Carbon::now()->subHours($hours));
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('sensor_timestamp', [$startDate, $endDate]);
    }
}
