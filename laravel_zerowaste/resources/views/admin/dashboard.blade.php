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

        // Gráfica de usuarios por día (Pandas)
        // La gráfica ahora es generada por el backend en Python (FastAPI).

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
    });
</script>
@endpush

@section('content')

{{-- Tarjetas de métricas --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-5 mb-8">
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-emerald-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-emerald-600">group</span>
            </div>
            <span class="text-sm text-gray-500 font-semibold">Usuarios</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-emerald-400">{{ $totalUsuarios }}</p>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-blue-600">forum</span>
            </div>
            <span class="text-sm text-gray-500 font-semibold">Posts</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-emerald-400">{{ $totalPosts }}</p>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-teal-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-teal-600">location_on</span>
            </div>
            <span class="text-sm text-gray-500 font-semibold">Puntos</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-emerald-400">{{ $totalPuntos }}</p>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-amber-600">campaign</span>
            </div>
            <span class="text-sm text-gray-500 font-semibold">Campañas</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-emerald-400">{{ $campaignCount }}</p>
    </div>
</div>

{{-- Segunda fila de tarjetas --}}
<div class="grid grid-cols-2 md:grid-cols-3 gap-5 mb-8">
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-indigo-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-indigo-600">mail</span>
            </div>
            <span class="text-sm text-gray-500 font-semibold">Mensajes</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-emerald-400">{{ $totalMensajes }}</p>
        <a href="{{ route('mensajes.index') }}" class="text-xs text-primary font-bold hover:underline mt-1 inline-block">Ver todos →</a>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-red-600">lock_reset</span>
            </div>
            <span class="text-sm text-gray-500 font-semibold">Recuperación</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-emerald-400">{{ $totalSolicitudes }}</p>
        <a href="{{ route('recuperacion.index') }}" class="text-xs text-primary font-bold hover:underline mt-1 inline-block">Ver todos →</a>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl hover:-translate-y-2 transition-all duration-500 group relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-32 h-32 bg-emerald-50 dark:bg-emerald-900/20 rounded-full blur-3xl group-hover:bg-primary/20 transition-all duration-500"></div>
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-purple-100 flex items-center justify-center">
                <span class="material-symbols-outlined text-purple-600">bolt</span>
            </div>
            <span class="text-sm text-gray-500 font-semibold">Actividades</span>
        </div>
        <p class="text-3xl font-black text-[#064E3B] dark:text-emerald-400">{{ $totalActividades }}</p>
    </div>
</div>

{{-- Gráficas --}}
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl transition-all duration-500">
        <h3 class="font-bold text-lg text-[#064E3B] dark:text-emerald-100 mb-4">Usuarios registrados</h3>
        <div class="relative h-[250px] w-full flex items-center justify-center">
            <img src="http://127.0.0.1:6001/analisis/grafica_usuarios" alt="Gráfica de Usuarios Registrados" class="w-full h-full object-contain">
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-[2rem] p-6 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm hover:shadow-2xl transition-all duration-500 flex flex-col items-center">
        <h3 class="font-bold text-lg text-[#064E3B] mb-6 dark:text-emerald-100">Sentimiento de la Comunidad (NLP)</h3>
        
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

{{-- Usuarios recientes --}}
<div class="bg-white dark:bg-forest-card rounded-[2rem] border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm overflow-hidden mb-8 hover:shadow-2xl transition-all duration-500">
    <div class="p-5 flex items-center justify-between border-b border-emerald-50 dark:border-emerald-800/50">
        <h3 class="font-bold text-lg text-[#064E3B] dark:text-emerald-100">Usuarios Recientes</h3>
        <a href="{{ route('usuarios.index') }}" class="text-sm text-primary font-bold hover:underline">Ver todos →</a>
    </div>
    <table class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-[#064E3B] dark:text-emerald-200 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-3 pl-5">Nombre</th>
                <th class="p-3">Email</th>
                <th class="p-3">Rol</th>
                <th class="p-3">Fecha</th>
            </tr>
        </thead>
        <tbody class="dark:text-emerald-100">
            @forelse ($usuariosRecientes as $u)
            <tr class="border-b border-emerald-50 dark:border-emerald-800/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                <td class="p-3 pl-5 font-bold">{{ $u->nombre }}</td>
                <td class="p-3 text-gray-600 dark:text-gray-400">{{ $u->email }}</td>
                <td class="p-3">
                    @if($u->is_admin)
                        <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                            Admin
                        </span>
                    @else
                        <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                            Usuario
                        </span>
                    @endif
                </td>
                <td class="p-3 text-gray-400 text-xs">{{ $u->created_at ? $u->created_at->format('d M Y') : '30 Mar 2026' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="p-6 text-center text-gray-400 italic">Sin usuarios registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>



@endsection