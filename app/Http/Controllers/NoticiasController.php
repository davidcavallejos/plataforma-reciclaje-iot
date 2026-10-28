<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Noticia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class NoticiasController extends Controller
{
    public function index(Request $request): View
    {
        $editando = null;
        if ($request->filled('editar')) {
            $editando = Noticia::find($request->integer('editar'));
        }

        return view('noticias.index', [
            'noticias' => Noticia::with('user')->orderByDesc('created_at')->get(),
            'editando' => $editando,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        $ruta = $request->file('imagen')->store('noticias', 'public');

        Noticia::create([
            'user_id' => $request->user()->id,
            'titulo' => $datos['titulo'],
            'descripcion' => $datos['descripcion'],
            'imagen_path' => $ruta,
        ]);

        return redirect()->route('noticias.index')->with('estado', 'Noticia publicada en el inicio.');
    }

    public function update(Request $request, Noticia $noticia): RedirectResponse
    {
        $datos = $request->validate($this->reglas(false), $this->mensajes());

        $noticia->titulo = $datos['titulo'];
        $noticia->descripcion = $datos['descripcion'];

        if ($request->hasFile('imagen')) {
            Storage::disk('public')->delete($noticia->imagen_path);
            $noticia->imagen_path = $request->file('imagen')->store('noticias', 'public');
        }

        $noticia->save();

        return redirect()->route('noticias.index')->with('estado', 'Noticia actualizada.');
    }

    public function destroy(Noticia $noticia): RedirectResponse
    {
        Storage::disk('public')->delete($noticia->imagen_path);
        $noticia->delete();

        return redirect()->route('noticias.index')->with('estado', 'Noticia eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function reglas(bool $imagenObligatoria = true): array
    {
        return [
            'titulo' => ['required', 'string', 'min:4', 'max:160'],
            'descripcion' => ['required', 'string', 'min:10', 'max:2000'],
            'imagen' => array_filter([
                $imagenObligatoria ? 'required' : 'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ]),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        return [
            'titulo.required' => 'Escribe un título.',
            'descripcion.required' => 'Escribe la descripción.',
            'imagen.required' => 'Adjunta una imagen.',
            'imagen.image' => 'El archivo debe ser una imagen.',
            'imagen.max' => 'La imagen no puede superar 2 MB.',
        ];
    }
}
