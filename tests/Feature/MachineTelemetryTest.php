<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MachineTelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_esp32_can_send_telemetry_and_receive_saponification_formula(): void
    {
        $response = $this->withHeaders([
            'X-API-Token' => 'esp32_secret_device_token_2026',
        ])->postJson('/api/machine/telemetry', [
            'oil_weight' => 2000.00, // 2000g of oil
            'temperature' => 60.50,
            'agitator_rpm' => 350,
            'heater_status' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'sensor_readings' => [
                    'oil_weight_grams' => 2000,
                    'oil_volume_liters' => 2.17,
                    'temperature_celsius' => 60.5,
                ],
                'saponification_formula' => [
                    'naoh_required_grams' => 268, // 2000 * 0.134
                    'water_required_ml' => 700,    // 2000 * 0.35
                ],
            ]);

        $this->assertDatabaseHas('machine_telemetry', [
            'oil_weight_grams' => 2000.00,
            'temperature_celsius' => 60.50,
        ]);
    }

    public function test_invalid_api_token_is_rejected(): void
    {
        $response = $this->withHeaders([
            'X-API-Token' => 'wrong_token',
        ])->postJson('/api/machine/telemetry', [
            'oil_weight' => 1000.00,
            'temperature' => 50.00,
        ]);

        $response->assertStatus(401);
    }
}
