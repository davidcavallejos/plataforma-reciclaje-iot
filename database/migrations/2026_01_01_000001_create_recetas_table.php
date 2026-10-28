<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Recetas de formulación.
     *
     * El usuario elige un ingrediente base (aceite, agua o sosa) y su peso; la
     * aplicación calcula el resto de forma proporcional. El resultado del
     * cálculo se guarda en `calculos` para que la receta sea reproducible
     * aunque cambien las fórmulas en el futuro.
     */
    public function up(): void
    {
        Schema::create('recetas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('nombre', 150);

            // Líquido o barra; condiciona aditivos y tiempos de curado.
            $table->enum('tipo_producto', ['liquido', 'barra']);

            // Ingrediente del que parte el cálculo proporcional.
            $table->enum('ingrediente_base', ['aceite', 'agua', 'sosa']);

            // Gramos del ingrediente base. numeric(10,2) evita el error de
            // redondeo de los tipos flotantes.
            $table->decimal('peso', 10, 2);

            // oliva, coco, girasol, reciclado, etc. Nullable cuando la base no es aceite.
            $table->string('tipo_aceite', 80)->nullable();

            // {"aceite": 1000, "agua": 380, "sosa": 140, "sobreengrasado": 5}
            $table->jsonb('calculos');

            // [{"nombre": "aceite esencial", "gramos": 15}, ...]
            $table->jsonb('aditivos')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index('ingrediente_base');
            $table->index('tipo_producto');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE recetas ADD CONSTRAINT recetas_peso_positivo CHECK (peso > 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('recetas');
    }
};
