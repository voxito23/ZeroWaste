@extends('layouts.admin')
@section('title','Editar regla de puntos') @section('page_title','Editar regla de puntos')
@section('content')
<div class="mx-auto max-w-4xl">
    <div class="glass-card overflow-hidden p-7 lg:p-10">
        <div class="flex items-center gap-4 border-b border-slate-100 pb-6 dark:border-emerald-900">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-700 text-white shadow-lg"><span class="material-symbols-outlined text-3xl">tune</span></div>
            <div><p class="text-xs font-extrabold uppercase tracking-[.18em] text-emerald-600">Motor de impacto</p><h2 class="text-3xl font-black dark:text-white">Editar regla</h2><p class="mt-1 text-sm text-slate-500">El backend seguirá siendo la autoridad del monto otorgado.</p></div>
        </div>
        <div class="mt-7 rounded-2xl border border-emerald-100 bg-emerald-50 p-5 dark:border-emerald-900 dark:bg-emerald-950/30"><span class="text-xs font-black uppercase tracking-widest text-emerald-700">Código protegido</span><code class="mt-2 block text-lg font-black text-emerald-950 dark:text-emerald-100">{{ $rule->codigo }}</code><p class="mt-1 text-xs text-emerald-800 dark:text-emerald-200">El código no se cambia porque identifica movimientos históricos e integraciones.</p></div>
        <form method="POST" action="{{ route('impacto.reglas.update',$rule->id) }}" class="mt-6 space-y-5" data-loading-form>@csrf @method('PUT')
            <label class="block text-sm font-bold dark:text-emerald-100">Descripción *<textarea name="descripcion" class="input-premium mt-2" rows="3" maxlength="255" required>{{ old('descripcion',$rule->descripcion) }}</textarea><span class="mt-1 block text-xs text-slate-400">Explica claramente cuándo se otorgan los puntos.</span></label>
            <div class="grid gap-4 md:grid-cols-2"><label class="text-sm font-bold dark:text-emerald-100">Puntos *<input class="input-premium mt-2" type="number" min="0" max="100000" name="puntos" value="{{ old('puntos',$rule->puntos) }}" required></label><label class="text-sm font-bold dark:text-emerald-100">Límite diario<input class="input-premium mt-2" type="number" min="1" max="1000" name="limite_diario" value="{{ old('limite_diario',$rule->limite_diario) }}"><span class="mt-1 block text-xs text-slate-400">Déjalo vacío cuando la regla no tenga límite.</span></label></div>
            <label class="flex items-center justify-between rounded-2xl border border-slate-200 p-4 dark:border-emerald-900"><span><b class="block dark:text-white">Regla activa</b><small class="text-slate-500">Permite que FastAPI la aplique en operaciones nuevas.</small></span><span><input type="hidden" name="activa" value="0"><input type="checkbox" name="activa" value="1" class="h-5 w-5 accent-emerald-600" @checked((bool)old('activa',$rule->activa))></span></label>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6 dark:border-emerald-900"><a href="{{ route('impacto.reglas') }}" class="btn-secondary"><span class="material-symbols-outlined text-lg">arrow_back</span> Cancelar</a><button type="submit" class="btn-primary"><span class="material-symbols-outlined text-lg">save</span> Guardar regla</button></div>
        </form>
    </div>
</div>
@endsection
@push('scripts')<script>document.querySelector('[data-loading-form]')?.addEventListener('submit',function(){const button=this.querySelector('button[type="submit"]');button.disabled=true;button.innerHTML='<span class="animate-spin">◌</span> Guardando…';});</script>@endpush
