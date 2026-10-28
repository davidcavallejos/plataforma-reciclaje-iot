<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\OrigenAccion;
use App\Models\HistorialTemperatura;
use App\Models\LogAccion;
use App\Models\Perfil;
use App\Models\Programacion;
use App\Services\Esp32Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaquinaController extends Controller
{
    /** Componentes que se pueden programar. */
    public function __construct(private readonly Esp32Service $esp32) {}

    public function index(Request $request): View
    {
        $perfilActivo = $this->perfilActivo($request);

        return view('maquina.index', [
            'perfiles' => Perfil::query()
                ->orderByDesc('es_predeterminado')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'es_predeterminado', 'configuraciones_json']),
            'perfilActivo' => $perfilActivo,
            'pwm' => $perfilActivo?->pwm() ?? Perfil::PWM_POR_DEFECTO,
            'pasosPerfil' => $perfilActivo?->pasos() ?? [],
            'programaciones' => Programacion::with('user:id,nombre')
                ->where('activa', true)
                ->orderBy('id')
                ->get(),
            'componentes' => Esp32Service::COMPONENTES,
            'pedidosAbiertos' => \App\Models\Pedido::abiertos()
                ->orderByRaw("case when estado = 'pendiente' then 0 else 1 end")
                ->orderBy('fecha_entrega')
                ->limit(8)
                ->get(['id', 'cliente', 'cantidad_peso', 'cantidad_unidades', 'fecha_entrega', 'estado']),
        ]);
    }

    /**
     * Estado en vivo: lo consulta el JavaScript cada 2 segundos.
     */
    public function estado(): JsonResponse
    {
        $estado = $this->esp32->estado();
        $this->registrarTemperatura($estado);

        return response()->json([
            'esp32' => $estado,
            'servidor' => now()->toIso8601String(),
            'programaciones' => Programacion::where('activa', true)->count(),
        ]);
    }

    public function comando(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'comando' => ['required', 'string', 'max:60'],
        ]);

        $comando = $this->esp32->normalizar($datos['comando']);

        if (! $this->esp32->comandoValido($comando)) {
            return response()->json([
                'ok' => false,
                'mensaje' => "Comando no permitido: {$comando}",
            ], 422);
        }

        $resultado = $this->esp32->enviar($comando);

        $this->registrarLog($request, $comando, $this->componenteDe($comando));

        return response()->json($resultado + ['comando' => $comando]);
    }

    /**
     * Activa un perfil y envía a la placa todos sus PWM explícitamente.
     */
    public function aplicarPerfil(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'perfil_id' => ['required', 'integer', 'exists:perfiles,id'],
        ]);

        $perfil = Perfil::findOrFail($datos['perfil_id']);
        $request->session()->put('perfil_activo_id', $perfil->id);

        $pwm = $perfil->pwm();
        $mapa = [
            'filter1' => 'FILTER1',
            'water' => 'WATER',
            'oil' => 'OIL',
            'filter2' => 'FILTER2',
            'pump4' => 'PUMP4',
            'l298n1' => 'V',
            'motor3' => 'MOTOR3 SPEED',
        ];

        $enviados = [];
        foreach ($mapa as $clave => $prefijo) {
            $valor = (int) $pwm[$clave];
            // Los comandos de bomba/filtro encienden al fijar el PWM; los de
            // motor solo cambian la velocidad si ya está en marcha.
            $comando = "{$prefijo} {$valor}";

            if ($this->esp32->comandoValido($comando)) {
                $resultado = $this->esp32->enviar($comando);
                $enviados[] = ['comando' => $comando, 'ok' => $resultado['ok']];
                $this->registrarLog($request, $comando, $this->componenteDe($comando));
            }
        }

        return response()->json([
            'ok' => true,
            'perfil' => $perfil->nombre,
            'enviados' => $enviados,
            'pasos' => $perfil->pasos(),
            'pwm' => $pwm,
        ]);
    }

    public function programar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'comando' => ['required', 'string', 'max:60'],
            'componente' => ['required', 'string', Rule::in(array_keys(Esp32Service::COMPONENTES))],
            'tipo' => ['required', Rule::in(['retardo', 'diaria'])],
            'minutos' => ['nullable', 'numeric', 'min:0.1', 'max:1440'],
            'segundos' => ['nullable', 'integer', 'min:1', 'max:86400'],
            'hora' => ['nullable', 'date_format:H:i'],
        ]);

        $segundos = (int) ($datos['segundos'] ?? 0);
        if (! empty($datos['minutos'])) {
            $segundos = (int) round((float) $datos['minutos'] * 60);
        }

        if ($datos['tipo'] === 'retardo' && $segundos < 1) {
            return response()->json(['ok' => false, 'mensaje' => 'Indica los minutos del temporizador.'], 422);
        }

        if ($datos['tipo'] === 'diaria' && empty($datos['hora'])) {
            return response()->json(['ok' => false, 'mensaje' => 'Indica la hora de la programación.'], 422);
        }

        $comando = $this->esp32->normalizar($datos['comando']);

        if (! $this->esp32->comandoValido($comando)) {
            return response()->json(['ok' => false, 'mensaje' => "Comando no permitido: {$comando}"], 422);
        }

        $programacion = Programacion::create([
            'user_id' => $request->user()->id,
            'componente' => $datos['componente'],
            'comando' => $comando,
            'ejecutar_en' => $datos['tipo'] === 'retardo'
                ? now()->addSeconds($segundos)
                : null,
            'hora' => $datos['tipo'] === 'diaria' ? $datos['hora'].':00' : null,
            'activa' => true,
        ]);

        return response()->json([
            'ok' => true,
            'programacion' => [
                'id' => $programacion->id,
                'comando' => $programacion->comando,
                'descripcion' => $programacion->descripcion(),
            ],
        ]);
    }

    public function cancelar(Programacion $programacion): JsonResponse
    {
        $programacion->update(['activa' => false]);

        return response()->json(['ok' => true, 'id' => $programacion->id]);
    }

    /**
     * Ejecuta las programaciones vencidas. Lo invoca el sondeo del panel, de
     * modo que la máquina obedece aunque nadie pulse un botón.
     */
    public function procesar(): JsonResponse
    {
        $ejecutadas = [];

        foreach (Programacion::where('activa', true)->get() as $programacion) {
            if (! $programacion->estaVencida()) {
                continue;
            }

            $resultado = $this->esp32->enviar($programacion->comando);

            $programacion->update([
                'ultima_ejecucion' => now(),
                // Las programaciones diarias siguen activas; las de una sola vez se cierran.
                'activa' => $programacion->esDiaria(),
            ]);

            LogAccion::create([
                'user_id' => $programacion->user_id,
                'accion' => $programacion->comando,
                'componente' => $programacion->componente,
                'origen' => OrigenAccion::Automatizacion,
                'fecha_hora' => now(),
            ]);

            $ejecutadas[] = [
                'id' => $programacion->id,
                'comando' => $programacion->comando,
                'ok' => $resultado['ok'],
            ];
        }

        return response()->json(['ejecutadas' => $ejecutadas]);
    }

    private function perfilActivo(Request $request): ?Perfil
    {
        $id = $request->session()->get('perfil_activo_id');

        if ($id !== null) {
            $perfil = Perfil::find($id);
            if ($perfil !== null) {
                return $perfil;
            }
        }

        return Perfil::where('es_predeterminado', true)->first();
    }

    /**
     * Guarda una muestra cada 30 s para no llenar la tabla de temperatura con
     * una lectura por cada sondeo del navegador.
     *
     * @param  array<string, mixed>  $estado
     */
    private function registrarTemperatura(array $estado): void
    {
        $valor = $estado['temperatura'] ?? null;

        if (! is_float($valor) && ! is_int($valor)) {
            return;
        }

        if ($valor <= -50 || $valor > 125) {
            return;
        }

        $ultima = HistorialTemperatura::orderByDesc('fecha_hora')->first();

        if ($ultima !== null && $ultima->fecha_hora->gt(now()->subSeconds(30))) {
            return;
        }

        HistorialTemperatura::create([
            'temperatura' => $valor,
            'fecha_hora' => now(),
        ]);
    }

    private function registrarLog(Request $request, string $comando, string $componente): void
    {
        LogAccion::create([
            'user_id' => $request->user()?->id,
            'accion' => $comando,
            'componente' => $componente,
            'origen' => OrigenAccion::Web,
            'fecha_hora' => now(),
        ]);
    }

    private function componenteDe(string $comando): string
    {
        if (str_starts_with($comando, 'MOTOR3')) {
            return 'MOTOR3';
        }

        if (str_starts_with($comando, 'V ')) {
            return 'MOTORES';
        }

        $primera = explode(' ', $comando)[0];

        return match ($primera) {
            'START', 'STOP', 'HOUR' => 'MOTORES',
            'ALLSTOP', 'FSTOP', 'PSTOP' => 'SISTEMA',
            'SENSORS', 'TEMP', 'IR1', 'IR2', 'STATUS', 'HELP' => 'SENSORES',
            default => $primera,
        };
    }
}
