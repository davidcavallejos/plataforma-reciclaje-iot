<div>
    <!-- Header Banner -->
    <div class="mb-8 p-6 sm:p-8 rounded-3xl glass-card border border-cyan-500/20 bg-gradient-to-r from-slate-900 via-cyan-950/40 to-slate-900 shadow-2xl flex flex-col md:flex-row items-center justify-between gap-6">
        <div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-500/20 text-cyan-300 text-xs font-semibold border border-cyan-500/30 mb-3">
                <i class="fa-solid fa-building-user text-cyan-400"></i> Red de Acopio & Empresas
            </span>
            <h1 class="heading-font text-3xl font-extrabold text-slate-100">
                Directorio de Centros de Acopio
            </h1>
            <p class="text-slate-300 text-sm mt-1 max-w-2xl">
                Encuentra empresas verificadas que reciben materiales a granel, aceites para procesamiento industrial o reciclan residuos específicos.
            </p>
        </div>

        @auth
            <button wire:click="$set('showRegisterModal', true)"
                class="px-6 py-3.5 bg-gradient-to-r from-cyan-500 to-teal-600 hover:from-cyan-400 hover:to-teal-500 text-white font-bold rounded-2xl shadow-lg shadow-cyan-500/25 transition transform active:scale-95 flex items-center gap-2 whitespace-nowrap">
                <i class="fa-solid fa-plus-circle text-lg"></i> Registrar Mi Centro
            </button>
        @else
            <a href="{{ route('login') }}" class="px-6 py-3 bg-slate-800 hover:bg-slate-700 text-cyan-300 border border-cyan-500/40 font-bold rounded-2xl transition">
                Iniciar Sesión para Registrar
            </a>
        @endauth
    </div>

    <!-- Businesses Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($businesses as $b)
            <div class="glass-card rounded-3xl p-6 border border-slate-700/80 hover:border-cyan-500/50 transition duration-300 flex flex-col justify-between shadow-xl">
                <div>
                    <div class="flex items-start justify-between gap-3 mb-4">
                        <div>
                            <span class="text-[10px] uppercase font-bold tracking-wider px-2.5 py-1 rounded-full bg-cyan-500/20 text-cyan-300 border border-cyan-500/30">
                                {{ $b->business_type }}
                            </span>
                            <h3 class="heading-font text-xl font-bold text-slate-100 mt-2">
                                {{ $b->business_name }}
                            </h3>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/30 flex items-center justify-center text-cyan-400 text-xl font-bold flex-shrink-0">
                            <i class="fa-solid fa-warehouse"></i>
                        </div>
                    </div>

                    <p class="text-xs text-slate-300 mb-4 line-clamp-2">
                        {{ $b->description }}
                    </p>

                    <!-- Materials Handled Pill Box -->
                    <div class="mb-4 bg-slate-950/60 p-3 rounded-2xl border border-slate-800">
                        <span class="block text-[11px] font-bold text-slate-400 mb-1">
                            <i class="fa-solid fa-boxes-packing text-emerald-400 mr-1"></i> Materiales Aceptados:
                        </span>
                        <p class="text-xs font-semibold text-emerald-300">
                            {{ $b->materials_handled }}
                        </p>
                    </div>

                    <!-- Contact & Address -->
                    <div class="space-y-2 text-xs text-slate-300 mb-6">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-rose-400 w-4"></i>
                            <span>{{ $b->address }}</span>
                        </div>
                        @if($b->nit_ruc)
                            <div class="flex items-center gap-2">
                                <i class="fa-solid fa-id-card text-amber-400 w-4"></i>
                                <span>NIT / RUC: {{ $b->nit_ruc }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Direct WhatsApp Action Button -->
                @php
                    $cleanPhone = preg_replace('/[^0-9]/', '', $b->phone_whatsapp);
                    $msg = rawurlencode("Hola {$b->business_name}, vi su centro de acopio en EcoRecicla IoT y deseo consultar sobre entregas de material.");
                @endphp
                <a href="https://wa.me/{{ $cleanPhone }}?text={{ $msg }}" target="_blank"
                    class="w-full py-3 bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold rounded-2xl text-xs shadow-lg shadow-emerald-500/20 transition flex items-center justify-center gap-2">
                    <i class="fa-brands fa-whatsapp text-lg"></i> Contactar por WhatsApp ({{ $b->phone_whatsapp }})
                </a>
            </div>
        @endforeach
    </div>

    <!-- Registration Modal -->
    @if($showRegisterModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            <div class="glass-card w-full max-w-lg rounded-3xl p-6 shadow-2xl border border-cyan-500/30 relative max-h-[90vh] overflow-y-auto">
                <button wire:click="$set('showRegisterModal', false)" class="absolute top-4 right-4 text-slate-400 hover:text-white text-lg">
                    <i class="fa-solid fa-xmark"></i>
                </button>

                <h2 class="heading-font text-xl font-bold text-slate-100 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-building-circle-check text-cyan-400"></i> Registrar Centro de Acopio
                </h2>

                <form wire:submit.prevent="registerBusiness" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Nombre del Centro / Empresa</label>
                        <input type="text" wire:model="business_name" placeholder="Ej: Reciclajes Ecogreen Bolivia" required
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100 focus:border-cyan-500">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Tipo de Negocio</label>
                            <input type="text" wire:model="business_type" placeholder="Ej: Acopio Industrial" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">WhatsApp de Contacto</label>
                            <input type="text" wire:model="phone_whatsapp" placeholder="+59171234567" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Materiales que Aceptan / Procesan</label>
                        <input type="text" wire:model="materials_handled" placeholder="Plástico, Aceite Usado de Cocina, Vidrio, Baterías" required
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">NIT / RUC (Opcional)</label>
                            <input type="text" wire:model="nit_ruc" placeholder="1029384756"
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                        </div>
                        <div>
                            <label class="block font-semibold text-slate-300 mb-1">Dirección Físicas</label>
                            <input type="text" wire:model="address" placeholder="Av. Ecológica #450" required
                                class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100">
                        </div>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-300 mb-1">Descripción del Servicio</label>
                        <textarea wire:model="description" rows="3" placeholder="Detalla horarios de recepción, precios de compra por kilo, etc."
                            class="w-full bg-slate-900 border border-slate-700 rounded-xl px-3.5 py-2.5 text-slate-100"></textarea>
                    </div>

                    <button type="submit"
                        class="w-full py-3 bg-gradient-to-r from-cyan-500 to-teal-600 text-white font-bold rounded-xl shadow-lg shadow-cyan-500/25 hover:from-cyan-400 hover:to-teal-500 transition">
                        Guardar Centro de Acopio
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
