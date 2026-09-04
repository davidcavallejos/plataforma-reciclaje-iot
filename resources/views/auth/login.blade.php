<x-app-layout>
    <x-slot name="title">Iniciar Sesión - Plataforma Multi-Reciclaje</x-slot>

    <div class="min-h-[75vh] flex items-center justify-center py-8">
        <div class="w-full max-w-md glass-card rounded-3xl p-8 shadow-2xl border border-emerald-500/20">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-tr from-emerald-500 to-teal-400 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/30">
                    <i class="fa-solid fa-recycle text-white text-3xl"></i>
                </div>
                <h1 class="heading-font text-2xl font-bold text-slate-100">Iniciar Sesión</h1>
                <p class="text-sm text-slate-400 mt-1">Accede al Marketplace Ecológico y Máquina IoT</p>
            </div>

            <!-- Pre-filled Admin Demo Hint Box -->
            <div class="mb-6 p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-xs">
                <p class="font-bold flex items-center gap-1.5 mb-1 text-amber-200">
                    <i class="fa-solid fa-key"></i> Credenciales Administrador IoT:
                </p>
                <div class="flex items-center justify-between font-mono bg-slate-900/60 p-2 rounded-xl border border-amber-500/20">
                    <span>Usuario: <strong class="text-white">admin</strong></span>
                    <span>Pass: <strong class="text-white">admin 12346789</strong></span>
                </div>
            </div>

            <form action="{{ route('login') }}" method="POST" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Correo Electrónico o Usuario Admin</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="text" name="login" value="{{ old('login', 'admin') }}" required autofocus
                            class="w-full bg-slate-900/80 border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition"
                            placeholder="admin o tu@correo.com">
                    </div>
                    @error('login')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Contraseña</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" value="admin 12346789" required
                            class="w-full bg-slate-900/80 border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition"
                            placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between text-xs">
                    <label class="flex items-center gap-2 text-slate-300 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-emerald-500">
                        <span>Recordarme</span>
                    </label>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-emerald-500 via-teal-500 to-emerald-600 hover:from-emerald-400 hover:to-teal-400 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 transition transform active:scale-95 flex items-center justify-center gap-2">
                    <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-400">
                ¿No tienes una cuenta aún?
                <a href="{{ route('register') }}" class="text-emerald-400 font-semibold hover:underline ml-1">Regístrate gratis</a>
            </div>
        </div>
    </div>
</x-app-layout>
