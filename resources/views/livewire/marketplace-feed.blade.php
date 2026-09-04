<div>
    <!-- Hero / Header Section -->
    <div class="mb-8 p-6 sm:p-8 rounded-3xl glass-card border border-emerald-500/20 bg-gradient-to-r from-slate-900 via-emerald-950/40 to-slate-900 shadow-2xl relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 opacity-10 text-emerald-400">
            <i class="fa-solid fa-recycle text-[200px]"></i>
        </div>

        <div class="relative z-10 max-w-3xl">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold border border-emerald-500/30 mb-3">
                <i class="fa-solid fa-leaf text-emerald-400"></i> Marketplace Social & Economía Circular
            </span>
            <h1 class="heading-font text-3xl sm:text-4xl font-extrabold text-slate-100 leading-tight">
                Conecta, Recicla y Transforma
            </h1>
            <p class="text-slate-300 text-sm sm:text-base mt-2">
                Publica materiales reciclables (plástico, cartón, aceite para jabón, electrónicos) o encuéntralos en centros de acopio cerca de ti.
            </p>

            <!-- Search & Action Row -->
            <div class="mt-6 flex flex-col sm:flex-row items-center gap-3">
                <div class="relative w-full sm:w-96">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" wire:model.live.debounce.300ms="search"
                        class="w-full bg-slate-900/90 border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-2xl pl-10 pr-4 py-3 text-sm text-slate-100 placeholder-slate-400 shadow-inner"
                        placeholder="Buscar por material, título o zona...">
                </div>

                @auth
                    <button wire:click="$set('showCreateModal', true)"
                        class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold rounded-2xl shadow-lg shadow-emerald-500/25 transition transform active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-plus-circle text-lg"></i> Publicar Material
                    </button>
                @else
                    <a href="{{ route('login') }}"
                        class="w-full sm:w-auto px-6 py-3 bg-slate-800 hover:bg-slate-700 text-emerald-400 border border-emerald-500/40 font-bold rounded-2xl transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-right-to-bracket"></i> Iniciar Sesión para Publicar
                    </a>
                @endauth
            </div>
        </div>
    </div>

    <!-- Filter Categories Bar -->
    <div class="mb-8 overflow-x-auto pb-2 scrollbar-none">
        <div class="flex items-center gap-2 min-w-max">
            @foreach($categories as $cat)
                @php
                    $iconMap = [
                        'Todos' => 'fa-globe',
                        'Plástico' => 'fa-bottle-water',
                        'Cartón' => 'fa-box',
                        'Metal' => 'fa-sheet-plastic',
                        'Vidrio' => 'fa-wine-bottle',
                        'Electrónicos' => 'fa-plug-circle-bolt',
                        'Aceite' => 'fa-oil-can',
                        'Orgánicos' => 'fa-seedling',
                        'Textiles' => 'fa-shirt',
                    ];
                    $icon = $iconMap[$cat] ?? 'fa-recycle';
                    $isSelected = $selectedCategory === $cat;
                @endphp
                <button wire:click="selectCategory('{{ $cat }}')"
                    class="px-4 py-2.5 rounded-2xl text-xs font-bold transition flex items-center gap-2 border {{ $isSelected ? 'bg-emerald-500 text-white border-emerald-400 shadow-lg shadow-emerald-500/30' : 'bg-slate-800/80 text-slate-300 border-slate-700 hover:bg-slate-700/80 hover:text-white' }}">
                    <i class="fa-solid {{ $icon }}"></i> {{ $cat }}
                </button>
            @endforeach
        </div>
    </div>

    <!-- Posts Grid / Social Feed -->
    @if($posts->isEmpty())
        <div class="text-center py-16 glass-card rounded-3xl border border-slate-800 my-8">
            <div class="w-16 h-16 bg-slate-800 text-slate-500 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-inbox"></i>
            </div>
            <h3 class="heading-font text-lg font-bold text-slate-200">No se encontraron publicaciones</h3>
            <p class="text-sm text-slate-400 mt-1">Sé el primero en crear una oferta o solicitud de reciclaje para esta categoría.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <div class="glass-card rounded-3xl overflow-hidden border border-slate-700/70 hover:border-emerald-500/50 transition-all duration-300 flex flex-col group shadow-xl">
                    
                    <!-- Post Header (Author & Badge) -->
                    <div class="p-4 flex items-center justify-between border-b border-slate-800/80">
                        <div class="flex items-center gap-3">
                            <img src="{{ $post->user->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&q=80' }}" class="w-10 h-10 rounded-full object-cover border border-emerald-500/40">
                            <div>
                                <h4 class="font-bold text-sm text-slate-100 leading-tight">{{ $post->user->name }}</h4>
                                <span class="text-[10px] text-slate-400 flex items-center gap-1 mt-0.5">
                                    <i class="fa-regular fa-clock"></i> {{ $post->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>

                        <!-- Category Pill -->
                        <span class="px-3 py-1 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                            {{ $post->category }}
                        </span>
                    </div>

                    <!-- Post Image (Cloudinary or Fallback) -->
                    <div class="relative aspect-video w-full overflow-hidden bg-slate-950">
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        
                        <!-- Donation or Price Badge -->
                        <div class="absolute top-3 left-3">
                            @if($post->is_donation)
                                <span class="px-3 py-1 bg-teal-500 text-slate-950 font-extrabold text-xs rounded-xl shadow-lg border border-teal-300 flex items-center gap-1">
                                    <i class="fa-solid fa-gift"></i> DONACIÓN
                                </span>
                            @else
                                <span class="px-3 py-1 bg-emerald-600 text-white font-extrabold text-xs rounded-xl shadow-lg border border-emerald-400">
                                    ${{ number_format($post->price, 2) }}
                                </span>
                            @endif
                        </div>

                        <!-- Quantity Pill -->
                        @if($post->quantity)
                            <div class="absolute bottom-3 right-3 bg-slate-900/80 backdrop-blur-md px-2.5 py-1 rounded-lg text-slate-200 text-xs font-semibold border border-slate-700">
                                <i class="fa-solid fa-scale-balanced text-emerald-400 mr-1"></i> {{ $post->quantity }}
                            </div>
                        @endif
                    </div>

                    <!-- Post Body -->
                    <div class="p-5 flex-grow flex flex-col justify-between">
                        <div>
                            <h3 class="heading-font font-bold text-lg text-slate-100 mb-2 leading-snug group-hover:text-emerald-400 transition-colors">
                                {{ $post->title }}
                            </h3>
                            <p class="text-slate-300 text-xs leading-relaxed line-clamp-3 mb-4">
                                {{ $post->description }}
                            </p>
                        </div>

                        <div>
                            <!-- Address / Location Badge -->
                            <div class="flex items-center gap-2 text-xs text-slate-400 mb-4 bg-slate-900/60 p-2.5 rounded-xl border border-slate-800">
                                <i class="fa-solid fa-location-dot text-rose-400"></i>
                                <span class="truncate">{{ $post->address ?? 'Ubicación geolocalizada' }}</span>
                            </div>

                            <!-- WhatsApp Contact Button -->
                            <a href="{{ $this->getWhatsAppUrl($post) }}" target="_blank"
                                class="w-full py-2.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-xl text-xs shadow-md shadow-emerald-600/30 transition flex items-center justify-center gap-2">
                                <i class="fa-brands fa-whatsapp text-lg"></i> Contactar por WhatsApp
                            </a>
                        </div>
                    </div>

                    <!-- Social Interactions Footer (Likes & Comments) -->
                    <div class="px-5 py-3 bg-slate-900/60 border-t border-slate-800/80 flex items-center justify-between text-xs">
                        <button wire:click="toggleLike({{ $post->id }})"
                            class="flex items-center gap-1.5 transition font-semibold {{ $post->isLikedBy(auth()->user()) ? 'text-rose-500' : 'text-slate-400 hover:text-rose-400' }}">
                            <i class="{{ $post->isLikedBy(auth()->user()) ? 'fa-solid' : 'fa-regular' }} fa-heart text-base"></i>
                            <span>{{ $post->likes->count() }} Me gusta</span>
                        </button>

                        <div class="text-slate-400 flex items-center gap-1 font-semibold">
                            <i class="fa-regular fa-comment text-base"></i>
                            <span>{{ $post->comments->count() }} Comentarios</span>
                        </div>
                    </div>

                    <!-- Comment Input & List -->
                    <div class="p-4 bg-slate-950/40 border-t border-slate-800/60 text-xs">
                        @auth
                            <div class="flex gap-2 mb-3">
                                <input type="text" wire:model.defer="newComments.{{ $post->id }}"
                                    wire:keydown.enter="addComment({{ $post->id }})"
                                    placeholder="Escribe un comentario..."
                                    class="flex-grow bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-slate-100 placeholder-slate-500 focus:outline-none focus:border-emerald-500 text-xs">
                                <button wire:click="addComment({{ $post->id }})"
                                    class="px-3 py-2 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-500 transition">
                                    <i class="fa-solid fa-paper-plane"></i>
                                </button>
                            </div>
                        @endauth

                        <!-- Latest 2 Comments -->
                        @foreach($post->comments->take(2) as $comment)
                            <div class="mb-2 bg-slate-900/70 p-2 rounded-xl border border-slate-800/60">
                                <span class="font-bold text-emerald-400">{{ $comment->user->name }}:</span>
                                <span class="text-slate-300">{{ $comment->comment }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <!-- Create Post Modal -->
    @if($showCreateModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            <div class="glass-card w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-emerald-500/30 relative max-h-[90vh] overflow-y-auto">
                <button wire:click="$set('showCreateModal', false)" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <h2 class="heading-font text-xl font-bold text-slate-100 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-plus-circle text-emerald-400"></i> Nueva Publicación de Reciclaje
                </h2>

                <form wire:submit.prevent="createPost" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Título de la Publicación</label>
                        <input type="text" wire:model="title" placeholder="Ej: 50 Litros de Aceite Vegetal Filtrado" required
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100 focus:border-emerald-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Categoría</label>
                            <select wire:model="category" class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                                @foreach(array_diff($categories, ['Todos']) as $c)
                                    <option value="{{ $c }}">{{ $c }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Cantidad Estimada</label>
                            <input type="text" wire:model="quantity" placeholder="Ej: 20 Kg / 50 Litros"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Descripción Detallada</label>
                        <textarea wire:model="description" rows="3" placeholder="Estado del material, condiciones de recogida, etc." required
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100"></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-3 items-center">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Precio ($)</label>
                            <input type="number" step="0.50" wire:model="price" {{ $is_donation ? 'disabled' : '' }}
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100 disabled:opacity-50">
                        </div>
                        <div class="pt-5">
                            <label class="flex items-center gap-2 cursor-pointer font-bold text-teal-400">
                                <input type="checkbox" wire:model.live="is_donation" class="rounded bg-slate-900 border-slate-700 text-emerald-500">
                                <span>¿Es Donación Gratuita?</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Dirección de Recogida</label>
                        <input type="text" wire:model="address" placeholder="Av. Principal #123, Zona Central" required
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Imagen (Subida Directa a Cloudinary CDN)</label>
                        <input type="file" wire:model="image" accept="image/*"
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3 py-2 text-slate-300 file:mr-4 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-600 file:text-white">
                        <div wire:loading wire:target="image" class="text-emerald-400 text-xs mt-1">Cargando imagen...</div>
                    </div>

                    <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-600 text-white font-bold rounded-xl shadow-lg shadow-emerald-500/25 hover:from-emerald-400 hover:to-teal-500 transition">
                        Publicar en Marketplace
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
