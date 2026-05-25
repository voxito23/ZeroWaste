<table>
    <thead>
        <tr>
            <th style="background-color:#064E3B; color:#fff; font-weight:bold; font-size:14px;" colspan="7">ZEROWASTE — {{ $titulo }}</th>
        </tr>
        <tr>
            <th style="background-color:#F0FDF4; font-weight:bold;">Generado</th>
            <th style="background-color:#F0FDF4;">{{ $fecha_generada }}</th>
            <th style="background-color:#F0FDF4; font-weight:bold;">Desde</th>
            <th style="background-color:#F0FDF4;">{{ $rango_inicio }}</th>
            <th style="background-color:#F0FDF4; font-weight:bold;">Hasta</th>
            <th style="background-color:#F0FDF4;">{{ $rango_fin }}</th>
            <th style="background-color:#F0FDF4; font-weight:bold;">Total: {{ $total }}</th>
        </tr>
        <tr><td colspan="7"></td></tr>
        <tr>
            @if($tipo === 'usuarios')
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">#</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Nombre</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Email</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Ubicación</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Rol</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Estado</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Registro</th>
            @elseif($tipo === 'campanas')
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">#</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Campaña</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Clasificación</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Descripción</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Lugar</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Estado</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Creada</th>
            @elseif($tipo === 'mapa')
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">#</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Punto</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Tipo</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Dirección</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Materiales</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Latitud</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Longitud</th>
            @elseif($tipo === 'eventos')
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">#</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Evento</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Tipo</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Ubicación</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Descripción</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Fecha Inicio</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Fecha Fin</th>
            @elseif($tipo === 'foro')
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">#</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Título Post</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Categoría</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Autor</th>
                <th style="background-color:#064E3B; color:#fff; font-weight:bold; padding:8px;">Fecha de Creación</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @forelse($registros as $idx => $item)
            <tr>
                @if($tipo === 'usuarios')
                    <td style="text-align:center; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->nombre }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->email }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->ubicacion ?? '—' }}</td>
                    <td style="font-weight:bold; color:{{ $item->is_admin ? '#6B21A8' : '#065F46' }}; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->is_admin ? 'Admin' : 'Usuario' }}</td>
                    <td style="font-weight:bold; color:{{ ($item->bloqueado ?? false) ? '#991B1B' : '#065F46' }}; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ ($item->bloqueado ?? false) ? 'Bloqueado' : 'Activo' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                @elseif($tipo === 'campanas')
                    <td style="text-align:center; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->nombre }}</td>
                    <td style="font-weight:bold; color:#1E40AF; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->tipo_etiqueta ?? 'General' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ mb_strimwidth($item->descripcion ?? '', 0, 60, '...') }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->lugar ?? '—' }}</td>
                    <td style="font-weight:bold; color:{{ ($item->activa ?? false) ? '#065F46' : '#991B1B' }}; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ ($item->activa ?? false) ? 'Activa' : 'Inactiva' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y') : '—' }}</td>
                @elseif($tipo === 'mapa')
                    <td style="text-align:center; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->nombre }}</td>
                    <td style="font-weight:bold; color:#92400E; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->tipo }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->direccion ?? '—' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->materiales ?? '—' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ number_format($item->latitud, 6) }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ number_format($item->longitud, 6) }}</td>
                @elseif($tipo === 'eventos')
                    <td style="text-align:center; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->titulo ?? $item->nombre ?? 'Evento' }}</td>
                    <td style="font-weight:bold; color:#6B21A8; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->tipo ?? 'General' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->ubicacion ?? $item->lugar ?? '—' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ mb_strimwidth($item->descripcion ?? '', 0, 50, '...') }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->fecha_inicio ? \Illuminate\Support\Carbon::parse($item->fecha_inicio)->format('d/m/Y H:i') : '—' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->fecha_fin ? \Illuminate\Support\Carbon::parse($item->fecha_fin)->format('d/m/Y H:i') : '—' }}</td>
                @elseif($tipo === 'foro')
                    <td style="text-align:center; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $idx + 1 }}</td>
                    <td style="font-weight:bold; color:#064E3B; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->titulo ?? 'Post' }}</td>
                    <td style="font-weight:bold; color:#059669; {{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->categoria->nombre ?? 'Sin Categoría' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->autor->nombre ?? 'Usuario Eliminado' }}</td>
                    <td style="{{ $idx % 2 === 0 ? '' : 'background-color:#F9FAFB;' }}">{{ $item->created_at ? \Illuminate\Support\Carbon::parse($item->created_at)->format('d/m/Y H:i') : '—' }}</td>
                @endif
            </tr>
        @empty
            <tr><td colspan="7" style="text-align:center; padding:20px; color:#9CA3AF;">Sin datos</td></tr>
        @endforelse
    </tbody>
</table>
