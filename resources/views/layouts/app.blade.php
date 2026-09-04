<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-900 text-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'Plataforma Multi-Reciclaje + IoT') }}</title>

    <!-- Google Fonts: Outfit & Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@500;600;700;800&display=swap" rel="stylesheet">

    <!-- Leaflet.js CSS for Interactive Maps -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"/>

    <!-- PWA Manifest & Theme Color -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#059669">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

    <!-- Tailwind CSS (via CDN with fallback to custom classes for immediate high aesthetic rendering) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        eco: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        outfit: ['Outfit', 'sans-serif'],
                    }
                }
            }
        }
    </script>

    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
        }
        .heading-font {
            font-family: 'Outfit', sans-serif;
        }
        .glass-card {
            background: rgba(30, 41, 59, 0.7);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .glass-nav {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(16, 185, 129, 0.2);
        }
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #0f172a;
        }
        ::-webkit-scrollbar-thumb {
            background: #059669;
            border-radius: 4px;
        }
    </style>
    @livewireStyles
</head>
<body class="min-h-screen flex flex-col antialiased selection:bg-emerald-500 selection:text-white pb-16 md:pb-0">

    <!-- Top Navigation Bar -->
    <header class="sticky top-0 z-50 glass-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <a href="{{ route('marketplace') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20 group-hover:scale-105 transition-transform">
                        <i class="fa-solid fa-recycle text-white text-xl"></i>
                    </div>
                    <div>
                        <span class="heading-font font-extrabold text-xl tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-emerald-400 via-teal-300 to-cyan-400">
                            EcoRecicla <span class="text-xs px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">IoT</span>
                        </span>
                        <p class="text-[10px] text-slate-400 -mt-1 hidden sm:block">Plataforma Multi-Material & Jabón Saponificado</p>
                    </div>
                </a>

                <!-- Navigation Links -->
                <nav class="hidden md:flex items-center gap-1 font-medium text-sm">
                    <a href="{{ route('marketplace') }}" class="px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-2 {{ request()->routeIs('marketplace') ? 'bg-emerald-600/20 text-emerald-400 font-semibold border border-emerald-500/30' : '' }}">
                        <i class="fa-solid fa-store text-emerald-400"></i> Marketplace
                    </a>
                    <a href="{{ route('map') }}" class="px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-2 {{ request()->routeIs('map') ? 'bg-emerald-600/20 text-emerald-400 font-semibold border border-emerald-500/30' : '' }}">
                        <i class="fa-solid fa-map-location-dot text-teal-400"></i> Mapa Ecológico
                    </a>
                    <a href="{{ route('businesses') }}" class="px-3 py-2 rounded-lg text-slate-300 hover:text-white hover:bg-slate-800/80 transition flex items-center gap-2 {{ request()->routeIs('businesses') ? 'bg-emerald-600/20 text-emerald-400 font-semibold border border-emerald-500/30' : '' }}">
                        <i class="fa-solid fa-building-user text-cyan-400"></i> Centros de Acopio
                    </a>

                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.machine') }}" class="px-3 py-2 rounded-lg bg-amber-500/20 text-amber-300 hover:bg-amber-500/30 transition flex items-center gap-2 border border-amber-500/40 font-semibold">
                                <i class="fa-solid fa-microchip text-amber-400 animate-pulse"></i> Panel Máquina IoT
                            </a>
                        @endif
                    @endauth
                </nav>

                <!-- User Action / Profile -->
                <div class="flex items-center gap-3">
                    @auth
                        <div class="flex items-center gap-3 bg-slate-800/80 border border-slate-700/60 pl-3 pr-2 py-1.5 rounded-full">
                            <img src="{{ auth()->user()->avatar_url ?? 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?auto=format&fit=crop&w=100&q=80' }}" class="w-8 h-8 rounded-full object-cover border border-emerald-400">
                            <div class="text-xs">
                                <p class="font-bold text-slate-200 leading-tight">{{ auth()->user()->name }}</p>
                                <span class="text-[10px] uppercase font-semibold px-1.5 py-0.2 rounded {{ auth()->user()->isAdmin() ? 'bg-amber-500/30 text-amber-300 border border-amber-500/40' : 'bg-emerald-500/20 text-emerald-400' }}">
                                    {{ auth()->user()->role }}
                                </span>
                            </div>
                            <form action="{{ route('logout') }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" title="Cerrar Sesión" class="w-8 h-8 rounded-full hover:bg-rose-500/20 hover:text-rose-400 text-slate-400 flex items-center justify-center transition">
                                    <i class="fa-solid fa-right-from-bracket text-xs"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <a href="{{ route('login') }}" class="px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition">
                                Iniciar Sesión
                            </a>
                            <a href="{{ route('register') }}" class="px-4 py-2 text-sm font-semibold rounded-xl bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white shadow-lg shadow-emerald-500/25 transition transform active:scale-95">
                                Registrarse
                            </a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Flash Alerts -->
    @if(session('success'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-emerald-500/20 border border-emerald-500/40 text-emerald-200 px-4 py-3 rounded-2xl flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-circle-check text-emerald-400 text-xl"></i>
                    <span>{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-emerald-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="max-w-7xl mx-auto px-4 mt-4 w-full">
            <div class="bg-rose-500/20 border border-rose-500/40 text-rose-200 px-4 py-3 rounded-2xl flex items-center justify-between shadow-lg">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-triangle-exclamation text-rose-400 text-xl"></i>
                    <span>{{ session('error') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-rose-300 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>
    @endif

    <!-- Main Body View -->
    <main class="flex-grow max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6">
        {{ $slot }}
    </main>

    <!-- Mobile Navigation Bar -->
    <div class="md:hidden fixed bottom-0 left-0 right-0 z-50 glass-nav border-t border-slate-700/80 px-4 py-2">
        <div class="flex items-center justify-around text-center">
            <a href="{{ route('marketplace') }}" class="flex flex-col items-center text-xs gap-1 {{ request()->routeIs('marketplace') ? 'text-emerald-400 font-bold' : 'text-slate-400' }}">
                <i class="fa-solid fa-store text-lg"></i>
                <span>Feed</span>
            </a>
            <a href="{{ route('map') }}" class="flex flex-col items-center text-xs gap-1 {{ request()->routeIs('map') ? 'text-teal-400 font-bold' : 'text-slate-400' }}">
                <i class="fa-solid fa-map-location-dot text-lg"></i>
                <span>Mapa</span>
            </a>
            <a href="{{ route('businesses') }}" class="flex flex-col items-center text-xs gap-1 {{ request()->routeIs('businesses') ? 'text-cyan-400 font-bold' : 'text-slate-400' }}">
                <i class="fa-solid fa-building-user text-lg"></i>
                <span>Negocios</span>
            </a>
            @auth
                @if(auth()->user()->isAdmin())
                    <a href="{{ route('admin.machine') }}" class="flex flex-col items-center text-xs gap-1 {{ request()->routeIs('admin.machine') ? 'text-amber-400 font-bold' : 'text-slate-400' }}">
                        <i class="fa-solid fa-microchip text-lg"></i>
                        <span>Máquina IoT</span>
                    </a>
                @endif
            @endauth
        </div>
    </div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    
    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js')
                    .then(reg => console.log('PWA ServiceWorker registrado:', reg.scope))
                    .catch(err => console.log('PWA ServiceWorker error:', err));
            });
        }
    </script>
    @livewireScripts
</body>
</html>
