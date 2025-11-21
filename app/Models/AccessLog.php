<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'timestamp',
        'user',
        'role',
        'action',
        'page',
        'ip',
        'user_agent',
        'city',
        'location',
        'latitude',
        'longitude',
    ];

    protected $casts = [
        'timestamp' => 'datetime',
    ];
}
