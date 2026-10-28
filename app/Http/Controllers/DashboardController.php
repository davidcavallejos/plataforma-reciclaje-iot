<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoLote;
use App\Enums\EstadoPedido;
use App\Models\ChatbotHistorial;
use App\Models\HistorialTemperatura;
use App\Models\LogAccion;
use App\Models\Lote;
use App\Models\Noticia;
use App\Models\Pedido;
use App\Models\Receta;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $usuario = $request->user();
        $esInvitado = $usuario === null;

        return view('dashboard', [
            'esInvitado' => $esInvitado,
            'noticias' => Noticia::query()
                ->select(['id', 'titulo', 'descripcion', 'imagen_path', 'created_at'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get(),
            'temperatura' => HistorialTemperatura::query()
                ->select(['temperatura', 'fecha_hora'])
                ->orderByDesc('fecha_hora')
                ->first(),
            'pedidosPendientes' => Pedido::abiertos()->count(),
            'ultimoLote' => Lote::query()
                ->with('receta:id,nombre')
                ->select(['id', 'codigo', 'receta_id', 'estado', 'fecha_inicio'])
                ->orderByDesc('fecha_inicio')
                ->first(),
            'totalRecetas' => Receta::count(),
            'lotesCompletados' => Lote::where('estado', EstadoLote::Completado)->count(),
            'actividad' => $esInvitado
                ? collect()
                : LogAccion::query()
                    ->with('user:id,nombre')
                    ->select(['id', 'user_id', 'accion', 'componente', 'fecha_hora'])
                    ->orderByDesc('fecha_hora')
                    ->limit(6)
                    ->get(),
            'conversacion' => ($esInvitado || $usuario === null)
                ? collect()
                : ChatbotHistorial::query()
                    ->where('user_id', $usuario->id)
                    ->orderByDesc('fecha_hora')
                    ->limit(8)
                    ->get()
                    ->reverse()
                    ->values(),
            'sugerencias' => $esInvitado ? [] : \App\Services\ChatbotService::SUGERENCIAS,
            'pedidosRecientes' => Pedido::query()
                ->where('estado', EstadoPedido::Pendiente)
                ->orderBy('fecha_entrega')
                ->limit(4)
                ->get(['id', 'cliente', 'cantidad_peso', 'cantidad_unidades', 'fecha_entrega', 'estado']),
        ]);
    }

    public function buscar(Request $request): JsonResponse
    {
        $datos = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $termino = trim($datos['q'] ?? '');

        if (mb_strlen($termino) < 2) {
            return response()->json(['termino' => $termino, 'grupos' => []]);
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $termino).'%';
        $grupos = [];

        $recetas = Receta::where('nombre', 'ilike', $like)
            ->limit(5)
            ->get(['id', 'nombre', 'tipo_producto', 'peso'])
            ->map(fn (Receta $r) => [
                'titulo' => $r->nombre,
                'detalle' => $r->tipo_producto->etiqueta().' · '.number_format((float) $r->peso, 2).' g',
                'url' => $this->enlace('recetas.index', ['buscar' => $r->nombre]),
            ]);
        if ($recetas->isNotEmpty()) {
            $grupos[] = ['tipo' => 'Recetas', 'items' => $recetas];
        }

        $lotes = Lote::with('receta:id,nombre')->where('codigo', 'ilike', $like)
            ->limit(5)
            ->get()
            ->map(fn (Lote $l) => [
                'titulo' => $l->codigo,
                'detalle' => ($l->receta?->nombre ?? 'Sin receta').' · '.$l->estado->etiqueta(),
                'url' => $this->enlace('lotes.index', ['buscar' => $l->codigo]),
            ]);
        if ($lotes->isNotEmpty()) {
            $grupos[] = ['tipo' => 'Lotes', 'items' => $lotes];
        }

        $pedidos = Pedido::where('cliente', 'ilike', $like)
            ->limit(5)
            ->get()
            ->map(fn (Pedido $p) => [
                'titulo' => $p->cliente,
                'detalle' => $p->descripcionCantidad().' · '.$p->estado->etiqueta(),
                'url' => $this->enlace('lotes.index', ['buscar' => $p->cliente]),
            ]);
        if ($pedidos->isNotEmpty()) {
            $grupos[] = ['tipo' => 'Pedidos', 'items' => $pedidos];
        }

        return response()->json(['termino' => $termino, 'grupos' => $grupos]);
    }

    /**
     * @param  array<string, string>  $parametros
     */
    private function enlace(string $ruta, array $parametros): string
    {
        return Route::has($ruta)
            ? route($ruta, $parametros)
            : route('proyecto.index');
    }
}
