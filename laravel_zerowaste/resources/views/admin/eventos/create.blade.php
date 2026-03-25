@extends('layouts.admin')

@section('title', 'Agregar Evento')
@section('page_title', 'Crear Nuevo Evento')

@section('content')
<div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 max-w-3xl">
    <form action="{{ route('eventos.store') }}" method="POST">
        @csrf
        
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Título del Evento</label>
            <input type="text" name="titulo" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha y Hora de Inicio</label>
                <input type="datetime-local" name="fecha_inicio" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Categoría</label>
                <input type="text" name="categoria" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors">
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Ubicación</label>
            <input type="text" name="ubicacion" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Descripción</label>
            <textarea name="descripcion" rows="4" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required></textarea>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-bold text-gray-700 mb-2">Enlace de "Unirse" (URL Google Maps)</label>
            <input type="url" name="link_unirse" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" placeholder="https://maps.google.com/...">
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
            <button type="submit" class="px-8 py-3 bg-[#064E3B] text-white rounded-xl font-bold hover:bg-[#00E096] transition-colors shadow-lg shadow-[#00E096]/20">
                Guardar Evento
            </button>
        </div>
    </form>
</div>
@endsection
