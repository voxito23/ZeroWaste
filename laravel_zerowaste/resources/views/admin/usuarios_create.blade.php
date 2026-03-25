@extends('layouts.admin')

@section('title', 'Nuevo Usuario')
@section('page_title', 'Crear Usuario')

@section('content')
<div class="max-w-2xl bg-white rounded-3xl shadow-lg border border-emerald-100 p-8">
    <h2 class="text-xl font-bold mb-6 text-secondary">Ingresa los datos del ciudadano</h2>
    
    <form action="{{ route('usuarios.store') }}" method="POST" class="flex flex-col gap-5">
        @csrf
        <div>
            <label class="block font-bold mb-2 text-sm text-gray-600">Nombre completo</label>
            <input type="text" name="nombre" required placeholder="Ej. Maria Martinez" class="w-full border border-emerald-200 rounded-lg p-3 outline-none focus:border-primary transition-colors">
        </div>
        
        <div>
            <label class="block font-bold mb-2 text-sm text-gray-600">Email</label>
            <input type="email" name="email" required placeholder="correo@ejemplo.com" class="w-full border border-emerald-200 rounded-lg p-3 outline-none focus:border-primary transition-colors">
        </div>
        
        <div>
            <label class="block font-bold mb-2 text-sm text-gray-600">Contraseña (Obligatoria)</label>
            <input type="password" name="password" required placeholder="••••••••" class="w-full border border-emerald-200 rounded-lg p-3 outline-none focus:border-primary transition-colors">
        </div>
        
        <div class="flex justify-end gap-4 mt-4 pt-4 border-t border-emerald-50">
            <a href="{{ route('usuarios.index') }}" class="px-6 py-3 rounded-xl font-bold text-gray-500 hover:bg-gray-100 transition-colors">Cancelar</a>
            <button type="submit" class="bg-primary hover:bg-emerald-500 text-secondary font-bold py-3 px-8 rounded-xl shadow-lg transition-transform hover:-translate-y-1">Crear Cuenta</button>
        </div>
    </form>
</div>
@endsection
