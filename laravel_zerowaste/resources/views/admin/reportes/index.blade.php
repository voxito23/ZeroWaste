@extends('layouts.admin')

@section('title', 'Analíticas y Reportes')
@section('page_title', 'Centro de Reportes')

@push('styles')
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

    .report-card { transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
    .report-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.1); }
    .dark .report-card:hover { box-shadow: 0 12px 40px rgba(0,0,0,0.3); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    let dateStart = null, dateEnd = null;
    let pickerStart, pickerEnd;
    const fpConfig = { dateFormat: "d/m/Y", locale: "es", disableMobile: true, animate: true };

    pickerStart = flatpickr("#date-start", { ...fpConfig,
        onChange: function(sel) { if (sel[0]) { dateStart = sel[0]; document.getElementById('wrap-start').classList.add('active'); if (pickerEnd) pickerEnd.set('minDate', sel[0]); syncBadge(); } }
    });
    pickerEnd = flatpickr("#date-end", { ...fpConfig,
        onChange: function(sel) { if (sel[0]) { dateEnd = new Date(sel[0]); dateEnd.setHours(23,59,59,999); document.getElementById('wrap-end').classList.add('active'); syncBadge(); updateKPIs(); } }
    });

    function fmtISO(d) { return [d.getFullYear(), String(d.getMonth()+1).padStart(2,'0'), String(d.getDate()).padStart(2,'0')].join('-'); }

    // ===== AJAX KPI Update =====
    function updateKPIs() {
        const search = document.getElementById('search-report')?.value || '';
        const categoria = document.getElementById('categoria-report')?.value || '';
        let p = new URLSearchParams();
        if (search) p.set('search', search);
        if (categoria) p.set('categoria', categoria);
        if (dateStart && dateEnd) { p.set('fecha_inicio', fmtISO(dateStart)); p.set('fecha_fin', fmtISO(dateEnd)); }

        const grid = document.getElementById('kpi-grid');
        if (grid) { grid.style.transition='opacity .2s'; grid.style.opacity='0.4'; }

        fetch(`{{ route('reportes.index') }}?${p.toString()}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(r => r.json())
        .then(data => {
            ['totalUsuarios','totalCampanas','totalPuntos','totalEventos','totalForo'].forEach(key => {
                const el = document.getElementById('kpi-' + key);
                if (!el) return;
                const target = data[key] || 0, current = parseInt(el.textContent) || 0;
                if (target === current) { el.textContent = target; return; }
                let frame = 0; const frames = 20;
                const iv = setInterval(() => { frame++; el.textContent = Math.round(current + (target - current) * (frame / frames)); if (frame >= frames) { clearInterval(iv); el.textContent = target; } }, 20);
            });
            if (grid) { grid.style.opacity = '1'; }
        })
        .catch(() => { if (grid) grid.style.opacity = '1'; });
    }

    // Search (debounce AJAX + filter modules)
    let searchTimeout;
    const searchInput = document.getElementById('search-report');
    function filterModules(query) {
        const modules = document.querySelectorAll('.report-module');
        const q = query.toLowerCase().trim();
        let anyVisible = false;
        modules.forEach(m => {
            const terms = (m.dataset.searchTerm || '') + ' ' + m.textContent.toLowerCase();
            if (!q || terms.includes(q)) {
                m.style.display = '';
                m.style.opacity = '1';
                anyVisible = true;
            } else {
                m.style.display = 'none';
            }
        });
    }
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            filterModules(this.value);
            searchTimeout = setTimeout(() => updateKPIs(), 400);
        });
    }

    // Dropdowns
    window.toggleDD = function(id) {
        ['period-dd','cat-dd','gran-dd'].forEach(dd => { if (dd !== id) document.getElementById(dd)?.classList.add('hidden'); });
        document.getElementById(id)?.classList.toggle('hidden');
    };
    document.addEventListener('click', e => {
        ['period-wrap','cat-wrap','gran-wrap'].forEach(wId => {
            const w = document.getElementById(wId);
            if (w && !w.contains(e.target)) w.querySelector('.filter-dropdown')?.classList.add('hidden');
        });
    });

    window.setCategoriaPreset = function(val, label) {
        document.getElementById('categoria-report').value = val;
        document.getElementById('cat-label').textContent = label;
        document.getElementById('cat-dd').classList.add('hidden');
        document.querySelectorAll('#cat-dd button').forEach(b => b.classList.remove('active-item'));
        if (event && event.target) event.target.closest('button')?.classList.add('active-item');
        updateKPIs();
    };

    const urlParams = new URLSearchParams(window.location.search);
    const currentCat = urlParams.get('categoria');
    if (currentCat) {
        const btn = document.querySelector(`button[onclick*="'${currentCat}'"]`);
        if (btn) { document.getElementById('cat-label').textContent = btn.innerText.trim(); document.querySelectorAll('#cat-dd button').forEach(b => b.classList.remove('active-item')); btn.classList.add('active-item'); }
    }

    let currentGranularity = 'dia';
    window.setGranularity = function(mode) {
        currentGranularity = mode;
        document.getElementById('gran-dia').classList.toggle('active-item', mode === 'dia');
        document.getElementById('gran-mes').classList.toggle('active-item', mode === 'mes');
        document.getElementById('gran-label').textContent = mode === 'dia' ? 'Por Día' : 'Por Mes';
        document.getElementById('gran-dd').classList.add('hidden');
        document.getElementById('period-opts-dia').classList.toggle('hidden', mode !== 'dia');
        document.getElementById('period-opts-mes').classList.toggle('hidden', mode !== 'mes');
        if (mode === 'dia') setPeriodPreset('hoy', 'Hoy'); else setPeriodPreset('3m', 'Últimos 3 meses');
    };

    window.setPeriodPreset = function(val, label) {
        document.getElementById('period-label').textContent = label;
        document.getElementById('period-dd').classList.add('hidden');
        document.querySelectorAll('#period-dd button.preset-btn').forEach(b => b.classList.remove('active-item'));
        if (event && event.target) event.target.closest('button')?.classList.add('active-item');
        const now = new Date(), today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        
        // Limpiar fecha minima
        if (pickerEnd) pickerEnd.set('minDate', null);

        if (val === 'custom') { dateStart = null; dateEnd = null; if (pickerStart) { pickerStart.clear(); document.getElementById('wrap-start').classList.remove('active'); } if (pickerEnd) { pickerEnd.clear(); document.getElementById('wrap-end').classList.remove('active'); } syncBadge(); updateKPIs(); return; }
        else if (val === 'hoy') { dateStart = new Date(today); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === 'ayer') { dateStart = new Date(today); dateStart.setDate(dateStart.getDate()-1); dateEnd = new Date(dateStart); dateEnd.setHours(23,59,59,999); }
        else if (val === '7d') { dateStart = new Date(today); dateStart.setDate(dateStart.getDate()-6); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '30d') { dateStart = new Date(today); dateStart.setDate(dateStart.getDate()-29); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '90d') { dateStart = new Date(today); dateStart.setDate(dateStart.getDate()-89); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === 'mes_pasado') { dateStart = new Date(now.getFullYear(), now.getMonth()-1, 1); dateEnd = new Date(now.getFullYear(), now.getMonth(), 0); dateEnd.setHours(23,59,59,999); }
        else if (val === 'mes_actual') { dateStart = new Date(now.getFullYear(), now.getMonth(), 1); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '3m') { dateStart = new Date(now.getFullYear(), now.getMonth()-3, now.getDate()); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '6m') { dateStart = new Date(now.getFullYear(), now.getMonth()-6, now.getDate()); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else if (val === '12m') { dateStart = new Date(now.getFullYear()-1, now.getMonth(), now.getDate()); dateEnd = new Date(today); dateEnd.setHours(23,59,59,999); }
        else { dateStart = null; dateEnd = null; }
        
        if (dateStart && pickerStart) { pickerStart.setDate(dateStart, false); document.getElementById('wrap-start').classList.add('active'); if (pickerEnd) pickerEnd.set('minDate', dateStart); } else if (pickerStart) { pickerStart.clear(); document.getElementById('wrap-start').classList.remove('active'); }
        if (dateEnd && pickerEnd) { pickerEnd.setDate(dateEnd, false); document.getElementById('wrap-end').classList.add('active'); } else if (pickerEnd) { pickerEnd.clear(); document.getElementById('wrap-end').classList.remove('active'); }
        
        syncBadge();
        updateKPIs();
    };

    function syncBadge() {
        const badge = document.getElementById('filter-badge'), text = document.getElementById('filter-badge-text');
        if (dateStart && dateEnd) {
            badge.classList.remove('hidden');
            const f = d => d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
            text.textContent = `${f(dateStart)} → ${f(dateEnd)}`;
            document.getElementById('form-fecha-inicio').value = fmtISO(dateStart);
            document.getElementById('form-fecha-fin').value = fmtISO(dateEnd);
        } else {
            badge.classList.add('hidden');
            document.getElementById('form-fecha-inicio').value = '';
            document.getElementById('form-fecha-fin').value = '';
        }
    }

    window.limpiarFiltro = function() {
        dateStart = null; dateEnd = null;
        if (pickerStart) { pickerStart.clear(); pickerEnd.set('minDate', null); }
        filterModules('');
        if (pickerEnd) pickerEnd.clear();
        document.getElementById('search-report').value = '';
        document.getElementById('categoria-report').value = '';
        document.getElementById('cat-label').textContent = 'Todas las Categorías';
        document.querySelectorAll('#cat-dd button').forEach(b => b.classList.remove('active-item'));
        document.querySelector('#cat-dd button:first-child')?.classList.add('active-item');
        document.getElementById('wrap-start').classList.remove('active');
        document.getElementById('wrap-end').classList.remove('active');
        document.getElementById('period-label').textContent = 'Hoy';
        currentGranularity = 'dia';
        document.getElementById('gran-label').textContent = 'Por Día';
        document.getElementById('period-opts-dia').classList.remove('hidden');
        document.getElementById('period-opts-mes').classList.add('hidden');
        syncBadge(); updateKPIs();
        if (typeof Swal !== 'undefined') {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({ toast: true, position: 'top', showConfirmButton: false, timer: 2500, timerProgressBar: true, background: isDark ? '#0F2A20' : '#fff',
                customClass: { popup: 'rounded-2xl border border-emerald-200/50 dark:border-emerald-700/40 shadow-[0_20px_50px_rgba(16,185,129,0.15)] mt-4 px-3 py-2' },
                showClass: { popup: 'swal2-show zw-toast-in' }, hideClass: { popup: 'swal2-hide zw-toast-out' },
                html: `<div class="flex items-center gap-3"><div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-teal-500 shadow-[0_4px_15px_rgba(16,185,129,0.4)] flex items-center justify-center text-white shrink-0"><span class="material-symbols-outlined text-[20px] font-black">filter_alt_off</span></div><div class="text-left pr-4"><p class="text-[10px] font-bold uppercase tracking-widest text-emerald-500 mb-0.5">Listo</p><h4 class="text-[14px] font-black m-0 leading-tight tracking-tight" style="color:${isDark?'#d1fae5':'#064E3B'}">Filtros restablecidos</h4></div></div>`
            });
        }
    };

    window.generateReport = function(tipo, formato) {
        if (!dateStart || !dateEnd) {
            const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({
                html: `<div class="text-center"><div class="w-20 h-20 rounded-full bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center mx-auto mb-6"><span class="material-symbols-outlined text-4xl text-emerald-500">date_range</span></div><h3 class="text-xl font-black mb-2">¿Generar sin filtro?</h3><p class="text-sm" style="color:${isDark?'#9CA3AF':'#6B7280'}">Se exportarán <span class="font-bold" style="color:${isDark?'#fff':'#064E3B'}">todos los registros</span> disponibles.</p></div>`,
                background: isDark ? '#0F2A20' : '#fff', color: isDark ? '#d1fae5' : '#064E3B', width: 400,
                customClass: { popup: 'rounded-[2rem] border border-emerald-100 dark:border-emerald-800/50 shadow-2xl', confirmButton: 'rounded-xl', cancelButton: 'rounded-xl' },
                showCancelButton: true, confirmButtonColor: '#059669', cancelButtonColor: isDark ? '#374151' : '#9CA3AF',
                confirmButtonText: 'Sí, exportar todo', cancelButtonText: 'Seleccionar fechas'
            }).then(r => { if (r.isConfirmed) doExport(tipo, formato, '', ''); });
            return;
        }
        doExport(tipo, formato, fmtISO(dateStart), fmtISO(dateEnd));
    };

    function doExport(tipo, formato, start, end) {
        const search = document.getElementById('search-report')?.value || '', categoria = document.getElementById('categoria-report')?.value || '';
        let url = `{{ route('reportes.exportar') }}?tipo=${tipo}&formato=${formato}`;
        if (start) url += `&fecha_inicio=${start}`; if (end) url += `&fecha_fin=${end}`;
        if (search) url += `&search=${encodeURIComponent(search)}`; if (categoria) url += `&categoria=${encodeURIComponent(categoria)}`;
        
        if (formato === 'preview') { window.open(url, '_blank'); return; }
        
        // Mostrar carga
        const loader = document.getElementById('export-loading');
        if (loader) { loader.classList.remove('hidden'); loader.classList.add('flex'); }
        // Ocultar despues de 12s
        const loaderTimeout = setTimeout(() => { if (loader) { loader.classList.add('hidden'); loader.classList.remove('flex'); } }, 12000);
        
        // Descargar archivo
        fetch(url)
            .then(resp => {
                const cd = resp.headers.get('Content-Disposition');
                let fname = `Reporte_${tipo}.${formato}`;
                if (cd) { const m = cd.match(/filename="?([^"]+)"?/); if (m) fname = m[1]; }
                return resp.blob().then(blob => ({ blob, fname }));
            })
            .then(({ blob, fname }) => {
                const a = document.createElement('a');
                a.href = URL.createObjectURL(blob);
                a.download = fname;
                a.click();
                URL.revokeObjectURL(a.href);
                // Ocultar carga
                clearTimeout(loaderTimeout);
                if (loader) { loader.classList.add('hidden'); loader.classList.remove('flex'); }
            })
            .catch(() => {
                clearTimeout(loaderTimeout);
                if (loader) { loader.classList.add('hidden'); loader.classList.remove('flex'); }
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo generar el reporte.', confirmButtonColor: '#059669' });
            });
    }

    window.previewReport = function(tipo) {
        if (!dateStart || !dateEnd) { const isDark = document.documentElement.classList.contains('dark');
            Swal.fire({ html: `<div class="text-center py-4"><span class="material-symbols-outlined text-4xl text-emerald-500 mb-4">info</span><p class="font-bold">Selecciona un rango de fechas para la vista previa.</p></div>`,
                background: isDark ? '#0F2A20' : '#fff', color: isDark ? '#d1fae5' : '#064E3B', width: 380,
                customClass: { popup: 'rounded-[2rem]', confirmButton: 'rounded-xl' }, confirmButtonColor: '#059669', confirmButtonText: 'Entendido' }); return;
        }
        const start = fmtISO(dateStart), end = fmtISO(dateEnd), search = document.getElementById('search-report')?.value || '', categoria = document.getElementById('categoria-report')?.value || '';
        let pdfUrl = `{{ route('reportes.exportar') }}?tipo=${tipo}&formato=preview&fecha_inicio=${start}&fecha_fin=${end}`;
        if (search) pdfUrl += `&search=${encodeURIComponent(search)}`; if (categoria) pdfUrl += `&categoria=${encodeURIComponent(categoria)}`;
        const isDark = document.documentElement.classList.contains('dark');
        Swal.fire({
            html: `<div class="text-left"><div class="flex items-center gap-3 mb-4"><div class="w-10 h-10 bg-emerald-500/10 rounded-xl flex items-center justify-center text-emerald-500 border border-emerald-500/20"><span class="material-symbols-outlined">description</span></div><div><h3 class="font-bold text-lg">Vista Previa</h3><p class="text-xs uppercase tracking-widest font-bold" style="color:#6B7280">${tipo}</p></div></div><div class="w-full h-[60vh] rounded-2xl overflow-hidden border" style="border-color:${isDark?'rgba(255,255,255,0.1)':'#E5E7EB'};background:${isDark?'#0B1F18':'#f9fafb'}"><iframe src="${pdfUrl}" width="100%" height="100%" frameborder="0"></iframe></div></div>`,
            width: '85%', background: isDark ? '#0F2A20' : '#fff', color: isDark ? '#d1fae5' : '#064E3B',
            customClass: { popup: 'rounded-[2rem] border shadow-2xl', confirmButton: 'rounded-xl' },
            confirmButtonColor: '#059669', confirmButtonText: 'Cerrar', showCloseButton: true
        });
    };

    // Init: load KPIs without date filter
    updateKPIs();
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

