@extends('layouts.admin')

@section('title', 'Administrar Campañas')
@section('page_title', 'Campañas Activas')

@section('content')
<div class="flex justify-end mb-6">
    <a href="{{ route('campanas.create') }}" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-6 rounded-xl shadow-lg flex items-center gap-2">
        <span class="material-symbols-outlined">add</span>
        Nueva Campaña
    </a>
</div>

<div class="bg-white rounded-3xl shadow-lg border border-emerald-100 overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-emerald-50 text-secondary font-bold">
            <tr>
                <th class="p-4">Nombre de la Campaña</th>
                <th class="p-4">Recompensa (Puntos)</th>
                <th class="p-4">Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($campaigns as $camp)
            <tr class="border-b border-emerald-50 hover:bg-emerald-50/50 transition-colors">
                <td class="p-4 font-bold">{{ $camp->nombre }}</td>
                <td class="p-4">
                    <span class="px-3 py-1 bg-emerald-100 text-emerald-800 rounded-full text-xs font-bold">+{{ $camp->recompensa_puntos }} pts</span>
                </td>
                <td class="p-4 text-sm font-bold">
                    @if($camp->activa)
                        <span class="text-green-600 flex items-center gap-1"><span class="material-symbols-outlined text-[1rem]">check_circle</span> Activa</span>
                    @else
                        <span class="text-red-500 flex items-center gap-1"><span class="material-symbols-outlined text-[1rem]">cancel</span> Inactiva</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="3" class="p-8 text-center text-gray-500 italic">No hay campañas registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
