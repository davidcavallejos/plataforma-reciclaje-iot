<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function invitado(Request $request): RedirectResponse
    {
        $request->session()->regenerate();
        $request->session()->put('es_invitado', true);

        return redirect()->route('dashboard')
            ->with('estado', 'Entraste como invitado. Puedes ver el inicio; inicia sesión para el resto del panel.');
    }

    public function login(Request $request): RedirectResponse
    {
        $credenciales = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Escribe tu correo electrónico.',
            'email.email' => 'El correo no tiene un formato válido.',
            'password.required' => 'Escribe tu contraseña.',
        ]);

        if (! Auth::attempt($credenciales, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'El correo o la contraseña no son correctos.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget('es_invitado');

        if (! $request->user()->activo) {
            Auth::logout();
            $request->session()->invalidate();

            throw ValidationException::withMessages([
                'email' => 'Tu cuenta está desactivada. Contacta al administrador.',
            ]);
        }

        return redirect()->intended(route('dashboard'))
            ->with('estado', 'Bienvenido de nuevo, '.$request->user()->nombre.'.');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            Auth::logout();
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('estado', 'Sesión cerrada.');
    }
}
