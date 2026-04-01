@extends('layouts.admin')

@section('title', 'Recuperación de Contraseñas')
@section('page_title', 'Solicitudes de Recuperación')

@section('content')
@if(session('success'))
<div class="mb-4 bg-emerald-50 border-l-4 border-emerald-400 text-emerald-800 p-4 rounded-xl font-bold text-sm">{{ session('success') }}</div>
@endif

<div class="bg-white dark:bg-forest-card rounded-3xl shadow-lg border border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
    <table class="w-full text-left text-sm">
        <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-[#064E3B] dark:text-emerald-200 font-bold text-xs uppercase tracking-wider">
            <tr>
                <th class="p-4">Email</th>
                <th class="p-4">Fecha</th>
                <th class="p-4">Estado</th>
                <th class="p-4">Notas</th>
                <th class="p-4">Acción</th>
            </tr>
        </thead>
        <tbody class="dark:text-emerald-100">
            @forelse ($solicitudes as $sol)
            <tr class="border-b border-emerald-50 dark:border-emerald-800/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                <td class="p-4 font-bold">{{ $sol->email }}</td>
                <td class="p-4 text-gray-400 dark:text-gray-500 text-xs">{{ $sol->created_at ? $sol->created_at->format('d M Y H:i') : '' }}</td>
                <td class="p-4">
                    <span class="px-3 py-1 rounded-full text-xs font-bold {{ $sol->estado === 'pendiente' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' }}">
                        {{ ucfirst($sol->estado) }}
                    </span>
                </td>
                <td class="p-4 text-gray-600 dark:text-gray-400 text-xs max-w-[200px]">{{ $sol->notas ?? '—' }}</td>
                <td class="p-4">
                    @if($sol->estado === 'pendiente')
                    <form action="{{ route('recuperacion.update', $sol->id) }}" method="POST" class="flex gap-2 items-center">
                        @csrf @method('PUT')
                        <input type="text" name="notas" placeholder="Nota..." class="text-xs border border-emerald-200 rounded-lg px-2 py-1.5 w-32 focus:border-emerald-500 outline-none">
                        <input type="hidden" name="estado" value="atendido">
                        <button type="submit" class="text-xs bg-emerald-500 text-white px-3 py-1.5 rounded-lg font-bold hover:bg-emerald-600 transition-colors">Atender</button>
                    </form>
                    @else
                    <span class="text-xs text-gray-400">Atendido</span>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">No hay solicitudes de recuperación.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
