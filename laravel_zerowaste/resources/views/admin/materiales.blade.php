@extends('layouts.admin')

@section('title', 'Materiales')
@section('page_title', 'Catálogo de Materiales')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-8">
    <!-- Formulario de registro de material -->
    <div class="md:col-span-1 bg-white dark:bg-forest-card rounded-[2rem] shadow-lg border-2 border-emerald-100 dark:border-emerald-800/50 p-8">
        <h2 class="text-xl font-black text-[#064E3B] dark:text-white mb-6">Agregar Material</h2>
        <form action="{{ route('materiales.store') }}" method="POST" class="flex flex-col gap-4">
            @csrf
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Nombre</label>
                <input type="text" name="nombre" required placeholder="Ej. PET Transparente" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
            </div>
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Categoría</label>
                <select name="tipo" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary">
                    <option value="Plástico">Plástico</option>
                    <option value="Vidrio">Vidrio</option>
                    <option value="Cartón/Papel">Cartón/Papel</option>
                    <option value="Electrónicos">Electrónicos</option>
                    <option value="Orgánicos">Orgánicos</option>
                </select>
            </div>
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Unidad de Medida</label>
                <input type="text" name="unidades_medida" required placeholder="Ej. kg, piezas" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
            </div>
            <div>
                <label class="block font-bold mb-2 text-xs uppercase tracking-wider text-gray-500 dark:text-emerald-400">Valor en Puntos</label>
                <input type="number" name="valor_puntos" min="0" value="0" class="w-full border-2 border-emerald-200 dark:border-emerald-800 dark:bg-forest-dark dark:text-white rounded-xl p-3 outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition">
            </div>
            
            <button type="submit" class="w-full bg-primary hover:bg-emerald-500 text-secondary font-black py-3 rounded-xl shadow-lg mt-4 transition-all hover:-translate-y-1 flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-lg">add_circle</span> Guardar Material
            </button>
        </form>
    </div>

    <!-- Tabla de materiales registrados -->
    <div class="md:col-span-2 bg-white dark:bg-forest-card rounded-[2rem] shadow-lg border-2 border-emerald-100 dark:border-emerald-800/50 overflow-hidden">
        <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-emerald-50 dark:bg-emerald-900/30 text-secondary dark:text-emerald-300 font-bold text-xs uppercase tracking-wider">
                <tr>
                    <th class="p-4">Material</th>
                    <th class="p-4">Categoría</th>
                    <th class="p-4">Medida</th>
                    <th class="p-4">Puntos</th>
                </tr>
            </thead>
            <tbody class="dark:text-emerald-100">
                @forelse ($materials as $mat)
                <tr class="border-b border-emerald-50 dark:border-emerald-800/50 hover:bg-emerald-50/50 dark:hover:bg-emerald-900/20 transition-colors">
                    <td class="p-4 font-bold text-[#064E3B] dark:text-white">{{ $mat->nombre }}</td>
                    <td class="p-4">
                        <span class="px-3 py-1 bg-gray-100 dark:bg-white/5 rounded-full text-xs font-bold text-gray-600 dark:text-gray-300 border border-gray-200 dark:border-white/10">{{ $mat->tipo }}</span>
                    </td>
                    <td class="p-4 text-gray-500 dark:text-gray-400">{{ $mat->unidades_medida }}</td>
                    <td class="p-4 font-black text-emerald-600 dark:text-emerald-400">{{ $mat->valor_puntos }} pts</td>
                </tr>
                @empty
                <tr>
                    <td colspan="4" class="p-8 text-center text-gray-500 dark:text-gray-400 italic">El catálogo de materiales está vacío.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>
</div>
@endsection
