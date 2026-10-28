<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\EstadoLote;
use App\Enums\EstadoPedido;
use App\Enums\OrigenAccion;
use App\Models\HistorialTemperatura;
use App\Models\LogAccion;
use App\Models\Lote;
use App\Models\Pedido;
use App\Models\ReporteGenerado;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportesController extends Controller
{
    /** @var array<string, string> */
    public const TIPOS = [
        'lotes' => 'Historial de lotes',
        'temperaturas' => 'Historial de temperaturas',
        'auditoria' => 'Auditoría de comandos',
        'consumo' => 'Consumo y producción',
    ];

    public function index(Request $request): View
    {
        $informe = null;
        $tipoVista = $request->query('tipo');
        $desdeVista = $request->query('desde');
        $hastaVista = $request->query('hasta');

        if (is_string($tipoVista) && isset(self::TIPOS[$tipoVista]) && $request->boolean('ver')) {
            $desde = is_string($desdeVista) && $desdeVista !== '' ? Carbon::parse($desdeVista)->startOfDay() : null;
            $hasta = is_string($hastaVista) && $hastaVista !== '' ? Carbon::parse($hastaVista)->endOfDay() : null;
            $informe = $this->construir($tipoVista, $desde, $hasta) + [
                'titulo' => self::TIPOS[$tipoVista],
            ];
        }

        return view('reportes.index', [
            'tipos' => self::TIPOS,
            'generados' => ReporteGenerado::with('user:id,nombre')->orderByDesc('fecha')->limit(12)->get(),
            'resumen' => [
                'lotes' => Lote::count(),
                'temperaturas' => HistorialTemperatura::count(),
                'logs' => LogAccion::count(),
                'pedidos' => Pedido::count(),
            ],
            'estadisticas' => $this->estadisticas(),
            'informe' => $informe,
            'filtro' => [
                'tipo' => $tipoVista,
                'desde' => $desdeVista,
                'hasta' => $hastaVista,
            ],
        ]);
    }

    public function generar(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'tipo' => ['required', 'in:lotes,temperaturas,auditoria,consumo'],
            'formato' => ['required', 'in:pdf,csv,pantalla'],
            'desde' => ['nullable', 'date', 'before_or_equal:hasta'],
            'hasta' => ['nullable', 'date'],
        ], [
            'tipo.required' => 'Selecciona el tipo de informe.',
            'desde.before_or_equal' => 'La fecha inicial no puede ser posterior a la final.',
        ]);

        $desde = ! empty($datos['desde']) ? Carbon::parse($datos['desde'])->startOfDay() : null;
        $hasta = ! empty($datos['hasta']) ? Carbon::parse($datos['hasta'])->endOfDay() : null;

        $informe = $this->construir($datos['tipo'], $desde, $hasta);

        if ($datos['formato'] === 'pantalla') {
            return redirect()->route('reportes.index', [
                'ver' => 1,
                'tipo' => $datos['tipo'],
                'desde' => $datos['desde'] ?? null,
                'hasta' => $datos['hasta'] ?? null,
            ]);
        }

        $sello = now()->format('Ymd_His');
        // Si el formato es csv/excel, guardamos como .csv compatible 100% con Microsoft Excel
        $extension = $datos['formato'] === 'pdf' ? 'pdf' : 'csv';
        $ruta = "reportes/{$datos['tipo']}_{$sello}.{$extension}";

        Storage::disk('local')->makeDirectory('reportes');

        if ($datos['formato'] === 'pdf') {
            $contenido = Pdf::loadView('reportes.pdf', [
                'titulo' => self::TIPOS[$datos['tipo']],
                'columnas' => $informe['columnas'],
                'filas' => $informe['filas'],
                'resumen' => $informe['resumen'],
                'desde' => $desde,
                'hasta' => $hasta,
                'generadoPor' => $request->user()->nombre,
            ])->setPaper('a4', 'landscape')->output();
        } else {
            $contenido = $this->csv($informe['columnas'], $informe['filas']);
        }

        Storage::disk('local')->put($ruta, $contenido);

        $reporteGenerado = ReporteGenerado::create([
            'user_id' => $request->user()->id,
            'tipo' => $datos['tipo'],
            'ruta_archivo' => $ruta,
            'fecha' => now(),
        ]);

        return redirect()->route('reportes.index', [
            'ver' => 1,
            'tipo' => $datos['tipo'],
            'desde' => $datos['desde'] ?? null,
            'hasta' => $datos['hasta'] ?? null,
        ])->with(
            'estado',
            'Informe generado y guardado: '.(self::TIPOS[$datos['tipo']])." (formato {$extension} para Excel/PDF · "
            .count($informe['filas']).' filas).'
        );
    }

    /**
     * Muestra el contenido del informe guardado directamente en pantalla.
     */
    public function ver(Request $request, ReporteGenerado $reporte): RedirectResponse
    {
        $esPropietario = $reporte->user_id === $request->user()->id;
        abort_unless($esPropietario || $request->user()->esAdministrador(), 403, 'No puedes consultar este informe.');

        return redirect()->route('reportes.index', [
            'ver' => 1,
            'tipo' => $reporte->tipo,
        ]);
    }

    public function descargar(Request $request, ReporteGenerado $reporte): BinaryFileResponse
    {
        $esPropietario = $reporte->user_id === $request->user()->id;
        abort_unless($esPropietario || $request->user()->esAdministrador(), 403, 'No puedes descargar este informe.');

        $ruta = Storage::disk('local')->path($reporte->ruta_archivo);
        abort_unless(is_file($ruta), 404, 'El archivo ya no existe en el servidor.');

        $nombreDescarga = basename($reporte->ruta_archivo);
        // Si el archivo es csv, enviamos cabeceras para asegurar apertura en Excel
        $headers = [];
        if (str_ends_with($nombreDescarga, '.csv')) {
            $headers = [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$nombreDescarga.'"',
            ];
        }

        return response()->download($ruta, $nombreDescarga, $headers);
    }

    /**
     * Construye columnas, filas y resumen del informe solicitado.
     *
     * @return array{columnas: list<string>, filas: list<list<string|int|float>>, resumen: array<string, string>}
     */
    private function construir(string $tipo, ?Carbon $desde, ?Carbon $hasta): array
    {
        return match ($tipo) {
            'lotes' => $this->informeLotes($desde, $hasta),
            'temperaturas' => $this->informeTemperaturas($desde, $hasta),
            'auditoria' => $this->informeAuditoria($desde, $hasta),
            'consumo' => $this->informeConsumo($desde, $hasta),
        };
    }

    /**
     * @return array{columnas: list<string>, filas: list<list<string|int|float>>, resumen: array<string, string>}
     */
    private function informeLotes(?Carbon $desde, ?Carbon $hasta): array
    {
        $lotes = Lote::with(['receta', 'perfil', 'user'])
            ->when($desde, fn ($q) => $q->where('fecha_inicio', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('fecha_inicio', '<=', $hasta))
            ->orderByDesc('fecha_inicio')
            ->get();

        $filas = $lotes->map(fn (Lote $lote) => [
            $lote->codigo,
            $lote->receta?->nombre ?? '—',
            $lote->perfil?->nombre ?? '—',
            $lote->fecha_inicio->format('d/m/Y H:i'),
            $lote->fecha_fin?->format('d/m/Y H:i') ?? '—',
            $lote->estado->etiqueta(),
            $lote->peso_producido !== null ? number_format((float) $lote->peso_producido, 2) : '—',
            $lote->user?->nombre ?? '—',
        ])->all();

        return [
            'columnas' => ['Código', 'Receta', 'Perfil', 'Inicio', 'Fin', 'Estado', 'Peso (kg)', 'Responsable'],
            'filas' => $filas,
            'resumen' => [
                'Lotes' => (string) $lotes->count(),
                'Completados' => (string) $lotes->where('estado', EstadoLote::Completado)->count(),
                'Peso total' => number_format((float) $lotes->sum('peso_producido'), 2).' kg',
            ],
        ];
    }

    /**
     * @return array{columnas: list<string>, filas: list<list<string|int|float>>, resumen: array<string, string>}
     */
    private function informeTemperaturas(?Carbon $desde, ?Carbon $hasta): array
    {
        $muestras = HistorialTemperatura::query()
            ->when($desde, fn ($q) => $q->where('fecha_hora', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('fecha_hora', '<=', $hasta))
            ->orderBy('fecha_hora')
            ->limit(5000)
            ->get();

        $filas = $muestras->map(fn (HistorialTemperatura $m) => [
            $m->fecha_hora->format('d/m/Y H:i:s'),
            number_format((float) $m->temperatura, 2),
        ])->all();

        return [
            'columnas' => ['Fecha y hora', 'Temperatura (°C)'],
            'filas' => $filas,
            'resumen' => [
                'Muestras' => (string) $muestras->count(),
                'Mínima' => $muestras->isEmpty() ? '—' : number_format((float) $muestras->min('temperatura'), 2).' °C',
                'Máxima' => $muestras->isEmpty() ? '—' : number_format((float) $muestras->max('temperatura'), 2).' °C',
                'Promedio' => $muestras->isEmpty() ? '—' : number_format((float) $muestras->avg('temperatura'), 2).' °C',
            ],
        ];
    }

    /**
     * @return array{columnas: list<string>, filas: list<list<string|int|float>>, resumen: array<string, string>}
     */
    private function informeAuditoria(?Carbon $desde, ?Carbon $hasta): array
    {
        $registros = LogAccion::with('user')
            ->when($desde, fn ($q) => $q->where('fecha_hora', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('fecha_hora', '<=', $hasta))
            ->orderByDesc('fecha_hora')
            ->limit(5000)
            ->get();

        $filas = $registros->map(fn (LogAccion $log) => [
            $log->fecha_hora->format('d/m/Y H:i:s'),
            $log->accion,
            $log->componente,
            $log->origen->etiqueta(),
            $log->user?->nombre ?? 'Sistema',
        ])->all();

        return [
            'columnas' => ['Fecha y hora', 'Acción', 'Componente', 'Origen', 'Usuario'],
            'filas' => $filas,
            'resumen' => [
                'Registros' => (string) $registros->count(),
                'Desde la web' => (string) $registros->where('origen', OrigenAccion::Web)->count(),
                'Automatización' => (string) $registros->where('origen', OrigenAccion::Automatizacion)->count(),
            ],
        ];
    }

    /**
     * @return array{columnas: list<string>, filas: list<list<string|int|float>>, resumen: array<string, string>}
     */
    private function informeConsumo(?Carbon $desde, ?Carbon $hasta): array
    {
        $produccion = Lote::query()
            ->join('recetas', 'recetas.id', '=', 'lotes.receta_id')
            ->where('lotes.estado', EstadoLote::Completado)
            ->when($desde, fn ($q) => $q->where('lotes.fecha_inicio', '>=', $desde))
            ->when($hasta, fn ($q) => $q->where('lotes.fecha_inicio', '<=', $hasta))
            ->groupBy('recetas.nombre')
            ->selectRaw('recetas.nombre as receta, count(*) as lotes, coalesce(sum(lotes.peso_producido), 0) as kilos')
            ->orderByDesc('kilos')
            ->get();

        $filas = $produccion->map(fn ($fila) => [
            $fila->receta,
            (int) $fila->lotes,
            number_format((float) $fila->kilos, 2),
        ])->all();

        $pedidosAbiertos = Pedido::abiertos()->count();
        $pedidosCompletados = Pedido::where('estado', EstadoPedido::Completado)->count();

        return [
            'columnas' => ['Receta', 'Lotes completados', 'Peso producido (kg)'],
            'filas' => $filas,
            'resumen' => [
                'Recetas producidas' => (string) $produccion->count(),
                'Peso total' => number_format((float) $produccion->sum('kilos'), 2).' kg',
                'Pedidos abiertos' => (string) $pedidosAbiertos,
                'Pedidos completados' => (string) $pedidosCompletados,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function estadisticas(): array
    {
        $lotesEstado = Lote::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $pedidosEstado = Pedido::query()
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $maxLote = max(1, (int) $lotesEstado->max());
        $maxPedido = max(1, (int) $pedidosEstado->max());

        $lotesBarras = [];
        foreach (EstadoLote::cases() as $estado) {
            $valor = (int) ($lotesEstado[$estado->value] ?? 0);
            $lotesBarras[] = [
                'etiqueta' => $estado->etiqueta(),
                'valor' => $valor,
                'porcentaje' => round(100 * $valor / $maxLote),
            ];
        }

        $pedidosBarras = [];
        foreach (EstadoPedido::cases() as $estado) {
            $valor = (int) ($pedidosEstado[$estado->value] ?? 0);
            $pedidosBarras[] = [
                'etiqueta' => $estado->etiqueta(),
                'valor' => $valor,
                'porcentaje' => round(100 * $valor / $maxPedido),
            ];
        }

        $comandos = LogAccion::query()
            ->selectRaw('componente, count(*) as total')
            ->groupBy('componente')
            ->orderByDesc('total')
            ->limit(6)
            ->get();

        $maxComando = max(1, (int) $comandos->max('total'));

        return [
            'lotes' => $lotesBarras,
            'pedidos' => $pedidosBarras,
            'comandos' => $comandos->map(fn ($fila) => [
                'etiqueta' => (string) $fila->componente,
                'valor' => (int) $fila->total,
                'porcentaje' => round(100 * (int) $fila->total / $maxComando),
            ])->all(),
            'peso' => (float) Lote::where('estado', EstadoLote::Completado)->sum('peso_producido'),
        ];
    }

    /**
     * CSV con BOM y punto y coma: Excel en español lo abre directamente.
     *
     * @param  list<string>  $columnas
     * @param  list<list<string|int|float>>  $filas
     */
    private function csv(array $columnas, array $filas): string
    {
        $manejador = fopen('php://temp', 'r+');
        fputcsv($manejador, $columnas, ';');

        foreach ($filas as $fila) {
            fputcsv($manejador, $fila, ';');
        }

        rewind($manejador);
        $contenido = stream_get_contents($manejador);
        fclose($manejador);

        return "\xEF\xBB\xBF".$contenido;
    }
}
