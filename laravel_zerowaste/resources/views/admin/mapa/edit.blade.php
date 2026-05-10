@extends('layouts.admin')

@section('title', 'Editar Punto de Reciclaje')
@section('page_title', 'Editar Punto')

@section('content')
<div class="bg-white/80 dark:bg-[#0B1F18]/80 backdrop-blur-xl rounded-[2rem] p-8 lg:p-10 shadow-2xl border border-white/50 dark:border-emerald-800/30 relative overflow-hidden group max-w-4xl mx-auto">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-gradient-to-br from-emerald-400/20 to-teal-500/10 rounded-full blur-3xl pointer-events-none transition group-hover:bg-emerald-400/20"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-tr from-emerald-500/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex items-center gap-4 mb-10 relative z-10 border-b border-gray-100 dark:border-emerald-800/30 pb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-[1.25rem] flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
            <span class="material-symbols-outlined text-[32px]">edit_location_alt</span>
        </div>
        <div>
            <h2 class="text-3xl font-black text-[#064E3B] dark:text-white tracking-tight">Editar Punto</h2>
            <p class="text-gray-500 dark:text-emerald-200/70 text-sm font-medium mt-1">Modifica la información de este centro de acopio.</p>
        </div>
    </div>

    @php
        // Attempt to parse 'Calle #Num, C.P. CP' format
        $calleStr = $location->direccion;
        $numStr = '';
        $cpStr = '';
        if (preg_match('/^(.*?)\s+#(.*?),?\s*(?:C\.?P\.?|CP)\s*(\d+)$/i', $calleStr, $m)) {
            $calleStr = trim($m[1]);
            $numStr = trim($m[2]);
            $cpStr = trim($m[3]);
        }
    @endphp

    <form id="customForm" novalidate action="{{ route('mapa.update', $location) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6 relative z-10">
        @csrf
        @method('PUT')

        <input type="hidden" name="direccion" id="hidden-direccion" value="{{ $location->direccion }}">

        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Nombre del Punto</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">storefront</span></div>
                    <input type="text" name="nombre" value="{{ $location->nombre }}" required class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                </div>
                <span id="err-nombre" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Calle y Colonia</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">signpost</span></div>
                        <input type="text" id="input-calle" value="{{ $calleStr }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20" required placeholder="Ej. Av. Universidad">
                    </div>
                    <span id="err-calle" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Número</label>
                        <input type="text" id="input-numero" value="{{ $numStr }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20" required placeholder="Ej. 123">
                        <span id="err-numero" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                    </div>
                    <div>
                        <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">C.P.</label>
                        <input type="text" id="input-cp" value="{{ $cpStr }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20" required placeholder="Ej. 76000">
                        <span id="err-cp" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Latitud</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">explore</span></div>
                        <input type="number" step="any" name="latitud" id="input-lat" value="{{ $location->latitud }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 font-mono" required>
                    </div>
                    <span id="err-latitud" class="hidden text-red-500 text-xs mt-1.5 font-medium ml-1"></span>
                </div>
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Longitud</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">explore</span></div>
                        <input type="number" step="any" name="longitud" id="input-lng" value="{{ $location->longitud }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 font-mono" required>
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Tipo de Punto</label>
                <div class="relative custom-dropdown" tabindex="0">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">category</span></div>
                    <input type="hidden" name="tipo" id="tipo" value="{{ $location->tipo }}" required>
                    
                    @php
                        $iconMap = [
                            'Plástico' => 'recycling',
                            'Vidrio' => 'wine_bar',
                            'Electrónicos' => 'devices',
                            'Centro Principal' => 'domain',
                            'Contenedor Público' => 'delete'
                        ];
                        $currentIcon = $iconMap[$location->tipo] ?? 'category';
                    @endphp
                    
                    <div class="dropdown-selected w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl block pl-11 pr-10 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 cursor-pointer flex justify-between items-center" onclick="this.nextElementSibling.classList.toggle('hidden')">
                        <span class="selected-text flex items-center gap-2">
                            <span class="material-symbols-outlined text-emerald-500 text-lg">{{ $currentIcon }}</span> {{ $location->tipo ?: 'Selecciona una categoría...' }}
                        </span>
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
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Materiales Aceptados (Opcional)</label>
                <div class="relative custom-dropdown-multi" tabindex="0">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">inventory_2</span></div>
                    <input type="hidden" name="materiales" id="materiales_hidden" value="{{ $location->materiales }}">
                    
                    <div class="dropdown-selected-multi w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-500 dark:text-gray-400 text-sm rounded-xl block pl-11 pr-10 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 cursor-pointer flex justify-between items-center" onclick="this.nextElementSibling.classList.toggle('hidden')">
                        <span class="selected-text-multi truncate w-full pr-4 text-left">Selecciona materiales...</span>
                        <span class="material-symbols-outlined text-gray-400 absolute right-3 pointer-events-none">expand_more</span>
                    </div>
                    
                    <div class="dropdown-options-multi hidden absolute z-50 w-full mt-2 bg-white dark:bg-[#0F2A20] border border-gray-100 dark:border-emerald-800/50 rounded-xl shadow-xl overflow-hidden max-h-48 overflow-y-auto">
                        @php
                            $selectedMaterials = array_map('trim', explode(',', $location->materiales));
                            $options = [
                                'PET' => 'water_bottle',
                                'Tapitas' => 'circles',
                                'Cartón' => 'inventory_2',
                                'Plástico' => 'recycling',
                                'Latas' => 'kitchen',
                                'Baterías' => 'battery_charging_full',
                                'Vidrio' => 'wine_bar',
                                'Electrónicos' => 'devices'
                            ];
                        @endphp
                        @foreach($options as $val => $icon)
                        <div class="option-multi-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-3 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="{{ $val }}">
                            <div class="w-5 h-5 border-2 border-emerald-500 rounded flex items-center justify-center check-box {{ in_array($val, $selectedMaterials) ? 'bg-emerald-500' : '' }}">
                                <span class="material-symbols-outlined text-sm text-white {{ in_array($val, $selectedMaterials) ? '' : 'hidden' }}">check</span>
                            </div>
                            <span class="material-symbols-outlined text-emerald-500 text-lg">{{ $icon }}</span> {{ $val }}
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Fotografía del Punto</label>
                @if($location->imagen && $location->imagen !== 'default_punto.png')
                <div class="mb-3 flex items-center gap-3 bg-gray-50 dark:bg-[#0B1F18] p-3 rounded-xl border border-gray-200 dark:border-emerald-800/30">
                    <img src="https://zerowaste-qro.com/static/img/{{ $location->imagen }}" alt="Imagen actual" class="h-16 w-24 object-cover rounded-lg border border-emerald-200 dark:border-emerald-700/50" onerror="this.style.display='none'">
                    <span class="text-xs font-bold text-gray-500 dark:text-emerald-500/80">Imagen actual: {{ $location->imagen }}</span>
                </div>
                @endif
                <div id="image-preview-container" class="mb-3 hidden">
                    <img id="image-preview" src="" alt="Vista previa" class="h-32 w-auto object-cover rounded-xl border border-emerald-300 dark:border-emerald-700/50 shadow-md">
                </div>
                <div class="relative group mt-1">
                    <input type="file" name="imagen_archivo" accept="image/*" onchange="previewFile(this)" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-dashed border-emerald-300 dark:border-emerald-700/50 rounded-xl p-4 dark:text-white text-sm file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 dark:file:bg-[#0B1F18] file:text-emerald-600 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50 file:cursor-pointer cursor-pointer transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                    <p class="text-xs text-gray-400 dark:text-emerald-600/70 mt-2 ml-1">Dejar vacío para mantener la imagen actual</p>
                </div>
            </div>
        </div>

        <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-100 dark:border-emerald-800/30">
            <a href="{{ route('mapa.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-emerald-900/30 transition-colors flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">arrow_back</span> Cancelar
            </a>
            <button type="submit" class="bg-gradient-to-r from-emerald-500 to-teal-600 hover:from-emerald-400 hover:to-teal-500 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-emerald-500/30 transition-all duration-300 hover:-translate-y-1 flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">save</span> Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
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
        });
    });

    // Custom Dropdown JS Multi
    let selectedMaterials = document.getElementById('materiales_hidden').value.split(',').map(s => s.trim()).filter(s => s);
    
    function updateMultiText() {
        const selectedText = document.querySelector('.selected-text-multi');
        if (selectedMaterials.length > 0) {
            selectedText.innerHTML = `<span class="font-bold text-emerald-600 dark:text-emerald-400">${selectedMaterials.length} seleccionados:</span> ${selectedMaterials.join(', ')}`;
            selectedText.classList.remove('text-gray-500', 'dark:text-gray-400');
            selectedText.classList.add('text-gray-900', 'dark:text-white');
        } else {
            selectedText.innerHTML = 'Selecciona materiales...';
            selectedText.classList.remove('text-gray-900', 'dark:text-white');
            selectedText.classList.add('text-gray-500', 'dark:text-gray-400');
        }
    }
    updateMultiText(); // Initialize on load

    document.querySelectorAll('.option-multi-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const value = this.dataset.value;
            const checkBox = this.querySelector('.check-box');
            const checkIcon = this.querySelector('.check-box span');
            const hiddenInput = document.getElementById('materiales_hidden');

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
            updateMultiText();
        });
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
                    }
                })
                .catch(err => console.error('Geocoding error:', err));
        }
    }

    inCalle.addEventListener('blur', geocodeAddress);
    inNum.addEventListener('blur', geocodeAddress);
    inCP.addEventListener('blur', geocodeAddress);

    const form = document.getElementById('customForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const nombre = form.querySelector('input[name="nombre"]');
        const inCalle = document.getElementById('input-calle');
        const inNum = document.getElementById('input-numero');
        const inCP = document.getElementById('input-cp');
        const hiddenDir = document.getElementById('hidden-direccion');
        const latitud = form.querySelector('input[name="latitud"]');

        const errNombre = document.getElementById('err-nombre');
        const errCalle = document.getElementById('err-calle');
        const errNum = document.getElementById('err-numero');
        const errCP = document.getElementById('err-cp');
        const errLatitud = document.getElementById('err-latitud');

        // Reset
        [errNombre, errCalle, errNum, errCP, errLatitud].forEach(el => el.classList.add('hidden'));
        [nombre, inCalle, inNum, inCP, latitud].forEach(el => el.classList.remove('border-red-500'));

        let isValid = true;

        if (!nombre.value.trim()) {
            errNombre.textContent = 'El nombre del punto es obligatorio.';
            errNombre.classList.remove('hidden');
            nombre.classList.add('border-red-500');
            isValid = false;
        }

        if (!inCalle.value.trim()) {
            errCalle.textContent = 'Obligatorio.';
            errCalle.classList.remove('hidden');
            inCalle.classList.add('border-red-500');
            isValid = false;
        }
        if (!inNum.value.trim()) {
            errNum.textContent = 'Obligatorio.';
            errNum.classList.remove('hidden');
            inNum.classList.add('border-red-500');
            isValid = false;
        }
        if (!inCP.value.trim()) {
            errCP.textContent = 'Obligatorio.';
            errCP.classList.remove('hidden');
            inCP.classList.add('border-red-500');
            isValid = false;
        }

        if (!latitud.value) {
            errLatitud.textContent = 'La latitud es obligatoria.';
            errLatitud.classList.remove('hidden');
            latitud.classList.add('border-red-500');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        } else {
            hiddenDir.value = `${inCalle.value.trim()} #${inNum.value.trim()}, C.P. ${inCP.value.trim()}`;
        }
    });
});
</script>
@endpush
