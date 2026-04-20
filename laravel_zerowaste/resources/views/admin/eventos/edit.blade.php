@extends('layouts.admin')

@section('title', 'Editar Evento')
@section('page_title', 'Editar Evento')

@section('content')
<div class="bg-white dark:bg-forest-card rounded-[2rem] p-8 shadow-lg border-2 border-emerald-100 dark:border-emerald-800/50 max-w-3xl">
    <form action="{{ route('eventos.update', $evento) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')
        
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-emerald-400 mb-2">Título del Evento</label>
            <input type="text" name="titulo" value="{{ $evento->titulo }}" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-forest-dark border-2 border-emerald-200 dark:border-emerald-800 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" required>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-emerald-400 mb-2">Fecha y Hora de Inicio</label>
                <input type="datetime-local" name="fecha_inicio" value="{{ date('Y-m-d\TH:i', strtotime($evento->fecha_inicio)) }}" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-forest-dark border-2 border-emerald-200 dark:border-emerald-800 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" required>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-emerald-400 mb-2">Categoría</label>
                <input type="text" name="categoria" value="{{ $evento->categoria }}" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-forest-dark border-2 border-emerald-200 dark:border-emerald-800 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all">
            </div>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-emerald-400 mb-2">Ubicación</label>
            <input type="text" name="ubicacion" value="{{ $evento->ubicacion }}" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-forest-dark border-2 border-emerald-200 dark:border-emerald-800 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" required>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-emerald-400 mb-2">Descripción</label>
            <textarea name="descripcion" rows="4" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-forest-dark border-2 border-emerald-200 dark:border-emerald-800 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all resize-none" required>{{ $evento->descripcion }}</textarea>
        </div>

        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-emerald-400 mb-2">Enlace de "Unirse" (URL Google Maps)</label>
            <input type="url" name="link_unirse" value="{{ $evento->link_unirse }}" class="w-full px-4 py-3 rounded-xl bg-white dark:bg-forest-dark border-2 border-emerald-200 dark:border-emerald-800 dark:text-white focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none transition-all" placeholder="https://maps.google.com/...">
        </div>

        <div class="flex justify-end gap-3 pt-4 border-t border-emerald-100 dark:border-emerald-800/50">
            <a href="{{ route('eventos.index') }}" class="px-6 py-3 rounded-xl text-gray-500 dark:text-gray-400 font-bold hover:bg-gray-100 dark:hover:bg-white/5 transition-colors">Cancelar</a>
            <button type="submit" class="px-8 py-3 bg-primary hover:bg-emerald-500 text-secondary font-black rounded-xl shadow-lg transition-all hover:-translate-y-0.5 flex items-center gap-2">
                <span class="material-symbols-outlined text-lg">save</span> Guardar Cambios
            </button>
        </div>
    </form>
</div>
@endsection
