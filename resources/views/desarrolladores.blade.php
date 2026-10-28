@extends('layouts.app')

@section('titulo', 'Desarrolladores')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Desarrolladores</h1>
            <p>Equipo responsable y herramientas utilizadas en el proyecto.</p>
        </div>
    </div>

    <section class="rejilla dos">
        @foreach ($equipo as $integrante)
            <div class="tarjeta">
                <div class="fila" style="align-items:flex-start;">
                    <div style="width:52px;height:52px;border-radius:50%;display:grid;place-items:center;
                                background:var(--verde-claro);color:var(--verde-oscuro);font-weight:700;font-size:1.1rem;">
                        {{ mb_substr($integrante['nombre'], 0, 1) }}
                    </div>
                    <div style="flex:1;">
                        <h2 style="margin-bottom:.1rem;">{{ $integrante['nombre'] }}</h2>
                        <span class="insignia verde">{{ $integrante['rol'] }}</span>
                        <p class="pequeno" style="margin-top:.7rem;">
                            {{ $integrante['detalle'] ?? 'Sin detalle registrado por el momento.' }}
                        </p>
                    </div>
                </div>
            </div>
        @endforeach
    </section>

    <section class="tarjeta separado">
        <h2>🛠️ Herramientas utilizadas</h2>
        <div class="tabla-envoltura separado">
            <table class="tabla">
                <thead>
                    <tr>
                        <th>Tecnología</th>
                        <th>Uso en el proyecto</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tecnologias as $tecnologia)
                        <tr>
                            <td><strong>{{ $tecnologia['nombre'] }}</strong></td>
                            <td>{{ $tecnologia['uso'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="tarjeta separado">
        <h2>📐 Arquitectura</h2>
        <ul class="lista-limpia">
            <li>
                <strong>Panel web (Laravel 12)</strong>
                <div class="pequeno">Autenticación por roles, CRUD de recetas, perfiles, lotes y pedidos, y auditoría de comandos.</div>
            </li>
            <li>
                <strong>Puente HTTP con el ESP32</strong>
                <div class="pequeno">El servidor valida y envía cada comando al firmware; el navegador nunca habla directo con la placa.</div>
            </li>
            <li>
                <strong>Firmware (ESP32 + 4 × L298N)</strong>
                <div class="pequeno">PWM de 8 bits a 1 kHz, sensores IR de molde y DS18B20 para temperatura.</div>
            </li>
            <li>
                <strong>PostgreSQL 17</strong>
                <div class="pequeno">Integridad referencial estricta, índices parciales y restricciones CHECK.</div>
            </li>
        </ul>
    </section>
@endsection
