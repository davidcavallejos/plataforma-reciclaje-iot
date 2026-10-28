<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\IngredienteBase;
use App\Enums\TipoProducto;
use App\Models\Receta;
use App\Services\FormulacionService;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RecetasController extends Controller
{
    public function __construct(private readonly FormulacionService $formulacion) {}

    public function index(Request $request): View
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $recetas = Receta::with('user:id,nombre')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where('nombre', 'ilike', '%'.$buscar.'%');
            })
            ->orderByDesc('created_at')
            ->paginate(10)
            ->withQueryString();

        return view('recetas.index', [
            'recetas' => $recetas,
            'buscar' => $buscar,
            'catalogo' => $this->formulacion->catalogo(),
            'tiposProducto' => TipoProducto::opciones(),
            'ingredientes' => IngredienteBase::opciones(),
        ]);
    }

    /**
     * Almacena una nueva receta calculada automáticamente en base a fuentes
     * saponificables confiables (SAP NaOH / KOH y porcentajes de agua y sobreengrasado).
     */
    public function store(Request $request): RedirectResponse
    {
        $aceites = array_keys(config('panel.aceites', []));

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:150'],
            'tipo_producto' => ['required', 'in:liquido,barra'],
            'tipo_aceite' => ['required', 'string', Rule::in($aceites)],
            'ingrediente_base' => ['required', 'in:aceite,agua,sosa'],
            'unidad' => ['nullable', 'in:g,kg'],
            'peso' => ['required', 'numeric', 'min:0.001', 'max:999999'],
            'aditivos' => ['nullable', 'array', 'max:12'],
            'aditivos.*.nombre' => ['nullable', 'string', 'max:80'],
            'aditivos.*.gramos' => ['nullable', 'numeric', 'min:0', 'max:99999'],
        ], [
            'nombre.required' => 'Ponle un nombre a la receta.',
            'tipo_aceite.required' => 'Elige el tipo de aceite.',
            'tipo_aceite.in' => 'El tipo de aceite no es válido.',
            'peso.required' => 'Indica el peso del ingrediente base.',
            'peso.min' => 'El peso debe ser mayor que cero.',
        ]);

        $unidad = $datos['unidad'] ?? 'g';
        // Convertimos a gramos si el usuario ingresó en kilogramos para mantener consistencia relacional
        $pesoGramos = $unidad === 'kg' ? ((float) $datos['peso']) * 1000 : (float) $datos['peso'];

        $tipo = TipoProducto::from($datos['tipo_producto']);
        $base = IngredienteBase::from($datos['ingrediente_base']);
        $calculos = $this->formulacion->calcular($tipo, $datos['tipo_aceite'], $base, $pesoGramos);

        if ($calculos === []) {
            return redirect()->route('recetas.index')
                ->withErrors(['peso' => 'No se pudo calcular la formulación con esos valores.'])
                ->withInput();
        }

        $aditivos = $this->aditivosValidos($request);
        if ($aditivos === []) {
            $aditivos = $calculos['aditivos_sugeridos'] ?? [];
        }

        unset($calculos['aditivos_sugeridos']);

        $aceite = $this->formulacion->aceite($datos['tipo_aceite']);

        $receta = Receta::create([
            'user_id' => $request->user()->id,
            'nombre' => $datos['nombre'],
            'tipo_producto' => $tipo,
            'ingrediente_base' => $base,
            'peso' => $pesoGramos,
            'tipo_aceite' => $aceite['nombre'] ?? $datos['tipo_aceite'],
            'calculos' => $calculos,
            'aditivos' => $aditivos,
        ]);

        $alcali = $calculos['alcali_nombre'] ?? 'sosa';

        return redirect()->route('recetas.index')->with(
            'estado',
            "Receta «{$receta->nombre}» guardada: "
            .number_format((float) $calculos['aceite'], 2).' g de aceite, '
            .number_format((float) $calculos['agua'], 2).' g de agua y '
            .number_format((float) $calculos['sosa'], 2).' g de '.$alcali.'.'
        );
    }

    public function destroy(Request $request, Receta $receta): RedirectResponse
    {
        $usuario = $request->user();

        if ($receta->user_id !== $usuario->id && ! $usuario->esAdministrador()) {
            abort(403, 'Solo el autor o el administrador pueden eliminar esta receta.');
        }

        try {
            $nombre = $receta->nombre;
            $receta->delete();

            return redirect()->route('recetas.index')->with('estado', "Receta «{$nombre}» eliminada.");
        } catch (QueryException) {
            return redirect()->route('recetas.index')->withErrors([
                'receta' => 'No se puede eliminar: la receta ya tiene lotes de producción asociados.',
            ]);
        }
    }

    /**
     * @return list<array{nombre: string, gramos: float}>
     */
    private function aditivosValidos(Request $request): array
    {
        $aditivos = [];

        foreach ((array) $request->input('aditivos', []) as $aditivo) {
            $nombre = trim((string) ($aditivo['nombre'] ?? ''));

            if ($nombre === '') {
                continue;
            }

            $aditivos[] = [
                'nombre' => $nombre,
                'gramos' => round((float) ($aditivo['gramos'] ?? 0), 2),
            ];
        }

        return $aditivos;
    }
}