{{-- ========== PREMIUM SEARCH BAR & CATEGORIES ========== --}}
<form action="{{ route('reportes.index') }}" method="GET" id="filter-form" onsubmit="return false;" class="glass-card p-2 flex flex-col md:flex-row items-center gap-2 mb-6 relative z-[60]">
    <div class="flex items-center flex-1 w-full pl-4 pr-2">
        <span class="material-symbols-outlined text-emerald-400 dark:text-emerald-500 text-[24px]">search</span>
        <input type="text" name="search" id="search-report" value="{{ request('search') }}" placeholder="Buscar por nombre, descripción, lugar..." class="w-full bg-transparent border-none focus:ring-0 text-gray-700 dark:text-gray-200 placeholder-gray-400 font-semibold text-[15px] outline-none px-4 py-3">
    </div>
    <div class="w-full md:w-[1px] h-[1px] md:h-10 bg-gray-100 dark:bg-emerald-800/50 hidden md:block"></div>
    <div class="relative w-full md:w-auto z-50" id="cat-wrap">
        <button type="button" onclick="toggleDD('cat-dd')" class="w-full md:w-auto flex items-center justify-between gap-3 px-4 py-3 bg-emerald-50/50 dark:bg-emerald-900/10 rounded-xl transition-colors hover:bg-emerald-50 dark:hover:bg-emerald-900/30">
            <div class="flex items-center gap-2">
                <span class="material-symbols-outlined text-emerald-600 dark:text-emerald-400 text-[20px]">category</span>
                <span id="cat-label" class="text-[#064E3B] dark:text-emerald-200 font-bold text-[14px] whitespace-nowrap">Todas las Categorías</span>
            </div>
            <span class="material-symbols-outlined text-gray-400 text-[20px]">expand_more</span>
        </button>
        <div id="cat-dd" class="filter-dropdown hidden z-50" style="min-width: 260px; right: 0; left: auto;">
            <button onclick="setCategoriaPreset('', 'Todas las Categorías')" class="active-item"><span class="material-symbols-outlined text-[18px] text-emerald-400">grid_view</span> Todas las Categorías</button>
            <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 4px 0;"></div>
            <button onclick="setCategoriaPreset('admins', 'Administradores')"><span class="material-symbols-outlined text-[18px] text-purple-400">admin_panel_settings</span> Administradores</button>
            <button onclick="setCategoriaPreset('users', 'Usuarios Base')"><span class="material-symbols-outlined text-[18px] text-blue-400">person</span> Usuarios Base</button>
            <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 4px 0;"></div>
            <button onclick="setCategoriaPreset('reciclaje', 'Puntos de Reciclaje')"><span class="material-symbols-outlined text-[18px] text-emerald-500">recycling</span> Puntos de Reciclaje</button>
            <button onclick="setCategoriaPreset('centro principal', 'Centros Principales')"><span class="material-symbols-outlined text-[18px] text-amber-500">star</span> Centros Principales</button>
            <button onclick="setCategoriaPreset('contenedor público', 'Contenedores Públicos')"><span class="material-symbols-outlined text-[18px] text-gray-500">delete</span> Contenedores Públicos</button>
            <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 4px 0;"></div>
            <button onclick="setCategoriaPreset('Impacto Positivo', 'Campañas Activas')"><span class="material-symbols-outlined text-[18px] text-green-500">verified</span> Campañas Activas</button>
            <button onclick="setCategoriaPreset('Limpieza', 'Eventos / Jornadas')"><span class="material-symbols-outlined text-[18px] text-cyan-500">cleaning_services</span> Eventos y Jornadas</button>
        </div>
        <input type="hidden" name="categoria" id="categoria-report" value="{{ request('categoria') }}">
        <input type="hidden" name="fecha_inicio" id="form-fecha-inicio" value="{{ request('fecha_inicio') }}">
        <input type="hidden" name="fecha_fin" id="form-fecha-fin" value="{{ request('fecha_fin') }}">
        <button type="submit" class="hidden"></button>
    </div>
