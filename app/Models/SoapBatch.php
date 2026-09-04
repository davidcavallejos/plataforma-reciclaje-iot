<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoapBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_code',
        'oil_used_liters',
        'naoh_grams',
        'water_ml',
        'start_time',
        'end_time',
        'status',
    ];

    protected $casts = [
        'oil_used_liters' => 'decimal:2',
        'naoh_grams' => 'decimal:2',
        'water_ml' => 'decimal:2',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
}
