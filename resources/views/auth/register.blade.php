<x-app-layout>
    <x-slot name="title">Registro de Usuario - Plataforma Multi-Reciclaje</x-slot>

    <div class="min-h-[75vh] flex items-center justify-center py-8">
        <div class="w-full max-w-md glass-card rounded-3xl p-8 shadow-2xl border border-teal-500/20">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-gradient-to-tr from-teal-500 to-cyan-400 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-teal-500/30">
                    <i class="fa-solid fa-user-plus text-white text-3xl"></i>
                </div>
                <h1 class="heading-font text-2xl font-bold text-slate-100">Crear una Cuenta</h1>
                <p class="text-sm text-slate-400 mt-1">Únete a la comunidad de recicladore(as) e iniciativas verdes</p>
            </div>

            <form action="{{ route('register') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre Completo</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-user"></i>
                        </span>
                        <input type="text" name="name" value="{{ old('name') }}" required
                            class="w-full bg-slate-900/80 border border-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition"
                            placeholder="Tu nombre">
                    </div>
                    @error('name')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Correo Electrónico</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-envelope"></i>
                        </span>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full bg-slate-900/80 border border-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition"
                            placeholder="correo@ejemplo.com">
                    </div>
                    @error('email')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Contraseña</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-lock"></i>
                        </span>
                        <input type="password" name="password" required
                            class="w-full bg-slate-900/80 border border-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition"
                            placeholder="Mínimo 6 caracteres">
                    </div>
                    @error('password')
                        <p class="text-rose-400 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 mb-1">Confirmar Contraseña</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                            <i class="fa-solid fa-lock-keyhole"></i>
                        </span>
                        <input type="password" name="password_confirmation" required
                            class="w-full bg-slate-900/80 border border-slate-700 focus:border-teal-500 focus:ring-1 focus:ring-teal-500 rounded-xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-500 transition"
                            placeholder="Repite la contraseña">
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-3.5 px-4 bg-gradient-to-r from-teal-500 via-emerald-500 to-cyan-600 hover:from-teal-400 hover:to-cyan-500 text-white font-bold rounded-xl shadow-lg shadow-teal-500/25 transition transform active:scale-95 flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-check"></i> Completar Registro
                </button>
            </form>

            <div class="mt-6 text-center text-xs text-slate-400">
                ¿Ya posees una cuenta?
                <a href="{{ route('login') }}" class="text-teal-400 font-semibold hover:underline ml-1">Inicia sesión</a>
            </div>
        </div>
    </div>
</x-app-layout>
