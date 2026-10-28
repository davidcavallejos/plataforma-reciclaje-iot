<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 22px 26px; }
        * { box-sizing: border-box; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5px; color: #14231e; }
        .cabecera { border-bottom: 2px solid #0f766e; padding-bottom: 8px; margin-bottom: 12px; }
        .cabecera h1 { margin: 0; font-size: 15px; color: #0b3d38; }
        .cabecera .institucion { font-size: 9px; color: #4b615a; }
        .meta { margin-top: 6px; font-size: 8.5px; color: #4b615a; }
        .resumen { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .resumen td { border: 1px solid #d7e4df; padding: 5px 7px; background: #f4faf8; }
        .resumen .etiqueta { font-size: 7.5px; text-transform: uppercase; letter-spacing: .06em; color: #4b615a; display: block; }
        .resumen .valor { font-size: 11px; font-weight: bold; }
        table.datos { width: 100%; border-collapse: collapse; }
        table.datos th {
            background: #0f766e; color: #fff; text-align: left;
            padding: 5px 6px; font-size: 8.5px; text-transform: uppercase; letter-spacing: .04em;
        }
        table.datos td { border-bottom: 1px solid #e3ece8; padding: 4px 6px; }
        table.datos tr:nth-child(even) td { background: #f8fbfa; }
        .pie { position: fixed; bottom: -6px; left: 0; right: 0; font-size: 7.5px; color: #6b7f78; text-align: center; }
        .vacio { padding: 14px; text-align: center; color: #6b7f78; border: 1px dashed #cfe0d9; }
    </style>
</head>
<body>
    <div class="cabecera">
        <h1>{{ $titulo }}</h1>
        <div class="institucion">
            Máquina automatizada de jabón · Instituto Técnico Nacional de Comercio "Federico Álvarez Plata" (Nocturno)
        </div>
        <div class="meta">
            Período:
            {{ $desde?->format('d/m/Y') ?? 'inicio' }} — {{ $hasta?->format('d/m/Y') ?? 'hoy' }}
            · Generado el {{ now()->format('d/m/Y H:i') }}
            · Por {{ $generadoPor }}
        </div>
    </div>

    <table class="resumen">
        <tr>
            @foreach ($resumen as $etiqueta => $valor)
                <td style="width: {{ round(100 / max(count($resumen), 1)) }}%;">
                    <span class="etiqueta">{{ $etiqueta }}</span>
                    <span class="valor">{{ $valor }}</span>
                </td>
            @endforeach
        </tr>
    </table>

    @if (count($filas) === 0)
        <div class="vacio">No hay datos en el período seleccionado.</div>
    @else
        <table class="datos">
            <thead>
                <tr>
                    @foreach ($columnas as $columna)
                        <th>{{ $columna }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        @foreach ($fila as $celda)
                            <td>{{ $celda }}</td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="pie">
        Panel local de control · {{ config('app.name') }} · Documento generado automáticamente
    </div>
</body>
</html>
