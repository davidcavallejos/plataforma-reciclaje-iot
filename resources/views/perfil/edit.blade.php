@extends('layouts.app')

@section('titulo', 'Mi perfil')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Mi perfil</h1>
            <p>Datos adicionales y avatar opcional. El rol lo asigna el administrador.</p>
        </div>
        <span class="insignia verde">{{ $usuario->rol->etiqueta() }}</span>
    </div>

    <section class="tarjeta perfil-ficha" style="max-width:640px;">
        <form method="POST" action="{{ route('perfil.update') }}">
            @csrf
            @method('PUT')

            <div class="campo">
                <label for="nombre">Nombre</label>
                <input id="nombre" type="text" name="nombre" required maxlength="120"
                       value="{{ old('nombre', $usuario->nombre) }}">
            </div>

            <div class="campo">
                <label>Correo</label>
                <input type="email" value="{{ $usuario->email }}" disabled>
            </div>

            <div class="fila-campos">
                <div class="campo">
                    <label for="telefono">Teléfono (solo números y caracteres telefónicos)</label>
                    <input id="telefono" type="tel" name="telefono" maxlength="40"
                           pattern="[0-9+\-\s()]+"
                           title="Solo números, +, -, paréntesis y espacios"
                           oninput="this.value = this.value.replace(/[^0-9+\-\s()]/g, '')"
                           placeholder="+591 71234567"
                           value="{{ old('telefono', $usuario->telefono) }}">
                </div>
                <div class="campo">
                    <label for="cargo">Cargo o área</label>
                    <input id="cargo" type="text" name="cargo" maxlength="80"
                           value="{{ old('cargo', $usuario->cargo) }}" placeholder="Ej. Operador de planta">
                </div>
            </div>

            <div class="campo">
                <label for="bio">Notas</label>
                <textarea id="bio" name="bio" rows="3" maxlength="500">{{ old('bio', $usuario->bio) }}</textarea>
            </div>

            <div class="campo">
                <span class="etiqueta-campo">Avatar (opcional)</span>
                <div class="selector-avatares">
                    <label class="avatar-opcion">
                        <input type="radio" name="avatar" value="" @checked(old('avatar', $usuario->avatar) === null || old('avatar', $usuario->avatar) === '')>
                        <span class="usuario-avatar-circulo">{{ $usuario->inicial() }}</span>
                    </label>
                    @foreach ($avatares as $clave => $emoji)
                        <label class="avatar-opcion">
                            <input type="radio" name="avatar" value="{{ $clave }}" @checked(old('avatar', $usuario->avatar) === $clave)>
                            <span class="usuario-avatar-circulo">{{ $emoji }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <button type="submit" class="boton primario bloque">Guardar perfil</button>
        </form>
    </section>
@endsection
