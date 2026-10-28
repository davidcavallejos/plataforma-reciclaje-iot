<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\Rol;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.registro', [
            // El primer registro del sistema se convierte en administrador.
            'esPrimerUsuario' => ! User::where('rol', Rol::Administrador)->exists(),
        ]);
    }

    public function register(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'nombre.required' => 'Escribe tu nombre completo.',
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
            'email.unique' => 'Ese correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        // El administrador es único: solo el primer registro puede serlo.
        $esPrimero = ! User::where('rol', Rol::Administrador)->exists();

        $user = User::create([
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'rol' => $esPrimero ? Rol::Administrador : Rol::Visualizador,
            'activo' => true,
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with(
            'estado',
            $esPrimero
                ? 'Tu cuenta es la primera del sistema: se te asignó el rol de administrador.'
                : 'Cuenta creada. Tu rol es visualizador; un administrador puede cambiarlo.'
        );
    }
}
