@php
    $value = function ($name, $fallback = null) use ($reward) {
        return old($name, $reward ? ($reward->{$name} ?? $fallback) : $fallback);
    };
@endphp
<div class="grid gap-5">
    <section class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-4 dark:border-emerald-800 dark:bg-emerald-950/30">
        <div class="mb-4 flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-700 font-black text-white">1</span><div><h4 class="font-black text-slate-900 dark:text-white">Presentación</h4><p class="text-xs text-slate-500">Así aparecerá en la tienda móvil.</p></div></div>
        <div class="grid gap-4">
            <label class="text-sm font-bold dark:text-emerald-100">Nombre de la recompensa *<input class="input-premium mt-1" name="nombre" maxlength="150" value="{{ $value('nombre') }}" placeholder="Ej. Compostera doméstica" required></label>
            <label class="text-sm font-bold dark:text-emerald-100">Descripción *<textarea class="input-premium mt-1" name="descripcion" maxlength="2000" rows="4" placeholder="Explica qué incluye y cómo se entrega." required>{{ $value('descripcion') }}</textarea><span class="mt-1 block text-xs font-medium text-slate-400">Usa un texto breve y claro para facilitar el canje.</span></label>
            <label class="text-sm font-bold">Imagen {{ $reward ? '(opcional)' : '*' }}<span class="mt-1 flex min-h-24 cursor-pointer items-center justify-center rounded-2xl border-2 border-dashed border-emerald-200 bg-white px-4 text-center text-sm font-bold text-emerald-700 transition hover:border-emerald-500 dark:bg-emerald-950/40"><span><span class="material-symbols-outlined block text-3xl">add_photo_alternate</span>Seleccionar imagen</span><input class="sr-only" type="file" accept="image/png,image/jpeg,image/webp" name="imagen_archivo" @required(!$reward)></span></label>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 dark:border-emerald-800 dark:bg-white/5">
        <div class="mb-4 flex items-center gap-3"><span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-700 font-black text-white">2</span><div><h4 class="font-black text-slate-900 dark:text-white">Canje y disponibilidad</h4><p class="text-xs text-slate-500">Controla costo, existencias y límites.</p></div></div>
        <div class="grid grid-cols-2 gap-3 md:grid-cols-4"><label class="text-xs font-bold">Costo en puntos *<input class="input-premium mt-1" type="number" min="1" name="costo_puntos" value="{{ $value('costo_puntos',1) }}" required></label><label class="text-xs font-bold">Existencias *<input class="input-premium mt-1" type="number" min="0" name="stock" value="{{ $value('stock',0) }}" required></label><label class="text-xs font-bold">Orden en tienda *<input class="input-premium mt-1" type="number" min="0" name="orden" value="{{ $value('orden',0) }}" required></label><label class="text-xs font-bold">Límite por persona<input class="input-premium mt-1" type="number" min="1" name="limite_por_usuario" value="{{ $value('limite_por_usuario') }}" placeholder="Sin límite"></label></div>
        <div class="mt-4 grid gap-3 md:grid-cols-2"><label class="text-sm font-bold">Disponible desde<input class="input-premium mt-1" type="datetime-local" name="available_at" value="{{ $value('available_at') ? \Illuminate\Support\Carbon::parse($value('available_at'))->format('Y-m-d\TH:i') : '' }}"></label><label class="flex min-h-14 items-center gap-3 rounded-2xl border border-emerald-100 bg-emerald-50 px-4 font-bold dark:border-emerald-800 dark:bg-emerald-950/30"><input type="hidden" name="activa" value="0"><input class="h-5 w-5 accent-emerald-600" type="checkbox" name="activa" value="1" @checked((bool)$value('activa',true))><span><span class="block text-slate-900 dark:text-white">Recompensa activa</span><small class="font-medium text-slate-500">Visible y canjeable en la tienda</small></span></label></div>
    </section>
</div>
