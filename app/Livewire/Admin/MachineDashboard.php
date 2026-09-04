<?php

namespace App\Livewire\Admin;

use App\Models\MachineTelemetry;
use App\Models\SoapBatch;
use Livewire\Component;

class MachineDashboard extends Component
{
    // Remote control states
    public bool $agitatorState = false;
    public int $agitatorRpm = 300;
    public bool $heaterSsrState = false;
    public bool $pumpState = false;
    public bool $emergencyStop = false;

    // Simulation tool state for testing without physical ESP32 connected
    public float $simOilWeight = 3000.00; // grams
    public float $simTemperature = 62.50;  // °C

    public function toggleAgitator()
    {
        if ($this->emergencyStop) return;
        $this->agitatorState = !$this->agitatorState;
        $this->recordControlAction();
    }

    public function toggleHeater()
    {
        if ($this->emergencyStop) return;
        $this->heaterSsrState = !$this->heaterSsrState;
        $this->recordControlAction();
    }

    public function togglePump()
    {
        if ($this->emergencyStop) return;
        $this->pumpState = !$this->pumpState;
        $this->recordControlAction();
    }

    public function triggerEmergencyStop()
    {
        $this->emergencyStop = !$this->emergencyStop;

        if ($this->emergencyStop) {
            $this->agitatorState = false;
            $this->heaterSsrState = false;
            $this->pumpState = false;
            session()->flash('error', '🚨 PARO DE EMERGENCIA ACTIVADO. Todos los actuadores desactivados.');
        } else {
            session()->flash('success', 'Paro de emergencia desactivado. Sistema reiniciado.');
        }

        $this->recordControlAction();
    }

    public function simulateEsp32Telemetry()
    {
        $oilVolume = round($this->simOilWeight / 920.0, 2);

        MachineTelemetry::create([
            'oil_weight_grams' => $this->simOilWeight,
            'oil_volume_liters' => $oilVolume,
            'temperature_celsius' => $this->simTemperature,
            'agitator_rpm' => $this->agitatorState ? $this->agitatorRpm : 0,
            'heater_status' => $this->heaterSsrState,
            'pump_status' => $this->pumpState,
            'trace_detected' => $this->simTemperature >= 60 && $this->agitatorState,
        ]);

        session()->flash('success', 'Telemetría ESP32 simulada enviada correctamente.');
    }

    public function finishActiveBatch(int $batchId)
    {
        $batch = SoapBatch::find($batchId);
        if ($batch) {
            $batch->update([
                'status' => 'completado',
                'end_time' => now(),
            ]);
            session()->flash('success', "Lote {$batch->batch_code} completado exitosamente.");
        }
    }

    private function recordControlAction()
    {
        $latest = MachineTelemetry::latest()->first();

        MachineTelemetry::create([
            'oil_weight_grams' => $latest?->oil_weight_grams ?? 2500.00,
            'oil_volume_liters' => $latest?->oil_volume_liters ?? 2.71,
            'temperature_celsius' => $latest?->temperature_celsius ?? 55.00,
            'agitator_rpm' => $this->agitatorState ? $this->agitatorRpm : 0,
            'heater_status' => $this->heaterSsrState,
            'pump_status' => $this->pumpState,
            'trace_detected' => $latest?->trace_detected ?? false,
        ]);
    }

    public function render()
    {
        $latestTelemetry = MachineTelemetry::latest()->first();

        // Calculate formulas based on latest or sim telemetry
        $currentWeight = $latestTelemetry?->oil_weight_grams ?? $this->simOilWeight;
        $calculatedVolume = round($currentWeight / 920.0, 2);
        $naohGrams = round($currentWeight * 0.134, 2);
        $waterMl = round($currentWeight * 0.35, 2);

        return view('livewire.admin.machine-dashboard', [
            'latest' => $latestTelemetry,
            'currentWeight' => $currentWeight,
            'calculatedVolume' => $calculatedVolume,
            'naohGrams' => $naohGrams,
            'waterMl' => $waterMl,
            'recentLogs' => MachineTelemetry::latest()->take(10)->get(),
            'soapBatches' => SoapBatch::latest()->take(5)->get(),
        ])->layout('layouts.app');
    }
}
