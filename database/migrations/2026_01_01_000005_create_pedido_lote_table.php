<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relación muchos a muchos entre pedidos y lotes: un pedido puede
     * consumir varios lotes y un lote puede cubrir varios pedidos.
     *
     * Al borrar un pedido o un lote, la asignación pierde sentido y se elimina
     * en cascada; el histórico de producción vive en `lotes`.
     */
    public function up(): void
    {
        Schema::create('pedido_lote', function (Blueprint $table) {
            $table->id();

            $table->foreignId('pedido_id')
                ->constrained('pedidos')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('lote_id')
                ->constrained('lotes')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();

            // Evita asignar el mismo lote dos veces al mismo pedido.
            $table->unique(['pedido_id', 'lote_id']);
            $table->index('lote_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pedido_lote');
    }
};
