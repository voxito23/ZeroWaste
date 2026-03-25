@extends('layouts.admin')

@section('title', 'Crear Campaña')
@section('page_title', 'Crear Nueva Campaña')

@section('content')
<div class="bg-white rounded-3xl shadow-lg border border-emerald-100 p-8 max-w-3xl">
    <form action="{{ route('campanas.store') }}" method="POST" class="flex flex-col gap-6">
        @csrf
        
        <div>
            <label class="block font-bold mb-2">Nombre de la Campaña</label>
            <input type="text" name="nombre" required class="w-full border-2 border-emerald-100 rounded-xl p-3 focus:border-primary outline-none">
        </div>
        
        <div>
            <label class="block font-bold mb-2">Descripción</label>
            <textarea name="descripcion" rows="4" required class="w-full border-2 border-emerald-100 rounded-xl p-3 focus:border-primary outline-none"></textarea>
        </div>
        
        <div>
            <label class="block font-bold mb-2">Recompensa (Puntos)</label>
            <input type="number" min="0" name="recompensa_puntos" value="0" class="w-full border-2 border-emerald-100 rounded-xl p-3 focus:border-primary outline-none">
        </div>

        <div class="flex items-center gap-3 mt-2">
            <input type="checkbox" name="activa" id="activa" checked class="w-5 h-5 text-primary border-emerald-200 rounded focus:ring-primary">
            <label for="activa" class="font-bold text-gray-700">Campaña Activa</label>
        </div>

        <div class="flex justify-end gap-4 mt-6">
            <a href="{{ route('campanas.index') }}" class="py-3 px-6 text-gray-500 font-bold hover:bg-gray-100 rounded-xl">Cancelar</a>
            <button type="submit" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-8 rounded-xl shadow-md transition-transform hover:-translate-y-1">Guardar Campaña</button>
        </div>
    </form>
</div>
@endsection
