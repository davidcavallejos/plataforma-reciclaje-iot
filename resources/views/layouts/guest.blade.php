<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'Acceso') · Máquina de jabón</title>
    <link rel="stylesheet" href="{{ asset('css/panel.css') }}">
</head>
<body class="pantalla-ingreso">
    <div class="tarjeta-ingreso">
        <div class="encabezado">
            <span class="burbuja">🧼</span>
            <h1>@yield('encabezado', 'Panel de la máquina')</h1>
            <p class="pequeno">@yield('subtitulo', 'Instituto Técnico Nacional de Comercio "Federico Álvarez Plata"')</p>
        </div>

        @if (session('estado'))
            <div class="alerta exito">{{ session('estado') }}</div>
        @endif

        @if ($errors->any())
            <div class="alerta error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('contenido')

        <p class="pie">@yield('pie')</p>
    </div>
</body>
</html>
