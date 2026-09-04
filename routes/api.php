<?php

use App\Http\Controllers\Api\MachineTelemetryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes for Machine IoT ESP32 / Arduino
|--------------------------------------------------------------------------
*/

Route::post('/machine/telemetry', [MachineTelemetryController::class, 'store']);
