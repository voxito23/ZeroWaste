@extends('layouts.admin')
@section('title', 'Horarios de recolección')
@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="page-header"><div><p class="text-xs font-extrabold uppercase tracking-[.18em] text-emerald-600">America/Mexico_City</p><h2>Horarios de recolección</h2><p class="mt-1 text-sm text-slate-500">Controla días, intervalos, capacidad y cierres desde una sola fuente.</p></div></div>
    @if(session('success'))<div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 font-bold text-emerald-800">{{ session('success') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-800"><strong>No fue posible guardar.</strong><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    <form method="POST" action="{{ route('admin.recolecciones.horarios.update') }}" class="glass-card p-6" data-loading-form>@csrf @method('PUT')
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @foreach($days as $number => $name)
            @php($day = $schedules->get($number))
            <fieldset class="rounded-2xl border border-slate-200 p-5 dark:border-emerald-900">
                <div class="flex items-center justify-between"><legend class="font-black dark:text-white">{{ $name }}</legend><label class="flex items-center gap-2 text-sm font-bold"><input type="hidden" name="days[{{ $number }}][active]" value="0"><input type="checkbox" name="days[{{ $number }}][active]" value="1" @checked(old("days.$number.active", $day?->active))> Activo</label></div>
                <div class="mt-4 grid grid-cols-2 gap-3"><label class="text-xs font-bold text-slate-500">Desde<input class="input-premium mt-1" type="time" name="days[{{ $number }}][starts_at]" value="{{ old("days.$number.starts_at", substr($day?->starts_at ?? '10:00',0,5)) }}" required></label><label class="text-xs font-bold text-slate-500">Hasta<input class="input-premium mt-1" type="time" name="days[{{ $number }}][ends_at]" value="{{ old("days.$number.ends_at", substr($day?->ends_at ?? '14:00',0,5)) }}" required></label></div>
                <div class="mt-3 grid grid-cols-2 gap-3"><label class="text-xs font-bold text-slate-500">Intervalo (min)<input class="input-premium mt-1" type="number" min="15" max="240" name="days[{{ $number }}][interval_minutes]" value="{{ old("days.$number.interval_minutes", $day?->interval_minutes ?? 60) }}" required></label><label class="text-xs font-bold text-slate-500">Capacidad<input class="input-premium mt-1" type="number" min="1" max="500" name="days[{{ $number }}][capacity_per_interval]" value="{{ old("days.$number.capacity_per_interval", $day?->capacity_per_interval ?? 10) }}" required></label></div>
            </fieldset>
        @endforeach
        </div>
        <div class="mt-6 flex flex-wrap justify-end gap-3"><button type="button" class="btn-secondary" onclick="document.getElementById('restore-schedule').requestSubmit()">Restaurar valores iniciales</button><button type="submit" class="btn-primary"><span class="material-symbols-outlined">save</span> Guardar cambios</button></div>
    </form>
    <form id="restore-schedule" method="POST" action="{{ route('admin.recolecciones.horarios.restore') }}" class="hidden">@csrf</form>

    <section class="glass-card p-6"><h3 class="text-lg font-black dark:text-white">Excepciones y fechas cerradas</h3>
        <form method="POST" action="{{ route('admin.recolecciones.horarios.excepciones.store') }}" class="mt-5 grid gap-3 md:grid-cols-5">@csrf
            <label class="text-xs font-bold text-slate-500">Fecha<input class="input-premium mt-1" type="date" name="exception_date" required></label>
            <label class="text-xs font-bold text-slate-500">Tipo<select class="input-premium mt-1" name="kind"><option value="closed">Día cerrado</option><option value="holiday">Día festivo</option><option value="blocked">Bloqueo temporal</option><option value="override">Horario especial</option></select></label>
            <label class="text-xs font-bold text-slate-500">Desde<input class="input-premium mt-1" type="time" name="starts_at"></label><label class="text-xs font-bold text-slate-500">Hasta<input class="input-premium mt-1" type="time" name="ends_at"></label>
            <label class="text-xs font-bold text-slate-500">Motivo<input class="input-premium mt-1" name="reason" maxlength="255" required></label>
            <button class="btn-primary md:col-start-5" type="submit">Agregar excepción</button>
        </form>
        <div class="mt-6 overflow-x-auto"><table class="premium-table"><thead><tr><th>Fecha</th><th>Tipo</th><th>Horario</th><th>Motivo</th><th></th></tr></thead><tbody>@forelse($exceptions as $item)<tr><td>{{ \Illuminate\Support\Carbon::parse($item->exception_date)->translatedFormat('j M Y') }}</td><td>{{ $item->kind }}</td><td>{{ $item->starts_at ? substr($item->starts_at,0,5).'–'.substr($item->ends_at,0,5) : 'Cerrado' }}</td><td>{{ $item->reason }}</td><td><form method="POST" action="{{ route('admin.recolecciones.horarios.excepciones.destroy', $item->id) }}">@csrf @method('DELETE')<button class="font-bold text-red-600">Retirar</button></form></td></tr>@empty<tr><td colspan="5" class="py-8 text-center">No hay excepciones activas.</td></tr>@endforelse</tbody></table></div>{{ $exceptions->links() }}
    </section>
</div>
@endsection
