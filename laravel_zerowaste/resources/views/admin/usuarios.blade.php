@extends('layouts.admin')

@section('title', 'Usuarios')
@section('page_title', 'Gestión de Usuarios')

@section('content')

{{-- Estadísticas rápidas (clickables) --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php $stats=[
        ['Total','group','#10B981',$totalCount,'all','from-emerald-400 to-emerald-600','ring-emerald-500/40'],
        ['Admins','shield_person','#8B5CF6',$adminCount,'admin','from-purple-400 to-purple-600','ring-purple-500/40'],
        ['Usuarios','person','#3B82F6',$userCount,'user','from-blue-400 to-blue-600','ring-blue-500/40'],
        ['Bloqueados','block','#F43F5E',$blockedCount,'blocked','from-rose-400 to-rose-600','ring-rose-500/40'],
    ]; @endphp
    @foreach($stats as $s)
    <div class="glass-card p-5 cursor-pointer hover:shadow-lg hover:-translate-y-1 transition-all duration-300 stat-card relative overflow-hidden group ring-2 {{ $s[4] === 'all' ? $s[6] : 'ring-transparent' }}" data-stat-tab="{{ $s[4] }}" data-ring-class="{{ $s[6] }}">
        {{-- Faded icon watermark --}}
        <div class="absolute -bottom-3 -right-3 pointer-events-none opacity-[0.15] group-hover:opacity-[0.25] transition-opacity duration-500">
            <span class="material-symbols-outlined" style="font-size: 80px; color: {{$s[2]}};">{{$s[1]}}</span>
        </div>
        <div class="relative z-10">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-br {{$s[5]}} flex items-center justify-center shadow-lg" style="box-shadow: 0 4px 15px {{$s[2]}}40;">
                    <span class="material-symbols-outlined text-white text-lg">{{$s[1]}}</span>
                </div>
                <div>
                    <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">{{$s[0]}}</p>
                    <p class="text-xl font-black text-[#064E3B] dark:text-white">{{$s[3]}}</p>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- Barra de acciones --}}
<div class="glass-card p-4 mb-6">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            <button data-user-tab="all" class="px-4 py-2 rounded-lg text-xs font-bold transition-all bg-emerald-500 text-white shadow-md">
                Todos <span class="opacity-70 ml-1">{{ $totalCount }}</span>
            </button>
            <button data-user-tab="admin" class="px-4 py-2 rounded-lg text-xs font-bold transition-all bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                Admins <span class="opacity-60">{{ $adminCount }}</span>
            </button>
            <button data-user-tab="user" class="px-4 py-2 rounded-lg text-xs font-bold transition-all bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                Usuarios <span class="opacity-60">{{ $userCount }}</span>
            </button>
            <button data-user-tab="blocked" class="px-4 py-2 rounded-lg text-xs font-bold transition-all bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                Bloqueados <span class="opacity-60">{{ $blockedCount }}</span>
            </button>
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1 sm:flex-initial">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input type="text" id="userSearch" placeholder="Buscar..." class="input-premium pl-10 sm:w-56">
            </div>
            <a href="{{ route('usuarios.create') }}" class="btn-primary whitespace-nowrap">
                <span class="material-symbols-outlined text-lg">person_add</span> Nuevo
            </a>
        </div>
    </div>
</div>

{{-- Contenedor de la Tabla (Se actualiza por AJAX) --}}
<div class="glass-card overflow-hidden" id="tableContainer">
    @include('admin.partials.usuarios_table')
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentTab = 'all';
    let currentSearch = '';

    const tableContainer = document.getElementById('tableContainer');
    const searchInput = document.getElementById('userSearch');
    const tabButtons = document.querySelectorAll('[data-user-tab]');
    const statCards = document.querySelectorAll('.stat-card');

    // Función para actualizar tabla
    function fetchTable(url) {
        const tbody = document.getElementById('usersTableBody');
        if(tbody) tbody.style.opacity = '0.3';

        fetch(url, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(response => response.text())
        .then(html => {
            tableContainer.innerHTML = html;
            const newTbody = document.getElementById('usersTableBody');
            if(newTbody) {
                newTbody.style.opacity = '0';
                setTimeout(() => newTbody.style.opacity = '1', 50);
            }
        })
        .catch(err => console.error('Error fetching data', err));
    }

    function updateData() {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', currentTab);
        if (currentSearch) {
            url.searchParams.set('search', currentSearch);
        } else {
            url.searchParams.delete('search');
        }
        url.searchParams.delete('page');
        fetchTable(url.toString());
    }

    // Sincronizar estilos activos en tabs Y stat cards
    function syncActiveStyles(tab) {
        // Tabs
        tabButtons.forEach(b => {
            b.classList.remove('bg-emerald-500', 'text-white', 'shadow-md');
            b.classList.add('bg-gray-100', 'dark:bg-white/5', 'text-gray-500', 'dark:text-gray-400');
        });
        const activeTab = document.querySelector(`[data-user-tab="${tab}"]`);
        if(activeTab) {
            activeTab.classList.remove('bg-gray-100', 'dark:bg-white/5', 'text-gray-500', 'dark:text-gray-400');
            activeTab.classList.add('bg-emerald-500', 'text-white', 'shadow-md');
        }

        // Stat cards
        statCards.forEach(c => {
            c.classList.remove('ring-2', 'shadow-lg', 'ring-emerald-500/40', 'ring-purple-500/40', 'ring-blue-500/40', 'ring-rose-500/40');
            c.classList.add('ring-transparent');
        });
        const activeCard = document.querySelector(`.stat-card[data-stat-tab="${tab}"]`);
        if(activeCard) {
            const ringClass = activeCard.dataset.ringClass;
            activeCard.classList.remove('ring-transparent');
            activeCard.classList.add('ring-2', ringClass, 'shadow-lg');
        }
    }

    // Click en tabs
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            currentTab = this.getAttribute('data-user-tab');
            syncActiveStyles(currentTab);
            updateData();
        });
    });

    // Click en stat cards (vinculan al tab correspondiente)
    statCards.forEach(card => {
        card.addEventListener('click', function() {
            currentTab = this.getAttribute('data-stat-tab');
            syncActiveStyles(currentTab);
            updateData();
        });
    });

    // Búsqueda (con debounce)
    let searchTimeout;
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            currentSearch = this.value.trim();
            updateData();
        }, 300);
    });

    // Paginación por AJAX
    tableContainer.addEventListener('click', function(e) {
        const link = e.target.closest('.ajax-link');
        if (link) {
            e.preventDefault();
            const url = new URL(link.href);
            url.searchParams.set('tab', currentTab);
            if (currentSearch) url.searchParams.set('search', currentSearch);
            fetchTable(url.toString());
        }
    });

    // Stagger animation
    document.querySelectorAll('.user-row').forEach((row, i) => {
        row.style.opacity = '0';
        row.style.transform = 'translateY(8px)';
        setTimeout(() => {
            row.style.transition = 'all 0.25s ease';
            row.style.opacity = '1';
            row.style.transform = 'translateY(0)';
        }, 50 * i);
    });
});
</script>
@endpush
