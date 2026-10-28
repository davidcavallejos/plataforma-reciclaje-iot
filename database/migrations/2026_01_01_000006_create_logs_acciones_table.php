<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoría de acciones sobre la máquina.
     *
     * `user_id` es nullable porque no toda acción tiene un humano detrás: la
     * automatización IR del ESP32 actúa sola cuando detecta el molde lleno.
     * `origen` distingue esas acciones de las pulsadas desde la web.
     */
    public function up(): void
    {
        Schema::create('logs_acciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Ej: "FILTER1 ON", "ALLSTOP", "MOTOR3 SPEED 150".
            $table->string('accion', 120);

            // Componente físico: FILTER1, WATER, OIL, MOTOR3, L298N1, IR1...
            $table->string('componente', 60);

            $table->enum('origen', ['web', 'esp32', 'automatizacion'])->default('web');

            $table->timestampTz('fecha_hora')->useCurrent();

            // Feed de actividades y filtros por componente en el rango de fechas.
            $table->index(['componente', 'fecha_hora']);
            $table->index('fecha_hora');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs_acciones');
    }
};
