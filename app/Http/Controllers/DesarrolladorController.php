<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class DesarrolladorController extends Controller
{
    public function index(): View
    {
        return view('desarrolladores', [
            'equipo' => [
                [
                    'nombre' => 'Bernabé Cruz Roger Darío',
                    'rol' => 'Integrante del equipo',
                    'detalle' => null,
                ],
                [
                    'nombre' => 'Catorceno Vallejos David',
                    'rol' => 'Desarrollo de software y hardware',
                    'detalle' => 'Diseño y programación de la aplicación Laravel, del firmware del ESP32 '
                        .'y de la integración entre el panel y los módulos L298N.',
                ],
            ],
            'tecnologias' => [
                ['nombre' => 'Laravel 12', 'uso' => 'Backend, rutas, autenticación y Eloquent.'],
                ['nombre' => 'PostgreSQL 17', 'uso' => 'Base de datos relacional con integridad referencial.'],
                ['nombre' => 'Blade + JavaScript', 'uso' => 'Interfaz y actualizaciones en vivo sin recargar.'],
                ['nombre' => 'ESP32 + Arduino', 'uso' => 'Control de motores, bombas y sensores.'],
                ['nombre' => 'L298N × 4', 'uso' => 'Puentes H para motoreductores y bombas.'],
                ['nombre' => 'DS18B20 / IR', 'uso' => 'Temperatura y detección de molde lleno.'],
            ],
        ]);
    }
}
