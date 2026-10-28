<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\Perfil;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $admin = User::factory()->administrador()->create([
            'nombre' => 'Administrador',
            'email' => 'admin@jabon.local',
            'password' => Hash::make('admin1234'),
        ]);

        // Perfil de arranque con los PWM definidos en los requisitos.
        Perfil::create([
            'user_id' => $admin->id,
            'nombre' => 'Producción estándar',
            'es_predeterminado' => true,
            'configuraciones_json' => [
                'pwm' => Perfil::PWM_POR_DEFECTO,
                'automatizacion_ir' => [
                    'habilitado' => true,
                    'requiere_ir1' => true,
                    'requiere_ir2' => true,
                    'tiempo_agitacion_s' => 30,
                    'motor_pwm' => Perfil::PWM_POR_DEFECTO['l298n1'],
                ],
                'intervalos_aceite' => [
                    'tiempo_on_s' => 5,
                    'tiempo_off_s' => 10,
                    'repeticiones' => 6,
                ],
            ],
        ]);

        $this->command?->info("Administrador creado: {$admin->email} (rol {$admin->rol->value})");
    }
}
