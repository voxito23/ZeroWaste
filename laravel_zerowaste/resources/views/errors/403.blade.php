@extends('layouts.admin')

@section('title', 'Acceso Denegado')
@section('page_title', '403 - Prohibido')

@section('content')
<div class="flex flex-col items-center justify-center min-h-[60vh] text-center px-4">
    <div class="w-24 h-24 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mb-6 shadow-lg shadow-red-500/20">
        <span class="material-symbols-outlined text-5xl text-red-500">gpp_bad</span>
    </div>
    
    <h1 class="text-4xl md:text-5xl font-black text-secondary dark:text-emerald-100 mb-4 tracking-tight">
        Acceso Exclusivo para Admin
    </h1>
    
    <p class="text-gray-500 dark:text-gray-400 text-lg md:text-xl max-w-xl mx-auto mb-10">
        Lo sentimos, pero tu cuenta no tiene los permisos necesarios para ingresar al panel de control. Esta zona está reservada para el equipo administrativo.
    </p>
    
    <div class="flex gap-4 flex-wrap justify-center">
        <a href="http://localhost:5001/" class="px-8 py-3 bg-primary hover:bg-emerald-400 text-secondary font-bold rounded-2xl shadow-lg shadow-primary/30 transition-all hover:-translate-y-1 flex items-center gap-2">
            <span class="material-symbols-outlined">home</span>
            Ir a la app principal
        </a>
    </div>
</div>
@endsection
