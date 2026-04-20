@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Panel de Administración')

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Validación inline del formulario de PDF
        const pdfForm = document.getElementById('pdfForm');
        if (pdfForm) {
            pdfForm.addEventListener('submit', function(e) {
                const inicioInput = document.getElementById('fecha_inicio');
                const finInput = document.getElementById('fecha_fin');
                const errInicio = document.getElementById('err-fecha-inicio');
                const errFin = document.getElementById('err-fecha-fin');

                // Reset
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
                }

                if (!isValid) {
                    e.preventDefault();
                }
            });
        }

        // CountUp Animation
        document.querySelectorAll('[data-count]').forEach(el => {
            const target = parseInt(el.dataset.count);
            const duration = 1200;
            const frameDuration = 1000 / 60;
            const totalFrames = Math.round(duration / frameDuration);
            let frame = 0;
            const counter = setInterval(() => {
                frame++;
                const progress = frame / totalFrames;
                const easeOut = 1 - Math.pow(1 - progress, 3);
                const current = Math.round(target * easeOut);
                el.textContent = current.toLocaleString('es-MX');
                if (frame === totalFrames) {
                    clearInterval(counter);
                    el.textContent = target.toLocaleString('es-MX');
                }
            }, frameDuration);
        });

        // Gráfica de sentimiento (Dona)
        const sentData = @json($sentimiento);
        if (document.getElementById('sentChart')) {
            new Chart(document.getElementById('sentChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Positivo', 'Neutro', 'Negativo'],
                    datasets: [{
                        data: [sentData.POS || 0, sentData.NEU || 0, sentData.NEG || 0],
                        backgroundColor: ['#10B981', '#9CA3AF', '#EF4444'],
                        borderWidth: 0,
                        hoverOffset: 8,
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#064E3B',
                            titleFont: { family: 'Inter', weight: 'bold' },
                            bodyFont: { family: 'Inter' },
                            cornerRadius: 12,
                            padding: 12,
                            callbacks: {
                                label: function(ctx) {
                                    return ' ' + ctx.label + ': ' + ctx.raw + '%';
                                }
                            }
                        }
                    },
                    cutout: '75%',
                    animation: {
                        animateScale: true,
                        animateRotate: true,
                        duration: 1200,
                        easing: 'easeOutQuart'
                    }
                }
            });
        }

        // Directory Tabs
        const tabBtns = document.querySelectorAll('[data-tab-target]');
        const tabRows = document.querySelectorAll('#directoryTable tbody tr[data-role]');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                tabBtns.forEach(b => {
                    b.classList.remove('bg-primary', 'text-secondary', 'shadow-lg');
                    b.classList.add('bg-gray-100', 'dark:bg-emerald-900/30', 'text-gray-600', 'dark:text-emerald-300');
                });
                btn.classList.remove('bg-gray-100', 'dark:bg-emerald-900/30', 'text-gray-600', 'dark:text-emerald-300');
                btn.classList.add('bg-primary', 'text-secondary', 'shadow-lg');

                const filter = btn.dataset.tabTarget;
                tabRows.forEach(row => {
                    if (filter === 'all' || row.dataset.role === filter) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });

        // Directory search
        const searchInput = document.getElementById('directorySearch');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase();
                tabRows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                // Reset tab to "all" on search
                tabBtns.forEach(b => {
                    b.classList.remove('bg-primary', 'text-secondary', 'shadow-lg');
                    b.classList.add('bg-gray-100', 'dark:bg-emerald-900/30', 'text-gray-600', 'dark:text-emerald-300');
                });
                tabBtns[0].classList.remove('bg-gray-100', 'dark:bg-emerald-900/30', 'text-gray-600', 'dark:text-emerald-300');
                tabBtns[0].classList.add('bg-primary', 'text-secondary', 'shadow-lg');
            });
        }

        // Stagger animation for table rows
        document.querySelectorAll('.stagger-row').forEach((row, i) => {
            row.style.opacity = '0';
            row.style.transform = 'translateY(10px)';
            setTimeout(() => {
                row.style.transition = 'all 0.3s ease';
                row.style.opacity = '1';
                row.style.transform = 'translateY(0)';
            }, 80 * i);
        });
    });
</script>
@endpush

@section('content')

