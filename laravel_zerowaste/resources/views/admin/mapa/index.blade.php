@extends('layouts.admin')

@section('title', 'Mapa de Querétaro')
@section('page_title', 'Puntos de Acopio en Santiago de Querétaro')

@push('scripts')
<link href="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.css" rel="stylesheet">
<script src="https://api.mapbox.com/mapbox-gl-js/v3.3.0/mapbox-gl.js"></script>
    <style>
    .mapboxgl-popup-content { border-radius: 1rem !important; box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important; transition: background-color 0.3s ease, border-color 0.3s ease; }
    html.dark .mapboxgl-popup-content { background: #022018 !important; border: 2px solid #00E096 !important; box-shadow: 0 15px 40px rgba(0,224,150,0.15) !important; color: white !important; }
    html.dark .mapboxgl-popup-tip { border-top-color: #022018 !important; border-bottom-color: #022018 !important; }
    /* Ocultar atribuciones */
    .mapboxgl-ctrl-attrib-inner a { display: none !important; }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            var isDark = document.documentElement.classList.contains('dark');
            mapboxgl.accessToken = '{{ env('MAPBOX_TOKEN', 'YOUR_MAPBOX_TOKEN_HERE') }}';
            var map = new mapboxgl.Map({
                container: 'qro-map',
                style: isDark ? 'mapbox://styles/mapbox/dark-v11' : 'mapbox://styles/mapbox/streets-v12',
                center: [-100.3899, 20.5881],
                zoom: 13,
                minZoom: 9,
                maxBounds: [
                    [-100.6, 19.9],
                    [-99.0, 21.7]
                ],
                attributionControl: true
            });
    
            map.addControl(new mapboxgl.NavigationControl(), 'bottom-right');

        // Ajustar al redimensionar ventana
        window.addEventListener('resize', function() {
            setTimeout(function() { map.resize(); }, 200);
        });

        // Cambiar tema del mapa
        new MutationObserver(function() {
            var nowDark = document.documentElement.classList.contains('dark');
            map.setStyle(nowDark ? 'mapbox://styles/mapbox/dark-v11' : 'mapbox://styles/mapbox/streets-v12');
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        let locations = {!! json_encode($locations ?? []) !!};

        locations.forEach(loc => {
            if(loc.latitud && loc.longitud) {
                // Marcador del punto
                const el = document.createElement('div');
                el.innerHTML = '<div style="background:#064E3B; width:44px; height:44px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3); border:3px solid #00E096; cursor:pointer;"><svg viewBox="0 0 24 24" width="22" height="22" fill="#00E096"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17.98.3 1.34.3C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/></svg></div>';

                let imgHtml = loc.imagen ? `<img src="https://zerowaste-qro.com/static/img/${loc.imagen}" class="w-full h-32 object-cover rounded-xl mb-3 shadow-md">` : '';
                
                const popup = new mapboxgl.Popup({ offset: 25, maxWidth: '220px' })
                    .setHTML(`
                        <div class="font-sans text-left min-w-[200px] p-1">
                            ${imgHtml}
                            <b class="text-emerald-800 dark:text-emerald-300 text-base block mb-2 leading-tight">${loc.nombre}</b>
                            <span class="text-xs font-bold text-gray-600 dark:text-emerald-100 bg-emerald-100 dark:bg-emerald-900 px-2 py-1 rounded-full inline-block shadow-sm">${loc.tipo}</span>
                            <div class="mt-3 bg-gray-50 dark:bg-emerald-900/40 p-2 rounded-lg border border-gray-100 dark:border-emerald-800">
                                <p class="text-[11px] text-gray-700 dark:text-gray-300 leading-snug"><b class="block text-emerald-700 dark:text-primary mb-0.5">Dirección:</b> ${loc.direccion}</p>
                            </div>
                            <p class="text-[10px] mt-2 text-gray-500 dark:text-gray-400 uppercase tracking-wide font-bold"><b>Materiales:</b> ${loc.materiales || 'N/A'}</p>
                        </div>
                    `);

                new mapboxgl.Marker({ element: el })
                    .setLngLat([loc.longitud, loc.latitud])
                    .setPopup(popup)
                    .addTo(map);
            }
        });
    });

    // Alerta para eliminar punto
    function confirmarEliminar(formId, nombre) {
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            html: `<div class="text-center">
                <div class="w-20 h-20 rounded-full mx-auto mb-5 flex items-center justify-center" style="background: linear-gradient(135deg, rgba(239,68,68,0.1), rgba(239,68,68,0.2)); border: 2px solid rgba(239,68,68,0.2);">
                    <span class="material-symbols-outlined text-red-500" style="font-size: 36px;">location_off</span>
                </div>
                <h3 style="font-size: 1.3rem; font-weight: 900; margin-bottom: 8px;">¿Eliminar punto?</h3>
                <p style="font-size: 0.875rem; opacity: 0.7;">El punto <b>"${nombre}"</b> será eliminado permanentemente del mapa.</p>
            </div>`,
            showCancelButton: true,
            confirmButtonColor: '#EF4444',
            cancelButtonColor: isDark ? '#1a3a2d' : '#E5E7EB',
            confirmButtonText: '<span class="font-bold flex items-center gap-2"><span class="material-symbols-outlined text-base">delete</span>Eliminar</span>',
            cancelButtonText: '<span class="font-bold">Cancelar</span>',
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
        <div class="flex-1 overflow-y-auto p-3">
            @forelse ($locations as $loc)
            <div class="mb-4 p-3 rounded-2xl border border-gray-100/60 dark:border-emerald-800/20 bg-white/50 dark:bg-white/[0.02] hover:bg-white dark:hover:bg-white/5 hover:shadow-[0_8px_30px_rgba(0,0,0,0.06)] transition-all duration-300 relative group">
                <div class="flex gap-3">
                    {{-- Imagen --}}
                    <div class="shrink-0 w-16 h-16 rounded-xl overflow-hidden bg-slate-100 dark:bg-emerald-900/30 border border-slate-200/60 dark:border-emerald-800/50">
                        @if($loc->imagen)
                        <img src="https://zerowaste-qro.com/static/img/{{ $loc->imagen }}" alt="{{ $loc->nombre }}" class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500">
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
                                <a href="{{ route('mapa.edit', $loc) }}" class="w-6 h-6 rounded-md bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 flex items-center justify-center transition-colors" title="Editar">
                                    <span class="material-symbols-outlined text-[13px]">edit</span>
                                </a>
                                <form id="delete-form-{{ $loc->id }}" action="{{ route('mapa.destroy', $loc) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="button" onclick="confirmarEliminar('delete-form-{{ $loc->id }}', '{{ $loc->nombre }}')" class="w-6 h-6 rounded-md bg-red-50 hover:bg-red-100 text-red-600 dark:bg-red-500/10 dark:text-red-400 flex items-center justify-center transition-colors" title="Eliminar">
                                        <span class="material-symbols-outlined text-[13px]">delete</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1 leading-relaxed" title="{{ $loc->direccion }}">
                            <span class="material-symbols-outlined text-[11px] align-middle mr-0.5">location_on</span>{{ $loc->direccion }}
                        </p>
                    </div>
                </div>
                {{-- Badge + materiales (debajo, separados) --}}
                <div class="flex items-center gap-2 mt-2.5 pt-2 border-t border-gray-100/60 dark:border-emerald-800/15">
                    <span class="px-2 py-0.5 bg-emerald-50 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 rounded-lg text-[9px] font-bold uppercase tracking-wide whitespace-nowrap shrink-0">{{ $loc->tipo }}</span>
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
        </div>
    </div>

    {{-- Mapa --}}
    <div class="lg:col-span-3 glass-card overflow-hidden relative" style="height:700px;border-radius:1.25rem">
        <div id="qro-map" class="w-full h-full z-0"></div>
    </div>
</div>
@endsection
