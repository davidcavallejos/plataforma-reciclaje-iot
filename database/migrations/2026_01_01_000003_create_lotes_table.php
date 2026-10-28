<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lotes de producción reales.
     *
     * Un lote nace de una receta y, opcionalmente, de un perfil de máquina.
     * `configuracion_aplicada` guarda la configuración efectiva del perfil en
     * el momento de la producción: los perfiles son editables, pero el
     * histórico de un lote debe permanecer inmutable para los reportes.
     */
    public function up(): void
    {
        Schema::create('lotes', function (Blueprint $table) {
            $table->id();

            // Código legible para el operario: LOTE-2026-0001.
            $table->string('codigo', 30)->unique();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('receta_id')
                ->constrained('recetas')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // Un lote puede ejecutarse sin perfil guardado (control manual).
            // Si el perfil se elimina, el lote conserva su configuración aplicada.
            $table->foreignId('perfil_id')
                ->nullable()
                ->constrained('perfiles')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->jsonb('configuracion_aplicada')->nullable();

            $table->timestampTz('fecha_inicio');
            $table->timestampTz('fecha_fin')->nullable();

            $table->enum('estado', ['pendiente', 'en_proceso', 'completado', 'cancelado'])
                ->default('pendiente');

            // Producción efectiva, que puede diferir de lo planificado en la receta.
            $table->decimal('peso_producido', 10, 2)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('receta_id');
            $table->index(['estado', 'fecha_inicio']);
            $table->index('fecha_inicio');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE lotes ADD CONSTRAINT lotes_fechas_coherentes
                 CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)'
            );
            DB::statement(
                'ALTER TABLE lotes ADD CONSTRAINT lotes_peso_producido_positivo
                 CHECK (peso_producido IS NULL OR peso_producido > 0)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('lotes');
    }
};
