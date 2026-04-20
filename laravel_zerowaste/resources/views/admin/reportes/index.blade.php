@extends('layouts.admin')

@section('title', 'Generador de Reportes')
@section('page_title', 'Centro de Reportes')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    .flatpickr-calendar { border-radius: 12px; border: none; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); }
    .flatpickr-day.selected { background: #00E096; border-color: #00E096; border-radius: 8px; }
    .flatpickr-day.selected:hover { background: #059669; }
    .flatpickr-calendar svg { width: 14px !important; height: 14px !important; }
    .report-card { transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
    .report-card:hover { transform: translateY(-6px); box-shadow: 0 20px 40px -12px rgba(0, 0, 0, 0.15); }
    .btn-export { transition: all 0.2s ease; }
    .btn-export:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        flatpickr(".datepicker", {
            locale: "es",
            dateFormat: "Y-m-d",
            altInput: true,
            altFormat: "d/m/Y",
            minDate: "2026-03-30",
            allowInput: false,
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

                if (!isValid) {
                    e.preventDefault();
                    return;
                }

                e.preventDefault();
                const formToSubmit = this;
                const dateStartStr = inicioInput.value;
                const dateEndStr = finInput.value;
                let currentFormato = e.submitter ? e.submitter.dataset.formato : 'pdf';
                formToSubmit.querySelector('.formato_input').value = currentFormato;
                
                let formatoNombre = currentFormato.toUpperCase();
                let accionNombre = 'descargar el archivo de';
                let btnColor = '#EF4444';

                if(currentFormato === 'xlsx') { formatoNombre = 'EXCEL'; btnColor = '#10B981'; }
                if(currentFormato === 'docx') { formatoNombre = 'WORD'; btnColor = '#3B82F6'; }
                if(currentFormato === 'preview') {
                    formatoNombre = 'PREVISUALIZACIÓN';
                    accionNombre = 'abrir en una pestaña nueva el reporte de';
                    btnColor = '#4B5563';
                    formToSubmit.target = '_blank';
                } else {
                    formToSubmit.target = '';
                }

                if (typeof Swal !== 'undefined') {
                    const isDark = document.documentElement.classList.contains('dark');
                    Swal.fire({
                        title: `¿Generar Reporte en ${formatoNombre}?`,
                        text: `¿Estás seguro de ${accionNombre} ${tipo} del ${dateStartStr} al ${dateEndStr}?`,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: btnColor,
                        cancelButtonColor: '#9CA3AF',
                        confirmButtonText: currentFormato === 'preview' ? 'Sí, Previsualizar' : 'Sí, Descargar',
                        cancelButtonText: 'Cancelar',
                        background: isDark ? '#0F2A20' : '#ffffff',
                        color: isDark ? '#d1fae5' : '#064E3B',
                        customClass: {
                            popup: 'rounded-3xl',
                            confirmButton: 'rounded-xl',
                            cancelButton: 'rounded-xl'
                        }
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

{{-- Header --}}
<div class="mb-6">
    <p class="text-gray-500 dark:text-emerald-100/70 text-base">Informes consolidados y análisis de volumen del ecosistema ZeroWaste.</p>
</div>

{{-- Métricas rápidas --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <span class="material-symbols-outlined text-white text-xl">group</span>
            </div>
            <div>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white">{{ $totalUsuarios }}</p>
                <p class="text-xs text-gray-400 dark:text-emerald-500 font-semibold">Usuarios registrados</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <span class="material-symbols-outlined text-white text-xl">military_tech</span>
            </div>
            <div>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white">{{ $totalCampanas }}</p>
                <p class="text-xs text-gray-400 dark:text-emerald-500 font-semibold">Campañas activas</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/30">
                <span class="material-symbols-outlined text-white text-xl">location_on</span>
            </div>
            <div>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white">{{ $totalPuntos }}</p>
                <p class="text-xs text-gray-400 dark:text-emerald-500 font-semibold">Puntos de acopio</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
        <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                <span class="material-symbols-outlined text-white text-xl">event</span>
            </div>
            <div>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white">{{ $totalEventos }}</p>
                <p class="text-xs text-gray-400 dark:text-emerald-500 font-semibold">Eventos programados</p>
            </div>
        </div>
    </div>
</div>

{{-- Título de sección --}}
<div class="flex items-center gap-3 mb-6">
    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/30">
        <span class="material-symbols-outlined text-white">description</span>
    </div>
    <div>
        <h3 class="font-bold text-lg text-[#064E3B] dark:text-white">Exportar Reportes</h3>
        <p class="text-xs text-gray-400 dark:text-emerald-500">Selecciona módulo, rango de fechas y formato de exportación.</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    
    {{-- Reporte de Usuarios --}}
    <div class="report-card bg-white dark:bg-forest-card rounded-[2rem] p-7 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-28 h-28 bg-emerald-50 dark:bg-emerald-900/10 rounded-full blur-2xl"></div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <span class="material-symbols-outlined text-3xl text-white">group</span>
            </div>
            <div>
                <h3 class="font-black text-xl text-[#064E3B] dark:text-white">Usuarios</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Listado de registros, roles y perfiles con fotos embebidas.</p>
            </div>
        </div>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="report-form z-10 relative" data-tipo="usuarios" novalidate>
            <input type="hidden" name="tipo" value="usuarios">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Desde</label>
                    <input type="text" name="fecha_inicio" id="fecha_inicio_usuarios" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white text-sm" placeholder="Inicio">
                    <span id="err_inicio_usuarios" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Hasta</label>
                    <input type="text" name="fecha_fin" id="fecha_fin_usuarios" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white text-sm" placeholder="Fin">
                    <span id="err_fin_usuarios" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
            </div>
            <div class="flex gap-2 w-full">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-600 hover:bg-gray-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Previsualizar">
                    <span class="material-symbols-outlined text-lg">visibility</span>
                    <span class="text-xs hidden sm:inline">Preview</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span>
                    <span class="text-xs hidden sm:inline">PDF</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span>
                    <span class="text-xs hidden sm:inline">Excel</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Word">
                    <span class="material-symbols-outlined text-lg">description</span>
                    <span class="text-xs hidden sm:inline">Word</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Reporte de Campañas --}}
    <div class="report-card bg-white dark:bg-forest-card rounded-[2rem] p-7 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-28 h-28 bg-blue-50 dark:bg-blue-900/10 rounded-full blur-2xl"></div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <span class="material-symbols-outlined text-3xl text-white">military_tech</span>
            </div>
            <div>
                <h3 class="font-black text-xl text-[#064E3B] dark:text-white">Campañas</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Campañas organizadas, estado de visibilidad y clasificación.</p>
            </div>
        </div>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="report-form z-10 relative" data-tipo="campanas" novalidate>
            <input type="hidden" name="tipo" value="campanas">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Desde</label>
                    <input type="text" name="fecha_inicio" id="fecha_inicio_campanas" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-blue-400 dark:text-white text-sm" placeholder="Inicio">
                    <span id="err_inicio_campanas" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Hasta</label>
                    <input type="text" name="fecha_fin" id="fecha_fin_campanas" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-blue-400 dark:text-white text-sm" placeholder="Fin">
                    <span id="err_fin_campanas" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
            </div>
            <div class="flex gap-2 w-full">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-600 hover:bg-gray-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Previsualizar">
                    <span class="material-symbols-outlined text-lg">visibility</span><span class="text-xs hidden sm:inline">Preview</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span><span class="text-xs hidden sm:inline">PDF</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span><span class="text-xs hidden sm:inline">Excel</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Word">
                    <span class="material-symbols-outlined text-lg">description</span><span class="text-xs hidden sm:inline">Word</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Reporte de Mapa --}}
    <div class="report-card bg-white dark:bg-forest-card rounded-[2rem] p-7 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-28 h-28 bg-amber-50 dark:bg-amber-900/10 rounded-full blur-2xl"></div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/30">
                <span class="material-symbols-outlined text-3xl text-white">location_on</span>
            </div>
            <div>
                <h3 class="font-black text-xl text-[#064E3B] dark:text-white">Mapa</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Puntos de acopio, centros de reciclaje, coordenadas e imágenes.</p>
            </div>
        </div>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="report-form z-10 relative" data-tipo="mapa" novalidate>
            <input type="hidden" name="tipo" value="mapa">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Desde</label>
                    <input type="text" name="fecha_inicio" id="fecha_inicio_mapa" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-amber-500 dark:text-white text-sm" placeholder="Inicio">
                    <span id="err_inicio_mapa" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Hasta</label>
                    <input type="text" name="fecha_fin" id="fecha_fin_mapa" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-amber-500 dark:text-white text-sm" placeholder="Fin">
                    <span id="err_fin_mapa" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
            </div>
            <div class="flex gap-2 w-full">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-600 hover:bg-gray-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Previsualizar">
                    <span class="material-symbols-outlined text-lg">visibility</span><span class="text-xs hidden sm:inline">Preview</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span><span class="text-xs hidden sm:inline">PDF</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span><span class="text-xs hidden sm:inline">Excel</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Word">
                    <span class="material-symbols-outlined text-lg">description</span><span class="text-xs hidden sm:inline">Word</span>
                </button>
            </div>
        </form>
    </div>

    {{-- Reporte de Eventos --}}
    <div class="report-card bg-white dark:bg-forest-card rounded-[2rem] p-7 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm relative overflow-hidden">
        <div class="absolute -right-8 -top-8 w-28 h-28 bg-purple-50 dark:bg-purple-900/10 rounded-full blur-2xl"></div>
        <div class="flex items-center gap-4 mb-4 relative z-10">
            <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                <span class="material-symbols-outlined text-3xl text-white">event</span>
            </div>
            <div>
                <h3 class="font-black text-xl text-[#064E3B] dark:text-white">Eventos</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Jornadas comunitarias, talleres ecológicos y limpiezas urbanas.</p>
            </div>
        </div>
        
        <form action="{{ route('reportes.exportar') }}" method="GET" class="report-form z-10 relative" data-tipo="eventos" novalidate>
            <input type="hidden" name="tipo" value="eventos">
            <input type="hidden" name="formato" class="formato_input" value="pdf">
            <div class="grid grid-cols-2 gap-3 mb-4">
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Desde</label>
                    <input type="text" name="fecha_inicio" id="fecha_inicio_eventos" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-purple-500 dark:text-white text-sm" placeholder="Inicio">
                    <span id="err_inicio_eventos" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
                <div>
                    <label class="text-[10px] text-gray-400 dark:text-emerald-500 font-bold uppercase tracking-wider mb-1 block">Hasta</label>
                    <input type="text" name="fecha_fin" id="fecha_fin_eventos" class="datepicker w-full px-3 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-purple-500 dark:text-white text-sm" placeholder="Fin">
                    <span id="err_fin_eventos" class="hidden text-red-500 text-xs font-bold mt-1 block"></span>
                </div>
            </div>
            <div class="flex gap-2 w-full">
                <button type="submit" data-formato="preview" class="btn-export bg-gray-600 hover:bg-gray-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Previsualizar">
                    <span class="material-symbols-outlined text-lg">visibility</span><span class="text-xs hidden sm:inline">Preview</span>
                </button>
                <button type="submit" data-formato="pdf" class="btn-export bg-red-600 hover:bg-red-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="PDF">
                    <span class="material-symbols-outlined text-lg">picture_as_pdf</span><span class="text-xs hidden sm:inline">PDF</span>
                </button>
                <button type="submit" data-formato="xlsx" class="btn-export bg-green-600 hover:bg-green-500 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Excel">
                    <span class="material-symbols-outlined text-lg">table_chart</span><span class="text-xs hidden sm:inline">Excel</span>
                </button>
                <button type="submit" data-formato="docx" class="btn-export bg-blue-500 hover:bg-blue-400 flex-1 text-white font-bold py-2.5 rounded-xl flex items-center justify-center gap-1.5" title="Word">
                    <span class="material-symbols-outlined text-lg">description</span><span class="text-xs hidden sm:inline">Word</span>
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
