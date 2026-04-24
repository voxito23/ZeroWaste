@extends('layouts.admin')

@section('title', 'Usuarios')
@section('page_title', 'Gestión de Usuarios')

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function() {
    const tabBtns = document.querySelectorAll('[data-user-tab]');
    const rows = document.querySelectorAll('#usersTable tbody tr[data-user-role]');
    const searchInput = document.getElementById('userSearch');
    const countAll = document.getElementById('count-all');
    const countAdmin = document.getElementById('count-admin');
    const countUser = document.getElementById('count-user');
    
    let currentTab = 'all';

    function filterTable() {
        const query = searchInput ? searchInput.value.toLowerCase() : '';
        rows.forEach(row => {
            const matchesRole = (currentTab === 'all' || row.dataset.userRole === currentTab);
            const matchesSearch = row.textContent.toLowerCase().includes(query);
            row.style.display = (matchesRole && matchesSearch) ? '' : 'none';
        });
    }

    tabBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            tabBtns.forEach(b => {
                b.classList.remove('bg-emerald-500', 'text-white', 'shadow-md');
                b.classList.add('bg-gray-100', 'dark:bg-white/5', 'text-gray-500', 'dark:text-gray-400');
            });
            btn.classList.remove('bg-gray-100', 'dark:bg-white/5', 'text-gray-500', 'dark:text-gray-400');
            btn.classList.add('bg-emerald-500', 'text-white', 'shadow-md');
            currentTab = btn.dataset.userTab;
            filterTable();
        });
    });

    if (searchInput) {
        searchInput.addEventListener('input', filterTable);
    }

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

@section('content')



{{-- Estadísticas rápidas --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    @php $stats=[['Total','group','#10B981',$totalCount,'count-all'],['Admins','shield_person','#8B5CF6',$adminCount,'count-admin'],['Usuarios','person','#3B82F6',$userCount,'count-user'],['Bloqueados','block','#F43F5E',$blockedCount,null]]; @endphp
    @foreach($stats as $s)
    <div class="glass-card p-5">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl flex items-center justify-center" style="background:{{$s[2]}}15">
                <span class="material-symbols-outlined text-lg" style="color:{{$s[2]}}">{{$s[1]}}</span>
            </div>
            <div>
                <p class="text-[11px] text-gray-400 font-bold uppercase tracking-wider">{{$s[0]}}</p>
                <p class="text-xl font-black text-[#064E3B] dark:text-white" @if($s[4]) id="{{$s[4]}}" @endif>{{$s[3]}}</p>
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
            @if($blockedCount > 0)
            <button data-user-tab="blocked" class="px-4 py-2 rounded-lg text-xs font-bold transition-all bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">
                Bloqueados <span class="opacity-60">{{ $blockedCount }}</span>
            </button>
            @endif
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

{{-- Tabla --}}
<div class="glass-card overflow-hidden">
    <div class="overflow-x-auto">
    <table id="usersTable" class="premium-table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Rol</th>
                <th>Estado</th>
                <th>Ubicación</th>
                <th>Título</th>
                <th>Registro</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $user)
            @php
                $userRole = $user->is_admin ? 'admin' : 'user';
                if ($user->bloqueado ?? false) $userRole = 'blocked';
            @endphp
            <tr class="user-row" data-user-role="{{ $userRole }}">
                <td>
                    <div class="flex items-center gap-3">
                        @php $userFoto = $user->foto_perfil ?? 'default.png'; @endphp
                        <img src="{{ url('/static/img/perfiles/' . $userFoto) }}" alt="{{ $user->nombre }}"
                             class="w-9 h-9 rounded-full border-2 {{ $user->is_admin ? 'border-violet-400' : 'border-emerald-400' }} object-cover"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%2334D399%22 width=%2240%22 height=%2240%22 rx=%2220%22/><text x=%2250%%22 y=%2254%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2218%22 font-weight=%22bold%22>{{ strtoupper(substr($user->nombre, 0, 1)) }}</text></svg>';">
                        <div>
                            <p class="font-bold text-[#064E3B] dark:text-white text-sm">{{ $user->nombre }}</p>
                            <p class="text-[11px] text-gray-400">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td>
                    @if($user->is_admin)
                        <span class="badge-sm bg-violet-500/10 text-violet-600 dark:text-violet-400">Admin</span>
                    @else
                        <span class="badge-sm bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">Usuario</span>
                    @endif
                </td>
                <td>
                    @if($user->bloqueado ?? false)
                        <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span><span class="text-xs font-bold text-red-500">Bloqueado</span></span>
                    @else
                        <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="text-xs font-bold text-emerald-500">Activo</span></span>
                    @endif
                </td>
                <td class="text-gray-400 text-xs">{{ $user->ubicacion ?? '—' }}</td>
                <td><span class="badge-sm bg-gray-100 dark:bg-white/5 text-gray-500 dark:text-gray-400">{{ $user->titulo_perfil ?? 'Ciudadano' }}</span></td>
                <td class="text-gray-400 text-xs">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('usuarios.edit', $user) }}" class="w-8 h-8 rounded-lg bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all" title="Editar">
                            <span class="material-symbols-outlined text-[16px]">edit</span>
                        </a>
                        @if($user->email !== 'vichdz@gmail.com' && (!auth()->check() || auth()->id() !== $user->id))
                        <form action="{{ route('usuarios.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este usuario?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all" title="Eliminar">
                                <span class="material-symbols-outlined text-[16px]">delete</span>
                            </button>
                        </form>
                        @else
                        <span class="w-8 h-8 rounded-lg bg-gray-100 dark:bg-white/5 text-gray-300 dark:text-gray-600 flex items-center justify-center cursor-not-allowed"><span class="material-symbols-outlined text-[16px]">lock</span></span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay usuarios registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>

