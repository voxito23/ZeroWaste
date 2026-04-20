@extends('layouts.admin')

@section('title', 'Analíticas y Reportes')
@section('page_title', 'Centro de Reportes')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Estilo profesional del calendario */
    .date-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(0, 224, 150, 0.03);
        border: 1.5px solid rgba(16, 185, 129, 0.15);
        border-radius: 14px;
        padding: 0 16px;
        height: 46px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    .dark .date-input-wrap { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); }
    .date-input-wrap:hover { border-color: rgba(16, 185, 129, 0.3); }
    .dark .date-input-wrap:hover { border-color: rgba(255,255,255,0.15); }
    .date-input-wrap:focus-within, .date-input-wrap.active {
        border-color: rgba(0, 224, 150, 0.6);
        box-shadow: 0 0 0 3px rgba(0, 224, 150, 0.08), 0 0 20px rgba(0, 224, 150, 0.05);
        background: rgba(0, 224, 150, 0.03);
    }
    .date-input-wrap input {
        background: transparent;
        border: none;
        outline: none;
        color: #064E3B;
        font-size: 13px;
        font-weight: 700;
        width: 110px;
        font-family: 'Inter', sans-serif;
        letter-spacing: -0.02em;
    }
    .dark .date-input-wrap input { color: #fff; }
    .date-input-wrap input::placeholder { color: #9CA3AF; font-weight: 500; }
    .dark .date-input-wrap input::placeholder { color: #555; }
    .date-separator {
        color: #9CA3AF;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.1em;
        padding: 0 4px;
    }

    /* Menús desplegables de filtro */
    .filter-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(0, 224, 150, 0.03);
        border: 1.5px solid rgba(16, 185, 129, 0.15);
        border-radius: 14px;
        padding: 0 18px;
        height: 46px;
        font-size: 13px;
        font-weight: 700;
        color: #064E3B;
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }
    .dark .filter-btn { background: rgba(255,255,255,0.03); border-color: rgba(255,255,255,0.08); color: #fff; }
    .filter-btn:hover { border-color: rgba(16, 185, 129, 0.3); background: rgba(0, 224, 150, 0.06); }
    .dark .filter-btn:hover { border-color: rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); }

    .filter-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        min-width: 200px;
        background: #fff;
        border: 1px solid #E5E7EB;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.15);
        z-index: 100;
        overflow: hidden;
        animation: dropIn 0.15s ease-out;
    }
    .dark .filter-dropdown { background: #0F2A20; border-color: rgba(255,255,255,0.08); box-shadow: 0 20px 60px rgba(0,0,0,0.6); }
    .filter-dropdown button {
        width: 100%;
        padding: 12px 18px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #6B7280;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.15s;
        border: none;
        background: transparent;
        cursor: pointer;
    }
    .dark .filter-dropdown button { color: #aaa; }
    .filter-dropdown button:hover { background: rgba(0, 224, 150, 0.06); color: #064E3B; }
    .dark .filter-dropdown button:hover { background: rgba(255,255,255,0.04); color: #fff; }
    .filter-dropdown button.active-item { color: #059669; background: rgba(0, 224, 150, 0.06); }
    .dark .filter-dropdown button.active-item { color: #34D399; background: rgba(52, 211, 153, 0.06); }

    /* Etiqueta de filtro activo (Verde) */
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(0, 224, 150, 0.08);
        border: 1.5px solid rgba(0, 224, 150, 0.25);
        border-radius: 14px;
        padding: 0 16px;
        height: 46px;
        color: #059669;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: -0.01em;
    }
    .dark .filter-badge { color: #34D399; }
    .filter-badge .dot {
        width: 6px;
        height: 6px;
        background: #00E096;
        border-radius: 50%;
        animation: pulse-green 2s infinite;
    }
    @keyframes pulse-green {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(0,224,150,0.5); }
        50% { opacity: 0.8; box-shadow: 0 0 0 4px rgba(0,224,150,0); }
    }
    @keyframes dropIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Sobrescrituras de Flatpickr - Modo claro */
    .flatpickr-calendar { border-radius: 20px !important; border: 1px solid #E5E7EB !important; box-shadow: 0 25px 60px rgba(0,0,0,0.12) !important; }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange { background: #059669 !important; border-color: #059669 !important; color: #fff !important; font-weight: 800 !important; }
    .flatpickr-day.inRange { background: rgba(0, 224, 150, 0.15) !important; border-color: transparent !important; box-shadow: none !important; }
    .flatpickr-day:hover { background: rgba(0, 224, 150, 0.2) !important; color: #064E3B !important; }

    /* Flatpickr en modo oscuro */
    .dark .flatpickr-calendar { background: #0F2A20 !important; border: 1px solid rgba(255,255,255,0.1) !important; box-shadow: 0 25px 60px rgba(0,0,0,0.7) !important; }
    .dark .flatpickr-months .flatpickr-month { color: #fff !important; }
    .dark .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 800 !important; color: #fff !important; }
    .dark .flatpickr-weekday { color: rgba(255,255,255,0.3) !important; font-weight: 700 !important; }
    .dark .flatpickr-day { color: #ccc !important; }
    .dark .flatpickr-day.flatpickr-disabled { color: #333 !important; }
    .dark span.flatpickr-prev-month, .dark span.flatpickr-next-month { color: #fff !important; fill: #fff !important; }
    .numInputWrapper span { display: none !important; }

    .report-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .report-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.1); }
    .dark .report-card:hover { box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://npmcdn.com/flatpickr/dist/l10n/es.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let dateStart = null;
    let dateEnd = null;
    let pickerStart, pickerEnd;

    // Inicializar Flatpickr
    const fpConfig = { dateFormat: "d/m/Y", locale: "es", disableMobile: true, animate: true };

    pickerStart = flatpickr("#date-start", {
        ...fpConfig,
        onChange: function(sel) {
            if (sel[0]) { dateStart = sel[0]; document.getElementById('wrap-start').classList.add('active'); if (pickerEnd) pickerEnd.set('minDate', sel[0]); syncBadge(); }
        }
    });

    pickerEnd = flatpickr("#date-end", {
        ...fpConfig,
        onChange: function(sel) {
            if (sel[0]) { dateEnd = new Date(sel[0]); dateEnd.setHours(23,59,59,999); document.getElementById('wrap-end').classList.add('active'); syncBadge(); }
        }
    });

    // Menús desplegables
    window.toggleDD = function(id) {
        ['period-dd'].forEach(dd => { if (dd !== id) document.getElementById(dd)?.classList.add('hidden'); });
        document.getElementById(id)?.classList.toggle('hidden');
    };

    document.addEventListener('click', (e) => {
        ['period-wrap'].forEach(wId => {
            const w = document.getElementById(wId);
            if (w && !w.contains(e.target)) w.querySelector('.filter-dropdown')?.classList.add('hidden');
        });
    });

    window.setPeriodPreset = function(val, label) {
        document.getElementById('period-label').textContent = label;
        document.getElementById('period-dd').classList.add('hidden');
        document.querySelectorAll('#period-dd button').forEach(b => b.classList.remove('active-item'));
        event.target.closest('button')?.classList.add('active-item');

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        if (val === 'hoy') { dateStart = new Date(today); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '7d') { dateStart = new Date(today); dateStart.setDate(dateStart.getDate() - 6); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '30d') { dateStart = new Date(today); dateStart.setDate(dateStart.getDate() - 29); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '90d') { dateStart = new Date(today); dateStart.setDate(dateStart.getDate() - 89); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else { dateStart = null; dateEnd = null; }

        if (dateStart && pickerStart) { pickerStart.setDate(dateStart, false); document.getElementById('wrap-start').classList.add('active'); }
        else if (pickerStart) { pickerStart.clear(); document.getElementById('wrap-start').classList.remove('active'); }
        if (dateEnd && pickerEnd) { pickerEnd.setDate(dateEnd, false); document.getElementById('wrap-end').classList.add('active'); }
        else if (pickerEnd) { pickerEnd.clear(); document.getElementById('wrap-end').classList.remove('active'); }
        syncBadge();
    };

    function syncBadge() {
        const badge = document.getElementById('filter-badge');
        const text = document.getElementById('filter-badge-text');
        if (dateStart && dateEnd) {
            badge.classList.remove('hidden');
            const f = (d) => d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
            text.textContent = `${f(dateStart)} → ${f(dateEnd)}`;
        } else { badge.classList.add('hidden'); }
    }

    window.limpiarFiltro = function() {
        dateStart = null; dateEnd = null;
        if (pickerStart) { pickerStart.clear(); pickerEnd.set('minDate', null); }
        if (pickerEnd) pickerEnd.clear();
        document.getElementById('wrap-start').classList.remove('active');
        document.getElementById('wrap-end').classList.remove('active');
        document.getElementById('period-label').textContent = 'Últimos 7 días';
        syncBadge();
        if (typeof Swal !== 'undefined') {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3500,
                timerProgressBar: true,
                background: isDark ? '#0F2A20' : '#ffffff',
                customClass: {
                    popup: 'rounded-2xl border shadow-xl border-emerald-100 dark:border-emerald-800/50 p-2',
                },
                html: `
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-full bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center flex-shrink-0 border border-emerald-200 dark:border-emerald-700 text-primary">
                        <span class="material-symbols-outlined text-[18px]">check_circle</span>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-secondary dark:text-emerald-100 m-0 leading-tight">Filtros restablecidos</h4>
                    </div>
                </div>`
            });
        }
    };

    // Generar reporte con filtro de fecha
    window.generateReport = function(tipo, formato) {
        if (!dateStart || !dateEnd) {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                html: `<div class="text-center">
                    <div class="w-20 h-20 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-outlined text-4xl text-emerald-500">date_range</span>
                    </div>
                    <h3 class="text-xl font-black mb-2">¿Generar sin filtro?</h3>
                    <p class="text-sm" style="color: ${isDark ? '#9CA3AF' : '#6B7280'}">Se exportarán <span class="font-bold" style="color: ${isDark ? '#fff' : '#064E3B'}">todos los registros</span> disponibles.</p>
                </div>`,
                background: isDark ? '#0F2A20' : '#fff', color: isDark ? '#d1fae5' : '#064E3B', width: 400,
                customClass: { popup: 'rounded-[2rem] border border-emerald-100 dark:border-emerald-800/50 shadow-2xl', confirmButton: 'rounded-xl', cancelButton: 'rounded-xl' },
                showCancelButton: true, confirmButtonColor: '#059669', cancelButtonColor: isDark ? '#374151' : '#9CA3AF',
                confirmButtonText: 'Sí, exportar todo', cancelButtonText: 'Seleccionar fechas'
            }).then(r => { if (r.isConfirmed) doExport(tipo, formato, '', ''); });
            return;
        }
        const start = dateStart.toISOString().split('T')[0];
        const end = dateEnd.toISOString().split('T')[0];
        doExport(tipo, formato, start, end);
    };

    function doExport(tipo, formato, start, end) {
        let url = `{{ route('reportes.exportar') }}?tipo=${tipo}&formato=${formato}`;
        if (start) url += `&fecha_inicio=${start}`;
        if (end) url += `&fecha_fin=${end}`;

        if (formato === 'preview') {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3500,
            timerProgressBar: true,
            background: isDark ? '#0F2A20' : '#ffffff',
            customClass: {
                popup: 'rounded-2xl border shadow-xl border-blue-100 dark:border-blue-900/50 p-2',
            },
            html: `
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/40 flex items-center justify-center flex-shrink-0 border border-blue-200 dark:border-blue-700 text-blue-500">
                    <span class="material-symbols-outlined text-[18px]">info</span>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-secondary dark:text-emerald-100 m-0 leading-tight">Generando documento...</h4>
                </div>
            </div>`
        });
    }

    // Previsualización en modal (estilo Macuin)
    window.previewReport = function(tipo) {
        if (!dateStart || !dateEnd) {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                html: `<div class="text-center py-4"><span class="material-symbols-outlined text-4xl text-emerald-500 mb-4">info</span><p class="font-bold">Selecciona un rango de fechas para la vista previa.</p></div>`,
                background: isDark ? '#0F2A20' : '#fff', color: isDark ? '#d1fae5' : '#064E3B', width: 380,
                customClass: { popup: 'rounded-[2rem]', confirmButton: 'rounded-xl' },
                confirmButtonColor: '#059669', confirmButtonText: 'Entendido'
            });
            return;
        }
        const start = dateStart.toISOString().split('T')[0];
        const end = dateEnd.toISOString().split('T')[0];
        let pdfUrl = `{{ route('reportes.exportar') }}?tipo=${tipo}&formato=preview&fecha_inicio=${start}&fecha_fin=${end}`;
        const isDark = document.documentElement.classList.contains('dark');

        Swal.fire({
            html: `<div class="text-left">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-500 border border-emerald-500/20"><span class="material-symbols-outlined">description</span></div>
                    <div><h3 class="font-bold text-lg">Vista Previa</h3><p class="text-xs uppercase tracking-widest font-bold" style="color:#6B7280">${tipo}</p></div>
                </div>
                <div class="w-full h-[60vh] rounded-2xl overflow-hidden border" style="border-color: ${isDark ? 'rgba(255,255,255,0.1)' : '#E5E7EB'}; background: ${isDark ? '#0B1F18' : '#f9fafb'}"><iframe src="${pdfUrl}" width="100%" height="100%" frameborder="0"></iframe></div>
            </div>`,
            width: '85%', background: isDark ? '#0F2A20' : '#fff', color: isDark ? '#d1fae5' : '#064E3B',
            customClass: { popup: 'rounded-[2rem] border shadow-2xl', confirmButton: 'rounded-xl' },
            confirmButtonColor: '#059669', confirmButtonText: 'Cerrar', showCloseButton: true
        });
    };

    // Inicializar con preajuste de 7 días
    setPeriodPreset('7d', 'Últimos 7 días');
});
</script>
@endpush

@section('content')

{{-- Header --}}
<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-emerald-500/10 dark:bg-emerald-500/10 rounded-[1.2rem] flex items-center justify-center text-emerald-500 shadow-[0_0_20px_rgba(0,224,150,0.12)]">
            <span class="material-symbols-outlined text-[28px]">insights</span>
        </div>
        <div>
            <h2 class="text-3xl font-black tracking-tight leading-none text-[#064E3B] dark:text-white">Panel de Análisis y Reportes</h2>
            <p class="text-gray-500 dark:text-gray-400 text-sm font-medium mt-1">Exportación y generación de informes clave del ecosistema.</p>
        </div>
    </div>
</div>

{{-- ========== PROFESSIONAL FILTER BAR ========== --}}
<div class="flex flex-wrap items-center gap-3 mb-8">
    {{-- Period Dropdown --}}
    <div class="relative" id="period-wrap">
        <button onclick="toggleDD('period-dd')" class="filter-btn">
            <span class="material-symbols-outlined text-gray-400 text-[20px]">calendar_month</span>
            <span id="period-label">Últimos 7 días</span>
            <span class="material-symbols-outlined text-gray-500 text-[16px]">expand_more</span>
        </button>
        <div id="period-dd" class="filter-dropdown hidden">
            <button onclick="setPeriodPreset('hoy','Hoy')"><span class="material-symbols-outlined text-[18px] text-amber-400">light_mode</span> Hoy</button>
            <button onclick="setPeriodPreset('7d','Últimos 7 días')" class="active-item"><span class="material-symbols-outlined text-[18px] text-blue-400">date_range</span> Últimos 7 días</button>
            <button onclick="setPeriodPreset('30d','Últimos 30 días')"><span class="material-symbols-outlined text-[18px] text-emerald-400">calendar_month</span> Últimos 30 días</button>
            <button onclick="setPeriodPreset('90d','Últimos 90 días')"><span class="material-symbols-outlined text-[18px] text-purple-400">event_note</span> Últimos 90 días</button>
            <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 2px 0;"></div>
            <button onclick="setPeriodPreset('todos','Todos')"><span class="material-symbols-outlined text-[18px] text-gray-400">all_inclusive</span> Todos los registros</button>
        </div>
    </div>

    {{-- Date Inputs --}}
    <div class="date-input-wrap" id="wrap-start">
        <span class="material-symbols-outlined text-gray-400 text-[20px]">calendar_today</span>
        <input type="text" id="date-start" placeholder="dd/mm/aaaa" readonly>
        <span class="material-symbols-outlined text-gray-300 dark:text-gray-600 text-[18px]">event</span>
    </div>

    <span class="date-separator">A</span>

    <div class="date-input-wrap" id="wrap-end">
        <span class="material-symbols-outlined text-gray-400 text-[20px]">calendar_today</span>
        <input type="text" id="date-end" placeholder="dd/mm/aaaa" readonly>
        <span class="material-symbols-outlined text-gray-300 dark:text-gray-600 text-[18px]">event</span>
    </div>

    {{-- Clear --}}
    <button onclick="limpiarFiltro()" class="filter-btn" style="padding: 0 14px;" title="Limpiar Filtros">
        <span class="material-symbols-outlined text-gray-400 text-[20px]">filter_alt_off</span>
    </button>

    {{-- Active Badge --}}
    <div id="filter-badge" class="filter-badge hidden">
        <span class="dot"></span>
        <span id="filter-badge-text">07-abr → 14-abr</span>
    </div>
</div>

{{-- ========== LIVE KPIs ========== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-white/[0.02] border-2 border-emerald-100 dark:border-white/5 rounded-2xl p-6 hover:border-emerald-300 dark:hover:border-emerald-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-emerald-500 text-[20px]">group</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Usuarios</span>
        </div>
        <p class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight">{{ $totalUsuarios }}</p>
        <p class="text-[10px] text-gray-400 font-bold mt-1">Registrados en plataforma</p>
    </div>
    <div class="bg-white dark:bg-white/[0.02] border-2 border-emerald-100 dark:border-white/5 rounded-2xl p-6 hover:border-blue-300 dark:hover:border-blue-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-blue-500 text-[20px]">military_tech</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Campañas</span>
        </div>
        <p class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight">{{ $totalCampanas }}</p>
        <p class="text-[10px] text-gray-400 font-bold mt-1">Total organizadas</p>
    </div>
    <div class="bg-white dark:bg-white/[0.02] border-2 border-emerald-100 dark:border-white/5 rounded-2xl p-6 hover:border-amber-300 dark:hover:border-yellow-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-amber-500 text-[20px]">location_on</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Puntos</span>
        </div>
        <p class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight">{{ $totalPuntos }}</p>
        <p class="text-[10px] text-gray-400 font-bold mt-1">Centros de acopio</p>
    </div>
    <div class="bg-white dark:bg-white/[0.02] border-2 border-emerald-100 dark:border-white/5 rounded-2xl p-6 hover:border-purple-300 dark:hover:border-pink-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-outlined text-purple-500 text-[20px]">event</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Eventos</span>
        </div>
        <p class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight">{{ $totalEventos }}</p>
        <p class="text-[10px] text-gray-400 font-bold mt-1">Programados</p>
    </div>
</div>

{{-- ========== REPORT MODULES ========== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    {{-- 1. Usuarios --}}
    <div class="report-card bg-white dark:bg-[#0F2A20] rounded-[2rem] p-8 border-2 border-emerald-100 dark:border-emerald-800/30 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-emerald-400/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-emerald-400/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-emerald-500/10 rounded-2xl flex items-center justify-center text-emerald-500 border border-emerald-500/20 group-hover:scale-110 transition">
                    <span class="material-symbols-outlined text-[24px]">group</span>
                </div>
                <span class="px-3 py-1 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-emerald-500/20">Módulo Usuarios</span>
            </div>
            <h3 class="text-xl font-black text-[#064E3B] dark:text-white mb-2 leading-tight tracking-tight">Directorio de Usuarios</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Listado completo de registros, roles, estados y fotos de perfil embebidas.</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewReport('usuarios')" class="px-4 py-2.5 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition text-sm font-bold text-[#064E3B] dark:text-white flex items-center gap-2 border border-gray-200 dark:border-white/5">
                <span class="material-symbols-outlined text-[16px]">visibility</span> Ver
            </button>
            <button onclick="generateReport('usuarios','pdf')" class="px-5 py-2.5 rounded-full bg-emerald-600 text-white font-black text-sm hover:bg-emerald-700 shadow-[0_0_15px_rgba(0,224,150,0.25)] hover:shadow-[0_0_25px_rgba(0,224,150,0.45)] transition hover:-translate-y-0.5">PDF</button>
            <button onclick="generateReport('usuarios','xlsx')" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</button>
            <button onclick="generateReport('usuarios','docx')" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</button>
        </div>
    </div>

    {{-- 2. Campañas --}}
    <div class="report-card bg-white dark:bg-[#0F2A20] rounded-[2rem] p-8 border-2 border-emerald-100 dark:border-emerald-800/30 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-blue-400/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-blue-400/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-blue-500/10 rounded-2xl flex items-center justify-center text-blue-500 border border-blue-500/20 group-hover:scale-110 transition">
                    <span class="material-symbols-outlined text-[24px]">military_tech</span>
                </div>
                <span class="px-3 py-1 bg-blue-500/10 text-blue-600 dark:text-blue-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-blue-500/20">Módulo Campañas</span>
            </div>
            <h3 class="text-xl font-black text-[#064E3B] dark:text-white mb-2 leading-tight tracking-tight">Campañas Ecológicas</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Campañas organizadas, estado de visibilidad y clasificación.</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewReport('campanas')" class="px-4 py-2.5 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition text-sm font-bold text-[#064E3B] dark:text-white flex items-center gap-2 border border-gray-200 dark:border-white/5">
                <span class="material-symbols-outlined text-[16px]">visibility</span> Ver
            </button>
            <button onclick="generateReport('campanas','pdf')" class="px-5 py-2.5 rounded-full bg-blue-600 text-white font-black text-sm hover:bg-blue-700 shadow-[0_0_15px_rgba(59,130,246,0.25)] transition hover:-translate-y-0.5">PDF</button>
            <button onclick="generateReport('campanas','xlsx')" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</button>
            <button onclick="generateReport('campanas','docx')" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</button>
        </div>
    </div>

    {{-- 3. Mapa --}}
    <div class="report-card bg-white dark:bg-[#0F2A20] rounded-[2rem] p-8 border-2 border-emerald-100 dark:border-emerald-800/30 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-amber-400/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-amber-400/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-amber-500/10 rounded-2xl flex items-center justify-center text-amber-500 border border-amber-500/20 group-hover:scale-110 transition">
                    <span class="material-symbols-outlined text-[24px]">location_on</span>
                </div>
                <span class="px-3 py-1 bg-amber-500/10 text-amber-600 dark:text-amber-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-amber-500/20">Módulo Mapa</span>
            </div>
            <h3 class="text-xl font-black text-[#064E3B] dark:text-white mb-2 leading-tight tracking-tight">Puntos de Acopio</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Centros de reciclaje, coordenadas geográficas e imágenes de ubicación.</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewReport('mapa')" class="px-4 py-2.5 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition text-sm font-bold text-[#064E3B] dark:text-white flex items-center gap-2 border border-gray-200 dark:border-white/5">
                <span class="material-symbols-outlined text-[16px]">visibility</span> Ver
            </button>
            <button onclick="generateReport('mapa','pdf')" class="px-5 py-2.5 rounded-full bg-amber-500 text-white font-black text-sm hover:bg-amber-600 shadow-[0_0_15px_rgba(245,158,11,0.25)] transition hover:-translate-y-0.5">PDF</button>
            <button onclick="generateReport('mapa','xlsx')" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</button>
            <button onclick="generateReport('mapa','docx')" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</button>
        </div>
    </div>

    {{-- 4. Eventos --}}
    <div class="report-card bg-white dark:bg-[#0F2A20] rounded-[2rem] p-8 border-2 border-emerald-100 dark:border-emerald-800/30 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-purple-400/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-purple-400/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-purple-500/10 rounded-2xl flex items-center justify-center text-purple-500 border border-purple-500/20 group-hover:scale-110 transition">
                    <span class="material-symbols-outlined text-[24px]">event</span>
                </div>
                <span class="px-3 py-1 bg-purple-500/10 text-purple-600 dark:text-purple-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-purple-500/20">Módulo Eventos</span>
            </div>
            <h3 class="text-xl font-black text-[#064E3B] dark:text-white mb-2 leading-tight tracking-tight">Jornadas y Eventos</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Talleres ecológicos, limpiezas y jornadas comunitarias del ecosistema.</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewReport('eventos')" class="px-4 py-2.5 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition text-sm font-bold text-[#064E3B] dark:text-white flex items-center gap-2 border border-gray-200 dark:border-white/5">
                <span class="material-symbols-outlined text-[16px]">visibility</span> Ver
            </button>
            <button onclick="generateReport('eventos','pdf')" class="px-5 py-2.5 rounded-full bg-purple-600 text-white font-black text-sm hover:bg-purple-700 shadow-[0_0_15px_rgba(147,51,234,0.25)] transition hover:-translate-y-0.5">PDF</button>
            <button onclick="generateReport('eventos','xlsx')" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</button>
            <button onclick="generateReport('eventos','docx')" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</button>
        </div>
    </div>

</div>

{{-- System Status --}}
<div class="bg-gradient-to-r from-white to-emerald-50/50 dark:from-[#0F2A20] dark:to-[#112E23] rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/30 flex items-center justify-between mb-10">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-emerald-50 dark:bg-white/5 flex items-center justify-center border border-emerald-200 dark:border-white/10">
            <span class="material-symbols-outlined text-[24px] text-emerald-500">cloud_done</span>
        </div>
        <h4 class="font-bold text-[#064E3B] dark:text-white">Reportes Generados por el Sistema</h4>
    </div>
    <span class="px-4 py-2 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 rounded-full font-bold text-xs uppercase tracking-widest flex items-center gap-2">
        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span> En línea
    </span>
</div>

@endsection