</form>

{{-- ========== PROFESSIONAL FILTER BAR ========== --}}
<div class="flex flex-wrap items-center gap-3 mb-8">
    {{-- Granularity Toggle (Por Día / Por Mes) --}}
    <div class="relative" id="gran-wrap">
        <button onclick="toggleDD('gran-dd')" class="filter-btn">
            <span class="material-symbols-outlined text-gray-400 text-[20px]">bar_chart</span>
            <span id="gran-label">Por Día</span>
            <span class="material-symbols-outlined text-gray-500 text-[16px]">expand_more</span>
        </button>
        <div id="gran-dd" class="filter-dropdown hidden">
            <button id="gran-dia" onclick="setGranularity('dia')" class="active-item"><span class="material-symbols-outlined text-[18px] text-blue-400">today</span> Por Día</button>
            <button id="gran-mes" onclick="setGranularity('mes')"><span class="material-symbols-outlined text-[18px] text-purple-400">calendar_month</span> Por Mes</button>
        </div>
    </div>

    {{-- Period Dropdown --}}
    <div class="relative" id="period-wrap">
        <button onclick="toggleDD('period-dd')" class="filter-btn">
            <span class="material-symbols-outlined text-gray-400 text-[20px]">calendar_month</span>
            <span id="period-label">Hoy</span>
            <span class="material-symbols-outlined text-gray-500 text-[16px]">expand_more</span>
        </button>
        <div id="period-dd" class="filter-dropdown hidden" style="min-width: 220px;">
            {{-- Daily options --}}
            <div id="period-opts-dia">
                <button class="preset-btn active-item" onclick="setPeriodPreset('hoy','Hoy')"><span class="material-symbols-outlined text-[18px] text-amber-400">light_mode</span> Hoy</button>
                <button class="preset-btn" onclick="setPeriodPreset('ayer','Ayer')"><span class="material-symbols-outlined text-[18px] text-orange-400">history</span> Ayer</button>
                <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 2px 0;"></div>
                <button class="preset-btn" onclick="setPeriodPreset('7d','Últimos 7 días')"><span class="material-symbols-outlined text-[18px] text-blue-400">date_range</span> Últimos 7 días</button>
                <button class="preset-btn" onclick="setPeriodPreset('30d','Últimos 30 días')"><span class="material-symbols-outlined text-[18px] text-emerald-400">calendar_month</span> Últimos 30 días</button>
                <button class="preset-btn" onclick="setPeriodPreset('90d','Últimos 90 días')"><span class="material-symbols-outlined text-[18px] text-purple-400">event_note</span> Últimos 90 días</button>
                <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 2px 0;"></div>
                <button class="preset-btn" onclick="setPeriodPreset('mes_pasado','Mes pasado')"><span class="material-symbols-outlined text-[18px] text-gray-400">undo</span> Mes pasado</button>
                <button class="preset-btn" onclick="setPeriodPreset('mes_actual','Mes actual')"><span class="material-symbols-outlined text-[18px] text-emerald-500">event_available</span> Mes actual</button>
                <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 2px 0;"></div>
                <button class="preset-btn" onclick="setPeriodPreset('custom','Fecha Personalizada')"><span class="material-symbols-outlined text-[18px] text-rose-400">edit_calendar</span> Fecha Personalizada</button>
            </div>
            {{-- Monthly options --}}
            <div id="period-opts-mes" class="hidden">
                <button class="preset-btn active-item" onclick="setPeriodPreset('3m','Últimos 3 meses')"><span class="material-symbols-outlined text-[18px] text-blue-400">date_range</span> Últimos 3 meses</button>
                <button class="preset-btn" onclick="setPeriodPreset('6m','Últimos 6 meses')"><span class="material-symbols-outlined text-[18px] text-emerald-400">calendar_month</span> Últimos 6 meses</button>
                <button class="preset-btn" onclick="setPeriodPreset('12m','Últimos 12 meses')"><span class="material-symbols-outlined text-[18px] text-purple-400">event_note</span> Últimos 12 meses</button>
                <div style="border-top: 1px solid rgba(0,0,0,0.05); margin: 2px 0;"></div>
                <button class="preset-btn" onclick="setPeriodPreset('custom','Fecha Personalizada')"><span class="material-symbols-outlined text-[18px] text-rose-400">edit_calendar</span> Fecha Personalizada</button>
            </div>
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
    <div id="filter-badge" class="filter-badge hidden ml-auto">
        <span class="dot"></span>
        <span id="filter-badge-text">07-abr → 14-abr</span>
    </div>