{{-- Paginación Premium --}}
@if($usuarios->hasPages())
<nav class="mt-6 flex items-center justify-between" aria-label="Paginación">
    {{-- Anterior --}}
    @if($usuarios->onFirstPage())
        <span class="flex items-center gap-2 text-gray-300 dark:text-gray-600 text-sm font-semibold cursor-not-allowed select-none">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Anterior
        </span>
    @else
        <a href="{{ $usuarios->previousPageUrl() }}" class="flex items-center gap-2 text-[#064E3B] dark:text-emerald-300 text-sm font-bold hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
            <span class="material-symbols-outlined text-lg">arrow_back</span> Anterior
        </a>
    @endif

    {{-- Números --}}
    <div class="flex items-center gap-1">
        @foreach($usuarios->getUrlRange(1, $usuarios->lastPage()) as $page => $url)
            @if($page == $usuarios->currentPage())
                <span class="w-9 h-9 flex items-center justify-center rounded-lg bg-emerald-500 text-white text-sm font-black shadow-md">{{ $page }}</span>
            @elseif($page == 1 || $page == $usuarios->lastPage() || abs($page - $usuarios->currentPage()) <= 1)
                <a href="{{ $url }}" class="w-9 h-9 flex items-center justify-center rounded-lg text-sm font-semibold text-gray-500 dark:text-gray-400 hover:bg-emerald-50 dark:hover:bg-white/5 transition-colors">{{ $page }}</a>
            @elseif(abs($page - $usuarios->currentPage()) == 2)
                <span class="w-6 text-center text-gray-400 text-sm">…</span>
            @endif
        @endforeach
    </div>

    {{-- Siguiente --}}
    @if($usuarios->hasMorePages())
        <a href="{{ $usuarios->nextPageUrl() }}" class="flex items-center gap-2 text-[#064E3B] dark:text-emerald-300 text-sm font-bold hover:text-emerald-600 dark:hover:text-emerald-400 transition-colors">
            Siguiente <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </a>
    @else
        <span class="flex items-center gap-2 text-gray-300 dark:text-gray-600 text-sm font-semibold cursor-not-allowed select-none">
            Siguiente <span class="material-symbols-outlined text-lg">arrow_forward</span>
        </span>
    @endif
</nav>
@endif

@endsection
