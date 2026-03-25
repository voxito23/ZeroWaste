@extends('layouts.admin')

@section('title', 'Agregar Punto de Reciclaje')
@section('page_title', 'Agregar Módulo de Mapa')

@section('content')
<div class="bg-white rounded-2xl p-8 shadow-sm border border-gray-100 max-w-3xl">
    <form action="{{ route('mapa.store') }}" method="POST">
        @csrf
        
        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Nombre del Punto</label>
            <input type="text" name="nombre" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Dirección</label>
            <input type="text" name="direccion" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
        </div>

        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Latitud</label>
                <input type="number" step="any" name="latitud" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Longitud</label>
                <input type="number" step="any" name="longitud" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-bold text-gray-700 mb-2">Tipo</label>
            <input type="text" name="tipo" placeholder="Ej. Centro Principal, Contenedor Público" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors" required>
        </div>

        <div class="mb-8">
            <label class="block text-sm font-bold text-gray-700 mb-2">Materiales Aceptados</label>
            <textarea name="materiales" rows="3" class="w-full px-4 py-3 rounded-xl bg-gray-50 border border-gray-200 focus:ring-2 focus:ring-[#00E096] focus:border-transparent transition-colors"></textarea>
        </div>

        <div class="flex justify-end pt-4 border-t border-gray-100">
            <button type="submit" class="px-8 py-3 bg-[#064E3B] text-white rounded-xl font-bold hover:bg-[#00E096] transition-colors shadow-lg shadow-[#00E096]/20">
                Guardar Punto
            </button>
        </div>
    </form>
</div>
@endsection
