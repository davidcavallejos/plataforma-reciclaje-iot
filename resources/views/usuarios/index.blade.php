@extends('layouts.app')

@section('titulo', 'Usuarios')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Gestión de usuarios</h1>
            <p>Alta, roles y estado. Cada persona completa su ficha en Mi perfil.</p>
        </div>
        <span class="insignia verde">Administrador: {{ $administradorActual?->nombre ?? 'ninguno' }}</span>
    </div>

    <section class="columna-usuarios">
        <div class="tarjeta">
            <h2>Nuevo usuario</h2>
            <form method="POST" action="{{ route('usuarios.store') }}">
                @csrf
                <div class="campo">
                    <label for="nombre">Nombre completo</label>
                    <input id="nombre" type="text" name="nombre" required maxlength="120" value="{{ old('nombre') }}">
                </div>
                <div class="campo">
                    <label for="email">Correo electrónico</label>
                    <input id="email" type="email" name="email" required maxlength="150" value="{{ old('email') }}">
                </div>
                <div class="campo">
                    <label for="password">Contraseña</label>
                    <input id="password" type="password" name="password" required minlength="8">
                </div>
                <div class="campo">
                    <label for="rol">Rol</label>
                    <select id="rol" name="rol" required>
                        @foreach ($roles as $valor => $etiqueta)
                            <option value="{{ $valor }}" @selected(old('rol', 'visualizador') === $valor)>{{ $etiqueta }}</option>
                        @endforeach
                    </select>
                </div>
                <label class="fila pequeno" style="margin-bottom:1rem;">
                    <input type="checkbox" name="activo" value="1" style="width:auto;" checked>
                    Cuenta activa
                </label>
                <button type="submit" class="boton primario bloque">Crear usuario</button>
            </form>
        </div>

        @foreach ($usuarios as $usuario)
            <article class="tarjeta ficha-usuario {{ $usuario->trashed() ? 'silencio' : '' }}">
                <div class="ficha-usuario-cabecera">
                    <span class="usuario-avatar-circulo grande">
                        {{ $usuario->avatarEmoji() ?? $usuario->inicial() }}
                    </span>
                    <div>
                        <strong>{{ $usuario->nombre }}</strong>
                        <div class="pequeno">{{ $usuario->email }}</div>
                        @if ($usuario->cargo || $usuario->telefono)
                            <div class="pequeno">{{ $usuario->cargo }}{{ $usuario->cargo && $usuario->telefono ? ' · ' : '' }}{{ $usuario->telefono }}</div>
                        @endif
                        @if ($usuario->trashed())
                            <span class="insignia rojo">Dado de baja</span>
                        @endif
                    </div>
                    <span class="insignia {{ $usuario->rol->value === 'administrador' ? 'verde' : 'gris' }}">
                        {{ $usuario->rol->etiqueta() }}
                    </span>
                </div>

                @if ($usuario->bio)
                    <p class="pequeno">{{ $usuario->bio }}</p>
                @endif

                <div class="pequeno">{{ $usuario->recetas_count }} recetas · {{ $usuario->lotes_count }} lotes · {{ $usuario->pedidos_count }} pedidos</div>

                @unless ($usuario->trashed())
                    <form method="POST" action="{{ route('usuarios.update', $usuario) }}" class="form-rol-vertical">
                        @csrf
                        @method('PATCH')
                        <div class="campo">
                            <label>Rol</label>
                            <select name="rol">
                                @foreach ($roles as $valor => $etiqueta)
                                    <option value="{{ $valor }}" @selected($usuario->rol->value === $valor)>{{ $etiqueta }}</option>
                                @endforeach
                            </select>
                        </div>
                        <label class="fila pequeno">
                            <input type="checkbox" name="activo" value="1" style="width:auto;" @checked($usuario->activo)>
                            Activo
                        </label>
                        <button type="submit" class="boton minimo">Guardar</button>
                    </form>
                @endunless

                @unless ($usuario->trashed() || $usuario->id === auth()->id())
                    <form method="POST" action="{{ route('usuarios.destroy', $usuario) }}"
                          onsubmit="return confirm('¿Dar de baja a {{ $usuario->nombre }}?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="boton minimo">Dar de baja</button>
                    </form>
                @endunless
            </article>
        @endforeach
    </section>
@endsection
