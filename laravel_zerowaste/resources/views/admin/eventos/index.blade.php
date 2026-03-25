@extends('layouts.admin')

@section('title', 'Eventos')
@section('page_title', 'Lista de Eventos')

@section('content')
<div class="mb-6 flex justify-between items-center">
    <h2 class="text-xl font-bold text-gray-800">Eventos Activos</h2>
    <a href="{{ route('eventos.create') }}" class="px-6 py-2 bg-[#00E096] text-white rounded-xl font-bold hover:bg-[#064E3B] transition-colors shadow-sm">
        + Nuevo Evento
    </a>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-left">
        <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Título</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Fecha</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Ubicación</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Link</th>
                <th class="px-6 py-4 text-xs font-bold text-gray-500 uppercase">Acciones</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($eventos as $evento)
            <tr class="hover:bg-gray-50 transition-colors">
                <td class="px-6 py-4 font-medium text-gray-800">{{ $evento->titulo }}</td>
                <td class="px-6 py-4 text-gray-600">{{ $evento->fecha_inicio }}</td>
                <td class="px-6 py-4 text-gray-600">{{ $evento->ubicacion }}</td>
                <td class="px-6 py-4 text-gray-600">
                    @if($evento->link_unirse)
                    <a href="{{ $evento->link_unirse }}" target="_blank" class="text-blue-500 underline">Unirse</a>
                    @else
                    -
                    @endif
                </td>
                <td class="px-6 py-4 flex gap-2">
                    <a href="{{ route('eventos.edit', $evento) }}" class="text-yellow-500 hover:text-yellow-600 font-bold">Editar</a>
                    <form action="{{ route('eventos.destroy', $evento) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este evento?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-red-500 hover:text-red-600 font-bold">Eliminar</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
