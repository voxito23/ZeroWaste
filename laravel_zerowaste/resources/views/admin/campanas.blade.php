@extends('layouts.admin')

@section('title', 'Administrar Campañas')
@section('page_title', 'Campañas')

@section('content')


<div class="page-header">
    <h2>Campañas</h2>
    <a href="{{ route('campanas.create') }}" class="btn-primary">
        <span class="material-symbols-outlined text-lg">add_circle</span> Nueva Campaña
    </a>
</div>

<div class="glass-card overflow-hidden">
    <table class="premium-table">
        <thead>
            <tr><th>Nombre</th><th>Lugar</th><th>Etiqueta</th><th>Estado</th><th>Link</th><th class="text-right">Acciones</th></tr>
        </thead>
        <tbody>
            @forelse ($campaigns as $camp)
            <tr>
                <td class="font-bold text-sm text-[#064E3B] dark:text-white">{{ $camp->nombre }}</td>
                <td class="text-gray-400 text-xs">{{ $camp->lugar ?? '—' }}</td>
                <td><span class="badge-sm bg-amber-500/10 text-amber-600 dark:text-amber-400">{{ $camp->tipo_etiqueta ?? 'General' }}</span></td>
                <td>
                    @if($camp->activa)
                        <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span><span class="text-xs font-bold text-emerald-500">Activa</span></span>
                    @else
                        <span class="flex items-center gap-1.5"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span><span class="text-xs font-bold text-red-500">Inactiva</span></span>
                    @endif
                </td>
                <td>@if($camp->link_evento)<a href="{{ $camp->link_evento }}" target="_blank" class="text-emerald-500 text-xs font-bold hover:underline">Ver →</a>@else<span class="text-gray-300 text-xs">—</span>@endif</td>
                <td class="text-right">
                    <div class="flex items-center justify-end gap-1">
                        <a href="{{ route('campanas.edit', $camp) }}" class="w-8 h-8 rounded-lg bg-blue-500/10 hover:bg-blue-500 text-blue-500 hover:text-white flex items-center justify-center transition-all"><span class="material-symbols-outlined text-[16px]">edit</span></a>
                        <form action="{{ route('campanas.destroy', $camp) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="w-8 h-8 rounded-lg bg-red-500/10 hover:bg-red-500 text-red-500 hover:text-white flex items-center justify-center transition-all"><span class="material-symbols-outlined text-[16px]">delete</span></button>
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
