@extends('layouts.admin')
@section('title','Movimientos de puntos')
@section('page_title','Movimientos de puntos')
@section('content')
@php
    $activeFilters = collect(['q','tipo','referencia','desde','hasta'])->filter(fn ($field) => request()->filled($field))->count();
@endphp
<div class="space-y-5">
    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-br from-emerald-950 via-emerald-900 to-emerald-700 p-7 text-white shadow-xl shadow-emerald-950/10">
        <div class="flex flex-wrap items-end justify-between gap-5">
            <div class="max-w-2xl"><p class="text-xs font-black uppercase tracking-[.22em] text-emerald-300">Libro mayor de impacto</p><h2 class="mt-2 text-3xl font-black">Movimientos claros y auditables</h2><p class="mt-3 leading-6 text-emerald-100">Consulta saldos, referencias y responsables sin alterar el historial de puntos.</p></div>
            <a href="{{ route('impacto.movimientos.export', request()->query()) }}" class="inline-flex min-h-12 items-center gap-2 rounded-2xl bg-white px-5 font-black text-emerald-900 shadow-lg transition hover:-translate-y-0.5"><span class="material-symbols-outlined">download</span>Exportar resultados</a>
        </div>
    </section>

    @if($errors->any())<div role="alert" class="rounded-2xl border border-red-200 bg-red-50 p-4 font-bold text-red-800">{{ $errors->first() }}</div>@endif

    <form method="GET" class="glass-card overflow-hidden p-0">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-emerald-900">
            <div><h3 class="flex items-center gap-2 font-black text-slate-900 dark:text-white"><span class="material-symbols-outlined text-emerald-600">filter_alt</span>Filtros de búsqueda @if($activeFilters)<span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs text-emerald-800">{{ $activeFilters }} activos</span>@endif</h3><p class="mt-1 text-xs text-slate-500">Combina los campos para encontrar una operación específica.</p></div>
            @if($activeFilters)<a href="{{ route('impacto.movimientos') }}" class="inline-flex items-center gap-1 text-sm font-bold text-slate-500 transition hover:text-red-600"><span class="material-symbols-outlined text-lg">filter_alt_off</span>Limpiar filtros</a>@endif
        </div>
        <div class="grid gap-4 p-5 md:grid-cols-2 xl:grid-cols-6">
            <label class="text-xs font-black uppercase tracking-wide text-slate-500 xl:col-span-2"><span class="mb-1.5 block">Persona</span><span class="relative block"><span class="material-symbols-outlined absolute left-3 top-3 text-lg text-slate-400">person_search</span><input class="input-premium pl-10" name="q" maxlength="150" value="{{ request('q') }}" placeholder="Nombre o correo"></span></label>
            <label class="text-xs font-black uppercase tracking-wide text-slate-500"><span class="mb-1.5 block">Tipo</span><select class="input-premium" name="tipo"><option value="">Todos</option>@foreach(['GANADO'=>'Ganado','CANJE'=>'Canje','DEVOLUCIÓN'=>'Devolución','AJUSTE'=>'Ajuste'] as $value=>$label)<option value="{{ $value }}" @selected(request('tipo')===$value)>{{ $label }}</option>@endforeach</select></label>
            <label class="text-xs font-black uppercase tracking-wide text-slate-500"><span class="mb-1.5 block">Referencia</span><input class="input-premium" name="referencia" maxlength="100" value="{{ request('referencia') }}" placeholder="Tipo o folio"></label>
            <label class="text-xs font-black uppercase tracking-wide text-slate-500"><span class="mb-1.5 block">Desde</span><input class="input-premium" type="date" name="desde" value="{{ request('desde') }}"></label>
            <label class="text-xs font-black uppercase tracking-wide text-slate-500"><span class="mb-1.5 block">Hasta</span><input class="input-premium" type="date" name="hasta" value="{{ request('hasta') }}"></label>
        </div>
        <div class="flex justify-end border-t border-slate-100 px-5 py-4 dark:border-emerald-900"><button class="btn-primary min-w-40 justify-center"><span class="material-symbols-outlined text-lg">search</span>Aplicar filtros</button></div>
    </form>

    <div class="glass-card overflow-hidden p-0">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 p-5 dark:border-emerald-900"><div><h3 class="font-black text-slate-900 dark:text-white">Historial encontrado</h3><p class="mt-1 text-xs text-slate-500">{{ $rows->total() }} {{ $rows->total() === 1 ? 'movimiento' : 'movimientos' }} · horario de Querétaro</p></div><span class="rounded-full bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-600">Página {{ $rows->currentPage() }} de {{ $rows->lastPage() }}</span></div>
        <div class="overflow-x-auto"><table class="premium-table"><thead><tr><th>Usuario</th><th>Tipo</th><th>Cantidad</th><th>Saldo anterior</th><th>Saldo nuevo</th><th>Impacto anterior</th><th>Impacto nuevo</th><th>Referencia</th><th>Fecha</th><th>Responsable</th><th>Motivo</th></tr></thead><tbody>@forelse($rows as $row)<tr><td class="font-bold">{{ $row->usuario }}</td><td><span class="rounded-full px-2.5 py-1 text-xs font-black {{ $row->tipo === 'GANADO' ? 'bg-emerald-100 text-emerald-800' : ($row->tipo === 'CANJE' ? 'bg-violet-100 text-violet-800' : ($row->tipo === 'DEVOLUCIÓN' ? 'bg-cyan-100 text-cyan-800' : 'bg-amber-100 text-amber-800')) }}">{{ $row->tipo }}</span></td><td class="font-black {{ $row->cantidad >= 0 ? 'text-emerald-600':'text-red-600' }}">{{ $row->cantidad >= 0 ? '+' : '' }}{{ $row->cantidad }}</td><td>{{ $row->saldo_anterior }}</td><td>{{ $row->saldo_nuevo }}</td><td>{{ $row->impacto_anterior }}</td><td>{{ $row->impacto_nuevo }}</td><td><span class="whitespace-nowrap rounded-lg bg-slate-50 px-2 py-1 font-mono text-xs">{{ trim(($row->referencia_tipo ?? '').' '.($row->referencia_id ?? '')) ?: 'Sin referencia' }}</span></td><td class="whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($row->created_at)->timezone('America/Mexico_City')->translatedFormat('j M Y, g:i a') }}</td><td>{{ $row->responsable ?? 'Sistema' }}</td><td class="min-w-56 leading-6">{{ $row->descripcion }}</td></tr>@empty<tr><td colspan="11" class="py-14 text-center"><span class="material-symbols-outlined text-5xl text-slate-300">manage_search</span><h3 class="mt-2 font-black text-slate-800 dark:text-white">No hay movimientos con estos filtros</h3><p class="mt-1 text-sm text-slate-500">Prueba con un periodo más amplio o limpia la búsqueda.</p></td></tr>@endforelse</tbody></table></div>
        <div class="p-5">{{ $rows->links() }}</div>
    </div>
</div>
@endsection
