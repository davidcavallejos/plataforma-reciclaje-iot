<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 glass-card p-6 rounded-2xl border border-slate-700/60 shadow-xl">
        <div>
            <h1 class="heading-font text-2xl font-extrabold text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <i class="fa-solid fa-users-gear text-white text-lg"></i>
                </div>
                Gestión y Control de Usuarios
            </h1>
            <p class="text-sm text-slate-400 mt-1">Administración centralizada de perfiles, roles y bajas seguras (Soft Deletes).</p>
        </div>

        <button wire:click="openCreateModal" class="px-5 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-semibold text-sm shadow-lg shadow-emerald-500/25 transition transform active:scale-95 flex items-center justify-center gap-2">
            <i class="fa-solid fa-user-plus"></i> Registrar Usuario Manual
        </button>
    </div>

    <!-- Filters & Search Bar -->
    <div class="glass-card p-4 rounded-2xl border border-slate-700/60 flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Search Input -->
        <div class="relative w-full md:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o correo..."
                class="w-full pl-10 pr-4 py-2 bg-slate-800/80 border border-slate-700 rounded-xl text-sm text-slate-200 placeholder-slate-400 focus:outline-none focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 transition">
        </div>

        <!-- Role Filter & Status Toggle -->
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <select wire:model.live="roleFilter" class="bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-emerald-500 transition">
                <option value="">Todos los Roles</option>
                <option value="admin">Administrador (admin)</option>
                <option value="user">Usuario Reciclador (user)</option>
                <option value="business">Centro de Acopio (business)</option>
            </select>

            <button wire:click="$toggle('showTrashed')" class="px-3 py-2 rounded-xl text-xs font-semibold border transition flex items-center gap-2 {{ $showTrashed ? 'bg-rose-500/20 text-rose-300 border-rose-500/40' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700' }}">
                <i class="fa-solid {{ $showTrashed ? 'fa-user-slash text-rose-400' : 'fa-users text-slate-400' }}"></i>
                {{ $showTrashed ? 'Viendo Inactivos (Desactivados)' : 'Ver Desactivados' }}
            </button>
        </div>
    </div>

    <!-- Users Table -->
    <div class="glass-card rounded-2xl border border-slate-700/60 overflow-hidden shadow-2xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-800/90 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-700/80">
                    <tr>
                        <th class="px-6 py-4">Usuario</th>
                        <th class="px-6 py-4">Correo Electrónico</th>
                        <th class="px-6 py-4">Rol de Sistema</th>
                        <th class="px-6 py-4">Estado</th>
                        <th class="px-6 py-4">Fecha Registro</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($users as $user)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-slate-800 border border-slate-700 flex items-center justify-center font-bold text-emerald-400 text-sm overflow-hidden">
                                        @if($user->avatar_url)
                                            <img src="{{ $user->avatar_url }}" class="w-full h-full object-cover">
                                        @else
                                            {{ strtoupper(substr($user->name, 0, 2)) }}
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-100">{{ $user->name }}</p>
                                        <span class="text-[11px] text-slate-400">ID: #{{ $user->id }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-slate-300 font-mono text-xs">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4">
                                @if($user->role === 'admin')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30">
                                        <i class="fa-solid fa-shield-halved text-amber-400"></i> Admin
                                    </span>
                                @elseif($user->role === 'business')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                        <i class="fa-solid fa-store text-cyan-400"></i> Negocio
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                        <i class="fa-solid fa-user text-emerald-400"></i> Reciclador
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($user->trashed())
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-400 border border-rose-500/30">
                                        Desactivado
                                    </span>
                                @else
                                    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                        Activo
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400">
                                {{ $user->created_at ? $user->created_at->format('d/m/Y H:i') : 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if($user->trashed())
                                    <button wire:click="restoreUser({{ $user->id }})" title="Restaurar Usuario" class="px-3 py-1.5 rounded-lg bg-emerald-500/20 hover:bg-emerald-500/30 text-emerald-300 border border-emerald-500/40 text-xs font-semibold transition">
                                        <i class="fa-solid fa-rotate-left mr-1"></i> Reactivar
                                    </button>
                                @else
                                    <button wire:click="openEditModal({{ $user->id }})" title="Editar Perfil" class="p-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white transition">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </button>
                                    @if($user->id !== auth()->id())
                                        <button wire:click="deleteUser({{ $user->id }})" onclick="return confirm('¿Desactivar este usuario con Soft Delete? Sus publicaciones continuarán ligadas.') || event.stopImmediatePropagation()" title="Desactivar (Soft Delete)" class="p-2 rounded-lg bg-rose-500/10 hover:bg-rose-500/30 text-rose-400 transition">
                                            <i class="fa-solid fa-user-slash"></i>
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                                <i class="fa-solid fa-user-xmark text-3xl text-slate-500 mb-2"></i>
                                <p>No se encontraron usuarios coincidentes.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($users->hasPages())
            <div class="p-4 border-t border-slate-700/60">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    <!-- Create User Modal -->
    @if($isCreateModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="glass-card w-full max-w-lg rounded-2xl border border-slate-700 p-6 space-y-5 shadow-2xl relative">
                <div class="flex items-center justify-between border-b border-slate-700/80 pb-3">
                    <h3 class="heading-font text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-plus text-emerald-400"></i> Registrar Nuevo Usuario
                    </h3>
                    <button wire:click="closeCreateModal" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>

                <form wire:submit.prevent="createUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre Completo</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        @error('name') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Correo Electrónico</label>
                        <input type="email" wire:model="email" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        @error('email') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Rol de Usuario</label>
                        <select wire:model="role" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                            <option value="user">Usuario Reciclador (user)</option>
                            <option value="business">Centro de Acopio (business)</option>
                            <option value="admin">Administrador (admin)</option>
                        </select>
                        @error('role') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Contraseña</label>
                            <input type="password" wire:model="password" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                            @error('password') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Confirmar Contraseña</label>
                            <input type="password" wire:model="password_confirmation" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-700/80">
                        <button type="button" wire:click="closeCreateModal" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">Cancelar</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-semibold shadow-lg shadow-emerald-500/20 transition">Guardar Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Edit User Modal -->
    @if($isEditModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="glass-card w-full max-w-lg rounded-2xl border border-slate-700 p-6 space-y-5 shadow-2xl relative">
                <div class="flex items-center justify-between border-b border-slate-700/80 pb-3">
                    <h3 class="heading-font text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-user-pen text-amber-400"></i> Editar Datos de Usuario
                    </h3>
                    <button wire:click="closeEditModal" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>

                <form wire:submit.prevent="updateUser" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Nombre Completo</label>
                        <input type="text" wire:model="name" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        @error('name') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Correo Electrónico</label>
                        <input type="email" wire:model="email" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                        @error('email') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">Rol de Usuario</label>
                        <select wire:model="role" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                            <option value="user">Usuario Reciclador (user)</option>
                            <option value="business">Centro de Acopio (business)</option>
                            <option value="admin">Administrador (admin)</option>
                        </select>
                        @error('role') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                    </div>

                    <div class="border-t border-slate-700/60 pt-3">
                        <p class="text-xs text-amber-400 font-semibold mb-2">Cambiar Contraseña (Opcional - dejar en blanco para mantener la actual)</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Nueva Contraseña</label>
                                <input type="password" wire:model="password" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                                @error('password') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-300 mb-1">Confirmar Nueva Contraseña</label>
                                <input type="password" wire:model="password_confirmation" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-emerald-500 focus:outline-none">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-700/80">
                        <button type="button" wire:click="closeEditModal" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">Cancelar</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold shadow-lg shadow-amber-500/20 transition">Actualizar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
