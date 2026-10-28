<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccesoTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_invitado_es_redirigido_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_la_pantalla_de_login_responde(): void
    {
        $this->get('/login')->assertOk()->assertSee('Iniciar sesión');
    }

    public function test_el_registro_publico_crea_el_administrador_unico(): void
    {
        $respuesta = $this->post('/registro', [
            'nombre' => 'Admin Inicial',
            'email' => 'primer.admin@test.bo',
            'password' => 'claveSegura123',
            'password_confirmation' => 'claveSegura123',
        ]);

        $respuesta->assertRedirect('/');

        $usuario = User::where('email', 'primer.admin@test.bo')->first();

        $this->assertNotNull($usuario);
        $this->assertSame(Rol::Administrador, $usuario->rol);
        $this->assertAuthenticatedAs($usuario);
    }

    public function test_el_visualizador_no_accede_al_control(): void
    {
        $visualizador = User::factory()->visualizador()->create();

        $this->actingAs($visualizador)
            ->get('/maquina')
            ->assertForbidden();

        $this->actingAs($visualizador)
            ->get('/proyecto')
            ->assertOk();
    }

    public function test_el_operador_controla_la_maquina_pero_no_gestiona_usuarios(): void
    {
        $operador = User::factory()->operador()->create();

        $this->actingAs($operador)->get('/maquina')->assertOk();
        $this->actingAs($operador)->get('/usuarios')->assertForbidden();
    }

    public function test_solo_puede_existir_un_administrador(): void
    {
        // El índice único parcial solo existe en PostgreSQL; SQLite (usado por
        // la suite por defecto) no lo aplica.
        if (\Illuminate\Support\Facades\DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('El administrador único se garantiza con un índice parcial de PostgreSQL.');
        }

        User::factory()->administrador()->create();
        $segundo = User::factory()->administrador()->make();

        $this->expectException(\Illuminate\Database\QueryException::class);

        $segundo->save();
    }

    public function test_el_perfil_completa_los_pwm_por_defecto(): void
    {
        $admin = User::factory()->administrador()->create();

        $perfil = Perfil::create([
            'user_id' => $admin->id,
            'nombre' => 'Perfil de prueba',
            'configuraciones_json' => ['pwm' => ['water' => 201]],
            'es_predeterminado' => true,
        ]);

        $this->assertSame(201, $perfil->pwm()['water']);
        $this->assertSame(250, $perfil->pwm()['filter1']);
    }

    public function test_las_pestanas_laterales_se_muestran_con_categorias_y_adaptabilidad(): void
    {
        $admin = User::factory()->administrador()->create();

        $respuesta = $this->actingAs($admin)->get('/');

        $respuesta->assertOk()
            ->assertSee('barra-lateral')
            ->assertSee('Principal')
            ->assertSee('Producción y Control')
            ->assertSee('Informes y Registros')
            ->assertSee('Administración y Sistema')
            ->assertSee('Control de la máquina')
            ->assertSee('Formulación y recetas')
            ->assertSee('Gestión de usuarios')
            ->assertSee('boton-menu-movil');
    }
}
