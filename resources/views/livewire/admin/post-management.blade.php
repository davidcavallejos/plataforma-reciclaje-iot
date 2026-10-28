<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 glass-card p-6 rounded-2xl border border-slate-700/60 shadow-xl">
        <div>
            <h1 class="heading-font text-2xl font-extrabold text-white flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-amber-500 to-teal-500 flex items-center justify-center shadow-lg shadow-amber-500/20">
                    <i class="fa-solid fa-layer-group text-white text-lg"></i>
                </div>
                Moderación y Control de Publicaciones
            </h1>
            <p class="text-sm text-slate-400 mt-1">Supervisión global de publicaciones en Marketplace y Social Feed con integración a Cloudinary.</p>
        </div>
    </div>

    <!-- Filters & Search Bar -->
    <div class="glass-card p-4 rounded-2xl border border-slate-700/60 flex flex-col md:flex-row items-center justify-between gap-4">
        <!-- Search Input -->
        <div class="relative w-full md:w-80">
            <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por título, autor o contenido..."
                class="w-full pl-10 pr-4 py-2 bg-slate-800/80 border border-slate-700 rounded-xl text-sm text-slate-200 placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition">
        </div>

        <!-- Category & Status Filters -->
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <select wire:model.live="categoryFilter" class="bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-amber-500 transition">
                <option value="">Todas las Categorías</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>

            <select wire:model.live="statusFilter" class="bg-slate-800/80 border border-slate-700 rounded-xl px-3 py-2 text-sm text-slate-200 focus:outline-none focus:border-amber-500 transition">
                <option value="">Todos los Estados</option>
                <option value="active">Activas</option>
                <option value="hidden">Ocultas / Retiradas</option>
                <option value="sold">Vendidas / Concluidas</option>
            </select>
        </div>
    </div>

    <!-- Posts Grid / List -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($posts as $post)
            <div class="glass-card rounded-2xl border border-slate-700/60 overflow-hidden flex flex-col justify-between hover:border-slate-500/80 transition duration-200 shadow-xl group">
                <!-- Image Header & Status Badge -->
                <div class="relative h-48 bg-slate-950 overflow-hidden">
                    @if($post->image_url)
                        <img src="{{ $post->image_url }}" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    @else
                        <div class="w-full h-full flex flex-col items-center justify-center text-slate-500">
                            <i class="fa-solid fa-recycle text-4xl mb-2"></i>
                            <span class="text-xs">Sin imagen</span>
                        </div>
                    @endif

                    <!-- Category Badge -->
                    <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-900/80 text-emerald-400 border border-emerald-500/30 backdrop-blur-md">
                        {{ $post->category }}
                    </span>

                    <!-- Status Badge -->
                    <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-xs font-bold uppercase backdrop-blur-md shadow-md {{ $post->status === 'hidden' ? 'bg-rose-900/90 text-rose-200 border border-rose-500/40' : ($post->status === 'sold' ? 'bg-slate-800/90 text-slate-300' : 'bg-emerald-900/90 text-emerald-200 border border-emerald-500/40') }}">
                        {{ $post->status === 'hidden' ? 'Oculta' : ($post->status === 'sold' ? 'Vendida' : 'Activa') }}
                    </span>
                </div>

                <!-- Post Content -->
                <div class="p-5 space-y-3 flex-grow">
                    <div class="flex items-start justify-between gap-2">
                        <h3 class="font-extrabold text-slate-100 text-base line-clamp-1 heading-font">{{ $post->title }}</h3>
                        <span class="text-xs font-mono font-bold text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded border border-amber-500/20">
                            {{ $post->is_donation ? 'Donación' : '$' . number_format($post->price, 2) }}
                        </span>
                    </div>

                    <p class="text-xs text-slate-300 line-clamp-2 leading-relaxed">
                        {{ $post->description ?? 'Sin descripción detallada.' }}
                    </p>

                    <!-- Author Info & Cloudinary Details -->
                    <div class="pt-3 border-t border-slate-700/60 flex items-center justify-between text-xs text-slate-400">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-user-circle text-slate-400"></i>
                            <span>{{ $post->user->name ?? 'Usuario Desconocido' }}</span>
                        </div>
                        @if($post->cloudinary_public_id)
                            <span class="text-[10px] font-mono text-cyan-400/80 bg-cyan-950/60 px-1.5 py-0.5 rounded border border-cyan-800/40" title="ID de recurso en Cloudinary">
                                <i class="fa-solid fa-cloud text-[9px] mr-0.5"></i> Cloudinary
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Actions Footer -->
                <div class="p-4 bg-slate-900/60 border-t border-slate-700/60 flex items-center justify-between gap-2">
                    <button wire:click="toggleStatus({{ $post->id }})" class="flex-1 py-1.5 px-2 rounded-lg text-xs font-semibold transition border flex items-center justify-center gap-1.5 {{ $post->status === 'hidden' ? 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30 hover:bg-emerald-500/30' : 'bg-slate-800 text-slate-300 border-slate-700 hover:bg-slate-700' }}">
                        <i class="fa-solid {{ $post->status === 'hidden' ? 'fa-eye' : 'fa-eye-slash' }}"></i>
                        {{ $post->status === 'hidden' ? 'Reactivar' : 'Ocultar' }}
                    </button>

                    <button wire:click="openEditModal({{ $post->id }})" class="p-2 rounded-lg bg-amber-500/20 hover:bg-amber-500/30 text-amber-300 border border-amber-500/40 text-xs font-semibold transition" title="Editar Publicación">
                        <i class="fa-solid fa-pen"></i>
                    </button>

                    <button wire:click="deletePost({{ $post->id }})" onclick="return confirm('¿Eliminar permanentemente esta publicación y destruir su archivo multimedia en Cloudinary?') || event.stopImmediatePropagation()" class="p-2 rounded-lg bg-rose-500/20 hover:bg-rose-500/30 text-rose-300 border border-rose-500/40 text-xs font-semibold transition" title="Eliminar definitivamente (Destruye multimedia en Cloudinary)">
                        <i class="fa-solid fa-trash-can"></i>
                    </button>
                </div>
            </div>
        @empty
            <div class="col-span-full glass-card p-12 text-center text-slate-400 rounded-2xl border border-slate-700/60">
                <i class="fa-solid fa-folder-open text-4xl text-slate-500 mb-3"></i>
                <p class="text-base font-semibold">No se encontraron publicaciones con los filtros seleccionados.</p>
            </div>
        @endforelse
    </div>

    @if($posts->hasPages())
        <div class="p-4 border-t border-slate-700/60">
            {{ $posts->links() }}
        </div>
    @endif

    <!-- Edit Post Modal -->
    @if($isEditModalOpen)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm">
            <div class="glass-card w-full max-w-2xl rounded-2xl border border-slate-700 p-6 space-y-5 shadow-2xl relative max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between border-b border-slate-700/80 pb-3">
                    <h3 class="heading-font text-lg font-bold text-white flex items-center gap-2">
                        <i class="fa-solid fa-pen-to-square text-amber-400"></i> Edición Administrativa de Publicación #{{ $selectedPostId }}
                    </h3>
                    <button wire:click="closeEditModal" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
                </div>

                <form wire:submit.prevent="updatePost" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Título de la Publicación</label>
                            <input type="text" wire:model="title" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-amber-500 focus:outline-none">
                            @error('title') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Categoría de Material</label>
                            <select wire:model="category" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-amber-500 focus:outline-none">
                                @foreach($categories as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('category') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Estado de Moderación</label>
                            <select wire:model="status" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-amber-500 focus:outline-none">
                                <option value="active">Activa (Pública)</option>
                                <option value="hidden">Oculta (Retirada por moderación)</option>
                                <option value="sold">Concluida / Vendida</option>
                            </select>
                            @error('status') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Precio ($ ARS / USD)</label>
                            <input type="number" step="0.01" wire:model="price" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-amber-500 focus:outline-none">
                            @error('price') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Cantidad / Peso (kg, unidades)</label>
                            <input type="number" step="0.1" wire:model="quantity" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-amber-500 focus:outline-none">
                            @error('quantity') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Descripción</label>
                            <textarea wire:model="description" rows="3" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-amber-500 focus:outline-none"></textarea>
                            @error('description') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-300 mb-1">Ubicación / Dirección de Retiro</label>
                            <input type="text" wire:model="address" class="w-full px-3 py-2 bg-slate-800 border border-slate-700 rounded-xl text-sm text-slate-100 focus:border-amber-500 focus:outline-none">
                        </div>

                        <!-- Image Management Section -->
                        <div class="md:col-span-2 space-y-2 border-t border-slate-700/60 pt-3">
                            <label class="block text-xs font-semibold text-amber-400 mb-1">Reemplazar Imagen en Cloudinary (Opcional)</label>
                            
                            @if($currentImageUrl)
                                <div class="flex items-center gap-3 bg-slate-800/80 p-2 rounded-xl border border-slate-700">
                                    <img src="{{ $currentImageUrl }}" class="w-12 h-12 rounded-lg object-cover">
                                    <div class="text-xs text-slate-300">
                                        <p class="font-semibold">Imagen actual cargada</p>
                                        <p class="text-[10px] text-slate-400 font-mono">Public ID: {{ $currentPublicId ?? 'Sin ID' }}</p>
                                    </div>
                                </div>
                            @endif

                            <input type="file" wire:model="newImage" accept="image/*" class="w-full text-xs text-slate-300 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-amber-500/20 file:text-amber-300 hover:file:bg-amber-500/30">
                            @error('newImage') <span class="text-xs text-rose-400 mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-700/80">
                        <button type="button" wire:click="closeEditModal" class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 text-sm font-semibold transition">Cancelar</button>
                        <button type="submit" class="px-5 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-sm font-semibold shadow-lg shadow-amber-500/20 transition">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
