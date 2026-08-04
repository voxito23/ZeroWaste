@extends('layouts.admin')
@section('title','Editar recompensa') @section('page_title','Editar recompensa')
@section('content')
<div class="mx-auto max-w-4xl">
    <div class="glass-card overflow-hidden p-7 lg:p-10">
        <div class="flex items-center gap-4 border-b border-slate-100 pb-6 dark:border-emerald-900">
            <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-400 to-emerald-700 text-white shadow-lg"><span class="material-symbols-outlined text-3xl">redeem</span></div>
            <div><p class="text-xs font-extrabold uppercase tracking-[.18em] text-emerald-600">Catálogo ZeroWaste</p><h2 class="text-3xl font-black dark:text-white">Editar recompensa</h2><p class="mt-1 text-sm text-slate-500">Actualiza su presentación, disponibilidad, costo y existencias.</p></div>
        </div>
        <form method="POST" action="{{ route('impacto.recompensas.update',$reward->id) }}" enctype="multipart/form-data" class="mt-7 space-y-5" data-loading-form>@csrf @method('PUT')
            @include('admin.impacto.partials.reward-fields',['reward'=>$reward])
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-6 dark:border-emerald-900"><a href="{{ route('impacto.recompensas') }}" class="btn-secondary"><span class="material-symbols-outlined text-lg">arrow_back</span> Cancelar</a><button type="submit" class="btn-primary"><span class="material-symbols-outlined text-lg">save</span> Guardar cambios</button></div>
        </form>
    </div>
</div>
@endsection
@push('scripts')<script>document.querySelector('[data-loading-form]')?.addEventListener('submit',function(){const button=this.querySelector('button[type="submit"]');button.disabled=true;button.innerHTML='<span class="animate-spin">◌</span> Guardando…';});</script>@endpush
