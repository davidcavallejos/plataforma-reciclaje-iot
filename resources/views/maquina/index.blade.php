@extends('layouts.app')

@section('titulo', 'Control de la máquina')

@php
    $componentesPanel = [
        ['clave' => 'MOTORES', 'titulo' => 'Motores 1 y 2', 'nota' => 'L298N #1 · ENA/ENB', 'valor' => $pwm['l298n1'], 'pwm' => 'V %d', 'encender' => 'START', 'apagar' => 'STOP', 'estado' => 'motores'],
        ['clave' => 'WATER', 'titulo' => 'Bomba de agua', 'nota' => 'L298N #2 · ENA', 'valor' => $pwm['water'], 'pwm' => 'WATER %d', 'encender' => 'WATER ON', 'apagar' => 'WATER OFF', 'estado' => 'agua'],
        ['clave' => 'OIL', 'titulo' => 'Bomba de aceite', 'nota' => 'L298N #2 · ENB', 'valor' => $pwm['oil'], 'pwm' => 'OIL %d', 'encender' => 'OIL ON', 'apagar' => 'OIL OFF', 'estado' => 'aceite'],
        ['clave' => 'FILTER1', 'titulo' => 'Filtro 1', 'nota' => 'L298N #3 · ENA', 'valor' => $pwm['filter1'], 'pwm' => 'FILTER1 %d', 'encender' => 'FILTER1 ON', 'apagar' => 'FILTER1 OFF', 'estado' => 'filter1'],
        ['clave' => 'FILTER2', 'titulo' => 'Filtro 2', 'nota' => 'L298N #3 · ENB', 'valor' => $pwm['filter2'], 'pwm' => 'FILTER2 %d', 'encender' => 'FILTER2 ON', 'apagar' => 'FILTER2 OFF', 'estado' => 'filter2'],
        ['clave' => 'MOTOR3', 'titulo' => 'Motoreductor 3', 'nota' => 'L298N #4 · ENA', 'valor' => $pwm['motor3'], 'pwm' => 'MOTOR3 SPEED %d', 'encender' => 'MOTOR3 CW', 'apagar' => 'MOTOR3 OFF', 'estado' => 'motor3'],
        ['clave' => 'PUMP4', 'titulo' => 'Bomba 4', 'nota' => 'L298N #4 · ENB', 'valor' => $pwm['pump4'], 'pwm' => 'PUMP4 %d', 'encender' => 'PUMP4 ON', 'apagar' => 'PUMP4 OFF', 'estado' => 'bomba4'],
    ];
@endphp