{{-- Tarjetas de métricas principales --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    {{-- Usuarios --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07] group-hover:opacity-[0.12] transition-opacity duration-500">
            <span class="material-symbols-outlined text-[5rem] text-emerald-600">group</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <span class="material-symbols-outlined text-white text-xl">group</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Usuarios</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-white relative z-10" data-count="{{ $totalUsuarios }}">0</p>
        <div class="flex items-center gap-1 mt-1 relative z-10">
            @if($trendUsuarios >= 0)
                <span class="material-symbols-outlined text-sm text-emerald-500">trending_up</span>
                <span class="text-xs font-bold text-emerald-500">+{{ $trendUsuarios }}%</span>
            @else
                <span class="material-symbols-outlined text-sm text-red-500">trending_down</span>
                <span class="text-xs font-bold text-red-500">{{ $trendUsuarios }}%</span>
            @endif
            <span class="text-xs text-gray-400 ml-1">vs semana anterior</span>
        </div>
    </div>

    {{-- Posts --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07] group-hover:opacity-[0.12] transition-opacity duration-500">
            <span class="material-symbols-outlined text-[5rem] text-blue-600">forum</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <span class="material-symbols-outlined text-white text-xl">forum</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Publicaciones</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-white relative z-10" data-count="{{ $totalPosts }}">0</p>
        <div class="flex items-center gap-1 mt-1 relative z-10">
            @if($trendPosts >= 0)
                <span class="material-symbols-outlined text-sm text-emerald-500">trending_up</span>
                <span class="text-xs font-bold text-emerald-500">+{{ $trendPosts }}%</span>
            @else
                <span class="material-symbols-outlined text-sm text-red-500">trending_down</span>
                <span class="text-xs font-bold text-red-500">{{ $trendPosts }}%</span>
            @endif
            <span class="text-xs text-gray-400 ml-1">vs semana anterior</span>
        </div>
    </div>

    {{-- Puntos --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07] group-hover:opacity-[0.12] transition-opacity duration-500">
            <span class="material-symbols-outlined text-[5rem] text-teal-600">location_on</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-teal-400 to-teal-600 flex items-center justify-center shadow-lg shadow-teal-500/30">
                <span class="material-symbols-outlined text-white text-xl">location_on</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Puntos de Acopio</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-white relative z-10" data-count="{{ $totalPuntos }}">0</p>
        <div class="flex items-center gap-1 mt-1 relative z-10">
            <span class="material-symbols-outlined text-sm text-blue-500">verified</span>
            <span class="text-xs text-gray-400">centros activos</span>
        </div>
    </div>

    {{-- Campañas --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07] group-hover:opacity-[0.12] transition-opacity duration-500">
            <span class="material-symbols-outlined text-[5rem] text-amber-600">campaign</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-amber-400 to-amber-600 flex items-center justify-center shadow-lg shadow-amber-500/30">
                <span class="material-symbols-outlined text-white text-xl">campaign</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Campañas</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-white relative z-10" data-count="{{ $campaignCount }}">0</p>
        <div class="flex items-center gap-1 mt-1 relative z-10">
            @if($trendCampanas >= 0)
                <span class="material-symbols-outlined text-sm text-emerald-500">trending_up</span>
                <span class="text-xs font-bold text-emerald-500">+{{ $trendCampanas }}%</span>
            @else
                <span class="material-symbols-outlined text-sm text-red-500">trending_down</span>
                <span class="text-xs font-bold text-red-500">{{ $trendCampanas }}%</span>
            @endif
            <span class="text-xs text-gray-400 ml-1">vs semana anterior</span>
        </div>
    </div>
</div>

{{-- Segunda fila de tarjetas --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
    {{-- Mensajes --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07]">
            <span class="material-symbols-outlined text-[5rem] text-indigo-600">mail</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-indigo-400 to-indigo-600 flex items-center justify-center shadow-lg shadow-indigo-500/30">
                <span class="material-symbols-outlined text-white text-xl">mail</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Mensajes</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-white relative z-10" data-count="{{ $totalMensajes }}">0</p>
        <a href="{{ route('mensajes.index') }}" class="text-xs text-primary font-bold hover:underline mt-1 inline-block relative z-10">Ver bandeja →</a>
    </div>

    {{-- Recuperación --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07]">
            <span class="material-symbols-outlined text-[5rem] text-red-600">lock_reset</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/30">
                <span class="material-symbols-outlined text-white text-xl">lock_reset</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Recuperación</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-white relative z-10" data-count="{{ $totalSolicitudes }}">0</p>
        <a href="{{ route('recuperacion.index') }}" class="text-xs text-primary font-bold hover:underline mt-1 inline-block relative z-10">Gestionar →</a>
    </div>

    {{-- Actividades --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07]">
            <span class="material-symbols-outlined text-[5rem] text-purple-600">bolt</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                <span class="material-symbols-outlined text-white text-xl">bolt</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Actividades</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-white relative z-10" data-count="{{ $totalActividades }}">0</p>
        <span class="text-xs text-gray-400 relative z-10">eventos registrados</span>
    </div>

    {{-- Último Registro --}}
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute right-4 top-4 opacity-[0.07]">
            <span class="material-symbols-outlined text-[5rem] text-cyan-600">person_add</span>
        </div>
        <div class="flex items-center gap-3 mb-3 relative z-10">
            <div class="w-11 h-11 rounded-xl bg-gradient-to-br from-cyan-400 to-cyan-600 flex items-center justify-center shadow-lg shadow-cyan-500/30">
                <span class="material-symbols-outlined text-white text-xl">person_add</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Último Registro</span>
        </div>
        @if($ultimoRegistro)
            <p class="text-lg font-black text-[#064E3B] dark:text-white relative z-10 truncate">{{ $ultimoRegistro->nombre }}</p>
            <span class="text-xs text-gray-400 relative z-10">{{ $ultimoRegistro->created_at ? $ultimoRegistro->created_at->diffForHumans() : 'Reciente' }}</span>
        @else
            <p class="text-lg font-black text-gray-400 relative z-10">Sin registros</p>
        @endif
    </div>
</div>

{{-- Gráficas --}}
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl transition-all duration-500">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-lg text-[#064E3B] dark:text-emerald-100">Usuarios registrados</h3>
            <span class="text-xs px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full font-bold text-emerald-600 dark:text-emerald-400">Últimos 7 días</span>
        </div>
        <div class="relative h-[250px] w-full flex items-center justify-center">
            <img src="https://zerowaste-qro.com/api/analisis/grafica_usuarios" alt="Gráfica de Usuarios Registrados" class="w-full h-full object-contain">
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl transition-all duration-500 flex flex-col items-center">
        <div class="flex items-center justify-between w-full mb-6">
            <h3 class="font-bold text-lg text-[#064E3B] dark:text-emerald-100">Sentimiento de la Comunidad (NLP)</h3>
            <span class="text-xs px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full font-bold text-emerald-600 dark:text-emerald-400">IA</span>
        </div>
        
        <div class="w-full max-w-[220px] mb-4 relative">
            <canvas id="sentChart"></canvas>
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="text-center mt-2">
                    <span class="text-3xl font-black text-[#00E096]">{{ $sentimiento['POS'] ?? 0 }}%</span>
                </div>
            </div>
        </div>

        <div class="flex gap-4 justify-center text-xs font-semibold w-full mt-2">
            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                <span class="w-2.5 h-2.5 rounded-sm bg-[#10B981]"></span> Positivo {{ $sentimiento['POS'] ?? 0 }}%
            </div>
            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                <span class="w-2.5 h-2.5 rounded-sm bg-[#9CA3AF]"></span> Neutral {{ $sentimiento['NEU'] ?? 0 }}%
            </div>
            <div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">
                <span class="w-2.5 h-2.5 rounded-sm bg-[#EF4444]"></span> Negativo {{ $sentimiento['NEG'] ?? 0 }}%
            </div>
        </div>
    </div>
</div>

{{-- Directorio de Usuarios (estilo Macuin) --}}
<div class="bg-white dark:bg-forest-card rounded-[2rem] border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm overflow-hidden mb-8 hover:shadow-2xl transition-all duration-500">
    <div class="p-5 border-b border-emerald-50 dark:border-emerald-800/50">
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
            <div>
                <h3 class="font-bold text-lg text-[#064E3B] dark:text-emerald-100">Directorio de Usuarios</h3>
                <p class="text-xs text-gray-400 dark:text-emerald-500 mt-0.5">Gestión de roles y accesos del ecosistema</p>
            </div>
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Tabs --}}
                <button data-tab-target="all" class="px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-primary text-secondary shadow-lg">
                    Todos <span class="ml-1 opacity-70">{{ $totalUsuarios }}</span>
                </button>
                <button data-tab-target="admin" class="px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-gray-100 dark:bg-emerald-900/30 text-gray-600 dark:text-emerald-300">
                    <span class="material-symbols-outlined text-[0.8rem] align-middle">shield</span> Admins <span class="ml-1 opacity-70">{{ $totalAdmins }}</span>
                </button>
                <button data-tab-target="user" class="px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-gray-100 dark:bg-emerald-900/30 text-gray-600 dark:text-emerald-300">
                    <span class="material-symbols-outlined text-[0.8rem] align-middle">person</span> Usuarios <span class="ml-1 opacity-70">{{ $totalNormales }}</span>
                </button>
                @if($totalBloqueados > 0)
                <button data-tab-target="blocked" class="px-4 py-1.5 rounded-full text-xs font-bold transition-all bg-gray-100 dark:bg-emerald-900/30 text-gray-600 dark:text-emerald-300">
                    <span class="material-symbols-outlined text-[0.8rem] align-middle">block</span> Bloqueados <span class="ml-1 opacity-70">{{ $totalBloqueados }}</span>
                </button>
                @endif
            </div>
        </div>
        <div class="mt-4">
            <div class="relative">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input type="text" id="directorySearch" placeholder="Buscar por nombre o correo..." class="w-full sm:w-80 pl-10 pr-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white text-sm font-medium transition-all focus:shadow-lg">
            </div>
        </div>
    </div>
    <div class="overflow-x-auto">
    <table id="directoryTable" class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-[#064E3B] dark:text-emerald-200 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-3 pl-5">Usuario</th>
                <th class="p-3">Rol</th>
                <th class="p-3">Estado</th>
                <th class="p-3">Ubicación</th>
                <th class="p-3">Registro</th>
                <th class="p-3 text-right pr-5">Acción</th>
            </tr>
        </thead>
        <tbody class="dark:text-emerald-100">
            @forelse ($todosUsuarios as $u)
            @php
                $role = $u->is_admin ? 'admin' : 'user';
                if ($u->bloqueado ?? false) $role = 'blocked';
            @endphp
            <tr class="border-b border-emerald-50 dark:border-emerald-800/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors stagger-row" data-role="{{ $role }}">
                <td class="p-3 pl-5">
                    <div class="flex items-center gap-3">
                        @php $userFoto = $u->foto_perfil ?? 'default.png'; @endphp
                        <img src="{{ url('/static/img/perfiles/' . $userFoto) }}" alt="{{ $u->nombre }}"
                             class="w-9 h-9 rounded-full border-2 {{ $u->is_admin ? 'border-purple-400' : 'border-primary' }} object-cover shadow-sm"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%2334D399%22 width=%2240%22 height=%2240%22 rx=%2220%22/><text x=%2250%%22 y=%2254%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2218%22 font-weight=%22bold%22 font-family=%22Inter%22>{{ strtoupper(substr($u->nombre, 0, 1)) }}</text></svg>';">
                        <div>
                            <p class="font-bold text-[#064E3B] dark:text-white text-sm">{{ $u->nombre }}</p>
                            <p class="text-xs text-gray-400 dark:text-emerald-500">{{ $u->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="p-3">
                    @if($u->is_admin)
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-purple-100 to-purple-50 text-purple-700 dark:from-purple-900/40 dark:to-purple-900/20 dark:text-purple-300 shadow-sm">
                            <span class="material-symbols-outlined text-[0.7rem] align-middle mr-0.5">shield_person</span> Admin
                        </span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-gradient-to-r from-emerald-100 to-emerald-50 text-emerald-700 dark:from-emerald-900/40 dark:to-emerald-900/20 dark:text-emerald-300 shadow-sm">
                            <span class="material-symbols-outlined text-[0.7rem] align-middle mr-0.5">person</span> Usuario
                        </span>
                    @endif
                </td>
                <td class="p-3">
                    @if($u->bloqueado ?? false)
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                            <span class="text-xs font-bold text-red-500">Bloqueado</span>
                        </span>
                    @else
                        <span class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50 animate-pulse"></span>
                            <span class="text-xs font-bold text-emerald-500">Activo</span>
                        </span>
                    @endif
                </td>
                <td class="p-3 text-gray-500 dark:text-gray-400 text-xs">{{ $u->ubicacion ?? '—' }}</td>
                <td class="p-3 text-gray-400 text-xs">{{ $u->created_at ? $u->created_at->format('d M Y') : '—' }}</td>
                <td class="p-3 text-right pr-5">
                    <a href="{{ route('usuarios.edit', $u) }}" class="px-3 py-1 rounded-lg text-xs font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-200 dark:hover:bg-emerald-900/50 transition-colors">
                        <span class="material-symbols-outlined text-sm align-middle">edit</span> Editar
                    </a>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="p-6 text-center text-gray-400 italic">Sin usuarios registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>



@endsection
