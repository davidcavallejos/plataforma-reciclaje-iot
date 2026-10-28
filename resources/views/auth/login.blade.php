@extends('layouts.guest')

@section('titulo', 'Iniciar sesión')
@section('encabezado', 'Iniciar sesión')
@section('subtitulo', 'Accede al panel de control de la máquina')

@section('contenido')
    <form method="POST" action="{{ route('login.post') }}">
        @csrf

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
        </div>

        <label class="fila pequeno" style="margin-bottom: 1rem;">
            <input type="checkbox" name="remember" value="1" style="width:auto;">
            Mantener la sesión iniciada
        </label>

        <button type="submit" class="boton primario bloque grande">Entrar</button>
    </form>

    <form method="POST" action="{{ route('login.invitado') }}" class="separado">
        @csrf
        <button type="submit" class="boton bloque">Ingresar como invitado</button>
    </form>
    <p class="pequeno centro">El invitado solo puede ver el inicio y las noticias.</p>
@endsection

@section('pie')
    ¿No tienes cuenta? <a href="{{ route('registro') }}">Solicita tu registro</a>
@endsection
