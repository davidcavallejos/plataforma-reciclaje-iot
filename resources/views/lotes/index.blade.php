@extends('layouts.app')

@section('titulo', 'Lotes y pedidos')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Lotes y pedidos</h1>
            <p>Producción real y pedidos de clientes, relacionados entre sí.</p>
        </div>
        <form method="GET" action="{{ route('lotes.index') }}" class="fila">
            <input type="search" name="buscar" value="{{ $buscar }}"
                   placeholder="Buscar por código, receta o cliente"
                   style="padding:.5rem .7rem;border:1px solid var(--borde);border-radius:9px;">
            <button type="submit" class="boton">Buscar</button>
        </form>
    </div>

    <section class="rejilla dos">
        <div class="tarjeta">
            <h2>📦 Nuevo lote</h2>
            @if ($recetas->isEmpty())
                <div class="vacio-estado">
                    Primero crea una receta en <a href="{{ route('recetas.index') }}">Formulación</a>.
                </div>
            @else
                <form method="POST" action="{{ route('lotes.store') }}">
                    @csrf
                    <div class="campo">
                        <label for="receta_id">Receta</label>
                        <select id="receta_id" name="receta_id" required>
                            @foreach ($recetas as $receta)
                                <option value="{{ $receta->id }}">{{ $receta->nombre }} ({{ $receta->tipo_producto->etiqueta() }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fila-campos">
                        <div class="campo">
                            <label for="perfil_id">Perfil de máquina</label>
                            <select id="perfil_id" name="perfil_id">
                                <option value="">Sin perfil (control manual)</option>
                                @foreach ($perfiles as $perfil)
                                    <option value="{{ $perfil->id }}">{{ $perfil->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo">
                            <label for="estado_lote">Estado inicial</label>
                            <select id="estado_lote" name="estado" required>
                                @foreach ($estadosLote as $valor => $etiqueta)
                                    <option value="{{ $valor }}">{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="campo">
                            <label for="peso_producido">Peso producido (kg)</label>
                            <input id="peso_producido" type="number" name="peso_producido" step="0.01" min="0.01"
                                   placeholder="opcional">
                        </div>
                    </div>

                    <button type="submit" class="boton primario bloque">Crear lote</button>
                </form>
            @endif
        </div>

        <div class="tarjeta">
            <h2>🧾 Nuevo pedido</h2>
            <form method="POST" action="{{ route('pedidos.store') }}">
                @csrf
                <div class="campo">
                    <label for="cliente">Cliente</label>
                    <input id="cliente" type="text" name="cliente" required maxlength="150"
                           placeholder="Nombre del cliente">
                </div>

                <div class="fila-campos">
                    <div class="campo">
                        <label for="cantidad_peso">Cantidad (kg)</label>
                        <input id="cantidad_peso" type="number" name="cantidad_peso" step="0.01" min="0.01">
                    </div>
                    <div class="campo">
                        <label for="cantidad_unidades">Cantidad (unidades)</label>
                        <input id="cantidad_unidades" type="number" name="cantidad_unidades" min="1">
                    </div>
                    <div class="campo">
                        <label for="fecha_entrega">Fecha de entrega</label>
                        <input id="fecha_entrega" type="date" name="fecha_entrega">
                    </div>
                </div>

                <div class="campo">
                    <label for="estado_pedido">Estado</label>
                    <select id="estado_pedido" name="estado" required>
                        @foreach ($estadosPedido as $valor => $etiqueta)
                            <option value="{{ $valor }}">{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="boton primario bloque">Registrar pedido</button>
            </form>
        </div>
    </section>

    <section class="tarjeta separado">
        <div class="entre">
            <h2>📦 Lotes de producción</h2>
            <span class="insignia gris">{{ $lotes->total() }}</span>
        </div>

        <div class="tabla-envoltura separado">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Receta / perfil</th>
                        <th>Inicio</th>
                        <th>Estado y peso</th>
                        <th>Pedidos</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($lotes as $lote)
                        <tr>
                            <td class="mono">{{ $lote->codigo }}</td>
                            <td>
                                {{ $lote->receta?->nombre ?? '—' }}
                                <div class="pequeno">
                                    {{ $lote->perfil?->nombre ?? 'sin perfil' }}
                                    · {{ $lote->user?->nombre ?? '—' }}
                                </div>
                            </td>
                            <td class="pequeno">{{ $lote->fecha_inicio->format('d/m/Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ route('lotes.estado', $lote) }}" class="fila" style="gap:.3rem;">
                                    @csrf
                                    @method('PATCH')
                                    <select name="estado" style="padding:.3rem;border:1px solid var(--borde);border-radius:7px;" onchange="this.form.submit()">
                                        @foreach ($estadosLote as $valor => $etiqueta)
                                            <option value="{{ $valor }}" @selected($lote->estado->value === $valor)>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                    <input type="number" name="peso_producido" step="0.01" min="0.01"
                                           value="{{ $lote->peso_producido }}" placeholder="kg"
                                           style="width:74px;padding:.3rem;border:1px solid var(--borde);border-radius:7px;">
                                    <button type="submit" class="boton minimo">OK</button>
                                </form>
                            </td>
                            <td>
                                @forelse ($lote->pedidos as $pedido)
                                    <span class="insignia verde">{{ $pedido->cliente }}</span>
                                @empty
                                    <span class="pequeno">—</span>
                                @endforelse
                            </td>
                            <td class="derecha">
                                <form method="POST" action="{{ route('lotes.destroy', $lote) }}"
                                      onsubmit="return confirm('¿Eliminar el lote {{ $lote->codigo }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="boton minimo">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="centro pequeno">No hay lotes registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="separado">{{ $lotes->links() }}</div>
    </section>

    <section class="tarjeta separado">
        <div class="entre">
            <h2>🧾 Pedidos</h2>
            <span class="insignia gris">{{ $pedidos->total() }}</span>
        </div>

        <div class="tabla-envoltura separado">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Cantidad</th>
                        <th>Entrega</th>
                        <th>Estado</th>
                        <th>Lotes asignados</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pedidos as $pedido)
                        <tr>
                            <td>
                                <strong>{{ $pedido->cliente }}</strong>
                                <div class="pequeno">{{ $pedido->user?->nombre ?? '—' }}</div>
                            </td>
                            <td class="pequeno">{{ $pedido->descripcionCantidad() }}</td>
                            <td class="pequeno">{{ $pedido->fecha_entrega?->format('d/m/Y') ?? '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('pedidos.estado', $pedido) }}">
                                    @csrf
                                    @method('PATCH')
                                    <select name="estado" style="padding:.3rem;border:1px solid var(--borde);border-radius:7px;" onchange="this.form.submit()">
                                        @foreach ($estadosPedido as $valor => $etiqueta)
                                            <option value="{{ $valor }}" @selected($pedido->estado->value === $valor)>{{ $etiqueta }}</option>
                                        @endforeach
                                    </select>
                                </form>
                            </td>
                            <td>
                                <div class="fila" style="gap:.25rem;margin-bottom:.3rem;">
                                    @forelse ($pedido->lotes as $lote)
                                        <span class="insignia verde">
                                            {{ $lote->codigo }}
                                            <form method="POST" action="{{ route('pedidos.lotes.quitar', [$pedido, $lote->id]) }}" style="display:inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="boton minimo" style="padding:0 .25rem;border:0;background:none;color:inherit;">×</button>
                                            </form>
                                        </span>
                                    @empty
                                        <span class="pequeno">Sin lotes</span>
                                    @endforelse
                                </div>

                                @if ($lotesDisponibles->isNotEmpty())
                                    <form method="POST" action="{{ route('pedidos.lotes.asignar', $pedido) }}" class="fila" style="gap:.25rem;">
                                        @csrf
                                        <select name="lote_id" style="padding:.25rem;border:1px solid var(--borde);border-radius:7px;font-size:.78rem;">
                                            @foreach ($lotesDisponibles as $lote)
                                                <option value="{{ $lote->id }}">{{ $lote->codigo }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="boton minimo">Asignar</button>
                                    </form>
                                @endif
                            </td>
                            <td class="derecha">
                                <form method="POST" action="{{ route('pedidos.destroy', $pedido) }}"
                                      onsubmit="return confirm('¿Eliminar el pedido de {{ $pedido->cliente }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="boton minimo">Eliminar</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="centro pequeno">No hay pedidos registrados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="separado">{{ $pedidos->links() }}</div>
    </section>
@endsection
