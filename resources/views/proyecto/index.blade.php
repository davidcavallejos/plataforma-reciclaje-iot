@extends('layouts.app')

@section('titulo', 'Proyecto y registros')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Proyecto y registros</h1>
            <p>Información pública del proyecto y actividad registrada por la máquina.</p>
        </div>
    </div>

    <section class="rejilla dos">
        <div class="tarjeta">
            <h2>🧼 Sobre el proyecto</h2>
            <p>
                Panel de control local para una máquina automatizada de fabricación de jabón.
                El equipo controla motores, bombas y filtros mediante cuatro módulos L298N
                gobernados por un ESP32, y registra cada comando para poder auditar la producción.
            </p>
            <p class="pequeno">
                El sistema permite formular recetas con cálculo proporcional, guardar perfiles de
                funcionamiento (PWM, tiempos y automatización por sensores) y relacionar los lotes
                producidos con los pedidos de los clientes.
            </p>
            <div class="pie pequeno">
                Instituto Técnico Nacional de Comercio "Federico Álvarez Plata" (Nocturno)
            </div>
        </div>

        <div class="tarjeta">
            <h2>📈 Métricas del sistema</h2>
            <div class="rejilla dos" style="gap: .8rem;">
                <div class="metrica">
                    <span class="etiqueta">Recetas</span>
                    <span class="valor">{{ $metricas['recetas'] }}</span>
                </div>
                <div class="metrica">
                    <span class="etiqueta">Lotes</span>
                    <span class="valor">{{ $metricas['lotes'] }}</span>
                    <span class="nota">{{ $metricas['lotes_completados'] }} completados</span>
                </div>
                <div class="metrica">
                    <span class="etiqueta">Pedidos abiertos</span>
                    <span class="valor">{{ $metricas['pedidos_abiertos'] }}</span>
                    <span class="nota">{{ $metricas['pedidos_pendientes'] }} pendientes</span>
                </div>
                <div class="metrica">
                    <span class="etiqueta">Temperaturas</span>
                    <span class="valor">{{ $metricas['muestras_temperatura'] }}</span>
                    <span class="nota">muestras guardadas</span>
                </div>
                <div class="metrica">
                    <span class="etiqueta">Usuarios</span>
                    <span class="valor">{{ $metricas['usuarios'] }}</span>
                </div>
                <div class="metrica">
                    <span class="etiqueta">Programaciones</span>
                    <span class="valor">{{ $metricas['programaciones_activas'] }}</span>
                    <span class="nota">activas</span>
                </div>
            </div>
        </div>
    </section>

    <section class="tarjeta separado">
        <div class="entre">
            <h2>🧾 Registro de actividad</h2>
            <form method="GET" action="{{ route('proyecto.index') }}" class="fila">
                <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Filtrar por acción o componente"
                       style="padding:.5rem .7rem;border:1px solid var(--borde);border-radius:9px;">
                <button type="submit" class="boton">Filtrar</button>
                @if ($buscar !== '')
                    <a href="{{ route('proyecto.index') }}" class="boton minimo">Limpiar</a>
                @endif
            </form>
        </div>

        <div class="tabla-envoltura separado">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Fecha y hora</th>
                        <th>Acción</th>
                        <th>Componente</th>
                        <th>Origen</th>
                        <th>Usuario</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($registros as $registro)
                        <tr>
                            <td class="mono">{{ $registro->fecha_hora->format('d/m/Y H:i:s') }}</td>
                            <td class="mono">{{ $registro->accion }}</td>
                            <td>{{ $registro->componente }}</td>
                            <td>
                                <span class="insignia {{ $registro->origen->value === 'web' ? 'verde' : ($registro->origen->value === 'esp32' ? 'ambar' : 'gris') }}">
                                    {{ $registro->origen->etiqueta() }}
                                </span>
                            </td>
                            <td>{{ $registro->user?->nombre ?? 'Sistema' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="centro pequeno">Todavía no hay registros de actividad.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="separado">{{ $registros->links() }}</div>
    </section>

    <section class="tarjeta separado">
        <h2>📦 Últimos lotes</h2>
        <div class="tabla-envoltura">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Receta</th>
                        <th>Inicio</th>
                        <th>Estado</th>
                        <th>Responsable</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ultimosLotes as $lote)
                        <tr>
                            <td class="mono">{{ $lote->codigo }}</td>
                            <td>{{ $lote->receta?->nombre ?? '—' }}</td>
                            <td>{{ $lote->fecha_inicio->format('d/m/Y H:i') }}</td>
                            <td><span class="insignia gris">{{ $lote->estado->etiqueta() }}</span></td>
                            <td>{{ $lote->user?->nombre ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="centro pequeno">Aún no se han registrado lotes.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
@endsection
