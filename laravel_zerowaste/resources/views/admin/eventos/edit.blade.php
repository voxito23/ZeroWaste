@extends('layouts.admin')

@section('title', 'Editar Evento')
@section('page_title', 'Editar Evento')

@section('content')
<div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 max-w-3xl">
    <form action="{{ route('eventos.update', $evento) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Título del Evento</label>
            <input type="text" name="titulo" value="{{ $evento->titulo }}" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha y Hora de Inicio</label>
                <input type="datetime-local" name="fecha_inicio" value="{{ date('Y-m-d\TH:i', strtotime($evento->fecha_inicio)) }}" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Categoría</label>
                <input type="text" name="categoria" value="{{ $evento->categoria }}" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Ubicación</label>
            <input type="text" name="ubicacion" value="{{ $evento->ubicacion }}" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
            <textarea name="descripcion" rows="4" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>{{ $evento->descripcion }}</textarea>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-bold text-gray-700 mb-2">Enlace de "Unirse" (URL Google Maps)</label>
            <input type="url" name="link_unirse" value="{{ $evento->link_unirse }}" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" placeholder="https://maps.google.com/...">
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
            <button type="submit" class="px-8 py-3 bg-[#064E3B] text-white rounded-xl font-bold hover:bg-[#00E096] transition-colors shadow-lg shadow-[#00E096]/20">
                Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection
