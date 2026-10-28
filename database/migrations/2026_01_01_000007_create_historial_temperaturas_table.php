<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Serie temporal de temperatura del DS18B20.
     *
     * Es una tabla de alta escritura (una muestra cada pocos segundos), por eso
     * solo lleva los índices necesarios para los gráficos y reportes.
     */
    public function up(): void
    {
        Schema::create('historial_temperaturas', function (Blueprint $table) {
            $table->id();

            // Rango del DS18B20: -55 °C .. +125 °C; decimal(5,2) lo cubre.
            $table->decimal('temperatura', 5, 2);

            $table->timestampTz('fecha_hora')->useCurrent();

            $table->index('fecha_hora');
        });

        if (DB::getDriverName() === 'pgsql') {
            // Descarta lecturas corruptas del sensor (DEVICE_DISCONNECTED_C = -127).
            DB::statement(
                'ALTER TABLE historial_temperaturas ADD CONSTRAINT temperaturas_fuera_de_rango
                 CHECK (temperatura BETWEEN -55 AND 125)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_temperaturas');
    }
};
