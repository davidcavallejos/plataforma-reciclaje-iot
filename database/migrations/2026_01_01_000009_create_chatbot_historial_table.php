<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de conversaciones con el chatbot del proyecto.
     *
     * `user_id` es nullable para permitir consultas antes de iniciar sesión;
     * las respuestas se guardan para poder citarlas y mejorar el asistente.
     */
    public function up(): void
    {
        Schema::create('chatbot_historial', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->text('pregunta');
            $table->text('respuesta');

            $table->timestampTz('fecha_hora')->useCurrent();

            $table->index(['user_id', 'fecha_hora']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chatbot_historial');
    }
};
