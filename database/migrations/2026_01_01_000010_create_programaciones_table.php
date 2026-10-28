<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Programaciones de comandos: temporizadores ("encender 15 s") y
     * programación por hora ("a las 08:30 abrir el filtro 1").
     *
     * Tabla añadida al diseño original: los requisitos piden ambas funciones
     * y no hay dónde persistirlas dentro de las diez tablas base.
     */
    public function up(): void
    {
        Schema::create('programaciones', function (Blueprint $table) {
            $table->id();

            // Quién la creó. Nullable si la crea la automatización.
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Componente físico: WATER, OIL, FILTER1, FILTER2, MOTOR3, PUMP4, MOTORES.
            $table->string('componente', 60);

            // Comando exacto que se enviará al ESP32.
            $table->string('comando', 60);

            // Retardo: instante exacto de ejecución (una sola vez).
            $table->timestampTz('ejecutar_en')->nullable();

            // Hora del día para repetición diaria (HH:MM:SS).
            $table->time('hora')->nullable();

            $table->boolean('activa')->default(true);

            $table->timestampTz('ultima_ejecucion')->nullable();

            $table->timestamps();

            $table->index(['activa', 'ejecutar_en']);
            $table->index(['activa', 'hora']);
            $table->index('user_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE programaciones ADD CONSTRAINT programaciones_momento_requerido
                 CHECK (ejecutar_en IS NOT NULL OR hora IS NOT NULL)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('programaciones');
    }
};
