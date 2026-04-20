@extends('layouts.admin')

@section('title', 'Administrar Campañas')
@section('page_title', 'Campañas')

@section('content')
@if(session('success'))
<div class="mb-4 bg-emerald-50 dark:bg-emerald-900/30 border-l-4 border-emerald-400 text-emerald-800 dark:text-emerald-200 p-4 rounded-xl font-bold text-sm">{{ session('success') }}</div>
@endif

<div class="flex justify-end mb-6">
    <a href="{{ route('campanas.create') }}" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-6 rounded-xl shadow-lg flex items-center gap-2 transition-all hover:-translate-y-1">
        <span class="material-symbols-outlined">add</span>
        Nueva Campaña
    </a>
</div>

<div class="bg-white dark:bg-forest-card rounded-3xl shadow-lg border border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-secondary dark:text-emerald-300 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-4">Nombre</th>
                <th class="p-4">Lugar</th>
                <th class="p-4">Etiqueta</th>
                <th class="p-4">Estado</th>
                <th class="p-4">Link</th>
                <th class="p-4 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($campaigns as $camp)
            <tr class="border-b border-emerald-50 dark:border-emerald-900/30 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                <td class="p-4 font-bold dark:text-white">{{ $camp->nombre }}</td>
                <td class="p-4 text-gray-500 dark:text-gray-400 text-xs">{{ $camp->lugar ?? 'N/A' }}</td>
                <td class="p-4">
                    <span class="px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 rounded-full text-xs font-bold">{{ $camp->tipo_etiqueta ?? 'General' }}</span>
                </td>
                <td class="p-4">
                    @if($camp->activa)
                        <span class="text-green-600 dark:text-green-400 flex items-center gap-1 text-xs font-bold"><span class="material-symbols-outlined text-[1rem]">check_circle</span> Activa</span>
                    @else
                        <span class="text-red-500 dark:text-red-400 flex items-center gap-1 text-xs font-bold"><span class="material-symbols-outlined text-[1rem]">cancel</span> Inactiva</span>
                    @endif
                </td>
                <td class="p-4">
                    @if($camp->link_evento)
                    <a href="{{ $camp->link_evento }}" target="_blank" class="text-primary text-xs font-bold hover:underline">Ver →</a>
                    @else
                    <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                    @endif
                </td>
                <td class="p-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        <a href="{{ route('campanas.edit', $camp) }}" class="w-9 h-9 rounded-xl bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 hover:shadow-lg hover:shadow-blue-500/25" title="Editar campaña">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </a>
                        <form action="{{ route('campanas.destroy', $camp) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar esta campaña?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-9 h-9 rounded-xl bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 hover:shadow-lg hover:shadow-red-500/25" title="Eliminar campaña">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay campañas registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
