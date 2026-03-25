@extends('layouts.admin')

@section('title', 'Materiales')
@section('page_title', 'Catálogo de Materiales')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Form to create new -->
    <div class="md:col-span-1 bg-white rounded-3xl shadow-lg border border-emerald-100 p-8">
        <h2 class="text-xl font-bold mb-6">Agregar Material Nuevo</h2>
        <form action="{{ route('materiales.store') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block font-bold mb-2 text-sm text-gray-600">Nombre</label>
                <input type="text" name="nombre" required placeholder="Ej. PET Transparente" class="w-full border border-emerald-200 rounded-lg p-3 outline-none focus:border-primary">
            </div>
            <div>
                <label class="block font-bold mb-2 text-sm text-gray-600">Tipo / Categoría</label>
                <select name="tipo" class="w-full border border-emerald-200 rounded-lg p-3 outline-none focus:border-primary">
                    <option value="Plástico">Plástico</option>
                    <option value="Vidrio">Vidrio</option>
                    <option value="Cartón/Papel">Cartón/Papel</option>
                    <option value="Electrónicos">Electrónicos</option>
                    <option value="Orgánicos">Orgánicos</option>
                </select>
            </div>
            <div>
                <label class="block font-bold mb-2 text-sm text-gray-600">Unidad de Medida</label>
                <input type="text" name="unidades_medida" required placeholder="Ej. kg, piezas" class="w-full border border-emerald-200 rounded-lg p-3 outline-none focus:border-primary">
            </div>
            <div>
                <label class="block font-bold mb-2 text-sm text-gray-600">Valor en Puntos</label>
                <input type="number" name="valor_puntos" min="0" value="0" class="w-full border border-emerald-200 rounded-lg p-3 outline-none focus:border-primary">
            </div>
            
            <button type="submit" class="w-full bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 rounded-lg shadow-md mt-4 transition-transform hover:-translate-y-1">Guardar Material</button>
        </form>
    </div>

    <!-- Table of existing -->
    <div class="md:col-span-2 bg-white rounded-3xl shadow-lg border border-emerald-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-emerald-50 text-secondary">
                <tr>
                    <th class="p-4 font-bold">Material</th>
                    <th class="p-4 font-bold">Categoría</th>
                    <th class="p-4 font-bold">Medida</th>
                    <th class="p-4 font-bold">Puntos</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($materials as $mat)
                <tr class="border-b text-sm border-emerald-50 hover:bg-emerald-50/50 transition-colors">
                    <td class="p-4 font-bold">{{ $mat->nombre }}</td>
                    <td class="p-4">
                        <span class="px-3 py-1 bg-gray-100 rounded-full text-xs font-bold text-gray-600">{{ $mat->tipo }}</span>
                    </td>
                    <td class="p-4">{{ $mat->unidades_medida }}</td>
                    <td class="p-4 font-bold text-emerald-600">{{ $mat->valor_puntos }} pts</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="p-8 text-center text-gray-500 italic">El catálogo de materiales está vacío.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
