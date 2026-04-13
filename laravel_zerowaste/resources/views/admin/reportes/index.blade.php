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
                // Capture format
                let currentFormato = e.submitter ? e.submitter.dataset.formato : 'pdf';
                formToSubmit.querySelector('.formato_input').value = currentFormato;
                
                let formatoNombre = currentFormato.toUpperCase();
                let accionNombre = 'descargar el archivo de';
                let btnColor = '#EF4444'; // PDF Default

                if(currentFormato === 'xlsx') {
                    formatoNombre = 'EXCEL';
                    btnColor = '#10B981';
                }
                if(currentFormato === 'docx') {
                    formatoNombre = 'WORD';
                    btnColor = '#3B82F6';
                }
                if(currentFormato === 'preview') {
                    formatoNombre = 'PREVISUALIZACIÓN';
                    accionNombre = 'abrir en una pestaña nueva el reporte de';
                    btnColor = '#4B5563'; // Gray
                    formToSubmit.target = '_blank'; // Opens in new tab
                } else {
                    formToSubmit.target = ''; // Downloads locally
                }

                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: `¿Generar Reporte en ${formatoNombre}?`,
                        text: `¿Estás seguro de ${accionNombre} ${tipo} del ${dateStartStr} al ${dateEndStr}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: btnColor,
                        cancelButtonColor: '#9CA3AF',
                        confirmButtonText: currentFormato === 'preview' ? 'Sí, Previsualizar' : 'Sí, Descargar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            formToSubmit.submit();
                        }
                    });
                } else {
                    if (confirm(`¿Estás seguro de ${accionNombre} ${tipo} en ${formatoNombre} del ${dateStartStr} al ${dateEndStr}?`)) {
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
    <p class="text-gray-600 dark:text-emerald-100/80 text-lg">Selecciona un módulo y rango de fechas para exportarlo o previsualizarlo dinámicamente.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
    
    {{-- Reporte de Usuarios --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative overflow-hidden flex flex-col items-center group w-full">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center mb-4 shadow-md z-10">
            <span class="material-symbols-outlined text-3xl text-emerald-600">group</span>
        </div>
        <h3 class="font-black text-xl text-[#064E3B] dark:text-white mb-2 z-10 w-full text-center">Usuarios</h3>
        <p class="text-xs text-center text-gray-500 mb-6 z-10 flex-grow">Total de registros, historial y detalles ecologistas.</p>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="w-full report-form z-10 flex flex-col justify-end" data-tipo="usuarios" novalidate>
            <input type="hidden" name="tipo" value="usuarios">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="mb-3">
                <input type="text" name="fecha_inicio" id="fecha_inicio_usuarios" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white text-sm bg-white" placeholder="Fecha Inicio">
                <span id="err_inicio_usuarios" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="mb-4">
                <input type="text" name="fecha_fin" id="fecha_fin_usuarios" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white text-sm bg-white" placeholder="Fecha Fin">
                <span id="err_fin_usuarios" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="flex gap-2 w-full mt-auto">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-500 hover:bg-gray-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Previsualizar en Navegador">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Word">
                    <span class="material-symbols-outlined text-lg">description</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Reporte de Campañas --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative overflow-hidden flex flex-col items-center group w-full">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-blue-50 dark:bg-blue-900/10 rounded-full blur-3xl group-hover:bg-blue-400/20 transition-all duration-500"></div>
        <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-4 shadow-md z-10">
            <span class="material-symbols-outlined text-3xl text-blue-600">military_tech</span>
        </div>
        <h3 class="font-black text-xl text-[#064E3B] dark:text-white mb-2 z-10 w-full text-center">Campañas</h3>
        <p class="text-xs text-center text-gray-500 mb-6 z-10 flex-grow">Campañas organizadas y estatus de visibilidad.</p>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="w-full report-form z-10 flex flex-col justify-end" data-tipo="campanas" novalidate>
            <input type="hidden" name="tipo" value="campanas">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="mb-3">
                <input type="text" name="fecha_inicio" id="fecha_inicio_campanas" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-blue-400 dark:text-white text-sm bg-white" placeholder="Fecha Inicio">
                <span id="err_inicio_campanas" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="mb-4">
                <input type="text" name="fecha_fin" id="fecha_fin_campanas" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-blue-400 dark:text-white text-sm bg-white" placeholder="Fecha Fin">
                <span id="err_fin_campanas" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="flex gap-2 w-full mt-auto">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-500 hover:bg-gray-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Previsualizar en Navegador">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Word">
                    <span class="material-symbols-outlined text-lg">description</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Reporte de Mapa --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative overflow-hidden flex flex-col items-center group w-full">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-amber-50 dark:bg-amber-900/10 rounded-full blur-3xl group-hover:bg-amber-400/20 transition-all duration-500"></div>
        <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center mb-4 shadow-md z-10">
            <span class="material-symbols-outlined text-3xl text-amber-600">location_on</span>
        </div>
        <h3 class="font-black text-xl text-[#064E3B] dark:text-white mb-2 z-10 w-full text-center">Mapa</h3>
        <p class="text-xs text-center text-gray-500 mb-6 z-10 flex-grow">Puntos, centros de acopio y contenedores activos.</p>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="w-full report-form z-10 flex flex-col justify-end" data-tipo="mapa" novalidate>
            <input type="hidden" name="tipo" value="mapa">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="mb-3">
                <input type="text" name="fecha_inicio" id="fecha_inicio_mapa" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-amber-500 dark:text-white text-sm bg-white" placeholder="Fecha Inicio">
                <span id="err_inicio_mapa" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="mb-4">
                <input type="text" name="fecha_fin" id="fecha_fin_mapa" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-amber-500 dark:text-white text-sm bg-white" placeholder="Fecha Fin">
                <span id="err_fin_mapa" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="flex gap-2 w-full mt-auto">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-500 hover:bg-gray-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Previsualizar en Navegador">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Word">
                    <span class="material-symbols-outlined text-lg">description</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Reporte de Eventos (4TO REPORTE) --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 relative overflow-hidden flex flex-col items-center group w-full">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-purple-50 dark:bg-purple-900/10 rounded-full blur-3xl group-hover:bg-purple-400/20 transition-all duration-500"></div>
        <div class="w-16 h-16 rounded-full bg-purple-100 flex items-center justify-center mb-4 shadow-md z-10">
            <span class="material-symbols-outlined text-3xl text-purple-600">event</span>
        </div>
        <h3 class="font-black text-xl text-[#064E3B] dark:text-white mb-2 z-10 w-full text-center">Eventos</h3>
        <p class="text-xs text-center text-gray-500 mb-6 z-10 flex-grow">Jornadas comunitarias, talleres y limpiezas urbanas.</p>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="w-full report-form z-10 flex flex-col justify-end" data-tipo="eventos" novalidate>
            <input type="hidden" name="tipo" value="eventos">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="mb-3">
                <input type="text" name="fecha_inicio" id="fecha_inicio_eventos" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-purple-500 dark:text-white text-sm bg-white" placeholder="Fecha Inicio">
                <span id="err_inicio_eventos" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="mb-4">
                <input type="text" name="fecha_fin" id="fecha_fin_eventos" class="datepicker w-full px-3 py-2 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-purple-500 dark:text-white text-sm bg-white" placeholder="Fecha Fin">
                <span id="err_fin_eventos" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
            </div>
            <div class="flex gap-2 w-full mt-auto">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-500 hover:bg-gray-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Previsualizar en Navegador">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2 rounded-xl flex items-center justify-center transition-all" title="Descargar Word">
                    <span class="material-symbols-outlined text-lg">description</span>
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
