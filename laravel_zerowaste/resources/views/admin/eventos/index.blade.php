@extends('layouts.admin')

@section('title', 'Administrar Eventos y Jornadas')
@section('page_title', 'Eventos y Jornadas')

@section('content')

<div class="page-header">
    <h2>Eventos y Jornadas</h2>
    <a href="{{ route('eventos.create') }}" class="btn-primary">
        <span class="material-symbols-outlined text-lg">add_circle</span> Nuevo Evento
    </a>
</div>

<div class="glass-card overflow-hidden">
    <table class="premium-table">
        <thead>
            <tr><th>Título</th><th>Lugar</th><th>Etiqueta</th><th>Link</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
            @forelse ($eventos as $evento)
            <tr>
                <td class="font-bold text-sm text-[#064E3B] dark:text-white">{{ $evento->titulo }}</td>
                <td class="text-gray-400 text-xs">{{ $evento->lugar ?? '—' }}</td>
                <td><span class="badge-sm bg-amber-500/10 text-amber-600 dark:text-amber-400">{{ $evento->tipo_etiqueta ?? 'General' }}</span></td>
                <td>@if($evento->link_evento)<a href="{{ $evento->link_evento }}" target="_blank" class="text-emerald-500 text-xs font-bold hover:underline">Ver →</a>@else<span class="text-gray-300 text-xs">—</span>@endif</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('eventos.edit', $evento) }}" class="w-8 h-8 rounded-lg bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all"><span class="material-symbols-outlined text-[16px]">edit</span></a>
                        <form action="{{ route('eventos.destroy', $evento) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all"><span class="material-symbols-outlined text-[16px]">delete</span></button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay eventos ni jornadas registrados.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
