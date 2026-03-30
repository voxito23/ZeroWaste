@extends('layouts.admin')

@section('title', 'Dashboard General')
@section('page_title', 'Resumen General')

@section('content')
<div class="flex justify-end mb-6">
    <a href="{{ route('dashboard.exportar-pdf') }}" target="_blank" class="bg-[#059669] hover:bg-[#065F46] text-white px-6 py-2 rounded-xl font-bold flex items-center gap-2 transition-colors shadow-md">
        <span class="material-symbols-outlined">picture_as_pdf</span>
        Exportar Reporte NLP
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">campaign</span>
            <h3 class="text-sm font-bold">Campañas</h3>
        </div>
        <p class="text-4xl font-black text-[#00E096]">{{ $campaignCount ?? 0 }}</p>
    </div>
    
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">group</span>
            <h3 class="text-sm font-bold">Usuarios</h3>
        </div>
        <p class="text-4xl font-black text-[#00E096]">{{ number_format($totalUsuarios ?? 0) }}</p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">forum</span>
            <h3 class="text-sm font-bold">Posts del Foro</h3>
        </div>
        <p class="text-4xl font-black text-[#00E096]">{{ $totalPosts ?? 0 }}</p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">map</span>
            <h3 class="text-sm font-bold">Puntos de Acopio</h3>
        </div>
        <p class="text-4xl font-black text-[#00E096]">{{ $totalPuntos ?? 0 }}</p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col">
        <div class="flex items-center gap-3 text-gray-500 mb-2">
            <span class="material-symbols-outlined">timeline</span>
            <h3 class="text-sm font-bold">Actividades</h3>
        </div>
        <p class="text-4xl font-black text-[#00E096]">{{ $totalActividades ?? 0 }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mt-8">
    <div class="col-span-1 md:col-span-4 mb-2">
        <h2 class="text-xl font-bold text-[#064E3B] uppercase tracking-wide border-b-2 border-emerald-100 pb-2">Análisis de Sentimiento de la Comunidad (IA)</h2>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col border-l-4 border-l-[#10B981]">
        <h3 class="text-md font-bold text-gray-500 mb-2">Sentimiento Positivo</h3>
        <p class="text-3xl font-black text-[#10B981]">{{ $sentimiento['POS'] ?? 0 }}%</p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col border-l-4 border-l-[#9CA3AF]">
        <h3 class="text-md font-bold text-gray-500 mb-2">Sentimiento Neutro</h3>
        <p class="text-3xl font-black text-[#6B7280]">{{ $sentimiento['NEU'] ?? 0 }}%</p>
    </div>

    <div class="bg-white p-6 rounded-2xl shadow-sm border border-emerald-100 flex flex-col border-l-4 border-l-[#EF4444]">
        <h3 class="text-md font-bold text-gray-500 mb-2">Sentimiento Negativo</h3>
        <p class="text-3xl font-black text-[#EF4444]">{{ $sentimiento['NEG'] ?? 0 }}%</p>
    </div>
</div>

<div class="mt-10 bg-white p-8 rounded-3xl shadow-lg border border-emerald-100">
    <h2 class="text-2xl font-bold mb-6 text-[#064E3B]">Sincronización con PostgreSQL</h2>
    <p class="text-gray-600">Este dashboard lee directamente de la base de datos compartida con Flask y FastAPI. Los conteos de Posts, Puntos y Actividades se actualizan en tiempo real.</p>
</div>
@endsection