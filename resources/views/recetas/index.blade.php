@extends('layouts.app')

@section('titulo', 'Formulación y recetas')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Formulación y recetas</h1>
            <p>Elige el producto y el aceite. Indica el peso de aceite, agua o álcali; el resto se calcula con valores SAP de referencia.</p>
        </div>
        <form method="GET" action="{{ route('recetas.index') }}" class="fila">
            <input type="search" name="buscar" value="{{ $buscar }}" placeholder="Buscar receta"
                   style="padding:.5rem .7rem;border:1px solid var(--borde);border-radius:9px;">
            <button type="submit" class="boton">Buscar</button>
        </form>
    </div>

    <section class="rejilla dos">
        <div class="tarjeta">
            <h2>Nueva receta</h2>
            <ol class="pasos-guia pequeno">
                <li>Tipo de producto</li>
                <li>Tipo de aceite</li>
                <li>Ingrediente base</li>
                <li>Peso en gramos</li>
            </ol>

            <form method="POST" action="{{ route('recetas.store') }}" id="form-receta">
                @csrf

                <div class="campo">
                    <label for="nombre">Nombre de la receta</label>
                    <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}"
                           placeholder="Jabón de oliva" required maxlength="150">
                </div>

                <div class="campo">
                    <label for="tipo_producto">Tipo de producto</label>
                    <select id="tipo_producto" name="tipo_producto" required>
                        @foreach ($tiposProducto as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('tipo_producto', 'barra') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="campo">
                    <label for="tipo_aceite">Tipo de aceite</label>
                    <select id="tipo_aceite" name="tipo_aceite" required>
                        @foreach ($catalogo['aceites'] as $clave => $aceite)
                            <option value="{{ $clave }}" @selected(old('tipo_aceite') === $clave)>
                                {{ $aceite['nombre'] }} · SAP NaOH {{ number_format((float) $aceite['sap_naoh'], 3) }}
                            </option>
                        @endforeach
                    </select>
                    <span class="ayuda" id="ayuda-producto"></span>
                </div>

                <div class="fila-campos">
                    <div class="campo" style="flex: 2;">
                        <label for="ingrediente_base">Ingrediente base</label>
                        <select id="ingrediente_base" name="ingrediente_base" required>
                            <option value="aceite" @selected(old('ingrediente_base', 'aceite') === 'aceite')>Aceite</option>
                            <option value="agua" @selected(old('ingrediente_base') === 'agua')>Agua</option>
                            <option value="sosa" @selected(old('ingrediente_base') === 'sosa')>Álcali (sosa / potasa)</option>
                        </select>
                    </div>
                    <div class="campo" style="flex: 1.5;">
                        <label for="unidad">Unidad</label>
                        <select id="unidad" name="unidad" required>
                            <option value="g" @selected(old('unidad', 'g') === 'g')>Gramos (g)</option>
                            <option value="kg" @selected(old('unidad') === 'kg')>Kilogramos (kg)</option>
                        </select>
                    </div>
                    <div class="campo" style="flex: 2;">
                        <label for="peso">Peso</label>
                        <input id="peso" type="number" name="peso" step="0.001" min="0.001"
                               value="{{ old('peso', 1000) }}" required placeholder="1000">
                    </div>
                </div>

                <div class="tarjeta sin-borde calculo-caja">
                    <strong class="pequeno">Cálculo de la receta</strong>
                    <div class="rejilla tres" style="gap:.6rem;margin-top:.5rem;">
                        <div class="metrica">
                            <span class="etiqueta">Aceite</span>
                            <span class="valor unidad" id="calc-aceite">—</span>
                        </div>
                        <div class="metrica">
                            <span class="etiqueta">Agua</span>
                            <span class="valor unidad" id="calc-agua">—</span>
                        </div>
                        <div class="metrica">
                            <span class="etiqueta" id="etiqueta-alcali">Álcali</span>
                            <span class="valor unidad" id="calc-sosa">—</span>
                        </div>
                    </div>
                    <div class="pequeno" style="margin-top:.5rem;" id="calc-nota"></div>
                </div>

                <h3 class="separado">Aditivos</h3>
                <p class="pequeno">Se proponen según el tipo de producto; puedes ajustar los gramos.</p>
                <div id="lista-aditivos"></div>
                <button type="button" class="boton minimo" id="agregar-aditivo">Añadir aditivo</button>

                <div class="pie">
                    <button type="submit" class="boton primario bloque">Crear receta</button>
                </div>
            </form>
        </div>

        <div class="tarjeta">
            <div class="entre">
                <h2>Recetas guardadas</h2>
                <span class="insignia gris">{{ $recetas->total() }}</span>
            </div>

            <div class="lista-vertical separado">
                @forelse ($recetas as $receta)
                    <article class="tarjeta-compacta">
                        <div class="entre">
                            <div>
                                <strong>{{ $receta->nombre }}</strong>
                                <div class="pequeno">
                                    {{ $receta->tipo_producto->etiqueta() }}
                                    @if ($receta->tipo_aceite) · {{ $receta->tipo_aceite }} @endif
                                </div>
                            </div>
                            <form method="POST" action="{{ route('recetas.destroy', $receta) }}"
                                  onsubmit="return confirm('¿Eliminar la receta {{ $receta->nombre }}?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="boton minimo">Eliminar</button>
                            </form>
                        </div>
                        <div class="rejilla tres" style="gap:.4rem;margin-top:.6rem;">
                            <div class="metrica">
                                <span class="etiqueta">Aceite</span>
                                <span class="valor unidad">{{ number_format((float) $receta->pesoDe('aceite'), 1) }} g</span>
                            </div>
                            <div class="metrica">
                                <span class="etiqueta">Agua</span>
                                <span class="valor unidad">{{ number_format((float) $receta->pesoDe('agua'), 1) }} g</span>
                            </div>
                            <div class="metrica">
                                <span class="etiqueta">{{ $receta->calculos['alcali_nombre'] ?? 'Álcali' }}</span>
                                <span class="valor unidad">{{ number_format((float) $receta->pesoDe('sosa'), 1) }} g</span>
                            </div>
                        </div>
                        @if (! empty($receta->aditivos))
                            <div class="pequeno separado">
                                Aditivos:
                                {{ collect($receta->aditivos)->map(fn ($a) => $a['nombre'].' ('.$a['gramos'].' g)')->implode(', ') }}
                            </div>
                        @endif
                    </article>
                @empty
                    <div class="vacio-estado">Todavía no hay recetas.</div>
                @endforelse
            </div>

            <div class="separado">{{ $recetas->links() }}</div>
        </div>
    </section>
@endsection

@push('scripts')
<script>
    (() => {
        const catalogo = @json($catalogo);
        const tipo = document.getElementById('tipo_producto');
        const aceite = document.getElementById('tipo_aceite');
        const base = document.getElementById('ingrediente_base');
        const unidad = document.getElementById('unidad');
        const peso = document.getElementById('peso');
        const lista = document.getElementById('lista-aditivos');
        let indice = 0;

        const redondear = (n) => Math.round(n * 100) / 100;

        const calcular = () => {
            const datosAceite = catalogo.aceites[aceite.value];
            const valorIngresado = Number(peso.value);
            const esKg = unidad.value === 'kg';
            // Convertir a gramos para el cálculo interno si el operario seleccionó kg
            const gramos = esKg ? valorIngresado * 1000 : valorIngresado;

            const esBarra = tipo.value === 'barra';
            const cfg = esBarra ? catalogo.barra : catalogo.liquido;

            document.getElementById('ayuda-producto').textContent = esBarra
                ? 'Barra: Sosa cáustica (NaOH), sobreengrasado 5 %, ratio de agua al 38 % sobre peso de aceite.'
                : 'Líquido: Potasa cáustica (KOH = NaOH × 1.4025), sobreengrasado 3 %, dilución base agua = 3 × KOH.';
            document.getElementById('etiqueta-alcali').textContent = cfg.etiqueta_alcali;

            if (!datosAceite || !gramos || gramos <= 0) {
                ['aceite', 'agua', 'sosa'].forEach((i) => {
                    document.getElementById(`calc-${i}`).textContent = '—';
                });
                document.getElementById('calc-nota').textContent = '';
                return null;
            }

            const sap = esBarra ? datosAceite.sap_naoh : datosAceite.sap_naoh * 1.4025;
            const factor = sap * (1 - cfg.sobreengrasado / 100);
            let aceites = gramos;
            if (base.value === 'sosa') aceites = gramos / factor;
            if (base.value === 'agua') aceites = esBarra ? gramos / 0.38 : gramos / (3 * factor);

            const alcali = redondear(aceites * factor);
            const agua = redondear(esBarra ? aceites * 0.38 : alcali * 3);
            aceites = redondear(aceites);

            const formatearPeso = (g) => {
                if (esKg) {
                    return `${(g / 1000).toFixed(3)} kg (${g.toFixed(1)} g)`;
                }
                return `${g.toFixed(2)} g`;
            };

            document.getElementById('calc-aceite').textContent = formatearPeso(aceites);
            document.getElementById('calc-agua').textContent = formatearPeso(agua);
            document.getElementById('calc-sosa').textContent = formatearPeso(alcali);
            const totalGramos = aceites + agua + alcali;
            document.getElementById('calc-nota').textContent =
                `SAP ${sap.toFixed(4)} · Sobreengrasado: ${cfg.sobreengrasado}% · Peso total mezcla: ${esKg ? (totalGramos / 1000).toFixed(3) + ' kg' : totalGramos.toFixed(2) + ' g'}`;

            return { aceites, sugeridos: catalogo.aditivos[tipo.value] || [] };
        };

        const pintarAditivos = (aceites) => {
            const sugeridos = catalogo.aditivos[tipo.value] || [];
            lista.innerHTML = '';
            indice = 0;
            sugeridos.forEach((item) => {
                const g = aceites ? redondear(aceites * item.porcentaje_aceite / 100) : 0;
                agregarFila(item.nombre, g);
            });
        };

        const agregarFila = (nombre = '', gramos = '') => {
            const i = indice++;
            const fila = document.createElement('div');
            fila.className = 'fila';
            fila.style.marginBottom = '.5rem';
            fila.innerHTML = `
                <input type="text" name="aditivos[${i}][nombre]" value="${nombre}" placeholder="Aditivo (ej. Esencia, Glicerina)"
                       style="flex:1;padding:.5rem .7rem;border:1px solid var(--borde);border-radius:9px;">
                <input type="number" name="aditivos[${i}][gramos]" value="${gramos}" placeholder="g" step="0.01" min="0"
                       style="width:110px;padding:.5rem .7rem;border:1px solid var(--borde);border-radius:9px;">
                <span class="pequeno" style="align-self:center;margin-left:.3rem;">g</span>`;
            lista.appendChild(fila);
        };

        const refrescar = () => {
            const r = calcular();
            pintarAditivos(r ? r.aceites : 0);
        };

        document.getElementById('agregar-aditivo').addEventListener('click', () => agregarFila());
        [tipo, aceite, base, unidad, peso].forEach((el) => {
            el.addEventListener('input', refrescar);
            el.addEventListener('change', refrescar);
        });
        refrescar();
    })();
</script>
@endpush
