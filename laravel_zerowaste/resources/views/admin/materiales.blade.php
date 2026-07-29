@extends('layouts.admin')

@section('title', 'Materiales')
@section('page_title', 'Catálogo de Materiales')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Formulario de registro de material -->
    <div class="md:col-span-1 bg-white dark:bg-forest-card rounded-[2rem] shadow-lg border-2 border-emerald-100 dark:border-emerald-800/50 p-8">
        <h2 class="text-xl font-black text-[#064E3B] dark:text-white mb-6">Agregar Material</h2>
        <form action="{{ route('materiales.store') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Nombre</label>
                <input type="text" name="nombre" id="input-nombre" required maxlength="30" placeholder="Ej. PET Transparente" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
                <div class="flex justify-between items-center mt-1">
                    <span id="err-nombre" class="hidden text-red-500 text-xs font-medium"></span>
                    <span id="counter-nombre" class="text-xs font-semibold text-gray-400 dark:text-emerald-500/50 ml-auto">0/30</span>
                </div>
            </div>
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Categoría</label>
                <select name="tipo" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary">
                    <option value="Plástico">Plástico</option>
                    <option value="Vidrio">Vidrio</option>
                    <option value="Cartón/Papel">Cartón/Papel</option>
                    <option value="Electrónicos">Electrónicos</option>
                    <option value="Orgánicos">Orgánicos</option>
                </select>
            </div>
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Unidad de Medida</label>
                <input type="text" name="unidades_medida" id="input-medida" required maxlength="15" placeholder="Ej. kg, piezas" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
                <div class="flex justify-between items-center mt-1">
                    <span id="err-medida" class="hidden text-red-500 text-xs font-medium"></span>
                    <span id="counter-medida" class="text-xs font-semibold text-gray-400 dark:text-emerald-500/50 ml-auto">0/15</span>
                </div>
            </div>
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Valor en Puntos</label>
                <input type="number" name="valor_puntos" min="0" value="0" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
            </div>
            
            <button type="submit" class="w-full bg-primary hover:bg-emerald-500 text-secondary font-black py-3 rounded-xl shadow-lg mt-4 transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">add_circle</span> Guardar Material
            </button>
        </form>
    </div>

    <!-- Tabla de materiales registrados -->
    <div class="md:col-span-2 bg-white dark:bg-forest-card rounded-[2rem] shadow-lg border-2 border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-secondary dark:text-emerald-300 font-bold text-xs uppercase tracking-wider">
                <tr>
                    <th class="p-4">Material</th>
                    <th class="p-4">Categoría</th>
                    <th class="p-4">Medida</th>
                    <th class="p-4">Puntos</th>
                </tr>
            </thead>
            <tbody class="dark:text-emerald-100">
                @forelse ($materials as $mat)
                <tr class="border-b border-emerald-50 dark:border-emerald-800/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                    <td class="p-4 font-bold text-[#064E3B] dark:text-white">{{ $mat->nombre }}</td>
                    <td class="p-4">
                        <span class="px-3 py-1 bg-gray-100 dark:bg-white/5 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-white/10">{{ $mat->tipo }}</span>
                    </td>
                    <td class="p-4 text-gray-500 dark:text-gray-400">{{ $mat->unidades_medida }}</td>
                    <td class="p-4 font-black text-emerald-600 dark:text-emerald-400">{{ $mat->valor_puntos }} pts</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">El catálogo de materiales está vacío.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
</div>
@endsection

@push('scripts')
<script>
function updateCounter(input, counterId, max, min, errId) {
    const counter = document.getElementById(counterId);
    const errSpan = document.getElementById(errId);
    if (!counter) return;
    const len = input.value.length;
    counter.textContent = len + '/' + max;
    if (len >= max) {
        counter.classList.remove('text-gray-400', 'dark:text-emerald-500/50');
        counter.classList.add('text-red-500', 'dark:text-red-400');
        input.classList.add('border-red-500');
        if (errSpan) { errSpan.textContent = 'Máximo ' + max + ' caracteres alcanzado.'; errSpan.classList.remove('hidden'); }
    } else if (len > 0 && len < min) {
        counter.classList.remove('text-gray-400', 'dark:text-emerald-500/50');
        counter.classList.add('text-red-500', 'dark:text-red-400');
        input.classList.add('border-red-500');
        if (errSpan) { errSpan.textContent = 'Mínimo ' + min + ' caracteres requeridos.'; errSpan.classList.remove('hidden'); }
    } else {
        counter.classList.add('text-gray-400', 'dark:text-emerald-500/50');
        counter.classList.remove('text-red-500', 'dark:text-red-400');
        input.classList.remove('border-red-500');
        if (errSpan) { errSpan.classList.add('hidden'); }
    }
}
function filterAlphaNum(input) {
    input.value = input.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ0-9\s]/g, '');
}

document.addEventListener('DOMContentLoaded', function() {
    const inputNombre = document.getElementById('input-nombre');
    if (inputNombre) {
        inputNombre.addEventListener('input', function() { filterAlphaNum(this); updateCounter(this, 'counter-nombre', 30, 5, 'err-nombre'); });
        inputNombre.addEventListener('paste', function() { setTimeout(() => { filterAlphaNum(this); updateCounter(this, 'counter-nombre', 30, 5, 'err-nombre'); }, 0); });
    }
    const inputMedida = document.getElementById('input-medida');
    if (inputMedida) {
        inputMedida.addEventListener('input', function() { updateCounter(this, 'counter-medida', 15, 1, 'err-medida'); });
    }

    const form = document.querySelector('form[action="{{ route(\'materiales.store\') }}"]');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        const nombre = form.querySelector('input[name="nombre"]');
        const errNombre = document.getElementById('err-nombre');
        if (errNombre) errNombre.classList.add('hidden');
        if (nombre) nombre.classList.remove('border-red-500');

        let isValid = true;
        if (nombre) {
            const val = nombre.value.trim();
            if (!val || val.length < 5) {
                if (errNombre) {
                    errNombre.textContent = 'El nombre debe tener al menos 5 caracteres.';
                    errNombre.classList.remove('hidden');
                }
                nombre.classList.add('border-red-500');
                isValid = false;
            }
        }
        if (!isValid) e.preventDefault();
    });
});
</script>
@endpush

