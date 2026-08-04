@extends('layouts.admin')
@section('title','Solicitudes de canje')
@section('page_title','Solicitudes de canje')
@section('content')
@php
    $states = [
        'SOLICITADA' => ['label' => 'Solicitada', 'icon' => 'inbox', 'tone' => 'bg-sky-100 text-sky-800'],
        'APROBADA' => ['label' => 'Aprobada', 'icon' => 'task_alt', 'tone' => 'bg-emerald-100 text-emerald-800'],
        'EN_PREPARACION' => ['label' => 'En preparación', 'icon' => 'inventory', 'tone' => 'bg-amber-100 text-amber-800'],
        'LISTA_PARA_ENTREGAR' => ['label' => 'Lista para entregar', 'icon' => 'package_2', 'tone' => 'bg-violet-100 text-violet-800'],
        'ENTREGADA' => ['label' => 'Entregada', 'icon' => 'verified', 'tone' => 'bg-emerald-100 text-emerald-800'],
        'RECHAZADA' => ['label' => 'Rechazada', 'icon' => 'cancel', 'tone' => 'bg-red-100 text-red-800'],
        'CANCELADA' => ['label' => 'Cancelada', 'icon' => 'block', 'tone' => 'bg-slate-200 text-slate-700'],
    ];
    $finalStates = ['ENTREGADA', 'RECHAZADA', 'CANCELADA'];
@endphp
<div class="space-y-5">
    <section class="overflow-hidden rounded-[2rem] bg-gradient-to-r from-emerald-950 to-emerald-800 p-6 text-white shadow-xl">
        <div class="flex items-center gap-4"><span class="material-symbols-outlined flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 text-3xl">inventory_2</span><div><p class="text-xs font-black uppercase tracking-[.2em] text-emerald-300">Tienda de recompensas</p><h2 class="mt-1 text-2xl font-black">Seguimiento de solicitudes</h2><p class="mt-1 text-sm text-emerald-100">Actualiza cada etapa con claridad. Los estados finales quedan protegidos.</p></div></div>
    </section>
    @if(session('success'))<div role="status" class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-800"><span class="material-symbols-outlined">check_circle</span>{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-red-800"><span class="material-symbols-outlined mt-0.5">error</span><div><p class="font-black">No fue posible actualizar el canje.</p><ul class="mt-1 list-disc pl-5 text-sm">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div></div>@endif
    <div class="glass-card overflow-x-auto p-5">
        <table class="premium-table">
            <thead><tr><th>Usuario</th><th>Recompensa</th><th>Puntos</th><th>Fecha</th><th class="min-w-[360px]">Estado</th></tr></thead>
            <tbody>
            @forelse($rows as $row)
                @php $current = $states[$row->estado] ?? ['label' => ucfirst(strtolower(str_replace('_', ' ', $row->estado))), 'icon' => 'info', 'tone' => 'bg-slate-100 text-slate-700']; $isFinal = in_array($row->estado, $finalStates, true); @endphp
                <tr>
                    <td><span class="font-black text-slate-900 dark:text-white">{{ $row->usuario }}</span></td>
                    <td><span class="font-bold">{{ $row->recompensa }}</span><span class="ml-2 rounded-full bg-slate-100 px-2 py-1 text-xs font-black text-slate-600">× {{ $row->cantidad }}</span></td>
                    <td><span class="inline-flex items-center gap-1 font-black text-emerald-700"><span class="material-symbols-outlined text-lg">toll</span>{{ number_format($row->puntos_utilizados) }}</span></td>
                    <td class="whitespace-nowrap text-sm">{{ \Illuminate\Support\Carbon::parse($row->created_at)->timezone('America/Mexico_City')->translatedFormat('j M Y, g:i a') }}</td>
                    <td>
                        @if($isFinal)
                            <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3">
                                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1.5 text-xs font-black {{ $current['tone'] }}"><span class="material-symbols-outlined text-base">{{ $current['icon'] }}</span>{{ $current['label'] }}</span>
                                <span class="text-xs font-bold text-slate-500">Estado final</span>
                            </div>
                        @else
                            <form method="POST" action="{{ route('impacto.canjes.update',$row->id) }}" class="flex items-center gap-2" data-redemption-form>
                                @csrf @method('PUT')
                                <label class="relative min-w-0 flex-1">
                                    <span class="material-symbols-outlined pointer-events-none absolute left-3 top-3.5 z-10 text-xl text-emerald-700" data-status-icon>{{ $current['icon'] }}</span>
                                    <select name="estado" class="input-premium cursor-pointer pl-11 pr-10 font-bold" data-status-select aria-label="Estado del canje de {{ $row->usuario }}">
                                        @foreach($states as $value => $meta)<option value="{{ $value }}" data-icon="{{ $meta['icon'] }}" @selected($row->estado === $value)>{{ $meta['label'] }}</option>@endforeach
                                    </select>
                                </label>
                                <button class="btn-primary min-h-12 shrink-0" type="submit"><span class="material-symbols-outlined text-lg">save</span>Guardar</button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-14 text-center"><span class="material-symbols-outlined text-5xl text-slate-300">redeem</span><h3 class="mt-2 font-black text-slate-800 dark:text-white">No hay solicitudes de canje</h3><p class="mt-1 text-sm text-slate-500">Las nuevas solicitudes aparecerán aquí.</p></td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-5">{{ $rows->links() }}</div>
    </div>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-status-select]').forEach(select => {
    const icon = select.closest('label')?.querySelector('[data-status-icon]');
    const sync = () => { if (icon) icon.textContent = select.selectedOptions[0]?.dataset.icon || 'info'; };
    select.addEventListener('change', sync);
    sync();
});
document.querySelectorAll('[data-redemption-form]').forEach(form => form.addEventListener('submit', () => {
    const button = form.querySelector('button[type="submit"]');
    if (button) { button.disabled = true; button.innerHTML = '<span class="material-symbols-outlined animate-spin text-lg">progress_activity</span>Guardando'; }
}));
</script>
@endpush
