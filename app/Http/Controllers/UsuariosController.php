<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UsuariosController extends Controller
{
    public function index(): View
    {
        $usuarios = User::withTrashed()
            ->withCount(['recetas', 'lotes', 'pedidos'])
            ->orderByRaw("case rol when 'administrador' then 0 when 'operador' then 1 else 2 end")
            ->orderBy('nombre')
            ->get();

        return view('usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => Rol::opciones(),
            'administradorActual' => User::where('rol', Rol::Administrador)->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'rol' => ['required', Rule::enum(Rol::class)],
            'activo' => ['nullable', 'boolean'],
        ], [
            'email.unique' => 'Ese correo ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ]);

        $rol = Rol::from($datos['rol']);

        if ($rol === Rol::Administrador && User::where('rol', Rol::Administrador)->exists()) {
            return redirect()->route('usuarios.index')->withErrors([
                'rol' => 'Ya existe un administrador. Cambia primero el rol del actual.',
            ]);
        }

        $usuario = User::create([
            'nombre' => $datos['nombre'],
            'email' => $datos['email'],
            'password' => $datos['password'],
            'rol' => $rol,
            'activo' => $request->boolean('activo', true),
        ]);

        return redirect()->route('usuarios.index')
            ->with('estado', "Usuario «{$usuario->nombre}» creado como {$usuario->rol->etiqueta()}.");
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $datos = $request->validate([
            'rol' => ['required', Rule::enum(Rol::class)],
            'activo' => ['nullable', 'boolean'],
        ]);

        $rol = Rol::from($datos['rol']);
        $activo = $request->boolean('activo');

        if ($usuario->id === $request->user()->id && ($rol !== Rol::Administrador || ! $activo)) {
            return redirect()->route('usuarios.index')->withErrors([
                'usuario' => 'No puedes quitarte el rol de administrador ni desactivar tu propia cuenta.',
            ]);
        }

        if ($rol === Rol::Administrador && ! $usuario->esAdministrador()) {
            $otroAdmin = User::where('rol', Rol::Administrador)->whereKeyNot($usuario->id)->first();

            if ($otroAdmin !== null) {
                return redirect()->route('usuarios.index')->withErrors([
                    'rol' => "Ya existe un administrador ({$otroAdmin->nombre}). Cambia primero su rol.",
                ]);
            }
        }

        try {
            $usuario->update(['rol' => $rol, 'activo' => $activo]);
        } catch (QueryException) {
            // Red de seguridad: el índice único parcial de PostgreSQL impide
            // que existan dos administradores activos.
            return redirect()->route('usuarios.index')->withErrors([
                'rol' => 'La base de datos rechazó el cambio: ya existe otro administrador.',
            ]);
        }

        return redirect()->route('usuarios.index')
            ->with('estado', "Usuario «{$usuario->nombre}» actualizado.");
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        if ($usuario->id === $request->user()->id) {
            return redirect()->route('usuarios.index')->withErrors([
                'usuario' => 'No puedes eliminar tu propia cuenta.',
            ]);
        }

        $nombre = $usuario->nombre;
        $usuario->delete();

        return redirect()->route('usuarios.index')
            ->with('estado', "Usuario «{$nombre}» dado de baja (sus registros se conservan).");
    }
}
