@extends('layouts.admin')

@section('title', 'Agregar Punto de Reciclaje')
@section('page_title', 'Agregar Punto de Reciclaje')

@section('content')
<div class="grid lg:grid-cols-2 gap-8">

    <!-- Mapa Interactivo -->
    <div class="bg-white dark:bg-forest-card rounded-[2rem] shadow-xl border-2 border-emerald-100 dark:border-emerald-800/50 overflow-hidden flex flex-col relative group">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-emerald-400/10 rounded-full blur-3xl pointer-events-none transition group-hover:bg-emerald-400/20"></div>
        <div class="p-6 border-b border-emerald-50 dark:border-emerald-800/50 flex flex-col gap-2 relative z-10">
            <h3 class="font-black text-2xl text-[#064E3B] dark:text-white flex items-center gap-3">
                <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-500 border border-emerald-500/20 shadow-inner">
                    <span class="material-symbols-outlined text-[24px]">pin_drop</span>
                </div>
                Ubicación Geográfica
            </h3>
            <p class="text-sm font-bold text-gray-500 dark:text-gray-400 ml-[60px]">Haz clic en el mapa interactivo para fijar el punto exacto.</p>
        </div>
        <div id="admin-map" class="w-full flex-1 min-h-[500px]"></div>
    </div>

