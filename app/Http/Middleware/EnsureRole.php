<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a los roles indicados.
 *
 * Uso: ->middleware('rol:administrador,operador')
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null) {
            abort(401);
        }

        if (! in_array($user->rol->value, $roles, true)) {
            abort(403, 'Tu rol no tiene acceso a esta sección.');
        }

        return $next($request);
    }
}
