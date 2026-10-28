<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pedidos de clientes.
     *
     * La cantidad puede expresarse en peso (kg) o en unidades de barra; el
     * CHECK correspondiente exige que al menos una de las dos exista.
     */
    public function up(): void
    {
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();

            // Usuario que registró el pedido.
            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('cliente', 150);

            $table->decimal('cantidad_peso', 10, 2)->nullable();
            $table->unsignedInteger('cantidad_unidades')->nullable();

            $table->enum('estado', ['pendiente', 'en_proceso', 'completado', 'cancelado'])
                ->default('pendiente');

            $table->date('fecha_entrega')->nullable();

            $table->timestamps();

            $table->index(['estado', 'fecha_entrega']);
            $table->index('fecha_entrega');
            $table->index(['user_id', 'created_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE pedidos ADD CONSTRAINT pedidos_cantidad_requerida
                 CHECK (cantidad_peso IS NOT NULL OR cantidad_unidades IS NOT NULL)'
            );
            DB::statement(
                'ALTER TABLE pedidos ADD CONSTRAINT pedidos_cantidades_positivas
                 CHECK ((cantidad_peso IS NULL OR cantidad_peso > 0)
                     AND (cantidad_unidades IS NULL OR cantidad_unidades > 0))'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
