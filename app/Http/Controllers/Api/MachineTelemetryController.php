<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MachineTelemetry;
use App\Models\SoapBatch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MachineTelemetryController extends Controller
{
    /**
     * Endpoint REST API para ESP32 / Arduino Machine Telemetry
     * POST /api/machine/telemetry
     */
    public function store(Request $request)
    {
        // 1. Token validation (Header X-API-Token or Bearer)
        $token = $request->header('X-API-Token') ?? $request->bearerToken() ?? $request->input('api_token');
        $expectedToken = config('app.iot_api_token', env('IOT_API_TOKEN', 'esp32_secret_device_token_2026'));

        if ($token !== $expectedToken) {
            return response()->json([
                'success' => false,
                'error' => 'No autorizado. Token API IoT no válido.',
            ], 401);
        }

        // 2. Validate payload from ESP32 sensors
        $validated = $request->validate([
            'oil_weight' => 'required|numeric|min:0', // Masa de aceite en gramos (g)
            'temperature' => 'required|numeric',     // Temperatura en °C
            'agitator_rpm' => 'nullable|integer|min:0',
            'heater_status' => 'nullable|boolean',
            'pump_status' => 'nullable|boolean',
            'trace_detected' => 'nullable|boolean',
        ]);

        $oilWeightGrams = (float) $validated['oil_weight'];
        $temperatureCelsius = (float) $validated['temperature'];
        $agitatorRpm = (int) ($validated['agitator_rpm'] ?? 0);
        $heaterStatus = (bool) ($validated['heater_status'] ?? false);
        $pumpStatus = (bool) ($validated['pump_status'] ?? false);
        $traceDetected = (bool) ($validated['trace_detected'] ?? false);

        // 3. Oil Volume in Liters (Density approx 0.92 g/ml -> 920 g/L)
        $oilVolumeLiters = round($oilWeightGrams / 920.0, 2);

        // 4. Automatic Saponification Formula Calculation per Specification:
        // NaOH (g) = Masa Aceite (g) * 0.134
        // Agua (ml) = Masa Aceite (g) * 0.35
        $naohGrams = round($oilWeightGrams * 0.134, 2);
        $waterMl = round($oilWeightGrams * 0.35, 2);

        // 5. Save Telemetry Record
        $telemetry = MachineTelemetry::create([
            'oil_weight_grams' => $oilWeightGrams,
            'oil_volume_liters' => $oilVolumeLiters,
            'temperature_celsius' => $temperatureCelsius,
            'agitator_rpm' => $agitatorRpm,
            'heater_status' => $heaterStatus,
            'pump_status' => $pumpStatus,
            'trace_detected' => $traceDetected,
        ]);

        // 6. Record or update Soap Batch if weight > 0
        $activeBatch = SoapBatch::where('status', 'en_proceso')->latest()->first();
        if (!$activeBatch && $oilWeightGrams > 500) {
            $activeBatch = SoapBatch::create([
                'batch_code' => 'LOTE-' . strtoupper(Str::random(6)),
                'oil_used_liters' => $oilVolumeLiters,
                'naoh_grams' => $naohGrams,
                'water_ml' => $waterMl,
                'start_time' => now(),
                'status' => 'en_proceso',
            ]);
        }

        // 7. Response JSON back to ESP32 with calculated values and actuators feedback
        return response()->json([
            'success' => true,
            'message' => 'Telemetría registrada y fórmula de saponificación calculada.',
            'timestamp' => now()->toIso8601String(),
            'sensor_readings' => [
                'oil_weight_grams' => $oilWeightGrams,
                'oil_volume_liters' => $oilVolumeLiters,
                'temperature_celsius' => $temperatureCelsius,
                'agitator_rpm' => $agitatorRpm,
                'heater_status' => $heaterStatus,
                'pump_status' => $pumpStatus,
                'trace_detected' => $traceDetected,
            ],
            'saponification_formula' => [
                'naoh_required_grams' => $naohGrams,
                'water_required_ml' => $waterMl,
                'formula_naoh' => "Masa Aceite ({$oilWeightGrams}g) * 0.134 = {$naohGrams}g",
                'formula_water' => "Masa Aceite ({$oilWeightGrams}g) * 0.35 = {$waterMl}ml",
            ],
            'active_batch' => $activeBatch ? [
                'batch_code' => $activeBatch->batch_code,
                'status' => $activeBatch->status,
            ] : null,
        ]);
    }
}
