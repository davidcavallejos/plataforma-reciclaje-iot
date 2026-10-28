<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilUsuarioController extends Controller
{
    public function edit(Request $request): View
    {
        return view('perfil.edit', [
            'usuario' => $request->user(),
            'avatares' => config('panel.avatares'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $avatares = array_keys(config('panel.avatares', []));

        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            // Teléfono: solo números y caracteres permitidos de formato (+, -, espacios, paréntesis)
            'telefono' => ['nullable', 'string', 'max:40', 'regex:/^[0-9+\-\s()]+$/'],
            'cargo' => ['nullable', 'string', 'max:80'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'string', Rule::in($avatares)],
        ], [
            'nombre.required' => 'El nombre completo es obligatorio.',
            'telefono.regex' => 'El teléfono solo debe contener números y símbolos válidos (+, -, paréntesis o espacios).',
        ]);

        $request->user()->update([
            'nombre' => $datos['nombre'],
            'telefono' => $datos['telefono'] ?: null,
            'cargo' => $datos['cargo'] ?: null,
            'bio' => $datos['bio'] ?: null,
            'avatar' => $datos['avatar'] ?: null,
        ]);

        return redirect()->route('perfil.edit')->with('estado', 'Perfil actualizado.');
    }
}
