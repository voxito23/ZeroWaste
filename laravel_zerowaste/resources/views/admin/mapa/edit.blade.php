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
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">category</span></div>
                    <select name="tipo" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 appearance-none" required>
                        <option value="Plástico" {{ $location->tipo == 'Plástico' ? 'selected' : '' }}>♻️ Plástico</option>
                        <option value="Vidrio" {{ $location->tipo == 'Vidrio' ? 'selected' : '' }}>🍷 Vidrio</option>
                        <option value="Electrónicos" {{ $location->tipo == 'Electrónicos' ? 'selected' : '' }}>💻 Electrónicos</option>
                        <option value="Centro Principal" {{ $location->tipo == 'Centro Principal' ? 'selected' : '' }}>🏢 Centro Principal</option>
                        <option value="Contenedor Público" {{ $location->tipo == 'Contenedor Público' ? 'selected' : '' }}>📦 Contenedor Público</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Materiales Aceptados (Opcional)</label>
                <div class="relative">
                    <div class="absolute top-3 left-0 pl-4 flex items-start pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">inventory_2</span></div>
                    <textarea name="materiales" rows="3" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">{{ $location->materiales }}</textarea>
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
                <div class="relative group mt-1">
                    <input type="file" name="imagen_archivo" accept="image/*" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-dashed border-emerald-300 dark:border-emerald-700/50 rounded-xl p-4 dark:text-white text-sm file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 dark:file:bg-[#0B1F18] file:text-emerald-600 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50 file:cursor-pointer cursor-pointer transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
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
document.addEventListener('DOMContentLoaded', function() {
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
