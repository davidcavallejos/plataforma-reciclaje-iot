<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Registro de los informes PDF/Excel generados.
     *
     * Guarda quién generó qué y dónde quedó el archivo, para poder auditar las
     * descargas y permitir re-descargas sin regenerar el documento.
     */
    public function up(): void
    {
        Schema::create('reportes_generados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            // lotes | temperaturas | auditoria | consumo
            $table->string('tipo', 40);

            // Ruta relativa dentro de storage/app/reportes.
            $table->string('ruta_archivo', 255);

            $table->timestampTz('fecha')->useCurrent();

            $table->index(['user_id', 'fecha']);
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_generados');
    }
};
