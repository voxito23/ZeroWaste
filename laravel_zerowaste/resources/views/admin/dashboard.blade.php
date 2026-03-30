@extends('layouts.admin')

@section('title', 'Dashboard General')
@section('page_title', 'Resumen General')

@section('content')
<div class="flex justify-end mb-6">
    <a href="{{ route('dashboard.exportar-pdf') }}" target="_blank" class="bg-gradient-to-r from-[#00E096] to-[#10B981] text-white px-6 py-2.5 rounded-full font-bold flex items-center gap-2 transition-all shadow-lg hover:-translate-y-1">
        <span class="material-symbols-outlined">picture_as_pdf</span>
        Exportar Reporte Profesional
    </a>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
    <!-- Card Base -->
    <div class="bg-white/10 backdrop-blur-xl p-6 rounded-[2rem] shadow-xl border border-white/20 flex flex-col relative group">
        <div class="flex items-center gap-3 text-emerald-800 mb-2">
            <span class="material-symbols-outlined bg-[#00E096]/20 p-2 rounded-xl text-[#064E3B]">campaign</span>
            <h3 class="text-sm font-bold uppercase text-[#064E3B]">Campañas</h3>
        </div>
        <p class="text-5xl font-black text-[#00E096] drop-shadow-md">{{ $campaignCount ?? 0 }}</p>
    </div>
    
    <div class="bg-white/10 backdrop-blur-xl p-6 rounded-[2rem] shadow-xl border border-white/20 flex flex-col relative group">
        <div class="flex items-center gap-3 text-emerald-800 mb-2">
            <span class="material-symbols-outlined bg-[#00E096]/20 p-2 rounded-xl text-[#064E3B]">group</span>
            <h3 class="text-sm font-bold uppercase text-[#064E3B]">Usuarios</h3>
        </div>
        <p class="text-5xl font-black text-[#00E096] drop-shadow-md">{{ number_format($totalUsuarios ?? 0) }}</p>
    </div>

    <div class="bg-white/10 backdrop-blur-xl p-6 rounded-[2rem] shadow-xl border border-white/20 flex flex-col relative group">
        <div class="flex items-center gap-3 text-emerald-800 mb-2">
            <span class="material-symbols-outlined bg-[#00E096]/20 p-2 rounded-xl text-[#064E3B]">forum</span>
            <h3 class="text-sm font-bold uppercase text-[#064E3B]">Foro</h3>
        </div>
        <p class="text-5xl font-black text-[#00E096] drop-shadow-md">{{ $totalPosts ?? 0 }}</p>
    </div>

    <div class="bg-white/10 backdrop-blur-xl p-6 rounded-[2rem] shadow-xl border border-white/20 flex flex-col relative group">
        <div class="flex items-center gap-3 text-emerald-800 mb-2">
            <span class="material-symbols-outlined bg-[#00E096]/20 p-2 rounded-xl text-[#064E3B]">map</span>
            <h3 class="text-sm font-bold uppercase text-[#064E3B]">Puntos</h3>
        </div>
        <p class="text-5xl font-black text-[#00E096] drop-shadow-md">{{ $totalPuntos ?? 0 }}</p>
    </div>

    <div class="bg-white/10 backdrop-blur-xl p-6 rounded-[2rem] shadow-xl border border-white/20 flex flex-col relative group">
        <div class="flex items-center gap-3 text-emerald-800 mb-2">
            <span class="material-symbols-outlined bg-[#00E096]/20 p-2 rounded-xl text-[#064E3B]">timeline</span>
            <h3 class="text-sm font-bold uppercase text-[#064E3B]">Eventos</h3>
        </div>
        <p class="text-5xl font-black text-[#00E096] drop-shadow-md">{{ $totalActividades ?? 0 }}</p>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-10">
    <div class="bg-white p-8 rounded-[2rem] shadow-xl border-b-8 border-b-[#10B981]">
        <h3 class="text-sm font-black uppercase text-gray-500 mb-2">Postura Positiva</h3>
        <p class="text-6xl font-black text-[#10B981]">{{ $sentimiento['POS'] ?? 0 }}%</p>
    </div>
    <div class="bg-white p-8 rounded-[2rem] shadow-xl border-b-8 border-b-gray-400">
        <h3 class="text-sm font-black uppercase text-gray-500 mb-2">Postura Neutra</h3>
        <p class="text-6xl font-black text-gray-400">{{ $sentimiento['NEU'] ?? 0 }}%</p>
    </div>
    <div class="bg-white p-8 rounded-[2rem] shadow-xl border-b-8 border-b-[#EF4444]">
        <h3 class="text-sm font-black uppercase text-gray-500 mb-2">Postura Negativa</h3>
        <p class="text-6xl font-black text-[#EF4444]">{{ $sentimiento['NEG'] ?? 0 }}%</p>
    </div>
</div>

<div class="mt-8 bg-gradient-to-r from-[#064E3B] to-[#047857] p-8 rounded-[2.5rem] shadow-2xl relative overflow-hidden group">
    <div class="absolute inset-0 bg-no-repeat bg-right opacity-10" style="background-image: url('{{ asset('static/img/logo.png') }}'); background-size: contain;"></div>
    <div class="relative z-10 flex items-center gap-4">
        <div class="p-3 bg-white/10 backdrop-blur-sm rounded-2xl"><span class="material-symbols-outlined text-[#00E096]">hub</span></div>
        <h2 class="text-2xl font-black text-white">Ecosistema Conectado [Laravel ⇌ FastAPI ⇌ Flask] PostgreSQL</h2>
    </div>
</div>
@endsection