@extends('layouts.app')

@section('titulo', 'Noticias')

@section('contenido')
    <div class="cabecera-pagina">
        <div>
            <h1>Noticias</h1>
            <p>Publicaciones con título, descripción e imagen. Se muestran en el inicio.</p>
        </div>
        @if ($editando)
            <a href="{{ route('noticias.index') }}" class="boton">Cancelar edición</a>
        @endif
    </div>

    <section class="rejilla dos">
        <div class="tarjeta">
            <h2>{{ $editando ? 'Editar noticia' : 'Nueva noticia' }}</h2>

            <form method="POST"
                  action="{{ $editando ? route('noticias.update', $editando) : route('noticias.store') }}"
                  enctype="multipart/form-data">
                @csrf
                @if ($editando)
                    @method('PUT')
                @endif

                <div class="campo">
                    <label for="titulo">Título</label>
                    <input id="titulo" type="text" name="titulo" required maxlength="160"
                           value="{{ old('titulo', $editando?->titulo) }}">
                </div>

                <div class="campo">
                    <label for="descripcion">Descripción</label>
                    <textarea id="descripcion" name="descripcion" rows="5" required maxlength="2000">{{ old('descripcion', $editando?->descripcion) }}</textarea>
                </div>

                <div class="campo">
                    <label for="imagen">Imagen {{ $editando ? '(opcional al editar)' : '' }}</label>
                    <input id="imagen" type="file" name="imagen" accept="image/jpeg,image/png,image/webp"
                           @required(! $editando)>
                    <span class="ayuda">JPG, PNG o WebP. Máximo 2 MB.</span>
                </div>

                @if ($editando)
                    <img src="{{ $editando->imagenUrl() }}" alt="" class="noticia-preview">
                @endif

                <button type="submit" class="boton primario bloque">
                    {{ $editando ? 'Guardar cambios' : 'Publicar noticia' }}
                </button>
            </form>
        </div>

        <div class="tarjeta">
            <div class="entre">
                <h2>Publicadas</h2>
                <span class="insignia gris">{{ $noticias->count() }}</span>
            </div>

            <div class="lista-vertical separado">
                @forelse ($noticias as $noticia)
                    <article class="noticia-admin">
                        <img src="{{ $noticia->imagenUrl() }}" alt="">
                        <div>
                            <strong>{{ $noticia->titulo }}</strong>
                            <p class="pequeno">{{ $noticia->descripcion }}</p>
                            <div class="pequeno">{{ $noticia->created_at->format('d/m/Y H:i') }} · {{ $noticia->user?->nombre }}</div>
                            <div class="fila" style="margin-top:.5rem;">
                                <a href="{{ route('noticias.index', ['editar' => $noticia->id]) }}" class="boton minimo">Editar</a>
                                <form method="POST" action="{{ route('noticias.destroy', $noticia) }}"
                                      onsubmit="return confirm('¿Eliminar esta noticia?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="boton minimo">Borrar</button>
                                </form>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="vacio-estado">Aún no hay noticias.</div>
                @endforelse
            </div>
        </div>
    </section>
@endsection
