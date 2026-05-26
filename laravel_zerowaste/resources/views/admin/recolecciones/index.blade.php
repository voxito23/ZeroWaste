@extends('layouts.admin')

@section('title', 'Recolecciones a Domicilio')
@section('page_title', 'Gestión de Recolecciones')

@section('content')

@if(session('success'))
<div class="bg-emerald-100 border border-emerald-400 text-emerald-700 px-4 py-3 rounded relative mb-4">
    <span class="block sm:inline">{{ session('success') }}</span>
</div>
@endif

@if($errors->any())
<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
    <ul class="list-disc pl-5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

<div class="flex justify-between items-center mb-6">
    <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Solicitudes de Recolección</h2>
    <div class="flex gap-3">
        <a href="{{ route('admin.recolecciones.reporte') }}" class="btn-primary bg-blue-500 hover:bg-blue-600">
            <span class="material-symbols-outlined">picture_as_pdf</span> Generar Reporte
        </a>
        <button onclick="document.getElementById('modalRecolector').classList.remove('hidden')" class="btn-primary">
            <span class="material-symbols-outlined">person_add</span> Nuevo Recolector
        </button>
    </div>
</div>

<div class="glass-card overflow-x-auto">
    <table class="premium-table w-full">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ciudadano</th>
                <th>Dirección</th>
                <th>Materiales</th>
                <th>Estado</th>
                <th>Recolector</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse($solicitudes as $solicitud)
            <tr>
                <td>#{{ $solicitud->id }}</td>
                <td>
                    <p class="font-bold">{{ $solicitud->ciudadano_nombre }}</p>
                    <p class="text-xs text-gray-500">{{ $solicitud->ciudadano_email }}</p>
                </td>
                <td class="text-sm max-w-xs truncate" title="{{ $solicitud->direccion }}">{{ $solicitud->direccion }}</td>
                <td class="text-sm">{{ $solicitud->materiales ?? 'No especificado' }}</td>
                <td>
                    @if($solicitud->estado == 'pendiente')
                        <span class="badge-sm bg-amber-500/10 text-amber-600">Pendiente</span>
                    @elseif($solicitud->estado == 'completada')
                        <span class="badge-sm bg-emerald-500/10 text-emerald-600">Completada</span>
                    @else
                        <span class="badge-sm bg-gray-500/10 text-gray-600">{{ ucfirst($solicitud->estado) }}</span>
                    @endif
                </td>
                <td>{{ $solicitud->recolector_nombre ?? 'Sin asignar' }}</td>
                <td class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($solicitud->created_at)->format('d M Y, H:i') }}</td>
                <td>
                    @if($solicitud->estado == 'pendiente')
                    <form action="{{ route('admin.recolecciones.completar', $solicitud->id) }}" method="POST" onsubmit="return confirm('¿Marcar como completada?')">
                        @csrf
                        <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-bold text-sm">Completar</button>
                    </form>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-gray-500 py-4">No hay solicitudes de recolección.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
    
    <div class="mt-4 p-4">
        {{ $solicitudes->links() }}
    </div>
</div>

{{-- Modal Registrar Recolector --}}
<div id="modalRecolector" class="fixed inset-0 bg-black/50 hidden flex items-center justify-center z-50">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-2xl p-6 w-full max-w-lg">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold">Registrar Nuevo Recolector</h3>
            <button onclick="document.getElementById('modalRecolector').classList.add('hidden')" class="text-gray-500 hover:text-red-500">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>
        
        <form action="{{ route('admin.recolecciones.recolector.store') }}" method="POST">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-sm font-bold mb-1">Nombre Completo</label>
                    <input type="text" name="nombre" class="input-premium w-full" required minlength="5">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Correo Electrónico</label>
                    <input type="email" name="email" class="input-premium w-full" required>
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Contraseña</label>
                    <input type="password" name="password" class="input-premium w-full" required minlength="6">
                </div>
                <div>
                    <label class="block text-sm font-bold mb-1">Edad</label>
                    <input type="number" name="edad" class="input-premium w-full" required min="18" max="80">
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-bold mb-1">Licencia de Conducir (Folio)</label>
                    <input type="text" name="licencia_conducir" class="input-premium w-full" required>
                </div>
            </div>
            <div class="flex justify-end gap-2 mt-6">
                <button type="button" onclick="document.getElementById('modalRecolector').classList.add('hidden')" class="btn-primary bg-gray-500 hover:bg-gray-600">Cancelar</button>
                <button type="submit" class="btn-primary">Registrar Recolector</button>
            </div>
        </form>
    </div>
</div>

@endsection
