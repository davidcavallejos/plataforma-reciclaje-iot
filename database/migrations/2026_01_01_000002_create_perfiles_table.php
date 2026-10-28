<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perfiles de funcionamiento: fotografía completa de la máquina (PWM,
     * tiempos, automatización IR e intervalos de aceite) que se combina con
     * una receta al iniciar un lote.
     *
     * Contrato esperado de `configuraciones_json`:
     * {
     *   "pwm": {
     *     "filter1": 250, "water": 200, "oil": 250, "filter2": 250,
     *     "pump4": 250, "l298n1": 150, "motor3": 150
     *   },
     *   "automatizacion_ir": {
     *     "habilitado": true, "requiere_ir1": true, "requiere_ir2": true,
     *     "tiempo_agitacion_s": 30, "motor_pwm": 150
     *   },
     *   "intervalos_aceite": {
     *     "tiempo_on_s": 5, "tiempo_off_s": 10, "repeticiones": 6
     *   }
     * }
     */
    public function up(): void
    {
        Schema::create('perfiles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('nombre', 120);

            // Se usa jsonb (no json) para poder indexar y consultar el contenido
            // sin deserializar en PHP.
            $table->jsonb('configuraciones_json');

            // Perfil que se precarga en la pestaña de control.
            $table->boolean('es_predeterminado')->default(false);

            $table->timestamps();

            // Un usuario no puede tener dos perfiles con el mismo nombre.
            $table->unique(['user_id', 'nombre']);
            $table->index('es_predeterminado');
        });

        if (DB::getDriverName() === 'pgsql') {
            // Como máximo un perfil predeterminado por usuario.
            DB::statement(
                'CREATE UNIQUE INDEX perfiles_un_predeterminado_por_usuario
                 ON perfiles (user_id)
                 WHERE es_predeterminado'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('perfiles');
    }
};
