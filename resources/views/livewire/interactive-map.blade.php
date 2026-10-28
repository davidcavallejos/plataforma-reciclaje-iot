<div>
    <!-- Map Header -->
    <div class="mb-6 p-6 rounded-3xl glass-card border border-teal-500/20 bg-gradient-to-r from-slate-900 via-teal-950/30 to-slate-900 shadow-xl flex items-center justify-between">
        <div>
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-500/20 text-teal-300 text-xs font-semibold border border-teal-500/30 mb-2">
                <i class="fa-solid fa-map-location-dot text-teal-400"></i> Geolocalización Inteligente Leaflet.js
            </span>
            <h1 class="heading-font text-2xl font-bold text-slate-100">
                Mapa Ecológico de Reciclaje
            </h1>
            <p class="text-xs text-slate-300 mt-1">
                Visualiza publicaciones activas y centros de acopio en tiempo real. Haz clic en los marcadores para contactar por WhatsApp.
            </p>
        </div>

        <div class="hidden sm:flex items-center gap-4 text-xs font-semibold">
            <div class="flex items-center gap-1.5 bg-slate-900/80 px-3 py-1.5 rounded-xl border border-emerald-500/40">
                <span class="w-3 h-3 rounded-full bg-emerald-500"></span> Publicación Reciclaje
            </div>
            <div class="flex items-center gap-1.5 bg-slate-900/80 px-3 py-1.5 rounded-xl border border-cyan-500/40">
                <span class="w-3 h-3 rounded-full bg-cyan-400"></span> Centro de Acopio
            </div>
        </div>
    </div>

    <!-- Leaflet Map Container -->
    <div class="glass-card rounded-3xl overflow-hidden border border-slate-700/80 shadow-2xl p-2">
        <div id="map" class="w-full h-[600px] rounded-2xl z-10"></div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const locations = {!! $locationsJson !!};

            // Initialize map centered around sample Bolivia lat/lng
            const map = L.map('map').setView([-16.500000, -68.140000], 12);

            // Dark Mode Tile Layer (CartoDB Dark Matter)
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                attribution: '&copy; OpenStreetMap &copy; CARTO',
                subdomains: 'abcd',
                maxZoom: 19
            }).addTo(map);

            // Custom Leaflet Icons
            const postIcon = L.divIcon({
                className: 'custom-leaflet-marker',
                html: `<div style="background-color: #10b981; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 4px 10px rgba(16,185,129,0.5);"><i class="fa-solid fa-recycle text-sm"></i></div>`,
                iconSize: [34, 34],
                iconAnchor: [17, 34],
                popupAnchor: [0, -34]
            });

            const businessIcon = L.divIcon({
                className: 'custom-leaflet-marker',
                html: `<div style="background-color: #06b6d4; width: 38px; height: 38px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; border: 3px solid white; box-shadow: 0 4px 12px rgba(6,182,212,0.6);"><i class="fa-solid fa-building-user text-sm"></i></div>`,
                iconSize: [38, 38],
                iconAnchor: [19, 38],
                popupAnchor: [0, -38]
            });

            locations.forEach(loc => {
                const icon = loc.type === 'post' ? postIcon : businessIcon;
                const marker = L.marker([loc.latitude, loc.longitude], { icon: icon }).addTo(map);

                let popupContent = '';
                if (loc.type === 'post') {
                    popupContent = `
                        <div style="font-family: 'Inter', sans-serif; min-width: 220px; color: #0f172a; padding: 4px;">
                            ${loc.image_url ? `<img src="${loc.image_url}" style="width: 100%; height: 110px; object-fit: cover; border-radius: 12px; margin-bottom: 8px;">` : ''}
                            <span style="font-size: 10px; font-weight: bold; background: #d1fae5; color: #047857; padding: 2px 8px; border-radius: 10px; text-transform: uppercase;">${loc.category}</span>
                            <h4 style="font-size: 14px; font-weight: bold; margin: 6px 0 2px 0; color: #0f172a;">${loc.title}</h4>
                            <p style="font-size: 11px; color: #64748b; margin-bottom: 8px;">📍 ${loc.address}</p>
                            <a href="${loc.whatsapp_url}" target="_blank" style="display: block; width: 100%; background: #059669; color: white; text-align: center; padding: 8px; border-radius: 10px; font-weight: bold; text-decoration: none; font-size: 12px; box-shadow: 0 4px 8px rgba(5,150,105,0.3);">
                                🟢 Contactar por WhatsApp
                            </a>
                        </div>
                    `;
                } else {
                    popupContent = `
                        <div style="font-family: 'Inter', sans-serif; min-width: 220px; color: #0f172a; padding: 4px;">
                            <span style="font-size: 10px; font-weight: bold; background: #cff4fc; color: #087990; padding: 2px 8px; border-radius: 10px;">${loc.category}</span>
                            <h4 style="font-size: 15px; font-weight: bold; margin: 6px 0 2px 0;">🏢 ${loc.title}</h4>
                            <p style="font-size: 11px; color: #059669; font-weight: bold; margin-bottom: 4px;">📦 Materiales: ${loc.materials}</p>
                            <p style="font-size: 11px; color: #64748b; margin-bottom: 10px;">📍 ${loc.address}</p>
                            <a href="${loc.whatsapp_url}" target="_blank" style="display: block; width: 100%; background: #087990; color: white; text-align: center; padding: 8px; border-radius: 10px; font-weight: bold; text-decoration: none; font-size: 12px;">
                                💬 WhatsApp Centro Acopio
                            </a>
                        </div>
                    `;
                }

                marker.bindPopup(popupContent);
            });
        });
    </script>
</div>
