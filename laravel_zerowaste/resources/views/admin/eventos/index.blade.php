@extends('layouts.admin')

@section('title', 'Eventos')
@section('page_title', 'Lista de Eventos')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-xl font-bold text-[#064E3B] dark:text-white">Eventos Activos</h2>
    <a href="{{ route('eventos.create') }}" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-2.5 px-5 rounded-xl shadow-lg flex items-center gap-2 transition-all hover:-translate-y-0.5">
        <span class="material-symbols-outlined text-lg">add</span>
        Nuevo Evento
    </a>
</div>

<div class="bg-white dark:bg-forest-card rounded-[2rem] shadow-lg border-2 border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-secondary dark:text-emerald-300 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-4">Título</th>
                <th class="p-4">Fecha</th>
                <th class="p-4">Ubicación</th>
                <th class="p-4">Link</th>
                <th class="p-4 text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($eventos as $evento)
            <tr class="border-b border-emerald-50 dark:border-emerald-900/30 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                <td class="p-4 font-bold text-[#064E3B] dark:text-white">{{ $evento->titulo }}</td>
                <td class="p-4 text-gray-500 dark:text-gray-400 text-xs">{{ $evento->fecha_inicio }}</td>
                <td class="p-4 text-gray-500 dark:text-gray-400 text-xs">{{ $evento->ubicacion }}</td>
                <td class="p-4">
                    @if($evento->link_unirse)
                    <a href="{{ $evento->link_unirse }}" target="_blank" class="text-primary text-xs font-bold hover:underline">Unirse →</a>
                    @else
                    <span class="text-gray-300 dark:text-gray-600 text-xs">—</span>
                    @endif
                </td>
                <td class="p-4 text-right">
                    <div class="flex items-center justify-end gap-1.5">
                        <a href="{{ route('eventos.edit', $evento) }}" class="w-9 h-9 rounded-xl bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 hover:shadow-lg hover:shadow-blue-500/25" title="Editar evento">
                            <span class="material-symbols-outlined text-[18px]">edit</span>
                        </a>
                        <form action="{{ route('eventos.destroy', $evento) }}" method="POST" class="inline" onsubmit="return confirm('¿Seguro que deseas eliminar este evento?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="w-9 h-9 rounded-xl bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all duration-200 hover:scale-110 hover:shadow-lg hover:shadow-red-500/25" title="Eliminar evento">
                                <span class="material-symbols-outlined text-[18px]">delete</span>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay eventos registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
@endsection
