@extends('layouts.admin')

@section('title', 'Agregar Punto de Reciclaje')
@section('page_title', 'Agregar Punto de Reciclaje')

@section('content')
<div class="grid lg:grid-cols-2 gap-8">

    <!-- Mapa Interactivo -->
    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-gray-50">
            <h3 class="font-bold text-lg text-[#064E3B] flex items-center gap-2">
                <span class="material-symbols-outlined text-[#00E096]">pin_drop</span>
                Haz clic en el mapa para seleccionar ubicación
            </h3>
        </div>
        <div id="admin-map" style="height: 500px; width: 100%;"></div>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-2xl p-8 shadow-lg border border-gray-100">
        <form action="{{ route('mapa.store') }}" method="POST">
            @csrf

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Punto</label>
                <input type="text" name="nombre" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required placeholder="Ej. Centro de Acopio UAQ">
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Dirección</label>
                <input type="text" name="direccion" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required placeholder="Calle, Número, Colonia">
            </div>

            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Latitud</label>
                    <input type="number" step="any" name="latitud" id="input-lat" class="w-full px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors font-mono text-sm" required readonly placeholder="Haz clic en el mapa">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Longitud</label>
                    <input type="number" step="any" name="longitud" id="input-lng" class="w-full px-4 py-3 rounded-xl bg-emerald-50 border border-emerald-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors font-mono text-sm" required readonly placeholder="Haz clic en el mapa">
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-sm font-bold text-gray-700 mb-2">Tipo</label>
                <select name="tipo" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
                    <option value="">Selecciona un tipo...</option>
                    <option value="Plástico">♻️ Plástico</option>
                    <option value="Vidrio">🍷 Vidrio</option>
                    <option value="Electrónicos">💻 Electrónicos</option>
                    <option value="Centro Principal">🏢 Centro Principal</option>
                    <option value="Contenedor Público">📦 Contenedor Público</option>
                </select>
            </div>

            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2">Materiales Aceptados</label>
                <textarea name="materiales" rows="3" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" placeholder="PET, Cartón, Vidrio, Aluminio..."></textarea>
            </div>

            <div class="flex justify-between items-center pt-4 border-t border-gray-100">
                <a href="{{ route('mapa.index') }}" class="px-6 py-3 text-gray-500 font-bold hover:text-gray-700 transition-colors">
                    ← Cancelar
                </a>
                <button type="submit" class="px-8 py-3 bg-[#064E3B] text-white rounded-xl font-bold hover:bg-[#00E096] hover:text-[#064E3B] transition-colors shadow-lg shadow-[#00E096]/20 flex items-center gap-2">
                    <span class="material-symbols-outlined">save</span> Guardar Punto
                </button>
            </div>
        </form>
    </div>

</div>
@endsection

@push('scripts')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const map = L.map('admin-map').setView([20.588, -100.389], 13);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO'
    }).addTo(map);

    let marker = null;

    const ecoIcon = L.divIcon({
        className: '',
        html: '<div style="background:#064E3B; width:40px; height:40px; border-radius:50%; display:flex; align-items:center; justify-content:center; box-shadow:0 4px 12px rgba(0,0,0,0.3); border:3px solid #00E096;"><svg viewBox="0 0 24 24" width="20" height="20" fill="#00E096"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg></div>',
        iconSize: [40, 40],
        iconAnchor: [20, 40]
    });

    map.on('click', function(e) {
        const lat = e.latlng.lat.toFixed(6);
        const lng = e.latlng.lng.toFixed(6);

        document.getElementById('input-lat').value = lat;
        document.getElementById('input-lng').value = lng;

        if (marker) {
            marker.setLatLng(e.latlng);
        } else {
            marker = L.marker(e.latlng, { icon: ecoIcon }).addTo(map);
        }

        marker.bindPopup(`<b style="font-family:Inter,sans-serif;">📍 ${lat}, ${lng}</b>`).openPopup();
    });
});
</script>
@endpush
