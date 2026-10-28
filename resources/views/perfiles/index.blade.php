@extends('layouts.app')

@section('titulo', 'Perfiles de funcionamiento')

@php
    $pasosIniciales = old('pasos', $editando?->pasos() ?? []);
@endphp

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Perfiles de funcionamiento</h1>
            <p>Elige cada componente, programa tiempo y velocidad, y define el orden de ejecución. El aceite admite intervalos y repeticiones.</p>
        </div>
        @if ($editando)
            <a href="{{ route('perfiles.index') }}" class="boton">Cancelar edición</a>
        @endif
    </div>

    <section class="rejilla dos">
        <div class="tarjeta">
            <h2>{{ $editando ? 'Editar perfil' : 'Nuevo perfil' }}</h2>

            <form method="POST"
                  action="{{ $editando ? route('perfiles.update', $editando) : route('perfiles.store') }}"
                  id="form-perfil-pasos">
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif

                <div class="campo">
                    <label for="nombre">Nombre del perfil</label>
                    <input id="nombre" type="text" name="nombre" required maxlength="120"
                           value="{{ old('nombre', $editando?->nombre) }}"
                           placeholder="Producción estándar">
                </div>

                <div class="entre">
                    <h3>Secuencia de comandos</h3>
                    <button type="button" class="boton minimo" id="agregar-paso">Añadir componente</button>
                </div>
                <p class="pequeno">El orden de la lista es el orden de ejecución en control de máquina.</p>

                <div id="lista-pasos" class="lista-pasos"></div>

                <label class="fila pequeno" style="margin-bottom:1rem;">
                    <input type="checkbox" name="es_predeterminado" value="1" style="width:auto;"
                           @checked(old('es_predeterminado', $editando?->es_predeterminado))>
                    Usar como perfil predeterminado
                </label>

                <button type="submit" class="boton primario bloque">
                    {{ $editando ? 'Actualizar perfil' : 'Crear perfil' }}
                </button>
            </form>
        </div>

        <div class="tarjeta">
            <div class="entre">
                <h2>Perfiles guardados</h2>
                <span class="insignia gris">{{ $perfiles->count() }}</span>
            </div>

            <div class="lista-vertical separado">
                @forelse ($perfiles as $perfil)
                    @php $pasos = $perfil->pasos(); @endphp
                    <article class="tarjeta-compacta">
                        <div class="entre">
                            <div>
                                <strong>{{ $perfil->nombre }}</strong>
                                @if ($perfil->es_predeterminado)
                                    <span class="insignia verde">Predeterminado</span>
                                @endif
                                <div class="pequeno">{{ $perfil->user?->nombre ?? '—' }} · {{ $perfil->lotes_count }} lote(s)</div>
                            </div>
                            <div class="fila">
                                <a href="{{ route('perfiles.index', ['editar' => $perfil->id]) }}" class="boton minimo">Editar</a>
                                @unless ($perfil->es_predeterminado)
                                    <form method="POST" action="{{ route('perfiles.predeterminado', $perfil) }}">
                                        @csrf
                                        <button type="submit" class="boton minimo">Predeterminar</button>
                                    </form>
                                @endunless
                                <form method="POST" action="{{ route('perfiles.destroy', $perfil) }}"
                                      onsubmit="return confirm('¿Eliminar el perfil {{ $perfil->nombre }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="boton minimo">Eliminar</button>
                                </form>
                            </div>
                        </div>
                        <ol class="secuencia pequeno">
                            @foreach ($pasos as $paso)
                                <li>
                                    {{ $componentes[$paso['componente']] ?? $paso['componente'] }}
                                    · {{ $paso['tiempo_min'] }} min
                                    · PWM {{ $paso['pwm'] }}
                                    @if (($paso['componente'] ?? '') === 'OIL')
                                        · {{ $paso['repeticiones'] ?? 0 }} pulsos
                                        ({{ $paso['tiempo_on_s'] ?? '—' }}/{{ $paso['tiempo_off_s'] ?? '—' }} s)
                                    @endif
                                </li>
                            @endforeach
                        </ol>
                    </article>
                @empty
                    <div class="vacio-estado">No hay perfiles guardados todavía.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (() => {
        const componentes = @json($componentes);
        const pwmDefecto = @json($pwmDefecto);
        const mapa = @json($mapaPwm);
        const iniciales = @json($pasosIniciales);
        const lista = document.getElementById('lista-pasos');
        let indice = 0;

        const pwmDe = (clave) => pwmDefecto[mapa[clave]] ?? 180;

        const plantilla = (paso = {}) => {
            const i = indice++;
            const componente = paso.componente || 'WATER';
            const esAceite = componente === 'OIL';
            const bloque = document.createElement('div');
            bloque.className = 'paso-componente';
            bloque.innerHTML = `
                <div class="paso-cabecera">
                    <strong>Paso <span class="n-paso"></span></strong>
                    <div class="fila">
                        <button type="button" class="boton minimo subir">↑</button>
                        <button type="button" class="boton minimo bajar">↓</button>
                        <button type="button" class="boton minimo quitar">Quitar</button>
                    </div>
                </div>
                <div class="fila-campos">
                    <div class="campo">
                        <label>Componente</label>
                        <select name="pasos[${i}][componente]" class="sel-componente" required>
                            ${Object.entries(componentes).map(([k, n]) =>
                                `<option value="${k}" ${k === componente ? 'selected' : ''}>${n}</option>`
                            ).join('')}
                        </select>
                    </div>
                    <div class="campo">
                        <label>Tiempo (min)</label>
                        <input type="number" name="pasos[${i}][tiempo_min]" min="0.1" max="240" step="0.1"
                               value="${paso.tiempo_min ?? 1}" required>
                    </div>
                    <div class="campo">
                        <label>Velocidad PWM</label>
                        <input type="number" name="pasos[${i}][pwm]" min="0" max="255" step="1"
                               value="${paso.pwm ?? pwmDe(componente)}" required>
                    </div>
                </div>
                <div class="extras-aceite fila-campos" style="${esAceite ? '' : 'display:none'}">
                    <div class="campo">
                        <label>Intervalo encendido (s)</label>
                        <input type="number" name="pasos[${i}][tiempo_on_s]" min="1" max="3600" value="${paso.tiempo_on_s ?? 5}">
                    </div>
                    <div class="campo">
                        <label>Intervalo apagado (s)</label>
                        <input type="number" name="pasos[${i}][tiempo_off_s]" min="1" max="3600" value="${paso.tiempo_off_s ?? 10}">
                    </div>
                    <div class="campo">
                        <label>Repeticiones</label>
                        <input type="number" name="pasos[${i}][repeticiones]" min="1" max="100" value="${paso.repeticiones ?? 6}">
                    </div>
                </div>`;
            return bloque;
        };

        const numerar = () => {
            lista.querySelectorAll('.paso-componente').forEach((el, i) => {
                el.querySelector('.n-paso').textContent = i + 1;
            });
        };

        const agregar = (paso) => {
            const bloque = plantilla(paso);
            lista.appendChild(bloque);
            numerar();
        };

        lista.addEventListener('change', (evento) => {
            const sel = evento.target.closest('.sel-componente');
            if (!sel) return;
            const extra = sel.closest('.paso-componente').querySelector('.extras-aceite');
            extra.style.display = sel.value === 'OIL' ? '' : 'none';
        });

        lista.addEventListener('click', (evento) => {
            const paso = evento.target.closest('.paso-componente');
            if (!paso) return;
            if (evento.target.closest('.quitar')) {
                if (lista.children.length <= 1) {
                    window.panel.avisar('Deja al menos un componente.', true);
                    return;
                }
                paso.remove();
                numerar();
            }
            if (evento.target.closest('.subir') && paso.previousElementSibling) {
                lista.insertBefore(paso, paso.previousElementSibling);
                numerar();
            }
            if (evento.target.closest('.bajar') && paso.nextElementSibling) {
                lista.insertBefore(paso.nextElementSibling, paso);
                numerar();
            }
        });

        document.getElementById('agregar-paso').addEventListener('click', () => agregar({}));

        if (iniciales.length) {
            iniciales.forEach((paso) => agregar(paso));
        } else {
            agregar({ componente: 'FILTER1', tiempo_min: 1, pwm: pwmDe('FILTER1') });
            agregar({ componente: 'WATER', tiempo_min: 2, pwm: pwmDe('WATER') });
            agregar({ componente: 'OIL', tiempo_min: 3, pwm: pwmDe('OIL'), tiempo_on_s: 5, tiempo_off_s: 10, repeticiones: 6 });
        }
    })();
</script>
@endpush
