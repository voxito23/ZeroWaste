@extends('layouts.admin')

@section('title', 'Mapa de Querétaro')
@section('page_title', 'Puntos de Acopio en Santiago de Querétaro')

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" crossorigin=""/>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" crossorigin=""></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Inicialización en coordenadas exactas de Santiago de Querétaro
        var map = L.map('qro-map').setView([20.5881, -100.3899], 13); 

        // 2. Capa gratuita de OpenStreetMap
        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; OpenStreetMap',
            maxZoom: 20
        }).addTo(map);

        // 3. Estilizamos un ícono personalizado
        var customIcon = new L.Icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
        });

        // 4. Recepción y renderización de los Puntos Dinámicos de la BD (zerowaste_db)
        let locations = {!! json_encode($locations ?? []) !!};
        
        locations.forEach(loc => {
            if(loc.latitud && loc.longitud) {
                L.marker([loc.latitud, loc.longitud], {icon: customIcon}).addTo(map)
                    .bindPopup(`
                        <div class="font-sans text-left min-w-[200px]">
                            <b class="text-emerald-800 text-lg">${loc.nombre}</b><br>
                            <span class="text-xs font-bold text-gray-600 bg-emerald-100 px-2 py-1 rounded-full mt-1 inline-block">${loc.tipo}</span>
                            <span class="text-sm mt-2 text-gray-700 block"><span class="font-bold">Dirección:</span> ${loc.direccion}</span>
                            ${loc.materiales_aceptados ? `<p class="mt-2 text-xs"><b class="text-emerald-600">Materiales:</b> ${loc.materiales_aceptados}</p>` : ''}
                        </div>
                    `);
            }
        });
    });
</script>
@endpush

@section('content')
<div class="relative w-full h-[700px] border-2 border-emerald-200 rounded-3xl overflow-hidden shadow-2xl">
    <div id="qro-map" class="w-full h-full z-0"></div>
    
    <!-- UI Overlay tipo Flask -->
    <div class="absolute top-6 left-6 z-[1000] w-80 bg-white/95 backdrop-blur-md p-5 rounded-2xl shadow-xl border border-emerald-50">
        <h2 class="font-bold text-xl text-[#064E3B] mb-2">Puntos Cercanos</h2>
        <p class="text-sm text-gray-500 mb-4">Encuentra dónde reciclar en Qro.</p>
        <div class="flex flex-wrap gap-2 text-xs font-bold">
            <span class="px-3 py-1 rounded-full bg-[#064E3B] text-white cursor-pointer">Todos</span>
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 cursor-pointer">Electrónicos</span>
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 cursor-pointer">PET</span>
        </div>
    </div>
</div>
@endsection
