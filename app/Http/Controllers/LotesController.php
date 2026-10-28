<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoLote;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\Perfil;
use App\Models\Receta;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LotesController extends Controller
{
    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $lotes = Lote::with(['receta', 'perfil', 'user', 'pedidos'])
            ->when($buscar !== '', function ($query) use ($buscar) {
                $like = '%'.$buscar.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('codigo', 'ilike', $like)
                        ->orWhereHas('receta', fn ($r) => $r->where('nombre', 'ilike', $like))
                        ->orWhereHas('pedidos', fn ($p) => $p->where('cliente', 'ilike', $like));
                });
            })
            ->orderByDesc('fecha_inicio')
            ->paginate(10, ['*'], 'lotes_page')
            ->withQueryString();

        $pedidos = Pedido::with(['user', 'lotes'])
            ->orderByRaw("case when estado = 'pendiente' then 0 when estado = 'en_proceso' then 1 else 2 end")
            ->orderBy('fecha_entrega')
            ->paginate(10, ['*'], 'pedidos_page')
            ->withQueryString();

        return view('lotes.index', [
            'lotes' => $lotes,
            'pedidos' => $pedidos,
            'buscar' => $buscar,
            'recetas' => Receta::orderBy('nombre')->get(),
            'perfiles' => Perfil::orderByDesc('es_predeterminado')->orderBy('nombre')->get(),
            'estadosLote' => EstadoLote::opciones(),
            'estadosPedido' => \App\Enums\EstadoPedido::opciones(),
            'lotesDisponibles' => Lote::whereIn('estado', [EstadoLote::Pendiente, EstadoLote::EnProceso])
                ->orderByDesc('fecha_inicio')
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'receta_id' => ['required', 'integer', 'exists:recetas,id'],
            'perfil_id' => ['nullable', 'integer', 'exists:perfiles,id'],
            'estado' => ['required', 'in:pendiente,en_proceso,completado,cancelado'],
            'peso_producido' => ['nullable', 'numeric', 'min:0.01', 'max:999999'],
        ], [
            'receta_id.required' => 'Selecciona la receta del lote.',
            'receta_id.exists' => 'La receta seleccionada no existe.',
        ]);

        $estado = EstadoLote::from($datos['estado']);

        $lote = Lote::create([
            'codigo' => $this->siguienteCodigo(),
            'user_id' => $request->user()->id,
            'receta_id' => $datos['receta_id'],
            'perfil_id' => $datos['perfil_id'] ?? null,
            'fecha_inicio' => now(),
            'fecha_fin' => $estado->estaFinalizado() ? now() : null,
            'estado' => $estado,
            'peso_producido' => $datos['peso_producido'] ?? null,
            // Congela la configuración del perfil: el lote queda auditable
            // aunque el perfil se edite después.
            'configuracion_aplicada' => $datos['perfil_id'] ?? null
                ? Perfil::find($datos['perfil_id'])?->configuracionNormalizada()
                : null,
        ]);

        return redirect()->route('lotes.index')
            ->with('estado', "Lote {$lote->codigo} creado en estado {$lote->estado->etiqueta()}.");
    }

    public function estado(Request $request, Lote $lote): RedirectResponse
    {
        $datos = $request->validate([
            'estado' => ['required', 'in:pendiente,en_proceso,completado,cancelado'],
            'peso_producido' => ['nullable', 'numeric', 'min:0.01', 'max:999999'],
        ]);

        $estado = EstadoLote::from($datos['estado']);

        $lote->update([
            'estado' => $estado,
            'peso_producido' => $datos['peso_producido'] ?? $lote->peso_producido,
            'fecha_fin' => $estado->estaFinalizado() ? ($lote->fecha_fin ?? now()) : null,
        ]);

        return redirect()->route('lotes.index')
            ->with('estado', "Lote {$lote->codigo}: {$estado->etiqueta()}.");
    }

    public function destroy(Request $request, Lote $lote): RedirectResponse
    {
        abort_unless(
            $lote->user_id === $request->user()->id || $request->user()->esAdministrador(),
            403,
            'Solo el responsable del lote o el administrador pueden eliminarlo.'
        );

        try {
            $codigo = $lote->codigo;
            $lote->delete();

            return redirect()->route('lotes.index')->with('estado', "Lote {$codigo} eliminado.");
        } catch (QueryException) {
            return redirect()->route('lotes.index')->withErrors([
                'lote' => 'No se pudo eliminar el lote.',
            ]);
        }
    }

    /**
     * Código legible: LOTE-2026-0001, reiniciando la numeración cada año.
     */
    private function siguienteCodigo(): string
    {
        $anio = (int) now()->format('Y');
        $secuencia = Lote::whereYear('fecha_inicio', $anio)->count() + 1;

        do {
            $codigo = sprintf('LOTE-%d-%04d', $anio, $secuencia);
            $secuencia++;
        } while (Lote::where('codigo', $codigo)->exists());

        return $codigo;
    }
}
