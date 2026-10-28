<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Usuarios del sistema y tablas de soporte de autenticación.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 120);
            $table->string('email', 150)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Rol del sistema. En PostgreSQL se traduce a varchar(255) + CHECK.
            $table->enum('rol', ['administrador', 'operador', 'visualizador'])
                ->default('visualizador');

            // Baja lógica: un usuario inactivo no puede iniciar sesión, pero sus
            // recetas, lotes y logs siguen siendo trazables.
            $table->boolean('activo')->default(true);

            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('rol');
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        // El sistema exige UN ÚNICO administrador. Un índice único parcial lo
        // garantiza en la base de datos, no solo en la capa de aplicación.
        // Excluye las bajas lógicas para permitir reasignar el rol.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX users_unico_administrador
                 ON users (rol)
                 WHERE rol = 'administrador' AND deleted_at IS NULL"
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};
