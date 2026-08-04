@extends('layouts.admin')

@section('title', 'Mapa de Querétaro')
@section('page_title', 'Puntos de Acopio en Santiago de Querétaro')

@push('scripts')
<link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet">
<script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
<script src="/static/js/mapbox-map.js"></script>
    <style>
    .mapboxgl-popup-content { border-radius: 1.5rem !important; padding: 10px !important; border: 1px solid #d1fae5 !important; box-shadow: 0 18px 50px rgba(6,78,59,0.2) !important; transition: background-color 0.3s ease, border-color 0.3s ease; }
    .mapboxgl-popup-close-button { font-size: 24px !important; width: 36px !important; height: 36px !important; line-height: 36px !important; right: 4px !important; top: 4px !important; color: #064E3B !important; font-weight: 900 !important; background: rgba(255,255,255,0.9) !important; border-radius: 50% !important; display: flex !important; align-items: center !important; justify-content: center !important; box-shadow: 0 2px 8px rgba(0,0,0,0.12) !important; z-index: 50 !important; }
    .mapboxgl-popup-close-button:hover { background: #064E3B !important; color: white !important; }
    html.dark .mapboxgl-popup-close-button { color: #00E096 !important; background: rgba(2,32,24,0.9) !important; }
    html.dark .mapboxgl-popup-close-button:hover { background: #00E096 !important; color: #022018 !important; }
    html.dark .mapboxgl-popup-content { background: #022018 !important; border: 2px solid #00E096 !important; box-shadow: 0 15px 40px rgba(0,224,150,0.15) !important; color: white !important; }
    html.dark .mapboxgl-popup-tip { border-top-color: #022018 !important; border-bottom-color: #022018 !important; }
    /* Ocultar atribuciones */
    .mapboxgl-ctrl-attrib-inner a { display: none !important; }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var isDark = document.documentElement.classList.contains('dark');
            const showMapError = (message) => {
                document.getElementById('qro-map-error-message').textContent = message;
                document.getElementById('qro-map-error').classList.remove('hidden');
                document.getElementById('qro-map-error').classList.add('flex');
            };
            var map = window.ZeroWasteMapbox.createMap({
                container: 'qro-map',
                token: @json($mapboxToken ?? ''),
                dark: isDark,
                center: [-100.3899, 20.5881],
                zoom: 13,
                minZoom: 9,
                maxBounds: [
                    [-100.6, 19.9],
                    [-99.0, 21.7]
                ],
                onError: showMapError,
                onReady: () => {
                    document.getElementById('qro-map-error').classList.add('hidden');
                    document.getElementById('qro-map-error').classList.remove('flex');
                },
            });
            if (!map) return;
    
            map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');

        // Ajustar al redimensionar ventana
        window.addEventListener('resize', function() {
            setTimeout(function() { map.resize(); }, 200);
        });

        // Cambiar tema del mapa
        new MutationObserver(function() {
            var nowDark = document.documentElement.classList.contains('dark');
            window.ZeroWasteMapbox.setTheme(map, nowDark);
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        const fallbackLocations = window.ZeroWasteMapbox.normalizePoints(@json($mapLocations ?? []));
        const escapeHtml = (value) => String(value ?? '').replace(/[&<>'"]/g, (character) => ({ '&':'&amp;', '<':'&lt;', '>':'&gt;', "'":'&#039;', '"':'&quot;' })[character]);

        window.ZeroWasteMapbox.fetchPoints('/api/mapa/puntos')
            .catch(() => fallbackLocations)
            .then((locations) => locations.forEach(loc => {
            if(loc.latitud && loc.longitud) {
                // Marcador del punto
                const el = document.createElement('div');
                el.innerHTML = '<div style="background:#064E3B; width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3); border:3px solid #00E096; cursor:pointer;"><svg viewBox="0 0 24 24" width="22" height="22" fill="#00E096"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17.98.3 1.34.3C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/></svg></div>';

                const safeName = escapeHtml(loc.nombre || 'Punto de acopio');
                const safeType = escapeHtml(loc.tipo || 'Centro de acopio');
                const safeAddress = escapeHtml(loc.direccion || 'Dirección no disponible');
                const safeMaterials = escapeHtml(loc.materiales || 'Múltiples materiales');
                const safeImage = /^https:\/\/[A-Za-z0-9.-]+(?:[:][0-9]+)?\/[A-Za-z0-9_~!$&'()*+,;=:@%\/.?-]+$/.test(String(loc.image_url || '')) ? escapeHtml(loc.image_url) : '';
                let imgHtml = safeImage ? `<img src="${safeImage}" alt="Imagen de ${safeName}" class="h-36 w-full rounded-2xl object-cover" onerror="this.style.display='none'">` : '<div class="flex h-24 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600"><span class="material-symbols-outlined text-3xl">recycling</span></div>';
                
                const popup = new mapboxgl.Popup({ offset: 28, maxWidth: '310px' })
                    .setHTML(`
                        <div class="min-w-[260px] p-1 text-left font-sans">
                            ${imgHtml}
                            <div class="mt-4 flex items-start justify-between gap-3"><b class="block text-lg font-black leading-tight text-emerald-950 dark:text-emerald-200">${safeName}</b><span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-emerald-100 px-2 py-1 text-[10px] font-black uppercase tracking-wide text-emerald-800"><span class="material-symbols-outlined text-sm text-emerald-600">check_circle</span>Activo</span></div>
                            <span class="mt-2 inline-block rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600 dark:bg-emerald-900 dark:text-emerald-100">${safeType}</span>
                            <div class="mt-3 rounded-2xl border border-emerald-100 bg-emerald-50/70 p-3 dark:border-emerald-800 dark:bg-emerald-900/40">
                                <p class="text-xs leading-5 text-slate-700 dark:text-gray-300"><b class="mb-1 flex items-center gap-1 text-emerald-800 dark:text-primary"><span class="material-symbols-outlined text-base">location_on</span>Dirección</b>${safeAddress}</p>
                            </div>
                            <p class="mt-3 text-xs leading-5 text-gray-500 dark:text-gray-300"><b class="text-slate-700 dark:text-white">Materiales:</b> ${safeMaterials}</p>
                        </div>
                    `);

                new mapboxgl.Marker({ element: el })
                    .setLngLat([loc.longitud, loc.latitud])
                    .setPopup(popup)
                    .addTo(map);
            }
            }));
    });

    // Alerta para eliminar punto
    function confirmarEliminar(formId, nombre) {
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            html: `<div class="text-center">
                <div class="w-20 h-20 rounded-full mx-auto mb-5 flex items-center justify-center" style="background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.2)); border: 2px solid rgba(239,68,68,0.2);">
                    <span class="material-symbols-outlined text-red-500" style="font-size: 36px;">location_off</span>
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 900; margin-bottom: 8px;">¿Eliminar punto de reciclaje?</h3>
                <p style="font-size: 0.875rem; opacity: 0.7;">Esta acción retirará el punto de las vistas públicas. Los registros históricos se conservarán.</p>
            </div>`,
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: isDark ? '#1a3a2d' : '#E5E7EB',
            confirmButtonText: '<span class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-base">delete</span>Eliminar punto</span>',
            cancelButtonText: '<span class="font-bold" style="color: ' + (isDark ? '#D1FAE5' : '#1F2937') + ';">Cancelar</span>',
            background: isDark ? '#0F2A20' : '#fff',
            color: isDark ? '#D1FAE5' : '#064E3B',
            width: 380,
            customClass: {
                popup: 'rounded-[2rem] border shadow-2xl',
                confirmButton: 'rounded-full px-6 py-2.5',
                cancelButton: 'rounded-full px-6 py-2.5'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        });
    }
</script>
@endpush

@section('content')


<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    {{-- Sidebar --}}
    <div class="glass-card overflow-hidden flex flex-col h-[700px]">
        <div class="p-5 border-b border-gray-100/50 dark:border-emerald-800/20 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg flex items-center justify-center" style="background:rgba(16,185,129,0.1)">
                    <span class="material-symbols-outlined text-emerald-500 text-lg">pin_drop</span>
                </div>
                <h3 class="font-black text-sm text-[#064E3B] dark:text-white">Puntos <span class="badge-sm bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 ml-1">{{ $locations->count() }}</span></h3>
            </div>
            <a href="{{ route('mapa.create') }}" class="btn-primary text-xs py-2 px-3">
                <span class="material-symbols-outlined text-sm">add</span> Nuevo
            </a>
        </div>
        <form method="GET" class="grid grid-cols-2 gap-2 border-b border-slate-100 p-3"><input class="input-premium col-span-2" name="q" value="{{ request('q') }}" placeholder="Buscar punto…"><select class="input-premium" name="estado"><option value="">Todos</option><option value="activo" @selected(request('estado')==='activo')>Activos</option><option value="inactivo" @selected(request('estado')==='inactivo')>Inactivos</option></select><select class="input-premium" name="qr"><option value="">Cualquier QR</option><option value="con" @selected(request('qr')==='con')>Con QR</option><option value="sin" @selected(request('qr')==='sin')>Sin QR</option></select><input class="input-premium" name="material" value="{{ request('material') }}" placeholder="Material"><button class="btn-secondary justify-center">Filtrar</button></form>
        <div class="flex-1 overflow-y-auto p-3">
            @forelse ($locations as $loc)
            <div class="mb-4 p-3 rounded-2xl border border-gray-100/60 dark:border-emerald-800/20 bg-white/50 dark:bg-white/[0.02] hover:bg-white dark:hover:bg-white/5 hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] transition-all duration-300 relative group">
                <div class="flex gap-3">
                    {{-- Imagen --}}
                    <div class="shrink-0 w-16 h-16 rounded-xl overflow-hidden bg-slate-100 dark:bg-emerald-900/30 border border-slate-200/60 dark:border-emerald-800/50">
                        @if($loc->image_url)
                        <img src="{{ $loc->image_url }}" alt="{{ $loc->nombre }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" onerror="this.style.display='none'">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-emerald-400/50">
                            <span class="material-symbols-outlined text-[24px]">image_not_supported</span>
                        </div>
                        @endif
                    </div>

                    {{-- Contenido --}}
                    <div class="flex-1 min-w-0">
                        <div class="flex justify-between items-start gap-1">
                            <h4 class="font-bold text-[13px] text-[#064E3B] dark:text-white leading-snug" title="{{ $loc->nombre }}">{{ $loc->nombre }}</h4>
                            
                            {{-- Acciones --}}
                            <div class="flex gap-1 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity duration-200 shrink-0">
                                @if(!$loc->trashed())<a href="{{ route('mapa.edit', $loc) }}" class="w-6 h-6 rounded-md bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 flex items-center justify-center transition-colors" title="Editar">
                                    <span class="material-symbols-outlined text-[13px]">edit</span>
                                </a>
                                <form action="{{ route('mapa.qr.generate', $loc) }}" method="POST">@csrf
                                    <button type="submit" class="w-6 h-6 rounded-md bg-emerald-50 hover:bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400 flex items-center justify-center transition-colors" title="Generar o ver QR">
                                        <span class="material-symbols-outlined text-sm">qr_code_2</span>
                                    </button>
                                </form>
                                <form id="delete-form-{{ $loc->id }}" action="{{ route('mapa.destroy', $loc) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmarEliminar('delete-form-{{ $loc->id }}', '{{ $loc->nombre }}')" class="w-6 h-6 rounded-md bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400 flex items-center justify-center transition-colors" title="Eliminar">
                                        <span class="material-symbols-outlined text-[13px]">delete</span>
                                    </button>
                                </form>
                                @else<form action="{{ route('mapa.reactivate', $loc->id) }}" method="POST">@csrf<button class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-700" title="Reactivar"><span class="material-symbols-outlined text-sm">restore</span></button></form>@endif
                            </div>
                        </div>

                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 leading-relaxed" title="{{ $loc->direccion }}">
                            <span class="material-symbols-outlined text-[11px] align-middle mr-0.5">location_on</span>{{ $loc->direccion }}
                        </p>
                    </div>
                </div>
                {{-- Badge + materiales (debajo, separados) --}}
                <div class="flex items-center gap-2 mt-2.5 pt-2 border-t border-gray-100/60 dark:border-emerald-800/15">
                    <span class="inline-flex items-center gap-1 rounded-lg bg-emerald-50 px-2 py-0.5 text-[9px] font-bold uppercase tracking-wide text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300"><span class="material-symbols-outlined text-[12px]">{{ $loc->trashed() ? 'pause_circle' : 'check_circle' }}</span>{{ $loc->trashed() ? 'Inactivo' : 'Activo' }}</span><span class="px-2 py-0.5 bg-slate-100 dark:bg-white/5 text-slate-600 dark:text-slate-300 rounded-lg text-[9px] font-bold uppercase tracking-wide whitespace-nowrap shrink-0">{{ $loc->tipo }}</span>
                    <span class="text-[9px] text-gray-400 dark:text-gray-500 truncate" title="{{ $loc->materiales }}">{{ $loc->materiales ?? 'Múltiples materiales' }}</span>
                </div>
            </div>
            @empty
            <div class="p-8 text-center">
                <span class="material-symbols-outlined text-4xl text-gray-300 dark:text-gray-600 block mb-2">map</span>
                <p class="text-gray-400 dark:text-gray-500 italic text-sm">No hay puntos registrados.</p>
                <a href="{{ route('mapa.create') }}" class="inline-block mt-3 text-primary font-bold text-sm hover:underline">+ Agregar primer punto</a>
            </div>
            @endforelse
        </div><div class="border-t border-slate-100 p-3">{{ $locations->links() }}</div>
    </div>

    {{-- Mapa --}}
    <div class="lg:col-span-3 glass-card overflow-hidden relative" style="height:700px;border-radius:1.25rem">
        <div id="qro-map" class="w-full h-full z-0"></div>
        <div id="qro-map-error" class="hidden absolute inset-0 z-20 bg-white/95 dark:bg-[#062e23]/95 items-center justify-center p-8 text-center">
            <div>
                <span class="material-symbols-outlined text-4xl text-emerald-600">map</span>
                <p id="qro-map-error-message" class="mt-3 font-bold text-[#064E3B] dark:text-white">No fue posible cargar el mapa.</p>
                <button type="button" onclick="window.location.reload()" class="btn-primary mt-4">Reintentar</button>
            </div>
        </div>
    </div>
</div>
@endsection
