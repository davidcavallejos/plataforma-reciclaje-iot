<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DesarrolladorController;
use App\Http\Controllers\LotesController;
use App\Http\Controllers\MaquinaController;
use App\Http\Controllers\NoticiasController;
use App\Http\Controllers\PedidosController;
use App\Http\Controllers\PerfilesController;
use App\Http\Controllers\PerfilUsuarioController;
use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\RecetasController;
use App\Http\Controllers\ReportesController;
use App\Http\Controllers\UsuariosController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.post');
    Route::post('/ingresar-invitado', [LoginController::class, 'invitado'])
        ->middleware('throttle:10,1')
        ->name('login.invitado');

    Route::get('/registro', [RegisterController::class, 'show'])->name('registro');
    Route::post('/registro', [RegisterController::class, 'register'])
        ->middleware('throttle:10,1')
        ->name('registro.post');
});

Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/', [DashboardController::class, 'index'])
    ->middleware('acceso.panel')
    ->name('dashboard');

Route::middleware(['auth', 'activo'])->group(function (): void {
    Route::get('/buscar', [DashboardController::class, 'buscar'])->name('buscar');
    Route::get('/perfil', [PerfilUsuarioController::class, 'edit'])->name('perfil.edit');
    Route::put('/perfil', [PerfilUsuarioController::class, 'update'])->name('perfil.update');

    Route::get('/proyecto', [ProyectoController::class, 'index'])->name('proyecto.index');
    Route::get('/desarrolladores', [DesarrolladorController::class, 'index'])->name('desarrolladores');
    Route::post('/chatbot', [ChatbotController::class, 'responder'])->name('chatbot.responder');

    Route::middleware('rol:administrador,operador')->prefix('maquina')->name('maquina.')->group(function (): void {
        Route::get('/', [MaquinaController::class, 'index'])->name('index');
        Route::get('/estado', [MaquinaController::class, 'estado'])->name('estado');
        Route::post('/comando', [MaquinaController::class, 'comando'])->name('comando');
        Route::post('/perfil', [MaquinaController::class, 'aplicarPerfil'])->name('perfil');
        Route::post('/programar', [MaquinaController::class, 'programar'])->name('programar');
        Route::delete('/programacion/{programacion}', [MaquinaController::class, 'cancelar'])
            ->name('programacion.cancelar');
        Route::post('/procesar', [MaquinaController::class, 'procesar'])->name('procesar');
    });

    Route::middleware('rol:administrador,operador')->group(function (): void {
        Route::get('/recetas', [RecetasController::class, 'index'])->name('recetas.index');
        Route::post('/recetas', [RecetasController::class, 'store'])->name('recetas.store');
        Route::delete('/recetas/{receta}', [RecetasController::class, 'destroy'])->name('recetas.destroy');

        Route::get('/perfiles', [PerfilesController::class, 'index'])->name('perfiles.index');
        Route::post('/perfiles', [PerfilesController::class, 'store'])->name('perfiles.store');
        Route::put('/perfiles/{perfil}', [PerfilesController::class, 'update'])->name('perfiles.update');
        Route::delete('/perfiles/{perfil}', [PerfilesController::class, 'destroy'])->name('perfiles.destroy');
        Route::post('/perfiles/{perfil}/predeterminado', [PerfilesController::class, 'predeterminado'])
            ->name('perfiles.predeterminado');

        Route::get('/lotes', [LotesController::class, 'index'])->name('lotes.index');
        Route::post('/lotes', [LotesController::class, 'store'])->name('lotes.store');
        Route::patch('/lotes/{lote}/estado', [LotesController::class, 'estado'])->name('lotes.estado');
        Route::delete('/lotes/{lote}', [LotesController::class, 'destroy'])->name('lotes.destroy');

        Route::post('/pedidos', [PedidosController::class, 'store'])->name('pedidos.store');
        Route::patch('/pedidos/{pedido}/estado', [PedidosController::class, 'estado'])->name('pedidos.estado');
        Route::delete('/pedidos/{pedido}', [PedidosController::class, 'destroy'])->name('pedidos.destroy');
        Route::post('/pedidos/{pedido}/lotes', [PedidosController::class, 'asignarLote'])
            ->name('pedidos.lotes.asignar');
        Route::delete('/pedidos/{pedido}/lotes/{lote}', [PedidosController::class, 'quitarLote'])
            ->name('pedidos.lotes.quitar');

        Route::get('/reportes', [ReportesController::class, 'index'])->name('reportes.index');
        Route::post('/reportes', [ReportesController::class, 'generar'])->name('reportes.generar');
        Route::get('/reportes/{reporte}/ver', [ReportesController::class, 'ver'])->name('reportes.ver');
        Route::get('/reportes/{reporte}/descargar', [ReportesController::class, 'descargar'])
            ->name('reportes.descargar');
    });

    Route::middleware('rol:administrador')->group(function (): void {
        Route::get('/usuarios', [UsuariosController::class, 'index'])->name('usuarios.index');
        Route::post('/usuarios', [UsuariosController::class, 'store'])->name('usuarios.store');
        Route::patch('/usuarios/{usuario}', [UsuariosController::class, 'update'])->name('usuarios.update');
        Route::delete('/usuarios/{usuario}', [UsuariosController::class, 'destroy'])->name('usuarios.destroy');

        Route::get('/noticias', [NoticiasController::class, 'index'])->name('noticias.index');
        Route::post('/noticias', [NoticiasController::class, 'store'])->name('noticias.store');
        Route::put('/noticias/{noticia}', [NoticiasController::class, 'update'])->name('noticias.update');
        Route::delete('/noticias/{noticia}', [NoticiasController::class, 'destroy'])->name('noticias.destroy');
    });
});
