@extends('layouts.admin')

@section('title', 'Recuperación de Contraseñas')
@section('page_title', 'Solicitudes de Recuperación')

@section('content')


<div class="page-header"><h2>Solicitudes de Recuperación</h2></div>

<div class="glass-card overflow-hidden">
    <table class="premium-table">
        <thead>
            <tr><th>Email</th><th>Fecha</th><th>Estado</th><th>Notas</th><th>Acción</th></tr>
        </thead>
        <tbody>
            @forelse ($solicitudes as $sol)
            <tr>
                <td class="font-bold text-sm text-[#064E3B] dark:text-white">{{ $sol->email }}</td>
                <td class="text-gray-400 text-xs">{{ $sol->created_at ? $sol->created_at->format('d M Y H:i') : '' }}</td>
                <td>
                    @if($sol->estado === 'pendiente')
                        <span class="badge-sm bg-amber-500/10 text-amber-600 dark:text-amber-400">Pendiente</span>
                    @else
                        <span class="badge-sm bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">{{ ucfirst($sol->estado) }}</span>
                    @endif
                </td>
                <td class="text-gray-400 text-xs max-w-[200px]">{{ $sol->notas ?? '—' }}</td>
                <td>
                    @if($sol->estado === 'pendiente')
                    <form action="{{ route('recuperacion.update', $sol->id) }}" method="POST" class="flex gap-2 items-center">
                        @csrf @method('PUT')
                        <input type="text" name="notas" placeholder="Nota..." class="input-premium text-xs w-28 py-1.5">
                        <input type="hidden" name="estado" value="atendido">
                        <button type="submit" class="btn-primary text-xs py-1.5 px-3">Atender</button>
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
