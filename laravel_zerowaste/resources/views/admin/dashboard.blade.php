@extends('layouts.admin')

@section('title', 'Dashboard General')
@section('page_title', 'Resumen General')

@section('content')
<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">campaign</span>
            <h3 class="text-lg font-bold">Campañas Activas</h3>
        </div>
        <p class="text-4xl font-black text-primary">{{ $campaignCount ?? 0 }}</p>
    </div>
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">recycling</span>
            <h3 class="text-lg font-bold">Materiales Registrados</h3>
        </div>
        <p class="text-4xl font-black text-primary">0</p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">map</span>
            <h3 class="text-lg font-bold">Puntos de Acopio</h3>
        </div>
        <p class="text-4xl font-black text-primary">0</p>
    </div>
</div>

<div class="mt-10 bg-white p-8 rounded-3xl shadow-lg border border-emerald-100">
    <h2 class="text-2xl font-bold mb-6 text-secondary">Actividad Reciente</h2>
    <p class="text-gray-500">Aún no hay actividad registrada en la plataforma.</p>
</div>
@endsection