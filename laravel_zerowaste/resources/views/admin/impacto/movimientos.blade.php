@extends('layouts.admin')
@section('title','Movimientos de puntos') @section('page_title','Movimientos de puntos')
@section('content')
<div class="glass-card p-5 overflow-x-auto"><table class="premium-table"><thead><tr><th>Usuario</th><th>Tipo</th><th>Cantidad</th><th>Saldo</th><th>Impacto</th><th>Referencia</th><th>Fecha</th></tr></thead><tbody>@forelse($rows as $row)<tr><td>{{ $row->usuario }}</td><td>{{ $row->tipo }}</td><td class="font-black {{ $row->cantidad >= 0 ? 'text-emerald-600':'text-red-600' }}">{{ $row->cantidad }}</td><td>{{ $row->saldo_anterior }} → {{ $row->saldo_nuevo }}</td><td>{{ $row->impacto_anterior }} → {{ $row->impacto_nuevo }}</td><td>{{ $row->referencia_tipo }} #{{ $row->referencia_id }}</td><td>{{ $row->created_at }}</td></tr>@empty<tr><td colspan="7" class="text-center py-10">No hay movimientos.</td></tr>@endforelse</tbody></table><div class="mt-5">{{ $rows->links() }}</div></div>
@endsection
