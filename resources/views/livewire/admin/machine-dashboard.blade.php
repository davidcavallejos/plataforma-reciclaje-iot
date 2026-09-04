<div wire:poll.3s>
    <!-- Header Banner -->
    <div class="mb-8 p-6 sm:p-8 rounded-3xl glass-card border border-amber-500/30 bg-gradient-to-r from-slate-900 via-amber-950/30 to-slate-900 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-500/20 text-amber-300 text-xs font-bold border border-amber-500/40 mb-3">
                <i class="fa-solid fa-microchip text-amber-400 animate-pulse"></i> Panel de Control IoT en Tiempo Real (ESP32)
            </span>
            <h1 class="heading-font text-3xl font-extrabold text-slate-100 flex items-center gap-3">
                Máquina Saponificadora de Jabón
            </h1>
            <p class="text-slate-300 text-sm mt-1">
                Monitoreo remoto de sensores, actuadores SSR y cálculo automatizado de la fórmula de saponificación.
            </p>
        </div>

        <!-- Emergency Stop Button -->
        <button wire:click="triggerEmergencyStop"
            class="px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-wider transition transform active:scale-95 shadow-2xl flex items-center gap-3 border {{ $emergencyStop ? 'bg-emerald-600 hover:bg-emerald-500 text-white border-emerald-400 shadow-emerald-600/40 animate-bounce' : 'bg-rose-600 hover:bg-rose-700 text-white border-rose-500 shadow-rose-600/50' }}">
            <i class="fa-solid fa-triangle-exclamation text-xl"></i>
            <span>{{ $emergencyStop ? 'REINICIAR SISTEMA' : 'PARO DE EMERGENCIA' }}</span>
        </button>
    </div>

    <!-- Live Sensor Telemetry Gauges (Grid of 4) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        
        <!-- Gauge 1: Temperature (°C) -->
        <div class="glass-card p-6 rounded-3xl border border-slate-700/80 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Temperatura Reactor</span>
                <div class="w-10 h-10 rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-400 text-lg">
                    <i class="fa-solid fa-temperature-high"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="heading-font text-4xl font-extrabold text-slate-100">
                    {{ number_format($latest?->temperature_celsius ?? 55.00, 1) }}
                </span>
                <span class="text-xl font-bold text-rose-400">°C</span>
            </div>
            <div class="mt-4 w-full bg-slate-800 rounded-full h-2 overflow-hidden">
                @php $temp = $latest?->temperature_celsius ?? 55.00; @endphp
                <div class="bg-gradient-to-r from-teal-400 via-amber-400 to-rose-500 h-full rounded-full transition-all duration-500" style="width: {{ min(100, max(10, ($temp / 100) * 100)) }}%"></div>
            </div>
            <span class="text-[10px] text-slate-400 mt-2 block">Rango óptimo saponificación: 50°C - 70°C</span>
        </div>

        <!-- Gauge 2: Oil Volume (Liters & Grams) -->
        <div class="glass-card p-6 rounded-3xl border border-slate-700/80 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Volumen / Masa Aceite</span>
                <div class="w-10 h-10 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-400 text-lg">
                    <i class="fa-solid fa-oil-can"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="heading-font text-4xl font-extrabold text-slate-100">
                    {{ $calculatedVolume }}
                </span>
                <span class="text-xl font-bold text-amber-400">Litros</span>
            </div>
            <p class="text-xs font-semibold text-slate-400 mt-2">
                Masa en celda de carga: <span class="text-amber-300 font-bold">{{ number_format($currentWeight, 0) }} g</span>
            </p>
        </div>

        <!-- Gauge 3: Agitator RPM -->
        <div class="glass-card p-6 rounded-3xl border border-slate-700/80 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Agitador Mecánico</span>
                <div class="w-10 h-10 rounded-2xl {{ $agitatorState ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 animate-spin' : 'bg-slate-800 text-slate-500' }} flex items-center justify-center text-lg">
                    <i class="fa-solid fa-compact-disc"></i>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <span class="heading-font text-4xl font-extrabold text-slate-100">
                    {{ $agitatorState ? ($latest?->agitator_rpm ?? $agitatorRpm) : 0 }}
                </span>
                <span class="text-xl font-bold text-emerald-400">RPM</span>
            </div>
            <span class="text-[10px] uppercase font-bold mt-2 inline-block px-2 py-0.5 rounded {{ $agitatorState ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400' }}">
                Status: {{ $agitatorState ? 'ACTIVADO' : 'APAGADO' }}
            </span>
        </div>

        <!-- Gauge 4: Heater SSR & Trace Sensor -->
        <div class="glass-card p-6 rounded-3xl border border-slate-700/80 shadow-xl relative overflow-hidden">
            <div class="flex items-center justify-between mb-4">
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Calefactor & Traza</span>
                <div class="w-10 h-10 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-lg">
                    <i class="fa-solid fa-fire-burner"></i>
                </div>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Calefactor SSR:</span>
                    <span class="font-bold px-2 py-0.5 rounded {{ $heaterSsrState ? 'bg-rose-500/20 text-rose-300 border border-rose-500/40' : 'bg-slate-800 text-slate-400' }}">
                        {{ $heaterSsrState ? 'ENCENDIDO' : 'OFF' }}
                    </span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-400">Estado de Traza:</span>
                    <span class="font-bold px-2 py-0.5 rounded {{ ($latest?->trace_detected) ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400' }}">
                        {{ ($latest?->trace_detected) ? 'DETECTADA' : 'MEZCLANDO' }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Saponification Calculator & Actuators Remote Control Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <!-- Saponification Automatic Formula Card (2 Cols) -->
        <div class="lg:col-span-2 glass-card p-6 sm:p-8 rounded-3xl border border-emerald-500/30 shadow-2xl relative">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <span class="text-xs font-bold text-emerald-400 uppercase tracking-wider">Algoritmo Químico Automático</span>
                    <h2 class="heading-font text-2xl font-bold text-slate-100 mt-1">Calculadora de Saponificación ($\text{NaOH}$ y Agua)</h2>
                </div>
                <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center text-emerald-400 text-2xl">
                    <i class="fa-solid fa-flask-vial"></i>
                </div>
            </div>

            <!-- Mathematical Formula Box per Spec -->
            <div class="mb-6 p-4 rounded-2xl bg-slate-950/80 border border-slate-800 text-xs font-mono space-y-2">
                <p class="text-emerald-400 font-bold text-sm">Fórmulas Químicas Aplicadas:</p>
                <div class="text-slate-300">
                    \(\text{NaOH (g)} = \text{Masa Aceite (g)} \times 0.134\)
                </div>
                <div class="text-slate-300">
                    \(\text{Agua (ml)} = \text{Masa Aceite (g)} \times 0.35\)
                </div>
            </div>

            <!-- Result Calculation Output Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="p-5 rounded-2xl bg-gradient-to-br from-emerald-950/40 to-slate-900 border border-emerald-500/40">
                    <span class="text-xs font-bold text-slate-400 uppercase">Hidróxido de Sodio (\(\text{NaOH}\)) Necesario</span>
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="heading-font text-3xl font-extrabold text-emerald-400">{{ number_format($naohGrams, 2) }}</span>
                        <span class="text-sm font-bold text-emerald-300">Gramos (g)</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Cálculo: {{ $currentWeight }}g × 0.134</p>
                </div>

                <div class="p-5 rounded-2xl bg-gradient-to-br from-teal-950/40 to-slate-900 border border-teal-500/40">
                    <span class="text-xs font-bold text-slate-400 uppercase">Agua Destilada Necesaria</span>
                    <div class="flex items-baseline gap-2 mt-2">
                        <span class="heading-font text-3xl font-extrabold text-teal-300">{{ number_format($waterMl, 2) }}</span>
                        <span class="text-sm font-bold text-teal-200">Mililitros (ml)</span>
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">Cálculo: {{ $currentWeight }}g × 0.35</p>
                </div>
            </div>
        </div>

        <!-- Remote Control Buttons Panel (1 Col) -->
        <div class="glass-card p-6 rounded-3xl border border-slate-700/80 shadow-2xl flex flex-col justify-between">
            <div>
                <h3 class="heading-font text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-gamepad text-amber-400"></i> Mandos Manuales Remotos
                </h3>

                <div class="space-y-4 text-xs">
                    <!-- Agitator Toggle -->
                    <button wire:click="toggleAgitator" {{ $emergencyStop ? 'disabled' : '' }}
                        class="w-full p-4 rounded-2xl font-bold flex items-center justify-between border transition transform active:scale-95 {{ $agitatorState ? 'bg-emerald-600/30 text-emerald-300 border-emerald-500 shadow-lg shadow-emerald-500/20' : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800' }}">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-arrows-spin text-base"></i> Motor Agitador Mecánico
                        </span>
                        <span class="font-extrabold px-2.5 py-1 rounded-lg {{ $agitatorState ? 'bg-emerald-500 text-slate-950' : 'bg-slate-800 text-slate-400' }}">
                            {{ $agitatorState ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <!-- Heater SSR Toggle -->
                    <button wire:click="toggleHeater" {{ $emergencyStop ? 'disabled' : '' }}
                        class="w-full p-4 rounded-2xl font-bold flex items-center justify-between border transition transform active:scale-95 {{ $heaterSsrState ? 'bg-rose-600/30 text-rose-300 border-rose-500 shadow-lg shadow-rose-500/20' : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800' }}">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-temperature-arrow-up text-base"></i> Calefactor SSR
                        </span>
                        <span class="font-extrabold px-2.5 py-1 rounded-lg {{ $heaterSsrState ? 'bg-rose-500 text-slate-950' : 'bg-slate-800 text-slate-400' }}">
                            {{ $heaterSsrState ? 'ON' : 'OFF' }}
                        </span>
                    </button>

                    <!-- Pump Toggle -->
                    <button wire:click="togglePump" {{ $emergencyStop ? 'disabled' : '' }}
                        class="w-full p-4 rounded-2xl font-bold flex items-center justify-between border transition transform active:scale-95 {{ $pumpState ? 'bg-cyan-600/30 text-cyan-300 border-cyan-500 shadow-lg shadow-cyan-500/20' : 'bg-slate-900 text-slate-300 border-slate-700 hover:bg-slate-800' }}">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-faucet-drip text-base"></i> Bomba de Dosificación
                        </span>
                        <span class="font-extrabold px-2.5 py-1 rounded-lg {{ $pumpState ? 'bg-cyan-500 text-slate-950' : 'bg-slate-800 text-slate-400' }}">
                            {{ $pumpState ? 'ON' : 'OFF' }}
                        </span>
                    </button>
                </div>
            </div>

            <!-- ESP32 Simulation Box -->
            <div class="mt-6 p-4 rounded-2xl bg-slate-950/60 border border-slate-800 text-xs">
                <span class="font-bold text-amber-400 block mb-2">Simulador de Telemetría ESP32</span>
                <div class="space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-slate-400">Masa Aceite (g):</label>
                        <input type="number" step="100" wire:model="simOilWeight" class="w-24 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-100 text-right">
                    </div>
                    <div class="flex items-center justify-between gap-2">
                        <label class="text-slate-400">Temp (°C):</label>
                        <input type="number" step="0.5" wire:model="simTemperature" class="w-24 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-slate-100 text-right">
                    </div>
                    <button wire:click="simulateEsp32Telemetry" class="w-full py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold rounded-xl transition">
                        Enviar Paquete ESP32
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Batches & Telemetry History Tables -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Active Batches -->
        <div class="glass-card p-6 rounded-3xl border border-slate-700/80 shadow-xl">
            <h3 class="heading-font text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-vials text-emerald-400"></i> Lotes de Jabón (`soap_batches`)
            </h3>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900 text-slate-400 uppercase text-[10px]">
                        <tr>
                            <th class="p-3">Código</th>
                            <th class="p-3">Aceite (L)</th>
                            <th class="p-3">NaOH (g)</th>
                            <th class="p-3">Estado</th>
                            <th class="p-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($soapBatches as $b)
                            <tr>
                                <td class="p-3 font-bold text-slate-100">{{ $b->batch_code }}</td>
                                <td class="p-3 text-amber-300 font-semibold">{{ $b->oil_used_liters }} L</td>
                                <td class="p-3 text-emerald-300 font-semibold">{{ $b->naoh_grams }} g</td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $b->status === 'completado' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                                        {{ strtoupper($b->status) }}
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    @if($b->status === 'en_proceso')
                                        <button wire:click="finishActiveBatch({{ $b->id }})" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold text-[10px]">
                                            Completar
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Telemetry Logs Stream -->
        <div class="glass-card p-6 rounded-3xl border border-slate-700/80 shadow-xl">
            <h3 class="heading-font text-lg font-bold text-slate-100 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-list-ol text-cyan-400"></i> Historial de Lecturas ESP32 (`machine_telemetry`)
            </h3>
            <div class="overflow-x-auto max-h-[300px] overflow-y-auto">
                <table class="w-full text-left text-xs text-slate-300">
                    <thead class="bg-slate-900 text-slate-400 uppercase text-[10px] sticky top-0">
                        <tr>
                            <th class="p-2.5">Hora</th>
                            <th class="p-2.5">Masa (g)</th>
                            <th class="p-2.5">Temp (°C)</th>
                            <th class="p-2.5">RPM</th>
                            <th class="p-2.5">Heater</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800">
                        @foreach($recentLogs as $log)
                            <tr>
                                <td class="p-2.5 font-mono text-[11px] text-slate-400">{{ $log->created_at->format('H:i:s') }}</td>
                                <td class="p-2.5 font-bold text-amber-300">{{ $log->oil_weight_grams }} g</td>
                                <td class="p-2.5 font-bold text-rose-400">{{ $log->temperature_celsius }} °C</td>
                                <td class="p-2.5 font-bold text-emerald-400">{{ $log->agitator_rpm }}</td>
                                <td class="p-2.5">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $log->heater_status ? 'bg-rose-500/20 text-rose-300' : 'bg-slate-800 text-slate-400' }}">
                                        {{ $log->heater_status ? 'ON' : 'OFF' }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
