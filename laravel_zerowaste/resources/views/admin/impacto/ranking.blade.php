@extends('layouts.admin')
@section('title','Ranking de impacto') @section('page_title','Ranking de impacto')
@section('content')
<div class="glass-card p-5 overflow-x-auto"><table class="premium-table"><thead><tr><th>Posición</th><th>Usuario</th><th>Impacto histórico</th><th>Puntos disponibles</th></tr></thead><tbody>
@forelse($rows as $row)<tr><td class="font-black text-emerald-600">#{{ ($rows->currentPage()-1)*$rows->perPage()+$loop->iteration }}</td><td class="font-bold">{{ $row->nombre }}</td><td>{{ number_format($row->impacto_historico) }}</td><td>{{ number_format($row->puntos_disponibles) }}</td></tr>@empty<tr><td colspan="4" class="text-center py-10 text-gray-500">Aún no hay movimientos de impacto.</td></tr>@endforelse
</tbody></table><div class="mt-5">{{ $rows->links() }}</div></div>
@endsection
