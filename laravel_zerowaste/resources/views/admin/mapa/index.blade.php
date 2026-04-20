@extends('layouts.admin')

@section('title', 'Mapa de Querétaro')
@section('page_title', 'Puntos de Acopio en Santiago de Querétaro')

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        var map = L.map('qro-map').setView([20.5881, -100.3899], 13);

        var isDark = document.documentElement.classList.contains('dark');
        var lightTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO', maxZoom: 20
        });
        var darkTiles = L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; CARTO', maxZoom: 20
        });
        (isDark ? darkTiles : lightTiles).addTo(map);

        // Observar cambios de tema para cambiar tiles en tiempo real
        new MutationObserver(function() {
            var nowDark = document.documentElement.classList.contains('dark');
            if (nowDark && map.hasLayer(lightTiles)) { map.removeLayer(lightTiles); darkTiles.addTo(map); }
            else if (!nowDark && map.hasLayer(darkTiles)) { map.removeLayer(darkTiles); lightTiles.addTo(map); }
        }).observe(document.documentElement, { attributes: true, attributeFilter: ['class'] });

        var ecoIcon = L.divIcon({
            className: '',
            html: '<div style="background:#064E3B; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3); border:3px solid #00E096;"><svg viewBox="0 0 24 24" width="16" height="16" fill="#00E096"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17.98.3 1.34.3C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/></svg></div>',
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            popupAnchor: [0, -32]
        });

        let locations = {!! json_encode($locations ?? []) !!};

        locations.forEach(loc => {
            if(loc.latitud && loc.longitud) {
                let imgHtml = loc.imagen ? `<img src="https://zerowaste-qro.com/static/img/${loc.imagen}" class="w-full h-32 object-cover rounded-xl mb-3 shadow-md">` : '';
                L.marker([loc.latitud, loc.longitud], {icon: ecoIcon}).addTo(map)
                    .bindPopup(`
                        <div class="font-sans text-left min-w-[200px] p-1">
                            ${imgHtml}
                            <b class="text-emerald-800 text-base block mb-2 leading-tight">${loc.nombre}</b>
                            <span class="text-xs font-bold text-gray-600 bg-emerald-100 px-2 py-1 rounded-full inline-block shadow-sm">${loc.tipo}</span>
                            <div class="mt-3 bg-gray-50 p-2 rounded-lg border border-gray-100">
                                <p class="text-[11px] text-gray-700 leading-snug"><b class="block text-emerald-700 mb-0.5">Dirección:</b> ${loc.direccion}</p>
                            </div>
                            <p class="text-[10px] mt-2 text-gray-500 uppercase tracking-wide font-bold"><b>Materiales:</b> ${loc.materiales || 'N/A'}</p>
                        </div>
                    `, { closeButton: true, maxWidth: 220 });
            }
        });
    });

    // Confirmación SweetAlert para eliminar punto
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
    {{-- Sidebar con listado de puntos --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] shadow-[0_8px_30px_rgb(0,0,0,0.04)] border border-slate-100 dark:border-emerald-800/40 overflow-hidden flex flex-col h-[700px]">
        <div class="p-6 border-b border-slate-50 dark:border-emerald-800/30 flex items-center justify-between shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-[#00E096]/10 dark:bg-[#00E096]/20 flex items-center justify-center">
                    <span class="material-symbols-outlined text-[#00E096] dark:text-[#00E096] text-[20px]">pin_drop</span>
                </div>
                <h3 class="font-extrabold text-[#064E3B] dark:text-white text-[15px]">Puntos de Acopio <span class="bg-[#00E096] text-[#064E3B] px-2 py-0.5 rounded-full text-[11px] ml-1">{{ $locations->count() }}</span></h3>
            </div>
            <a href="{{ route('mapa.create') }}" class="text-xs bg-[#00E096] hover:bg-[#00c986] text-[#064E3B] px-4 py-2.5 rounded-full font-extrabold flex items-center gap-1 transition-transform hover:-translate-y-0.5 shadow-[0_4px_15px_rgba(0,224,150,0.3)]">
                <span class="material-symbols-outlined text-[16px]">add</span> Nuevo
            </a>
        </div>
        <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
            @forelse ($locations as $loc)
            <div class="mb-4 bg-white dark:bg-forest-dark p-4 rounded-2xl border border-slate-100 dark:border-emerald-800/30 shadow-sm hover:shadow-[0_8px_25px_rgba(0,0,0,0.06)] transition-all group">
                <div class="flex items-start gap-4">
                    <div class="shrink-0 w-12 h-12 rounded-full overflow-hidden bg-slate-50 border border-slate-100 dark:border-emerald-800/50 flex-shrink-0">
                        @if($loc->imagen)
                        <img src="https://zerowaste-qro.com/static/img/{{ $loc->imagen }}" alt="{{ $loc->nombre }}" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center bg-emerald-50 text-emerald-400">
                            <span class="material-symbols-outlined text-[20px]">image_not_supported</span>
                        </div>
                        @endif
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="font-bold text-xs text-[#064E3B] dark:text-white truncate">{{ $loc->nombre }}</h4>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400 truncate">{{ $loc->direccion }}</p>
                        <div class="flex items-center gap-1.5 mt-1">
                            <span class="px-1.5 py-0.5 bg-teal-100 dark:bg-teal-900/30 text-teal-700 dark:text-teal-300 rounded-full text-[9px] font-bold uppercase">{{ $loc->tipo }}</span>
                            <span class="text-[9px] text-gray-400 truncate">{{ \Illuminate\Support\Str::limit($loc->materiales ?? 'Sin materiales', 20) }}</span>
                        </div>
                    </div>
                    <div class="flex gap-1 shrink-0">
                        <a href="{{ route('mapa.edit', $loc) }}" class="w-8 h-8 rounded-lg bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110" title="Editar punto">
                            <span class="material-symbols-outlined text-[14px]">edit</span>
                        </a>
                        <form id="delete-form-{{ $loc->id }}" action="{{ route('mapa.destroy', $loc) }}" method="POST">
                            @csrf @method('DELETE')
                            <button type="button" onclick="confirmarEliminar('delete-form-{{ $loc->id }}', '{{ $loc->nombre }}')" class="w-8 h-8 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110" title="Eliminar punto">
                                <span class="material-symbols-outlined text-[14px]">delete</span>
                            </button>
                        </form>
                    </div>
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

    {{-- Mapa Ultra Premium --}}
    <div class="lg:col-span-3 relative w-full h-[700px] border-[6px] border-white dark:border-forest-card rounded-[2.5rem] overflow-hidden shadow-[0_8px_30px_rgb(0,0,0,0.08)] z-0">
        <div id="qro-map" class="w-full h-full z-0"></div>
    </div>
</div>
@endsection
