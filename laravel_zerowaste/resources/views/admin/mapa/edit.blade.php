@extends('layouts.admin')

@section('title', 'Editar Punto de Reciclaje')
@section('page_title', 'Editar Punto')

@section('content')
<div class="bg-white dark:bg-forest-card rounded-3xl shadow-lg border border-emerald-100 dark:border-emerald-800/50 p-8 max-w-3xl">
    <form id="customForm" novalidate action="{{ route('mapa.update', $location) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6">
        @csrf
        @method('PUT')

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Nombre del Punto</label>
            <input type="text" name="nombre" value="{{ $location->nombre }}" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors">
            <span id="err-nombre" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Dirección</label>
            <input type="text" name="direccion" value="{{ $location->direccion }}" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors">
            <span id="err-direccion" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Latitud</label>
                <input type="number" step="any" name="latitud" value="{{ $location->latitud }}" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none font-mono text-sm transition-colors">
                <span id="err-latitud" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
            </div>
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Longitud</label>
                <input type="number" step="any" name="longitud" value="{{ $location->longitud }}" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none font-mono text-sm transition-colors">
            </div>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Tipo</label>
            <select name="tipo" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors" required>
                <option value="Plástico" {{ $location->tipo == 'Plástico' ? 'selected' : '' }}>♻️ Plástico</option>
                <option value="Vidrio" {{ $location->tipo == 'Vidrio' ? 'selected' : '' }}>🍷 Vidrio</option>
                <option value="Electrónicos" {{ $location->tipo == 'Electrónicos' ? 'selected' : '' }}>💻 Electrónicos</option>
                <option value="Centro Principal" {{ $location->tipo == 'Centro Principal' ? 'selected' : '' }}>🏢 Centro Principal</option>
                <option value="Contenedor Público" {{ $location->tipo == 'Contenedor Público' ? 'selected' : '' }}>📦 Contenedor Público</option>
            </select>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Materiales Aceptados (Opcional)</label>
            <textarea name="materiales" rows="3" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none">{{ $location->materiales }}</textarea>
        </div>
        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Imagen del punto</label>
            @if($location->imagen && $location->imagen !== 'default_punto.png')
            <div class="mb-3 flex items-center gap-3">
                <img src="https://zerowaste-qro.com/static/img/{{ $location->imagen }}" alt="Imagen actual" class="h-16 w-24 object-cover rounded-lg border-2 border-emerald-200 dark:border-emerald-700" onerror="this.style.display='none'">
                <span class="text-xs text-gray-400 dark:text-emerald-500">Imagen actual: {{ $location->imagen }}</span>
            </div>
            @endif
            <input type="file" name="imagen_archivo" accept="image/*"
                   class="w-full border-2 border-dashed border-emerald-200 dark:border-emerald-700 rounded-xl p-4 dark:bg-forest-dark dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-primary file:text-secondary hover:file:bg-emerald-400 file:cursor-pointer cursor-pointer">
            <p class="text-xs text-gray-400 dark:text-emerald-600 mt-1">Dejar vacío para mantener la imagen actual</p>
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('mapa.index') }}" class="py-3 px-6 text-gray-500 dark:text-gray-400 font-bold hover:bg-gray-100 dark:hover:bg-emerald-900/30 rounded-xl">Cancelar</a>
            <button type="submit" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-8 rounded-xl shadow-md transition-transform hover:-translate-y-1">Guardar Cambios</button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const nombre = form.querySelector('input[name="nombre"]');
        const direccion = form.querySelector('input[name="direccion"]');
        const latitud = form.querySelector('input[name="latitud"]');

        const errNombre = document.getElementById('err-nombre');
        const errDireccion = document.getElementById('err-direccion');
        const errLatitud = document.getElementById('err-latitud');

        // Reset
        [errNombre, errDireccion, errLatitud].forEach(el => el.classList.add('hidden'));
        [nombre, direccion, latitud].forEach(el => el.classList.remove('border-red-500'));

        let isValid = true;

        if (!nombre.value.trim()) {
            errNombre.textContent = 'El nombre del punto es obligatorio.';
            errNombre.classList.remove('hidden');
            nombre.classList.add('border-red-500');
            isValid = false;
        }

        if (!direccion.value.trim()) {
            errDireccion.textContent = 'La dirección es obligatoria.';
            errDireccion.classList.remove('hidden');
            direccion.classList.add('border-red-500');
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
        }
    });
});
</script>
@endpush
