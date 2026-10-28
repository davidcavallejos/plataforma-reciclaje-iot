<?php

declare(strict_types=1);

use App\Http\Middleware\AccesoPanel;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'rol' => EnsureRole::class,
            'activo' => EnsureUserIsActive::class,
            'acceso.panel' => AccesoPanel::class,
        ]);

        // Laravel redirige aquí a los invitados que intentan entrar al panel.
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
