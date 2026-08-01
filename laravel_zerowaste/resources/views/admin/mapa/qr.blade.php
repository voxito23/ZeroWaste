@extends('layouts.admin')

@section('title', 'Código QR del punto')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <div class="page-header print:hidden">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[.2em] text-emerald-600">Punto de reciclaje</p>
            <h2>Código QR seguro</h2>
        </div>
        <a href="{{ route('mapa.index') }}" class="btn-secondary"><span class="material-symbols-outlined">arrow_back</span> Volver</a>
    </div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-semibold text-emerald-800" role="status">{{ session('success') }}</div>@endif
    @if(session('error'))<div class="rounded-xl border border-red-200 bg-red-50 p-4 font-semibold text-red-800" role="alert">{{ session('error') }}</div>@endif

    <article id="print-card" class="glass-card overflow-hidden bg-white p-7 sm:p-10 dark:bg-[#0F2A20]">
        <div class="grid items-center gap-8 md:grid-cols-[1fr_360px]">
            <div>
                <img src="/static/img/logo.png" alt="ZeroWaste" class="mb-8 h-12 w-auto" onerror="this.hidden=true">
                <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-extrabold uppercase tracking-wider text-emerald-800">Activo</span>
                <h1 class="mt-4 text-3xl font-black text-[#064E3B] dark:text-white">{{ $location->nombre }}</h1>
                <p class="mt-3 text-base text-slate-600 dark:text-emerald-100/70">{{ $location->direccion }}</p>
                <dl class="mt-7 grid gap-4 text-sm sm:grid-cols-2">
                    <div><dt class="font-bold text-slate-500">Identificador público</dt><dd class="mt-1 font-mono font-bold dark:text-white">{{ $qr['public_id'] }}</dd></div>
                    <div><dt class="font-bold text-slate-500">Generado</dt><dd class="mt-1 font-semibold dark:text-white">{{ \Illuminate\Support\Carbon::parse($qr['generated_at'])->timezone('America/Mexico_City')->translatedFormat('j M Y, g:i a') }}</dd></div>
                </dl>
                <p class="mt-8 rounded-2xl bg-emerald-50 p-4 font-bold text-[#064E3B]">Escanea este código con la aplicación ZeroWaste.</p>
            </div>
            <div class="mx-auto aspect-square w-full max-w-[360px] rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
                <img src="{{ route('mapa.qr.download', [$location, 'svg']) }}" alt="Código QR de {{ $location->nombre }}" class="h-full w-full object-contain">
            </div>
        </div>
    </article>

    <div class="flex flex-wrap gap-3 print:hidden">
        <a class="btn-primary" href="{{ route('mapa.qr.download', [$location, 'png']) }}"><span class="material-symbols-outlined">download</span> Descargar PNG</a>
        <a class="btn-secondary" href="{{ route('mapa.qr.download', [$location, 'svg']) }}"><span class="material-symbols-outlined">download</span> Descargar SVG</a>
        <button class="btn-secondary" type="button" onclick="window.print()"><span class="material-symbols-outlined">print</span> Imprimir</button>
        <form method="POST" action="{{ route('mapa.qr.regenerate', $location) }}" data-confirm="¿Regenerar el código? El anterior dejará de funcionar.">@csrf<button class="btn-secondary" type="submit">Regenerar</button></form>
        <form method="POST" action="{{ route('mapa.qr.revoke', $location) }}" data-confirm="¿Revocar el código QR?">@csrf<button class="rounded-xl border border-red-200 px-4 py-2 text-sm font-bold text-red-700" type="submit">Revocar</button></form>
    </div>

    <section class="glass-card p-6 print:hidden">
        <h3 class="font-black dark:text-white">Historial del código QR</h3>
        <div class="mt-4 overflow-x-auto"><table class="premium-table"><thead><tr><th>ID público</th><th>Versión</th><th>Estado</th><th>Generado</th><th>Revocado</th></tr></thead><tbody>
        @forelse($history as $item)<tr><td class="font-mono">{{ $item['public_id'] }}</td><td>{{ $item['version'] }}</td><td>{{ $item['active'] ? 'Activo' : 'Revocado' }}</td><td>{{ \Illuminate\Support\Carbon::parse($item['generated_at'])->timezone('America/Mexico_City')->translatedFormat('j M Y, g:i a') }}</td><td>{{ $item['revoked_at'] ? \Illuminate\Support\Carbon::parse($item['revoked_at'])->timezone('America/Mexico_City')->translatedFormat('j M Y, g:i a') : '—' }}</td></tr>@empty<tr><td colspan="5" class="py-8 text-center">Sin historial.</td></tr>@endforelse
        </tbody></table></div>
    </section>
</div>
@endsection

@push('scripts')
<style>
@media print { @page { size: A4; margin: 18mm; } body { background:#fff!important; } #sidebar, header, .print\:hidden { display:none!important; } main { margin:0!important; padding:0!important; } #print-card { border:0; box-shadow:none; color:#111; } }
</style>
<script>
document.querySelectorAll('[data-confirm]').forEach((form) => form.addEventListener('submit', async (event) => {
    if (form.dataset.approved) return;
    event.preventDefault();
    const result = await Swal.fire({ title: form.dataset.confirm, icon: 'warning', showCancelButton: true, confirmButtonText: 'Confirmar', cancelButtonText: 'Cancelar', confirmButtonColor: '#059669' });
    if (result.isConfirmed) { form.dataset.approved = '1'; form.requestSubmit(); }
}));
</script>
@endpush
