<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permite el panel a usuarios autenticados activos, o al modo invitado
 * únicamente en la pantalla de inicio.
 */
class AccesoPanel
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            if (! $user->activo) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')
                    ->withErrors(['email' => 'Tu cuenta está desactivada. Contacta al administrador.']);
            }

            $request->session()->forget('es_invitado');

            return $next($request);
        }

        if ($request->session()->get('es_invitado') === true && $request->routeIs('dashboard')) {
            return $next($request);
        }

        return redirect()->route('login');
    }
};
