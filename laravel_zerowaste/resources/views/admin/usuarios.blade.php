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
                b.classList.remove('bg-primary', 'text-secondary', 'shadow-lg', 'ring-2', 'ring-primary/30');
                b.classList.add('bg-gray-100', 'dark:bg-emerald-900/30', 'text-gray-600', 'dark:text-emerald-300');
            });
            btn.classList.remove('bg-gray-100', 'dark:bg-emerald-900/30', 'text-gray-600', 'dark:text-emerald-300');
            btn.classList.add('bg-primary', 'text-secondary', 'shadow-lg', 'ring-2', 'ring-primary/30');
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
@php
    $admins = $usuarios->where('is_admin', true);
    $normales = $usuarios->where('is_admin', false);
    $bloqueados = $usuarios->where('bloqueado', true);
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center shadow-lg shadow-emerald-500/30">
                <span class="material-symbols-outlined text-white text-lg">group</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-emerald-400 font-semibold">Total</p>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white" id="count-all">{{ $usuarios->count() }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-purple-400 to-purple-600 flex items-center justify-center shadow-lg shadow-purple-500/30">
                <span class="material-symbols-outlined text-white text-lg">shield_person</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-emerald-400 font-semibold">Admins</p>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white" id="count-admin">{{ $admins->count() }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-blue-400 to-blue-600 flex items-center justify-center shadow-lg shadow-blue-500/30">
                <span class="material-symbols-outlined text-white text-lg">person</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-emerald-400 font-semibold">Usuarios</p>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white" id="count-user">{{ $normales->count() }}</p>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-forest-card rounded-2xl p-5 border-2 border-emerald-100 dark:border-emerald-800/50 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-red-400 to-red-600 flex items-center justify-center shadow-lg shadow-red-500/30">
                <span class="material-symbols-outlined text-white text-lg">block</span>
            </div>
            <div>
                <p class="text-xs text-gray-500 dark:text-emerald-400 font-semibold">Bloqueados</p>
                <p class="text-2xl font-black text-[#064E3B] dark:text-white">{{ $bloqueados->count() }}</p>
            </div>
        </div>
    </div>
</div>

{{-- Barra de acciones --}}
<div class="bg-white dark:bg-forest-card rounded-2xl border-2 border-emerald-100 dark:border-emerald-800/50 p-4 mb-6 shadow-sm">
    <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
        <div class="flex items-center gap-2 flex-wrap">
            <button data-user-tab="all" class="px-4 py-2 rounded-full text-xs font-bold transition-all bg-primary text-secondary shadow-lg ring-2 ring-primary/30">
                Todos <span class="opacity-70 ml-1">{{ $usuarios->count() }}</span>
            </button>
            <button data-user-tab="admin" class="px-4 py-2 rounded-full text-xs font-bold transition-all bg-gray-100 dark:bg-emerald-900/30 text-gray-600 dark:text-emerald-300">
                <span class="material-symbols-outlined text-[0.8rem] align-middle">shield</span> Administradores <span class="opacity-70 ml-1">{{ $admins->count() }}</span>
            </button>
            <button data-user-tab="user" class="px-4 py-2 rounded-full text-xs font-bold transition-all bg-gray-100 dark:bg-emerald-900/30 text-gray-600 dark:text-emerald-300">
                <span class="material-symbols-outlined text-[0.8rem] align-middle">person</span> Usuarios <span class="opacity-70 ml-1">{{ $normales->count() }}</span>
            </button>
            @if($bloqueados->count() > 0)
            <button data-user-tab="blocked" class="px-4 py-2 rounded-full text-xs font-bold transition-all bg-gray-100 dark:bg-emerald-900/30 text-gray-600 dark:text-emerald-300">
                <span class="material-symbols-outlined text-[0.8rem] align-middle">block</span> Bloqueados <span class="opacity-70 ml-1">{{ $bloqueados->count() }}</span>
            </button>
            @endif
        </div>
        <div class="flex items-center gap-3 w-full sm:w-auto">
            <div class="relative flex-1 sm:flex-initial">
                <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-lg">search</span>
                <input type="text" id="userSearch" placeholder="Buscar usuario..." class="w-full sm:w-64 pl-10 pr-4 py-2.5 rounded-xl bg-gray-50 dark:bg-forest-dark border border-emerald-200 dark:border-emerald-800 focus:ring-2 focus:ring-[#00E096] dark:text-white text-sm font-medium">
            </div>
            <a href="{{ route('usuarios.create') }}" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-2.5 px-5 rounded-xl shadow-lg flex items-center gap-2 transition-all hover:-translate-y-0.5 whitespace-nowrap">
                <span class="material-symbols-outlined text-lg">person_add</span>
                Nuevo
            </a>
        </div>
    </div>
</div>

{{-- Tabla --}}
<div class="bg-white dark:bg-forest-card rounded-[2rem] shadow-lg border border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
    <div class="overflow-x-auto">
    <table id="usersTable" class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-secondary dark:text-emerald-300 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-4">Usuario</th>
                <th class="p-4">Rol</th>
                <th class="p-4">Estado</th>
                <th class="p-4">Ubicación</th>
                <th class="p-4">Título</th>
                <th class="p-4">Registro</th>
                <th class="p-4 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $user)
            @php
                $userRole = $user->is_admin ? 'admin' : 'user';
                if ($user->bloqueado ?? false) $userRole = 'blocked';
            @endphp
            <tr class="border-b border-emerald-50 dark:border-emerald-900/30 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors user-row" data-user-role="{{ $userRole }}">
                <td class="p-4">
                    <div class="flex items-center gap-3">
                        @php
                            $userFoto = $user->foto_perfil ?? 'default.png';
                        @endphp
                        <img src="{{ url('/static/img/perfiles/' . $userFoto) }}" alt="{{ $user->nombre }}"
                             class="w-10 h-10 rounded-full border-2 {{ $user->is_admin ? 'border-purple-400' : 'border-primary' }} object-cover shadow-sm"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%2334D399%22 width=%2240%22 height=%2240%22 rx=%2220%22/><text x=%2250%%22 y=%2254%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2218%22 font-weight=%22bold%22 font-family=%22Inter%22>{{ strtoupper(substr($user->nombre, 0, 1)) }}</text></svg>';">
                        <div>
                            <p class="font-bold text-[#064E3B] dark:text-white">{{ $user->nombre }}</p>
                            <p class="text-xs text-gray-400 dark:text-emerald-500">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="p-4">
                    @if($user->is_admin)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-purple-100 to-purple-50 dark:from-purple-900/40 dark:to-purple-900/20 text-purple-700 dark:text-purple-300 shadow-sm">
                            <span class="material-symbols-outlined text-[0.85rem] align-middle mr-0.5">shield_person</span> Admin
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-gradient-to-r from-emerald-100 to-emerald-50 dark:from-emerald-900/40 dark:to-emerald-900/20 text-emerald-700 dark:text-emerald-300 shadow-sm">
                            <span class="material-symbols-outlined text-[0.85rem] align-middle mr-0.5">person</span> Usuario
                        </span>
                    @endif
                </td>
                <td class="p-4">
                    @if($user->bloqueado ?? false)
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-red-500 shadow-sm shadow-red-500/50"></span>
                            <span class="text-xs font-bold text-red-500">Bloqueado</span>
                        </span>
                    @else
                        <span class="flex items-center gap-1.5">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-sm shadow-emerald-500/50 animate-pulse"></span>
                            <span class="text-xs font-bold text-emerald-500">Activo</span>
                        </span>
                    @endif
                </td>
                <td class="p-4 text-gray-500 dark:text-gray-400">{{ $user->ubicacion ?? 'No especificada' }}</td>
                <td class="p-4">
                    <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-xs font-bold text-emerald-800 dark:text-emerald-300">{{ $user->titulo_perfil ?? 'Ciudadano' }}</span>
                </td>
                <td class="p-4 text-gray-400 text-xs">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</td>
                <td class="p-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        <a href="{{ route('usuarios.edit', $user) }}" class="w-9 h-9 rounded-xl bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 hover:shadow-lg hover:shadow-blue-500/25" title="Editar usuario">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </a>
                        @if($user->email !== 'vichdz@gmail.com' && (!auth()->check() || auth()->id() !== $user->id))
                        <form action="{{ route('usuarios.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este usuario y todos sus datos relacionados?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-9 h-9 rounded-xl bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 hover:shadow-lg hover:shadow-red-500/25" title="Eliminar usuario">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                        @else
                        <span class="w-9 h-9 rounded-xl bg-gray-100 dark:bg-white/5 text-gray-300 dark:text-gray-600 flex items-center justify-center cursor-not-allowed" title="Protegido">
                            <span class="material-symbols-outlined text-[18px]">lock</span>
                        </span>
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
@endsection
