@extends('layouts.app')

@section('titulo', 'Inicio')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>
                @if ($esInvitado)
                    Inicio
                @else
                    Hola, {{ auth()->user()->nombre }}
                @endif
            </h1>
            <p>
                @if ($esInvitado)
                    Vista de invitado: resumen y noticias. Inicia sesión para operar el panel.
                @else
                    Resumen del proyecto, la máquina y los pedidos en curso.
                @endif
            </p>
        </div>

        @unless ($esInvitado)
            <div class="buscador">
                <span class="lupa">🔍</span>
                <input id="buscador" type="search" placeholder="Buscar recetas, lotes o pedidos…"
                       autocomplete="off" aria-label="Buscador global">
                <div class="buscador-resultados" id="resultados-busqueda"></div>
            </div>
        @endunless
    </div>

    <section class="rejilla cuatro">
        <div class="tarjeta metrica">
            <span class="etiqueta">Temperatura</span>
            <span class="valor">
                {{ $temperatura ? number_format((float) $temperatura->temperatura, 1) : '—' }}
                <small>°C</small>
            </span>
            <span class="nota">
                {{ $temperatura ? 'Última lectura: '.$temperatura->fecha_hora->format('H:i:s') : 'Sin lecturas todavía' }}
            </span>
        </div>
        <div class="tarjeta metrica">
            <span class="etiqueta">Pedidos pendientes</span>
            <span class="valor">{{ $pedidosPendientes }}</span>
            <span class="nota">{{ $pedidosRecientes->isNotEmpty() ? 'Próxima entrega: '.$pedidosRecientes->first()->fecha_entrega?->format('d/m/Y') : 'Sin entregas programadas' }}</span>
        </div>
        <div class="tarjeta metrica">
            <span class="etiqueta">Último lote</span>
            <span class="valor unidad">{{ $ultimoLote?->codigo ?? '—' }}</span>
            <span class="nota">
                {{ $ultimoLote ? ($ultimoLote->receta?->nombre ?? 'Sin receta').' · '.$ultimoLote->estado->etiqueta() : 'Aún no hay lotes registrados' }}
            </span>
        </div>
        <div class="tarjeta metrica">
            <span class="etiqueta">Recetas registradas</span>
            <span class="valor">{{ $totalRecetas }}</span>
            <span class="nota">{{ $lotesCompletados }} lote(s) completado(s)</span>
        </div>
    </section>

    <section class="separado">
        <h2>Noticias</h2>
        @if ($noticias->isEmpty())
            <div class="vacio-estado">No hay noticias publicadas.</div>
        @else
            <div class="rejilla tres separado">
                @foreach ($noticias as $noticia)
                    <article class="tarjeta noticia-card">
                        <img src="{{ $noticia->imagenUrl() }}" alt="{{ $noticia->titulo }}">
                        <h3>{{ $noticia->titulo }}</h3>
                        <p class="pequeno">{{ $noticia->descripcion }}</p>
                        <span class="pequeno">{{ $noticia->created_at->format('d/m/Y') }}</span>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    @unless ($esInvitado)
        <section class="rejilla dos separado">
            <div class="tarjeta">
                <h2>Asistente del proyecto</h2>
                <p class="pequeno">Pregunta por pedidos, lotes, recetas, temperatura o el propio proyecto.</p>
                <div class="chat">
                    <div class="chat-mensajes" id="chat-mensajes">
                        <div class="mensaje bot">
                            Hola. Puedo consultar la base de datos del proyecto.
                        </div>
                        @foreach ($conversacion as $mensaje)
                            <div class="mensaje usuario">{{ $mensaje->pregunta }}</div>
                            <div class="mensaje bot">{{ $mensaje->respuesta }}</div>
                        @endforeach
                    </div>
                    <div class="sugerencias">
                        @foreach ($sugerencias as $sugerencia)
                            <button type="button" class="sugerencia" data-pregunta="{{ $sugerencia }}">{{ $sugerencia }}</button>
                        @endforeach
                    </div>
                    <form class="chat-form" id="chat-form">
                        <input id="chat-entrada" type="text" placeholder="Escribe tu pregunta…" autocomplete="off" maxlength="500">
                        <button type="submit" class="boton primario">Enviar</button>
                    </form>
                </div>
            </div>

            <div class="rejilla" style="gap: 1rem;">
                <div class="tarjeta">
                    <h2>Actividad reciente</h2>
                    @if ($actividad->isEmpty())
                        <div class="vacio-estado">Todavía no hay comandos registrados.</div>
                    @else
                        <ul class="lista-limpia">
                            @foreach ($actividad as $registro)
                                <li class="entre">
                                    <div>
                                        <strong class="mono">{{ $registro->accion }}</strong>
                                        <div class="pequeno">{{ $registro->componente }} · {{ $registro->user?->nombre ?? 'Sistema' }}</div>
                                    </div>
                                    <span class="pequeno">{{ $registro->fecha_hora->format('d/m H:i') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <div class="tarjeta">
                    <h2>Pedidos por entregar</h2>
                    @if ($pedidosRecientes->isEmpty())
                        <div class="vacio-estado">No hay pedidos pendientes.</div>
                    @else
                        <ul class="lista-limpia">
                            @foreach ($pedidosRecientes as $pedido)
                                <li class="entre">
                                    <div>
                                        <strong>{{ $pedido->cliente }}</strong>
                                        <div class="pequeno">{{ $pedido->descripcionCantidad() }}</div>
                                    </div>
                                    <span class="insignia ambar">{{ $pedido->fecha_entrega?->format('d/m') ?? 'sin fecha' }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </section>
    @endunless
@endsection

@unless ($esInvitado)
@push('scripts')
<script>
    (() => {
        const entrada = document.getElementById('buscador');
        const caja = document.getElementById('resultados-busqueda');
        if (!entrada) return;
        let temporizador;

        const pintar = (grupos) => {
            caja.innerHTML = '';
            if (!grupos.length) {
                caja.innerHTML = '<div class="vacio">Sin resultados.</div>';
                caja.classList.add('visible');
                return;
            }
            grupos.forEach((grupo) => {
                const bloque = document.createElement('div');
                bloque.className = 'grupo';
                bloque.innerHTML = `<span>${grupo.tipo}</span>`;
                grupo.items.forEach((item) => {
                    const enlace = document.createElement('a');
                    enlace.href = item.url;
                    enlace.innerHTML = `<strong>${item.titulo}</strong><span class="detalle">${item.detalle}</span>`;
                    bloque.appendChild(enlace);
                });
                caja.appendChild(bloque);
            });
            caja.classList.add('visible');
        };

        entrada.addEventListener('input', () => {
            clearTimeout(temporizador);
            const texto = entrada.value.trim();
            if (texto.length < 2) {
                caja.classList.remove('visible');
                return;
            }
            temporizador = setTimeout(async () => {
                try {
                    const respuesta = await fetch(`{{ route('buscar') }}?q=${encodeURIComponent(texto)}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const datos = await respuesta.json();
                    pintar(datos.grupos || []);
                } catch (error) {
                    caja.classList.remove('visible');
                }
            }, 250);
        });

        const form = document.getElementById('chat-form');
        const campo = document.getElementById('chat-entrada');
        const mensajes = document.getElementById('chat-mensajes');
        const agregar = (texto, tipo) => {
            const div = document.createElement('div');
            div.className = `mensaje ${tipo}`;
            div.textContent = texto;
            mensajes.appendChild(div);
            mensajes.scrollTop = mensajes.scrollHeight;
        };
        const preguntar = async (texto) => {
            if (!texto.trim()) return;
            agregar(texto, 'usuario');
            campo.value = '';
            try {
                const datos = await window.panel.pedir('{{ route('chatbot.responder') }}', {
                    method: 'POST',
                    body: JSON.stringify({ pregunta: texto }),
                });
                agregar(datos.respuesta, 'bot');
            } catch (error) {
                agregar(error.message, 'bot');
            }
        };
        form.addEventListener('submit', (evento) => {
            evento.preventDefault();
            preguntar(campo.value);
        });
        document.querySelectorAll('.sugerencia').forEach((boton) => {
            boton.addEventListener('click', () => preguntar(boton.dataset.pregunta));
        });
    })();
</script>
@endpush
@endunless
