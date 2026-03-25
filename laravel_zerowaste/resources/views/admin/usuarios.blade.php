@extends('layouts.admin')

@section('title', 'Usuarios')
@section('page_title', 'Gestión de Usuarios')

@section('content')
<div class="flex justify-end mb-6">
    <a href="{{ route('usuarios.create') }}" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-6 rounded-xl shadow-lg flex items-center gap-2 transition-all hover:-translate-y-1">
        <span class="material-symbols-outlined">person_add</span>
        Nuevo Usuario
    </a>
</div>

<div class="bg-white rounded-3xl shadow-lg border border-emerald-100 overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-emerald-50 text-secondary font-bold">
            <tr>
                <th class="p-4">Nombre</th>
                <th class="p-4">Email</th>
                <th class="p-4">Rol / Título</th>
                <th class="p-4">Ubicación</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($usuarios as $user)
            <tr class="border-b border-emerald-50 hover:bg-emerald-50/50 transition-colors">
                <td class="p-4 font-bold">{{ $user->nombre }}</td>
                <td class="p-4">{{ $user->email }}</td>
                <td class="p-4">
                    <span class="px-3 py-1 bg-emerald-100 rounded-full text-xs font-bold text-emerald-800">{{ $user->titulo_perfil ?? 'Ciudadano' }}</span>
                </td>
                <td class="p-4 text-sm text-gray-500">{{ $user->ubicacion ?? 'No especificada' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="p-8 text-center text-gray-500 italic">No hay usuarios registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
