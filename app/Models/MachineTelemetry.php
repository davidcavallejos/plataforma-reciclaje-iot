<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MachineTelemetry extends Model
{
    use HasFactory;

    protected $table = 'machine_telemetry';

    protected $fillable = [
        'oil_weight_grams',
        'oil_volume_liters',
        'temperature_celsius',
        'agitator_rpm',
        'heater_status',
        'pump_status',
        'trace_detected',
    ];

    protected $casts = [
        'oil_weight_grams' => 'decimal:2',
        'oil_volume_liters' => 'decimal:2',
        'temperature_celsius' => 'decimal:2',
        'agitator_rpm' => 'integer',
        'heater_status' => 'boolean',
        'pump_status' => 'boolean',
        'trace_detected' => 'boolean',
    ];
}
