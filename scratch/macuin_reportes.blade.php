@extends('admin.dashboard')

@section('titulo', 'Analíticas y Reportes')

@section('contenido')
<style>
    /* Professional Calendar Styling */
    .date-input-wrap {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.03);
        border: 1.5px solid rgba(255,255,255,0.08);
        border-radius: 14px;
        padding: 0 16px;
        height: 46px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: pointer;
    }
    .date-input-wrap:hover { border-color: rgba(255,255,255,0.15); }
    .date-input-wrap:focus-within, .date-input-wrap.active {
        border-color: rgba(250, 204, 21, 0.6);
        box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.08), 0 0 20px rgba(250, 204, 21, 0.05);
        background: rgba(250, 204, 21, 0.03);
    }
    .date-input-wrap input {
        background: transparent;
        border: none;
        outline: none;
        color: #fff;
        font-size: 13px;
        font-weight: 700;
        width: 110px;
        font-family: 'Inter', sans-serif;
        letter-spacing: -0.02em;
    }
    .date-input-wrap input::placeholder { color: #555; font-weight: 500; }
    .date-separator {
        color: #444;
        font-size: 11px;
        font-weight: 800;
        letter-spacing: 0.1em;
        padding: 0 4px;
    }

    /* Filter Dropdowns */
    .filter-btn {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255,255,255,0.03);
        border: 1.5px solid rgba(255,255,255,0.08);
        border-radius: 14px;
        padding: 0 18px;
        height: 46px;
        font-size: 13px;
        font-weight: 700;
        color: #fff;
        cursor: pointer;
        transition: all 0.2s;
        user-select: none;
    }
    .filter-btn:hover { border-color: rgba(255,255,255,0.15); background: rgba(255,255,255,0.05); }

    .filter-dropdown {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        min-width: 200px;
        background: #141414;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.6);
        z-index: 100;
        overflow: hidden;
        animation: dropIn 0.15s ease-out;
    }
    .filter-dropdown button {
        width: 100%;
        padding: 12px 18px;
        text-align: left;
        font-size: 13px;
        font-weight: 600;
        color: #aaa;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.15s;
        border: none;
        background: transparent;
        cursor: pointer;
    }
    .filter-dropdown button:hover { background: rgba(255,255,255,0.04); color: #fff; }
    .filter-dropdown button.active-item { color: #c084fc; background: rgba(192,132,252,0.06); }

    /* Active Filter Badge (Purple) */
    .filter-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(147, 51, 234, 0.1);
        border: 1.5px solid rgba(147, 51, 234, 0.25);
        border-radius: 14px;
        padding: 0 16px;
        height: 46px;
        color: #c084fc;
        font-size: 13px;
        font-weight: 700;
        letter-spacing: -0.01em;
    }
    .filter-badge .dot {
        width: 6px;
        height: 6px;
        background: #c084fc;
        border-radius: 50%;
        animation: pulse-purple 2s infinite;
    }
    @keyframes pulse-purple {
        0%, 100% { opacity: 1; box-shadow: 0 0 0 0 rgba(192,132,252,0.5); }
        50% { opacity: 0.8; box-shadow: 0 0 0 4px rgba(192,132,252,0); }
    }
    @keyframes dropIn {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Flatpickr overrides for this view */
    .flatpickr-calendar {
        background: #111 !important;
        border: 1px solid rgba(255,255,255,0.1) !important;
        box-shadow: 0 25px 60px rgba(0,0,0,0.7) !important;
        border-radius: 20px !important;
    }
    .flatpickr-day.selected, .flatpickr-day.startRange, .flatpickr-day.endRange {
        background: #9333ea !important;
        border-color: #9333ea !important;
        color: #fff !important;
        font-weight: 800 !important;
    }
    .flatpickr-day.inRange {
        background: rgba(147, 51, 234, 0.15) !important;
        border-color: transparent !important;
        box-shadow: none !important;
    }
    .flatpickr-day:hover {
        background: rgba(147, 51, 234, 0.2) !important;
        color: #c084fc !important;
    }
    .flatpickr-months .flatpickr-month { color: #fff !important; }
    .flatpickr-current-month .flatpickr-monthDropdown-months { font-weight: 800 !important; color: #fff !important; }
    .flatpickr-weekday { color: rgba(255,255,255,0.3) !important; font-weight: 700 !important; }
    .flatpickr-day { color: #ccc !important; }
    .flatpickr-day.flatpickr-disabled { color: #333 !important; }
    span.flatpickr-prev-month, span.flatpickr-next-month { color: #fff !important; fill: #fff !important; }
    .numInputWrapper span { display: none !important; }
</style>

<!-- Header -->
<div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6 mb-6">
    <div class="flex items-center gap-4">
        <div class="w-14 h-14 bg-purple-500/10 rounded-[1.2rem] flex items-center justify-center text-purple-400 shadow-[0_0_20px_rgba(168,85,247,0.12)]">
            <span class="material-symbols-rounded text-[28px]">insights</span>
        </div>
        <div>
            <h2 class="text-3xl font-black tracking-tight leading-none">Métricas Inteligentes</h2>
            <p class="text-gray-500 text-sm font-medium mt-1">Informes consolidados y análisis de volumen.</p>
        </div>
    </div>
</div>

<!-- ========== PROFESSIONAL FILTER BAR ========== -->
<div class="flex flex-wrap items-center gap-3 mb-8">
    <!-- Granularity Dropdown -->
    <div class="relative" id="granularity-wrap">
        <button onclick="toggleDD('granularity-dd')" class="filter-btn">
            <span class="material-symbols-rounded text-gray-400 text-[20px]">bar_chart</span>
            <span id="granularity-label">Por Día</span>
            <span class="material-symbols-rounded text-gray-500 text-[16px]">expand_more</span>
        </button>
        <div id="granularity-dd" class="filter-dropdown hidden">
            <button onclick="setGranularity('dia','Por Día')" class="active-item"><span class="material-symbols-rounded text-[18px] text-purple-400">today</span> Por Día</button>
            <button onclick="setGranularity('semana','Por Semana')"><span class="material-symbols-rounded text-[18px] text-blue-400">date_range</span> Por Semana</button>
            <button onclick="setGranularity('mes','Por Mes')"><span class="material-symbols-rounded text-[18px] text-emerald-400">calendar_month</span> Por Mes</button>
        </div>
    </div>

    <!-- Period Dropdown -->
    <div class="relative" id="period-wrap">
        <button onclick="toggleDD('period-dd')" class="filter-btn">
            <span class="material-symbols-rounded text-gray-400 text-[20px]">calendar_month</span>
            <span id="period-label">Últimos 7 días</span>
            <span class="material-symbols-rounded text-gray-500 text-[16px]">expand_more</span>
        </button>
        <div id="period-dd" class="filter-dropdown hidden">
            <button onclick="setPeriodPreset('hoy','Hoy')"><span class="material-symbols-rounded text-[18px] text-yellow-400">light_mode</span> Hoy</button>
            <button onclick="setPeriodPreset('7d','Últimos 7 días')" class="active-item"><span class="material-symbols-rounded text-[18px] text-blue-400">date_range</span> Últimos 7 días</button>
            <button onclick="setPeriodPreset('30d','Últimos 30 días')"><span class="material-symbols-rounded text-[18px] text-emerald-400">calendar_month</span> Últimos 30 días</button>
            <button onclick="setPeriodPreset('90d','Últimos 90 días')"><span class="material-symbols-rounded text-[18px] text-purple-400">event_note</span> Últimos 90 días</button>
            <div style="border-top: 1px solid rgba(255,255,255,0.05); margin: 2px 0;"></div>
            <button onclick="setPeriodPreset('todos','Todos')"><span class="material-symbols-rounded text-[18px] text-gray-500">all_inclusive</span> Todos los registros</button>
        </div>
    </div>

    <!-- Date Inputs (Desde / Hasta) -->
    <div class="date-input-wrap" id="wrap-start">
        <span class="material-symbols-rounded text-gray-500 text-[20px]">calendar_today</span>
        <input type="text" id="date-start" placeholder="dd/mm/aaaa" readonly>
        <span class="material-symbols-rounded text-gray-600 text-[18px]">event</span>
    </div>

    <span class="date-separator">A</span>

    <div class="date-input-wrap" id="wrap-end">
        <span class="material-symbols-rounded text-gray-500 text-[20px]">calendar_today</span>
        <input type="text" id="date-end" placeholder="dd/mm/aaaa" readonly>
        <span class="material-symbols-rounded text-gray-600 text-[18px]">event</span>
    </div>

    <!-- Clear Filter -->
    <button onclick="limpiarFiltro()" class="filter-btn" style="padding: 0 14px;" title="Limpiar Filtros">
        <span class="material-symbols-rounded text-gray-500 text-[20px]">filter_alt_off</span>
    </button>

    <!-- Active Filter Badge (Purple) -->
    <div id="filter-badge" class="filter-badge hidden">
        <span class="dot"></span>
        <span id="filter-badge-text">07-abr → 14-abr</span>
    </div>
</div>

<!-- ========== LIVE KPIs ========== -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 hover:border-emerald-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-rounded text-emerald-400 text-[20px]">payments</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Ingresos</span>
        </div>
        <p class="text-2xl font-black text-white tracking-tight" id="kpi-ingresos">$0.00</p>
        <p class="text-[10px] text-gray-600 font-bold mt-1" id="kpi-ingresos-sub">En el periodo seleccionado</p>
    </div>
    <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 hover:border-blue-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-rounded text-blue-400 text-[20px]">shopping_bag</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Órdenes</span>
        </div>
        <p class="text-2xl font-black text-white tracking-tight" id="kpi-ordenes">0</p>
        <p class="text-[10px] text-gray-600 font-bold mt-1" id="kpi-ordenes-sub">Total procesadas</p>
    </div>
    <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 hover:border-yellow-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-rounded text-yellow-400 text-[20px]">inventory_2</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Productos</span>
        </div>
        <p class="text-2xl font-black text-white tracking-tight" id="kpi-productos">0</p>
        <p class="text-[10px] text-gray-600 font-bold mt-1">Catálogo activo</p>
    </div>
    <div class="bg-white/[0.02] border border-white/5 rounded-2xl p-6 hover:border-pink-500/20 transition group">
        <div class="flex items-center gap-3 mb-3">
            <span class="material-symbols-rounded text-pink-400 text-[20px]">group</span>
            <span class="text-[10px] text-gray-500 uppercase font-black tracking-widest">Clientes</span>
        </div>
        <p class="text-2xl font-black text-white tracking-tight" id="kpi-clientes">0</p>
        <p class="text-[10px] text-gray-600 font-bold mt-1">Usuarios registrados</p>
    </div>
</div>

<!-- ========== REPORT MODULES ========== -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

    <!-- 1. Stock Activo -->
    <div class="card p-8 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-yellow-400/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-yellow-400/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-gray-400 border border-white/5 group-hover:scale-110 transition">
                    <span class="material-symbols-rounded text-[24px]">inventory_2</span>
                </div>
                <span class="px-3 py-1 bg-yellow-400/10 text-yellow-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-yellow-400/20">Módulo Directorio</span>
            </div>
            <h3 class="text-xl font-black text-white mb-2 leading-tight tracking-tight">Stock Activo (Inventario)</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Genera un documento profesional con la lista completa de autopartes, imágenes y valoración de existencias.</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewJSON('inventario')" class="px-4 py-2.5 rounded-full bg-white/5 hover:bg-white/10 transition text-sm font-bold text-white flex items-center gap-2 border border-white/5">
                <span class="material-symbols-rounded text-[16px]">visibility</span> Ver
            </button>
            <a href="http://localhost:8001/reportes/inventario/pdf" target="_blank" class="px-5 py-2.5 rounded-full bg-yellow-400 text-black font-black text-sm hover:bg-yellow-500 shadow-[0_0_15px_rgba(255,193,7,0.25)] hover:shadow-[0_0_25px_rgba(255,193,7,0.45)] transition hover:-translate-y-0.5">PDF</a>
            <a href="http://localhost:8001/reportes/inventario/excel" target="_blank" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</a>
            <a href="http://localhost:8001/reportes/inventario/word" target="_blank" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</a>
        </div>
    </div>

    <!-- 2. Directorio Clientes -->
    <div class="card p-8 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-pink-500/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-pink-500/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-gray-400 border border-white/5 group-hover:scale-110 transition">
                    <span class="material-symbols-rounded text-[24px]">group</span>
                </div>
                <span class="px-3 py-1 bg-pink-500/10 text-pink-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-pink-500/20">Módulo Usuarios</span>
            </div>
            <h3 class="text-xl font-black text-white mb-2 leading-tight tracking-tight">Directorio de Clientes</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Listado de cuentas maestras del ecosistema. Exporta registros de usuarios con sus perfiles asignados.</p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewJSON('clientes')" class="px-4 py-2.5 rounded-full bg-white/5 hover:bg-white/10 transition text-sm font-bold text-white flex items-center gap-2 border border-white/5">
                <span class="material-symbols-rounded text-[16px]">visibility</span> Ver
            </button>
            <a href="http://localhost:8001/reportes/clientes/pdf" target="_blank" class="px-5 py-2.5 rounded-full bg-pink-500 text-white font-black text-sm hover:bg-pink-600 shadow-[0_0_15px_rgba(236,72,153,0.35)] transition hover:-translate-y-0.5">PDF</a>
            <a href="http://localhost:8001/reportes/clientes/excel" target="_blank" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</a>
            <a href="http://localhost:8001/reportes/clientes/word" target="_blank" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</a>
        </div>
    </div>

    <!-- 3. Historial Pedidos -->
    <div class="card p-8 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-sky-500/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-sky-500/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-gray-400 border border-white/5 group-hover:scale-110 transition">
                    <span class="material-symbols-rounded text-[24px]">local_shipping</span>
                </div>
                <span class="px-3 py-1 bg-sky-500/10 text-sky-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-sky-500/20">Módulo Operaciones</span>
            </div>
            <h3 class="text-xl font-black text-white mb-2 leading-tight tracking-tight">Historial de Pedidos</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Auditoría logística de todas las órdenes, estados de paquetería y recepción. <span class="text-purple-400 font-bold"></span></p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewJSON('pedidos')" class="px-4 py-2.5 rounded-full bg-white/5 hover:bg-white/10 transition text-sm font-bold text-white flex items-center gap-2 border border-white/5">
                <span class="material-symbols-rounded text-[16px]">visibility</span> Ver
            </button>
            <a href="#" onclick="downloadFiltered(event,'/reportes/pedidos/pdf')" class="px-5 py-2.5 rounded-full bg-sky-500 text-white font-black text-sm hover:bg-sky-600 shadow-[0_0_15px_rgba(14,165,233,0.35)] transition hover:-translate-y-0.5">PDF</a>
            <a href="#" onclick="downloadFiltered(event,'/reportes/pedidos/excel')" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</a>
            <a href="#" onclick="downloadFiltered(event,'/reportes/pedidos/word')" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</a>
        </div>
    </div>

    <!-- 4. Reporte Ventas -->
    <div class="card p-8 flex flex-col justify-between group overflow-hidden relative min-h-[280px]">
        <div class="absolute -bottom-10 -right-10 w-40 h-40 bg-purple-500/5 rounded-full blur-3xl pointer-events-none transition group-hover:bg-purple-500/15"></div>
        <div>
            <div class="flex items-center justify-between mb-5">
                <div class="w-12 h-12 bg-white/5 rounded-2xl flex items-center justify-center text-gray-400 border border-white/5 group-hover:scale-110 transition">
                    <span class="material-symbols-rounded text-[24px]">monitoring</span>
                </div>
                <span class="px-3 py-1 bg-purple-500/10 text-purple-400 font-bold text-[10px] uppercase tracking-widest rounded-full border border-purple-500/20">Módulo Analítico</span>
            </div>
            <h3 class="text-xl font-black text-white mb-2 leading-tight tracking-tight">Reporte de Ventas</h3>
            <p class="text-gray-500 text-sm font-medium mb-6 leading-relaxed">Extrae reportes detallados sobre la rentabilidad y líneas populares. <span class="text-purple-400 font-bold"></span></p>
        </div>
        <div class="flex flex-wrap gap-2 mt-auto items-center">
            <button onclick="previewJSON('ventas')" class="px-4 py-2.5 rounded-full bg-white/5 hover:bg-white/10 transition text-sm font-bold text-white flex items-center gap-2 border border-white/5">
                <span class="material-symbols-rounded text-[16px]">visibility</span> Ver
            </button>
            <a href="#" onclick="downloadFiltered(event,'/reportes/ventas/pdf')" class="px-5 py-2.5 rounded-full bg-purple-500 text-white font-black text-sm hover:bg-purple-600 shadow-[0_0_15px_rgba(168,85,247,0.35)] transition hover:-translate-y-0.5">PDF</a>
            <a href="#" onclick="downloadFiltered(event,'/reportes/ventas/excel')" class="px-5 py-2.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 font-bold text-sm hover:bg-emerald-500 hover:text-white transition">Excel</a>
            <a href="#" onclick="downloadFiltered(event,'/reportes/ventas/word')" class="px-5 py-2.5 rounded-full bg-blue-500/10 text-blue-400 border border-blue-500/20 font-bold text-sm hover:bg-blue-500 hover:text-white transition">Word</a>
        </div>
    </div>
</div>

<!-- System status -->
<div class="card p-6 bg-gradient-to-r from-[#111] to-[#151515] flex items-center justify-between mb-10">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-full bg-white/5 flex items-center justify-center border border-white/10">
            <span class="material-symbols-rounded text-[24px] text-gray-500">cloud_done</span>
        </div>
        <h4 class="font-bold text-white">Reportes Generados por el Sistema</h4>
    </div>
    <span class="px-4 py-2 bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 rounded-full font-bold text-xs uppercase tracking-widest flex items-center gap-2">
        <span class="w-1.5 h-1.5 bg-emerald-400 rounded-full animate-pulse"></span> En línea
    </span>
</div>

<script>
    // ========== STATE ==========
    let dateStart = null;
    let dateEnd = null;
    let currentGranularity = 'dia';
    let pickerStart, pickerEnd;

    // ========== INIT FLATPICKR (two separate professional inputs) ==========
    function initPickers() {
        const fpConfig = {
            dateFormat: "d/m/Y",
            locale: "es",
            disableMobile: true,
            animate: true,
        };

        pickerStart = flatpickr("#date-start", {
            ...fpConfig,
            onChange: function(selectedDates) {
                if (selectedDates[0]) {
                    dateStart = selectedDates[0];
                    document.getElementById('wrap-start').classList.add('active');
                    // Set min date for end picker
                    if (pickerEnd) pickerEnd.set('minDate', selectedDates[0]);
                    syncBadge();
                    updateKPIs();
                }
            }
        });

        pickerEnd = flatpickr("#date-end", {
            ...fpConfig,
            onChange: function(selectedDates) {
                if (selectedDates[0]) {
                    dateEnd = new Date(selectedDates[0]);
                    dateEnd.setHours(23, 59, 59, 999);
                    document.getElementById('wrap-end').classList.add('active');
                    syncBadge();
                    updateKPIs();
                }
            }
        });
    }

    // ========== DROPDOWN LOGIC ==========
    function toggleDD(id) {
        const allDDs = ['granularity-dd', 'period-dd'];
        allDDs.forEach(dd => { if (dd !== id) document.getElementById(dd)?.classList.add('hidden'); });
        document.getElementById(id)?.classList.toggle('hidden');
    }

    document.addEventListener('click', (e) => {
        ['granularity-wrap', 'period-wrap'].forEach(wId => {
            const w = document.getElementById(wId);
            if (w && !w.contains(e.target)) {
                w.querySelector('.filter-dropdown')?.classList.add('hidden');
            }
        });
    });

    function setGranularity(val, label) {
        currentGranularity = val;
        document.getElementById('granularity-label').textContent = label;
        document.getElementById('granularity-dd').classList.add('hidden');
        // Update active state
        document.querySelectorAll('#granularity-dd button').forEach(b => b.classList.remove('active-item'));
        event.target.closest('button').classList.add('active-item');
    }

    function setPeriodPreset(val, label) {
        document.getElementById('period-label').textContent = label;
        document.getElementById('period-dd').classList.add('hidden');

        // Update active state
        document.querySelectorAll('#period-dd button').forEach(b => b.classList.remove('active-item'));
        event.target.closest('button')?.classList.add('active-item');

        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());

        if (val === 'hoy') {
            dateStart = new Date(today);
            dateEnd = new Date(today); dateEnd.setHours(23,59,59,999);
        } else if (val === '7d') {
            dateStart = new Date(today); dateStart.setDate(dateStart.getDate() - 6);
            dateEnd = new Date(today); dateEnd.setHours(23,59,59,999);
        } else if (val === '30d') {
            dateStart = new Date(today); dateStart.setDate(dateStart.getDate() - 29);
            dateEnd = new Date(today); dateEnd.setHours(23,59,59,999);
        } else if (val === '90d') {
            dateStart = new Date(today); dateStart.setDate(dateStart.getDate() - 89);
            dateEnd = new Date(today); dateEnd.setHours(23,59,59,999);
        } else {
            dateStart = null; dateEnd = null;
        }

        // Sync pickers visually
        if (dateStart && pickerStart) {
            pickerStart.setDate(dateStart, false);
            document.getElementById('wrap-start').classList.add('active');
        } else if (pickerStart) {
            pickerStart.clear();
            document.getElementById('wrap-start').classList.remove('active');
        }
        if (dateEnd && pickerEnd) {
            pickerEnd.setDate(dateEnd, false);
            document.getElementById('wrap-end').classList.add('active');
        } else if (pickerEnd) {
            pickerEnd.clear();
            document.getElementById('wrap-end').classList.remove('active');
        }

        syncBadge();
        updateKPIs();
    }

    // ========== BADGE ==========
    function syncBadge() {
        const badge = document.getElementById('filter-badge');
        const text = document.getElementById('filter-badge-text');
        if (dateStart && dateEnd) {
            badge.classList.remove('hidden');
            const f = (d) => d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
            text.textContent = `${f(dateStart)} → ${f(dateEnd)}`;
        } else {
            badge.classList.add('hidden');
        }
    }

    function limpiarFiltro() {
        dateStart = null; dateEnd = null;
        if (pickerStart) { pickerStart.clear(); pickerEnd.set('minDate', null); }
        if (pickerEnd) pickerEnd.clear();
        document.getElementById('wrap-start').classList.remove('active');
        document.getElementById('wrap-end').classList.remove('active');
        document.getElementById('period-label').textContent = 'Últimos 7 días';
        syncBadge();
        updateKPIs();
        Toast.fire({ icon: 'success', title: 'Filtros restablecidos' });
    }

    // ========== KPIs ==========
    async function updateKPIs() {
        try {
            const h = JWT_TOKEN ? { 'Authorization': 'Bearer ' + JWT_TOKEN } : {};
            const [pR, aR, uR] = await Promise.all([
                fetch(API + '/pedidos/', { headers: h }),
                fetch(API + '/autopartes/', { headers: h }),
                fetch(API + '/usuarios/', { headers: h })
            ]);
            const pedidos = await pR.json();
            const productos = await aR.json();
            const usuarios = await uR.json();

            const apMap = {};
            if (Array.isArray(productos)) productos.forEach(p => apMap[p.id] = p);

            let filtered = Array.isArray(pedidos) ? pedidos : [];
            if (dateStart && dateEnd) {
                filtered = filtered.filter(p => {
                    if (!p.fecha) return false;
                    const s = p.fecha.toString();
                    const d = new Date(s.includes('T') ? s : s.replace(' ', 'T'));
                    return d >= dateStart && d <= dateEnd;
                });
            }

            let income = 0;
            filtered.forEach(p => {
                if (p.estado !== 'cancelado') {
                    (p.productos || []).forEach(pr => {
                        if (apMap[pr.autoparte_id]) income += apMap[pr.autoparte_id].precio * pr.cantidad;
                    });
                }
            });

            document.getElementById('kpi-ingresos').textContent = '$' + income.toLocaleString('en-US', { minimumFractionDigits: 2 });
            document.getElementById('kpi-ordenes').textContent = filtered.length;
            document.getElementById('kpi-productos').textContent = Array.isArray(productos) ? productos.length : 0;
            document.getElementById('kpi-clientes').textContent = Array.isArray(usuarios) ? usuarios.filter(u => u.rol !== 'admin').length : 0;

            // Sub labels
            if (dateStart && dateEnd) {
                const f = (d) => d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short' });
                document.getElementById('kpi-ingresos-sub').textContent = `${f(dateStart)} – ${f(dateEnd)}`;
                document.getElementById('kpi-ordenes-sub').textContent = `${f(dateStart)} – ${f(dateEnd)}`;
            } else {
                document.getElementById('kpi-ingresos-sub').textContent = 'Todos los registros';
                document.getElementById('kpi-ordenes-sub').textContent = 'Total procesadas';
            }
        } catch(e) { console.error('KPI error:', e); }
    }

    // ========== EXPORTS (with date filter) ==========
    function getDateParams() {
        if (!dateStart || !dateEnd) return { start: '', end: '' };
        return { start: dateStart.toISOString().split('T')[0], end: dateEnd.toISOString().split('T')[0] };
    }

    function downloadFiltered(e, endpoint) {
        e.preventDefault();
        const { start, end } = getDateParams();

        if ((endpoint.includes('pedidos') || endpoint.includes('ventas')) && !start && !end) {
            Swal.fire({
                html: `<div class="text-center">
                    <div class="w-20 h-20 rounded-full bg-purple-500/10 border border-purple-500/20 flex items-center justify-center mx-auto mb-6">
                        <span class="material-symbols-rounded text-4xl text-purple-400">date_range</span>
                    </div>
                    <h3 class="text-xl font-black text-white mb-2">¿Generar sin filtro?</h3>
                    <p class="text-gray-400 text-sm">Se exportarán <span class="font-bold text-white">todos los registros</span> disponibles.</p>
                </div>`,
                background: '#111', color: '#fff', width: 400,
                customClass: { popup: 'rounded-[2rem] border border-white/10' },
                showCancelButton: true, confirmButtonColor: '#9333ea', cancelButtonColor: '#374151',
                confirmButtonText: '<span class="text-white font-bold">Sí, exportar todo</span>',
                cancelButtonText: 'Seleccionar fechas'
            }).then(r => { if (r.isConfirmed) window.open(API + endpoint, '_blank'); });
            return;
        }

        let url = new URL(API + endpoint);
        if (start) url.searchParams.append('start_date', start + 'T00:00:00');
        if (end) url.searchParams.append('end_date', end + 'T23:59:59');
        window.open(url.toString(), '_blank');
        Toast.fire({ icon: 'success', title: 'Generando documento...' });
    }

    // ========== PREVIEW ==========
    async function previewJSON(tipo) {
        Swal.fire({
            html: `<div class="text-center py-8"><span class="material-symbols-rounded animate-spin text-[40px] text-purple-400 mb-4">progress_activity</span><p class="text-gray-400 font-bold">Construyendo vista previa...</p></div>`,
            background: '#111', color: '#fff', width: 400,
            customClass: { popup: 'rounded-[2rem] border border-white/10' },
            allowOutsideClick: false, showConfirmButton: false
        });
        try {
            const { start, end } = getDateParams();
            const endpointMap = { inventario: '/reportes/inventario/pdf', clientes: '/reportes/clientes/pdf', pedidos: '/reportes/pedidos/pdf', ventas: '/reportes/ventas/pdf' };
            let pdfUrl = new URL(API + endpointMap[tipo]);
            if (tipo === 'pedidos' || tipo === 'ventas') {
                if (start) pdfUrl.searchParams.append('start_date', start + 'T00:00:00');
                if (end) pdfUrl.searchParams.append('end_date', end + 'T23:59:59');
            }
            Swal.fire({
                html: `<div class="text-left">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-purple-500/10 rounded-xl flex items-center justify-center text-purple-400 border border-purple-500/20"><span class="material-symbols-rounded">description</span></div>
                        <div><h3 class="font-bold text-white text-lg">Vista Previa</h3><p class="text-xs text-gray-500 uppercase tracking-widest font-bold">${tipo}</p></div>
                    </div>
                    <div class="w-full h-[60vh] rounded-2xl overflow-hidden border border-white/10 bg-gray-900"><iframe src="${pdfUrl.toString()}" width="100%" height="100%" frameborder="0"></iframe></div>
                </div>`,
                width: '85%', background: '#111', color: '#fff',
                customClass: { popup: 'rounded-[2rem] border border-white/10 shadow-2xl' },
                confirmButtonColor: '#9333ea',
                confirmButtonText: '<span class="text-white font-bold">Cerrar</span>',
                showCloseButton: true
            });
        } catch(e) {
            Swal.fire({
                html: `<div class="text-center"><div class="w-20 h-20 rounded-full bg-red-500/10 text-red-500 flex items-center justify-center mx-auto mb-6 border border-red-500/20"><span class="material-symbols-rounded text-4xl">error</span></div><h3 class="text-xl font-black text-white mb-2">Error</h3><p class="text-gray-400 text-sm">No se pudo generar la vista previa.</p></div>`,
                background: '#111', color: '#fff', width: 400,
                customClass: { popup: 'rounded-[2rem] border border-white/10' },
                confirmButtonColor: '#ef4444', confirmButtonText: 'Cerrar'
            });
        }
    }

    // ========== BOOT ==========
    initPickers();
    setPeriodPreset('7d', 'Últimos 7 días');
</script>
@endsection