<div class="bg-white/80 dark:bg-[#0B1F18]/80 backdrop-blur-xl rounded-[2rem] p-8 lg:p-10 shadow-2xl border border-white/50 dark:border-emerald-800/30 relative overflow-hidden group">
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-tr from-emerald-500/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>
        
        <div class="flex items-center gap-4 mb-8 relative z-10 border-b border-gray-100 dark:border-emerald-800/30 pb-6">
            <div class="w-14 h-14 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-[1.25rem] flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
                <span class="material-symbols-outlined text-[28px]">add_location_alt</span>
            </div>
            <div>
                <h2 class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight">Datos del Punto</h2>
                <p class="text-gray-500 dark:text-emerald-200/70 text-xs font-medium mt-1">Completa la información del centro de acopio.</p>
            </div>
        </div>

        <form id="customForm" novalidate action="{{ route('mapa.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-5 relative z-10">
            @csrf

            <input type="hidden" name="direccion" id="hidden-direccion">

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Nombre del Punto</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">storefront</span></div>
                    <input type="text" name="nombre" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20" required placeholder="Ej. Centro de Acopio UAQ">
                </div>
                <span id="err-nombre" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Calle y Colonia</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">signpost</span></div>
                        <input type="text" id="input-calle" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20" required placeholder="Ej. Av. Universidad">
                    </div>
                    <span id="err-calle" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Número</label>
                        <input type="text" id="input-numero" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20" required placeholder="Ej. 123">
                        <span id="err-numero" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                    </div>
                    <div>
                        <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">C.P.</label>
                        <input type="text" id="input-cp" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20" required placeholder="Ej. 76000">
                        <span id="err-cp" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-5">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Latitud</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">explore</span></div>
                        <input type="number" step="any" name="latitud" id="input-lat" class="w-full bg-emerald-50 dark:bg-[#064E3B]/20 border border-emerald-200 dark:border-emerald-800/50 text-emerald-900 dark:text-emerald-100 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 font-mono" required readonly placeholder="Haz clic en mapa">
                    </div>
                    <span id="err-latitud" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Longitud</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">explore</span></div>
                        <input type="number" step="any" name="longitud" id="input-lng" class="w-full bg-emerald-50 dark:bg-[#064E3B]/20 border border-emerald-200 dark:border-emerald-800/50 text-emerald-900 dark:text-emerald-100 text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 font-mono" required readonly placeholder="Haz clic en mapa">
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Tipo de Punto</label>
                <div class="relative custom-dropdown" tabindex="0">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">category</span></div>
                    <input type="hidden" name="tipo" id="tipo" required>
                    
                    <div class="dropdown-selected w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-500 dark:text-gray-400 text-sm rounded-xl block pl-11 pr-10 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 cursor-pointer flex justify-between items-center" onclick="this.nextElementSibling.classList.toggle('hidden')">
                        <span class="selected-text flex items-center gap-2">Selecciona una categoría...</span>
                        <span class="material-symbols-outlined text-gray-400 absolute right-3 pointer-events-none">expand_more</span>
                    </div>
                    
                    <div class="dropdown-options hidden absolute z-50 w-full mt-2 bg-white dark:bg-[#0F2A20] border border-gray-100 dark:border-emerald-800/50 rounded-xl shadow-xl overflow-hidden">
                        <div class="option-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-2 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Plástico">
                            <span class="material-symbols-outlined text-emerald-500 text-lg">recycling</span> Reciclaje de Plástico
                        </div>
                        <div class="option-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-2 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Vidrio">
                            <span class="material-symbols-outlined text-emerald-500 text-lg">wine_bar</span> Reciclaje de Vidrio
                        </div>
                        <div class="option-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-2 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Electrónicos">
                            <span class="material-symbols-outlined text-emerald-500 text-lg">devices</span> Desechos Electrónicos
                        </div>
                        <div class="option-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-2 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Centro Principal">
                            <span class="material-symbols-outlined text-emerald-500 text-lg">domain</span> Centro Principal
                        </div>
                        <div class="option-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-2 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Contenedor Público">
                            <span class="material-symbols-outlined text-emerald-500 text-lg">delete</span> Contenedor Público
                        </div>
                    </div>
                </div>
                <span id="err-tipo" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Materiales Aceptados (Opcional)</label>
                <div class="relative custom-dropdown-multi" tabindex="0">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">inventory_2</span></div>
                    <input type="hidden" name="materiales" id="materiales_hidden" value="">
                    
                    <div class="dropdown-selected-multi w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-500 dark:text-gray-400 text-sm rounded-xl block pl-11 pr-10 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 cursor-pointer flex justify-between items-center" onclick="this.nextElementSibling.classList.toggle('hidden')">
                        <span class="selected-text-multi truncate w-full pr-4 text-left">Selecciona materiales...</span>
                        <span class="material-symbols-outlined text-gray-400 absolute right-3 pointer-events-none">expand_more</span>
                    </div>
                    
                    <div class="dropdown-options-multi hidden absolute z-50 w-full mt-2 bg-white dark:bg-[#0F2A20] border border-gray-100 dark:border-emerald-800/50 rounded-xl shadow-xl overflow-hidden max-h-48 overflow-y-auto">
                        <div class="option-multi-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-3 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Cartón">
                            <div class="w-5 h-5 border-2 border-emerald-500 rounded flex items-center justify-center check-box"><span class="material-symbols-outlined text-sm text-white hidden">check</span></div>
                            <span class="material-symbols-outlined text-emerald-500 text-lg">inventory_2</span> Cartón
                        </div>
                        <div class="option-multi-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-3 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Plástico">
                            <div class="w-5 h-5 border-2 border-emerald-500 rounded flex items-center justify-center check-box"><span class="material-symbols-outlined text-sm text-white hidden">check</span></div>
                            <span class="material-symbols-outlined text-emerald-500 text-lg">recycling</span> Plástico
                        </div>
                        <div class="option-multi-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-3 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Latas">
                            <div class="w-5 h-5 border-2 border-emerald-500 rounded flex items-center justify-center check-box"><span class="material-symbols-outlined text-sm text-white hidden">check</span></div>
                            <span class="material-symbols-outlined text-emerald-500 text-lg">kitchen</span> Latas
                        </div>
                        <div class="option-multi-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-3 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Baterías">
                            <div class="w-5 h-5 border-2 border-emerald-500 rounded flex items-center justify-center check-box"><span class="material-symbols-outlined text-sm text-white hidden">check</span></div>
                            <span class="material-symbols-outlined text-emerald-500 text-lg">battery_charging_full</span> Baterías
                        </div>
                        <div class="option-multi-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-3 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Vidrio">
                            <div class="w-5 h-5 border-2 border-emerald-500 rounded flex items-center justify-center check-box"><span class="material-symbols-outlined text-sm text-white hidden">check</span></div>
                            <span class="material-symbols-outlined text-emerald-500 text-lg">wine_bar</span> Vidrio
                        </div>
                        <div class="option-multi-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-3 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="Electrónicos">
                            <div class="w-5 h-5 border-2 border-emerald-500 rounded flex items-center justify-center check-box"><span class="material-symbols-outlined text-sm text-white hidden">check</span></div>
                            <span class="material-symbols-outlined text-emerald-500 text-lg">devices</span> Electrónicos
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Fotografía del Punto</label>
                <div id="image-preview-container" class="mb-3 hidden">
                    <img id="image-preview" src="" alt="Vista previa" class="h-32 w-auto object-cover rounded-xl border border-emerald-300 dark:border-emerald-700/50 shadow-md">
                </div>
                <div class="relative group mt-1">
                    <input type="file" name="imagen_archivo" accept="image/*" onchange="previewFile(this)" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-dashed border-emerald-300 dark:border-emerald-700/50 rounded-xl p-4 dark:text-white text-sm file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 dark:file:bg-[#0B1F18] file:text-emerald-600 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50 file:cursor-pointer cursor-pointer transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                    <p class="text-xs text-gray-400 dark:text-emerald-600/70 mt-2 ml-1">JPG, PNG, WEBP permitidos.</p>
                </div>
            </div>

            <div class="flex justify-between items-center mt-4 pt-6 border-t border-gray-100 dark:border-emerald-800/30">
                <a href="{{ route('mapa.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-emerald-900/30 transition-colors flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">arrow_back</span> Cancelar
                </a>
                <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-500/30 transition-all duration-300 hover:-translate-y-1 flex items-center gap-2">
                    <span class="material-symbols-outlined text-lg">save</span> Guardar Punto
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
window.previewFile = function(input) {
    const previewContainer = document.getElementById('image-preview-container');
    const previewImage = document.getElementById('image-preview');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.classList.remove('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    } else {
        previewContainer.classList.add('hidden');
        previewImage.src = '';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Custom Dropdown JS Single
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown') && !e.target.closest('.custom-dropdown-multi')) {
            document.querySelectorAll('.dropdown-options, .dropdown-options-multi').forEach(el => el.classList.add('hidden'));
        }
    });

    document.querySelectorAll('.option-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.custom-dropdown');
            const hiddenInput = dropdown.querySelector('input[type="hidden"]');
            const selectedText = dropdown.querySelector('.selected-text');
            
            hiddenInput.value = this.dataset.value;
            selectedText.innerHTML = this.innerHTML;
            selectedText.classList.remove('text-gray-500', 'dark:text-gray-400');
            selectedText.classList.add('text-gray-900', 'dark:text-white');
            
            dropdown.querySelector('.dropdown-options').classList.add('hidden');
            document.getElementById('err-tipo').classList.add('hidden');
        });
    });

    // Custom Dropdown JS Multi
    let selectedMaterials = [];
    document.querySelectorAll('.option-multi-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const value = this.dataset.value;
            const checkBox = this.querySelector('.check-box');
            const checkIcon = this.querySelector('.check-box span');
            const dropdown = this.closest('.custom-dropdown-multi');
            const hiddenInput = dropdown.querySelector('input[type="hidden"]');
            const selectedText = dropdown.querySelector('.selected-text-multi');

            if (selectedMaterials.includes(value)) {
                selectedMaterials = selectedMaterials.filter(m => m !== value);
                checkBox.classList.remove('bg-emerald-500');
                checkIcon.classList.add('hidden');
            } else {
                selectedMaterials.push(value);
                checkBox.classList.add('bg-emerald-500');
                checkIcon.classList.remove('hidden');
            }

            hiddenInput.value = selectedMaterials.join(', ');
            
            if (selectedMaterials.length > 0) {
                selectedText.innerHTML = `<span class="font-bold text-emerald-600 dark:text-emerald-400">${selectedMaterials.length} seleccionados:</span> ${selectedMaterials.join(', ')}`;
                selectedText.classList.remove('text-gray-500', 'dark:text-gray-400');
                selectedText.classList.add('text-gray-900', 'dark:text-white');
            } else {
                selectedText.innerHTML = 'Selecciona materiales...';
                selectedText.classList.remove('text-gray-900', 'dark:text-white');
                selectedText.classList.add('text-gray-500', 'dark:text-gray-400');
            }
        });
    });

    const map = L.map('admin-map').setView([20.588, -100.389], 13);
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '&copy; CARTO'
    }).addTo(map);

    let marker = null;

    const ecoIcon = L.divIcon({
        className: '',
        html: '<div style="width:36px;height:36px;border-radius:50%;background:linear-gradient(135deg,#10B981,#06D6A0);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(16,185,129,0.5),0 0 0 3px rgba(255,255,255,0.9);"><svg viewBox="0 0 24 24" width="18" height="18" fill="#fff"><path d="M17 8C8 10 5.9 16.17 3.82 21.34l1.89.66.95-2.3c.48.17.98.3 1.34.3C19 20 22 3 22 3c-1 2-8 2.25-13 3.25S2 11.5 2 13.5s1.75 3.75 1.75 3.75C7 8 17 8 17 8z"/></svg></div>',
        iconSize: [36, 36],
        iconAnchor: [18, 36]
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

        marker.bindPopup(`<div style="display:flex;align-items:center;gap:6px;font-family:Inter,sans-serif;font-weight:bold;color:#064E3B;"><img src="/static/img/logo.png" style="width:16px;height:16px;filter:brightness(0.5);"/> ${lat}, ${lng}</div>`).openPopup();

        // Limpiar error de coordenadas si existía
        const errLat = document.getElementById('err-latitud');
        if (errLat) { errLat.classList.add('hidden'); }
        document.getElementById('input-lat').classList.remove('border-red-500');
    });

    // Geocoding automático
    const inCalle = document.getElementById('input-calle');
    const inNum = document.getElementById('input-numero');
    const inCP = document.getElementById('input-cp');
    const inLat = document.getElementById('input-lat');
    const inLng = document.getElementById('input-lng');

    function geocodeAddress() {
        const calle = inCalle.value.trim();
        const num = inNum.value.trim();
        const cp = inCP.value.trim();

        if (calle && num && cp) {
            const query = `${calle} ${num}, ${cp}, Querétaro, Mexico`;
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`)
                .then(res => res.json())
                .then(data => {
                    if (data && data.length > 0) {
                        const lat = parseFloat(data[0].lat);
                        const lng = parseFloat(data[0].lon);
                        
                        inLat.value = lat.toFixed(6);
                        inLng.value = lng.toFixed(6);
                        
                        const newLatLng = new L.LatLng(lat, lng);
                        map.setView(newLatLng, 15);
                        
                        if (marker) {
                            marker.setLatLng(newLatLng);
                        } else {
                            marker = L.marker(newLatLng, { icon: ecoIcon }).addTo(map);
                        }
                        marker.bindPopup(`<div style="display:flex;align-items:center;gap:6px;font-family:Inter,sans-serif;font-weight:bold;color:#064E3B;"><img src="/static/img/logo.png" style="width:16px;height:16px;filter:brightness(0.5);"/> ${lat.toFixed(4)}, ${lng.toFixed(4)}</div>`).openPopup();
                    }
                })
                .catch(err => console.error('Geocoding error:', err));
        }
    }

    inCalle.addEventListener('blur', geocodeAddress);
    inNum.addEventListener('blur', geocodeAddress);
    inCP.addEventListener('blur', geocodeAddress);

    // Validación inline
    const form = document.getElementById('customForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const nombre = form.querySelector('input[name="nombre"]');
            const inCalle = document.getElementById('input-calle');
            const inNum = document.getElementById('input-numero');
            const inCP = document.getElementById('input-cp');
            const hiddenDir = document.getElementById('hidden-direccion');
            
            const latitud = document.getElementById('input-lat');
            const tipo = document.getElementById('tipo');
            const tipoDropdown = tipo.closest('.custom-dropdown').querySelector('.dropdown-selected');

            const errNombre = document.getElementById('err-nombre');
            const errCalle = document.getElementById('err-calle');
            const errNum = document.getElementById('err-numero');
            const errCP = document.getElementById('err-cp');
            const errLatitud = document.getElementById('err-latitud');
            const errTipo = document.getElementById('err-tipo');

            // Reset
            [errNombre, errCalle, errNum, errCP, errLatitud, errTipo].forEach(el => el.classList.add('hidden'));
            [nombre, inCalle, inNum, inCP, latitud].forEach(el => el.classList.remove('border-red-500'));
            tipoDropdown.classList.remove('border-red-500');

            let isValid = true;

            if (!nombre.value.trim()) {
                errNombre.textContent = 'El nombre del punto es obligatorio.';
                errNombre.classList.remove('hidden');
                nombre.classList.add('border-red-500');
                isValid = false;
            }

            if (!inCalle.value.trim()) {
                errCalle.textContent = 'La calle/colonia es obligatoria.';
                errCalle.classList.remove('hidden');
                inCalle.classList.add('border-red-500');
                isValid = false;
            }
            if (!inNum.value.trim()) {
                errNum.textContent = 'Requerido.';
                errNum.classList.remove('hidden');
                inNum.classList.add('border-red-500');
                isValid = false;
            }
            if (!inCP.value.trim()) {
                errCP.textContent = 'Requerido.';
                errCP.classList.remove('hidden');
                inCP.classList.add('border-red-500');
                isValid = false;
            }

            if (!latitud.value) {
                errLatitud.textContent = 'Haz clic en el mapa para seleccionar una ubicación.';
                errLatitud.classList.remove('hidden');
                latitud.classList.add('border-red-500');
                isValid = false;
            }

            if (!tipo.value) {
                errTipo.textContent = 'Selecciona un tipo de punto.';
                errTipo.classList.remove('hidden');
                tipoDropdown.classList.add('border-red-500');
                isValid = false;
            }

            if (!isValid) {
                e.preventDefault();
            } else {
                // Construct single format direction
                hiddenDir.value = `${inCalle.value.trim()} #${inNum.value.trim()}, C.P. ${inCP.value.trim()}`;
            }
        });
    }
});
</script>
@endpush
