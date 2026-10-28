<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoLote;
use App\Enums\EstadoPedido;
use App\Models\HistorialTemperatura;
use App\Models\LogAccion;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\Programacion;
use App\Models\Receta;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProyectoController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $registros = LogAccion::with('user')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%'.$buscar.'%';
                $query->where(fn ($q) => $q->where('accion', 'ilike', $like)
                    ->orWhere('componente', 'ilike', $like));
            })
            ->orderByDesc('fecha_hora')
            ->paginate(20)
            ->withQueryString();

        return view('proyecto.index', [
            'registros' => $registros,
            'buscar' => $buscar,
            'metricas' => [
                'recetas' => Receta::count(),
                'lotes' => Lote::count(),
                'lotes_completados' => Lote::where('estado', EstadoLote::Completado)->count(),
                'pedidos_abiertos' => Pedido::abiertos()->count(),
                'pedidos_pendientes' => Pedido::where('estado', EstadoPedido::Pendiente)->count(),
                'usuarios' => User::count(),
                'muestras_temperatura' => HistorialTemperatura::count(),
                'programaciones_activas' => Programacion::where('activa', true)->count(),
            ],
            'ultimosLotes' => Lote::with(['receta', 'user'])->orderByDesc('fecha_inicio')->limit(5)->get(),
        ]);
    }
}
