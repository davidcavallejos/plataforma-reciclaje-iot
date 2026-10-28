@extends('layouts.guest')

@section('titulo', 'Registro')
@section('encabezado', 'Crear cuenta')
@section('subtitulo', 'El registro es público; el administrador puede cambiar tu rol')

@section('contenido')
    @if ($esPrimerUsuario)
        <div class="alerta info">
            Este es el primer registro del sistema: la cuenta se creará con el rol de
            <strong>administrador</strong>.
        </div>
    @endif

    <form method="POST" action="{{ route('registro.post') }}">
        @csrf

        <div class="campo">
            <label for="nombre">Nombre completo</label>
            <input id="nombre" type="text" name="nombre" value="{{ old('nombre') }}" required autofocus autocomplete="name">
        </div>

        <div class="campo">
            <label for="email">Correo electrónico</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
        </div>

        <div class="campo">
            <label for="password">Contraseña</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            <span class="ayuda">Mínimo 8 caracteres.</span>
        </div>

        <div class="campo">
            <label for="password_confirmation">Repetir contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="boton primario bloque grande">Crear cuenta</button>
    </form>
@endsection

@section('pie')
    ¿Ya tienes cuenta? <a href="{{ route('login') }}">Inicia sesión</a>
@endsection