</div>

{{-- ========== LIVE KPIs ========== --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8" id="kpi-grid">
    @php $rkpis = [
        ['label'=>'Usuarios','icon'=>'group','key'=>'totalUsuarios','val'=>$totalUsuarios,'sub'=>'Registrados en plataforma','color'=>'#10B981','bg'=>'from-emerald-400 to-emerald-600'],
        ['label'=>'Campañas','icon'=>'military_tech','key'=>'totalCampanas','val'=>$totalCampanas,'sub'=>'Total organizadas','color'=>'#3B82F6','bg'=>'from-blue-400 to-blue-600'],
        ['label'=>'Puntos','icon'=>'location_on','key'=>'totalPuntos','val'=>$totalPuntos,'sub'=>'Centros de acopio','color'=>'#F59E0B','bg'=>'from-amber-400 to-orange-500'],
        ['label'=>'Eventos','icon'=>'event','key'=>'totalEventos','val'=>$totalEventos,'sub'=>'Programados','color'=>'#8B5CF6','bg'=>'from-purple-400 to-purple-600'],
        ['label'=>'Foro','icon'=>'forum','key'=>'totalForo','val'=>$totalForo,'sub'=>'Posts publicados','color'=>'#059669','bg'=>'from-teal-400 to-emerald-600'],
    ]; @endphp
    @foreach($rkpis as $ri => $rk)
    <div class="glass-card p-5 group relative overflow-hidden">
        {{-- Faded icon watermark --}}
        <div class="absolute -bottom-3 -right-3 pointer-events-none opacity-[0.15] group-hover:opacity-[0.25] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 90px; color: {{$rk['color']}};">{{$rk['icon']}}</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{$rk['bg']}} flex items-center justify-center shadow-lg text-white group-hover:scale-110 transition-transform" style="box-shadow: 0 4px 15px {{$rk['color']}}40;">
                    <span class="material-symbols-outlined text-[20px]">{{$rk['icon']}}</span>
                </div>
                <span class="text-[11px] text-gray-500 uppercase font-black tracking-widest">{{$rk['label']}}</span>
            </div>
            <p class="text-2xl font-black text-[#064E3B] dark:text-white tracking-tight" id="kpi-{{$rk['key']}}">{{ $rk['val'] }}</p>
            <p class="text-[10px] text-gray-400 font-bold mt-1">{{$rk['sub']}}</p>
        </div>
    </div>
    @endforeach
</div>

<div id="export-loading" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-white/40 dark:bg-black/60 backdrop-blur-sm">
    <div class="rounded-[2rem] p-10 flex flex-col items-center gap-5 max-w-sm w-full mx-4 bg-white/90 dark:bg-gray-900/90 backdrop-blur-xl border border-gray-100 dark:border-gray-800 shadow-2xl shadow-emerald-500/10 dark:shadow-emerald-900/20">
        <div class="relative">
            <div class="w-16 h-16 border-[3px] border-gray-100 dark:border-gray-800 rounded-full"></div>
            <div class="w-16 h-16 border-[3px] border-transparent border-t-emerald-500 rounded-full animate-spin absolute inset-0"></div>
            <div class="absolute inset-0 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-500 text-xl">description</span>
            </div>
        </div>
        <div class="text-center">
            <p class="text-lg font-black text-gray-900 dark:text-white tracking-tight">Generando reporte</p>
            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium mt-1">Preparando tu documento...</p>
        </div>
        <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-1.5 overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-400 to-emerald-600 h-full rounded-full animate-pulse" style="width: 60%; animation: loadbar 2s ease-in-out infinite;"></div>
        </div>
    </div>
</div>
<style>
@keyframes loadbar { 0% { width: 20%; } 50% { width: 80%; } 100% { width: 20%; } }
</style>

{{-- ========== REPORT MODULES ========== --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8" id="report-modules">

    {{-- 1. Usuarios --}}
    <div class="glass-card p-7 flex flex-col justify-between group overflow-hidden relative min-h-[260px] report-module" data-search-term="directorio usuarios registros roles fotos perfil">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gradient-to-tr from-emerald-400/20 to-teal-400/5 rounded-full blur-3xl pointer-events-none transition duration-700 group-hover:scale-150"></div>
        <div class="absolute -bottom-5 -right-5 pointer-events-none opacity-[0.15] group-hover:opacity-[0.25] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 150px; color: #10B981;">group</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-5">
                <div class="w-14 h-14 rounded-[1.25rem] bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-white shadow-[0_8px_20px_rgba(52,211,153,0.3)] group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px]">group</span>
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
    <div class="glass-card p-7 flex flex-col justify-between group overflow-hidden relative min-h-[260px] report-module" data-search-term="campañas ecologicas organizadas visibilidad clasificacion">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gradient-to-tr from-blue-400/20 to-cyan-400/5 rounded-full blur-3xl pointer-events-none transition duration-700 group-hover:scale-150"></div>
        <div class="absolute -bottom-5 -right-5 pointer-events-none opacity-[0.15] group-hover:opacity-[0.25] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 150px; color: #3B82F6;">military_tech</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-5">
                <div class="w-14 h-14 rounded-[1.25rem] bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center text-white shadow-[0_8px_20px_rgba(96,165,250,0.3)] group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px]">military_tech</span>
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
    <div class="glass-card p-7 flex flex-col justify-between group overflow-hidden relative min-h-[260px] report-module" data-search-term="puntos acopio centros reciclaje mapa coordenadas ubicacion">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gradient-to-tr from-amber-400/20 to-orange-400/5 rounded-full blur-3xl pointer-events-none transition duration-700 group-hover:scale-150"></div>
        <div class="absolute -bottom-5 -right-5 pointer-events-none opacity-[0.15] group-hover:opacity-[0.25] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 150px; color: #F59E0B;">location_on</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-5">
                <div class="w-14 h-14 rounded-[1.25rem] bg-gradient-to-br from-amber-400 to-orange-500 flex items-center justify-center text-white shadow-[0_8px_20px_rgba(251,191,36,0.3)] group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px]">location_on</span>
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
    <div class="glass-card p-7 flex flex-col justify-between group overflow-hidden relative min-h-[260px] report-module" data-search-term="jornadas eventos talleres limpiezas comunitarias">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gradient-to-tr from-purple-400/20 to-pink-400/5 rounded-full blur-3xl pointer-events-none transition duration-700 group-hover:scale-150"></div>
        <div class="absolute -bottom-5 -right-5 pointer-events-none opacity-[0.15] group-hover:opacity-[0.25] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 150px; color: #8B5CF6;">event</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-5">
                <div class="w-14 h-14 rounded-[1.25rem] bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center text-white shadow-[0_8px_20px_rgba(192,132,252,0.3)] group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px]">event</span>
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

    {{-- 5. Foro --}}
    <div class="glass-card p-7 flex flex-col justify-between group overflow-hidden relative min-h-[260px] report-module" data-search-term="foro posts comunidad comentarios preguntas discusiones">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-gradient-to-tr from-teal-400/20 to-emerald-400/5 rounded-full blur-3xl pointer-events-none transition duration-700 group-hover:scale-150"></div>
        <div class="absolute -bottom-5 -right-5 pointer-events-none opacity-[0.15] group-hover:opacity-[0.25] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 150px; color: #059669;">forum</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center justify-between mb-5">
                <div class="w-14 h-14 rounded-[1.25rem] bg-gradient-to-br from-teal-400 to-emerald-600 flex items-center justify-center text-white shadow-[0_8px_20px_rgba(5,150,105,0.3)] group-hover:scale-110 group-hover:-translate-y-1 transition-all duration-300">
                    <span class="material-symbols-outlined text-[28px]">forum</span>
                </div>
                <span class="px-3 py-1 bg-teal-500/10 text-teal-600 dark:text-teal-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-teal-500/20">Módulo Foro</span>
            </div>
            <h3 class="text-xl font-black text-[#064E3B] dark:text-white mb-2 leading-tight tracking-tight">Comunidad y Foro</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Reporte de publicaciones, actividad de usuarios y discusiones.</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewReport('foro')" class="px-4 py-2.5 rounded-full bg-gray-100 dark:bg-white/5 hover:bg-gray-200 dark:hover:bg-white/10 transition text-sm font-bold text-[#064E3B] dark:text-white flex items-center gap-2 border border-gray-200 dark:border-white/5">
                <span class="material-symbols-outlined text-[16px]">visibility</span> Ver
            </button>
            <button onclick="generateReport('foro','pdf')" class="px-5 py-2.5 rounded-full bg-teal-600 text-white font-black text-sm hover:bg-teal-700 shadow-[0_0_15px_rgba(5,150,105,0.25)] hover:shadow-[0_0_25px_rgba(5,150,105,0.45)] transition hover:-translate-y-0.5">PDF</button>
            <button onclick="generateReport('foro','xlsx')" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</button>
            <button onclick="generateReport('foro','docx')" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-600 dark:text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</button>
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
