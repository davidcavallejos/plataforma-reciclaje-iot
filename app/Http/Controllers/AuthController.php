<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('marketplace');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim(e($credentials['login']));
        $password = $credentials['password'];

        // Determine if logging in by email or admin username
        $user = User::where('email', $loginInput)->first();

        if ($user && Hash::check($password, $user->password)) {
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();

            if ($user->isAdmin()) {
                return redirect()->route('admin.machine')->with('success', 'Bienvenido Administrador a la Plataforma IoT.');
            }
            return redirect()->route('marketplace')->with('success', '¡Sesión iniciada correctamente!');
        }

        return back()->withErrors([
            'login' => 'Las credenciales proporcionadas no son válidas.',
        ])->onlyInput('login');
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('marketplace');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:150|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name' => strip_tags(trim($validated['name'])),
            'email' => strtolower(trim($validated['email'])),
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'avatar_url' => 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=300&q=80',
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('marketplace')->with('success', '¡Cuenta creada con éxito! bienvenido a la plataforma multi-reciclaje.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('marketplace')->with('success', 'Sesión cerrada correctamente.');
    }
}
