<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoPedido;
use App\Models\Pedido;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PedidosController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'cliente' => ['required', 'string', 'min:2', 'max:150'],
            'cantidad_peso' => ['nullable', 'numeric', 'min:0.01', 'max:999999'],
            'cantidad_unidades' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'estado' => ['required', 'in:pendiente,en_proceso,completado,cancelado'],
            'fecha_entrega' => ['nullable', 'date'],
        ], [
            'cliente.required' => 'Indica el nombre del cliente.',
        ]);

        if (empty($datos['cantidad_peso']) && empty($datos['cantidad_unidades'])) {
            return redirect()->route('lotes.index')->withErrors([
                'cantidad_peso' => 'Indica la cantidad en peso o en unidades (al menos una).',
            ])->withInput();
        }

        $pedido = Pedido::create([
            'user_id' => $request->user()->id,
            'cliente' => $datos['cliente'],
            'cantidad_peso' => $datos['cantidad_peso'] ?? null,
            'cantidad_unidades' => $datos['cantidad_unidades'] ?? null,
            'estado' => EstadoPedido::from($datos['estado']),
            'fecha_entrega' => $datos['fecha_entrega'] ?? null,
        ]);

        return redirect()->route('lotes.index')
            ->with('estado', "Pedido de {$pedido->cliente} registrado.");
    }

    public function estado(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:pendiente,en_proceso,completado,cancelado'],
        ]);

        $estado = EstadoPedido::from($datos['estado']);
        $pedido->update(['estado' => $estado]);

        return redirect()->route('lotes.index')
            ->with('estado', "Pedido de {$pedido->cliente}: {$estado->etiqueta()}.");
    }

    public function destroy(Request $request, Pedido $pedido): RedirectResponse
    {
        abort_unless(
            $pedido->user_id === $request->user()->id || $request->user()->esAdministrador(),
            403,
            'Solo quien registró el pedido o el administrador pueden eliminarlo.'
        );

        $cliente = $pedido->cliente;
        $pedido->delete();

        return redirect()->route('lotes.index')->with('estado', "Pedido de {$cliente} eliminado.");
    }

    /**
     * Asigna un lote al pedido (relación muchos a muchos).
     */
    public function asignarLote(Request $request, Pedido $pedido): RedirectResponse
    {
        $datos = $request->validate([
            'lote_id' => ['required', 'integer', 'exists:lotes,id'],
        ]);

        $pedido->lotes()->syncWithoutDetaching([$datos['lote_id']]);

        return redirect()->route('lotes.index')
            ->with('estado', 'Lote asignado al pedido de '.$pedido->cliente.'.');
    }

    public function quitarLote(Pedido $pedido, int $lote): RedirectResponse
    {
        $pedido->lotes()->detach($lote);

        return redirect()->route('lotes.index')
            ->with('estado', 'Lote desasignado del pedido.');
    }
}
