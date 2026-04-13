@extends('layouts.admin')

@section('title', 'Usuarios')
@section('page_title', 'Gestión de Usuarios')

@section('content')
@if(session('success'))
<div class="mb-4 bg-emerald-50 dark:bg-emerald-900/30 border-l-4 border-emerald-400 text-emerald-800 dark:text-emerald-200 p-4 rounded-xl font-bold text-sm">{{ session('success') }}</div>
@endif

<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <div class="bg-emerald-100 dark:bg-emerald-900/30 px-5 py-3 rounded-2xl border-2 border-emerald-200 dark:border-emerald-800/50">
            <span class="text-sm text-gray-500 dark:text-emerald-400 font-semibold">Total Registrados</span>
            <p class="text-2xl font-black text-[#064E3B] dark:text-white">{{ $usuarios->count() }}</p>
        </div>
    </div>
    <a href="{{ route('usuarios.create') }}" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-6 rounded-xl shadow-lg flex items-center gap-2 transition-all hover:-translate-y-1">
        <span class="material-symbols-outlined">person_add</span>
        Nuevo Usuario
    </a>
</div>

<div class="bg-white dark:bg-forest-card rounded-3xl shadow-lg border border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-secondary dark:text-emerald-300 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-4">Usuario</th>
                <th class="p-4">Rol</th>
                <th class="p-4">Ubicación</th>
                <th class="p-4">Título</th>
                <th class="p-4 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $user)
            <tr class="border-b border-emerald-50 dark:border-emerald-900/30 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                <td class="p-4">
                    <div class="flex items-center gap-3">
                        @php
                            $userFoto = $user->foto_perfil ?? 'default.png';
                        @endphp
                        <img src="{{ url('/static/img/perfiles/' . $userFoto) }}" alt="{{ $user->nombre }}"
                             class="w-10 h-10 rounded-full border-2 border-primary object-cover shadow-sm"
                             onerror="this.onerror=null; this.src='data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 40 40%22><rect fill=%22%2334D399%22 width=%2240%22 height=%2240%22 rx=%2220%22/><text x=%2250%%22 y=%2254%%22 text-anchor=%22middle%22 fill=%22%23064E3B%22 font-size=%2218%22 font-weight=%22bold%22 font-family=%22Inter%22>{{ strtoupper(substr($user->nombre, 0, 1)) }}</text></svg>';">
                        <div>
                            <p class="font-bold text-[#064E3B] dark:text-white">{{ $user->nombre }}</p>
                            <p class="text-xs text-gray-400 dark:text-emerald-500">{{ $user->email }}</p>
                        </div>
                    </div>
                </td>
                <td class="p-4">
                    @if($user->is_admin)
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300">
                            <span class="material-symbols-outlined text-[0.85rem] align-middle mr-0.5">shield_person</span> Admin
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-300">
                            <span class="material-symbols-outlined text-[0.85rem] align-middle mr-0.5">person</span> Usuario
                        </span>
                    @endif
                </td>
                <td class="p-4 text-gray-500 dark:text-gray-400">{{ $user->ubicacion ?? 'No especificada' }}</td>
                <td class="p-4">
                    <span class="px-3 py-1 bg-emerald-100 dark:bg-emerald-900/30 rounded-full text-xs font-bold text-emerald-800 dark:text-emerald-300">{{ $user->titulo_perfil ?? 'Ciudadano' }}</span>
                </td>
                <td class="p-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('usuarios.edit', $user) }}" class="text-xs bg-blue-500 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-blue-600 transition-colors">Editar</a>
                        <form action="{{ route('usuarios.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este usuario?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-xs bg-red-500 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-red-600 transition-colors">Eliminar</button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay usuarios registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
