<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Por favor inicie sesión para acceder a esta área.');
        }

        if ($request->user()->role !== $role) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Acceso denegado. Permisos insuficientes.'], 403);
            }
            return redirect()->route('marketplace')->with('error', 'Acceso denegado. Se requiere el rol de ' . ucfirst($role) . '.');
        }

        return $next($request);
    }
}
