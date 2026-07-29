@extends('layouts.admin')

@section('title', 'Editar Campaña')
@section('page_title', 'Editar Campaña')

@section('content')
<div class="bg-white/80 dark:bg-[#0B1F18]/80 backdrop-blur-xl rounded-[2rem] p-8 lg:p-10 shadow-2xl border border-white/50 dark:border-emerald-800/30 relative overflow-hidden group max-w-4xl mx-auto">
    <div class="absolute -top-40 -right-40 w-96 h-96 bg-gradient-to-br from-emerald-400/20 to-teal-500/10 rounded-full blur-3xl pointer-events-none transition group-hover:bg-emerald-400/20"></div>
    <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-gradient-to-tr from-emerald-500/10 to-transparent rounded-full blur-3xl pointer-events-none"></div>

    <div class="flex items-center gap-4 mb-10 relative z-10 border-b border-gray-100 dark:border-emerald-800/30 pb-6">
        <div class="w-16 h-16 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-[1.25rem] flex items-center justify-center text-white shadow-lg shadow-emerald-500/30">
            <span class="material-symbols-outlined text-[32px]">edit_document</span>
        </div>
        <div>
            <h2 class="text-3xl font-black text-[#064E3B] dark:text-white tracking-tight">Editar Campaña</h2>
            <p class="text-gray-500 dark:text-emerald-200/70 text-sm font-medium mt-1">Modifica la información y ajustes de esta campaña.</p>
        </div>
    </div>

    <form id="customForm" novalidate action="{{ route('campanas.update', $campaign->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-6 relative z-10">
        @csrf
        @method('PUT')
        <div class="grid grid-cols-1 gap-6">
            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Nombre de la Campaña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">edit_note</span></div>
                    <input type="text" name="nombre" id="input-nombre" value="{{ $campaign->nombre }}" required maxlength="30" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                </div>
                <div class="flex justify-between items-center mt-1">
                    <span id="err-nombre" class="hidden text-red-500 text-xs font-medium ml-1"></span>
                    <span id="counter-nombre" class="text-xs font-semibold text-gray-400 dark:text-emerald-500/50 ml-auto">{{ strlen($campaign->nombre) }}/30</span>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Descripción</label>
                <div class="relative">
                    <div class="absolute top-3 left-0 pl-4 flex items-start pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">subject</span></div>
                    <textarea name="descripcion" rows="4" required class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">{{ $campaign->descripcion }}</textarea>
                </div>
                <div class="flex justify-between items-center mt-1">
                    <span id="err-descripcion" class="hidden text-red-500 text-xs font-medium ml-1"></span>
                    <span id="counter-descripcion" class="text-xs font-semibold text-gray-400 dark:text-emerald-500/50 ml-auto">{{ strlen($campaign->descripcion) }}/500</span>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Lugar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">place</span></div>
                        <input type="text" name="lugar" id="input-lugar" value="{{ $campaign->lugar }}" maxlength="30" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                    </div>
                    <div class="flex justify-between items-center mt-1">
                        <span id="err-lugar" class="hidden text-red-500 text-xs font-medium ml-1"></span>
                        <span id="counter-lugar" class="text-xs font-semibold text-gray-400 dark:text-emerald-500/50 ml-auto">{{ strlen($campaign->lugar) }}/30</span>
                    </div>
                </div>
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Tipo / Etiqueta</label>
                    <div class="relative custom-dropdown-etiqueta" tabindex="0">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">sell</span></div>
                        <input type="hidden" name="tipo_etiqueta" id="tipo_etiqueta" value="{{ $campaign->tipo_etiqueta }}">
                        <div class="dropdown-selected-etiqueta w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl block pl-11 pr-10 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 cursor-pointer flex justify-between items-center" onclick="this.nextElementSibling.classList.toggle('hidden')">
                            <span class="selected-text-etiqueta">{{ $campaign->tipo_etiqueta ?: 'Selecciona una etiqueta...' }}</span>
                            <span class="material-symbols-outlined text-gray-400 absolute right-3 pointer-events-none">expand_more</span>
                        </div>
                        <div class="dropdown-options-etiqueta hidden absolute z-50 w-full mt-2 bg-white dark:bg-[#0F2A20] border border-gray-100 dark:border-emerald-800/50 rounded-xl shadow-xl overflow-hidden max-h-52 overflow-y-auto">
                            @php
                                $etiquetas = [
                                    'EDUCACIÓN' => 'school',
                                    'IMPACTO POSITIVO' => 'eco',
                                    'RECAUDACIÓN' => 'volunteer_activism',
                                    'LIMPIEZA' => 'cleaning_services',
                                    'TALLER' => 'construction',
                                    'CONFERENCIA' => 'groups',
                                    'RECICLAJE' => 'recycling',
                                    'CONCIENTIZACIÓN' => 'campaign',
                                ];
                            @endphp
                            @foreach($etiquetas as $etiqueta => $icon)
                            <div class="option-etiqueta-item p-3 hover:bg-emerald-50 dark:hover:bg-emerald-900/30 cursor-pointer flex items-center gap-2 text-gray-700 dark:text-gray-300 transition-colors text-sm" data-value="{{ $etiqueta }}">
                                <span class="material-symbols-outlined text-emerald-500 text-lg">{{ $icon }}</span> {{ $etiqueta }}
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Fecha Inicio</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">event</span></div>
                        <input type="text" name="fecha_inicio" id="fp-fecha-inicio" value="{{ $campaign->fecha_inicio ? date('Y-m-d', strtotime($campaign->fecha_inicio)) : '' }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 cursor-pointer" placeholder="Seleccionar fecha" readonly>
                    </div>
                </div>
                <div>
                    <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Fecha Fin</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">event_available</span></div>
                        <input type="text" name="fecha_fin" id="fp-fecha-fin" value="{{ $campaign->fecha_fin ? date('Y-m-d', strtotime($campaign->fecha_fin)) : '' }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20 cursor-pointer" placeholder="Seleccionar fecha" readonly>
                    </div>
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Enlace Externo (Opcional)</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none"><span class="material-symbols-outlined text-gray-400 dark:text-emerald-500/50 text-lg">link</span></div>
                    <input type="url" name="link_evento" value="{{ $campaign->link_evento }}" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-gray-200 dark:border-emerald-800/30 text-gray-900 dark:text-white text-sm rounded-xl focus:ring-emerald-500 focus:border-emerald-500 block pl-11 p-3.5 transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                </div>
            </div>

            <div>
                <label class="block font-bold mb-1.5 text-sm text-gray-700 dark:text-emerald-200">Banner de la Campaña</label>
                @if($campaign->imagen_url)
                <div class="mb-3 flex items-center gap-3 bg-gray-50 dark:bg-[#0B1F18] p-3 rounded-xl border border-gray-200 dark:border-emerald-800/30">
                    <img src="https://zerowaste-qro.com/static/img/eventos/{{ $campaign->imagen_url }}" alt="Imagen actual" class="h-16 w-24 object-cover rounded-lg border border-emerald-200 dark:border-emerald-700/50" onerror="this.style.display='none'">
                    <span class="text-xs font-bold text-gray-500 dark:text-emerald-500/80">Imagen actual: {{ $campaign->imagen_url }}</span>
                </div>
                @endif
                <div class="relative group mt-1">
                    <input type="file" name="imagen_archivo" accept="image/*" class="w-full bg-gray-50/50 dark:bg-[#064E3B]/10 border border-dashed border-emerald-300 dark:border-emerald-700/50 rounded-xl p-4 dark:text-white text-sm file:mr-4 file:py-2.5 file:px-5 file:rounded-xl file:border-0 file:text-sm file:font-bold file:bg-emerald-50 dark:file:bg-[#0B1F18] file:text-emerald-600 dark:file:text-emerald-400 hover:file:bg-emerald-100 dark:hover:file:bg-emerald-900/50 file:cursor-pointer cursor-pointer transition-all duration-300 hover:bg-white dark:hover:bg-[#064E3B]/20">
                    <p class="text-xs text-gray-400 dark:text-emerald-600/70 mt-2 ml-1">Dejar vacío para mantener la imagen actual.</p>
                </div>
            </div>
        </div>

        <div class="mt-4 bg-emerald-50 dark:bg-[#064E3B]/20 rounded-xl p-4 border border-emerald-100 dark:border-emerald-800/30 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-full bg-white dark:bg-[#0B1F18] flex items-center justify-center text-emerald-600 dark:text-emerald-400 shadow-sm">
                    <span class="material-symbols-outlined">public</span>
                </div>
                <div>
                    <h4 class="font-bold text-gray-800 dark:text-white text-sm">Estado de Visibilidad</h4>
                    <p class="text-xs text-gray-500 dark:text-emerald-200/70 mt-0.5">Controla si la campaña está visible en el portal público.</p>
                </div>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="activa" id="activa" {{ $campaign->activa ? 'checked' : '' }} class="sr-only peer">
                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 dark:peer-focus:ring-emerald-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-emerald-500"></div>
            </label>
        </div>

        <div class="flex justify-between items-center mt-6 pt-6 border-t border-gray-100 dark:border-emerald-800/30">
            <a href="{{ route('campanas.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-emerald-900/30 transition-colors flex items-center gap-2">
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
function updateCounter(input, counterId, max, min, errId) {
    const counter = document.getElementById(counterId);
    const errSpan = document.getElementById(errId);
    const len = input.value.length;
    counter.textContent = len + '/' + max;
    if (len >= max) {
        counter.classList.remove('text-gray-400', 'dark:text-emerald-500/50');
        counter.classList.add('text-red-500', 'dark:text-red-400');
        input.classList.add('border-red-500', 'dark:border-red-500');
        input.classList.remove('border-gray-200', 'dark:border-emerald-800/30');
        if (errSpan) { errSpan.textContent = 'Máximo ' + max + ' caracteres alcanzado.'; errSpan.classList.remove('hidden'); }
    } else if (len > 0 && len < min) {
        counter.classList.remove('text-gray-400', 'dark:text-emerald-500/50');
        counter.classList.add('text-red-500', 'dark:text-red-400');
        input.classList.add('border-red-500', 'dark:border-red-500');
        input.classList.remove('border-gray-200', 'dark:border-emerald-800/30');
        if (errSpan) { errSpan.textContent = 'Mínimo ' + min + ' caracteres requeridos.'; errSpan.classList.remove('hidden'); }
    } else {
        counter.classList.add('text-gray-400', 'dark:text-emerald-500/50');
        counter.classList.remove('text-red-500', 'dark:text-red-400');
        input.classList.remove('border-red-500', 'dark:border-red-500');
        input.classList.add('border-gray-200', 'dark:border-emerald-800/30');
        if (errSpan) { errSpan.classList.add('hidden'); }
    }
}
function filterAlphaNum(input) {
    input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s]/g, '');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('customForm');
    if (!form) return;

    // Real-time validation
    const inputNombre = document.getElementById('input-nombre');
    if (inputNombre) {
        inputNombre.addEventListener('input', function() { filterAlphaNum(this); updateCounter(this, 'counter-nombre', 30, 5, 'err-nombre'); });
        inputNombre.addEventListener('paste', function() { setTimeout(() => { filterAlphaNum(this); updateCounter(this, 'counter-nombre', 30, 5, 'err-nombre'); }, 0); });
    }
    const inputLugar = document.getElementById('input-lugar');
    if (inputLugar) {
        inputLugar.addEventListener('input', function() { updateCounter(this, 'counter-lugar', 30, 5, 'err-lugar'); });
    }
    const textDescripcion = form.querySelector('textarea[name="descripcion"]');
    if (textDescripcion) {
        textDescripcion.addEventListener('input', function() { updateCounter(this, 'counter-descripcion', 500, 10, 'err-descripcion'); });
    }

    // Etiqueta dropdown
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown-etiqueta')) {
            document.querySelectorAll('.dropdown-options-etiqueta').forEach(el => el.classList.add('hidden'));
        }
    });
    document.querySelectorAll('.option-etiqueta-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.stopPropagation();
            const dropdown = this.closest('.custom-dropdown-etiqueta');
            dropdown.querySelector('input[type="hidden"]').value = this.dataset.value;
            dropdown.querySelector('.selected-text-etiqueta').textContent = this.dataset.value;
            dropdown.querySelector('.dropdown-options-etiqueta').classList.add('hidden');
        });
    });

    form.addEventListener('submit', function(e) {
        const nombre = form.querySelector('input[name="nombre"]');
        const descripcion = form.querySelector('textarea[name="descripcion"]');
        const lugar = form.querySelector('input[name="lugar"]');
        const errNombre = document.getElementById('err-nombre');
        const errDesc = document.getElementById('err-descripcion');
        const errLugar = document.getElementById('err-lugar');

        [errNombre, errDesc, errLugar].forEach(el => { if(el) el.classList.add('hidden'); });
        [nombre, descripcion, lugar].forEach(el => { if(el) el.classList.remove('border-red-500', 'dark:border-red-500'); });

        let isValid = true;

        const nombreVal = nombre.value.trim();
        if (!nombreVal) {
            errNombre.textContent = 'El nombre de la campaña es obligatorio.';
            errNombre.classList.remove('hidden'); nombre.classList.add('border-red-500'); isValid = false;
        } else if (nombreVal.length < 5) {
            errNombre.textContent = 'El nombre debe tener al menos 5 caracteres.';
            errNombre.classList.remove('hidden'); nombre.classList.add('border-red-500'); isValid = false;
        }

        const descVal = descripcion.value.trim();
        if (!descVal) {
            errDesc.textContent = 'La descripción es obligatoria.';
            errDesc.classList.remove('hidden'); descripcion.classList.add('border-red-500'); isValid = false;
        } else if (descVal.length < 10) {
            errDesc.textContent = 'La descripción debe tener al menos 10 caracteres.';
            errDesc.classList.remove('hidden'); descripcion.classList.add('border-red-500'); isValid = false;
        }

        if (lugar) {
            const lugarVal = lugar.value.trim();
            if (lugarVal.length > 0 && lugarVal.length < 5) {
                errLugar.textContent = 'El lugar debe tener al menos 5 caracteres.';
                errLugar.classList.remove('hidden'); lugar.classList.add('border-red-500'); isValid = false;
            }
        }

        if (!isValid) { e.preventDefault(); }
    });
});
</script>
@endpush




@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const fpConfig = {
        locale: 'es',
        dateFormat: 'Y-m-d',
        altInput: true,
        altFormat: 'd/m/Y',
        allowInput: false,
        disableMobile: true,
    };
    flatpickr('#fp-fecha-inicio', fpConfig);
    flatpickr('#fp-fecha-fin', fpConfig);
});
</script>
