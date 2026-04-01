@extends('layouts.admin')

@section('title', 'Generador de Reportes')
@section('page_title', 'Reportes PDF')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Styling extra for flatpickr rounding inside inputs */
    .flatpickr-calendar { border-radius: 12px; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
    .flatpickr-day.selected { background: #00E096; border-color: #00E096; border-radius: 8px; }
    .flatpickr-day.selected:hover { background: #059669; }
    /* Fix global SVG overwrite crashing the arrows */
    .flatpickr-calendar svg { width: 14px !important; height: 14px !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize Flatpickr
        flatpickr(".datepicker", {
            locale: "es",
            dateFormat: "Y-m-d", // backend format
            altInput: true,
            altFormat: "d/m/Y", // visual format
            minDate: "2026-03-30",
            allowInput: false, // Prevent the buggy typing
            disableMobile: "true"
        });
        const forms = document.querySelectorAll('.report-form');
        
        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                const tipo = this.dataset.tipo;
                const inicioInput = document.getElementById(`fecha_inicio_${tipo}`);
                const finInput = document.getElementById(`fecha_fin_${tipo}`);
                const errInicio = document.getElementById(`err_inicio_${tipo}`);
                const errFin = document.getElementById(`err_fin_${tipo}`);

                // Reset validations
                errInicio.classList.add('hidden');
                errFin.classList.add('hidden');
                inicioInput.classList.remove('border-red-500');
                finInput.classList.remove('border-red-500');

                let isValid = true;

                if (!inicioInput.value) {
                    errInicio.textContent = 'Selecciona la fecha de inicio.';
                    errInicio.classList.remove('hidden');
                    inicioInput.classList.add('border-red-500');
                    isValid = false;
                } else if (new Date(inicioInput.value) < new Date('2026-03-30')) {
                    errInicio.textContent = 'Solo fechas a partir del 30 de marzo de 2026.';
                    errInicio.classList.remove('hidden');
                    inicioInput.classList.add('border-red-500');
                    isValid = false;
                }

                if (!finInput.value) {
                    errFin.textContent = 'Selecciona la fecha de fin.';
                    errFin.classList.remove('hidden');
                    finInput.classList.add('border-red-500');
                    isValid = false;
                } else if (new Date(finInput.value) < new Date(inicioInput.value)) {
                    errFin.textContent = 'La fecha fin no puede ser anterior al inicio.';
                    errFin.classList.remove('hidden');
                    finInput.classList.add('border-red-500');
                    isValid = false;
                }

                // Deshabilitar propagación si es inválido
                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                const formToSubmit = this;
                const dateStartStr = inicioInput.value;
                const dateEndStr = finInput.value;

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: '¿Generar Reporte PDF?',
                        text: `¿Estás seguro de descargar el PDF de ${tipo} del ${dateStartStr} al ${dateEndStr}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#00E096',
                        cancelButtonColor: '#EF4444',
                        confirmButtonText: 'Sí, descargar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formToSubmit.submit();
                        }
                    });
                } else {
                    if (confirm(`¿Estás seguro de descargar el PDF de ${tipo} del ${dateStartStr} al ${dateEndStr}?`)) {
                        formToSubmit.submit();
                    }
                }
            });
        });
    });
</script>
@endpush

@section('content')

<div class="mb-8">
    <p class="text-gray-600 dark:text-emerald-100/80 text-lg">Selecciona un módulo para generar su respectivo reporte en PDF, filtrando por fechas dinámicamente.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    
    {{-- Reporte de Usuarios --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-8 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative overflow-hidden flex flex-col items-center group">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mb-6 shadow-md z-10">
            <span class="material-symbols-outlined text-4xl text-emerald-600">group</span>
        </div>
        <h3 class="font-black text-2xl text-[#064E3B] dark:text-white mb-2 z-10">Usuarios</h3>
        <p class="text-sm text-center text-gray-500 mb-8 z-10">Total de registros, historial y detalles de cada ecologista de la plataforma.</p>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="w-full report-form z-10" data-tipo="usuarios" novalidate>
            <input type="hidden" name="tipo" value="usuarios">
            <div class="mb-4">
                <label class="block text-xs font-black uppercase text-gray-500 mb-2">Fecha Inicio:</label>
                <input type="text" name="fecha_inicio" id="fecha_inicio_usuarios" class="datepicker w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white transition-all bg-white" placeholder="Seleccionar...">
                <span id="err_inicio_usuarios" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-black uppercase text-gray-500 mb-2">Fecha Fin:</label>
                <input type="text" name="fecha_fin" id="fecha_fin_usuarios" class="datepicker w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white transition-all bg-white" placeholder="Seleccionar...">
                <span id="err_fin_usuarios" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <button type="submit" class="w-full bg-[#064E3B] hover:bg-primary text-white font-bold py-3.5 rounded-xl shadow-lg flex items-center justify-center gap-2 transition-all group-hover:scale-105">
                <span class="material-symbols-outlined">download</span> Descargar
            </button>
        </form>
    </div>

    {{-- Reporte de Campañas --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-8 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative overflow-hidden flex flex-col items-center group">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-50 dark:bg-blue-900/10 rounded-full blur-3xl group-hover:bg-blue-400/20 transition-all duration-500"></div>
        <div class="w-20 h-20 rounded-full bg-blue-100 flex items-center justify-center mb-6 shadow-md z-10">
            <span class="material-symbols-outlined text-4xl text-blue-600">military_tech</span>
        </div>
        <h3 class="font-black text-2xl text-[#064E3B] dark:text-white mb-2 z-10">Campañas</h3>
        <p class="text-sm text-center text-gray-500 mb-8 z-10">Eventos organizados, fechas de inicio y estatus de visibilidad pública.</p>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="w-full report-form z-10" data-tipo="campanas" novalidate>
            <input type="hidden" name="tipo" value="campanas">
            <div class="mb-4">
                <label class="block text-xs font-black uppercase text-gray-500 mb-2">Fecha Inicio:</label>
                <input type="text" name="fecha_inicio" id="fecha_inicio_campanas" class="datepicker w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-blue-400 dark:text-white transition-all bg-white" placeholder="Seleccionar...">
                <span id="err_inicio_campanas" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-black uppercase text-gray-500 mb-2">Fecha Fin:</label>
                <input type="text" name="fecha_fin" id="fecha_fin_campanas" class="datepicker w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-blue-400 dark:text-white transition-all bg-white" placeholder="Seleccionar...">
                <span id="err_fin_campanas" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-3.5 rounded-xl shadow-lg flex items-center justify-center gap-2 transition-all group-hover:scale-105">
                <span class="material-symbols-outlined">download</span> Descargar
            </button>
        </form>
    </div>

    {{-- Reporte de Mapa --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-8 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative overflow-hidden flex flex-col items-center group">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-amber-50 dark:bg-amber-900/10 rounded-full blur-3xl group-hover:bg-amber-400/20 transition-all duration-500"></div>
        <div class="w-20 h-20 rounded-full bg-amber-100 flex items-center justify-center mb-6 shadow-md z-10">
            <span class="material-symbols-outlined text-4xl text-amber-600">location_on</span>
        </div>
        <h3 class="font-black text-2xl text-[#064E3B] dark:text-white mb-2 z-10">Mapa</h3>
        <p class="text-sm text-center text-gray-500 mb-8 z-10">Puntos de reciclaje, centros de acopio y contenedores activos en la app.</p>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="w-full report-form z-10" data-tipo="mapa" novalidate>
            <input type="hidden" name="tipo" value="mapa">
            <div class="mb-4">
                <label class="block text-xs font-black uppercase text-gray-500 mb-2">Fecha Inicio:</label>
                <input type="text" name="fecha_inicio" id="fecha_inicio_mapa" class="datepicker w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-amber-500 dark:text-white transition-all bg-white" placeholder="Seleccionar...">
                <span id="err_inicio_mapa" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="mb-6">
                <label class="block text-xs font-black uppercase text-gray-500 mb-2">Fecha Fin:</label>
                <input type="text" name="fecha_fin" id="fecha_fin_mapa" class="datepicker w-full px-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-amber-500 dark:text-white transition-all bg-white" placeholder="Seleccionar...">
                <span id="err_fin_mapa" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <button type="submit" class="w-full bg-amber-600 hover:bg-amber-500 text-white font-bold py-3.5 rounded-xl shadow-lg flex items-center justify-center gap-2 transition-all group-hover:scale-105">
                <span class="material-symbols-outlined">download</span> Descargar
            </button>
        </form>
    </div>

</div>

@endsection
