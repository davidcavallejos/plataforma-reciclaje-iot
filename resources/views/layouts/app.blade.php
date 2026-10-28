<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Panel') · Máquina de jabón</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body>
@php
    $usuario = auth()->user();
    $esInvitado = $usuario === null;
    $pestanas = collect(config('panel.pestanas'))->filter(function (array $p) use ($usuario, $esInvitado) {
        if (! \Illuminate\Support\Facades\Route::has($p['ruta'])) {
            return false;
        }

        if ($esInvitado) {
            return $p['ruta'] === 'dashboard';
        }

        return match ($p['permiso'] ?? null) {
            'controlar' => $usuario->puedeControlarMaquina(),
            'reportes' => $usuario->puedeVerReportes(),
            'usuarios' => $usuario->puedeGestionarUsuarios(),
            default => true,
        };
    });

    $pestanaActiva = $pestanas->first(function ($p) {
        $raiz = explode('.', $p['ruta'])[0];
        return request()->routeIs($p['ruta']) || request()->routeIs($raiz.'*');
    });

    $pestanasPorCategoria = $pestanas->groupBy(fn ($item) => $item['categoria'] ?? 'General');
@endphp

<div class="disposicion-panel" id="disposicion-panel">
    {{-- Telón de fondo para dispositivos táctiles/móviles --}}
    <div class="fondo-bloqueo-lateral" id="fondo-bloqueo-lateral" onclick="window.panel.cerrarMenuLateral()" aria-hidden="true"></div>

    {{-- Pestañas a la izquierda organizadas por categorías --}}
    <aside class="barra-lateral" id="barra-lateral" aria-label="Menú del sistema">
        <div class="lateral-cabecera">
            <a href="{{ route('dashboard') }}" class="marca-lateral" title="Ir al Inicio">
                <span class="burbuja">🧼</span>
                <div class="marca-titulos">
                    <strong>Máquina de jabón</strong>
                    <small>Panel de Control</small>
                </div>
            </a>
            <button type="button" class="boton-cerrar-lateral" id="boton-cerrar-lateral" aria-label="Cerrar navegación" onclick="window.panel.cerrarMenuLateral()">
                ✕
            </button>
        </div>

        <nav class="lateral-navegacion" id="lateral-navegacion" aria-label="Navegación principal">
            @foreach ($pestanasPorCategoria as $categoria => $items)
                <div class="lateral-categoria">
                    <div class="lateral-categoria-titulo">
                        <span>{{ $categoria }}</span>
                    </div>
                    <ul class="lateral-pestanas">
                        @foreach ($items as $pestana)
                            @php
                                $raiz = explode('.', $pestana['ruta'])[0];
                                $activa = request()->routeIs($pestana['ruta']) || request()->routeIs($raiz.'*');
                            @endphp
                            <li class="pestana-item">
                                <a href="{{ route($pestana['ruta']) }}"
                                   class="pestana-enlace {{ $activa ? 'activa' : '' }}"
                                   title="{{ $pestana['titulo'] }}"
                                   @if($activa) aria-current="page" @endif>
                                    <span class="pestana-icono" aria-hidden="true">{{ $pestana['icono'] }}</span>
                                    <span class="pestana-texto">{{ $pestana['titulo'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </nav>

        <div class="lateral-pie">
            @if ($esInvitado)
                <div class="usuario-tarjeta-lateral">
                    <div class="usuario-avatar-circulo" aria-hidden="true">👤</div>
                    <div class="usuario-info-lateral">
                        <strong>Invitado</strong>
                        <span>Solo lectura</span>
                    </div>
                </div>
            @else
                <a href="{{ route('perfil.edit') }}" class="usuario-tarjeta-lateral" title="Mi perfil">
                    <div class="usuario-avatar-circulo" aria-hidden="true">
                        {{ $usuario->avatarEmoji() ?? $usuario->inicial() }}
                    </div>
                    <div class="usuario-info-lateral">
                        <strong title="{{ $usuario->nombre }}">{{ $usuario->nombre }}</strong>
                        <span>{{ $usuario->rol->etiqueta() }}</span>
                    </div>
                </a>
            @endif
            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                @csrf
                <button type="submit" class="boton-salir-lateral" title="Cerrar sesión">
                    <span>{{ $esInvitado ? 'Salir' : 'Salir' }}</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- Área principal de contenido --}}
    <div class="area-principal">
        <header class="barra-superior-global">
            <div class="barra-superior-izq">
                <button type="button" class="boton-menu-movil" id="boton-menu-movil" aria-label="Abrir menú de navegación" onclick="window.panel.conmutarMenuLateral()">
                    <span aria-hidden="true">☰</span>
                </button>
                <div class="barra-miga">
                    @if ($pestanaActiva)
                        <span class="barra-miga-categoria">{{ $pestanaActiva['categoria'] ?? 'General' }}</span>
                        <span class="barra-miga-separador">/</span>
                        <span class="barra-miga-actual">{{ $pestanaActiva['titulo'] }}</span>
                    @else
                        <span class="barra-miga-actual">@yield('titulo', 'Panel')</span>
                    @endif
                </div>
            </div>

            <div class="barra-superior-der">
                <div class="badge-conexion" title="Servidor local conectado">
                    <span class="punto"></span>
                    <span>En línea</span>
                </div>
            </div>
        </header>

        <main class="contenedor">
            @if (session('estado'))
                <div class="alerta exito">{{ session('estado') }}</div>
            @endif

            @if ($errors->any())
                <div class="alerta error">
                    <strong>Revisa lo siguiente:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('contenido')
        </main>

        <footer class="pie-pagina">
            Instituto Técnico Nacional de Comercio "Federico Álvarez Plata" (Nocturno) · Panel local
        </footer>
    </div>
</div>

<div class="aviso-flotante" id="aviso-panel" role="status" aria-live="polite"></div>

<script>
    window.panel = {
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        abrirMenuLateral() {
            const lateral = document.getElementById('barra-lateral');
            const fondo = document.getElementById('fondo-bloqueo-lateral');
            if (lateral) lateral.classList.add('abierta');
            if (fondo) fondo.classList.add('activo');
            document.body.classList.add('menu-bloqueado');
        },
        cerrarMenuLateral() {
            const lateral = document.getElementById('barra-lateral');
            const fondo = document.getElementById('fondo-bloqueo-lateral');
            if (lateral) lateral.classList.remove('abierta');
            if (fondo) fondo.classList.remove('activo');
            document.body.classList.remove('menu-bloqueado');
        },
        conmutarMenuLateral() {
            const lateral = document.getElementById('barra-lateral');
            if (lateral && lateral.classList.contains('abierta')) {
                this.cerrarMenuLateral();
            } else {
                this.abrirMenuLateral();
            }
        },
        avisar(mensaje, esError = false) {
            const caja = document.getElementById('aviso-panel');
            caja.textContent = mensaje;
            caja.classList.toggle('error', esError);
            caja.classList.add('visible');
            clearTimeout(window.panel._t);
            window.panel._t = setTimeout(() => caja.classList.remove('visible'), 3200);
        },
        async pedir(url, opciones = {}) {
            const respuesta = await fetch(url, {
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': window.panel.csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                ...opciones,
            });

            let datos = {};
            try { datos = await respuesta.json(); } catch (e) { datos = {}; }

            if (!respuesta.ok) {
                throw new Error(datos.mensaje || 'No se pudo completar la operación.');
            }

            return datos;
        },
    };

    // Cerrar menú móvil con tecla Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            window.panel.cerrarMenuLateral();
        }
    });

    // Cerrar menú móvil al cambiar de tamaño a pantalla ancha
    window.addEventListener('resize', function() {
        if (window.innerWidth >= 992) {
            window.panel.cerrarMenuLateral();
        }
    });
</script>

@stack('scripts')
</body>
</html>
