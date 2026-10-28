<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\EstadoLote;
use App\Enums\EstadoPedido;
use App\Models\HistorialTemperatura;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Asistente del proyecto basado en intenciones y consultas a la base de datos.
 *
 * No depende de servicios externos: funciona en la red local del taller y
 * responde con datos reales del sistema.
 */
class ChatbotService
{
    /** @var list<string> */
    public const SUGERENCIAS = [
        '¿Cuántos pedidos pendientes hay?',
        '¿Cuál fue el último lote?',
        '¿Cuántas recetas tengo registradas?',
        '¿Cuál es la temperatura actual?',
        '¿Qué puede hacer cada rol?',
        '¿Qué es este proyecto?',
    ];

    /**
     * @return array{respuesta: string, sugerencias: list<string>}
     */
    public function responder(string $pregunta, ?User $user = null): array
    {
        $texto = $this->normalizar($pregunta);
        $respuesta = $this->resolver($texto, $user);

        return [
            'respuesta' => $respuesta,
            'sugerencias' => self::SUGERENCIAS,
        ];
    }

    private function normalizar(string $texto): string
    {
        return Str::lower(Str::ascii(trim($texto)));
    }

    private function resolver(string $t, ?User $user): string
    {
        if ($t === '') {
            return 'Escribe una pregunta o usa una de las sugerencias.';
        }

        if ($this->contiene($t, ['hola', 'buenas', 'saludos', 'hey'])) {
            $nombre = $user?->nombre ?? 'operador';

            return "Hola {$nombre}. Puedo informarte sobre pedidos, lotes, recetas y temperatura de la máquina.";
        }

        if ($this->contiene($t, ['que es este proyecto', 'de que trata', 'para que sirve', 'que es el proyecto'])) {
            return 'Es el panel de control local de una máquina automatizada de fabricación de jabón. '
                .'La máquina la gobierna un ESP32 con cuatro módulos L298N, sensores infrarrojos y un sensor de temperatura DS18B20. '
                .'El panel registra recetas, lotes, pedidos y una auditoría de cada comando.';
        }

        if ($this->contiene($t, ['rol', 'roles', 'permiso', 'quien puede'])) {
            return 'Hay tres roles: el administrador (único) controla la máquina, gestiona usuarios y ve reportes; '
                .'el operador controla la máquina y ve reportes, pero no gestiona usuarios; '
                .'el visualizador solo accede al Inicio y a Proyecto/Registros.';
        }

        if ($this->contiene($t, ['desarrollador', 'equipo', 'quien lo hizo', 'autor', 'catorceno', 'bernabe'])) {
            return 'El proyecto lo desarrollaron Bernabé Cruz Roger Darío y Catorceno Vallejos David, '
                .'encargado este último del desarrollo de software y hardware, en el Instituto Técnico Nacional de Comercio '
                .'"Federico Álvarez Plata" (Nocturno).';
        }

        if ($this->contiene($t, ['temperatura', 'grados', 'caliente', 'frio', 'ds18b20'])) {
            $ultima = HistorialTemperatura::orderByDesc('fecha_hora')->first();

            return $ultima === null
                ? 'Todavía no hay lecturas de temperatura registradas. Abre la pestaña de control con la máquina encendida.'
                : 'La última temperatura registrada es de '.number_format((float) $ultima->temperatura, 2).' °C, '
                    .'tomada el '.$ultima->fecha_hora->format('d/m/Y H:i:s').'.';
        }

        if ($this->contiene($t, ['pendiente', 'pedidos pendientes', 'pedidos'])) {
            $pendientes = Pedido::pendientes()->count();
            $abiertos = Pedido::abiertos()->count();
            $proximos = Pedido::abiertos()
                ->whereNotNull('fecha_entrega')
                ->orderBy('fecha_entrega')
                ->limit(3)
                ->get();

            $mensaje = "Hay {$pendientes} pedido(s) pendiente(s) y {$abiertos} abierto(s) en total.";

            if ($proximos->isNotEmpty()) {
                $lista = $proximos
                    ->map(fn (Pedido $p) => $p->cliente.' ('.$p->fecha_entrega->format('d/m/Y').')')
                    ->implode(', ');
                $mensaje .= " Los próximos vencimientos: {$lista}.";
            }

            return $mensaje;
        }

        if ($this->contiene($t, ['lote', 'lotes', 'produccion', 'ultimo lote'])) {
            $ultimo = Lote::with('receta')->orderByDesc('fecha_inicio')->first();

            if ($ultimo === null) {
                return 'Aún no se registró ningún lote de producción.';
            }

            $completados = Lote::where('estado', EstadoLote::Completado)->count();

            return "El último lote es {$ultimo->codigo} ({$ultimo->receta?->nombre}), iniciado el "
                .$ultimo->fecha_inicio->format('d/m/Y H:i').' en estado '.$ultimo->estado->etiqueta()
                .". En total hay {$completados} lote(s) completado(s).";
        }

        if ($this->contiene($t, ['receta', 'recetas', 'formula', 'formulacion'])) {
            $total = Receta::count();
            $ultimas = Receta::orderByDesc('created_at')->limit(3)->pluck('nombre')->implode(', ');

            return $total === 0
                ? 'Todavía no hay recetas registradas. Puedes crearlas en la pestaña de Formulación.'
                : "Hay {$total} receta(s) registrada(s). Las más recientes: {$ultimas}.";
        }

        if ($this->contiene($t, ['consumo', 'cuanto se produjo', 'kilos', 'peso producido'])) {
            $total = Lote::where('estado', EstadoLote::Completado)->sum('peso_producido');

            return 'La producción acumulada de los lotes completados es de '.number_format((float) $total, 2).' kg.';
        }

        if ($this->contiene($t, ['comando', 'comandos', 'encender', 'apagar', 'controlar'])) {
            return 'Desde la pestaña de Control puedes encender y apagar los motores, las bombas y los filtros, '
                .'ajustar el PWM, programar temporizadores y configurar la automatización por sensores IR. '
                .'Cada comando queda registrado en la auditoría.';
        }

        if ($this->contiene($t, ['gracias', 'adios', 'hasta luego'])) {
            return 'Con gusto. Si necesitas otra consulta, aquí estoy.';
        }

        return 'No encontré una respuesta para eso. Prueba con: pedidos pendientes, último lote, recetas, '
            .'temperatura actual, roles del sistema o información del proyecto.';
    }

    /**
     * @param  list<string>  $claves
     */
    private function contiene(string $texto, array $claves): bool
    {
        foreach ($claves as $clave) {
            if (str_contains($texto, $clave)) {
                return true;
            }
        }

        return false;
    }
}