@section('contenido')
    @php
        // Mapa que consume el JavaScript para saber qué comando encender/apagar
        // corresponde a cada componente (se calcula aquí para no anidar
        // expresiones complejas dentro de @json).
        $accionesJs = collect($componentesPanel)->mapWithKeys(fn (array $componente) => [
            $componente['clave'] => [
                'on' => $componente['encender'],
                'off' => $componente['apagar'],
                'pwm' => $componente['pwm'],
            ],
        ])->all();
        $perfilesJs = $perfiles->map(fn ($perfil) => [
            'id' => $perfil->id,
            'nombre' => $perfil->nombre,
            'pasos' => $perfil->pasos(),
        ])->values();
    @endphp
    <div class="cabecera-pagina">
        <div>
            <h1>Control de la máquina</h1>
            <p>Estado en vivo del ESP32, velocidades PWM, temporizadores y programación horaria.</p>
        </div>
        <div class="fila">
            <span class="insignia gris" id="chip-conexion"><span class="punto"></span> Conectando…</span>
            <span class="insignia gris" id="chip-hora">—</span>
        </div>
    </div>

    <section class="rejilla cuatro">
        <div class="sensor">
            <span class="nombre">Temperatura</span>
            <span class="dato" id="valor-temperatura">— <small>°C</small></span>
        </div>
        <div class="sensor">
            <span class="nombre">IR1 · molde</span>
            <span class="dato" id="valor-ir1">—</span>
        </div>
        <div class="sensor">
            <span class="nombre">IR2 · molde</span>
            <span class="dato" id="valor-ir2">—</span>
        </div>
        <div class="sensor">
            <span class="nombre">Motores 1 y 2</span>
            <span class="dato" id="valor-motores">—</span>
        </div>
    </section>

    <section class="rejilla dos separado">
        <div class="tarjeta">
            <div class="entre">
                <h2>🎚️ Perfil de funcionamiento</h2>
                @if ($perfilActivo)
                    <span class="insignia verde">{{ $perfilActivo->nombre }}</span>
                @else
                    <span class="insignia ambar">Sin perfil</span>
                @endif
            </div>

            <form id="form-perfil" class="fila separado">
                <select name="perfil_id" id="perfil_id" style="flex:1; padding:.55rem .7rem; border:1px solid var(--borde); border-radius:9px;">
                    @forelse ($perfiles as $perfil)
                        <option value="{{ $perfil->id }}" @selected($perfilActivo?->id === $perfil->id)>
                            {{ $perfil->nombre }}{{ $perfil->es_predeterminado ? ' (predeterminado)' : '' }}
                        </option>
                    @empty
                        <option value="">No hay perfiles guardados</option>
                    @endforelse
                </select>
                <button type="submit" class="boton" @disabled($perfiles->isEmpty())>Aplicar velocidades</button>
                <button type="button" class="boton primario" id="ejecutar-perfil" @disabled($perfiles->isEmpty())>Ejecutar secuencia</button>
            </form>

            <ol class="secuencia pequeno separado" id="lista-pasos-activos">
                @foreach ($pasosPerfil as $paso)
                    <li>
                        {{ $componentes[$paso['componente']] ?? $paso['componente'] }}
                        · {{ $paso['tiempo_min'] }} min · PWM {{ $paso['pwm'] }}
                        @if (($paso['componente'] ?? '') === 'OIL')
                            · {{ $paso['repeticiones'] ?? 0 }} pulsos
                        @endif
                    </li>
                @endforeach
            </ol>

            <div class="progreso-bloque separado">
                <div class="entre pequeno">
                    <span id="progreso-texto">Sin ejecución</span>
                    <span id="progreso-pct">0%</span>
                </div>
                <div class="barra-fondo grande"><span id="barra-progreso" style="width:0%"></span></div>
            </div>
        </div>

        <div class="tarjeta">
            <h2>🚨 Parada de la máquina</h2>
            <p class="pequeno">
                <code>ALLSTOP</code> apaga motores y bombas por software. No sustituye al
                interruptor físico de corte de alimentación.
            </p>
            <button type="button" class="boton peligro bloque grande" data-comando="ALLSTOP">
                Parada total (ALLSTOP)
            </button>
            <div class="grupo-botones separado">
                <button type="button" class="boton" data-comando="FSTOP">Apagar filtros</button>
                <button type="button" class="boton" data-comando="PSTOP">Apagar bombas</button>
            </div>
            <div class="pie pequeno">
                Conectado a <span class="mono">{{ config('services.esp32.url') }}</span>
                · modo ESP32 (no hay simulación: si la placa está apagada los comandos se registran como fallidos).
            </div>
        </div>
    </section>

    <h2 class="separado">Componentes</h2>
    <section class="rejilla tres">
        @foreach ($componentesPanel as $componente)
            <div class="tarjeta componente">
                <div class="titulo">
                    <h3>{{ $componente['titulo'] }}</h3>
                    <span class="insignia gris" data-estado="{{ $componente['estado'] }}">—</span>
                </div>
                <div class="pequeno">{{ $componente['nota'] }}</div>

                <div class="acciones">
                    <button type="button" class="boton suave" data-comando="{{ $componente['encender'] }}">Encender</button>
                    <button type="button" class="boton" data-comando="{{ $componente['apagar'] }}">Apagar</button>
                </div>

                <div class="pwm">
                    <input type="number" min="0" max="255" step="1"
                           value="{{ $componente['valor'] }}"
                           data-pwm="{{ $componente['pwm'] }}"
                           aria-label="PWM de {{ $componente['titulo'] }}">
                    <button type="button" class="boton minimo" data-aplicar-pwm>Aplicar PWM</button>
                    <span>0–255</span>
                </div>
            </div>
        @endforeach
    </section>

    <section class="rejilla dos separado">
        <div class="tarjeta">
            <h2>⏱️ Temporizador</h2>
            <p class="pequeno">Enciende un componente y lo apaga automáticamente al cumplirse los minutos indicados.</p>

            <form id="form-temporizador">
                <div class="fila-campos">
                    <div class="campo">
                        <label for="temp-componente">Componente</label>
                        <select id="temp-componente" name="componente" required>
                            @foreach ($componentes as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="campo">
                        <label for="temp-minutos">Tiempo (minutos)</label>
                        <input id="temp-minutos" type="number" name="minutos" min="0.1" max="1440" step="0.1" value="1" required placeholder="Minutos (ej. 1, 2.5)">
                    </div>
                </div>
                <button type="submit" class="boton primario">Encender por el tiempo indicado</button>
            </form>

            <hr class="separado" style="border:0;border-top:1px dashed var(--borde);">

            <h3>🕗 Programación por hora</h3>
            <form id="form-diaria">
                <div class="fila-campos">
                    <div class="campo">
                        <label for="diaria-componente">Componente</label>
                        <select id="diaria-componente" name="componente" required>
                            @foreach ($componentes as $clave => $nombre)
                                <option value="{{ $clave }}">{{ $nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="campo">
                        <label for="diaria-accion">Acción</label>
                        <select id="diaria-accion" name="accion" required>
                            <option value="on">Encender</option>
                            <option value="off">Apagar</option>
                        </select>
                    </div>
                    <div class="campo">
                        <label for="diaria-hora">Hora</label>
                        <input id="diaria-hora" type="time" name="hora" value="08:00" required>
                    </div>
                </div>
                <button type="submit" class="boton primario">Programar todos los días</button>
            </form>
        </div>

        <div class="tarjeta">
            <div class="entre">
                <h2>📅 Programaciones activas</h2>
                <span class="insignia gris" id="contador-programaciones">{{ $programaciones->count() }}</span>
            </div>

            <div class="tabla-envoltura">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Comando</th>
                            <th>Cuándo</th>
                            <th>Creada por</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="tabla-programaciones">
                        @forelse ($programaciones as $programacion)
                            <tr data-programacion="{{ $programacion->id }}">
                                <td class="mono">{{ $programacion->comando }}</td>
                                <td>{{ $programacion->descripcion() }}</td>
                                <td>{{ $programacion->user?->nombre ?? 'Sistema' }}</td>
                                <td class="derecha">
                                    <button type="button" class="boton minimo" data-cancelar="{{ $programacion->id }}">Cancelar</button>
                                </td>
                            </tr>
                        @empty
                            <tr id="fila-vacia"><td colspan="4" class="centro pequeno">No hay programaciones activas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <section class="tarjeta separado">
        <div class="entre">
            <h2>📦 Pedidos por gestionar</h2>
            <span class="insignia gris">{{ $pedidosAbiertos->count() }}</span>
        </div>

        @if ($pedidosAbiertos->isEmpty())
            <div class="vacio-estado separado">No hay pedidos abiertos. Los nuevos aparecerán aquí.</div>
        @else
            <div class="tabla-envoltura separado">
                <table class="tabla">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>Cantidad</th>
                            <th>Entrega</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pedidosAbiertos as $pedido)
                            <tr>
                                <td><strong>{{ $pedido->cliente }}</strong></td>
                                <td class="pequeno">{{ $pedido->descripcionCantidad() }}</td>
                                <td class="pequeno">{{ $pedido->fecha_entrega?->format('d/m/Y') ?? '—' }}</td>
                                <td><span class="insignia ambar">{{ $pedido->estado->etiqueta() }}</span></td>
                                <td class="derecha" style="white-space:nowrap;">
                                    <form method="POST" action="{{ route('pedidos.estado', $pedido) }}" style="display:inline;">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="estado" value="completado">
                                        <button type="submit" class="boton minimo">Completar</button>
                                    </form>
                                    <form method="POST" action="{{ route('pedidos.estado', $pedido) }}" style="display:inline;"
                                          onsubmit="return confirm('¿Cancelar el pedido de {{ $pedido->cliente }}?');">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="estado" value="cancelado">
                                        <button type="submit" class="boton minimo">Cancelar</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <p class="pequeno separado">
            El alta de pedidos y su relación con los lotes se gestiona en
            <a href="{{ route('lotes.index') }}">Lotes y pedidos</a>.
        </p>
    </section>
@endsection

@push('scripts')
<script>
    (() => {
        const rutas = {
            estado: '{{ route('maquina.estado') }}',
            comando: '{{ route('maquina.comando') }}',
            perfil: '{{ route('maquina.perfil') }}',
            programar: '{{ route('maquina.programar') }}',
            procesar: '{{ route('maquina.procesar') }}',
            programacion: '{{ url('/maquina/programacion') }}',
        };

        const acciones = @json($accionesJs);

        // -------------------------------------------------------- Envío de comandos
        const enviar = async (comando) => {
            try {
                const datos = await window.panel.pedir(rutas.comando, {
                    method: 'POST',
                    body: JSON.stringify({ comando }),
                });

                if (datos.ok) {
                    window.panel.avisar(`${comando} → enviado`);
                } else {
                    window.panel.avisar(datos.mensaje || `${comando} no se pudo enviar`, true);
                }
            } catch (error) {
                window.panel.avisar(error.message, true);
            }
        };

        document.querySelectorAll('[data-comando]').forEach((boton) => {
            boton.addEventListener('click', () => enviar(boton.dataset.comando));
        });

        document.querySelectorAll('[data-aplicar-pwm]').forEach((boton) => {
            boton.addEventListener('click', () => {
                const entrada = boton.closest('.pwm').querySelector('input[data-pwm]');
                const valor = Number(entrada.value);

                if (!Number.isInteger(valor) || valor < 0 || valor > 255) {
                    window.panel.avisar('El PWM debe ser un entero entre 0 y 255.', true);
                    return;
                }

                enviar(entrada.dataset.pwm.replace('%d', String(valor)));
            });
        });

        // --------------------------------------------------------- Estado en vivo
        const pintar = (esp32) => {
            const conexion = document.getElementById('chip-conexion');
            conexion.className = `insignia ${esp32.conectado ? 'verde' : 'rojo'}`;
            conexion.innerHTML = `<span class="punto ${esp32.conectado ? 'activo' : 'inactivo'}"></span> ` +
                (esp32.conectado ? 'ESP32 en línea' : 'ESP32 sin conexión');

            document.getElementById('chip-hora').textContent =
                `Servidor ${new Date().toLocaleTimeString('es-BO')}`;

            const temperatura = document.getElementById('valor-temperatura');
            temperatura.innerHTML = esp32.temperatura !== null
                ? `${Number(esp32.temperatura).toFixed(1)} <small>°C</small>`
                : '— <small>°C</small>';

            const ir = (valor) => (valor === null ? '—' : (valor ? 'Molde lleno' : 'Libre'));
            document.getElementById('valor-ir1').textContent = ir(esp32.ir1);
            document.getElementById('valor-ir2').textContent = ir(esp32.ir2);
            document.getElementById('valor-motores').textContent = esp32.motores ? 'Encendidos' : 'Detenidos';

            const estados = {
                motores: esp32.motores ? 'Encendidos' : 'Detenidos',
                agua: esp32.agua > 0 ? `PWM ${esp32.agua}` : 'Apagada',
                aceite: '—',
                filter1: '—',
                filter2: '—',
                motor3: esp32.motor3,
                bomba4: esp32.bomba4 > 0 ? `PWM ${esp32.bomba4}` : 'Apagada',
            };

            document.querySelectorAll('[data-estado]').forEach((insignia) => {
                const estado = estados[insignia.dataset.estado];
                insignia.textContent = estado ?? '—';
                insignia.className = 'insignia ' + (
                    ['Encendidos', 'CW', 'CCW'].includes(estado) || String(estado).startsWith('PWM')
                        ? 'verde'
                        : estado === '—' ? 'gris' : 'gris'
                );
            });
        };

        const consultarEstado = async () => {
            try {
                const datos = await window.panel.pedir(rutas.estado);
                pintar(datos.esp32);
            } catch (error) {
                document.getElementById('chip-conexion').className = 'insignia rojo';
                document.getElementById('chip-conexion').innerHTML =
                    '<span class="punto inactivo"></span> Panel sin respuesta';
            }
        };

        const procesarProgramaciones = async () => {
            try {
                const datos = await window.panel.pedir(rutas.procesar, { method: 'POST', body: '{}' });

                if (datos.ejecutadas.length) {
                    datos.ejecutadas.forEach((ejecucion) => {
                        window.panel.avisar(
                            `Programación ejecutada: ${ejecucion.comando}` + (ejecucion.ok ? '' : ' (falló)'),
                            !ejecucion.ok
                        );
                        const fila = document.querySelector(`[data-programacion="${ejecucion.id}"]`);
                        if (fila) fila.remove();
                    });
                    actualizarContador();
                }
            } catch (error) {
                // Silencioso: el sondeo no debe interrumpir al operario.
            }
        };

        const actualizarContador = () => {
            const total = document.querySelectorAll('#tabla-programaciones [data-programacion]').length;
            document.getElementById('contador-programaciones').textContent = total;

            const vacia = document.getElementById('fila-vacia');
            if (vacia && total > 0) vacia.remove();
        };

        // ------------------------------------------------------------ Perfil PWM
        document.getElementById('form-perfil').addEventListener('submit', async (evento) => {
            evento.preventDefault();
            const perfilId = document.getElementById('perfil_id').value;

            if (!perfilId) return;

            try {
                const datos = await window.panel.pedir(rutas.perfil, {
                    method: 'POST',
                    body: JSON.stringify({ perfil_id: Number(perfilId) }),
                });
                const fallidos = datos.enviados.filter((envio) => !envio.ok).length;
                window.panel.avisar(
                    fallidos === 0
                        ? `Perfil «${datos.perfil}» aplicado (${datos.enviados.length} comandos).`
                        : `Perfil aplicado con ${fallidos} comando(s) fallido(s).`,
                    fallidos > 0
                );
            } catch (error) {
                window.panel.avisar(error.message, true);
            }
        });

        // --------------------------------------------------------- Perfiles y ejecución de secuencia
        const perfiles = @json($perfilesJs);
        let ejecucionEnCurso = false;
        let cancelarEjecucionActual = null;

        const pintarPasosPerfil = (perfilId) => {
            const p = perfiles.find((item) => Number(item.id) === Number(perfilId));
            const lista = document.getElementById('lista-pasos-activos');
            if (!lista) return;
            lista.innerHTML = '';
            if (!p || !p.pasos || !p.pasos.length) {
                lista.innerHTML = '<li>Sin pasos configurados en este perfil.</li>';
                return;
            }
            p.pasos.forEach((paso) => {
                const li = document.createElement('li');
                let texto = `${paso.componente} · ${paso.tiempo_min} min · PWM ${paso.pwm}`;
                if (paso.componente === 'OIL') {
                    texto += ` · ${paso.repeticiones || 0} pulsos (${paso.tiempo_on_s || 0}/${paso.tiempo_off_s || 0} s)`;
                }
                li.textContent = texto;
                lista.appendChild(li);
            });
        };

        const selectPerfil = document.getElementById('perfil_id');
        if (selectPerfil) {
            selectPerfil.addEventListener('change', () => {
                pintarPasosPerfil(selectPerfil.value);
            });
        }

        const actualizarBarra = (pct, texto) => {
            const barra = document.getElementById('barra-progreso');
            const textoEl = document.getElementById('progreso-texto');
            const pctEl = document.getElementById('progreso-pct');
            const porcentaje = Math.max(0, Math.min(100, Math.round(pct)));
            if (barra) barra.style.width = `${porcentaje}%`;
            if (pctEl) pctEl.textContent = `${porcentaje}%`;
            if (textoEl) textoEl.textContent = texto;
        };

        const dormir = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

        const ejecutarSecuenciaPerfil = async () => {
            if (ejecucionEnCurso) {
                window.panel.avisar('Ya hay una secuencia en ejecución. Pulsa Parada si deseas interrumpirla.', true);
                return;
            }
            const perfilId = Number(document.getElementById('perfil_id').value);
            const perfil = perfiles.find((item) => Number(item.id) === perfilId);
            if (!perfil || !perfil.pasos || !perfil.pasos.length) {
                window.panel.avisar('El perfil seleccionado no tiene pasos definidos.', true);
                return;
            }

            ejecucionEnCurso = true;
            let cancelado = false;
            cancelarEjecucionActual = () => { cancelado = true; };

            window.panel.avisar(`Iniciando secuencia del perfil «${perfil.nombre}»...`);
            actualizarBarra(0, `Iniciando «${perfil.nombre}»...`);

            const pasos = perfil.pasos;
            // Cálculo del tiempo total en segundos de la secuencia
            let duracionTotalSegundos = 0;
            pasos.forEach((p) => {
                duracionTotalSegundos += Math.max(1, (Number(p.tiempo_min) || 1) * 60);
            });
            let segundosTranscurridos = 0;

            try {
                for (let i = 0; i < pasos.length; i++) {
                    if (cancelado) break;
                    const paso = pasos[i];
                    const comp = paso.componente;
                    const pwmVal = Number(paso.pwm) || 150;
                    const duracionPasoSeg = Math.max(1, (Number(paso.tiempo_min) || 1) * 60);
                    const accion = acciones[comp];

                    actualizarBarra(
                        (segundosTranscurridos / duracionTotalSegundos) * 100,
                        `Paso ${i + 1}/${pasos.length}: ${comp} (${paso.tiempo_min} min, PWM ${pwmVal})`
                    );

                    // 1. Enviar velocidad PWM
                    if (accion && accion.pwm) {
                        await enviar(accion.pwm.replace('%d', String(pwmVal)));
                        await dormir(400);
                    }

                    // 2. Ejecutar lógica según sea componente general o aceite con intervalos
                    if (comp === 'OIL' && paso.repeticiones && paso.tiempo_on_s && paso.tiempo_off_s) {
                        const reps = Number(paso.repeticiones);
                        const tOn = Number(paso.tiempo_on_s);
                        const tOff = Number(paso.tiempo_off_s);

                        for (let r = 0; r < reps; r++) {
                            if (cancelado) break;
                            actualizarBarra(
                                (segundosTranscurridos / duracionTotalSegundos) * 100,
                                `Aceite: pulso ${r + 1}/${reps} (ON ${tOn}s)`
                            );
                            if (accion) await enviar(accion.on);
                            for (let s = 0; s < tOn; s++) {
                                if (cancelado) break;
                                await dormir(1000);
                                segundosTranscurridos++;
                                actualizarBarra((segundosTranscurridos / duracionTotalSegundos) * 100, `Aceite: pulso ${r + 1}/${reps} (ON)`);
                            }
                            if (accion) await enviar(accion.off);
                            if (cancelado) break;

                            actualizarBarra(
                                (segundosTranscurridos / duracionTotalSegundos) * 100,
                                `Aceite: intervalo descanso (OFF ${tOff}s)`
                            );
                            for (let s = 0; s < tOff; s++) {
                                if (cancelado) break;
                                await dormir(1000);
                                segundosTranscurridos++;
                                actualizarBarra((segundosTranscurridos / duracionTotalSegundos) * 100, `Aceite: pausa ${r + 1}/${reps}`);
                            }
                        }
                    } else {
                        // Componente estándar: Encender durante tiempo_min y apagar
                        if (accion) await enviar(accion.on);
                        for (let s = 0; s < duracionPasoSeg; s++) {
                            if (cancelado) break;
                            await dormir(1000);
                            segundosTranscurridos++;
                            actualizarBarra(
                                (segundosTranscurridos / duracionTotalSegundos) * 100,
                                `Paso ${i + 1}/${pasos.length}: ${comp} (${Math.round(duracionPasoSeg - s)} s restantes)`
                            );
                        }
                        if (accion) await enviar(accion.off);
                    }
                }

                if (cancelado) {
                    actualizarBarra(0, 'Secuencia cancelada por usuario.');
                    window.panel.avisar('Secuencia del perfil detenida.', true);
                } else {
                    actualizarBarra(100, `Completada secuencia de «${perfil.nombre}»`);
                    window.panel.avisar(`¡Secuencia del perfil «${perfil.nombre}» finalizada con éxito!`);
                }
            } catch (err) {
                window.panel.avisar('Error en ejecución del perfil: ' + err.message, true);
                actualizarBarra(0, 'Error en ejecución');
            } finally {
                ejecucionEnCurso = false;
                cancelarEjecucionActual = null;
            }
        };

        const btnEjecutarPerfil = document.getElementById('ejecutar-perfil');
        if (btnEjecutarPerfil) {
            btnEjecutarPerfil.addEventListener('click', ejecutarSecuenciaPerfil);
        }

        // Si se pulsa Parada total, abortar secuencia en JavaScript
        document.querySelectorAll('[data-comando="ALLSTOP"]').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (cancelarEjecucionActual) {
                    cancelarEjecucionActual();
                }
            });
        });

        // --------------------------------------------------------- Temporizador
        document.getElementById('form-temporizador').addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const componente = document.getElementById('temp-componente').value;
            const minutos = parseFloat(document.getElementById('temp-minutos').value);
            const accion = acciones[componente];

            if (!accion || isNaN(minutos) || minutos <= 0) return;

            const segundos = Math.max(1, Math.round(minutos * 60));

            // 1) Encender ahora
            await enviar(accion.on);

            // 2) Programar el apagado
            try {
                const datos = await window.panel.pedir(rutas.programar, {
                    method: 'POST',
                    body: JSON.stringify({
                        comando: accion.off,
                        componente,
                        tipo: 'retardo',
                        minutos,
                        segundos,
                    }),
                });

                agregarProgramacion(datos.programacion, 'Temporizador');
                window.panel.avisar(`Se apagará ${accion.off} en ${minutos} min (${segundos} s).`);
            } catch (error) {
                window.panel.avisar(error.message, true);
            }
        });

        // ------------------------------------------------------- Programación diaria
        document.getElementById('form-diaria').addEventListener('submit', async (evento) => {
            evento.preventDefault();

            const componente = document.getElementById('diaria-componente').value;
            const accion = document.getElementById('diaria-accion').value;
            const hora = document.getElementById('diaria-hora').value;
            const comando = acciones[componente][accion];

            try {
                const datos = await window.panel.pedir(rutas.programar, {
                    method: 'POST',
                    body: JSON.stringify({ comando, componente, tipo: 'diaria', hora }),
                });

                agregarProgramacion(datos.programacion, 'Diaria');
                window.panel.avisar(`Programado: ${comando} a las ${hora}.`);
            } catch (error) {
                window.panel.avisar(error.message, true);
            }
        });

        const agregarProgramacion = (programacion, autor) => {
            const vacia = document.getElementById('fila-vacia');
            if (vacia) vacia.remove();

            const fila = document.createElement('tr');
            fila.dataset.programacion = programacion.id;
            fila.innerHTML = `
                <td class="mono">${programacion.comando}</td>
                <td>${programacion.descripcion}</td>
                <td>${autor}</td>
                <td class="derecha">
                    <button type="button" class="boton minimo" data-cancelar="${programacion.id}">Cancelar</button>
                </td>`;
            document.getElementById('tabla-programaciones').appendChild(fila);
            actualizarContador();
        };

        // ------------------------------------------------------------- Cancelar
        document.addEventListener('click', async (evento) => {
            const boton = evento.target.closest('[data-cancelar]');
            if (!boton) return;

            try {
                await window.panel.pedir(`${rutas.programacion}/${boton.dataset.cancelar}`, { method: 'DELETE' });
                boton.closest('tr').remove();
                actualizarContador();
                window.panel.avisar('Programación cancelada.');
            } catch (error) {
                window.panel.avisar(error.message, true);
            }
        });

        // --------------------------------------------------------------- Sondeo
        consultarEstado();
        setInterval(consultarEstado, 2000);
        setInterval(procesarProgramaciones, 5000);
        actualizarContador();
    })();
</script>
@endpush
