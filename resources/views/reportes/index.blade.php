@extends('layouts.app')

@section('titulo', 'Reportes e informes')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Reportes e informes</h1>
            <p>Consulta estadísticas, visualiza el informe en pantalla o descárgalo en PDF y Excel.</p>
        </div>
    </div>

    <section class="rejilla tres">
        <div class="tarjeta">
            <h2>Lotes por estado</h2>
            @foreach ($estadisticas['lotes'] as $barra)
                <div class="barra-dato">
                    <div class="entre pequeno"><span>{{ $barra['etiqueta'] }}</span><strong>{{ $barra['valor'] }}</strong></div>
                    <div class="barra-fondo"><span style="width: {{ $barra['porcentaje'] }}%"></span></div>
                </div>
            @endforeach
        </div>
        <div class="tarjeta">
            <h2>Pedidos por estado</h2>
            @foreach ($estadisticas['pedidos'] as $barra)
                <div class="barra-dato">
                    <div class="entre pequeno"><span>{{ $barra['etiqueta'] }}</span><strong>{{ $barra['valor'] }}</strong></div>
                    <div class="barra-fondo"><span style="width: {{ $barra['porcentaje'] }}%"></span></div>
                </div>
            @endforeach
        </div>
        <div class="tarjeta">
            <h2>Comandos por componente</h2>
            @forelse ($estadisticas['comandos'] as $barra)
                <div class="barra-dato">
                    <div class="entre pequeno"><span>{{ $barra['etiqueta'] }}</span><strong>{{ $barra['valor'] }}</strong></div>
                    <div class="barra-fondo"><span style="width: {{ $barra['porcentaje'] }}%"></span></div>
                </div>
            @empty
                <div class="pequeno">Aún no hay comandos registrados.</div>
            @endforelse
            <div class="pie pequeno">Producción completada: {{ number_format($estadisticas['peso'], 2) }} kg</div>
        </div>
    </section>

    <section class="rejilla dos separado">
        <div class="tarjeta">
            <h2>Generar o ver informe</h2>
            <form method="POST" action="{{ route('reportes.generar') }}">
                @csrf
                <div class="campo">
                    <label for="tipo">Tipo de informe</label>
                    <select id="tipo" name="tipo" required>
                        @foreach ($tipos as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('tipo', $filtro['tipo']) === $valor)>
                                {{ $etiqueta }} ({{ $resumen[$valor === 'auditoria' ? 'logs' : $valor] ?? 0 }} registros)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="fila-campos">
                    <div class="campo">
                        <label for="desde">Desde</label>
                        <input id="desde" type="date" name="desde" value="{{ old('desde', $filtro['desde']) }}">
                    </div>
                    <div class="campo">
                        <label for="hasta">Hasta</label>
                        <input id="hasta" type="date" name="hasta" value="{{ old('hasta', $filtro['hasta']) }}">
                    </div>
                    <div class="campo">
                        <label for="formato">Salida y Formato</label>
                        <select id="formato" name="formato" required>
                            <option value="pantalla" @selected(old('formato', 'pantalla') === 'pantalla')>Ver en pantalla</option>
                            <option value="csv" @selected(old('formato') === 'csv')>Formato Excel (.csv)</option>
                            <option value="pdf" @selected(old('formato') === 'pdf')>Formato PDF (.pdf)</option>
                        </select>
                    </div>
                </div>
                <button type="submit" class="boton primario bloque">Generar o ver informe</button>
            </form>
        </div>

        <div class="tarjeta">
            <h2>Archivos guardados</h2>
            <div class="tabla-envoltura separado">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Informe</th>
                            <th>Fecha</th>
                            <th>Autor</th>
                            <th>Opciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($generados as $reporte)
                            <tr>
                                <td>
                                    <strong>{{ $tipos[$reporte->tipo] ?? $reporte->tipo }}</strong>
                                    <div class="pequeno mono">{{ basename($reporte->ruta_archivo) }}</div>
                                </td>
                                <td class="pequeno">{{ $reporte->fecha->format('d/m/Y H:i') }}</td>
                                <td class="pequeno">{{ $reporte->user?->nombre ?? '—' }}</td>
                                <td class="derecha" style="white-space:nowrap;">
                                    <a href="{{ route('reportes.ver', $reporte) }}" class="boton minimo suave">Ver informe</a>
                                    <a href="{{ route('reportes.descargar', $reporte) }}" class="boton minimo primario" title="Descargar en formato Excel o PDF">Descargar</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="centro pequeno">Todavía no se ha generado ningún archivo.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @if ($informe)
        <section class="tarjeta separado" id="informe-en-pantalla">
            <div class="entre">
                <h2>{{ $informe['titulo'] }}</h2>
                <span class="insignia gris">{{ count($informe['filas']) }} filas</span>
            </div>
            <div class="rejilla cuatro separado">
                @foreach ($informe['resumen'] as $etiqueta => $valor)
                    <div class="metrica">
                        <span class="etiqueta">{{ $etiqueta }}</span>
                        <span class="valor unidad">{{ $valor }}</span>
                    </div>
                @endforeach
            </div>
            <div class="tabla-envoltura separado">
                <table class="tabla">
                    <thead>
                        <tr>
                            @foreach ($informe['columnas'] as $columna)
                                <th>{{ $columna }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($informe['filas'] as $fila)
                            <tr>
                                @foreach ($fila as $celda)
                                    <td>{{ $celda }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr><td colspan="{{ count($informe['columnas']) }}" class="centro pequeno">Sin datos en el período.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
@endsection
