@extends('layouts.admin')

@section('title', 'Crear Campaña')
@section('page_title', 'Crear Nueva Campaña')

@section('content')
<div class="bg-white dark:bg-forest-card rounded-[2rem] p-8 shadow-xl border-2 border-emerald-100 dark:border-emerald-800/50 relative overflow-hidden group max-w-4xl mx-auto">
    <div class="absolute -top-20 -right-10 w-60 h-60 bg-emerald-400/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-emerald-400/10"></div>
    <div class="flex items-center gap-4 mb-8 relative z-10 border-b border-emerald-50 dark:border-emerald-800/50 pb-6">
        <div class="w-14 h-14 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-500 border border-emerald-500/20 shadow-inner">
            <span class="material-symbols-outlined text-[28px]">campaign</span>
        </div>
        <div>
            <h2 class="text-3xl font-black text-[#064E3B] dark:text-white tracking-tight">Nueva Campaña</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm font-bold mt-1">Registra información sobre eventos, talleres y voluntariados.</p>
        </div>
    </div>
    <form id="customForm" novalidate action="{{ route('campanas.store') }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6 relative z-10">
        @csrf

        @if ($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-xl mb-4">
                <strong class="font-bold">Error de validación:</strong>
                <ul class="list-disc pl-5 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Nombre de la Campaña</label>
            <input type="text" name="nombre" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors">
            <span id="err-nombre" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Descripción</label>
            <textarea name="descripcion" rows="4" required class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none transition-colors"></textarea>
            <span id="err-descripcion" class="hidden text-red-500 text-sm mt-1 font-medium"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Lugar</label>
                <input type="text" name="lugar" placeholder="Querétaro, Qro." class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none">
            </div>
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Tipo / Etiqueta</label>
                <input type="text" name="tipo_etiqueta" placeholder="Taller, Acopio, Educación..." class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Fecha Inicio</label>
                <input type="date" name="fecha_inicio" min="2026-03-30" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none">
            </div>
            <div>
                <label class="block font-bold mb-2 dark:text-emerald-200">Fecha Fin</label>
                <input type="date" name="fecha_fin" min="2026-03-30" class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none">
            </div>
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Link de Campaña</label>
            <input type="url" name="link_evento" placeholder="https://..." class="w-full border-2 border-emerald-100 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 focus:border-emerald-400 outline-none">
        </div>

        <div>
            <label class="block font-bold mb-2 dark:text-emerald-200">Imagen de la Campaña</label>
            <div class="relative">
                <input type="file" name="imagen_archivo" accept="image/*" id="camp-img-input"
                       class="w-full border-2 border-dashed border-emerald-200 dark:border-emerald-700 rounded-xl p-4 dark:bg-forest-dark dark:text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-primary file:text-secondary hover:file:bg-emerald-400 file:cursor-pointer cursor-pointer">
                <p class="text-xs text-gray-400 dark:text-emerald-600 mt-1">Formatos: JPG, PNG, WEBP. Tamaño máximo: 250MB</p>
            </div>
        </div>

        <div class="flex items-center gap-3 mt-2">
            <input type="checkbox" name="activa" id="activa" checked class="w-5 h-5 text-emerald-500 border-emerald-200 rounded focus:ring-emerald-400 accent-emerald-500">
            <label for="activa" class="font-bold text-gray-700 dark:text-emerald-200">Campaña Activa</label>
        </div>

        <div class="flex justify-between items-center pt-4 border-t border-gray-100 dark:border-emerald-800/50">
            <a href="{{ route('campanas.index') }}" class="px-6 py-3 text-gray-500 dark:text-gray-400 font-bold hover:text-gray-700 dark:hover:text-white transition-colors">← Cancelar</a>
            <button type="submit" class="px-8 py-3 bg-[#064E3B] text-white rounded-xl font-bold hover:bg-[#00E096] hover:text-[#064E3B] transition-colors shadow-lg shadow-[#00E096]/20 flex items-center gap-2">
                <span class="material-symbols-outlined">save</span> Guardar Campaña
            </button>
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
        const descripcion = form.querySelector('textarea[name="descripcion"]');
        const errNombre = document.getElementById('err-nombre');
        const errDesc = document.getElementById('err-descripcion');

        // Reset
        errNombre.classList.add('hidden');
        errDesc.classList.add('hidden');
        nombre.classList.remove('border-red-500');
        descripcion.classList.remove('border-red-500');

        let isValid = true;

        if (!nombre.value.trim()) {
            errNombre.textContent = 'El nombre de la campaña es obligatorio.';
            errNombre.classList.remove('hidden');
            nombre.classList.add('border-red-500');
            isValid = false;
        }

        if (!descripcion.value.trim()) {
            errDesc.textContent = 'La descripción es obligatoria.';
            errDesc.classList.remove('hidden');
            descripcion.classList.add('border-red-500');
            isValid = false;
        }

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
@endpush
